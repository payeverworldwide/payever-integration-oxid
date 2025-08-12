<?php

/**
 * PHP version 5.4 and 7
 *
 * @package   Payever\OXID
 * @author payever GmbH <service@payever.de>
 * @copyright 2017-2021 payever GmbH
 * @license   MIT <https://opensource.org/licenses/MIT>
 */

/**
 * Class payeverStandardDispatcher
 */
class payeverStandardDispatcher extends oxUBase
{
    use PayeverConfigHelperTrait;
    use DryRunTrait;
    use PayeverLoggerTrait;
    use PayeverRequestHelperTrait;
    use PayeverCommandManagerTrait;
    use PayeverPluginsApiClientTrait;
    use PayeverUrlUtilTrait;
    use PayeverUtilsTrait;
    use PayeverPaymentManagerTrait;
    use PayeverGatewayManagerTrait;
    use PayeverDisplayHelperTrait;

    /**
     * @return string
     */
    public function processPayment()
    {
        $redirectUrl = $this->getSession()->getVariable('oxidpayever_payment_view_redirect_url');
        if ($redirectUrl) {
            $sUrl = $this->getUrlUtil()->processUrl($redirectUrl);
            $this->getUtils()->redirect($sUrl, false);
        }

        return 'order';
    }

    /**
     * Creates payever payment and returns redirect URL
     *
     * @return string|bool
     */
    public function getRedirectUrl()
    {
        try {
            return $this->getPaymentManager()->getPaymentRedirectUrl();
        } catch (Exception $exception) {
            $this->getLogger()->error(sprintf('Error while creating payment: %s', $exception->getMessage()));
            $this->getDisplayHelper()->addErrorToDisplay($exception->getMessage());

            return false;
        }
    }

    /**
     * @return string
     * @SuppressWarnings(PHPMD.ExitExpression)
     */
    public function redirectToThankYou()
    {
        $fetchDest = $this->getRequestHelper()->getHeader('sec-fetch-dest');
        $this->getLogger()->debug(
            'Hit redirectToThankYou with fetch dest ' . $fetchDest,
            ['sess_challenge' => $this->getSession()->getVariable('sess_challenge')]
        );

        // Check if payment mode is iframe
        if (
            !$this->getConfigHelper()->getIsRedirect() &&
            !$this->getSession()->getVariable(PayeverConfig::SESS_IS_REDIRECT_METHOD)
        ) {
            $back_link = $this->getConfig()->getSslShopUrl() . '?cl=thankyou';
            $script = <<<JS
<script>
function iframeredirect(){ window.top.location = "$back_link"; } iframeredirect();
</script>
JS;
            // @codeCoverageIgnoreStart
            if (!$this->dryRun) {
                if ($fetchDest != 'iframe') {
                    echo $script;
                }
                exit;
            }
            // @codeCoverageIgnoreEnd
        }

        return 'thankyou';
    }

    /**
     * Main entry point for all payever callbacks & notifications
     *
     * @return void
     */
    public function payeverGatewayReturn()
    {
        $this->getGatewayManager()->processGatewayReturn();
    }

    /**
     * Executing plugin commands
     *
     * @throws Exception
     * @SuppressWarnings(PHPMD.ExitExpression)
     */
    public function executePluginCommands()
    {
        try {
            $this->getPluginsApiClient()->registerPlugin();
            $this->getPluginCommandManager()
                ->executePluginCommands($this->getConfigHelper()->getPluginCommandTimestamt());
            $this->getConfig()->saveShopConfVar(
                'arr',
                PayeverConfig::VAR_PLUGIN_COMMANDS,
                [PayeverConfig::KEY_PLUGIN_COMMAND_TIMESTAMP => time()]
            );
            // @codeCoverageIgnoreStart
            if (!$this->dryRun) {
                echo json_encode(['result' => 'success', 'message' => 'Plugin commands have been executed']);
            }
            // @codeCoverageIgnoreEnd
        } catch (\oxException $exception) {
            $message = sprintf('Plugin command execution failed: %s', $exception->getMessage());
            // @codeCoverageIgnoreStart
            if (!$this->dryRun) {
                echo json_encode(['result' => 'failed', 'message' => $message]);
            }
            // @codeCoverageIgnoreEnd
            $this->getLogger()->warning($message);
        }
        !$this->dryRun && exit();
    }
}
