// phpcs:ignoreFile
(function ($) {
    'use strict';

    $.fn.companySearch = function(options) {
        class PayeverCompanySearch {
            options = {
                selectorAddressFields: {
                    street: `input[name="${options.form}[${options.prefix}__oxstreet]"]`,
                    streetNumber: `input[name="${options.form}[${options.prefix}__oxstreetnr]"]`,
                    city: `input[name="${options.form}[${options.prefix}__oxcity]"]`,
                    zipcode: `input[name="${options.form}[${options.prefix}__oxzip]"]`,
                    countryId: `select[name="${options.form}[${options.prefix}__oxcountryid]"]`,
                    vatId: `input[name="${options.form}[${options.prefix}__oxvatid]"]`,
                    vatNumber: `input[name="${options.form}[${options.prefix}__oxustid]]"]`,
                    company: `input[name="${options.form}[${options.prefix}__oxcompany]"]`,
                    companyId: `input[name="${options.form}[${options.prefix}__oxexternalid]"]`,
                    phone: `input[name="${options.form}[${options.prefix}__oxfon]"]`,
                },
                selectorAutocomplete:'.payever-company-autocomplete',
                selectorAutocompletePopup: '.payever-company-autocomplete-popup',
                selectorCompanyInput: `input[name="${options.form}[${options.prefix}__oxcompany]"]`,
                selectorCountryCode: '#countries_selector_code',
                companySearchType: options.companySearchType,
                onlyCountries: options.onlyCountries || {},
                defaultCountry: options.defaultCountry
            }

            constructor(element, options) {
                // Init
                this.element = element;
                this.options = Object.assign(this.options, options);

                this.form = this.element.closest('form');
                if (!this.form.length) {
                    return;
                }

                // Init plugins
                this.initCountryPicker();
                this.initAutocompletePopup();
                this.initAutocompleteDropdown();

                // Init search
                this.element.on('keyup', () => this.search(1150));
                this.element.data('companySearch', this);
            }

            initCountryPicker() {
                if (this.options.companySearchType === 'dropdown' || this.options.companySearchType === 'mixed') {
                    this.countryPicker = this.element.companySearchCountryPicker({
                        countryIds: this.options.onlyCountries,
                        defaultCountry: this.options.defaultCountry,
                        onlyCountries: this.options.onlyCountries.iso2
                    });
                    this.countryPicker.setSelectCallback(this.search.bind(this));
                }
                if (this.options.companySearchType === 'popup') {
                    const countryCode = this.form.find(this.options.selectorCountryCode);
                    const countryId = this.form.find(this.options.selectorAddressFields.countryId);

                    countryCode.val(countryId.val());
                    countryId.on('change', (e) => {
                        countryCode.val(e.target.value)
                        this.search(1150);
                    });
                }
            }

            initAutocompleteDropdown() {
                let autocompleteElm = this.form.find(this.options.selectorAutocomplete);
                if (!autocompleteElm.length) {
                    autocompleteElm = $(`
                        <div class="payever-company-autocomplete" 
                            data-payever-company-autocomplete="true" 
                            data-payever-company-search-type=${this.options.companySearchType}
                        >
                            <div class="payever-company-autocomplete-loading">
                                <div class="payever-loading-animation">
                                    <div class="payever-loader"></div>
                                </div>
                            </div>
                            <ul class="payever-company-autocomplete-items"></ul>
                        </div>
                    `);

                    this.element.parent().after(autocompleteElm);
                }

                this.autocomplete = autocompleteElm.companySearchAutocomplete({
                    companySearchType: this.options.companySearchType
                });
                this.autocomplete.setAddressCallback(this.fillAddress.bind(this));
                this.autocomplete.setClearFieldsCallback(this.clearFields.bind(this));
                this.autocomplete.setCompanyIdRequiredCallback(this.makeCompanyIdRequired.bind(this));
            }

            initAutocompletePopup() {
                let autocompleteElm = this.form.find(this.options.selectorAutocompletePopup);
                if (!autocompleteElm.length) {
                    autocompleteElm = $(`
                         <div class="payever-company-autocomplete-popup">
                            <div class="payever-company-autocomplete-popup-items"></div>
                         </div>
                    `);

                    this.element.parent().after(autocompleteElm);
                }

                autocompleteElm.companySearchPopup({
                    form: options.form,
                    prefix: options.prefix,
                });
            }

            search(timeout = 0) {
                const searchValue = this.element.val().trim();
                if (searchValue.length < 3) {
                    this.currentTimer && window.clearTimeout(this.currentTimer);
                    this.autocomplete.abortLastApiRequest();

                    return;
                }

                if (this.currentTimer && this.getLastSearch !== searchValue) {
                    window.clearTimeout(this.currentTimer);
                    this.autocomplete.abortLastApiRequest();
                }

                let self = this;
                this.currentTimer = window.setTimeout(function () {
                    self.autocomplete.clearSearchItems();
                    self.autocomplete.showLoadingIndicator();
                    self.autocomplete.show();

                    // Get Country
                    const country = self.form.find(self.options.selectorCountryCode).val();
                    self.autocomplete.search(searchValue, country);

                    self.getLastSearch = searchValue;
                    self.currentTimer = null;
                }, timeout);
            }

            /**
             * Clear fields
             */
            clearFields() {
                this.clearCompanyId();
                this.makeCompanyIdOptional();
            }

            /**
             * Clear company ID field
             */
            clearCompanyId() {
                this.form.find(this.options.selectorAddressFields.companyId).val('');
            }

            /**
             * Make company id required
             */
            makeCompanyIdRequired() {
                this.form.find(this.options.selectorAddressFields.companyId).attr('required', 'required');
            }

            /**
             * Make company id optional
             */
            makeCompanyIdOptional() {
                this.form.find(this.options.selectorAddressFields.companyId).removeAttr('required');
            }

            /**
             * Fill address in default shopware fields
             * @param company{object}
             * @return void
             */
            fillAddress(company) {
                const fields = this.options.selectorAddressFields;
                const country = this.options.onlyCountries[company.address.country_code.toLowerCase()];

                this.setValue(fields.street, company.address.street_name);
                this.setValue(fields.streetNumber, company.address.street_number);
                this.setValue(fields.zipcode, company.address.post_code);
                this.setValue(fields.city, company.address.city);
                this.setValue(fields.company, company.name);
                this.setValue(fields.companyId, company.id);
                this.setValue(fields.vatId, company.vat_id);
                this.setValue(fields.vatNumber, company.vat_id);
                this.setValue(fields.phone, company.phone_number);
                this.setValue(fields.countryId, country, true);
            }

            /**
             * Fill value in field
             *
             * @param selector {string}
             * @param value {string}
             * @param change {boolean}
             *
             * @return void
             */
            setValue(selector, value, change = false) {
                if (value === undefined) {
                    return;
                }

                const field = this.form.find(selector);
                if (!field.length) {
                    return;
                }

                if (field.value === value) {
                    return;
                }

                field.val(value);

                if (change) {
                    field.trigger('change');
                }
            }

            getCompanyAddressString() {
                const address = ['street', 'city', 'zipcode', 'countryId']
                const addressString = [];

                address.forEach((value) => {
                    const field = this.form.find(this.options.selectorAddressFields[value]);
                    if (!field[0].checkVisibility()) {
                        return;
                    }

                    if (!field.val()?.trim()) {
                        return;
                    }

                    if (field[0].nodeName === 'SELECT') {
                        addressString.push(field[0].querySelector('option:checked').innerText.trim());
                        return;
                    }

                    addressString.push(field.val());
                });

                return addressString.join(', ');
            }

            isSameCompanySelected(company) {
                const fields = this.options.selectorAddressFields;
                const streetElement = this.form.find(fields.street);
                const zipElement = this.form.find(fields.zipcode);
                const cityElement = this.form.find(fields.city);
                const companyElement = this.form.find(fields.company);
                const countryIdElement = this.form.find(fields.countryId);

                const fullStreet = this.decodeAndMakeLowerCase(company.address.street_name)
                    + ' ' + this.decodeAndMakeLowerCase(company.address.street_number);

                if (this.decodeAndMakeLowerCase(streetElement.val()) !== fullStreet) {
                    return false;
                }

                if (this.decodeAndMakeLowerCase(zipElement.val()) !== this.decodeAndMakeLowerCase(company.address.post_code)) {
                    return false;
                }

                if (this.decodeAndMakeLowerCase(cityElement.val()) !== this.decodeAndMakeLowerCase(company.address.city)) {
                    return false;
                }

                if (this.decodeAndMakeLowerCase(companyElement.val()) !== this.decodeAndMakeLowerCase(company.name)) {
                    return false;
                }

                if (this.decodeAndMakeLowerCase(countryIdElement.val()) !== this.decodeAndMakeLowerCase(company.address.country_code)) {
                    return false;
                }

                return true;
            }

            decodeAndMakeLowerCase(value) {
                if (typeof value !== 'string') {
                    return '';
                }

                return decodeURI(value.toLowerCase());
            }
        }

        return new PayeverCompanySearch(this, options);
    };
})(jQuery);
