<?php

/**
 * @codeCoverageIgnore
 * phpcs:disable PSR2.Methods.MethodDeclaration.Underscore
 */
class payeverproductsexport extends oxAdminView
{
    use PayeverConfigHelperTrait;

    /** @var PayeverExportManager */
    private $exportManager;

    /**
     * {@inheritDoc}
     */
    public function __construct()
    {
        parent::__construct();
        $this->exportManager = new PayeverExportManager();
    }

    /**
     * {@inheritDoc}
     */
    protected function _authorize()
    {
        $externalId = $this->getConfigHelper()->getProductsSyncExternalId();

        return $externalId && $this->getConfig()->getRequestParameter('externalId');
    }

    /**
     * Performs export
     * @SuppressWarnings(PHPMD.ExitExpression)
     */
    public function export()
    {
        $this->exportManager->export(
            (int) $this->getConfig()->getRequestParameter('page'),
            (int) $this->getConfig()->getRequestParameter('aggregate')
        );
        $result = \json_encode([
            'next_page' => $this->exportManager->getNextPage(),
            'aggregate' => $this->exportManager->getAggregate(),
            'error' => implode(', ', $this->exportManager->getErrors())
        ]);
        \headers_sent() || header('Content-Type: application/json');
        echo $result;
        exit;
    }
}
// phpcs:enable PSR2.Methods.MethodDeclaration.Underscore
