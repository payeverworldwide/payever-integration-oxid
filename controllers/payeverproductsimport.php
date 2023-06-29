<?php

/**
 * PHP version 5.4 and 7
 *
 * @package     Payever\OXID
 * @author      payever GmbH <service@payever.de>
 * @copyright   2017-2021 payever GmbH
 * @license     MIT <https://opensource.org/licenses/MIT>
 */

class payeverProductsImport extends oxUBase
{
    const PARAM_ACTION = 'sync_action';
    const PARAM_EXTERNAL_ID = 'external_id';

    /** @var PayeverImportManager */
    protected $importManager;

    /** @var bool */
    protected $enableOutput = true;

    /**
     * @throws ReflectionException
     */
    public function import()
    {
        $syncAction = $this->getStringParam(self::PARAM_ACTION);
        $externalId = $this->getStringParam(self::PARAM_EXTERNAL_ID);
        $payload = file_get_contents('php://input');
        $this->getImportManager()->import($syncAction, $externalId, $payload);
    }

    /**
     * {@inheritDoc}
     * @SuppressWarnings(PHPMD.ElseExpression)
     * @SuppressWarnings(PHPMD.ExitExpression)
     */
    public function render()
    {
        $data = [];
        !$this->enableOutput || header('Content-Type: application/json');
        $errors = $this->getImportManager()->getErrors();
        if ($errors) {
            !$this->enableOutput || header('HTTP/1.1 400 Bad Request');
            $data['errors'] = $errors;
        } else {
            !$this->enableOutput || header('HTTP/1.1 200');
            $data['success'] = true;
        }
        if ($this->enableOutput) {
            //@codeCoverageIgnoreStart
            echo \json_encode($data);
            exit;
            //@codeCoverageIgnoreEnd
        }

        return $data;
    }

    /**
     * @param string $key
     * @return string|null
     */
    protected function getStringParam($key)
    {
        $data = $this->getConfig()->getRequestParameter($key);

        return is_string($data) ? $data : null;
    }

    /**
     * @param PayeverImportManager $importManager
     * @return $this
     */
    public function setImportManager(PayeverImportManager $importManager)
    {
        $this->importManager = $importManager;

        return $this;
    }

    /**
     * @return PayeverImportManager
     * @codeCoverageIgnore
     */
    protected function getImportManager()
    {
        return null === $this->importManager
            ? $this->importManager = new PayeverImportManager()
            : $this->importManager;
    }

    /**
     * @param bool $enableOutput
     * @return $this
     */
    public function setEnableOutput($enableOutput)
    {
        $this->enableOutput = $enableOutput;

        return $this;
    }
}
