<?php

/**
 * PHP version 5.4 and 7
 *
 * @package     Payever\OXID
 * @author      payever GmbH <service@payever.de>
 * @copyright   2017-2021 payever GmbH
 * @license     MIT <https://opensource.org/licenses/MIT>
 */

use Payever\ExternalIntegration\Core\Enum\ChannelSet;
use Payever\ExternalIntegration\Core\PseudoRandomStringGenerator;
use Payever\ExternalIntegration\ThirdParty\Enum\ActionEnum;
use Payever\ExternalIntegration\ThirdParty\Http\MessageEntity\SubscriptionActionEntity;
use Payever\ExternalIntegration\ThirdParty\Http\RequestEntity\SubscriptionRequestEntity;
use Payever\ExternalIntegration\ThirdParty\Http\ResponseEntity\SubscriptionResponseEntity;
use Payever\ExternalIntegration\ThirdParty\ThirdPartyApiClient;

require_once __DIR__ . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . 'autoload.php';

class PayeverSubscriptionManager
{
    use PayeverActionQueueManagerTrait;
    use PayeverGenericManagerTrait;

    /** @var ThirdPartyApiClient */
    protected $thirdPartyClient;

    /** @var PseudoRandomStringGenerator */
    protected $randomSourceGenerator;

    /**
     * @param bool $isActive
     * @return bool
     */
    public function toggleSubscription($isActive)
    {
        $this->cleanMessages();
        $this->getConfigHelper()->reset();
        $result = false;
        try {
            $isActive ? $this->enable() : $this->disable();
            $result = $isActive;
        } catch (\Exception $exception) {
            $this->cleanup();
            $message = sprintf(
                'Unable to %s subscription: %s',
                $isActive ? 'disable' : 'enable',
                $exception->getMessage()
            );
            $this->addError($message);
        }
        $this->logMessages();

        return $result;
    }

    /**
     * @return array
     * @throws ReflectionException
     */
    public function getSupportedActions()
    {
        return array_diff(
            ActionEnum::enum(),
            [
                ActionEnum::ACTION_PRODUCTS_SYNC,
            ]
        );
    }

    /**
     * @return void
     */
    public function disable()
    {
        try {
            $this->getThirdPartyApiClient()->unsubscribe($this->getSubscriptionEntity());
        } catch (\Exception $e) {
            $this->getLogger()->notice('Unable to unsubscribe');
        }
        $this->cleanup();
    }

    /**
     * @throws \Exception
     * @throws ReflectionException
     */
    private function enable()
    {
        $subscriptionEntity = $this->getSubscriptionEntity();
        $baseUrl = rtrim($this->getConfigHelper()->getShopUrl(), '/');
        foreach ($this->getSupportedActions() as $actionName) {
            $actionUrl = sprintf(
                '%s/index.php?cl=payeverProductsImport&fnc=import&%s=%s&%s=%s',
                $baseUrl,
                'sync_action',
                $actionName,
                'external_id',
                $subscriptionEntity->getExternalId()
            );
            $subscriptionEntity->addAction(
                new SubscriptionActionEntity(
                    [
                        'name' => $actionName,
                        'url' => $actionUrl,
                        'method' => 'POST',
                    ]
                )
            );
        }
        $this->getThirdPartyApiClient()->subscribe($subscriptionEntity);
        $subscriptionRecordResponse = $this->getThirdPartyApiClient()->getSubscriptionStatus($subscriptionEntity);
        /** @var SubscriptionResponseEntity $responseEntity */
        $responseEntity = $subscriptionRecordResponse->getResponseEntity();
        $this->getConfigHelper()->setProductsSyncEnabled((bool) $responseEntity);
        $this->getConfigHelper()->setProductsSyncExternalId($subscriptionEntity->getExternalId());
    }

    /**
     * @return SubscriptionRequestEntity
     * @throws \Exception
     */
    private function getSubscriptionEntity()
    {
        $subscriptionEntity = new SubscriptionRequestEntity();
        $externalId = $this->getConfigHelper()->getProductsSyncExternalId();
        if (!$externalId) {
            $externalId = $this->getRandomSourceGenerator()->generate();
        }
        $subscriptionEntity->setExternalId($externalId);
        $subscriptionEntity->setBusinessUuid($this->configHelper->getBusinessUuid());
        $subscriptionEntity->setThirdPartyName(ChannelSet::CHANNEL_OXID);

        return $subscriptionEntity;
    }

    /**
     * @return void
     */
    protected function cleanup()
    {
        try {
            $this->getActionQueueManager()->emptyQueue();
            $this->getConfigHelper()->setProductsSyncEnabled(false);
            $this->getConfigHelper()->setProductsSyncExternalId('');
        } catch (\Exception $exception) {
            $this->getLogger()->warning($exception->getMessage());
        }
    }

    /**
     * @param ThirdPartyApiClient $thirdPartyApiClient
     * @return $this
     * @internal
     */
    public function setThirdPartyApiClient(ThirdPartyApiClient $thirdPartyApiClient)
    {
        $this->thirdPartyClient = $thirdPartyApiClient;

        return $this;
    }

    /**
     * @return ThirdPartyApiClient
     * @throws Exception
     * @codeCoverageIgnore
     */
    protected function getThirdPartyApiClient()
    {
        return null === $this->thirdPartyClient
            ? $this->thirdPartyClient = PayeverApiClientProvider::getThirdPartyApiClient()
            : $this->thirdPartyClient;
    }

    /**
     * @param PseudoRandomStringGenerator $randomSourceGenerator
     * @return $this
     * @internal
     */
    public function setRandomSourceGenerator(PseudoRandomStringGenerator $randomSourceGenerator)
    {
        $this->randomSourceGenerator = $randomSourceGenerator;

        return $this;
    }

    /**
     * @return PseudoRandomStringGenerator
     * @codeCoverageIgnore
     */
    protected function getRandomSourceGenerator()
    {
        return null === $this->randomSourceGenerator
            ? $this->randomSourceGenerator = new PseudoRandomStringGenerator()
            : $this->randomSourceGenerator;
    }
}
