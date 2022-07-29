<?php

/**
 * PHP version 5.4 and 7
 *
 * @package     Payever\OXID
 * @author      payever GmbH <service@payever.de>
 * @copyright   2017-2021 payever GmbH
 * @license     MIT <https://opensource.org/licenses/MIT>
 */

use Payever\ExternalIntegration\Inventory\InventoryApiClient;
use Payever\ExternalIntegration\Products\ProductsApiClient;

class PayeverExportManager
{
    use PayeverConfigTrait;
    use PayeverDatabaseTrait;
    use PayeverGenericManagerTrait;
    use PayeverSubscriptionManagerTrait;

    const DEFAULT_LIMIT = 5;

    /** @var oxlang */
    protected $language;

    /** @var InventoryApiClient */
    protected $inventoryApiClient;

    /** @var ProductsApiClient */
    protected $productsApiClient;

    /** @var PayeverArticleListCollectionFactory */
    protected $articleListCollectionFactory;

    /** @var int|null */
    private $nextPage;

    /** @var int */
    private $aggregate = 0;

    /**
     * @param int $currentPage
     * @param int $aggregate
     * @return bool
     */
    public function export($currentPage, $aggregate)
    {
        $result = false;
        $this->cleanMessages();
        try {
            if ($this->isProductsSyncEnabled() && $this->isProductsOutwardSyncEnabled()) {
                $this->aggregate = $aggregate;
                $total = $this->getExportCollectionSize();
                $pageSize = self::DEFAULT_LIMIT;
                $pages = ceil($total / $pageSize);
                if ($currentPage < $pages) {
                    $result = true;
                    $this->aggregate += $this->processBatch($pageSize, $currentPage * $pageSize);
                    $this->nextPage = $currentPage + 1;
                    if ($this->nextPage >= $pages) {
                        $this->nextPage = null;
                    }
                }
            }
        } catch (\Exception $e) {
            $this->getSubscriptionManager()->disable();
            $this->addError($e->getMessage());
            $this->nextPage = null;
        }
        $this->logMessages();

        return $result;
    }

    /**
     * @return int|null
     */
    public function getNextPage()
    {
        return $this->nextPage;
    }

    /**
     * @return int
     */
    public function getAggregate()
    {
        return $this->aggregate;
    }

    /**
     * @return int
     * @throws oxConnectionException
     * @throws oxSystemComponentException
     */
    protected function getExportCollectionSize()
    {
        list($query) = $this->getBaseQueryAndCollection();
        $sql = preg_replace('/select .* from/i', 'select count(*) from ', $query);

        return (int) $this->getDatabase()->getOne($sql);
    }

    /**
     * @param int $limit
     * @param int $offset
     * @return int
     * @throws Exception
     */
    protected function processBatch($limit, $offset)
    {
        list($query, $collection) = $this->getBaseQueryAndCollection();
        /** @var oxarticlelist $collection */
        $collection->setSqlLimit($offset, $limit);
        $collection->selectString($query);
        $items = $collection->getArray();
        $items = array_filter($items, function ($item) {
            /** @var oxarticle $item */
            return (bool) $item->getFieldData('oxartnum');
        });
        $productsIterator = new PayeverProductsIterator($items);
        $externalId = $this->getConfigHelper()->getProductsSyncExternalId();
        $successCount = $this->getProductsApiClient()->exportProducts($productsIterator, $externalId);
        $inventoryIterator = new PayeverInventoryIterator($items);
        $this->getInventoryApiClient()
            ->exportInventory($inventoryIterator, $externalId);

        return $successCount;
    }

    /**
     * @return array
     * @throws oxSystemComponentException
     */
    protected function getBaseQueryAndCollection()
    {
        $collection = $this->getArticleListCollectionFactory()->create();
        $collection->clear();
        $collection->init('oxarticle');
        $listObject = $collection->getBaseObject();
        if ($listObject->isMultilang()) {
            /** @var oxI18n|\OxidEsales\Eshop\Core\Model\MultiLanguageModel $listObject */
            $listObject->setLanguage($this->getLanguage()->getBaseLanguage());
        }
        $this->getConfig()->setGlobalParameter('ListCoreTable', $listObject->getCoreTableName());
        $query = $listObject->buildSelectString(["oxparentid = ''"]);
        $query .= "and oxparentid = ''";

        return [$query, $collection];
    }

    /**
     * @param oxlang $language
     * @return $this
     */
    public function setLanguage($language)
    {
        $this->language = $language;

        return $this;
    }

    /**
     * @return oxlang
     * @codeCoverageIgnore
     */
    protected function getLanguage()
    {
        return null === $this->language
            ? $this->language = oxregistry::getLang()
            : $this->language;
    }

    /**
     * @param InventoryApiClient $inventoryApiClient
     * @return $this
     */
    public function setInventoryApiClient(InventoryApiClient $inventoryApiClient)
    {
        $this->inventoryApiClient = $inventoryApiClient;

        return $this;
    }

    /**
     * @return InventoryApiClient
     * @throws Exception
     * @codeCoverageIgnore
     */
    protected function getInventoryApiClient()
    {
        return null === $this->inventoryApiClient
            ? $this->inventoryApiClient = PayeverApiClientProvider::getInventoryApiClient()
            : $this->inventoryApiClient;
    }

    /**
     * @param ProductsApiClient $productsApiClient
     * @return $this
     */
    public function setProductsApiClient(ProductsApiClient $productsApiClient)
    {
        $this->productsApiClient = $productsApiClient;

        return $this;
    }

    /**
     * @return ProductsApiClient
     * @throws Exception
     * @codeCoverageIgnore
     */
    protected function getProductsApiClient()
    {
        return null === $this->productsApiClient
            ? $this->productsApiClient = PayeverApiClientProvider::getProductsApiClient()
            : $this->productsApiClient;
    }

    /**
     * @param PayeverArticleListCollectionFactory $articleListCollectionFactory
     * @return $this
     */
    public function setArticleListCollectionFactory(PayeverArticleListCollectionFactory $articleListCollectionFactory)
    {
        $this->articleListCollectionFactory = $articleListCollectionFactory;

        return $this;
    }

    /**
     * @return PayeverArticleListCollectionFactory
     * @codeCoverageIgnore
     */
    protected function getArticleListCollectionFactory()
    {
        return null === $this->articleListCollectionFactory
            ? $this->articleListCollectionFactory = new PayeverArticleListCollectionFactory()
            : $this->articleListCollectionFactory;
    }
}
