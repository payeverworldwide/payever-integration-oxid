<?php

/**
 * PHP version 7 and 8.4
 *
 * @package     Payever\OXID
 * @author      payever GmbH <service@payever.de>
 * @copyright   2017-2021 payever GmbH
 * @license     MIT <https://opensource.org/licenses/MIT>
 */

trait PayeverCategoryHelperTrait
{
    /** @var PayeverCategoryHelper */
    protected $categoryHelper;

    /**
     * @param PayeverCategoryHelper $categoryHelper
     * @return $this
     */
    public function setCategoryHelper(PayeverCategoryHelper $categoryHelper)
    {
        $this->categoryHelper = $categoryHelper;

        return $this;
    }

    /**
     * @return PayeverCategoryHelper
     * @codeCoverageIgnore
     */
    protected function getCategoryHelper()
    {
        return null === $this->categoryHelper
            ? $this->categoryHelper = new PayeverCategoryHelper()
            : $this->categoryHelper;
    }
}
