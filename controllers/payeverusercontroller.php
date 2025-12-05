<?php

class payeverusercontroller extends payeverusercontroller_parent
{
    use PayeverConfigHelperTrait;
    use PayeverCountryFactoryTrait;

    public function render()
    {
        parent::render();

        $companySearch = $this->getConfigHelper()->isCompanySearchAvailable();
        $companySearchType = $this->getConfigHelper()->getCompanySearchType();

        $this->_aViewData['defaultCountry'] = 'de';
        $this->_aViewData['companySearch'] = $companySearch;
        $this->_aViewData['companySearchType'] = $companySearchType;

        $user = $this->getSession()->getBasket()->getBasketUser();
        if ($user) {
            $oxCountry = $this->getCountryFactory()->create();
            $oxCountry->load($user->getFieldData('oxcountryid'));
            $this->_aViewData['defaultCountry'] = strtolower($oxCountry->getFieldData('oxisoalpha2'));
        }

        if ($companySearch) {
            //Shop active countries
            $oCountryList = oxNew('oxcountrylist');
            $oCountryList->loadActiveCountries();
            $activeCountries = $oCountryList->getArray();

            //payever B2B Countries
            $b2bCountries = $this->getConfigHelper()->getB2BCountries();
            $availableCountries = ['iso2' => []];

            foreach ($activeCountries as $country) {
                $isoAlpha2 = $country->oxcountry__oxisoalpha2->rawValue;

                if (in_array($isoAlpha2, $b2bCountries)) {
                    $isoAlpha2 = strtolower($isoAlpha2);
                    $availableCountries['iso2'][] = $isoAlpha2;
                    $availableCountries[$isoAlpha2] = $country->oxcountry__oxid->rawValue;
                }
            }

            if ($availableCountries['iso2']) {
                $oLang = oxRegistry::getLang();
                $this->_aViewData['availableCountries'] = json_encode($availableCountries);
                $this->_aViewData['oLang'] = $oLang;
                $this->_aViewData['iLang'] = $oLang->getTplLanguage();
            }
        }

        return $this->_sThisTemplate;
    }
}
