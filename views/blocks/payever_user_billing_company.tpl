[{$smarty.block.parent}]
[{if $companySearch && $availableCountries}]
    <input type="hidden" name="invadr[oxuser__oxexternalid]" value="[{if isset( $invadr.oxuser__oxexternalid )}][{$invadr.oxuser__oxexternalid}][{else}][{$oxcmp_user->oxuser__oxexternalid->value}][{/if}]">
    <input type="hidden" name="invadr[oxuser__oxvatid]" value="[{if isset( $invadr.oxuser__oxvatid )}][{$invadr.oxuser__oxvatid}][{else}][{$oxcmp_user->oxuser__oxvatid->value}][{/if}]">
    <input type="hidden" name="country_code" id="countries_selector_code" value="">

    <script type="text/javascript">
        var onlyCountries = [{$availableCountries}];
        var companySearchType = '[{$companySearchType}]';
        var defaultCountry = '[{$defaultCountry}]';
        var companyValidationMsg = '[{ $oLang->translateString("PAYEVER_COMPANY_VALIDATION", $iLang, true) }]';
        var companySearchTransactions = {
            title: '[{ $oLang->translateString("PAYEVER_COMPANY_MODAL_TITLE", $iLang, true) }]',
            confirmationComment: '[{ $oLang->translateString("PAYEVER_COMPANY_MODAL_COMMENT", $iLang, true) }]',
            applyButton: '[{ $oLang->translateString("PAYEVER_COMPANY_MODAL_APPLY", $iLang, true) }]',
            confirmButton: '[{ $oLang->translateString("PAYEVER_COMPANY_MODAL_CONFIRM", $iLang, true) }]',
            backButton: '[{ $oLang->translateString("PAYEVER_COMPANY_MODAL_BACK", $iLang, true) }]',
            originalAddressTitle: '[{ $oLang->translateString("PAYEVER_COMPANY_MODAL_ORG_ADDRESS", $iLang, true) }]',
            newAddressTitle: '[{ $oLang->translateString("PAYEVER_COMPANY_MODAL_NEW_ADDRESS", $iLang, true) }]',
            discardButton: '[{ $oLang->translateString("PAYEVER_COMPANY_MODAL_DISCARD", $iLang, true) }]',
            yourEntryTitle: '[{ $oLang->translateString("PAYEVER_COMPANY_MODAL_ENTRY", $iLang, true) }]',
            company: '[{ $oLang->translateString("PAYEVER_COMPANY_MODAL_COMPANY", $iLang, true) }]',
            address: '[{ $oLang->translateString("PAYEVER_COMPANY_MODAL_ADDRESS", $iLang, true) }]',
        };
    </script>

    [{assign var="sModuleUrl" value=$oViewConf->getModuleUrl('payever')}]
    [{oxstyle include="`$sModuleUrl`out/src/css/company-search/address-autocomplete.css" priority=11}]
    [{oxstyle include="`$sModuleUrl`out/src/css/company-search/company-search-popup.css" priority=11}]
    [{oxstyle include="`$sModuleUrl`out/src/css/company-search/country-picker.css" priority=11}]
    [{oxstyle include="`$sModuleUrl`out/src/css/company-search/loading-animation.css" priority=11}]
    [{oxscript include="`$sModuleUrl`out/src/js/company-search/company_search.js" priority=11}]
    [{oxscript include="`$sModuleUrl`out/src/js/company-search/company_search_api.js" priority=11}]
    [{oxscript include="`$sModuleUrl`out/src/js/company-search/company_search_autocomplete.js" priority=11}]
    [{oxscript include="`$sModuleUrl`out/src/js/company-search/company_search_country_picker.js" priority=11}]
    [{oxscript include="`$sModuleUrl`out/src/js/company-search/company_search_popup.js" priority=11}]
    [{oxscript include="`$sModuleUrl`out/src/js/company-search/index.js" priority=11}]
[{/if}]
