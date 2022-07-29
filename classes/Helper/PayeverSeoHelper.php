<?php

/**
 * PHP version 5.4 and 7
 *
 * @package     Payever\OXID
 * @author      payever GmbH <service@payever.de>
 * @copyright   2017-2021 payever GmbH
 * @license     MIT <https://opensource.org/licenses/MIT>
 */

class PayeverSeoHelper
{
    use PayeverCategoryHelperTrait;
    use PayeverConfigHelperTrait;
    use PayeverConfigTrait;

    /** @var oxSeoEncoderArticle */
    protected $productSeoEncoder;

    /**
     * @param oxarticle $product
     * @throws oxConnectionException
     * @throws oxSystemComponentException
     */
    public function processProductSeo($product)
    {
        $productId = $product->getId();
        $shopId = $this->getConfig()->getShopId();
        $defaultCategoryId = $this->getCategoryHelper()->getDefaultCategory()->getId();
        foreach ($this->getConfigHelper()->getLanguageIds() as $langId) {
            $sParams = "oxparams = '$defaultCategoryId'";
            $this->getProdutSeoEncoder()->markAsExpired($productId, $shopId, 1, $langId, $sParams);
            $this->getProdutSeoEncoder()->addSeoEntry(
                $productId,
                $shopId,
                $langId,
                $product->getBaseStdLink($langId, true, false),
                $product->getFieldData('oxartnum'),
                'oxarticle',
                0,
                '',
                '',
                $defaultCategoryId,
                true,
                $productId
            );
        }
    }

    /**
     * @param $productSeoEncoder
     * @return $this
     */
    public function setProductSeoEncoder($productSeoEncoder)
    {
        $this->productSeoEncoder = $productSeoEncoder;

        return $this;
    }

    /**
     * @return oxSeoEncoderArticle
     * @codeCoverageIgnore
     */
    protected function getProdutSeoEncoder()
    {
        return null === $this->productSeoEncoder
            ? $this->productSeoEncoder = oxRegistry::get('oxSeoEncoderArticle')
            : $this->productSeoEncoder;
    }
}
