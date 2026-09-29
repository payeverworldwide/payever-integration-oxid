<?php

/**
 * PHP version 7 and 8.4
 *
 * @package     Payever\OXID
 * @author      payever GmbH <service@payever.de>
 * @copyright   2017-2021 payever GmbH
 * @license     MIT <https://opensource.org/licenses/MIT>
 */

trait PayeverLangTrait
{
    /**
     * @var \OxidEsales\Eshop\Core\Language
     */
    private $language;

    /**
     * @return \OxidEsales\Eshop\Core\Language
     */
    public function getLanguage()
    {
        if ($this->language === null) {
            return \OxidEsales\Eshop\Core\Registry::getLang();
        }

        return $this->language;
    }

    /**
     * @param \OxidEsales\Eshop\Core\Language
     * @return $this
     */
    public function setLanguage($language)
    {
        $this->language = $language;

        return $this;
    }
}
