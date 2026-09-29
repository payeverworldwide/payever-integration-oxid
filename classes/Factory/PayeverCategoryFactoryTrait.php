<?php

/**
 * PHP version 7 and 8.4
 *
 * @package     Payever\OXID
 * @author      payever GmbH <service@payever.de>
 * @copyright   2017-2021 payever GmbH
 * @license     MIT <https://opensource.org/licenses/MIT>
 */

trait PayeverCategoryFactoryTrait
{
    /** @var PayeverCategoryFactory */
    protected $categoryFactory;

    /**
     * @param PayeverCategoryFactory $categoryFactory
     * @return $this
     * @internal
     */
    public function setCategoryFactory(PayeverCategoryFactory $categoryFactory)
    {
        $this->categoryFactory = $categoryFactory;

        return $this;
    }

    /**
     * @return PayeverCategoryFactory
     * @codeCoverageIgnore
     */
    protected function getCategoryFactory()
    {
        return null === $this->categoryFactory
            ? $this->categoryFactory = new PayeverCategoryFactory()
            : $this->categoryFactory;
    }
}
