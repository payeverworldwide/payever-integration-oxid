<?php

/**
 * PHP version 5.4 and 7
 *
 * @package     Payever\OXID
 * @author      payever GmbH <service@payever.de>
 * @copyright   2017-2021 payever GmbH
 * @license     MIT <https://opensource.org/licenses/MIT>
 */

class PayeverInvoiceManager
{
    use PayeverLoggerTrait;

    /**
     * @var PayeverInvoiceFactory
     */
    private $invoiceFactory;

    /**
     * @var PayeverInvoiceListFactory
     */
    private $invoiceListFactory;

    /**
     * @var PayeverInvoiceGenerator
     */
    private $invoiceGenerator;

    /**
     * @SuppressWarnings(PHPMD.BooleanArgumentFlag)
     */
    public function __construct($dryRun = false)
    {
        if ($dryRun) {
            return;
        }

        $this->invoiceFactory = new PayeverInvoiceFactory();
        $this->invoiceListFactory = new PayeverInvoiceListFactory();
        $this->invoiceGenerator = new PayeverInvoiceGenerator(
            new PayeverFPDFFactory()
        );
    }

    /**
     * @param oxOrder $order
     *
     * @return object|payeverinvoices|null
     * @throws oxSystemComponentException
     */
    public function addInvoice(oxOrder $order)
    {
        $date = new DateTime();

        try {
            $contents = $this->invoiceGenerator->generate(
                $order,
                $order->getFieldData('OXORDERNR'),
                $date,
                ''
            );
            if (empty($contents)) {
                throw new Exception('Invoice could not be generated');
            }

            $invoice = $this->invoiceFactory->create();
            $invoice->assign(
                [
                    'OXINVOICEKEY' => $this->getRandomString(),
                    'OXORDERID' => $order->getId(),
                    'OXPAYMENTID' => $order->getFieldData('oxtransid'),
                    'OXEXTERNALID' => $order->getUser()->getFieldData('oxexternalid'),
                    'OXCONTENTS' => $contents,
                    'OXTIMESTAMP' => $date->getTimestamp(),
                ]
            );

            $invoice->save();

            $this->getLogger()->info(
                sprintf('Invoice has been created for order #%s', $order->getId())
            );

            return $invoice;
        } catch (\Exception $exception) {
            $this->getLogger()->error(
                'Invoice could not be generated: ' . $exception->getMessage(),
                [
                    'message' => $exception->getMessage(),
                    'trace' => $exception->getTraceAsString()
                ]
            );
        }

        return null;
    }

    /**
     * Checks if order has invoices.
     *
     * @param oxOrder $order
     *
     * @return bool
     */
    public function hasInvoice(oxOrder $order)
    {
        try {
            $collection = $this->invoiceListFactory->create();
            $collection->clear();

            $invoiceObject = $this->invoiceFactory->create();
            method_exists($collection, 'setBaseObject') && $collection->setBaseObject($invoiceObject);
            $query = $invoiceObject->buildSelectString(['OXORDERID' => $order->getId()]);
            $collection->selectString($query);

            return count($collection->getArray()) > 0;
        } catch (\Exception $exception) {
            $this->getLogger()->error(
                'hasInvoice exception: ' . $exception->getMessage(),
                [
                    'message' => $exception->getMessage(),
                    'trace' => $exception->getTraceAsString()
                ]
            );
        }

        return false;
    }

    /**
     * @param $externalID
     *
     * @return payeverinvoices[]
     * @throws oxSystemComponentException
     */
    public function getInvoicesByExternalID($externalID)
    {
        $collection = $this->invoiceListFactory->create();
        $collection->clear();

        $invoiceObject = $this->invoiceFactory->create();
        method_exists($collection, 'setBaseObject') && $collection->setBaseObject($invoiceObject);
        $query = $invoiceObject->buildSelectString(['OXEXTERNALID' => $externalID]);
        $collection->selectString($query);

        return $collection->getArray();
    }

    /**
     * @param $key
     *
     * @return payeverinvoices|null
     * @throws oxSystemComponentException
     */
    public function getInvoiceByKey($key)
    {
        $collection = $this->invoiceListFactory->create();
        $collection->clear();

        $invoiceObject = $this->invoiceFactory->create();
        method_exists($collection, 'setBaseObject') && $collection->setBaseObject($invoiceObject);
        $query = $invoiceObject->buildSelectString(['OXINVOICEKEY' => $key]);
        $collection->selectString($query);

        $invoices = $collection->getArray();
        foreach ($invoices as $invoice) {
            return $invoice;
        }

        return null;
    }

    private function getRandomString()
    {
        $sHash = '';
        $sSalt = '';
        for ($i = 0; $i < 32; $i++) {
            $sHash = hash('sha256', $sHash . mt_rand());
            $iPosition = mt_rand(0, 62);
            $sSalt .= $sHash[$iPosition];
        }

        return $sSalt;
    }
}
