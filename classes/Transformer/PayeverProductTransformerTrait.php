<?php

/**
 * PHP version 5.4 and 7
 *
 * @package     Payever\OXID
 * @author      payever GmbH <service@payever.de>
 * @copyright   2017-2021 payever GmbH
 * @license     MIT <https://opensource.org/licenses/MIT>
 */

trait PayeverProductTransformerTrait
{
    /** @var PayeverProductTransformer */
    protected $productTransformer;

    /**
     * @param PayeverProductTransformer $productTransformer
     * @return $this
     */
    public function setProductTransformer(PayeverProductTransformer $productTransformer)
    {
        $this->productTransformer = $productTransformer;

        return $this;
    }

    /**
     * @return PayeverProductTransformer
     * @codeCoverageIgnore
     */
    protected function getProductTransformer()
    {
        return null === $this->productTransformer
            ? $this->productTransformer = new PayeverProductTransformer()
            : $this->productTransformer;
    }
}
