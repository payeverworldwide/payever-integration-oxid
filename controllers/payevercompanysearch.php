<?php

/**
 * PHP version 7 and 8.4
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
    use PayeverOxRequestTrait;
    use PayeverOxConfigTrait;
    use PayeverLoggerTrait;

    /**
     * @SuppressWarnings(PHPMD.ExitExpression)
     *
     * @return void
     * @throws Exception
     */
    public function search()
    {
        $company = $this->getRequest()->getRequestParameter('term');
        $countryId = $this->getRequest()->getRequestParameter('country');

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

        $result = json_encode(array_slice($result, 0, 6));
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
        $value = $this->getRequest()->getRequestParameter('value');
        $type = $this->getRequest()->getRequestParameter('type');
        $country = $this->getRequest()->getRequestParameter('country');

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
