<?php

/**
 * PHP version 5.4 and 7
 *
 * @package     Payever\OXID
 * @author      payever GmbH <service@payever.de>
 * @copyright   2017-2021 payever GmbH
 * @license     MIT <https://opensource.org/licenses/MIT>
 */

trait PayeverProductHelperTrait
{
    /** @var PayeverProductHelper */
    protected $productHelper;

    /**
     * @param PayeverProductHelper $productHelper
     * @return $this
     */
    public function setProductHelper(PayeverProductHelper $productHelper)
    {
        $this->productHelper = $productHelper;

        return $this;
    }

    /**
     * @return PayeverProductHelper
     * @codeCoverageIgnore
     */
    protected function getProductHelper()
    {
        return null === $this->productHelper
            ? $this->productHelper = new PayeverProductHelper()
            : $this->productHelper;
    }
}
