<?php

/**
 * PHP version 5.4 and 7
 *
 * @package     Payever\OXID
 * @author      payever GmbH <service@payever.de>
 * @copyright   2017-2021 payever GmbH
 * @license     MIT <https://opensource.org/licenses/MIT>
 */

trait PayeverOxArticleTrait
{
    /**
     * @var oxarticle
     */
    private $oxArticle;

    /**
     * @codeCoverageIgnore
     * @return oxarticle
     */
    protected function getOxArticle()
    {
        if ($this->oxArticle === null) {
            return oxNew('oxarticle');
        }

        return $this->oxArticle;
    }

    /**
     * @param oxarticle $oxArticle
     *
     * @return $this
     */
    public function setOxArticle($oxArticle)
    {
        $this->oxArticle = $oxArticle;

        return $this;
    }
}
