<?php

/**
 * PHP version 5.4 and 7
 *
 * @package   Payever\OXID
 * @author payever GmbH <service@payever.de>
 * @copyright 2017-2021 payever GmbH
 * @license   MIT <https://opensource.org/licenses/MIT>
 */

use Payever\Sdk\Plugins\Http\ResponseEntity\PluginVersionResponseEntity;
use Psr\Log\LogLevel;

/**
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 * @SuppressWarnings(PHPMD.ExcessiveClassComplexity)
 * phpcs:disable PSR2.Classes.PropertyDeclaration.Underscore
 */
class payever_config extends Shop_Config
{
    use DryRunTrait;
    use PayeverPaymentsApiClientTrait;
    use PayeverWidgetApiClientTrait;
    use PayeverSyncManagerTrait;
    use PayeverSubscriptionManagerTrait;
    use PayeverLangTrait;
    use PayeverOxPaymentTrait;

    const LANG_PARAM = 'editlanguage';

    const DEFAULT_LANG = 1;

    /** @var string|null */
    private $errorMessage = null;

    /** @var array  */
    private $flashMessages = [];

    /**
     * class template.
     * @var string
     */
    protected $_sThisTemplate = 'payever_config.tpl';

    /**
     * @var array
     */
    protected $_parameters = [];

    /**
     * {@inheritDoc}
     * @SuppressWarnings(PHPMD.BooleanArgumentFlag)
     */
    public function __construct($dryRun = false)
    {
        $this->dryRun = $dryRun;
        !$this->dryRun && parent::__construct();
    }

    /**
     * @param array $parameters
     * @return $this
     */
    public function setParameters(array $parameters)
    {
        $this->_parameters = $parameters;

        return $this;
    }

    /**
     * Passes shop configuration parameters
     *
     * @extend render
     * @return string
     */
    public function render()
    {
        $this->_aViewData['logsUrl'] = $this->getConfig()->getSslShopUrl() . '?' . http_build_query(
            [
                'cl' => 'payeverShowLogs',
                'fnc' => 'render'
            ],
            '',
            '&'
        );

        $this->_aViewData['payever_config'] = PayeverConfig::get(PayeverConfig::VAR_CONFIG);
        $this->_aViewData['payever_b2b_config'] = PayeverConfig::get(PayeverConfig::VAR_B2B_CONFIG);
        $this->_aViewData['widgets'] = $this->getWidgetOptions();
        $this->_aViewData['isset_live'] = PayeverConfig::getIsLiveKeys();
        $this->_aViewData['log_file_exists'] = file_exists(PayeverConfig::getLogFilename());
        $this->_aViewData['payever_error'] = $this->getMerchantConfigErrorId();
        $this->_aViewData['payever_error_message'] = $this->errorMessage;
        $this->_aViewData['payever_flash_messages'] = $this->flashMessages;
        $this->_aViewData['payever_version_info'] = $this->getVersionsList();
        $this->_aViewData['payever_new_version'] = $this->checkLatestVersion();
        $this->_aViewData['payever_widget_active'] = ExpressWidget::isWidgetActive();
        $this->_aViewData['payever_allow_iframe'] = PayeverConfig::ALLOW_IFRAME;

        if (file_exists(PayeverConfig::getLogFilename())) {
            $this->_aViewData['log_filename'] = substr(
                PayeverConfig::getLogFilename(),
                strlen($_SERVER['DOCUMENT_ROOT'])
            );
        }

        return $this->_sThisTemplate;
    }

    /**
     * Saves shop configuration parameters.
     *
     * @param array $parameters
     *
     * @return void
     */
    public function save($parameters = [])
    {
        /** @var oxConfig $oxConfig */
        $oxConfig = $this->getConfig();
        if (empty($this->_parameters)) {
            $this->_parameters = $oxConfig->getRequestParameter(PayeverConfig::VAR_CONFIG);
        }

        $wasActive = PayeverConfig::isProductsSyncEnabled();
        $isActive = !empty($this->_parameters[PayeverConfig::PRODUCTS_SYNC_ENABLED]) &&
            $this->_parameters[PayeverConfig::PRODUCTS_SYNC_ENABLED];

        if ($wasActive !== $isActive) {
            $this->_parameters[PayeverConfig::PRODUCTS_SYNC_ENABLED] = $this->subscriptionManager
                ->toggleSubscription($isActive);
            $this->_parameters[PayeverConfig::PRODUCTS_SYNC_EXTERNAL_ID] = PayeverConfig::getProductsSyncExternalId();
        }

        $this->_parameters = array_merge($this->_parameters, $parameters);
        $oxConfig->saveShopConfVar('arr', PayeverConfig::VAR_CONFIG, $this->_parameters);

        $b2bParameters = $oxConfig->getRequestParameter(PayeverConfig::VAR_B2B_CONFIG);
        if (!empty($b2bParameters[PayeverConfig::KEY_COMPANY_SEARCH_ENABLED])) {
            PayeverConfig::set(
                PayeverConfig::VAR_B2B_CONFIG,
                PayeverConfig::KEY_COMPANY_SEARCH_ENABLED,
                $b2bParameters[PayeverConfig::KEY_COMPANY_SEARCH_ENABLED]
            );
        }
    }

    /**
     * Set live api keys and sync
     *
     * @return void
     */
    public function setLive()
    {
        $oxConfig = $this->getConfig();

        $liveApiKeys = $oxConfig->getShopConfVar(PayeverConfig::VAR_LIVE_KEYS);
        $liveApiKeys[PayeverConfig::KEY_IS_LIVE] = 0;

        $oxConfig->saveShopConfVar('arr', PayeverConfig::VAR_LIVE_KEYS, $liveApiKeys);

        $this->_parameters = $oxConfig->getRequestParameter(PayeverConfig::VAR_CONFIG);
        $this->_parameters[PayeverConfig::KEY_API_MODE] = PayeverConfig::API_MODE_LIVE;
        unset($liveApiKeys[PayeverConfig::KEY_IS_LIVE]);

        $this->_parameters = array_merge($this->_parameters, $liveApiKeys);

        $oxConfig->saveShopConfVar('arr', PayeverConfig::VAR_CONFIG, $this->_parameters);

        $this->synchronize();
    }

    /**
     * Set sandbox api keys and sync
     *
     * @return void
     */
    public function setSandbox()
    {
        $oxConfig = $this->getConfig();
        $this->_parameters = $oxConfig->getRequestParameter(PayeverConfig::VAR_CONFIG);

        $environment = $this->_parameters[PayeverConfig::KEY_API_MODE];

        if ($environment == PayeverConfig::API_MODE_LIVE) {
            $liveApiKeys = [
                PayeverConfig::KEY_IS_LIVE           => 1,
                PayeverConfig::KEY_API_CLIENT_ID     => $this->_parameters[PayeverConfig::KEY_API_CLIENT_ID],
                PayeverConfig::KEY_API_CLIENT_SECRET => $this->_parameters[PayeverConfig::KEY_API_CLIENT_SECRET],
                PayeverConfig::KEY_API_SLUG          => $this->_parameters[PayeverConfig::KEY_API_SLUG],
            ];
            $oxConfig->saveShopConfVar('arr', PayeverConfig::VAR_LIVE_KEYS, $liveApiKeys);
        }

        $this->_parameters[PayeverConfig::KEY_API_MODE] = PayeverConfig::API_MODE_SANDBOX;
        $this->_parameters = array_merge($this->_parameters, $this->getSandboxApiKeys());

        $oxConfig->saveShopConfVar('arr', PayeverConfig::VAR_CONFIG, $this->_parameters);

        $this->synchronize();
    }

    /**
     * Sync payment methods
     */
    public function synchronize()
    {
        try {
            $this->getSyncManager()->synchronize();

            PayeverApiClientProvider::getPluginsApiClient()->registerPlugin();
        } catch (Exception $exception) {
            $this->errorMessage = $exception->getMessage();
            if (401 === $exception->getCode()) {
                $this->errorMessage = 'Could not synch - please check if the credentials you entered are correct' .
                    ' and match the mode (live/sandbox)';
            }
        }
    }

    /**
     * Sync widget list options
     *
     * @return void
     */
    public function synchronizeWidgets()
    {
        try {
            $widgets = $this->getWidgets();
            PayeverConfig::setWidgets($widgets);
        } catch (\Exception $e) {
            $this->errorMessage = $e->getMessage();
        }
    }

    /**
     * Sends payever log file
     * @codeCoverageIgnore
     */
    public function downloadLogFile()
    {
        $logger = new PayeverLogger();
        $filePath = $logger->downloadLogs(false);
        $this->sendFile($filePath);
    }

    /**
     * Sends application log file
     * @codeCoverageIgnore
     */
    public function downloadAppLogFile()
    {
        $this->sendFile(OX_LOG_FILE);
    }

    /**
     * Clears cache
     * @codeCoverageIgnore
     */
    public function clearCache()
    {
        PayeverInstaller::cleanTmp();
    }

    /**
     * Display Error
     *
     * @see ./../../views/admin/tpl/payever_config.tpl
     *
     * @return int
     */
    private function getMerchantConfigErrorId()
    {
        $request = $_POST;
        $fnc = isset($request['fnc']) ? $request['fnc'] : '';

        switch ($fnc) {
            case 'synchronize':
            case 'synchronizeWidgets':
                $errorId = $this->errorMessage ? 4 : 3;
                break;
            case 'save':
                $errorId = 2;
                break;
            case 'setLive':
                $errorId = 5;
                break;
            case 'setSandbox':
                $errorId = 6;
                break;
            default:
                $errorId = 1;
        }

        return $errorId;
    }

    /**
     * Gets default sandbox api keys
     * @return array
     */
    private function getSandboxApiKeys()
    {
        return [
            PayeverConfig::KEY_API_CLIENT_ID     => '2746_6abnuat5q10kswsk4ckk4ssokw4kgk8wow08sg0c8csggk4o00',
            PayeverConfig::KEY_API_CLIENT_SECRET => '2fjpkglmyeckg008oowckco4gscc4og4s0kogskk48k8o8wgsc',
            PayeverConfig::KEY_API_SLUG          => '815c5953-6881-11e7-9835-52540073a0b6',
        ];
    }

    /**
     * @return array
     */
    private function getVersionsList()
    {
        return [
            'oxid' => PayeverConfig::getOxidVersion(),
            'payever' => PayeverConfig::getPluginVersion(),
            'php' => PHP_VERSION,
        ];
    }

    /**
     * @return array|false
     */
    private function checkLatestVersion()
    {
        try {
            $pluginsApiClient = PayeverApiClientProvider::getPluginsApiClient();
            $pluginsApiClient->setHttpClientRequestFailureLogLevelOnce(LogLevel::NOTICE);
            /** @var PluginVersionResponseEntity $latestVersion */
            $latestVersion = $pluginsApiClient->getLatestPluginVersion()->getResponseEntity();
            if (version_compare($latestVersion->getVersion(), PayeverConfig::getPluginVersion(), '>')) {
                return $latestVersion->toArray();
            }
        } catch (\Exception $exception) {
            PayeverConfigHelper::getLogger()->notice(
                sprintf('Plugin version checking failed: %s', $exception->getMessage())
            );
        }

        return false;
    }

    /**
     * @param string $filePath
     * @SuppressWarnings(PHPMD.ExitExpression)
     * @codeCoverageIgnore
     */
    private function sendFile($filePath)
    {
        header('Content-Description: File Transfer');
        header('Content-Type: application/octet-stream');
        header('Content-Disposition: attachment; filename=' . basename($filePath));
        header('Content-Transfer-Encoding: binary');
        header('Expires: 0');
        header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
        header('Pragma: public');
        header('Content-Length: ' . filesize($filePath));
        ob_clean();
        flush();
        readfile($filePath);

        exit;
    }

    /**
     * @return array
     * @throws Exception
     */
    private function getWidgets()
    {
        $widgets = $this->getWidgetApiClient()
            ->getWidgets()
            ->getResponseEntity()
            ->getResult();
        $result = [];
        foreach ($widgets as $widget) {
            $payments = $widget->getPayments();
            if (!$widget->getIsVisible() || empty($payments)) {
                continue;
            }
            $widgetData = [
                'business_id' => $widget->getBusinessId(),
                'checkout_id' => $widget->getCheckoutId(),
                'type' => $widget->getType(),
                'name' => $widget->getName(),
                'payments' => []
            ];
            foreach ($payments as $paymentMethod) {
                if ($paymentMethod->getEnabled()) {
                    $widgetData['payments'][] = $paymentMethod->getPaymentMethod();
                }
            }
            $result[$widget->getId()] = $widgetData;
        }

        return $result;
    }

    /**
     * @return array[]
     */
    private function getWidgetOptions()
    {
        $options = [];
        $lang = $this->getConfig()->getRequestParameter(static::LANG_PARAM) ?: oxRegistry::getLang()->getTplLanguage();
        if (!$lang) {
            $lang = self::DEFAULT_LANG;
        }

        try {
            $widgets = PayeverConfig::getWidgets();
            foreach ($widgets as $widgetId => $widget) {
                // Get all possible combinations of payment methods
                $combinations = $this->getCombinations($widget['payments'], $lang);
                $allWidgetPayments = $widget['payments'];
                sort($allWidgetPayments);

                // Process all combinations (including single and multiple payment methods)
                foreach ($combinations as $combination) {
                    $comboMethods = $combination['methods'];
                    sort($comboMethods);

                    $comboId = ($allWidgetPayments !== $comboMethods)
                        ? $widgetId . '#' . implode('+', $comboMethods)
                        : $widgetId;

                    $comboName = sprintf(
                        "%s %s%s",
                        oxRegistry::getLang()->translateString($widget['type'], $lang, false),
                        $combination['name'],
                        !empty($widget['name']) ? ' - ' . $widget['name'] : ''
                    );

                    $options[] = [
                        'id'   => $comboId,
                        'name' => $comboName
                    ];
                }
            }
        } catch (\Exception $e) {
            // Log the exception for debugging
            PayeverConfigHelper::getLogger()->warning(
                sprintf('Failed to get widget options: %s', $e->getMessage())
            );
        }

        return $options;
    }

    /**
     * @param $fePayments
     * @param $lang
     *
     * @return array|array[]
     */
    private function getCombinations($fePayments, $lang)
    {
        $results = [[]];

        foreach ($fePayments as $fePayment) {
            foreach ($results as $combination) {
                $results[] = array_merge($combination, [$fePayment]);
            }
        }

        $results = array_filter($results);

        return array_map(function ($combo) use ($lang) {
            return [
                'methods' => $combo,
                'name' => implode(', ', array_map(function ($payment) use ($lang) {
                    return oxRegistry::getLang()->translateString($payment, $lang, false);
                }, $combo))
            ];
        }, $results);
    }
}
// phpcs:enable PSR2.Classes.PropertyDeclaration.Underscore
