jQuery.widget('ui.autocomplete', jQuery.ui.autocomplete, {
    _renderItem: function (ul, item) {
        const li = jQuery("<li class='company-name'>");
        let content = jQuery("<a>").html(item.label);

        if (item.label.includes('payever-loading-animation')) {
            content = jQuery("<span>").html(item.label);
        }

        li.append(content);
        const address = item?.address;
        if (address?.street_name) {
            const line = `${address.street_number} ${address.street_name} ${address.city} ${address.post_code}`;
            li.append(jQuery("<span class='company-address'>").text(line));
        }

        return li.appendTo(ul);
    },
});

class CompanyAutocomplete {
    constructor(companyFields) {
        this.companyFields = companyFields;
        this.availableCountries = availableCountries;
        this.companyValidationMsg = companyValidationMsg;
        this.xhr = null;
        this.selectedItem = null;
        this.invalidCompanyId = '0000000000';

        this.init();
    }

    init() {
        this.companyFields.forEach(field => this.setupField(field));
    }

    setupField(field) {
        const companyElm = jQuery(`input[name="${field.form}[${field.prefix}__oxcompany]"]`);
        const countryElm = jQuery(`select[name="${field.form}[${field.prefix}__oxcountryid]"]`);
        const countryCodeElm = jQuery("input[name='country_code']");
        const externalIdElm = jQuery(`input[name="${field.form}[${field.prefix}__oxexternalid]"]`);
        const vatIdElm = jQuery(`input[name="${field.form}[${field.prefix}__oxvatid]"]`);

        this.initializeCountrySelect(companyElm, countryElm);
        this.setupAutocomplete(companyElm, countryElm, externalIdElm, vatIdElm, field);
        this.setupEventHandlers(companyElm, countryElm, countryCodeElm, externalIdElm, vatIdElm);
    }

    initializeCountrySelect(companyElm, countryElm) {
        const iso2 = this.availableCountries.iso2;
        let defaultCountry = this.getIsoCode(countryElm.val()) || iso2[0];

        countryElm.val(this.getCountryId(defaultCountry));
        companyElm.countrySelect({ onlyCountries: iso2 });
        companyElm.countrySelect("selectCountry", defaultCountry);
    }

    setupAutocomplete(companyElm, countryElm, externalIdElm, vatIdElm, field) {
        const bind = this; // Capture 'this' for use in inner functions
        companyElm.autocomplete({
            source: function (request, response) {
                if (!countryElm.val()) return;

                if (bind.xhr) bind.xhr.abort();

                bind.refreshAutocompleteSize();
                response([{
                    label: (
                        `<div id="payever-loading-animation" class="payever-loading-animation">
                            <div class="payever-loader">&nbsp;</div>
                        </div>`
                    )
                }]);
                bind.displayLoadingButton(companyElm);
                bind.removeValidationMsg();

                bind.xhr = jQuery.get('/index.php', {
                    cl: 'payevercompanysearch',
                    fnc: 'search',
                    company: request.term,
                    country: countryElm.val(),
                }, function (data) {
                    response(data);
                    bind.xhr = null;
                    bind.refreshAutocompleteSize();
                    bind.removeLoadingButton(companyElm);
                }).fail(function () {
                    bind.xhr = null;
                    bind.removeLoadingButton(companyElm);
                });
            },
            select: function (event, ui) {
                if (ui.item.id === bind.invalidCompanyId && ui.item.hasOwnProperty('company_identifier')) {
                    jQuery.get('/index.php', {
                        cl: 'payevercompanysearch',
                        fnc: 'retrieve',
                        value: ui.item.company_identifier.id_value,
                        type: ui.item.company_identifier.id_type_code,
                        country: ui.item.address?.country_code,
                    }, function (data) {
                        const retrieve = { item: data };
                        bind.selectedItem = retrieve;
                        bind.populateSelectedCompanyData(retrieve, companyElm, externalIdElm, vatIdElm, field);
                    });

                    return false;
                }

                bind.selectedItem = ui;
                bind.populateSelectedCompanyData(ui, companyElm, externalIdElm, vatIdElm, field);

                return false;
            },
            delay: 800,
            minLength: 2,
        }).focus(() => {
            if (!bind.selectedItem || bind.selectedItem?.company !== companyElm.value) {
                companyElm.autocomplete('search');
            }
        });
    }

    setupEventHandlers(companyElm, countryElm, countryCodeElm, externalIdElm, vatIdElm) {
        companyElm.on('input', () => this.clearCompanyData(externalIdElm, vatIdElm));
        companyElm.bind('paste', () => setTimeout(() => companyElm.autocomplete('search', companyElm.val()), 0));

        countryCodeElm.change(() => {
            this.clearCompanyData(externalIdElm, vatIdElm);
            countryElm.val(this.getCountryId(countryCodeElm.val()));
            countryElm.selectpicker('refresh');
            companyElm.autocomplete('search', companyElm.val());
        });

        companyElm.closest('form').on('submit', (e) => this.handleFormSubmit(e, companyElm, externalIdElm));
    }

    populateSelectedCompanyData(ui, companyElm, externalIdElm, vatIdElm, field) {
        companyElm.val(ui.item.name);
        externalIdElm.val(ui.item.id);
        vatIdElm.val(ui.item.vat_id);

        jQuery(`input[name="${field.form}[${field.prefix}__oxstreet]"]`).val(ui.item.address.street_name);
        jQuery(`input[name="${field.form}[${field.prefix}__oxstreetnr]"]`).val(ui.item.address.street_number);
        jQuery(`input[name="${field.form}[${field.prefix}__oxzip]"]`).val(ui.item.address.post_code);
        jQuery(`input[name="${field.form}[${field.prefix}__oxcity]"]`).val(ui.item.address.city);
        jQuery(`input[name="${field.form}[${field.prefix}__oxfon]"]`).val(ui.item.phone_number);
    }

    clearCompanyData(externalIdElm, vatIdElm) {
        this.removeValidationMsg();
        externalIdElm.val('');
        vatIdElm.val('');
    }

    handleFormSubmit(event, companyElm, externalIdElm) {
        if (companyElm.val() && !externalIdElm.val()) {
            event.preventDefault();
            this.addValidationMsg();
            $('html, body').animate({
                scrollTop: companyElm.offset().top - 100
            }, 100);
        }
    }

    displayLoadingButton(companyElm) {
        jQuery('.payever-button-loader').remove();
        const submitButton = companyElm.closest('form').find("button[type='submit'][name='userform']");
        submitButton.hide();

        const loadingButtonHtml = `
            <button type="button" class="btn btn-primary pull-right submitButton largeButton nextStep payever-button-loader">
                <div id="payever-loading-animation" class="payever-loading-animation">
                    <div class="payever-loader">&nbsp;</div>
                </div>
            </button>`;
        submitButton.after(loadingButtonHtml);
    }

    removeLoadingButton(companyElm) {
        companyElm.closest('form').find("button[type='submit'][name='userform']").show();
        jQuery('.payever-button-loader').remove();
    }

    addValidationMsg() {
        if (jQuery('#company-validation').length === 0) {
            const validationHtml = `<div id="company-validation">${this.companyValidationMsg}</div>`;
            jQuery('.country-select').after(validationHtml);
        }
    }

    removeValidationMsg() {
        jQuery('#company-validation').remove();
    }

    refreshAutocompleteSize() {
        jQuery('.ui-autocomplete').css('max-width', jQuery('.country-select').outerWidth() + 'px');
    }

    getIsoCode(countryId) {
        return Object.keys(this.availableCountries).find(isoCode => this.availableCountries[isoCode] === countryId) || '';
    }

    getCountryId(iso2) {
        return this.availableCountries[iso2] || '';
    }
}

new CompanyAutocomplete([{
    form: 'invadr',
    prefix: 'oxuser',
}]);
