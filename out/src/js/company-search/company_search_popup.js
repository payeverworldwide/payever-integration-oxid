(function ($) {
    'use strict';

    $.fn.companySearchPopup = function(options) {
        const companySearchPopup = {
            options: {
                selectorCompanyInput: `input[name="${options.form}[${options.prefix}__oxcompany]"]`,
                selectorCompanySearchRadio: 'div.payever-mark',
                selectorCompanySearchCheckedRadio: 'div.payever-mark.selected',
                selectorApplyCompany: '[name*=apply_company]',
                selectorBackDialog: '[name*=back_dialog]',
                selectorDiscardCompany: '[name*=discard_company]',
                selectorCloseModal: '.close-modal',
                translations: window.companySearchTransactions || {},
            },

            init(elm) {
                this.$el = elm;
                this.form = this.$el.closest('form');
                if (!this.form.length) return;

                this.registerDomEvents();
            },

            registerDomEvents() {
                this.form.on('submit', this.openModal.bind(this));
            },

            openModal(event) {
                if (!this.canOpenModal()) {
                    return;
                }

                const companies = this.getCompanySearchContentElement().html().trim();
                if (!companies) {
                    return;
                }

                event.preventDefault();
                $('.js-pseudo-modal-template').remove();

                this.getCompanySearchPlugin().clearCompanyId();

                const content = $(`
                    <div class="js-pseudo-modal-template">
                        <div class="modal fade" id="company-search-modal" tabindex="-1" role="dialog">
                            <div class="modal-dialog payever-modal-dialog" role="document">
                                ${this.buildSelectDialog(companies)}
                                ${this.buildConfirmDialog()}
                            </div>
                        </div>
                    </div>
                `);

                //Show popup modal
                $('body').append(content);

                const searchModalElement = $('#company-search-modal');
                searchModalElement.modal('show');
                searchModalElement.on('shown.bs.modal', this.onOpenModal.bind(this))
            },

            buildSelectDialog(companies) {
                return `
                    <div class="modal-content payever-company-selection-modal select-dialog active-dialog" data-index="select">
                        <div class="selection-modal-header">
                            <div class="xmark">
                                <span>${this.options.translations.title}</span>
                                ${this.getCloseButtonSvg()}
                            </div>
                        </div>
                        <div class="payever-company-input">${companies}</div>
                        <div class="apply-button-parent">
                            <div class="apply-button">
                                <button name="apply_company" class="apply-options" data-next-dialog="confirm" disabled>
                                    <div class="apply">${this.options.translations.applyButton}</div>
                                </button>
                                <button name="discard_company" class="apply-options1">
                                    <div class="i-did-not-container">
                                        <span>${this.options.translations.discardButton}</span>
                                    </div>
                                </button>
                            </div>
                            <div class="selection-modal-header current-company-info-block-header">
                                <div class="xmark">
                                    <span>${this.options.translations.yourEntryTitle}</span>
                                </div>
                            </div>
                            <div class="payever-company-input">${this.getCurrentCompanyTemplatePart()}</div>
                            <div class="payever-icon"></div>
                        </div>
                    </div>`;
            },

            buildConfirmDialog() {
                return `
                    <div class="modal-content payever-company-selection-modal confirm-dialog" data-index="confirm">
                        <div class="selection-modal-header">
                            <div class="xmark">
                                <span>${this.options.translations.originalAddressTitle}</span>
                                ${this.getCloseButtonSvg()}
                            </div>
                        </div>
                        <div class="payever-company-input original-address-confirmation">
                            <label class="download-links-wrapper">
                                <div class="download-links">
                                    <div class="download-buttons">
                                        <input disabled type="radio" id="search_company_for_popup_original" name="search_company_id" value="" class="radio-btn">
                                        <div class="payever-mark"></div>
                                        <div class="payever-company-address-block">
                                            <input type="hidden" id="company_search_item_original" name="company_search_item_original" value="">
                                            <div class="payever-company-title"></div>
                                            <div class="payever-company-address"></div>
                                        </div>
                                    </div>
                                </div>
                            </label>
                        </div>
                        <div class="selection-modal-header">
                            <div class="xmark">
                                <span>${this.options.translations.newAddressTitle}</span>
                            </div>
                        </div>
                        <div class="payever-company-input new-address-confirmation">
                            <label class="download-links-wrapper">
                                <div class="download-links">
                                    <div class="download-buttons"></div>
                                </div>
                            </label>
                            <p class="comment">
                                <span>${this.options.translations.confirmationComment}</span>
                            </p>
                        </div>
                        <div class="apply-button-parent">
                            <div class="apply-button">
                                <button name="apply_company" class="apply-options">
                                    <div class="apply">${this.options.translations.confirmButton}</div>
                                </button>
                                <button name="back_dialog" class="back-button" data-next-dialog="select">
                                    <div class="i-did-not-container">
                                        <span>${this.options.translations.backButton}</span>
                                    </div>
                                </button>
                                <div class="payever-icon"></div>
                            </div>
                        </div>
                    </div>`;
            },

            getCurrentCompanyTemplatePart() {
                const company = this.getCompanyInputElement().val();
                const address = this.getCompanySearchPlugin().getCompanyAddressString();

                return `
                    <ul class="current-entry">
                        <li class="current-entry-item">
                            <div class="current-entry-item-label">${this.options.translations.company}</div>
                            <div class="current-entry-item-value">${company}</div>
                        </li>
                        <li class="current-entry-item">
                            <div class="current-entry-item-label">${this.options.translations.address}</div>
                            <div class="current-entry-item-value">${address}</div>
                        </li>
                    </ul>`;
            },

            getCloseButtonSvg() {
                return `
                    <svg class="close-modal" xmlns="http://www.w3.org/2000/svg" width="15" height="15" viewBox="0 0 15 15" fill="none">
                        <path d="M1.1045 12.4728C0.735361 12.8419 0.717783 13.5011 1.11329 13.8878C1.50001 14.2745 2.15919 14.2658 2.52833 13.8966L7.90724 8.50892L13.2949 13.8966C13.6729 14.2745 14.3233 14.2745 14.71 13.8878C15.0879 13.4923 15.0967 12.8507 14.71 12.4728L9.33106 7.08509L14.71 1.70619C15.0967 1.32826 15.0967 0.677866 14.71 0.291147C14.3145 -0.0867825 13.6729 -0.0955716 13.2949 0.282358L7.90724 5.67005L2.52833 0.282358C2.15919 -0.0867825 1.49122 -0.104361 1.11329 0.291147C0.726572 0.677866 0.735361 1.33705 1.1045 1.70619L6.4922 7.08509L1.1045 12.4728Z" fill="black"/>
                    </svg>`;
            },

            onOpenModal() {
                const modal = $('#company-search-modal');
                const applyBtns = modal.find(this.options.selectorApplyCompany);
                const discardBtn = modal.find(this.options.selectorDiscardCompany);
                const backBtn = modal.find(this.options.selectorBackDialog);

                this.getCompanySearchAutocompletePlugin().hide();

                modal.find(this.options.selectorCompanySearchRadio).on('click', (e) => {
                    applyBtns.removeAttr('disabled');
                    $(this.options.selectorCompanySearchCheckedRadio).removeClass('selected');
                    $(e.target).addClass('selected');
                });

                applyBtns.each((_, applyBtn) => {
                    $(applyBtn).on('click', () => this.handleApply(modal, $(applyBtn)));
                });

                discardBtn.on('click', () => {
                    this.getCompanySearchPlugin().makeCompanyIdOptional();
                    modal.modal('hide');
                    this.form[0].submit();
                });

                backBtn.on('click', () => {
                    const nextDialog = backBtn.data('next-dialog');
                    if (nextDialog) {
                        this.showDialog(modal, nextDialog);
                    }
                });

                modal.find(this.options.selectorCloseModal).on('click', () => modal.modal('hide'));
            },

            handleApply(modal, applyBtn) {
                const selectedRadio = modal.find(this.options.selectorCompanySearchCheckedRadio);
                if (!selectedRadio.length) return;

                let companyItem = false;
                const selectedCompanyElement = selectedRadio.parent().find('div.payever-company-data');

                if (selectedCompanyElement.length) {
                    companyItem = selectedCompanyElement.data('value');

                    if (companyItem.id.startsWith('0000000000') && companyItem.company_identifier) {
                        this.showLoadingState(applyBtn);

                        this.getCompanySearchAutocompletePlugin().retrieveCompany(
                            companyItem.company_identifier.id_value,
                            companyItem.company_identifier.id_type_code,
                            companyItem.address.country_code,
                            (newItem) => {
                                newItem.address.country_code = companyItem.address.country_code;
                                this.replaceCompanyItem(newItem, selectedRadio, selectedCompanyElement, applyBtn);
                                this.handleNextDialog(modal, applyBtn, newItem, selectedRadio);
                            }
                        );
                        return;
                    }
                }

                if (!this.handleNextDialog(modal, applyBtn, companyItem, selectedRadio)) return;

                this.getCompanySearchPlugin().makeCompanyIdOptional();
                if (companyItem) {
                    this.getCompanySearchPlugin().fillAddress(companyItem);
                    this.getCompanySearchContentElement().html('');
                }

                modal.modal('hide')
                this.form[0].submit();
            },

            showLoadingState(applyBtn) {
                const buttonText = applyBtn.find('.apply');
                buttonText.hide();

                const animation = document.createRange().createContextualFragment(`
                    <div class="payever-loading-animation"><div class="payever-loader-white"></div></div>
                `);
                applyBtn.append(animation);
                applyBtn.prop('disabled', true);
            },

            replaceCompanyItem(newItem, radio, input, applyBtn) {
                radio.attr('id', `search_company_for_popup_${newItem.id}`);
                input.data('value', newItem);

                applyBtn.find('.payever-loading-animation').remove();
                applyBtn.prop('disabled', false);
                applyBtn.find('.apply').show();
            },

            handleNextDialog(modal, applyBtn, item, selectedRadio) {
                const nextDialog = applyBtn.data('next-dialog');
                if (nextDialog) {
                    if (nextDialog === 'confirm' && !this.getCompanySearchPlugin().isSameCompanySelected(item)) {
                        const container = modal.find('.new-address-confirmation .download-buttons');
                        container.html(selectedRadio.closest('.download-buttons').html());

                        const newRadio = container.find(this.options.selectorCompanySearchRadio);
                        newRadio.attr({ name: 'confirmed-input-name', id: 'confirmed_input_id', checked: true });

                        modal.find('.original-address-confirmation .payever-company-title')
                            .html(this.getCompanyInputElement().val());

                        modal.find('.original-address-confirmation .payever-company-address')
                            .html(this.getCompanySearchPlugin().getCompanyAddressString());
                    }

                    if (nextDialog !== 'confirm' || !this.getCompanySearchPlugin().isSameCompanySelected(item)) {
                        this.showDialog(modal, nextDialog);

                        return false;
                    }
                }

                return true;
            },

            showDialog(modal, type) {
                const dialogs = modal.find('.payever-company-selection-modal');
                dialogs.each((_, dialog) => {
                    dialog.classList.remove('active-dialog');
                    if ($(dialog).data('index') === type) {
                        dialog.classList.add('active-dialog');
                    }
                });
            },

            onCloseModal() {
                console.log('the modal was closed');
            },

            canOpenModal() {
                const fields = this.form.find(`input[name*="invadr"], select[name*="invadr"], textarea[name*="invadr"]`);
                const invalidFields = [];
                fields.each((_, item) => {
                    if (!item.checkValidity()) {
                        invalidFields.push(item.name);
                    }
                });

                return invalidFields.length === 0;
            },

            getCompanySearchContentElement() {
                return this.form.find('.payever-company-autocomplete-popup-items');
            },

            getCompanyInputElement() {
                return this.form.find(this.options.selectorCompanyInput);
            },

            getCompanySearchPlugin() {
                return this.form.find(`input[name="${options.form}[${options.prefix}__oxcompany]"]`).data('companySearch');
            },

            getCompanySearchAutocompletePlugin() {
                return this.form.find('.payever-company-autocomplete').data('companySearchAutocomplete');
            },
        };

        companySearchPopup.init(this);

        return companySearchPopup;
    };
})(jQuery);
