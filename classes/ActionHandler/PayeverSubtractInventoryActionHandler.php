<?php

/**
 * PHP version 5.4 and 7
 *
 * @package     Payever\OXID
 * @author      payever GmbH <service@payever.de>
 * @copyright   2017-2021 payever GmbH
 * @license     MIT <https://opensource.org/licenses/MIT>
 */

class PayeverSubtractInventoryActionHandler extends PayeverSetInventoryActionHandler
{
    /** @var bool */
    protected $considerDiff = true;

    /** @var int */
    protected $sign = -1;

    /**
     * {@inheritDoc}
     */
    public function getSupportedAction()
    {
        return \Payever\ExternalIntegration\ThirdParty\Enum\ActionEnum::ACTION_SUBTRACT_INVENTORY;
    }

    /**
     * Increment updated count
     */
    protected function incrementActionResult()
    {
        $this->actionResult->incrementUpdated();
    }
}
