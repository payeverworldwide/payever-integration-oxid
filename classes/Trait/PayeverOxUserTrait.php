<?php

/**
 * PHP version 7 and 8.4
 *
 * @package     Payever\OXID
 * @author      payever GmbH <service@payever.de>
 * @copyright   2017-2021 payever GmbH
 * @license     MIT <https://opensource.org/licenses/MIT>
 */

trait PayeverOxUserTrait
{
    /**
     * @var oxuser
     */
    private $oxUser;

    /**
     * @codeCoverageIgnore
     * @return oxuser
     */
    protected function getOxUser()
    {
        if ($this->oxUser === null) {
            return oxNew('oxuser');
        }

        return $this->oxUser;
    }

    /**
     * @param oxuser $oxUser
     */
    public function setOxUser($oxUser)
    {
        $this->oxUser = $oxUser;

        return $this;
    }
}
