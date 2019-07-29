[{if $oViewConf->getActiveTheme() != 'mobile' }]
<form action="[{$oViewConf->getSslSelfLink()}]" method="post" id="orderConfirmAgbBottom">
	[{$oViewConf->getHiddenSid()}]
    [{$oViewConf->getNavFormParams()}]
    <input type="hidden" name="cl" value="order">
    <input type="hidden" name="fnc" value="[{$oView->getExecuteFnc()}]">
    <input type="hidden" name="challenge" value="[{$challenge}]">
    <input type="hidden" name="sDeliveryAddressMD5" value="[{$oView->getDeliveryAddressMD5()}]">

    [{if $oViewConf->isFunctionalityEnabled("blShowTSInternationalFeesMessage")}]
		[{oxifcontent ident="oxtsinternationalfees" object="oTSIFContent"}]
			<hr/>
			<div class="clear">
				<span class="title">[{$oTSIFContent->oxcontents__oxcontent->value}]</span>
			</div>
        [{/oxifcontent}]
    [{/if}]

    [{if $payment->oxpayments__oxid->value eq "oxidcashondel" && $oViewConf->isFunctionalityEnabled("blShowTSCODMessage")}]
		[{oxifcontent ident="oxtscodmessage" object="oTSCODContent"}]
			<hr/>
            <div class="clear">
				<span class="title">[{$oTSCODContent->oxcontents__oxcontent->value}]</span>
            </div>
        [{/oxifcontent}]
    [{/if}]
    <hr/>

    [{if !$oView->showOrderButtonOnTop()}]
		[{include file="page/checkout/inc/agb.tpl"}]
        <hr/>
    [{else}]
        [{include file="page/checkout/inc/agb.tpl" hideButtons=true}]
    [{/if}]

    <a href="[{oxgetseourl ident=$oViewConf->getPaymentLink()}]" class="prevStep submitButton largeButton">[{oxmultilang ident="PREVIOUS_STEP"}]</a>
    <button type="submit" class="submitButton nextStep largeButton">[{oxmultilang ident="SUBMIT_ORDER"}]</button>
</form>
[{assign var="payment_type" value=$oViewConf->displayPayment()}]
[{if in_array($payment_type, array(payeverinvoice,payeverprepayment,payevercreditcard,payeveronlinetransfer,payeverpaypal,payeversepa,payevereps,payeverideal))}]
[{oxscript add="$('.nextStep').click(function(){
$('.nextStep').attr('disabled','disabled');$('#orderConfirmAgbBottom').submit(); })"}]
[{/if}]
[{else}]
 <form action="[{$oViewConf->getSslSelfLink()}]" method="post" id="orderConfirmAgbBottom">
	[{$oViewConf->getHiddenSid()}]
	[{$oViewConf->getNavFormParams()}]
	<input type="hidden" name="cl" value="order">
	<input type="hidden" name="fnc" value="[{$oView->getExecuteFnc()}]">
	<input type="hidden" name="challenge" value="[{$challenge}]">
	<input type="hidden" name="sDeliveryAddressMD5" value="[{$oView->getDeliveryAddressMD5()}]">

	[{if !$oView->showOrderButtonOnTop()}]
		[{include file="page/checkout/inc/agb.tpl"}]
		<hr/>
	[{else}]
		[{include file="page/checkout/inc/agb.tpl" hideButtons=true}]
	[{/if}]

	<ul class="form">
		<li><button type="submit" class="btn">[{oxmultilang ident="SUBMIT_ORDER"}]</button></li>
		<li><input type="button" class="btn previous" value="[{oxmultilang ident="PREVIOUS_STEP"}]" onclick="window.open('[{oxgetseourl ident=$oViewConf->getPaymentLink()}]', '_self');" /></li>
	</ul>
	</form>
[{assign var="payment_type" value=$oViewConf->displayPayment()}]
[{if in_array($payment_type, array(payeverinvoice,payeverprepayment,payevercreditcard,payeveronlinetransfer,payeverpaypal,payeversepa,payevereps,payeverideal))}]
[{oxscript add="$('.btn').click(function(){
if($(this).text() == 'Order now' || $(this).text() ==  'Zahlungspflichtig bestellen'    ){
$('.btn').attr('disabled','disabled');$('#orderConfirmAgbBottom').submit(); }})"}]
[{/if}]
[{/if}]
