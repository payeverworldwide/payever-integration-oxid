[{if $edit}]
    <table width="200" border="0" cellspacing="0" cellpadding="0" nowrap>
        <tr>
            <td class="edittext" valign="top">
                [{block name="admin_order_overview_billingaddress"}]
                <b>[{oxmultilang ident="GENERAL_BILLADDRESS"}]</b><br/><br/>
                [{if $edit->oxorder__oxbillcompany->value}][{oxmultilang ident="GENERAL_COMPANY"}] [{$edit->oxorder__oxbillcompany->value}]<br/>[{/if}]
                [{if $edit->oxorder__oxbilladdinfo->value}][{$edit->oxorder__oxbilladdinfo->value}]<br/>[{/if}]
                [{$edit->oxorder__oxbillsal->value|oxmultilangsal}] [{$edit->oxorder__oxbillfname->value}] [{$edit->oxorder__oxbilllname->value}]<br/>
                [{$edit->oxorder__oxbillstreet->value}] [{$edit->oxorder__oxbillstreetnr->value}]<br/>
                [{$edit->oxorder__oxbillstateid->value}]
                [{$edit->oxorder__oxbillzip->value}] [{$edit->oxorder__oxbillcity->value}]<br/>
                [{$edit->oxorder__oxbillcountry->value}]<br/>
                [{if $edit->oxorder__oxbillcompany->value && $edit->oxorder__oxbillustid->value}]<br/>
                [{oxmultilang ident="ORDER_OVERVIEW_VATID"}]
                [{$edit->oxorder__oxbillustid->value}]<br/>
                [{if !$edit->oxorder__oxbillustidstatus->value}]
                <span class="error">[{oxmultilang ident="ORDER_OVERVIEW_VATIDCHECKFAIL"}]</span><br/>
                [{/if}]
                [{/if}]<br/>
                [{oxmultilang ident="GENERAL_EMAIL"}]:
                <a href="mailto:[{$edit->oxorder__oxbillemail->value}]?subject=[{$actshop}] - [{oxmultilang ident="GENERAL_ORDERNUM"}] [{$edit->oxorder__oxordernr->value}]" class="edittext">
                    <em>[{$edit->oxorder__oxbillemail->value}]</em>
                </a><br/><br/>
                [{/block}]
            </td>
            [{if $edit->oxorder__oxdelstreet->value}]
            <td class="edittext" valign="top">
                [{block name="admin_order_overview_deliveryaddress"}]
                <b>[{oxmultilang ident="GENERAL_DELIVERYADDRESS"}]:</b><br/><br/>
                [{if $edit->oxorder__oxdelcompany->value}]Firma [{$edit->oxorder__oxdelcompany->value}]<br/>[{/if}]
                [{if $edit->oxorder__oxdeladdinfo->value}][{$edit->oxorder__oxdeladdinfo->value}]<br/>[{/if}]
                [{$edit->oxorder__oxdelsal->value|oxmultilangsal }] [{$edit->oxorder__oxdelfname->value}] [{$edit->oxorder__oxdellname->value}]<br/>
                [{$edit->oxorder__oxdelstreet->value}] [{$edit->oxorder__oxdelstreetnr->value}]<br/>
                [{$edit->oxorder__oxdelstateid->value}]
                [{$edit->oxorder__oxdelzip->value}] [{$edit->oxorder__oxdelcity->value}]<br/>
                [{$edit->oxorder__oxdelcountry->value}]<br/><br/>
                [{/block}]
            </td>
            [{/if}]
        </tr>
    </table>

    <b>[{oxmultilang ident="GENERAL_ITEM"}]:</b><br/><br/>

    <table cellspacing="0" cellpadding="0" border="0">
        [{block name="admin_order_overview_items"}]
        [{foreach from=$orderArticles item=listitem}]
        <tr>
            <td valign="top" class="edittext">[{$listitem->oxorderarticles__oxamount->value}] * </td>
            <td valign="top" class="edittext">&nbsp;[{$listitem->oxorderarticles__oxartnum->value}]</td>
            <td valign="top" class="edittext">&nbsp;
                [{$listitem->oxorderarticles__oxtitle->getRawValue()|oxtruncate:20:""|strip_tags}]
                [{if $listitem->oxwrapping__oxname->value}]
                &nbsp;([{$listitem->oxwrapping__oxname->value}])&nbsp;
                [{/if}]
            </td>
            <td valign="top" class="edittext">&nbsp;[{$listitem->oxorderarticles__oxselvariant->value}]</td>
            <td valign="top" class="edittext">&nbsp;&nbsp;[{$listitem->getTotalBrutPriceFormated()}] [{$edit->oxorder__oxcurrency->value}]</td>
            [{if $listitem->getPersParams()}]
            <td valign="top" class="edittext">
                [{foreach key=sVar from=$listitem->getPersParams() item=aParam name=persparams}]
                &nbsp;&nbsp;,&nbsp;<em>
                [{if $smarty.foreach.persparams.first && $smarty.foreach.persparams.last}]
                [{oxmultilang ident="GENERAL_LABEL"}]
                [{else}]
                [{$sVar}] :
                [{/if}]
                [{$aParam}]
            </em>
                [{/foreach}]
            </td>
            [{/if}]
        </tr>
        [{/foreach}]
        [{/block}]
    </table><br/>

    [{if $edit->oxorder__oxstorno->value}]
    <span class="orderstorno">[{oxmultilang ident="ORDER_OVERVIEW_STORNO"}]</span><br/><br/>
    [{/if}]

    <b>[{oxmultilang ident="GENERAL_ATALL"}]: </b><br/><br/>
    <table border="0" cellspacing="0" cellpadding="0" id="order.info">
        [{block name="admin_order_overview_total"}]
        <tr>
            <td class="edittext" height="15">[{oxmultilang ident="GENERAL_IBRUTTO"}]</td>
            <td class="edittext" align="right"><b>[{$edit->getFormattedTotalBrutSum()}]</b></td>
            <td class="edittext">&nbsp;<b>[{if $edit->oxorder__oxcurrency->value}] [{$edit->oxorder__oxcurrency->value}] [{else}] [{$currency->name}] [{/if}]</b></td>
        </tr>
        <tr>
            <td class="edittext" height="15">[{oxmultilang ident="GENERAL_DISCOUNT"}]&nbsp;&nbsp;</td>
            <td class="edittext" align="right"><b>- [{$edit->getFormattedDiscount()}]</b></td>
            <td class="edittext">&nbsp;<b>[{if $edit->oxorder__oxcurrency->value}] [{$edit->oxorder__oxcurrency->value}] [{else}] [{$currency->name}] [{/if}]</b></td>
        </tr>
        <tr>
            <td class="edittext" height="15">[{oxmultilang ident="GENERAL_INETTO"}]</td>
            <td class="edittext" align="right"><b>[{$edit->getFormattedTotalNetSum()}]</b></td>
            <td class="edittext">&nbsp;<b>[{if $edit->oxorder__oxcurrency->value}] [{$edit->oxorder__oxcurrency->value}] [{else}] [{$currency->name}] [{/if}]</b></td>
        </tr>
        [{foreach key=iVat from=$aProductVats item=dVatPrice}]
        <tr>
            <td class="edittext" height="15">[{oxmultilang ident="GENERAL_IVAT"}] ([{$iVat}]%)</td>
            <td class="edittext" align="right"><b>[{$dVatPrice}]</b></td>
            <td class="edittext">&nbsp;<b>[{if $edit->oxorder__oxcurrency->value}] [{$edit->oxorder__oxcurrency->value}] [{else}] [{$currency->name}] [{/if}]</b></td>
        </tr>
        [{/foreach}]
        [{if $edit->oxorder__oxvoucherdiscount->value}]
        <tr>
            <td class="edittext" height="15">[{oxmultilang ident="GENERAL_VOUCHERS"}]</td>
            <td class="edittext" align="right"><b>- [{$edit->getFormattedTotalVouchers()}]</b></td>
            <td class="edittext">&nbsp;<b>[{if $edit->oxorder__oxcurrency->value}] [{$edit->oxorder__oxcurrency->value}] [{else}] [{$currency->name}] [{/if}]</b></td>
        </tr>
        [{/if}]
        <tr>
            <td class="edittext" height="15">[{oxmultilang ident="GENERAL_DELIVERYCOST"}]&nbsp;&nbsp;</td>
            <td class="edittext" align="right"><b>[{$edit->getFormattedeliveryCost()}]</b></td>
            <td class="edittext">&nbsp;<b>[{if $edit->oxorder__oxcurrency->value}] [{$edit->oxorder__oxcurrency->value}] [{else}] [{$currency->name}] [{/if}]</b></td>
        </tr>
        <tr>
            <td class="edittext" height="15">[{oxmultilang ident="GENERAL_PAYCOST"}]&nbsp;&nbsp;</td>
            <td class="edittext" align="right"><b>[{$edit->getFormattedPayCost()}]</b></td>
            <td class="edittext">&nbsp;<b>[{if $edit->oxorder__oxcurrency->value}] [{$edit->oxorder__oxcurrency->value}] [{else}] [{$currency->name}] [{/if}]</b></td>
        </tr>
        [{if $edit->oxorder__oxwrapcost->value}]
        <tr>
            <td class="edittext" height="15">[{oxmultilang ident="GENERAL_WRAPPING"}]&nbsp;[{if $wrapping}]([{$wrapping->oxwrapping__oxname->value}])[{/if}]&nbsp;</td>
            <td class="edittext" align="right"><b>[{$edit->getFormattedWrapCost()}]</b></td>
            <td class="edittext">&nbsp;<b>[{if $edit->oxorder__oxcurrency->value}] [{$edit->oxorder__oxcurrency->value}] [{else}] [{$currency->name}] [{/if}]</b></td>
        </tr>
        [{/if}]
        [{if $edit->oxorder__oxgiftcardcost->value}]
        <tr>
            <td class="edittext" height="15">[{oxmultilang ident="GENERAL_CARD"}]&nbsp;[{if $giftCard}]([{$giftCard->oxwrapping__oxname->value}])[{/if}]&nbsp;</td>
            <td class="edittext" align="right"><b>[{$edit->getFormattedGiftCardCost()}]</b></td>
            <td class="edittext">&nbsp;<b>[{if $edit->oxorder__oxcurrency->value}] [{$edit->oxorder__oxcurrency->value}] [{else}] [{$currency->name}] [{/if}]</b></td>
        </tr>
        [{/if}]
        [{if $edit->oxorder__oxtsprotectid->value}]
        <tr>
            <td class="edittext" height="15">[{oxmultilang ident=ORDER_OVERVIEW_PROTECTION}]&nbsp;</td>
            <td class="edittext" align="right"><b>[{$tsprotectcosts}]</b></td>
            <td class="edittext">&nbsp;<b>[{if $edit->oxorder__oxcurrency->value}] [{$edit->oxorder__oxcurrency->value}] [{else}] [{$currency->name}] [{/if}]</b></td>
        </tr>
        [{/if}]
        <tr>
            <td class="edittext" height="25">[{oxmultilang ident="GENERAL_SUMTOTAL"}]&nbsp;&nbsp;</td>
            <td class="edittext" align="right"><b>[{$edit->getFormattedTotalOrderSum()}]</b></td>
            <td class="edittext">&nbsp;<b>[{if $edit->oxorder__oxcurrency->value}] [{$edit->oxorder__oxcurrency->value}] [{else}] [{$currency->name}] [{/if}]</b></td>
        </tr>
        [{/block}]
    </table><br/>

    <table>
        [{block name="admin_order_overview_checkout"}]
        [{if $paymentType}]
        <tr>
            <td class="edittext">[{oxmultilang ident="ORDER_OVERVIEW_PAYMENTTYPE"}]: </td>
            <td class="edittext">
                [{if $paymentType->oxpayments__oxdesc->value}]
                    <b>[{$paymentType->oxpayments__oxdesc->value }]</b>
                [{else}]
                    <b>[{$paymentType->oxuserpayments__oxpaymentsid->value}]</b>
                [{/if}]
            </td>
        </tr>
        [{/if}]
        <tr>
            <td class="edittext">[{oxmultilang ident="ORDER_OVERVIEW_DELTYPE"}]: </td>
            <td class="edittext"><b>[{$deliveryType->oxdeliveryset__oxtitle->value}]</b><br></td>
        </tr>
        [{/block}]
    </table><br/>

    [{if $paymentType && $paymentType->aDynValues}]
    <table cellspacing="0" cellpadding="0" border="0">
        [{block name="admin_order_overview_dynamic"}]
        [{foreach from=$paymentType->aDynValues item=value}]
        [{assign var="ident" value='ORDER_OVERVIEW_'|cat:$value->name}]
        [{assign var="ident" value=$ident|oxupper}]
        <tr>
            <td class="edittext">
                [{oxmultilang ident=$ident}]:&nbsp;
            </td>
            <td class="edittext">
                [{$value->value}]
            </td>
        </tr>
        [{/foreach}]
        [{/block}]
    </table><br/>
    [{/if}]

    [{if $edit->oxorder__oxremark->value}]
    <b>[{oxmultilang ident="GENERAL_REMARK"}]</b>
    <table cellspacing="0" cellpadding="0" border="0">
        <tr>
            <td class="edittext wrap">[{$edit->oxorder__oxremark->value}]</td>
        </tr>
    </table>
    [{/if}]
[{/if}]