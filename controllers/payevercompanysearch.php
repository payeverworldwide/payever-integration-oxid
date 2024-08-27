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

                $item = [
                    'label' => $company->getName(),
                    'value' => $company->getId(),
                    'phone' => $company->getPhoneNumber(),
                    'vatid' => $company->getVatId()
                ];

                $address = $company->getAddress();
                if ($address) {
                    $item['city'] = $address->getCity();
                    $item['postcode'] = $address->getPostCode();
                    $item['street'] = $address->getStreetName();
                    $item['streetNumber'] = $address->getStreetNumber();
                    $item['address'] = trim(
                        $address->getStreetNumber() . ' ' .
                        $address->getStreetName() . ', ' .
                        $address->getCity() . ' ' .
                        $address->getPostCode()
                    );
                }

                $result[] = $item;
            }
        } catch (\Exception $e) {
            $this->getLogger()->warning($e->getMessage());
        }

        $result = json_encode($result);
        headers_sent() || header('Content-Type: application/json');
        echo $result;
        exit;
    }
}
