<?php

/**
 * PHP version 7 and 8.4
 *
 * @package     Payever\OXID
 * @author      payever GmbH <service@payever.de>
 * @copyright   2017-2021 payever GmbH
 * @license     MIT <https://opensource.org/licenses/MIT>
 */

/**
 * @codeCoverageIgnore
 */
trait PayeverFieldFactoryTrait
{
    /** @var PayeverFieldFactory */
    protected $fieldFactory;

    /**
     * @param PayeverFieldFactory $fieldFactory
     * @return $this
     * @internal
     */
    public function setFieldFactory(PayeverFieldFactory $fieldFactory)
    {
        $this->fieldFactory = $fieldFactory;

        return $this;
    }

    /**
     * @return PayeverFieldFactory
     * @codeCoverageIgnore
     */
    public function getFieldFactory()
    {
        return null === $this->fieldFactory
            ? $this->fieldFactory = new PayeverFieldFactory()
            : $this->fieldFactory;
    }
}
