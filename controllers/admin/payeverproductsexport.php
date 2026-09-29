<?php

/**
 * @codeCoverageIgnore
 * phpcs:disable PSR2.Methods.MethodDeclaration.Underscore
 */
class payeverproductsexport extends oxAdminView
{
    use PayeverOxRequestTrait;
    use PayeverOxConfigTrait;
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

        return $externalId && $this->getRequest()->getRequestParameter('externalId');
    }

    /**
     * Performs export
     * @SuppressWarnings(PHPMD.ExitExpression)
     */
    public function export()
    {
        $this->exportManager->export(
            (int) $this->getRequest()->getRequestParameter('page'),
            (int) $this->getRequest()->getRequestParameter('aggregate')
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
