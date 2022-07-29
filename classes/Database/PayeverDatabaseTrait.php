<?php

/**
 * PHP version 5.4 and 7
 *
 * @package     Payever\OXID
 * @author      payever GmbH <service@payever.de>
 * @copyright   2017-2021 payever GmbH
 * @license     MIT <https://opensource.org/licenses/MIT>
 */

trait PayeverDatabaseTrait
{
    /** @var DatabaseInterface|oxDb|oxLegacyDb */
    protected $database;

    /**
     * @param DatabaseInterface|oxDb|oxLegacyDb $database
     * @return $this
     */
    public function setDatabase($database)
    {
        $this->database = $database;

        return $this;
    }

    /**
     * @return DatabaseInterface|oxDb|oxLegacyDb
     * @throws oxConnectionException
     * @codeCoverageIgnore
     */
    protected function getDatabase()
    {
        return null === $this->database
            ? $this->database = oxDb::getDb(oxDb::FETCH_MODE_ASSOC)
            : $this->database;
    }
}
