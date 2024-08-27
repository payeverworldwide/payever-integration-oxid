[{capture append="oxidBlock_content"}]

    [{block name="checkout_thankyou_main"}]
        [{assign var="basket" value=$oView->getBasket()}]
        <div id="thankyouPage">
            <h3 class="blockHead">[{oxmultilang ident="THANK_YOU"}]</h3>

            [{block name="checkout_thankyou_info"}]
                <p>[{oxmultilang ident="THANK_YOU_FOR_ORDER"}] [{$oxcmp_shop->oxshops__oxname->value}].</p>
                <br>
                <p>[{$oLang->translateString("PAYEVER_PAYMENT_PENDING_RECEIVED", $iLang, true)}]</p>
                <p>[{$oLang->translateString("PAYEVER_PAYMENT_PENDING_PROCESSED", $iLang, true)}]</p>
                <p>[{$oLang->translateString("PAYEVER_PAYMENT_PENDING_CONFIRMATION", $iLang, true)}]</p>
                <br>
            [{/block}]

            <div class="order-status-loader">
                <p id="payever-status-message">[{$oLang->translateString("PAYEVER_PAYMENT_LOADER_MSG", $iLang, true)}]</p>
                <div id="payever-loading-animation" class="payever-loading-animation">
                    <div class="payever-loader">&nbsp;</div>
                </div>
            </div>
        </div>
    [{/block}]
    [{insert name="oxid_tracker" title=$template_title}]

    [{assign var="sPayeverCssPath" value=$oViewConf->getModuleUrl('payever', 'out/src/css/loader.css')}]
    <link rel="stylesheet" href="[{$sPayeverCssPath}]" type="text/css" />

    [{oxscript include=$oViewConf->getModuleUrl('payever', 'out/src/js/status.js')}]
    <script type="application/javascript">
        document.addEventListener('DOMContentLoaded', function () {
            window.PayeverPendingStatusChecker.checkStatus('[{$checkStatusLink}]');
        });
    </script>
[{/capture}]
[{include file="layout/page.tpl"}]
