<?php

/**
 * PHP version 5.4 and 7
 *
 * @package     Payever\OXID
 * @author      payever GmbH <service@payever.de>
 * @copyright   2017-2021 payever GmbH
 * @license     MIT <https://opensource.org/licenses/MIT>
 */

trait PayeverInvoiceManagerTrait
{
    /**
     * @var PayeverInvoiceManager
     */
    protected $invoiceManager;

    /**
     * @param PayeverInvoiceManager $invoiceManager
     * @return $this
     * @internal
     */
    public function setInvoiceManager(PayeverInvoiceManager $invoiceManager)
    {
        $this->invoiceManager = $invoiceManager;

        return $this;
    }

    /**
     * @return PayeverInvoiceManager
     * @codeCoverageIgnore
     */
    protected function getInvoiceManager()
    {
        return null === $this->invoiceManager
            ? $this->invoiceManager = new PayeverInvoiceManager()
            : $this->invoiceManager;
    }
}
