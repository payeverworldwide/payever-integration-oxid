var companyFields = [
    {
        form: 'invadr',
        prefix: 'oxuser',
    }
];

jQuery.widget('ui.autocomplete', jQuery.ui.autocomplete, {
    showAjaxResult: function (items) {
        var bind = this;
        setTimeout(function () {
            bind._suggest(items);
            bind._trigger('open');
        }, 0);
    },
    _renderItem: function (ul, item) {
        var li = jQuery("<li class='company-name'>");
        var content = jQuery("<a>").html(item.label);

        if (item.label.includes('payever-loading-animation')) {
            content = jQuery("<span>").html(item.label);
        }

        li.append(content);
        
        if (item.street) {
            li.append(jQuery("<span class='company-address'>").text(item.address));
        }

        return li.appendTo(ul);
    },
});

var getIsoCode = function(countryId) {
    for (var isoCode in availableCountries) {
        if (availableCountries[isoCode] == countryId) {
            return isoCode;
        }
    }

    return '';
};

var getCountryId = function(iso2) {
    for (var isoCode in availableCountries) {
        if (isoCode == iso2) {
            return availableCountries[isoCode];
        }
    }

    return '';
};

var refreshAutocompleteSize = function() {
    jQuery('.ui-autocomplete').css('max-width', jQuery('.country-select').outerWidth() + 'px');
};

// Display Loading button
var displayLoadingButton = function (companyElm) {
    jQuery('.payever-button-loader').remove();
    let submitButton = companyElm.closest('form').find("button[type='submit'][name='userform']");

   
    submitButton.css('display', 'none');
    var loadingButtonHtml = `
        <button type="button" class="btn btn-primary pull-right submitButton largeButton nextStep  payever-button-loader">
            <div id="payever-loading-animation" class="payever-loading-animation">
                <div class="payever-loader">&nbsp;</div>
            </div>
        </button>
    `;
    submitButton.after(loadingButtonHtml);
};

// Hide Loading buttton
var removeLoadingButton = function (companyElm) {
    let submitButton = companyElm.closest('form').find("button[type='submit'][name='userform']");
    submitButton.css('display', 'block');
    jQuery('.payever-button-loader').remove();
};

// Hide company validation message
var addValidationMsg = function () {
    if (jQuery('#company-validation').length === 0) {
        var CompanyValidation = '<div id="company-validation">' + companyValidationMsg + '</div>';
        jQuery('.country-select').after(CompanyValidation);
    }
};

// Hide company validation message
var removeValidationMsg = function () {
    jQuery('#company-validation').remove();
};

companyFields.forEach(function (item) {
    var companyElm = jQuery(`input[name="${item.form}[${item.prefix}__oxcompany]"]`)
    var countryElm = jQuery(`select[name="${item.form}[${item.prefix}__oxcountryid]"]`)
    var countryCodeElm = jQuery("input[name='country_code']")
    var externalIdElm = jQuery(`input[name="${item.form}[${item.prefix}__oxexternalid]"]`)
    var vatIdElm = jQuery(`input[name="${item.form}[${item.prefix}__oxvatid]"]`)
    var xhr;

    companyElm.countrySelect({
        onlyCountries: availableCountries.iso2
    });

    var selectedIsoCode = getIsoCode(countryElm.val());
    var defaultCountry = selectedIsoCode != '' ? selectedIsoCode : availableCountries.iso2[0];
    companyElm.countrySelect("selectCountry", defaultCountry);

    companyElm.attr('autocomplete', 'new-password');

    companyElm.autocomplete({
        source: function (request, response) {
            if (!countryElm.val()) {
                return;
            }

            if (xhr) {
                xhr.abort()
            }
    
            refreshAutocompleteSize();
            response([{'label' : `<div id="payever-loading-animation" class="payever-loading-animation">
                    <div class="payever-loader">&nbsp;</div>
                </div>`}]);
            displayLoadingButton(companyElm);
            removeValidationMsg();
            xhr = jQuery.get('/index.php', {
                cl: 'payevercompanysearch',
                fnc: 'search',
                company: request.term,
                country: countryElm.val(),
            }, function (data) {
                response(data);
                xhr = false;
                refreshAutocompleteSize();
                removeLoadingButton(companyElm);
            }).fail(function (e) {
                xhr = false;
                removeLoadingButton(companyElm);
            });
        },
        response: function (event, ui) {
            var autocomplete = companyElm.data('autocomplete') || companyElm.data('ui-autocomplete');
            autocomplete.showAjaxResult(ui.content);
        },
        select: function (event, ui) {
            companyElm.val(ui.item.label);
            externalIdElm.val(ui.item.value);
            vatIdElm.val(ui.item.vatid);
            jQuery(`input[name="${item.form}[${item.prefix}__oxstreet]"]`).val(ui.item.street);
            jQuery(`input[name="${item.form}[${item.prefix}__oxstreetnr]"]`).val(ui.item.streetNumber);
            jQuery(`input[name="${item.form}[${item.prefix}__oxzip]"]`).val(ui.item.postcode);
            jQuery(`input[name="${item.form}[${item.prefix}__oxcity]"]`).val(ui.item.city);
            jQuery(`input[name="${item.form}[${item.prefix}__oxfon]"]`).val(ui.item.phone);

            return false;
        },
        delay: 800,
        minLength: 2,
    }).focus(function () {
        $(this).autocomplete('search');
    });

    companyElm.bind('paste', function () {
        setTimeout(function () {
            companyElm.autocomplete('search', companyElm.val());
        }, 0);
    });

    //Reset company data if company changed
    companyElm.on('input', function () {
        removeValidationMsg();
        externalIdElm.val('');
        vatIdElm.val('');
    });

    //Fire autocomplete if country flag is changed
    countryCodeElm.change(function () {
        externalIdElm.val('');
        vatIdElm.val('');
        countryElm.val(getCountryId(countryCodeElm.val()));
        countryElm.selectpicker('refresh');
        companyElm.autocomplete('search', companyElm.val());
        
    });

    //Company validation before submiting
    companyElm.closest('form').on('submit', function (e) {
            if (companyElm.val() && !externalIdElm.val()) {
                e.preventDefault();
                addValidationMsg();

                $('html, body').animate({
                    scrollTop: companyElm.offset().top - 100
                }, 100, function () {
                    // companyElm.focus();
                });
    
                return;
            }
            this.submit();
        });

});
