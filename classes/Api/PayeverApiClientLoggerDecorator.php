<?php
/**
 * PHP version 5.4 and 7
 *
 * @package   Payever\OXID
 * @author payever GmbH <service@payever.de>
 * @copyright 2017-2019 payever GmbH
 * @license   MIT <https://opensource.org/licenses/MIT>
 */

use Payever\ExternalIntegration\Core\Http\Request;
use Payever\ExternalIntegration\Core\Http\Response;

class PayeverApiClientLoggerDecorator extends PayeverApiClientThrowerDecorator
{
    /** @var PayeverLogger */
    private $logger;

    public function __construct(PayeverLogger $logger)
    {
        $this->logger = $logger;
    }

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
            $this->captureResponse($request, $response);
            return $response;
        } catch (Exception $exception) {
            $this->captureException($request, $exception);
            throw $exception;
        }
    }

    /**
     * @param Request $request
     * @param Response $response
     *
     * @return void
     */
    private function captureResponse(Request $request, Response $response)
    {
        $this->logger->log(
            $response->isFailed() ? PayeverLogger::ERROR : PayeverLogger::DEBUG,
            sprintf("API Request %s", $request->getUrl()),
            [
                'request' => $request->getRequestEntity()->toArray(),
                'response' => $response->getResponseEntity()->toArray(),
            ]
        );
    }

    /**
     * @param Request $request
     * @param Exception $exception
     *
     * @return void
     */
    private function captureException(Request $request, Exception $exception)
    {
        $this->logger->error(
            sprintf("EXCEPTION API Request: %s, URL: %s", $exception->getMessage(), $request->getUrl()),
            [
                'request' => $request->getRequestEntity()->toArray(),
                'trace' => $exception->getTrace(),
            ]
        );
    }
}
