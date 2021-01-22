<?php
/**
 * PHP version 5.4 and 7
 *
 * @package     Payever\OXID
 * @author      payever GmbH <service@payever.de>
 * @copyright   2017-2020 payever GmbH
 * @license     MIT <https://opensource.org/licenses/MIT>
 */

use Payever\ExternalIntegration\ThirdParty\Enum\DirectionEnum;

class PayeverImportManager
{
    use PayeverGenericManagerTrait;
    use PayeverSynchronizationManagerTrait;

    /** @var PayeverSubscriptionManager */
    protected $subscriptionManager;

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
        if ($this->isProductsSyncEnabled() && $this->isValidAction($syncAction) && $this->isValidExternalId($externalId)
            && $this->isValidPayload($payload)) {
            $this->getSynchronizationManager()->handleAction(
                $syncAction,
                DirectionEnum::INWARD,
                $payload
            );
        }
        $this->logMessages();

        return !$this->errors;
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
            $this->errors[] = 'The action is not supported';
            $this->debugMessages[] = [
                'message' => sprintf('Attempt to call action "%s"', $action),
            ];
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
            $this->errors[] = 'ExternalId is invalid';
            $this->debugMessages[] = [
                'message' => sprintf(
                    'Expected external id is "%s", actual is "%s"',
                    $expectedExternalId,
                    $externalId
                ),
            ];
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
            $this->errors[] = 'Cannot decode payload';
        } else {
            $this->debugMessages[] = [
                'message' => 'Synchronization payload',
                'context' => [$payload],
            ];
        }

        return $result;
    }

    /**
     * @param PayeverSubscriptionManager $subscriptionManager
     * @return $this
     */
    public function setSubscriptionManager(PayeverSubscriptionManager $subscriptionManager)
    {
        $this->subscriptionManager = $subscriptionManager;

        return $this;
    }

    /**
     * @return PayeverSubscriptionManager
     * @codeCoverageIgnore
     */
    protected function getSubscriptionManager()
    {
        return null === $this->subscriptionManager
            ? $this->subscriptionManager = new PayeverSubscriptionManager()
            : $this->subscriptionManager;
    }
}
