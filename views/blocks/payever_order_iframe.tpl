[{if $oView->isIframePayeverPayment() == false}]
[{$smarty.block.parent}]
[{else}]

<iframe sandbox="allow-same-origin allow-forms allow-top-navigation allow-scripts allow-popups allow-popups-to-escape-sandbox" frameborder="0" width="100%"  style="min-height: 600px" id="payever_iframe" src="[{$oView->getIframePaymentUrl()}]"></iframe>

<div id="basketContent" class="lineBox clear">
    [{block name="checkout_order_next_step_bottom"}]
        [{if $oView->isLowOrderPrice()}]
            [{block name="checkout_order_loworderprice_bottom"}]
                <div>[{oxmultilang ident="MIN_ORDER_PRICE"}] [{$oView->getMinOrderPrice()}] [{$currency->sign}]</div>
            [{/block}]
        [{else}]
            [{block name="checkout_order_btn_confirm_bottom"}]
                <form action="[{$oViewConf->getSslSelfLink()}]" method="post" id="orderConfirmAgbBottom">
                    [{$oViewConf->getHiddenSid()}]
                    [{$oViewConf->getNavFormParams()}]
                    <input type="hidden" name="cl" value="order">
                    <input type="hidden" name="fnc" value="[{$oView->getExecuteFnc()}]">
                    <input type="hidden" name="challenge" value="[{$challenge}]">
                    <input type="hidden" name="sDeliveryAddressMD5" value="[{$oView->getDeliveryAddressMD5()}]">

                    <a href="[{oxgetseourl ident=$oViewConf->getPaymentLink()}]&clearIframeSession=true" class="prevStep submitButton largeButton">[{oxmultilang ident="PREVIOUS_STEP"}]</a>
                </form>
            [{/block}]
        [{/if}]
    [{/block}]
</div>
    [{$oView->clearIframeSession()}]
    <script type="text/javascript">
        function sendCheckoutNewScrollOffset() {
            var iframe = document.getElementById('payever_iframe');
            if (iframe) {
                var scrollTop = window.pageYOffset || document.documentElement.scrollTop;
                var offsetTop = iframe.offsetTop;
                iframe.contentWindow.postMessage({
                    'event': 'sendPayeverCheckoutScrollOffset',
                    'scrollTop':    scrollTop,
                    'offsetTop':    offsetTop,
                    'windowHeight': window.innerHeight
                }, "*");
            }
        }

        if (window.addEventListener) {
            window.addEventListener("message", onMessagePayever, false);
            window.addEventListener('scroll', sendCheckoutNewScrollOffset, false);
            window.addEventListener('resize', sendCheckoutNewScrollOffset, false);
        } else if (window.attachEvent) {
            window.attachEvent("onmessage", onMessagePayever, false);
            window.attachEvent('onscroll', sendCheckoutNewScrollOffset, false);
            window.attachEvent('onresize', sendCheckoutNewScrollOffset, false);
        }

        function onMessagePayever(event) {
            if (event && event.data) {
                switch (event.data.event) {
                    case 'payeverCheckoutHeightChanged':
                        var value = Math.max(0, parseInt(event.data.value));
                        document.getElementById('payever_iframe').style.height = value+"px";
                        break;
                    case 'payeverCheckoutScrollOffsetRequested':
                        sendCheckoutNewScrollOffset();
                }
            }
        }
    </script>
[{/if}]
