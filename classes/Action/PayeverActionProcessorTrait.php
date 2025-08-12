<?php

/**
 * PHP version 5.4 and 7
 *
 * @package     Payever\OXID
 * @author      payever GmbH <service@payever.de>
 * @copyright   2017-2021 payever GmbH
 * @license     MIT <https://opensource.org/licenses/MIT>
 */

trait PayeverActionProcessorTrait
{
    /** @var PayeverActionProcessor */
    protected $actionProcessor;

    /**
     * @param PayeverActionProcessor $actionProcessor
     * @return $this
     * @internal
     */
    public function setActionProcessor(PayeverActionProcessor $actionProcessor)
    {
        $this->actionProcessor = $actionProcessor;

        return $this;
    }

    /**
     * @return PayeverActionProcessor
     * @codeCoverageIgnore
     */
    protected function getActionProcessor()
    {
        return null === $this->actionProcessor
            ? $this->actionProcessor = new PayeverActionProcessor()
            : $this->actionProcessor;
    }
}
