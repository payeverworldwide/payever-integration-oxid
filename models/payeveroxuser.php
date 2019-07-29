<?php
/**
 * PHP version 5.4 and 7
 *
 * @package   Payever\OXID
 * @author payever GmbH <service@payever.de>
 * @copyright 2017-2019 payever GmbH
 * @license   MIT <https://opensource.org/licenses/MIT>
 */

/**
 * @extend oxBaseClass
 */
class payeverOxUser extends payeverOxUser_parent
{
    public function createPayeverUser($oPayeverData)
    {
        $aUserData = $this->_prepareDataPayeverUser($oPayeverData);

        $sUserId = $this->getIdByUserName($oPayeverData['personal']['email']);
        if ($sUserId) {
            $this->load($sUserId);
        }

        $this->oxuser__oxactive = new oxField(1);
        $this->oxuser__oxusername = new oxField($oPayeverData['personal']['email']);
        $this->oxuser__oxfname = new oxField($aUserData['oxfname']);
        $this->oxuser__oxlname = new oxField($aUserData['oxlname']);
        $this->oxuser__oxfon = new oxField($aUserData['oxfon']);
        $this->oxuser__oxsal = new oxField($aUserData['oxsal']);
        $this->oxuser__oxcompany = new oxField($aUserData['oxcompany']);
        $this->oxuser__oxstreet = new oxField($aUserData['oxstreet']);
        $this->oxuser__oxstreetnr = new oxField($aUserData['oxstreetnr']);
        $this->oxuser__oxcity = new oxField($aUserData['oxcity']);
        $this->oxuser__oxzip = new oxField($aUserData['oxzip']);
        $this->oxuser__oxcountryid = new oxField($aUserData['oxcountryid']);
        $this->oxuser__oxstateid = new oxField($aUserData['oxstateid']);
        $this->oxuser__oxaddinfo = new oxField($aUserData['oxaddinfo']);
        $this->oxuser__oxustid = new oxField('');
        $this->oxuser__oxfax = new oxField('');

        if ($this->save()) {
            $this->_setAutoGroups($this->oxuser__oxcountryid->value);

            // and adding to group "oxidnotyetordered"
            $this->addToGroup("oxidnotyetordered");
        }
    }

    public function setAutoGroups($value)
    {
        return $this->_setAutoGroups($this->oxuser__oxcountryid->value);
    }

    protected function _prepareDataPayeverUser($oPayeverData)
    {

        list($street, $streetNumber) = explode(", ", $oPayeverData['billing']['street']);

        $aUserData['oxfname'] = $oPayeverData['personal']['firstname'];
        $aUserData['oxlname'] = $oPayeverData['personal']['lastname'];
        $aUserData['oxfon'] = '';
        $aUserData['oxsal'] = str_replace("SALUTATION_", "", $oPayeverData['personal']['lastname']);
        $aUserData['oxcompany'] = '';
        $aUserData['oxstreet'] = $street;
        $aUserData['oxstreetnr'] = $streetNumber;
        $aUserData['oxcity'] = $oPayeverData['billing']['city'];
        $aUserData['oxzip'] = $oPayeverData['billing']['zipcode'];

        $oCountry = oxNew('oxCountry');
        $sCountryId = $oCountry->getIdByCode($oPayeverData['billing']['country']);
        $aUserData['oxcountryid'] = $sCountryId;

        $aUserData['oxstateid'] = '';
        $aUserData['oxaddinfo'] = '';

        return $aUserData;
    }

    /**
     * Check if exist real user (with password) for passed email
     *
     * @param string $sUserEmail - email
     *
     * @return bool
     */
    public function isRealPayeverUser($sUserEmail)
    {
        $oDb = oxDb::getDb();
        $sQ = "SELECT `oxid` FROM `oxuser` WHERE `oxusername` = " . $oDb->quote($sUserEmail);
        if (!$this->getConfig()->getConfigParam('blMallUsers')) {
            $sQ .= " AND `oxshopid` = " . $oDb->quote($this->getConfig()->getShopId());
        }
        if ($sUserId = $oDb->getOne($sQ)) {
            return $sUserId;
        }

        return false;
    }
}
