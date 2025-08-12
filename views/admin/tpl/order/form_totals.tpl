<form id="[{$action}]-total-form" class="total-form" action="[{$oViewConf->getSelfLink()}]" method="post">
    [{$oViewConf->getHiddenSid()}]
    <input type="hidden" name="cl" value="payeverordertab">
    <input type="hidden" name="fnc" value="processAction">
    <input type="hidden" name="oxid" value="[{$oxid}]">
    <input type="hidden" name="type" value="total">
    <input type="hidden" name="action" value="[{$action}]">
    <input type="hidden" name="editval[oxorder__oxid]" value="[{$oxid}]">
    [{if isset($formError[$action])}]
        <div class="edittext red" style="color:red;">
            [{$formError[$action]}]
        </div>
    [{/if}]
    <div>
        <input type="submit" class="edittext total-form-submit-btn" style="padding: 3px;" value="[{oxmultilang ident="PAYEVER_ORDER_PROCESS_`$actionLang`"}]">
    </div>
</form>
