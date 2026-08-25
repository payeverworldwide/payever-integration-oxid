[{assign var='action' value=$form->getActionType()}]
[{assign var='actionAllowed' value=$form->isActionAllowed($edit)}]
[{assign var='actionLang' value=$action|upper}]

[{if $actionAllowed.enabled}]
    <div class="form-block form-template">
        <div class="form-block-label">
            <b>[{oxmultilang ident="PAYEVER_`$actionLang`_STATUS"}]:</b>
        </div>
        [{include file='payever/order/form_totals.tpl'}]
    </div>
[{/if}]
