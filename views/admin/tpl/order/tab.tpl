[{include file="headitem.tpl" title="[OXID Benutzerverwaltung]"}]

[{if $readonly}]
    [{assign var="readonly" value="readonly disabled"}]
    [{else}]
    [{assign var="readonly" value=""}]
[{/if}]

[{assign var="oConfig" value=$oViewConf->getConfig()}]

<form name="transfer" id="transfer" action="[{$oViewConf->getSelfLink()}]" method="post">
    [{$oViewConf->getHiddenSid()}]
    <input type="hidden" name="oxid" value="[{$oxid}]">
    <input type="hidden" name="cl" value="payeverordertab">
</form>

<style>
    .order-tab-form {
        margin-bottom: 15px;
    }
    .form-block {
        border: 1px solid #A9A9A9;
        padding: 6px;
        min-width: 50%;
        display: inline-block
    }
    .form-block-label {
        margin-bottom: 2px;
        line-height: 20px
    }
    .form-block-error {
        color:red;
        line-height: 20px;
    }
    .loader:after {
        content: '';
        width: 28px;
        height: 28px;
        border: 3px solid #a4a4a4;
        border-bottom-color: transparent;
        border-radius: 50%;
        display: inline-block;
        box-sizing: border-box;
        animation: rotation 1s linear infinite;
        vertical-align: bottom;
    }
    @keyframes rotation {
        0% {
            transform: rotate(0deg);
        }
        100% {
            transform: rotate(360deg);
        }
    }
</style>

<table cellspacing="0" cellpadding="0" border="0" width="98%" id="payever-order">
    <tr>
        <td valign="top" class="edittext" width="50%">
            [{include file='payever/order/info.tpl'}]
        </td>
        <td valign="top" class="edittext" width="50%">
            [{if $isPayeverOrder}]
                <div id="order-tab">
                    <div class="order-tab-form">
                        <div class="form-block">
                            <div class="form-block-label">
                                <b>[{oxmultilang ident="PAYEVER_PAYEVERORDERSTATUS"}]:</b>
                            </div>
                            <table>
                                <tr>
                                    <td>[{oxmultilang ident="ORDER_OVERVIEW_INTSTATUS"}]:</td>
                                    <td>[{$transaction.status}]</td>
                                </tr>
                                [{if $edit->oxorder__oxtransid->value}]
                                <tr>
                                    <td>[{oxmultilang ident="PAYEVER_ORDER_REFERENCEID"}]:</td>
                                    <td>[{$edit->oxorder__oxtransid->value}]</td>
                                </tr>
                                [{/if}]
                            </table>
                        </div>
                    </div>
                    <div class="order-tab-form">
                        [{include file='payever/order/action_capture.tpl' form=$oView->getForm('shipping_goods')}]
                    </div>
                    <div class="order-tab-form">
                        [{include file='payever/order/action_cancel.tpl' form=$oView->getForm('cancel')}]
                    </div>
                    <div class="order-tab-form">
                        [{include file='payever/order/action_refund.tpl' form=$oView->getForm('refund')}]
                    </div>
                    <div class="order-tab-form">
                        [{include file='payever/order/action_claim.tpl' form=$oView->getForm('claim')}]
                    </div>
                    <div class="order-tab-form">
                        [{include file='payever/order/action_settle.tpl' form=$oView->getForm('settle')}]
                    </div>
                    <div class="order-tab-form">
                        [{include file='payever/order/action_invoice.tpl' form=$oView->getForm('invoice')}]
                    </div>
                </div>
            [{else}]
                <div class="form-block-error">[{oxmultilang ident="PAYEVER_SELECTED_PAYMENT_NOT_PAYEVER"}]</div>
            [{/if}]
        </td>
    </tr>
</table>

<script type="application/javascript">
  var formsTemplates = document.getElementById('order-tab').querySelectorAll('.form-template');

  formsTemplates.forEach(function (form) {
    var partialItemsForm = form.querySelector('.partial-items-form');
    var partialAmountForm = form.querySelector('.partial-amount-form');
    var totalForm = form.querySelector('.total-form');

    if (partialItemsForm) {
      var partialShipTotalSumElm = form.querySelector('.partial-total-sum');
      var calculateTotal = function () {
        var totalSum = 0;

        var orderItemActive = partialItemsForm.querySelectorAll('.order-item-checkbox:not(:disabled)');
        for (var i = 0; i < orderItemActive.length; i++) {
          var tr = orderItemActive[i].parentElement.parentElement;
          var qnt = tr.querySelector('.order-item-qnt').value;
          var price = tr.querySelector('.order-item-price').value;
          var subpriceElm = tr.querySelector('.order-item-subtotal');
          var subprice = Math.round(qnt * price * 100) / 100;
          subpriceElm.innerHTML = subprice.toFixed(2);

          if (orderItemActive[i].checked) {
            totalSum += subprice;
          }
        }

        partialShipTotalSumElm.innerHTML = totalSum.toFixed(2);
      }

      var orderItemsCalc = partialItemsForm.querySelectorAll('.order-item-checkbox, .order-item-qnt');
      for (var i = 0; i < orderItemsCalc.length; i++) {
        orderItemsCalc[i].addEventListener('change', calculateTotal);
      }

      var orderItemsCheckbox = partialItemsForm.querySelectorAll('.order-item-checkbox');
      for (var i = 0; i < orderItemsCheckbox.length; i++) {
        orderItemsCheckbox[i].addEventListener('change', function (e) {
          var tr = e.target.parentElement.parentElement;
          tr.querySelector('.order-item-qnt').required = e.target.checked;
        });
      }

      if (partialAmountForm) {
        form.querySelector('.order-item-amount').addEventListener('change', function (e) {
          for (var i = 0; i < orderItemsCalc.length; i++) {
            orderItemsCalc[i].disabled = !(e.target.value === '');
          }
        });
      }

      calculateTotal();
    }

    if (partialAmountForm) {
      var total = form.querySelector('.order-total').value;
      form.querySelector('.order-item-amount').addEventListener('change', function (e) {
        if (parseInt(this.value) > parseInt(total)) {
          this.value = total;
        }
      });
    }

    if (totalForm) {
      totalForm.addEventListener('submit', function () {
        var btn = totalForm.querySelector('.total-form-submit-btn');
        btn.disabled = true;
        btn.parentElement.classList.add('loader');
      });
    }

    var submitElm = form.querySelector('.form-submit-btn');
    if (submitElm) {
      submitElm.addEventListener('click', function (e) {
        var formSubmit;
        if (partialItemsForm && partialAmountForm) {
          formSubmit = form.querySelector('.order-item-amount').value !== '' ? partialAmountForm : partialItemsForm;
        } else {
          formSubmit = partialItemsForm || partialAmountForm;
        }

        if (formSubmit.checkValidity()) {
          e.target.disabled = true;
          e.target.parentElement.classList.add('loader');
          formSubmit.submit();
        } else {
          formSubmit.reportValidity();
        }
      });
    }
  });

</script>

[{include file="bottomnaviitem.tpl"}]
</table>
[{include file="bottomitem.tpl"}]
