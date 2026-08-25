[{$smarty.block.parent}]
[{if $oView->getPaymentList()}]
    [{ assign var="payment_terms" value=$oViewConf->getTermsData($oView->getPaymentList()) }]
    [{if $payment_terms}]
        <script type="text/javascript">var payment_terms = [{ $payment_terms|@json_encode }]</script>
        [{assign var="sModuleUrl" value=$oViewConf->getModuleUrl('payever')}]
        [{oxscript include="`$sModuleUrl`out/src/js/terms.js" priority=11}]
    [{/if}]
[{/if}]
