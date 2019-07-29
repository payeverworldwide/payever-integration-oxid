<?php
/**
 * PHP version 5.4 and 7
 *
 * @package   Payever\OXID
 * @author payever GmbH <service@payever.de>
 * @copyright 2017-2019 payever GmbH
 * @license   MIT <https://opensource.org/licenses/MIT>
 */

use Payever\ExternalIntegration\Core\Http\CurlClient;
use Payever\ExternalIntegration\Core\Http\Request;
use Payever\ExternalIntegration\Core\Http\Response;

class PayeverApiClientThrowerDecorator extends CurlClient
{
    /**
     * @param Request $request
     *
     * @return Response
     *
     * @throws \Exception
     */
    public function execute($request)
    {
        try {
            $response = parent::execute($request);
        } catch (\Exception $exception) {
            /**
             * Reset tokens in case some of them caused this error
             */
            PayeverApi::getInstance()->getTokens()->clear()->save();

            throw $this->humanizeExceptionMessage($exception);
        }

        if ($response->isFailed()) {
            $error = $response->getResponseEntity()->getErrorDescription();
            throw new BadMethodCallException(sprintf("Unexpected API error: %s", $error ?: "Unknown error"));
        }

        return $response;
    }

    /**
     * @param \Exception $exception
     *
     * @return \Exception
     */
    private function humanizeExceptionMessage(Exception $exception)
    {
        $content = $exception->getMessage();

        if (($data = json_decode($content, true)) && isset($data['error_description'])) {
            $exception = new Exception($data['error_description'], $exception->getCode(), $exception);
        }

        return $exception;
    }
}
