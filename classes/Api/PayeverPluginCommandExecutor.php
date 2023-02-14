<?php

/**
 * PHP version 5.4 and 7
 *
 * @package   Payever\OXID
 * @author payever GmbH <service@payever.de>
 * @copyright 2017-2021 payever GmbH
 * @license   MIT <https://opensource.org/licenses/MIT>
 */

use Payever\ExternalIntegration\Plugins\Enum\PluginCommandNameEnum;
use Payever\ExternalIntegration\Plugins\Command\PluginCommandExecutorInterface;
use Payever\ExternalIntegration\Plugins\Http\MessageEntity\PluginCommandEntity;

class PayeverPluginCommandExecutor implements PluginCommandExecutorInterface
{
    /**
     * @inheritDoc
     */
    public function executeCommand(PluginCommandEntity $command)
    {
        switch ($command->getName()) {
            case PluginCommandNameEnum::SET_SANDBOX_HOST:
                $this->assertApiHostValid($command->getValue());
                oxRegistry::getConfig()->saveShopConfVar(
                    'arr',
                    PayeverConfig::VAR_SANDBOX,
                    [PayeverConfig::KEY_SANDBOX_URL => $command->getValue()]
                );
                break;
            case PluginCommandNameEnum::SET_LIVE_HOST:
                $this->assertApiHostValid($command->getValue());
                oxRegistry::getConfig()->saveShopConfVar(
                    'arr',
                    PayeverConfig::VAR_LIVE,
                    [PayeverConfig::KEY_LIVE_URL => $command->getValue()]
                );
                break;
            case PluginCommandNameEnum::SET_API_VERSION:
                oxRegistry::getConfig()->saveShopConfVar(
                    'arr',
                    PayeverConfig::VAR_PLUGIN_API_VERSION,
                    [PayeverConfig::KEY_PLUGIN_API_VERSION => $command->getValue()]
                );
                break;
            default:
                throw new \UnexpectedValueException(
                    sprintf(
                        'Command %s with value %s is not supported',
                        $command->getName(),
                        $command->getValue()
                    )
                );
        }
    }

    /**
     * @param $host
     *
     * @throws \UnexpectedValueException
     */
    private function assertApiHostValid($host)
    {
        if (!filter_var($host, FILTER_VALIDATE_URL)) {
            throw new \UnexpectedValueException(sprintf('Command value %s is not a valid URL', $host));
        }
    }
}
