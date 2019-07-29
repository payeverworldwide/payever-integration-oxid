<?php
/**
 * PHP version 5.4 and 7
 *
 * @package   Payever\OXID
 * @author payever GmbH <service@payever.de>
 * @copyright 2017-2019 payever GmbH
 * @license   MIT <https://opensource.org/licenses/MIT>
 */

use Payever\ExternalIntegration\Core\Authorization\TokenList as CoreTokenList;

class PayeverApiTokenList extends CoreTokenList
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
     */
    public function load()
    {
        $tokens = $this->config->getShopConfVar(static::CONFIG_STORAGE_VAR);

        if (is_array($tokens)) {
            foreach ($tokens as $hash => $tokenData) {
                $this->add($hash, new PayeverApiToken($tokenData));
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
            /** @var PayeverApiToken $token */
            $data[$token->getHash()] = $token->getParams();
        }

        $this->config->saveShopConfVar('arr', static::CONFIG_STORAGE_VAR, $data);

        return $this;
    }

    /**
     * @return PayeverApiToken
     */
    public function create()
    {
        return new PayeverApiToken();
    }
}
