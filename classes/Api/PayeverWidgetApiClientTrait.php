<?php

/**
 * PHP version 5.4 and 7
 *
 * @package     Payever\OXID
 * @author      payever GmbH <service@payever.de>
 * @copyright   2017-2021 payever GmbH
 * @license     MIT <https://opensource.org/licenses/MIT>
 */

use Payever\Sdk\Payments\WidgetsApiClient;

trait PayeverWidgetApiClientTrait
{
    /**
     * @var WidgetsApiClient
     */
    private $widgetsApiClient;

    /**
     * @return WidgetsApiClient
     * @throws Exception
     * @codeCoverageIgnore
     */
    protected function getWidgetApiClient()
    {
        return null === $this->widgetsApiClient
            ? $this->widgetsApiClient = PayeverApiClientProvider::getWidgetsApiClient()
            : $this->widgetsApiClient;
    }

    /**
     * @param WidgetsApiClient $widgetsApiClient
     * @return $this
     * @codeCoverageIgnore
     */
    public function setWidgetApiClient($widgetsApiClient)
    {
        $this->widgetsApiClient = $widgetsApiClient;

        return $this;
    }
}
