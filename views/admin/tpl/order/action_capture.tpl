[{assign var='action' value=$form->getActionType()}]
[{assign var='actionAllowed' value=$form->isActionAllowed($edit)}]
[{assign var='actionLang' value=$action|upper}]

<div class="form-block form-template">
    <div class="form-block-label">
        <b>[{oxmultilang ident="PAYEVER_`$actionLang`_STATUS"}]:</b>
    </div>
    [{if $actionAllowed.enabled}]
        [{if $actionAllowed.partialAllowed}]
            [{assign var='sentAmount' value=$form->getSentAmount($edit)}]

            [{if $form->partialItemsFormAllowed($edit)}]
                [{include file='payever/order/form_partial_items.tpl'}]
            [{/if}]

            [{if $form->partialAmountFormAllowed($edit)}]
                [{include file='payever/order/form_partial_amount.tpl'}]
            [{/if}]

            <div style="font-weight: bold; text-align: right;padding: 0 8px;line-height: 28px;">
                [{oxmultilang ident="PAYEVER_TOTAL_`$actionLang`_COMPLETE"}]:
                <span id="sentAmount">[{$sentAmount}]</span>
                [{$edit->oxorder__oxcurrency->value}]
            </div>

            [{if isset($formError[$action])}]
                <div style="text-align: right;padding: 0 8px;color:red;">
                    [{$formError[$action]}]
                </div>
            [{/if}]

            <div style="padding: 2px 7px">
                <input type="button" name="[{$action}]-submit-btn" class="form-submit-btn" style="padding: 3px;" value="[{oxmultilang ident="PAYEVER_ORDER_PROCESS_`$actionLang`"}]">
            </div>
        [{else}]
            [{include file='payever/order/form_totals.tpl'}]
        [{/if}]
    [{else}]
        <div class="form-block-error">[{oxmultilang ident="PAYEVER_ORDER_`$actionLang`_NOT_ALLOWED"}]</div>
    [{/if}]
</div>
