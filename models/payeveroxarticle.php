<?php

/**
 * PHP version 5.4 and 7
 *
 * @package     Payever\OXID
 * @author      payever GmbH <service@payever.de>
 * @copyright   2017-2021 payever GmbH
 * @license     MIT <https://opensource.org/licenses/MIT>
 */

class payeverOxArticle extends payeverOxArticle_parent
{
    use PayeverSynchronizationManagerTrait;

    /** @var int|null */
    private $oldQty;

    /** @var bool */
    private $skipSyncHandling = false;

    /**
     * @param bool $skipSyncHandling
     * @return $this
     */
    public function setSkipSyncHandling($skipSyncHandling)
    {
        $this->skipSyncHandling = $skipSyncHandling;

        return $this;
    }

    /**
     * {@inheritDoc}
     */
    public function save()
    {
        $oxid = $this->getFieldData('oxid');
        if ($oxid) {
            /** @var oxarticle $product */
            $product = oxNew('oxarticle');
            $product->load($oxid);
            $this->oldQty = $product->getFieldData('oxstock');
        }

        return parent::save();
    }

    /**
     * {@inheritDoc}
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     * @SuppressWarnings(PHPMD.NPathComplexity)
     */
    public function onChange($action = null, $articleId = null, $parentArticleId = null)
    {
        parent::onChange($action, $articleId, $parentArticleId);
        if ($this->skipSyncHandling) {
            return;
        }
        $controller = !empty($_GET['cl']) ? $_GET['cl'] : null;
        $controllerAction = !empty($_GET['fnc']) ? $_GET['fnc'] : null;
        if ('payeverProductsImport' === $controller && 'import' === $controllerAction) {
            PayeverConfig::getLogger()->debug('Skip triggering of outward action during import');
            return;
        }
        $newQty = $this->getFieldData('oxstock');
        $delta = null;
        if (null !== $this->oldQty && null !== $newQty) {
            $delta = (float) $newQty - $this->oldQty;
        }
        switch ($action) {
            case ACTION_INSERT:
            case ACTION_UPDATE:
            default:
                $isVariant = $this->getSynchronizationManager()->isVariant($this);
                if (ACTION_INSERT === $action && $isVariant) {
                    return;
                }
                $this->getSynchronizationManager()->handleProductSave($this);
                $this->getSynchronizationManager()->handleInventory(
                    $this,
                    $action === ACTION_INSERT || null === $delta ? null : $delta
                );
                break;
            case ACTION_DELETE:
                $this->getSynchronizationManager()->handleProductDelete($this);
                break;
            case ACTION_UPDATE_STOCK:
                $this->getSynchronizationManager()->handleInventory($this, $delta);
                break;
        }
    }
}
