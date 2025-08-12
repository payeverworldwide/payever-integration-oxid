[{assign var='totalAmount' value=$form->getTotalAmount($edit)}]
[{assign var='actionsList' value=$form->getActions($edit)}]
<form class="partial-amount-form" id="[{$action}]-amount-form" action="[{$oViewConf->getSelfLink()}]" method="post">
    [{$oViewConf->getHiddenSid()}]
    <input type="hidden" name="cl" value="payeverordertab">
    <input type="hidden" name="fnc" value="processAction">
    <input type="hidden" name="oxid" value="[{$oxid}]">
    <input type="hidden" name="type" value="amount">
    <input type="hidden" name="action" value="[{$action}]">
    <input type="hidden" name="editval[oxorder__oxid]" value="[{$oxid}]">
    <input type="hidden" name="total" class="order-total" value="[{$totalAmount-$sentAmount}]">
    <table style="width: 100%; text-align: right">
        <tr>
            <td style="padding: 2px 7px">[{oxmultilang ident="PAYEVER_`$actionLang`_MANUAL_SUM"}]:</td>
            <td style="padding: 2px 7px;width: 100px;white-space: nowrap">
                <input type="number"
                       name="amount"
                       id="[{$action}]-amount"
                       style="padding: 3px;"
                       class="editinput order-item-amount"
                       size="3"
                       step="0.01"
                       min="0.01"
                       [{if $form->prefillAmountAllowed($edit)}]value="[{$totalAmount-$sentAmount}]"[{/if}]
                       max="[{$totalAmount-$sentAmount}]"
                >
                [{$edit->oxorder__oxcurrency->value}]
            </td>
        </tr>
        [{if $form->confirmCheckbox}]
            <tr>
                <td style="padding: 2px 7px">
                    [{oxmultilang ident="PAYEVER_CONFIRM_`$actionLang`"}]:
                </td>
                <td style="padding: 2px 7px;text-align: left">
                    <input class="edittext" type="checkbox" name="payeverConfirm" value="1">
                </td>
            </tr>
        [{/if}]
        [{if $actionsList}]
            [{foreach from=$actionsList item=actionItem}]
            <tr>
                <td style="padding: 2px 7px">
                    [{$actionItem.TIMESTAMP|oxformdate:'datetime':true}]
                </td>
                <td style="padding: 2px 7px">
                    [{$actionItem.AMOUNT}] [{$edit->oxorder__oxcurrency->value}]
                </td>
            </tr>
            [{/foreach}]
        [{/if}]
    </table>
</form>
