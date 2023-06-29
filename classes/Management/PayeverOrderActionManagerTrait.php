<?php

/**
 * PHP version 5.4 and 7
 *
 * @package     Payever\OXID
 * @author      payever GmbH <service@payever.de>
 * @copyright   2017-2021 payever GmbH
 * @license     MIT <https://opensource.org/licenses/MIT>
 */

trait PayeverOrderActionManagerTrait
{
    /** @var PayeverCaptureManager */
    protected $captureManager;

    /** @var PayeverRefundManager */
    protected $refundManager;

    /** @var PayeverCancelManager */
    protected $cancelManager;

    /** @var PayeverOrderActionValidator */
    protected $validator;

    /**
     * @param string $type
     *
     * @return PayeverCancelManager|PayeverCaptureManager|PayeverRefundManager|void
     */
    public function getManager($type)
    {
        switch ($type) {
            case payeverorderaction::ACTION_SHIPPING_GOODS:
                return $this->getCaptureManager();
            case payeverorderaction::ACTION_REFUND:
                return $this->getRefundManager();
            case payeverorderaction::ACTION_CANCEL:
                return $this->getCancelManager();
        }
    }

    /**
     * @return PayeverRefundManager
     */
    public function getRefundManager()
    {
        return null === $this->refundManager
            ? $this->refundManager = new PayeverRefundManager()
            : $this->refundManager;
    }

    /**
     * @param PayeverRefundManager $refundManager
     *
     * @return $this
     */
    public function setRefundManager($refundManager)
    {
        $this->refundManager = $refundManager;

        return $this;
    }

    /**
     * @return PayeverCaptureManager
     */
    public function getCaptureManager()
    {
        return null === $this->captureManager
            ? $this->captureManager = new PayeverCaptureManager()
            : $this->captureManager;
    }

    /**
     * @param PayeverCaptureManager $captureManager
     *
     * @return $this
     */
    public function setCaptureManager($captureManager)
    {
        $this->captureManager = $captureManager;

        return $this;
    }

    /**
     * @return PayeverCancelManager
     */
    public function getCancelManager()
    {
        return null === $this->cancelManager
            ? $this->cancelManager = new PayeverCancelManager()
            : $this->cancelManager;
    }

    /**
     * @param PayeverCancelManager $cancelManager
     *
     * @return $this
     */
    public function setCancelManager($cancelManager)
    {
        $this->cancelManager = $cancelManager;

        return $this;
    }

    /**
     * @param string $type
     *
     * @return PayeverOrderActionValidator
     */
    public function getValidator($type)
    {
        return null === $this->validator
            ? $this->validator = new PayeverOrderActionValidator($this->getManager($type))
            : $this->validator;
    }

    /**
     * @param PayeverOrderActionValidator $validator
     *
     * @return $this
     */
    public function setValidator($validator)
    {
        $this->validator = $validator;

        return $this;
    }
}
