<?php

/**
 * PHP version 5.4 and 7
 *
 * @package     Payever\OXID
 * @author      payever GmbH <service@payever.de>
 * @copyright   2017-2021 payever GmbH
 * @license     MIT <https://opensource.org/licenses/MIT>
 */

trait PayeverSessionTrait
{
    /** @var oxsession */
    private $oSession;

    /**
     * @return oxsession
     * @codeCoverageIgnore
     */
    public function getSession()
    {
        if ($this->oSession == null) {
            $this->oSession = oxRegistry::getSession();
        }

        return $this->oSession;
    }

    /**
     * @param oxsession $oSession
     * @return $this
     * @internal
     */
    public function setSession(oxsession $oSession)
    {
        $this->oSession = $oSession;

        return $this;
    }
}
