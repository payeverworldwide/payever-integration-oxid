[{* PAYEVER BEGIN *}]
[{ assign var="dt_payever_logo" value=' style="height:30px; vertical-align: middle;"'}]
[{ assign var="payment_desc" value=$oViewConf->displayPaymentDesc() }]
[{ assign var="payment_mode" value=$oViewConf->getModeTitle() }]
[{if $sPaymentID|strstr:"oxpe_" }]
<div class="well well-sm">
    <dl>
        <dt>
            <input style="vertical-align: middle;" id="payment_[{$sPaymentID}]" type="radio" name="paymentid" value="[{$sPaymentID}]" [{if $oView->getCheckedPaymentId() == $paymentmethod->oxpayments__oxid->value}]checked[{/if}]>
            [{if $payment_desc.payever.display_logo eq 1}]
                <span class="payever-payment-icon">
                    [{if $paymentmethod->oxpayments__oxthumbnail->value|count_characters > 0}]
                        <img src="[{$paymentmethod->oxpayments__oxthumbnail->value}]" alt="[{ $paymentmethod->oxpayments__oxdesc->value }]"[{$dt_payever_logo}]/>
                    [{else}]
                        <img src="[{$oViewConf->getModuleUrl('payever','out/src/img/')}][{if $sPaymentID|strpos:"-"}][{$sPaymentID|strstr:"-":true}][{else}][{$sPaymentID}][{/if}].png" alt="[{ $paymentmethod->oxpayments__oxdesc->value }]"[{$dt_payever_logo}]/>
                    [{/if}]
                </span>
            [{/if}]
            <label for="payment_[{$sPaymentID}]"><b>[{ $paymentmethod->oxpayments__oxdesc->value }] [{if $payment_mode eq 0}]SANDBOX Mode[{/if}]</b>
            [{if $paymentmethod->getPrice()}]
                [{assign var="oPaymentPrice" value=$paymentmethod->getPrice() }]
                ([{$oPaymentPrice->getBruttoPrice()|number_format:2:".":","}] [{ $currency->sign}])
            [{/if}]
            </label>
        </dt>
    <dd class="[{if $oView->getCheckedPaymentId() == $paymentmethod->oxpayments__oxid->value}]activePayment[{/if}]">
        [{block name="checkout_payment_longdesc"}]
            [{if $paymentmethod->oxpayments__oxlongdesc->value && $payment_desc.payever.display_desc eq 1}]
                <div class="desc payever-payment-description">
                    [{ $paymentmethod->oxpayments__oxlongdesc->value }]
                </div>
            [{/if}]
        [{/block}]
    </dd>
    </dl>
</div>
[{else}]
    [{$smarty.block.parent}]
[{/if}]
