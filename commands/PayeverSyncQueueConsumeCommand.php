<?php

/**
 * PHP version 5.4 and 7
 *
 * @package     Payever\OXID
 * @author      payever GmbH <service@payever.de>
 * @copyright   2017-2021 payever GmbH
 * @license     MIT <https://opensource.org/licenses/MIT>
 */

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class PayeverSyncQueueConsumeCommand extends \Symfony\Component\Console\Command\Command
{
    use PayeverActionQueueManagerTrait;
    use PayeverLoggerTrait;
    use PayeverSynchronizationManagerTrait;

    /**
     * How many queue items we process during one cron job run
     */
    const QUEUE_PROCESSING_SIZE = 25;

    /**
     * How many times we give queue item a chance to be processed
     */
    const QUEUE_PROCESSING_MAX_ATTEMPTS = 2;

    /**
     * {@inheritDoc}
     */
    protected function configure()
    {
        $this->setName('payever:synchronization:queue:consume');
    }

    /**
     * {@inheritDoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        if (!$this->getSynchronizationManager()->isEnabled()) {
            $output->writeln('Products and inventory synchronization is disabled');
            return;
        }
        $items = $this->getActionQueueManager()->getItems(self::QUEUE_PROCESSING_SIZE);
        $output->writeln('START: Processing payever sync action queue');
        $processed = 0;
        $attempt = 0;
        /** @var payeversynchronizationqueue $item */
        foreach ($items as $item) {
            try {
                $queueId = $item->getFieldData('oxid');
                $attempt = $item->getFieldData('attempt');
                $payloadField = $item->payeversynchronizationqueue__payload;
                $payload = !empty($payloadField) ? $payloadField->rawValue : null;
                $this->getSynchronizationManager()
                    ->setIsInstantMode(true)
                    ->handleAction(
                        $item->getFieldData('action'),
                        $item->getFieldData('direction'),
                        \json_decode($payload, true)
                    );
                $processed++;
                $this->getActionQueueManager()->delete($queueId);
            } catch (\Exception $exception) {
                $this->getLogger()->warning($exception->getMessage());
                if ($attempt >= self::QUEUE_PROCESSING_MAX_ATTEMPTS) {
                    $output->writeln(
                        'Queue item exceeded max processing attempts count and going to be removed.' .
                        'This may lead to data loss and out of sync state.'
                    );
                    $this->getActionQueueManager()->delete($queueId);
                    continue;
                }
                $this->getActionQueueManager()->setAttempt($queueId, ++$attempt);
            }
        }
        $output->writeln(sprintf('FINISH: Processed %d queue records', $processed));
    }
}
