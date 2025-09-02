<?php

/**
 * PHP version 5.4 and 7
 *
 * @package     Payever\OXID
 * @author      payever GmbH <service@payever.de>
 * @copyright   2017-2021 payever GmbH
 * @license     MIT <https://opensource.org/licenses/MIT>
 */

class PayeverInvoiceGenerator
{
    /**
     * @var PayeverFPDFFactory
     */
    private $pdfFactory;

    public function __construct(
        PayeverFPDFFactory $pdfFactory
    ) {
        $this->pdfFactory = $pdfFactory;
    }

    /**
     * @param oxOrder $order
     * @param string $number
     * @param \DateTime $date
     * @param string $comment
     *
     * @return string
     */
    public function generate(oxOrder $order, $number, $date, $comment)
    {
        $pdf = $this->pdfFactory->create();
        $pdf->AddPage();
        $pdf->SetDrawColor(190, 190, 190);
        $pdf->SetTextColor(20, 20, 20);
        $pdf->Ln(15);

        // Invoice details
        $pdf->SetFont('helvetica', '', 20);
        $pdf->Cell(0, 10, 'Invoice ' . $number, 0, 1, 'L');

        $pdf->SetFont('helvetica', '', 10);
        $pdf->Ln(20);

        $this->renderOrderInfo($pdf, $order, $date);
        $this->renderTableHeader($pdf);
        $this->renderTableContent($pdf, $order);
        $this->renderTotals($pdf, $order);
        $this->renderComment($pdf, $comment);

        return $pdf->Output('S');
    }

    /**
     * @param FPDF $pdf
     * @param oxOrder $order
     * @return void
     */
    private function renderOrderInfo(FPDF $pdf, oxOrder $order, \DateTime $date)
    {
        $orderDate = new \DateTime($order->getFieldData('OXORDERDATE'));

        // Billing Information
        $line1 = $order->getFieldData('OXBILLFNAME') . ' ' . $order->getFieldData('OXBILLLNAME');
        $line2 = $order->getFieldData('OXBILLSTREET') . ' ' . $order->getFieldData('OXBILLSTREETNR');
        $line3 = $order->getFieldData('OXBILLZIP') . ' ' . $order->getFieldData('OXBILLCITY');

        $billingY = $pdf->GetY();
        $pdf->Cell(100, 5, $line1, 0, 1);
        $pdf->Cell(100, 5, $line2, 0, 1);
        $pdf->Cell(100, 5, $line3, 0, 1);

        // Order Information
        $pdf->SetY($billingY);
        $pdf->setX(100);
        $pdf->Cell(100, 5, sprintf('Order no.: %s', $order->getFieldData('OXORDERNR')), 0, 1, 'R');
        $pdf->setX(100);
        $pdf->Cell(100, 5, sprintf('Order date: %s', $orderDate->format('d M Y')), 0, 1, 'R');
        $pdf->setX(100);
        $pdf->Cell(100, 5, sprintf('Date: %s', $date->format('d M Y')), 0, 1, 'R');
        $pdf->Ln(12);
    }

    /**
     * @param FPDF $pdf
     * @return void
     */
    private function renderTableHeader(FPDF $pdf)
    {
        // Table header
        $pdf->Ln(5);
        $pdf->SetFont('helvetica', 'B', 10);
        $pdf->Cell(40, 10, 'Prod. sku');
        $pdf->Cell(60, 10, 'Prod. name');
        $pdf->Cell(30, 10, 'Quantity', 0, 0, 'R');
        $pdf->Cell(30, 10, 'Unit price', 0, 0, 'R');
        $pdf->Cell(30, 10, 'Total', 0, 0, 'R');
        $pdf->Ln(15);

        $pdf->Line($pdf->GetX(), $pdf->GetY(), 200, $pdf->GetY());
        $pdf->Ln(5);
    }

    /**
     * @param FPDF $pdf
     * @param oxOrder $order
     * @return void
     */
    private function renderTableContent(FPDF $pdf, oxOrder $order)
    {
        // Table content
        $pdf->SetFont('helvetica', '', 10);

        $articles = $order->getOrderArticles();
        foreach ($articles as $article) {
            $pdf->Cell(40, 10, $article->getFieldData('OXARTNUM'));
            $pdf->Cell(60, 10, $article->getFieldData('OXTITLE'));
            $pdf->Cell(30, 10, $article->getFieldData('OXAMOUNT'), 0, 0, 'R');
            $pdf->Cell(30, 10, $article->getFieldData('OXBPRICE'), 0, 0, 'R');
            $pdf->Cell(30, 10, $article->getFieldData('OXBRUTPRICE'), 0, 0, 'R');
            $pdf->Ln(8);
        }

        $pdf->Ln(7);

        $pdf->Line($pdf->GetX(), $pdf->GetY(), 200, $pdf->GetY());
    }

    /**
     * @param FPDF $pdf
     * @param oxOrder $order
     * @return void
     */
    private function renderTotals(FPDF $pdf, oxOrder $order)
    {
        // Totals
        $shipping = $order->getFieldData('OXDELCOST');
        if ($shipping > 0) {
            $pdf->Cell(130);
            $pdf->Cell(30, 10, 'Shipping:', 0, 0, 'R');
            $pdf->Cell(30, 10, $shipping, 0, 0, 'R');
            $pdf->Ln(8);
        }

        $pdf->Cell(130);
        $pdf->SetFont('helvetica', 'B', 10);
        $pdf->Cell(30, 10, 'Total:', 0, 0, 'R');
        $pdf->Cell(30, 10, $order->getFieldData('OXTOTALORDERSUM'), 0, 0, 'R');
        $pdf->Ln(12);

        $pdf->SetFont('helvetica', 'B', 10);
        $pdf->Cell(40, 10, 'Payment Method:');
        $pdf->SetFont('helvetica', '', 10);
        $pdf->Cell(30, 10, $order->getFieldData('OXPAYMENTTYPE'));
        $pdf->Ln(8);
    }

    /**
     * @param FPDF $pdf
     * @param string $comment
     * @return void
     */
    private function renderComment(FPDF $pdf, $comment)
    {
        if (!empty($comment)) {
            $pdf->Ln(8);
            $pdf->SetFont('helvetica', 'B', 10);
            $pdf->Cell(40, 10, 'Comment:');
            $pdf->SetFont('helvetica', '', 10);
            $pdf->Cell(30, 10, $comment);
        }
    }
}
