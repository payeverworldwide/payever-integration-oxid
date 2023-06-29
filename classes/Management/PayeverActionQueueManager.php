<?php

/**
 * PHP version 5.4 and 7
 *
 * @package     Payever\OXID
 * @author      payever GmbH <service@payever.de>
 * @copyright   2017-2021 payever GmbH
 * @license     MIT <https://opensource.org/licenses/MIT>
 */

class PayeverActionQueueManager
{
    use PayeverDatabaseTrait;
    use PayeverLoggerTrait;
    use PayeverSynchronizationQueueFactoryTrait;

    /** @var PayeverSynchronizationQueueListFactory */
    protected $syncQueueListFactory;

    /**
     * @return void
     */
    public function emptyQueue()
    {
        try {
            $this->getDatabase()->execute('DELETE FROM payeversynchronizationqueue WHERE 1');
        } catch (Exception $exception) {
            $this->getLogger()->warning($exception->getMessage());
        }
    }

    /**
     * @param int $limit
     * @return array
     * @SuppressWarnings(PHPMD.ErrorControlOperator)
     */
    public function getItems($limit)
    {
        $collection = $this->getSyncQueueListFactory()->create();
        $collection->clear();
        $listObject = $this->getSynchronizationQueueFactory()->create();
        method_exists($collection, 'setBaseObject') && $collection->setBaseObject($listObject);
        $query = $listObject->buildSelectString(null);
        $collection->setSqlLimit(0, $limit);
        $query .= ' ORDER BY `inc`';
        $collection->selectString($query);
        $items = $collection->getArray();
        @usort($items, function ($itemA, $itemB) {
            /** @var payeversynchronizationqueue $itemA */
            /** @var payeversynchronizationqueue $itemB */
            return $itemA->getFieldData('inc') < $itemB->getFieldData('inc') ? -1 : 1;
        });

        return $items;
    }

    /**
     * @param string $direction
     * @param string $action
     * @param string $payload
     */
    public function addItem($direction, $action, $payload)
    {
        $item = $this->getSynchronizationQueueFactory()->create();
        $item->assign([
            'action' => $action,
            'direction' => $direction,
            'payload' => $payload,
            'created_at' => date(DATE_ATOM),
        ]);
        try {
            $item->save();
        } catch (\Exception $e) {
            $this->getLogger()->warning($e->getMessage());
        }
    }

    /**
     * @param string $itemId
     * @param int $attempt
     */
    public function setAttempt($itemId, $attempt)
    {
        try {
            $this->getDatabase()->execute(
                sprintf(
                    'UPDATE payeversynchronizationqueue set attempt = "%s" WHERE OXID = "%s"',
                    $attempt,
                    $itemId
                )
            );
        } catch (\Exception $exception) {
            $this->getLogger()->warning($exception->getMessage());
        }
    }

    /**
     * @param string $itemId
     */
    public function delete($itemId)
    {
        try {
            $this->getDatabase()->execute(
                sprintf(
                    'DELETE FROM payeversynchronizationqueue WHERE OXID = "%s"',
                    $itemId
                )
            );
        } catch (\Exception $exception) {
            $this->getLogger()->warning($exception);
        }
    }

    /**
     * @param PayeverSynchronizationQueueListFactory $syncQueueListFactory
     * @return $this
     * @internal
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
