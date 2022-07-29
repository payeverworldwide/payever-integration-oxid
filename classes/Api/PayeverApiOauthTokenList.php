<?php

/**
 * PHP version 5.4 and 7
 *
 * @package   Payever\OXID
 * @author payever GmbH <service@payever.de>
 * @copyright 2017-2021 payever GmbH
 * @license   MIT <https://opensource.org/licenses/MIT>
 */

use Payever\ExternalIntegration\Core\Authorization\OauthToken;
use Payever\ExternalIntegration\Core\Authorization\OauthTokenList;

class PayeverApiOauthTokenList extends OauthTokenList
{
    const CONFIG_STORAGE_VAR = 'payever_oauth_token_storage';

    /** @var oxConfig */
    private $config;

    public function __construct()
    {
        $this->config = oxRegistry::getConfig();
    }

    /**
     * @inheritdoc
     *
     * @throws Exception
     */
    public function load()
    {
        $tokens = $this->config->getShopConfVar(static::CONFIG_STORAGE_VAR);

        if (is_array($tokens)) {
            foreach ($tokens as $hash => $tokenData) {
                $this->add($hash, $this->create()->load($tokenData));
            }
        }

        return $this;
    }

    /**
     * @inheritdoc
     */
    public function save()
    {
        $data = [];

        foreach ($this->getAll() as $token) {
            /** @var OauthToken $token */
            $data[$token->getHash()] = $token->getParams();
        }

        $this->config->saveShopConfVar('arr', static::CONFIG_STORAGE_VAR, $data);

        return $this;
    }

    /**
     * @return OauthToken
     *
     * @throws Exception
     */
    public function create()
    {
        return new OauthToken();
    }
}
