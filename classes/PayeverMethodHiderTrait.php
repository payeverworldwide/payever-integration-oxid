<?php

/**
 * PHP version 7 and 8.4
 *
 * @package   Payever\OXID
 * @author payever GmbH <service@payever.de>
 * @copyright 2017-2021 payever GmbH
 * @license   MIT <https://opensource.org/licenses/MIT>
 */

trait PayeverMethodHiderTrait
{
    /** @var PayeverMethodHider */
    protected $methodHider;

    /**
     * @param PayeverMethodHider $methodHider
     * @return $this
     * @internal
     */
    public function setMethodHider($methodHider)
    {
        $this->methodHider = $methodHider;

        return $this;
    }

    /**
     * @return PayeverMethodHider
     * @codeCoverageIgnore
     */
    protected function getMethodHider()
    {
        return null === $this->methodHider
            ? $this->methodHider = PayeverMethodHider::getInstance()
            : $this->methodHider;
    }
}
