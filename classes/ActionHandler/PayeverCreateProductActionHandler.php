<?php

/**
 * PHP version 5.4 and 7
 *
 * @package     Payever\OXID
 * @author      payever GmbH <service@payever.de>
 * @copyright   2017-2021 payever GmbH
 * @license     MIT <https://opensource.org/licenses/MIT>
 */

class PayeverCreateProductActionHandler extends PayeverUpdateProductActionHandler
{
    /**
     * {@inheritDoc}
     */
    public function getSupportedAction()
    {
        return \Payever\ExternalIntegration\ThirdParty\Enum\ActionEnum::ACTION_CREATE_PRODUCT;
    }

    /**
     * Increment created count
     */
    protected function incrementActionResult()
    {
        $this->actionResult->incrementCreated();
    }
}
