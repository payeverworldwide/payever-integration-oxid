[{$smarty.block.parent}]
[{if $companySearch && $availableCountries}]
    <input type="hidden" name="invadr[oxuser__oxexternalid]" value="[{if isset( $invadr.oxuser__oxexternalid )}][{$invadr.oxuser__oxexternalid}][{else}][{$oxcmp_user->oxuser__oxexternalid->value}][{/if}]">
    <input type="hidden" name="invadr[oxuser__oxvatid]" value="[{if isset( $invadr.oxuser__oxvatid )}][{$invadr.oxuser__oxvatid}][{else}][{$oxcmp_user->oxuser__oxvatid->value}][{/if}]">
    <input type="hidden" name="country_code" value="">

    <script type="text/javascript">
        var availableCountries = [{$availableCountries}];
        var companyValidationMsg = '[{ $oLang->translateString("PAYEVER_COMPANY_VALIDATION", $iLang, true) }]';
    </script>

    [{assign var="sModuleUrl" value=$oViewConf->getModuleUrl('payever')}]
    [{oxstyle include="`$sModuleUrl`out/src/css/loader.css" priority=11}]
    [{oxstyle include="`$sModuleUrl`out/src/css/payever-company.css" priority=11}]
    [{oxscript include="`$sModuleUrl`out/src/js/country-picker.js" priority=11}]
    [{oxscript include="`$sModuleUrl`out/src/js/payever-company.js" priority=11}]
[{/if}]