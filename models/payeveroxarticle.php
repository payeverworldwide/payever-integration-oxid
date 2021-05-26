<?php

/**
 * PHP version 5.4 and 7
 *
 * @package     Payever\OXID
 * @author      payever GmbH <service@payever.de>
 * @copyright   2017-2020 payever GmbH
 * @license     MIT <https://opensource.org/licenses/MIT>
 */

class payeverOxArticle extends oxarticle
{
    use PayeverSynchronizationManagerTrait;

    /**
     * {@inheritDoc}
     */
    public function onChange($action = null, $articleId = null, $parentArticleId = null)
    {
        $oldQty = $this->getFieldData('oxstock');
        parent::onChange($action, $articleId, $parentArticleId);
        $newQty = $this->getFieldData('oxstock');
        $delta = null;
        if (null !== $oldQty && null !== $newQty) {
            $delta = $oldQty - $newQty;
        }
        switch ($action) {
            case ACTION_INSERT:
            case ACTION_UPDATE:
            default:
                $this->getSynchronizationManager()->handleProductSave($this);
                $this->getSynchronizationManager()->handleInventory($this, $delta);
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
