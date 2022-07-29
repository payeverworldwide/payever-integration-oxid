<?php

/**
 * PHP version 5.4 and 7
 *
 * @package     Payever\OXID
 * @author      payever GmbH <service@payever.de>
 * @copyright   2017-2021 payever GmbH
 * @license     MIT <https://opensource.org/licenses/MIT>
 */

use Payever\ExternalIntegration\Products\Http\MessageEntity\ProductCategoryEntity;
use Payever\ExternalIntegration\Products\Http\RequestEntity\ProductRequestEntity;

class PayeverCategoryManager
{
    use PayeverDatabaseTrait;
    use PayeverCategoryFactoryTrait;
    use PayeverCategoryHelperTrait;
    use PayeverGenericManagerTrait;

    /** @var Object2CategoryFactory */
    protected $object2CategoryFactory;

    /**
     * @param oxarticle $product
     * @return array
     * @throws oxSystemComponentException
     */
    public function getCategoryNames($product)
    {
        $categoryIds = $product->getCategoryIds();
        $categoryNames = [];
        foreach ($categoryIds as $categoryId) {
            $category = $this->getCategoryFactory()->create();
            $category->loadInLang($this->getConfigHelper()->getDefaultLanguageId(), $categoryId);
            $name = $category->getTitle();
            if ($name) {
                $categoryNames[] = $name;
            }
        }

        return $categoryNames;
    }

    /**
     * @param oxarticle $product
     * @param ProductRequestEntity $requestEntity
     * @throws oxConnectionException
     * @throws oxSystemComponentException
     * @SuppressWarnings(PHPMD.ElseExpression)
     */
    public function setCategories($product, ProductRequestEntity $requestEntity)
    {
        $productId = $product->getId();
        $defaultCategoryId = $this->getCategoryHelper()->getDefaultCategory()->getId();
        $categoryIds = [$defaultCategoryId => $defaultCategoryId];
        $requestEntities = $requestEntity->getCategories();
        foreach ($requestEntities as $requestCategoryEntity) {
            $categoryName = $this->getRequestCategoryEntityName($requestCategoryEntity);
            if ($categoryName) {
                $matchedCategory = $this->getCategoryHelper()->getCategoryByTitle($categoryName);
                if ($matchedCategory) {
                    $categoryIds[$matchedCategory->getId()] = $matchedCategory->getId();
                } else {
                    $category = $this->getCategoryFactory()->create();
                    $data = [
                        'oxtitle' => $categoryName,
                        'oxparentid' => $defaultCategoryId,
                    ];
                    foreach ($this->getConfigHelper()->getLanguageIds() as $langId) {
                        $category->setLanguage($langId);
                        $category->assign($data);
                        if ($category->save()) {
                            $categoryIds[$category->getId()] = $category->getId();
                        }
                    }
                }
            }
        }
        $this->getDatabase()->execute(
            'DELETE FROM oxobject2category WHERE OXOBJECTID = ?',
            [$productId]
        );
        foreach ($categoryIds as $categoryId) {
            $object2Category = $this->getObject2CategoryFactory()->create();
            $object2Category->setId($this->getConfigHelper()->generateUID());
            $object2Category->assign([
                'oxobjectid' => $productId,
                'oxcatnid' => $categoryId,
                'oxtime' => time(),
            ]);
            $object2Category->save();
        }
    }

    /**
     * @param ProductCategoryEntity|string $requestCategoryEntity
     * @return string
     */
    protected function getRequestCategoryEntityName($requestCategoryEntity)
    {
        return $requestCategoryEntity instanceof ProductCategoryEntity
            ? $requestCategoryEntity->getTitle()
            : $requestCategoryEntity;
    }

    /**
     * @param Object2CategoryFactory $object2CategoryFactory
     * @return $this
     */
    public function setObject2CategoryFactory(Object2CategoryFactory $object2CategoryFactory)
    {
        $this->object2CategoryFactory = $object2CategoryFactory;

        return $this;
    }

    /**
     * @return Object2CategoryFactory
     * @codeCoverageIgnore
     */
    protected function getObject2CategoryFactory()
    {
        return null === $this->object2CategoryFactory
            ? $this->object2CategoryFactory = new Object2CategoryFactory()
            : $this->object2CategoryFactory;
    }
}
