<?php

/**
 * PHP version 5.4 and 7
 *
 * @package     Payever\OXID
 * @author      payever GmbH <service@payever.de>
 * @copyright   2017-2021 payever GmbH
 * @license     MIT <https://opensource.org/licenses/MIT>
 */

use Psr\Log\LoggerInterface;

trait PayeverLoggerTrait
{
    /** @var LoggerInterface */
    protected $logger;

    /**
     * @param LoggerInterface $logger
     * @return $this
     * @internal
     */
    public function setLogger(LoggerInterface $logger)
    {
        $this->logger = $logger;

        return $this;
    }

    /**
     * @return LoggerInterface
     * @codeCoverageIgnore
     */
    protected function getLogger()
    {
        return null === $this->logger
            ? $this->logger = PayeverConfig::getLogger()
            : $this->logger;
    }
}
