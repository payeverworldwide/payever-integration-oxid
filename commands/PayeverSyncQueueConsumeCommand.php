<?php
/**
 * PHP version 5.4 and 7
 *
 * @package     Payever\OXID
 * @author      payever GmbH <service@payever.de>
 * @copyright   2017-2020 payever GmbH
 * @license     MIT <https://opensource.org/licenses/MIT>
 */

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class PayeverSyncQueueConsumeCommand extends \Symfony\Component\Console\Command\Command
{
    use PayeverDatabaseTrait;
    use PayeverLoggerTrait;
    use PayeverSynchronizationManagerTrait;
    use PayeverSynchronizationQueueFactoryTrait;

    /**
     * How many queue items we process during one cron job run
     */
    const QUEUE_PROCESSING_SIZE = 25;

    /**
     * How many times we give queue item a chance to be processed
     */
    const QUEUE_PROCESSING_MAX_ATTEMPTS = 2;

    /** @var PayeverSynchronizationQueueListFactory */
    protected $syncQueueListFactory;

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
        $collection = $this->getSyncQueueListFactory()->create();
        $collection->clear();
        $listObject = $this->getSynchronizationQueueFactory()->create();
        method_exists($collection, 'setBaseObject') && $collection->setBaseObject($listObject);
        $query = $listObject->buildSelectString(null);
        $collection->setSqlLimit(0, self::QUEUE_PROCESSING_SIZE);
        $query .= ' ORDER BY `inc`';
        $collection->selectString($query);
        $items = $collection->getArray();
        @usort($items, function ($itemA, $itemB) {
            /** @var payeversynchronizationqueue $itemA */
            /** @var payeversynchronizationqueue $itemB */
            return $itemA->getFieldData('inc') < $itemB->getFieldData('inc') ? -1 : 1;
        });

        $output->writeln('START: Processing payever sync action queue');
        $processed = 0;
        /** @var payeversynchronizationqueue $item */
        foreach ($items as $item) {
            try {
                $id = $item->getFieldData('oxid');
                $attempt = $item->getFieldData('attempt');
                $payloadField = $item->payeversynchronizationqueue__payload;
                $payload = !empty($payloadField) ? $payloadField->rawValue : null;
                $this->getSynchronizationManager()->handleAction(
                    $item->getFieldData('action'),
                    $item->getFieldData('direction'),
                    \json_decode($payload, true),
                    true
                );
                $processed++;
                $this->deleteItem($id);
            } catch (\Exception $e) {
                $this->getLogger()->warning($e->getMessage());
                if ($attempt >= self::QUEUE_PROCESSING_MAX_ATTEMPTS) {
                    $output->writeln(
                        'Queue item exceeded max processing attempts count and going to be removed.' .
                        'This may lead to data loss and out of sync state.'
                    );
                    $this->deleteItem($id);
                } else {
                    $this->getDatabase()->execute(
                        sprintf(
                            'UPDATE payeversynchronizationqueue set attempt = "%s" WHERE OXID = "%s"',
                            ++$attempt,
                            $id
                        )
                    );
                }
            }
        }
        $output->writeln(sprintf('FINISH: Processed %d queue records', $processed));
    }

    /**
     * @param string $id
     * @throws oxConnectionException
     */
    protected function deleteItem($id)
    {
        $this->getDatabase()->execute(
            sprintf(
                'DELETE FROM payeversynchronizationqueue WHERE OXID = "%s"',
                $id
            )
        );
    }

    /**
     * @param PayeverSynchronizationQueueListFactory $syncQueueListFactory
     * @return $this
     */
    public function setSyncQueueListFactory(PayeverSynchronizationQueueListFactory $syncQueueListFactory)
    {
        $this->syncQueueListFactory = $syncQueueListFactory;

        return $this;
    }

    /**
     * @return PayeverSynchronizationQueueListFactory
     * @codeCoverageIgnore
     */
    protected function getSyncQueueListFactory()
    {
        return null === $this->syncQueueListFactory
            ? new PayeverSynchronizationQueueListFactory()
            : $this->syncQueueListFactory;
    }
}
