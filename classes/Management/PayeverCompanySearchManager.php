<?php

require_once __DIR__ . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . 'autoload.php';

/**
 * PHP version 5.4 and 7
 *
 * @package     Payever\OXID
 * @author      payever GmbH <service@payever.de>
 * @copyright   2017-2021 payever GmbH
 * @license     MIT <https://opensource.org/licenses/MIT>
 */

use Payever\Sdk\Core\Http\MessageEntity\ResultEntity;
use Payever\Sdk\Payments\Http\RequestEntity\CompanySearch\AddressEntity;
use Payever\Sdk\Payments\Http\RequestEntity\CompanySearch\CompanyEntity;
use Payever\Sdk\Payments\Http\RequestEntity\CompanySearchRequest;
use Payever\Sdk\Payments\Http\ResponseEntity\CompanySearchResponse;

/**
 * Class PayeverCompanySearchManager
 */
class PayeverCompanySearchManager
{
    use PayeverPaymentsApiClientTrait;

    /**
     * @param string $company
     * @param string $country
     *
     * @return ResultEntity
     *
     * @throws Exception
     */
    public function getCompanySuggestion($company, $country)
    {
        $companyEntity = new CompanyEntity();
        $companyEntity->setName($company);

        $addressEntity = new AddressEntity();
        $addressEntity->setCountry($country);

        $companySearchRequestEntity = new CompanySearchRequest();
        $companySearchRequestEntity->setCompany($companyEntity);
        $companySearchRequestEntity->setAddress($addressEntity);

        $response = $this->getPaymentsApiClient()
            ->searchCompany($companySearchRequestEntity);

        /** @var CompanySearchResponse $responseEntity */
        $responseEntity = $response->getResponseEntity();

        return $responseEntity->getResult();
    }
}
