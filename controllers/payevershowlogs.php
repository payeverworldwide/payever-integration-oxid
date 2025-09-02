<?php

/**
 * PHP version 5.4 and 7
 *
 * @package   Payever\OXID
 * @author payever GmbH <service@payever.de>
 * @copyright 2017-2021 payever GmbH
 * @license   MIT <https://opensource.org/licenses/MIT>
 */

/**
 * Getting dynamic values and params for Payever payment types
 *
 * @extend oxBaseClass
 * @codeCoverageIgnore
 */
class payeverShowLogs extends oxUBase
{
    use PayeverConfigHelperTrait;
    use PayeverPaymentsApiClientTrait;
    use PayeverRequestHelperTrait;

    /**
     * @return void
     * @SuppressWarnings(PHPMD.ExitExpression)
     */
    public function render()
    {
        try {
            $this->validateRequest();

            $logger = new PayeverLogger();
            $this->send($logger->downloadLogs(false));
        } catch (\Exception $e) {
            header("HTTP/1.1 401 Unauthorized");

            //@codeCoverageIgnoreStart
            echo 'Error: ' . $e->getMessage();
            exit;
            //@codeCoverageIgnoreEnd
        }
    }

    /**
     * @SuppressWarnings(PHPMD.ExitExpression)
     * @return void
     */
    public function shop()
    {
        try {
            $this->validateRequest();

            $logger = new PayeverLogger();
            $this->send($logger->downloadLogs(true));
        } catch (\Exception $e) {
            header("HTTP/1.1 401 Unauthorized");

            //@codeCoverageIgnoreStart
            echo 'Error: ' . $e->getMessage();
            exit;
            //@codeCoverageIgnoreEnd
        }
    }

    /**
     * @param $zipFile
     * @SuppressWarnings(PHPMD.ExitExpression)
     * @return void
     */
    private function send($zipFile)
    {
        header('Content-Description: File Transfer');
        header('Content-Type: application/zip;');
        header('Content-Disposition: attachment; filename="' . basename($zipFile) . '";');
        header('Content-Transfer-Encoding: binary');
        header('Expires: 0');
        header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
        header('Pragma: public');
        readfile($zipFile);
        exit();
    }

    /**
     * @return $this
     * @throws Exception
     */
    private function validateRequest()
    {
        $token = $this->getRequestHelper()->getHeader('Authorization');

        if (empty($token)) {
            $token = $this->getRequestHelper()->getStringParam('token');
        }

        if (
            !$this->getThirdPartyPluginsApiClient()
                ->validateToken(
                    $this->getConfigHelper()->getBusinessUuid(),
                    $token
                )
        ) {
            throw new \BadMethodCallException('Access denied.');
        }

        return $this;
    }
}
