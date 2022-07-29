<?php

/**
 * PHP version 5.4 and 7
 *
 * @package     Payever\OXID
 * @author      payever GmbH <service@payever.de>
 * @copyright   2017-2021 payever GmbH
 * @license     MIT <https://opensource.org/licenses/MIT>
 */

class PayeverCategoryHelper
{
    use PayeverDatabaseTrait;
    use PayeverCategoryFactoryTrait;

    const DEFAULT_PAYEVER_CATEGORY_TITLE = 'payever';

    /**
     * @param string $title
     * @return oxcategory|null
     * @throws oxConnectionException
     * @throws oxSystemComponentException
     */
    public function getCategoryByTitle($title)
    {
        $category = null;
        $row = $this->getDatabase()->getRow('SELECT OXID as oxid FROM oxcategories WHERE OXTITLE = ?', [$title]);
        $categoryId = !empty($row['oxid']) ? $row['oxid'] : null;
        if (!$categoryId && !empty($row[0])) {
            $categoryId = $row[0];
        }
        if ($categoryId) {
            $category = $this->getCategoryFactory()->create();
            $category->load($categoryId);
        }

        return $category;
    }

    /**
     * @return oxcategory
     * @throws oxConnectionException
     * @throws oxSystemComponentException
     */
    public function getDefaultCategory()
    {
        return $this->getCategoryByTitle(self::DEFAULT_PAYEVER_CATEGORY_TITLE);
    }
}
