// phpcs:ignoreFile
(function ($) {
    'use strict';

    $.fn.companySearchAutocomplete = function(options) {
        class PayeverCompanySearchAutocomplete {
            options = {
                /**
                 * These elements will contain single suggestions.
                 * @type Node
                 */
                autocompleteItemsElement: null,

                /**
                 * This element will contain single suggestions for popup.
                 * @type Node
                 */
                autocompletePopupElement: null,

                /**
                 * Type of company search
                 * @type string
                 */
                companySearchType: 'mixed',

                /**
                 * Selector to find the loading indicator
                 * @type string
                 */
                selectorLoadingIndicator: '.payever-company-autocomplete-loading',

                /**
                 * Invalid company id
                 */
                invalidCompanyId: '0000000000',
            }

            constructor(element, options) {
                this.element = element;
                this.options = Object.assign(this.options, options);

                this.options.autocompleteItemsElement = $('.payever-company-autocomplete-items');
                this.options.autocompletePopupElement = $('.payever-company-autocomplete-popup-items');

                this.payeverApiClient = new PayeverApiClient();

                this.element.data('companySearchAutocomplete', this);
            }

            /**
             * Set the address callback. Will be fired if the user select a specific address
             *
             * @param addressCallback{function(addressCallback)}
             */
            setAddressCallback(addressCallback) {
                this.addressCallback = addressCallback;
            }

            /**
             * Set the clear fields callback
             *
             * @param clearFieldsCallback{function()}
             */
            setClearFieldsCallback(clearFieldsCallback) {
                this.clearFieldsCallback = clearFieldsCallback;
            }

            /**
             * Set the company id required callback
             *
             * @param companyIdRequiredCallback{function()}
             */
            setCompanyIdRequiredCallback(companyIdRequiredCallback) {
                this.makeCompanyIdRequiredCallback = companyIdRequiredCallback;
            }

            /**
             * Start the search for an address
             *
             * @param needle{string}
             * @param country{string}
             */
            search(needle, country) {
                this.abortLastApiRequest();

                this.searchTimeoutId = window.setTimeout(() => {
                    // prepare autocomplete dropdown for new search
                    this.clearSearchItems();
                    this.showLoadingIndicator();
                    this.show();

                    const callback = {
                        success: ((response) => {
                            if (!response.length) {
                                if (this.isPopup() && this.clearFieldsCallback) {
                                    this.clearFieldsCallback();
                                }

                                return;
                            }

                            response.forEach((value, i) => {
                                if (value.id === this.options.invalidCompanyId) {
                                    value.id += '_' + i;
                                }

                                if (this.isDropdown()) {
                                    this.addSearchItem(value);
                                }

                                if (this.isPopup()) {
                                    this.addSearchItemForPopup(value);
                                }
                            });

                            if (this.isPopup() && this.clearFieldsCallback) {
                                this.clearFieldsCallback();
                            }

                            if (this.makeCompanyIdRequiredCallback) {
                                this.makeCompanyIdRequiredCallback();
                            }
                        }),
                        hideLoadingIndicator: this.hideLoadingIndicator.bind(this),
                        showLoadingIndicator: this.showLoadingIndicator.bind(this),
                    };

                    // fire request to api
                    this.payeverApiClient.companyFind(needle, country, callback);
                }, 50);
            }

            /**
             * @param identifier{string}
             * @param type{string}
             * @param country{string}
             * @param callback
             * @returns {XMLHttpRequest}
             */
            retrieveCompany(identifier, type, country, callback) {
                // prepare autocomplete dropdown for new search
                this.clearSearchItems();
                this.showLoadingIndicator();
                this.show();

                return this.payeverApiClient.companyRetrieve(
                    identifier,
                    type,
                    country,
                    {
                        success: callback,
                        hideLoadingIndicator: this.hideLoadingIndicator.bind(this),
                        showLoadingIndicator: this.showLoadingIndicator.bind(this),
                    },
                );
            }

            /**
             * @param item{object}
             * @param success{function()}
             */
            retrieveAddressDetails(item, success) {
                this.clearSearchItems();
                this.showLoadingIndicator();
                this.show();

                this.payeverApiClient.addressRetrieve(
                    item,
                    {
                        success,
                        hideLoadingIndicator: this.hideLoadingIndicator.bind(this),
                        showLoadingIndicator: this.showLoadingIndicator.bind(this),
                    },
                );
            }

            /**
             * Abort a running search
             */
            abortLastApiRequest() {
                // clear the input timeout
                clearTimeout(this.searchTimeoutId);
            }

            /**
             * Add a search item to the autocomplete dropdown. The item contains one of the following keys:
             *
             * @param item{object}
             * @return void
             */
            addSearchItem(item) {
                const typeAsClass = item.Type === 'Address' ? 'is-single' : 'is-group';
                const itemId = 'search_company_' + item.id;

                // Add item to DOM
                // phpcs:disable
                const itemElement = $(`
                    <li class="payever-company-autocomplete-item ${typeAsClass}" id="${itemId}"> 
                        <a class="payever-company-autocomplete-item-link ${typeAsClass}" href="javascript:void(0)">
                            ${item.name}
                            &#160;-&#160;
                            <span class="payever-company-autocomplete-item-link-secondary-text">
                                ${item.address.street_name} ${item.address.street_number}, ${item.address.post_code}, ${item.address.city} 
                            </span>
                        </a>
                    </li>
                `);
                // phpcs:enable
                this.options.autocompleteItemsElement.append(itemElement);

                // Add click event in the added item
                let self = this;
                itemElement.on('click', function (event) {
                    event.stopPropagation();
                    self.handleSearchItemClick(item);
                })
            }

            addSearchItemForPopup(item) {
                const itemId = 'search_company_for_popup_' + item.id;
                const companySearchItem = 'company_search_item_' + item.id;
                const itemJson = JSON.stringify(item);

                // Add item to DOM
                const template = $(`
                    <label class="download-links-wrapper">
                        <div class="download-links">
                            <div class="download-buttons">
                                <div class="payever-mark" id="${itemId}" data-id="${item.id}"></div>
        
                                <div class="payever-company-address-block">
                                    <div class="payever-company-data" id="${companySearchItem}" data-value='${itemJson}' />
                                    <div class="payever-company-title">${item.name}</div>
                                    <div class="payever-company-address">
                                        ${item.address.street_name} ${item.address.street_number}, ${item.address.post_code}, ${item.address.city}
                                    </div>
                                </div>
                            </div>
                        </div>
                    </label>
                `);

                this.options.autocompletePopupElement.append(template);
            }

            /**
             * Handle a click on a search item in the autocomplete dropdown
             * @param item{object}
             * @private
             */
            handleSearchItemClick(item) {
                if (item.id.indexOf(this.options.invalidCompanyId) !== -1 && typeof item.company_identifier !== "undefined") {
                    item.id = this.options.invalidCompanyId;
                    const self = this;
                    this.retrieveCompany(
                        item.company_identifier.id_value,
                        item.company_identifier.id_type_code,
                        item.address.country_code,
                        function (newItem) {
                            self.retrieveAddressDetails(newItem, (address) => {
                                self.addressCallback(address.data);
                                self.hide();
                            })
                        }.bind(self)
                    );

                    return;
                }

                this.retrieveAddressDetails(item, (address) => {
                    this.addressCallback(address);
                    this.hide();
                });
            }

            /**
             * Remove all search result items
             */
            clearSearchItems() {
                let elements = $(this.options.autocompleteItemsElement).find('li');
                if (elements.length) {
                    elements.each((key, element) => element.remove());
                }

                let elementsForPopup = $(this.options.autocompletePopupElement).find('label');
                if (elementsForPopup.length) {
                    elementsForPopup.each((key, element) => element.remove());
                }
            }

            /**
             * Show autocomplete dropdown
             * @return void
             */
            show() {
                if (this.isDropdown()) {
                    this.element.css('display', 'block');
                }
            }

            /**
             * Hide autocomplete dropdown
             * @return void
             */
            hide() {
                this.element.css('display', 'none');
            }

            /**
             * Show/Hide the loading indicator
             */
            showLoadingIndicator() {
                if (this.isDropdown()) {
                    const indicator = $(this.element).find(this.options.selectorLoadingIndicator);
                    if (indicator.length) {
                        indicator.css('display', 'block');
                    }
                }

                if (this.isPopup()) {
                    const formButton = $(this.element.closest('form')).find('[type*=submit].nextStep');
                    if (formButton.length) {
                        const display = formButton.css('display');
                        formButton.css('display', 'none');

                        const parentBlock = formButton.closest('div.cart-buttons');
                        let loader = $(parentBlock).find('.fake-button-for-animation');
                        if (!loader.length) {
                            loader = formButton.first().clone();
                            loader.empty();
                            const animation = $(`
                                <div class="payever-loading-animation"><div class="payever-loader-white"></div></div>
                            `);
                            loader.append(animation);
                            loader.addClass('fake-button-for-animation');
                            loader.css('display', display);
                            loader.attr('disabled', true);
                            loader.removeAttr("type").attr("type", "button");

                            parentBlock.prepend(loader);
                            loader.show();
                        }
                    }
                }
            }

            hideLoadingIndicator() {
                const indicator = $(this.element).find(this.options.selectorLoadingIndicator);
                if (indicator.length) {
                    indicator.css('display', 'none');
                }

                const formButton = $(this.element.closest('form')).find('[type*=submit]');
                if (formButton.length) {
                    const parentBlock = formButton.closest('div');
                    const loader = $(parentBlock).find('.fake-button-for-animation');
                    if (loader.length) {
                        loader.remove();
                    }

                    formButton.css('display', 'block');
                }
            }

            /**
             * Checks if company search popup type is active
             */
            isPopup() {
                return (this.options.companySearchType === 'popup' || this.options.companySearchType === 'mixed');
            }

            /**
             * Checks if company search dropdown type is active
             */
            isDropdown() {
                return (this.options.companySearchType === 'dropdown' || this.options.companySearchType === 'mixed');
            }
        }

        return new PayeverCompanySearchAutocomplete(this, options);
    };
})(jQuery);
