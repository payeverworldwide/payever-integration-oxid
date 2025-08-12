[{assign var='action' value=$form->getActionType()}]
[{assign var='actionAllowed' value=$form->isActionAllowed($edit)}]
[{assign var='claimActionAllowed' value=$form->isClaimActionAllowed($edit)}]
[{assign var='actionLang' value=$action|upper}]

[{if $actionAllowed}]
    <div class="form-block">
        <div class="form-block-label">
            <b>[{oxmultilang ident="PAYEVER_`$actionLang`_STATUS"}]:</b>
        </div>
        <form class="claim-form" id="claim-form" action="[{$oViewConf->getSelfLink()}]" method="post" enctype="multipart/form-data">
            [{$oViewConf->getHiddenSid()}]
            <input type="hidden" name="cl" value="payeverordertab">
            <input type="hidden" name="fnc" value="processAction">
            <input type="hidden" name="oxid" value="[{$oxid}]">
            <input type="hidden" name="action" id="claim_action" value="claim_upload">
            <input type="hidden" name="editval[oxorder__oxid]" value="[{$oxid}]">
            <table style="width: 100%; text-align: right">
                <tr>
                    <td style="padding: 2px 7px">[{oxmultilang ident="PAYEVER_CLAIM_IS_DISPUTED"}]:</td>
                    <td style="padding: 2px 7px;width: 100px;text-align: left">
                        <input type="checkbox"
                               id="claim-is-disputed"
                               name="is_disputed"
                               class="editinput"
                               style="padding: 3px;"
                               value="1"
                        >
                    </td>
                </tr>
                <tr>
                    <td style="padding: 2px 7px">[{oxmultilang ident="PAYEVER_CLAIM_UPLOAD_FILES"}]:</td>
                    <td style="padding: 2px 7px;width: 100px;white-space: nowrap">
                        <input type="file"
                               required
                               multiple
                               accept="image/*, application/pdf"
                               id="claim-upload-files"
                               name="claim_upload_files[]"
                               style="padding: 3px;"
                               class="editinput order-item-amount"
                        >
                    </td>
                </tr>
                <tr>
                    <td style="padding: 2px 7px"></td>
                    <td style="padding: 2px 7px;width: 100px;text-align: left">
                        <input type="button" name="claim-upload-btn" id="claim-upload-btn" style="padding: 3px;" value="[{oxmultilang ident="PAYEVER_ORDER_PROCESS_CLAIM_UPLOAD"}]">
                    </td>
                </tr>
            </table>
        </form>

        [{if isset($formError.claim)}]
            <div style="text-align: right;padding: 0 8px;color:red;">
                [{$formError.claim}]
            </div>
        [{/if}]

        [{if isset($formError.claim_upload)}]
            <div style="text-align: right;padding: 0 8px;color:red;">
                [{$formError.claim_upload}]
            </div>
        [{/if}]

        <div style="padding: 2px 7px">
            <input type="button"
                   name="claim-btn"
                   id="claim-btn"
                   style="padding: 3px;"
                   value="[{oxmultilang ident="PAYEVER_ORDER_PROCESS_`$actionLang`"}]"
                   [{if !$claimActionAllowed.enabled}]disabled[{/if}]
            >
        </div>
    </div>
    <script type="application/javascript">
        var claimForm = document.getElementById('claim-form');
        claimForm.addEventListener('submit', function () {
            var btn = claimForm.querySelector('.form-submit-btn');
            btn.disabled = true;
            btn.parentElement.classList.add('loader');
        });

        document.getElementById('claim-upload-btn').addEventListener('click', function (e) {
            if (claimForm.checkValidity()) {
                e.target.disabled = true;
                e.target.parentElement.classList.add('loader');
                document.getElementById('claim_action').value = 'claim_upload';
                claimForm.submit();
            } else {
                claimForm.reportValidity();
            }
        });

        document.getElementById('claim-btn').addEventListener('click', function (e) {
            e.target.disabled = true;
            e.target.parentElement.classList.add('loader');
            document.getElementById('claim_action').value = 'claim';
            claimForm.submit();
        });

    </script>
[{/if}]
