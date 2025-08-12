<?php

/**
 * PHP version 5.4 and 7
 *
 * @package     Payever\OXID
 * @author      payever GmbH <service@payever.de>
 * @copyright   2017-2021 payever GmbH
 * @license     MIT <https://opensource.org/licenses/MIT>
 */

require_once __DIR__ . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . 'autoload.php';

class PayeverActionValidator
{
    use PayeverConfigTrait;
    use PayeverActionRequestTrait;

    /**
     * @var PayeverFormBase
     */
    protected $form;

    /**
     * @param PayeverFormBase $form
     */
    public function __construct($form)
    {
        $this->form = $form;
    }

    /**
     * Validate actions form
     *
     * @param oxOrder $order
     * @return bool
     * @throws oxConnectionException
     */
    public function validate($order, $type)
    {
        switch ($type) {
            case PayeverActionInterface::TYPE_ITEM:
                $valid = $this->validateItems();
                break;
            case PayeverActionInterface::TYPE_AMOUNT:
                $valid = $this->validateAmount($order);
                break;
            default:
                $valid = true;
        }

        return $valid;
    }

    /**
     * Validate partial amount form
     *
     * @param oxOrder $order
     * @return bool
     * @throws oxConnectionException
     */
    private function validateAmount($order)
    {
        //Check form confirm checkbox
        if ($this->form->confirmCheckbox) {
            $confirm = (bool)$this->getConfig()->getRequestParameter('payeverConfirm');
            if (!$confirm) {
                throw new \InvalidArgumentException(
                    'PAYEVER_ORDER_CHECK_' . strtoupper($this->form->getActionType()) . '_CHECKBOX'
                );
            }
        }

        $amount = $this->getActionRequest()->getAmount();

        //Check if amount is not empty
        if ($amount <= 0) {
            throw new \InvalidArgumentException(
                'PAYEVER_ORDER_INVALID_' . strtoupper($this->form->getActionType()) . '_AMOUNT'
            );
        }

        //Check if total shipped amount <= total amount
        $partialTotal = $this->form->getSentAmount($order);
        if ($amount + $partialTotal > (float)$order->oxorder__oxtotalordersum->value) {
            throw new \InvalidArgumentException(
                'PAYEVER_ORDER_INVALID_' . strtoupper($this->form->getActionType()) . '_AMOUNT'
            );
        }

        return true;
    }

    /**
     * Validate partial items form
     *
     * @return bool
     */
    private function validateItems()
    {
        $formItems = $this->getActionRequest()->getItems();

        //Check order items was selected
        if (!$formItems) {
            throw new \InvalidArgumentException(
                'PAYEVER_ORDER_INVALID_' . strtoupper($this->form->getActionType()) . '_AMOUNT'
            );
        }

        return true;
    }
}
