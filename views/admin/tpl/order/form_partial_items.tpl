[{assign var='articleQntField' value=$form->getActionField()}]
<form class="partial-items-form" id="[{$action}]-items-form" action="[{$oViewConf->getSelfLink()}]" method="post">
    [{$oViewConf->getHiddenSid()}]
    <input type="hidden" name="cl" value="payeverordertab">
    <input type="hidden" name="fnc" value="processAction">
    <input type="hidden" name="oxid" value="[{$oxid}]">
    <input type="hidden" name="type" value="item">
    <input type="hidden" name="action" value="[{$action}]">
    <input type="hidden" name="editval[oxorder__oxid]" value="[{$oxid}]">
    <table cellspacing="0" cellpadding="0" border="0" style="width: 100%;">
        [{foreach from=$orderArticles item=listitem}]

            [{assign var='availableQnt' value=$form->getArticleAvailableQnt($listitem)}]

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
                                   id="[{$action}]-items-qnt-[{$listitem->oxorderarticles__oxartnum->value}]"
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
                           [{if $form->isWrapCostSent($edit)}]disabled[{/if}]
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
                           [{if $form->isGiftCardCostSent($edit)}]disabled[{/if}]
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
