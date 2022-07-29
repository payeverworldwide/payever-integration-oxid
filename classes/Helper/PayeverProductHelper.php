<?php

/**
 * PHP version 5.4 and 7
 *
 * @package     Payever\OXID
 * @author      payever GmbH <service@payever.de>
 * @copyright   2017-2021 payever GmbH
 * @license     MIT <https://opensource.org/licenses/MIT>
 */

class PayeverProductHelper
{
    /** @var PayeverArticleFactory */
    protected $articleFactory;

    /**
     * @param string $productId
     * @return oxarticle
     * @throws oxSystemComponentException
     */
    public function getProductById($productId)
    {
        $product = $this->getArticleFactory()->create();
        $product->load($productId);

        return $product;
    }

    /**
     * @param string $sku
     * @return object|oxarticle
     * @throws oxSystemComponentException
     */
    public function getProductBySku($sku)
    {
        $product = $this->getArticleFactory()->create();
        $query = $product->buildSelectString(['oxartnum' => $sku]);
        $product->assignRecord($query);

        return $product;
    }

    /**
     * @param PayeverArticleFactory $articleFactory
     * @return $this
     */
    public function setArticleFactory(PayeverArticleFactory $articleFactory)
    {
        $this->articleFactory = $articleFactory;

        return $this;
    }

    /**
     * @return PayeverArticleFactory
     * @codeCoverageIgnore
     */
    protected function getArticleFactory()
    {
        return null === $this->articleFactory
            ? $this->articleFactory = new PayeverArticleFactory()
            : $this->articleFactory;
    }
}
