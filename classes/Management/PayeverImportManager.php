<?php

/**
 * PHP version 5.4 and 7
 *
 * @package     Payever\OXID
 * @author      payever GmbH <service@payever.de>
 * @copyright   2017-2021 payever GmbH
 * @license     MIT <https://opensource.org/licenses/MIT>
 */

class PayeverImportManager
{
    use PayeverGenericManagerTrait;
    use PayeverSubscriptionManagerTrait;
    use PayeverSynchronizationManagerTrait;

    /**
     * @param string $syncAction
     * @param string $externalId
     * @param string $payload
     * @return bool
     * @throws ReflectionException
     */
    public function import($syncAction, $externalId, $payload)
    {
        $this->cleanMessages();
        if (
            $this->isProductsSyncEnabled() && $this->isValidAction($syncAction)
            && $this->isValidExternalId($externalId) && $this->isValidPayload($payload)
        ) {
            $this->getSynchronizationManager()->handleInwardAction($syncAction, $payload);
        }
        $this->logMessages();

        return !$this->getErrors();
    }

    /**
     * @param string $action
     * @return bool
     * @throws \ReflectionException
     */
    protected function isValidAction($action)
    {
        $result = true;
        if (!in_array($action, $this->getSubscriptionManager()->getSupportedActions(), true)) {
            $this->addError('The action is not supported');
            $this->addDebug(sprintf('Attempt to call action "%s"', $action));
            $result = false;
        }

        return $result;
    }

    /**
     * @param string $externalId
     * @return bool
     */
    protected function isValidExternalId($externalId)
    {
        $expectedExternalId = $this->getConfigHelper()->getProductsSyncExternalId();
        $result = $expectedExternalId === $externalId;
        if (!$result) {
            $this->addError('ExternalId is invalid');
            $this->addDebug(sprintf(
                'Expected external id is "%s", actual is "%s"',
                $expectedExternalId,
                $externalId
            ));
        }

        return $result;
    }

    /**
     * @param string $payload
     * @return bool
     */
    protected function isValidPayload($payload)
    {
        $result = \json_decode($payload, true) !== null;
        if (!$result) {
            $this->addError('Cannot decode payload');
            return false;
        }
        $this->addDebug([
            'message' => 'Synchronization payload',
            'context' => [$payload],
        ]);

        return true;
    }
}
