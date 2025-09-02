<?php

/**
 * PHP version 5.4 and 7
 *
 * @package     Payever\OXID
 * @author      payever GmbH <service@payever.de>
 * @copyright   2017-2021 payever GmbH
 * @license     MIT <https://opensource.org/licenses/MIT>
 */

use Payever\Sdk\Payments\Action\ActionDeciderInterface;

require_once __DIR__ . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . 'autoload.php';

trait PayeverActionFormTrait
{
    /** @var PayeverActionValidator */
    protected $validator;

    /**
     * @param string $type
     *
     * @return PayeverFormCancel|PayeverFormCapture|PayeverFormRefund|PayeverFormClaim|PayeverFormSettle|PayeverFormInvoice
     */
    public function getForm($type)
    {
        switch ($type) {
            case ActionDeciderInterface::ACTION_SHIPPING_GOODS:
                $manager = new PayeverFormCapture();
                break;
            case ActionDeciderInterface::ACTION_REFUND:
                $manager = new PayeverFormRefund();
                break;
            case ActionDeciderInterface::ACTION_CANCEL:
                $manager = new PayeverFormCancel();
                break;
            case ActionDeciderInterface::ACTION_CLAIM:
            case ActionDeciderInterface::ACTION_CLAIM_UPLOAD:
                $manager = new PayeverFormClaim();
                break;
            case ActionDeciderInterface::ACTION_SETTLE;
                $manager = new PayeverFormSettle();
                break;
            case ActionDeciderInterface::ACTION_INVOICE;
                $manager = new PayeverFormInvoice();
                break;
            default:
                throw new \InvalidArgumentException('Manager not found.');
        }

        return $manager;
    }

    /**
     * @param string $type
     *
     * @return PayeverActionValidator
     */
    public function getValidator($type)
    {
        $form = $this->getForm($type);

        return null === $this->validator
            ? $this->validator = new PayeverActionValidator($form)
            : $this->validator;
    }

    /**
     * @param PayeverActionValidator $validator
     *
     * @return $this
     */
    public function setValidator($validator)
    {
        $this->validator = $validator;

        return $this;
    }
}
