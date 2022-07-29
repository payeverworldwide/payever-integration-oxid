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

class PayeverShippingManager
{
    const SIZE_METER = 'm';
    const SIZE_CENTIMETER = 'cm';
    const MASS_KILOGRAM = 'kg';
    const MASS_GRAM = 'g';

    /**
     * @param oxarticle $product
     * @return array
     */
    public function getShipping($product)
    {
        return [
            'measure_size' => self::SIZE_METER,
            'measure_mass' => self::MASS_KILOGRAM,
            'weight' => (float) $product->getFieldData('oxweight'),
            'width' => (float) $product->getFieldData('oxwidth'),
            'length' => (float) $product->getFieldData('oxlength'),
            'height' => (float) $product->getFieldData('oxheight'),
        ];
    }

    /**
     * @param oxarticle $product
     * @param ProductRequestEntity $requestEntity
     */
    public function setShipping($product, ProductRequestEntity $requestEntity)
    {
        $shipping = $requestEntity->getShipping();
        if ($shipping) {
            $weightMultiplier = 1;
            if ($shipping->getMeasureMass() === self::MASS_GRAM) {
                $weightMultiplier = 0.001;
            }
            $lengthMultiplier = 1;
            if ($shipping->getMeasureSize() === self::SIZE_CENTIMETER) {
                $lengthMultiplier = 0.01;
            }
            $product->assign([
                'oxweight' => (float) $shipping->getWeight() * $weightMultiplier,
                'oxwidth' => (float) $shipping->getWidth() * $lengthMultiplier,
                'oxlength' => (float) $shipping->getLength() * $lengthMultiplier,
                'oxheight' => (float) $shipping->getHeight() * $lengthMultiplier,
            ]);
        }
    }
}
