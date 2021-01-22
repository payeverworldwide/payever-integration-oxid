[{$smarty.block.parent}]
[{if $edit and $edit->isPayeverFee($edit->oxpayments__oxid)}]
    <tr>
        <td class="edittext">
            [{ oxmultilang ident="PAYEVER_PAYMENT_ACCEPT_FEE" }]
        </td>
        <td class="edittext">
            <select name="editval[oxpayments__oxacceptfee]" class="editinput" disabled="disabled" [{ $readonly }]>
                <option value="0" [{ if 0 == $edit->oxpayments__oxacceptfee->value}]SELECTED[{/if}]>[{ oxmultilang ident="PAYEVER_PAYMENT_NO" }]</option>
                <option value="1" [{ if 1 == $edit->oxpayments__oxacceptfee->value}]SELECTED[{/if}]>[{ oxmultilang ident="PAYEVER_PAYMENT_YES" }]</option>
            </select>
        </td>
    </tr>
    <tr>
        <td class="edittext">
            [{ oxmultilang ident="PAYEVER_PAYMENT_MAIN_FEE" }]
        </td>
        <td class="edittext">
            <input type="text" readonly="readonly" class="editinput" size="5" maxlength="[{$edit->oxpayments__oxpercentfee->fldmax_length}]" name="editval[oxpayments__oxpercentfee]" value="[{$edit->oxpayments__oxpercentfee->value}]" [{ $readonly }]> [{ oxmultilang ident="PAYEVER_PAYMENT_MAIN_FEE_SEP" }] [{ oxmultilang ident="PAYEVER_PAYMENT_MAIN_FIXED_FEE" }] <input readonly="readonly" type="text" class="editinput" size="5" maxlength="[{$edit->oxpayments__oxfixedfee->fldmax_length}]" name="editval[oxpayments__oxfixedfee]" value="[{$edit->oxpayments__oxfixedfee->value}]" [{ $readonly }]> ([{ $oActCur->sign }])
        </td>
        [{oxscript add="document.getElementsByName('editval[oxpayments__oxaddsum]')[0].parentElement.parentElement.style.display = 'none';"}]
        [{oxscript}]
    </tr>
[{/if}]
[{if $edit and $edit->isRedirectMethod()}]
    <tr>
        <td class="edittext">
            [{ oxmultilang ident="PAYEVER_PAYMENT_IS_REDIRECT_METHOD" }]
        </td>
        <td class="edittext">
            <select name="editval[oxpayments__oxisredirectmethod]" class="editinput">
                <option value="0" [{ if 0 == $edit->oxpayments__oxisredirectmethod->value}]SELECTED[{/if}]>[{ oxmultilang ident="PAYEVER_PAYMENT_NO" }]</option>
                <option value="1" [{ if 1 == $edit->oxpayments__oxisredirectmethod->value}]SELECTED[{/if}]>[{ oxmultilang ident="PAYEVER_PAYMENT_YES" }]</option>
            </select>
        </td>
    </tr>
[{/if}]
