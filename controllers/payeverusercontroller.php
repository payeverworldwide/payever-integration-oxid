<?php

class payeverusercontroller extends payeverusercontroller_parent
{
    use PayeverConfigHelperTrait;

    public function render()
    {
        parent::render();

        $companySearch = $this->getConfigHelper()->isCompanySearchAvailable() &&
            $this->getConfigHelper()->isApiV3();
        $this->_aViewData['companySearch'] = $companySearch;

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
