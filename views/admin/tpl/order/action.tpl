[{assign var='actionType' value=$manager->getActionType()}]
[{assign var='actionAllowed' value=$manager->isActionAllowed($edit)}]
[{assign var='actionLang' value=$actionType|upper}]
[{assign var='articleQntField' value=$manager->getActionField()}]

<div class="form-block form-template">
    <div class="form-block-label">
        <b>[{oxmultilang ident="PAYEVER_`$actionLang`_STATUS"}]:</b>
    </div>
    [{if $actionAllowed.enabled}]
        [{if $actionAllowed.partialAllowed}]
            [{if $manager->partialItemsFormAllowed($edit)}]
                <form class="partial-items-form" id="[{$actionType}]-items-form" action="[{$oViewConf->getSelfLink()}]" method="post">
                    [{$oViewConf->getHiddenSid()}]
                    <input type="hidden" name="cl" value="payeverordertab">
                    <input type="hidden" name="fnc" value="processItems">
                    <input type="hidden" name="oxid" value="[{$oxid}]">
                    <input type="hidden" name="actionType" value="[{$actionType}]">
                    <input type="hidden" name="editval[oxorder__oxid]" value="[{$oxid}]">
                    <table cellspacing="0" cellpadding="0" border="0" style="width: 100%;">
                        [{foreach from=$orderArticles item=listitem}]

                            [{assign var='availableQnt' value=$manager->getArticleAvailableQnt($listitem)}]

                            [{assign var='completed' value=false}]
                            [{if $listitem->getFieldData($articleQntField) >= $availableQnt}]
                                [{assign var='completed' value=true}]
                            [{/if}]

                            [{if $availableQnt > 0}]
                                <tr>
                                    <td style="padding: 2px 7px" width="20px">
                                        <input type="checkbox"
                                               name="itemActive[[{$listitem->getId()}]]"
                                               class="edittext order-item-checkbox"
                                               [{if $completed}]disabled[{/if}]
                                               checked="checked"
                                               value="1"
                                        >
                                    </td>
                                    <td style="padding: 2px 7px">
                                        [{$listitem->getFieldData($articleQntField)}]/[{$availableQnt}]
                                    </td>
                                    <td style="padding: 2px 7px">
                                        [{$listitem->oxorderarticles__oxartnum->value}]
                                    </td>
                                    <td style="padding: 2px 7px">
                                        [{$listitem->oxorderarticles__oxtitle->getRawValue()|oxtruncate:20:""|strip_tags}]
                                        [{if isset($listitem->oxwrapping__oxname->value)}]
                                        &nbsp;([{$listitem->oxwrapping__oxname->value}])&nbsp;
                                        [{/if}]
                                    </td>
                                    <td style="padding: 2px 7px; text-align: center">
                                        [{if $listitem->getFieldData($articleQntField) >= $availableQnt}]
                                            <span style="color: green">&#10004;</span>
                                        [{else}]
                                            <input type="number"
                                                   id="[{$actionType}]-items-qnt-[{$listitem->oxorderarticles__oxartnum->value}]"
                                                   name="itemQnt[[{$listitem->getId()}]]"
                                                   class="editinput order-item-qnt"
                                                   size="3"
                                                   style="padding: 3px;"
                                                   [{if $completed}]disabled[{/if}]
                                                   step="1"
                                                   min="1"
                                                   required
                                                   max="[{$availableQnt-$listitem->getFieldData($articleQntField)}]"
                                                   value="[{$availableQnt-$listitem->getFieldData($articleQntField)}]"
                                            >
                                        [{/if}]
                                    </td>
                                    <td style="padding: 2px 7px">
                                        <input type="hidden" class="order-item-price" value="[{$listitem->oxorderarticles__oxbprice->value}]">
                                        [{$listitem->oxorderarticles__oxbprice->value}]
                                    </td>
                                    <td style="padding: 2px 7px;text-align: right">
                                        <span class="order-item-subtotal">[{$availableQnt*$listitem->oxorderarticles__oxbprice->value}]</span>
                                    </td>
                                    <td style="padding: 2px 7px;text-align: right">
                                        [{$edit->oxorder__oxcurrency->value}]
                                    </td>
                                </tr>
                            [{/if}]
                        [{/foreach}]
                        [{if $edit->oxorder__oxwrapcost->value}]
                            <tr>
                                <td colspan="3" style="padding: 2px 7px" width="20px">
                                    <input type="checkbox"
                                           name="itemActive[wrapcost]"
                                           class="edittext order-item-checkbox"
                                           checked="checked"
                                           [{if $manager->isWrapCostSent($edit)}]disabled[{/if}]
                                           value="1"
                                    >
                                </td>
                                <td colspan="3" style="padding: 2px 7px">
                                    [{oxmultilang ident="GENERAL_WRAPPING"}]
                                </td>
                                <td style="padding: 2px 7px; text-align: right">
                                    <input type="hidden" name="itemQnt[wrapcost]" class="order-item-qnt" value="1">
                                    <input type="hidden" name="itemAmount[wrapcost]" class="order-item-price" value="[{$edit->oxorder__oxwrapcost->value}]">
                                    <span class="order-item-subtotal">[{$edit->oxorder__oxwrapcost->value}]</span>
                                </td>
                                <td style="padding: 2px 7px; text-align: right">
                                    [{$edit->oxorder__oxcurrency->value}]
                                </td>
                            </tr>
                        [{/if}]
                        [{if $edit->oxorder__oxgiftcardcost->value}]
                            <tr>
                                <td colspan="3" style="padding: 2px 7px" width="20px">
                                    <input type="checkbox"
                                           name="itemActive[giftcardcost]"
                                           class="edittext order-item-checkbox"
                                           checked="checked"
                                           [{if $manager->isGiftCardCostSent($edit)}]disabled[{/if}]
                                           value="1"
                                    >
                                </td>
                                <td colspan="3" style="padding: 2px 7px">
                                    [{oxmultilang ident="GENERAL_CARD"}]
                                </td>
                                <td style="padding: 2px 7px; text-align: right">
                                    <input type="hidden" name="itemQnt[giftcardcost]" class="order-item-qnt" value="1">
                                    <input type="hidden" name="itemAmount[giftcardcost]" class="order-item-price" value="[{$edit->oxorder__oxgiftcardcost->value}]">
                                    <span class="order-item-subtotal">[{$edit->oxorder__oxgiftcardcost->value}]</span>
                                </td>
                                <td style="padding: 2px 7px; text-align: right">
                                    [{$edit->oxorder__oxcurrency->value}]
                                </td>
                            </tr>
                        [{/if}]
                        <tr>
                            <td colspan="6" style="text-align: right;padding: 2px 7px;">[{oxmultilang ident="PAYEVER_`$actionLang`_SUM"}]:</td>
                            <td style="padding: 2px 7px;line-height: 25px;text-align: right">
                                <span class="partial-total-sum">[{$edit->getFormattedTotalOrderSum()}]</span>
                            </td>
                            <td style="padding: 2px 7px;line-height: 25px;text-align: right">
                                [{$edit->oxorder__oxcurrency->value}]
                            </td>
                        </tr>
                    </table>
                </form>
                <hr/>
            [{/if}]

            [{assign var='totalAmount' value=$manager->getTotalAmount($edit)}]
            [{assign var='sentAmount' value=$manager->getSentAmount($edit)}]
            [{assign var='actionsList' value=$manager->getActions($edit)}]
            [{if $manager->partialAmountFormAllowed($edit)}]
                <form class="partial-amount-form" id="[{$actionType}]-amount-form" action="[{$oViewConf->getSelfLink()}]" method="post">
                    [{$oViewConf->getHiddenSid()}]
                    <input type="hidden" name="cl" value="payeverordertab">
                    <input type="hidden" name="fnc" value="processAmount">
                    <input type="hidden" name="oxid" value="[{$oxid}]">
                    <input type="hidden" name="actionType" value="[{$actionType}]">
                    <input type="hidden" name="editval[oxorder__oxid]" value="[{$oxid}]">
                    <input type="hidden" name="total" class="order-total" value="[{$totalAmount-$sentAmount}]">
                    <table style="width: 100%; text-align: right">
                        <tr>
                            <td style="padding: 2px 7px">[{oxmultilang ident="PAYEVER_`$actionLang`_MANUAL_SUM"}]:</td>
                            <td style="padding: 2px 7px;width: 100px;white-space: nowrap">
                                <input type="number"
                                       name="amount"
                                       id="[{$actionType}]-amount"
                                       style="padding: 3px;"
                                       class="editinput order-item-amount"
                                       size="3"
                                       step="0.01"
                                       min="0.01"
                                       [{if $manager->prefillAmountAllowed($edit)}]value="[{$totalAmount-$sentAmount}]"[{/if}]
                                       max="[{$totalAmount-$sentAmount}]"
                                >
                                [{$edit->oxorder__oxcurrency->value}]
                            </td>
                        </tr>
                        [{if $manager->confirmCheckbox}]
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
            [{/if}]
            <div style="font-weight: bold; text-align: right;padding: 0 8px;line-height: 28px;">
                [{oxmultilang ident="PAYEVER_TOTAL_`$actionLang`_COMPLETE"}]:
                <span id="sentAmount">[{$sentAmount}]</span>
                [{$edit->oxorder__oxcurrency->value}]
            </div>
            [{if isset($formError[$actionType])}]
                <div style="text-align: right;padding: 0 8px;color:red;">
                    [{$formError[$actionType]}]
                </div>
            [{/if}]
            <div style="padding: 2px 7px">
                <input type="button" name="[{$actionType}]-submit-btn" class="form-submit-btn" style="padding: 3px;" value="[{oxmultilang ident="PAYEVER_ORDER_PROCESS_`$actionLang`"}]">
            </div>
        [{else}]
            <form id="[{$actionType}]-total-form" class="total-form" action="[{$oViewConf->getSelfLink()}]" method="post">
                [{$oViewConf->getHiddenSid()}]
                <input type="hidden" name="cl" value="payeverordertab">
                <input type="hidden" name="fnc" value="processTotal">
                <input type="hidden" name="oxid" value="[{$oxid}]">
                <input type="hidden" name="actionType" value="[{$actionType}]">
                <input type="hidden" name="editval[oxorder__oxid]" value="[{$oxid}]">
                [{if isset($formError[$actionType])}]
                    <div class="edittext red" style="color:red;">
                        [{$formError[$actionType]}]
                    </div>
                [{/if}]
                <div>
                    <input type="submit" class="edittext total-form-submit-btn" style="padding: 3px;" value="[{oxmultilang ident="PAYEVER_ORDER_PROCESS_`$actionLang`"}]">
                </div>
            </form>
        [{/if}]
    [{else}]
        <div class="form-block-error">[{oxmultilang ident="PAYEVER_ORDER_`$actionLang`_NOT_ALLOWED"}]</div>
    [{/if}]
</div>
