<?php

/**
 * PHP version 5.4 and 7
 *
 * @package     Payever\OXID
 * @author      payever GmbH <service@payever.de>
 * @copyright   2017-2021 payever GmbH
 * @license     MIT <https://opensource.org/licenses/MIT>
 */

use Payever\ExternalIntegration\Products\Enum\ProductTypeEnum;
use Payever\ExternalIntegration\Products\Http\RequestEntity\ProductRemovedRequestEntity;
use Payever\ExternalIntegration\Products\Http\RequestEntity\ProductRequestEntity;

/**
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class PayeverProductTransformer
{
    use PayeverCategoryFactoryTrait;
    use PayeverConfigHelperTrait;
    use PayeverConfigTrait;
    use PayeverProductHelperTrait;

    const STOCK_FLAG_STANDARD = 1;

    /** @var PayeverCategoryManager */
    protected $categoryManager;

    /** @var PayeverPriceManager */
    protected $priceManager;

    /** @var PayeverShippingManager */
    protected $shippingManager;

    /** @var PayeverGalleryManager */
    protected $galleryManager;

    /** @var PayeverOptionManager */
    protected $optionManager;

    /**
     * @param string $type
     * @return bool
     */
    public function isTypeSupported($type)
    {
        return in_array($type, [ProductTypeEnum::TYPE_DIGITAL, ProductTypeEnum::TYPE_PHYSICAL], true);
    }

    /**
     * @param oxarticle $product
     * @param bool $variantMode
     * @return ProductRequestEntity
     * @throws oxSystemComponentException
     * @SuppressWarnings(PHPMD.BooleanArgumentFlag)
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     * @SuppressWarnings(PHPMD.ElseExpression)
     */
    public function transformFromOxidIntoPayever($product, $variantMode = false)
    {
        $productRequestEntity = new PayeverProductRequestEntity();
        $sku = $product->getFieldData('oxartnum');
        $title = $product->getFieldData('oxtitle');
        $description = $product->getFieldData('oxshortdesc');
        $isActive = (bool) $product->getFieldData('oxactive');
        $type = $product->getFieldData('oxnonmaterial')
            ? ProductTypeEnum::TYPE_DIGITAL
            : ProductTypeEnum::TYPE_PHYSICAL;
        $categoryIds = !$variantMode ? $product->getCategoryIds() : [];
        $categoryNames = [];
        foreach ($categoryIds as $categoryId) {
            $category = $this->getCategoryFactory()->create();
            $category->loadInLang($this->getConfigHelper()->getDefaultLanguageId(), $categoryId);
            $name = $category->getFieldData('oxtitle');
            if ($name) {
                $categoryNames[] = $name;
            }
        }
        $gallery = !$variantMode ? $this->getGalleryManager()->getGallery($product) : [];
        $price = (float) $product->getFieldData('oxprice');
        $salePrice = null;
        if ($product->getFieldData('oxtprice')) {
            $price = (float) $product->getFieldData('oxtprice');
            $salePrice = (float) $product->getFieldData('oxprice');
        }
        $productRequestEntity
            ->setBusinessUuid($this->getConfigHelper()->getBusinessUuid())
            ->setExternalId($this->getConfigHelper()->getProductsSyncExternalId())
            ->setActive($isActive)
            ->setType($type)
            ->setSku($sku)
            ->setTitle($title)
            ->setDescription($description)
            ->setPrice($price)
            ->setSalePrice($salePrice)
            ->setCurrency($this->getPriceManager()->getCurrencyName())
            ->setShipping($this->getShippingManager()->getShipping($product))
            ->setCategories($categoryNames)
            ->setImages($gallery);
        if (!$variantMode) {
            $product->setInList();
            $variants = $product->getVariants();
            if ($variants) {
                $requestVariants = [];
                foreach ($variants as $variant) {
                    $requestVariants[] = $this->transformFromOxidIntoPayever($variant, true);
                }
                $productRequestEntity->setVariants($requestVariants);
            }
        }

        return $productRequestEntity;
    }

    /**
     * @param ProductRequestEntity $requestEntity
     * @return oxarticle
     * @throws oxConnectionException
     * @throws oxSystemComponentException
     */
    public function transformFromPayeverIntoOxid(ProductRequestEntity $requestEntity)
    {
        $product = $this->getProductHelper()->getProductBySku($requestEntity->getSku());
        $this->fillProductFromRequestEntity($requestEntity, $product);
        $variants = $requestEntity->getVariants();
        if ($variants) {
            $this->getOptionManager()->setSelectionName($product, $variants);
            foreach ($variants as $variantRequestEntity) {
                if (!$variantRequestEntity->getVatRate()) {
                    $variantRequestEntity->setVatRate($requestEntity->getVatRate());
                }
                $this->processVariantFromRequestEntity($variantRequestEntity, $product);
            }
            $product->assign(['oxvarcount' => count($variants)]);
        }

        return $product;
    }

    /**
     * @param ProductRequestEntity $requestEntity
     * @param oxarticle $product
     * @throws oxConnectionException
     * @throws oxSystemComponentException
     */
    protected function fillProductFromRequestEntity(ProductRequestEntity $requestEntity, $product)
    {
        $title = $requestEntity->getTitle();
        $data = [
            'oxartnum' => $requestEntity->getSku(),
            'oxtitle' => $title,
            'oxsearchkeys' => str_replace(' ', ',', $title),
            'oxshortdesc' => $requestEntity->getDescription(),
            'oxactive' => $requestEntity->getActive(),
            'oxnonmaterial' => $requestEntity->getType() !== ProductTypeEnum::TYPE_PHYSICAL,
            'oxstock' => 0,
            'oxvarstock' => 0,
            'oxvarcount' => 0,
            'oxparentid' => null,
            'oxshopid' => $this->getConfig()->getShopId(),
        ];
        PayeverRegistry::set(PayeverRegistry::LAST_INWARD_PROCESSED_PRODUCT, $product);
        foreach ($this->getConfigHelper()->getLanguageIds() as $langId) {
            $product->setLanguage($langId);
            $product->assign($data);
            $product->save();
        }
        $this->getCategoryManager()->setCategories($product, $requestEntity);
        $this->getPriceManager()->setVat($product, $requestEntity);
        $this->getPriceManager()->setPrice($product, $requestEntity);
        $this->getShippingManager()->setShipping($product, $requestEntity);
        $this->getGalleryManager()->appendGallery($product, $requestEntity);
    }

    /**
     * @param ProductRequestEntity $variantRequestEntity
     * @param oxarticle $product
     * @throws oxConnectionException
     * @throws oxSystemComponentException
     */
    protected function processVariantFromRequestEntity(ProductRequestEntity $variantRequestEntity, $product)
    {
        $variant = $this->getProductHelper()->getProductBySku($variantRequestEntity->getSku());
        $this->fillProductFromRequestEntity($variantRequestEntity, $variant);
        $variant->setAdminMode(true);
        $variant->setLoadParentData(false);
        $variant->assign([
            'oxparentid' => $product->getId(),
            'oxstockflag' => self::STOCK_FLAG_STANDARD,
        ]);
        PayeverRegistry::set(PayeverRegistry::LAST_INWARD_PROCESSED_PRODUCT, $variant);
        $this->getOptionManager()->setVariantSelectionName($variant, $variantRequestEntity);
        $variant->save();
    }

    /**
     * @param oxarticle $product
     * @return ProductRemovedRequestEntity
     */
    public function transformRemovedOxidIntoPayever($product)
    {
        $productRemovedRequest = new ProductRemovedRequestEntity();
        $productRemovedRequest->setExternalId($this->getConfigHelper()->getProductsSyncExternalId());
        $productRemovedRequest->setSku($product->getFieldData('oxartnum'));

        return $productRemovedRequest;
    }

    /**
     * @param ProductRemovedRequestEntity $productRemovedRequest
     * @return oxarticle
     * @throws oxSystemComponentException
     */
    public function transformRemovedPayeverIntoOxid(ProductRemovedRequestEntity $productRemovedRequest)
    {
        return $this->getProductHelper()->getProductBySku($productRemovedRequest->getSku());
    }

    /**
     * @param PayeverCategoryManager $categoryManager
     * @return $this
     */
    public function setCategoryManager(PayeverCategoryManager $categoryManager)
    {
        $this->categoryManager = $categoryManager;

        return $this;
    }

    /**
     * @return PayeverCategoryManager
     * @codeCoverageIgnore
     */
    protected function getCategoryManager()
    {
        return null === $this->categoryManager
            ? $this->categoryManager = new PayeverCategoryManager()
            : $this->categoryManager;
    }

    /**
     * @param PayeverPriceManager $priceManager
     * @return $this
     */
    public function setPriceManager(PayeverPriceManager $priceManager)
    {
        $this->priceManager = $priceManager;

        return $this;
    }

    /**
     * @return PayeverPriceManager
     * @codeCoverageIgnore
     */
    protected function getPriceManager()
    {
        return null === $this->priceManager
            ? $this->priceManager = new PayeverPriceManager()
            : $this->priceManager;
    }

    /**
     * @param PayeverShippingManager $shippingManager
     * @return $this
     */
    public function setShippingManager(PayeverShippingManager $shippingManager)
    {
        $this->shippingManager = $shippingManager;

        return $this;
    }

    /**
     * @return PayeverShippingManager
     * @codeCoverageIgnore
     */
    protected function getShippingManager()
    {
        return null === $this->shippingManager
            ? $this->shippingManager = new PayeverShippingManager()
            : $this->shippingManager;
    }

    /**
     * @param PayeverGalleryManager $galleryManager
     * @return $this
     */
    public function setGalleryManager(PayeverGalleryManager $galleryManager)
    {
        $this->galleryManager = $galleryManager;

        return $this;
    }

    /**
     * @return PayeverGalleryManager
     * @codeCoverageIgnore
     */
    protected function getGalleryManager()
    {
        return null === $this->galleryManager
            ? $this->galleryManager = new PayeverGalleryManager()
            : $this->galleryManager;
    }

    /**
     * @param PayeverOptionManager $optionManager
     * @return $this
     */
    public function setOptionManager(PayeverOptionManager $optionManager)
    {
        $this->optionManager = $optionManager;

        return $this;
    }

    /**
     * @return PayeverOptionManager
     * @codeCoverageIgnore
     */
    protected function getOptionManager()
    {
        return null === $this->optionManager
            ? $this->optionManager = new PayeverOptionManager()
            : $this->optionManager;
    }
}
