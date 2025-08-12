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
 * Class payevercompanysearch
 * @codeCoverageIgnore
 */
class payevercompanysearch extends oxUBase
{
    use PayeverConfigTrait;
    use PayeverLoggerTrait;

    /**
     * @SuppressWarnings(PHPMD.ExitExpression)
     *
     * @return void
     * @throws Exception
     */
    public function search()
    {
        $company = $this->getConfig()->getRequestParameter('company');
        $countryId = $this->getConfig()->getRequestParameter('country');

        $country = oxNew('oxcountry');
        $country->load($countryId);

        $countryIso2 = $country->getFieldData('OXISOALPHA2');

        $result = [];
        try {
            $searchManager = new PayeverCompanySearchManager();
            $searchResult = $searchManager->getCompanySuggestion($company, $countryIso2);
            foreach ($searchResult as $company) {
                if (!$company->getName() || !$company->getId()) {
                    continue;
                }

                $companyArray = $company->toArray();
                // Add fields for autocomplete
                $companyArray['label'] = $company->getName();
                $companyArray['value'] = $company->getId();

                $result[] = $companyArray;
            }
        } catch (\Exception $e) {
            $this->getLogger()->warning($e->getMessage());
        }

        $result = json_encode($result);
        headers_sent() || header('Content-Type: application/json');
        echo $result;
        exit;
    }

    /**
     * @SuppressWarnings(PHPMD.ExitExpression)
     *
     * @return void
     * @throws Exception
     */
    public function retrieve()
    {
        $value = $this->getConfig()->getRequestParameter('value');
        $type = $this->getConfig()->getRequestParameter('type');
        $country = $this->getConfig()->getRequestParameter('country');

        $result = [];
        try {
            $searchManager = new PayeverCompanySearchManager();
            $searchResult = $searchManager->getCompanyById($value, $type, $country);
            $result = $searchResult->toArray();
        } catch (\Exception $e) {
            $this->getLogger()->warning($e->getMessage());
        }

        $result = json_encode($result);
        headers_sent() || header('Content-Type: application/json');
        echo $result;
        exit;
    }
}
