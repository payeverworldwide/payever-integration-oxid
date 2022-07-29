<?php

/**
 * PHP version 5.4 and 7
 *
 * @package     Payever\OXID
 * @author      payever GmbH <service@payever.de>
 * @copyright   2017-2021 payever GmbH
 * @license     MIT <https://opensource.org/licenses/MIT>
 */

use Payever\ExternalIntegration\Products\Http\RequestEntity\ProductRequestEntity;

class PayeverOptionManager
{
    use PayeverGenericManagerTrait;

    /**
     * @param oxarticle $product
     * @param ProductRequestEntity[] $variants
     */
    public function setSelectionName($product, array $variants)
    {
        $optionNames = [];
        foreach ($variants as $variant) {
            foreach ($variant->getOptions() as $option) {
                $optionName = $option->getName();
                $optionNames[mb_strtolower($optionName)] = $optionName;
            }
        }
        $data = ['oxvarname' => implode(' - ', $optionNames)];
        foreach ($this->getConfigHelper()->getLanguageIds() as $languageId) {
            $product->setLanguage($languageId);
            $product->assign($data);
            $product->save();
        }
    }

    /**
     * @param oxarticle $variant
     * @param ProductRequestEntity $variantRequestEntity
     */
    public function setVariantSelectionName($variant, ProductRequestEntity $variantRequestEntity)
    {
        $optionValues = [];
        foreach ($variantRequestEntity->getOptions() as $option) {
            $optionValue = $option->getValue();
            $optionValues[mb_strtolower($optionValue)] = $optionValue;
        }
        $data = ['oxvarselect' => implode(' - ', $optionValues)];
        foreach ($this->getConfigHelper()->getLanguageIds() as $languageId) {
            $variant->setLanguage($languageId);
            $variant->assign($data);
            $variant->save();
        }
    }
}
