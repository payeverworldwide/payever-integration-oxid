<?php

/**
 * PHP version 5.4 and 7
 *
 * @package     Payever\OXID
 * @author      payever GmbH <service@payever.de>
 * @copyright   2017-2020 payever GmbH
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
    use PayeverGenericManagerTrait;

    /** @var ThirdPartyApiClient */
    protected $thirdPartyClient;

    /** @var PseudoRandomStringGenerator */
    protected $randomSourceGenerator;

    /**
     * @param bool $isActive
     * @return bool
     * @throws ReflectionException
     * @SuppressWarnings(PHPMD.ElseExpression)
     */
    public function toggleSubscription($isActive)
    {
        $this->cleanMessages();
        $this->getConfigHelper()->reset();
        $externalId = $this->getConfigHelper()->getProductsSyncExternalId();
        if (!$externalId) {
            $externalId = $this->getRandomSourceGenerator()->generate();
            $this->getConfigHelper()->setProductsSyncExternalId($externalId);
        }
        $businessId = $this->getConfigHelper()->getBusinessUuid();
        $subscriptionEntity = new SubscriptionRequestEntity();
        $subscriptionEntity->setExternalId($externalId);
        $subscriptionEntity->setBusinessUuid($businessId);
        $subscriptionEntity->setThirdPartyName(ChannelSet::CHANNEL_OXID);
        $baseUrl = rtrim($this->getConfigHelper()->getShopUrl(), '/');
        foreach ($this->getSupportedActions() as $actionName) {
            $actionUrl = sprintf(
                '%s/index.php?cl=payeverProductsImport&fnc=import&%s=%s&%s=%s',
                $baseUrl,
                'sync_action',
                $actionName,
                'external_id',
                $externalId
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
        $subscriptionResponseEntity = null;
        try {
            if ($isActive) {
                $this->getThirdPartyApiClient()->subscribe($subscriptionEntity);
                $subscriptionRecordResponse = $this->getThirdPartyApiClient()
                    ->getSubscriptionStatus($subscriptionEntity);
                /** @var SubscriptionResponseEntity $subscriptionResponseEntity */
                $subscriptionResponseEntity = $subscriptionRecordResponse->getResponseEntity();
            } else {
                $this->getConfigHelper()->setProductsSyncExternalId(null);
                $this->getThirdPartyApiClient()->unsubscribe($subscriptionEntity);
            }
        } catch (\Exception $e) {
            $this->getConfigHelper()->setProductsSyncExternalId(null);
            $this->errors[] = $e->getMessage();
        }
        $isActive = (bool) $subscriptionResponseEntity;
        $this->logMessages();

        return $isActive;
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
     * @param ThirdPartyApiClient $thirdPartyApiClient
     * @return $this
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
