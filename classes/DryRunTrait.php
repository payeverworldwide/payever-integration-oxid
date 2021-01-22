<?php

/**
 * PHP version 5.4 and 7
 *
 * @package   Payever\OXID
 * @author payever GmbH <service@payever.de>
 * @copyright 2017-2021 payever GmbH
 * @license   MIT <https://opensource.org/licenses/MIT>
 */

trait DryRunTrait
{
    /** @var bool */
    protected $dryRun;

    /**
     * @param bool $flag
     * @return $this
     */
    public function setDryRun($flag)
    {
        $this->dryRun = $flag;

        return $this;
    }
}
