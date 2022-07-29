<?php

/**
 * PHP version 5.4 and 7
 *
 * @package     Payever\OXID
 * @author      payever GmbH <service@payever.de>
 * @copyright   2017-2021 payever GmbH
 * @license     MIT <https://opensource.org/licenses/MIT>
 */

use Payever\ExternalIntegration\Core\Http\RequestEntity;
use Payever\ExternalIntegration\Products\Http\RequestEntity\ProductRequestEntity;

class PayeverUpdateProductActionHandler extends PayeverAbstractActionHandler
{
    use PayeverProductTransformerTrait;

    /** @var PayeverSeoHelper */
    protected $seoHelper;

    /**
     * {@inheritDoc}
     */
    public function getSupportedAction()
    {
        return \Payever\ExternalIntegration\ThirdParty\Enum\ActionEnum::ACTION_UPDATE_PRODUCT;
    }

    /**
     * @param RequestEntity|ProductRequestEntity $entity
     * @throws oxConnectionException
     * @throws oxSystemComponentException
     */
    protected function process($entity)
    {
        if (!$this->getProductTransformer()->isTypeSupported($entity->getType())) {
            throw new \BadMethodCallException('Product type is not supported');
        }
        if ($entity->isVariant()) {
            throw new \BadMethodCallException(
                'Product is variant. This integration only supports full product payload'
            );
        }
        $product = $this->getProductTransformer()->transformFromPayeverIntoOxid($entity);
        $this->pushToRegistry($product);
        if ($product->save()) {
            if (null === $product->getFieldData('oxvarcount')) {
                $product->assign(['oxvarcount' => 0]);
            }
            $this->getSeoHelper()->processProductSeo($product);
            $collection = $product->getFullVariants(false);
            if ($collection instanceof oxArticleList) {
                foreach ($collection->getArray() as $variant) {
                    $this->getSeoHelper()->processProductSeo($variant);
                }
            }
        }
    }

    /**
     * Increment updated count
     */
    protected function incrementActionResult()
    {
        $this->actionResult->incrementUpdated();
    }

    /**
     * @param PayeverSeoHelper $seoHelper
     * @return $this
     */
    public function setSeoHelper(PayeverSeoHelper $seoHelper)
    {
        $this->seoHelper = $seoHelper;

        return $this;
    }

    /**
     * @return PayeverSeoHelper
     * @codeCoverageIgnore
     */
    protected function getSeoHelper()
    {
        return null === $this->seoHelper
            ? $this->seoHelper = new PayeverSeoHelper()
            : $this->seoHelper;
    }
}
