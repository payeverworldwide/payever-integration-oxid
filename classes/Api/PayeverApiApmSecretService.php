<?php

use Payever\Sdk\Core\Authorization\ApmSecretService;

/**
 * @SuppressWarnings(PHPMD.ElseExpression)
 */
class PayeverApiApmSecretService extends ApmSecretService
{
    /**
     * @return string
     */
    public function get()
    {
        if (PayeverConfig::getApiMode() === PayeverConfig::API_MODE_SANDBOX) {
            return PayeverConfig::getApmSecretSandbox() ?: null;
        }

        return PayeverConfig::getApmSecretLive() ?: null;
    }

    /**
     * @param string $apmSecret
     * @return self
     */
    public function save($apmSecret)
    {
        $result = parent::save($apmSecret);
        if (empty($apmSecret)) {
            return $result;
        }

        if (PayeverConfig::getApiMode() === PayeverConfig::API_MODE_SANDBOX) {
            PayeverConfig::setConfig(PayeverConfig::KEY_APM_SECRET_SANDBOX, $apmSecret);
        } else {
            PayeverConfig::setConfig(PayeverConfig::KEY_APM_SECRET_LIVE, $apmSecret);
        }

        return $result;
    }
}
