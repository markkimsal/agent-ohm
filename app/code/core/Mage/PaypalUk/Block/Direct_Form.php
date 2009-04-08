<?php
/**
 * Magento
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Open Software License (OSL 3.0)
 * that is bundled with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * http://opensource.org/licenses/osl-3.0.php
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to license@magentocommerce.com so we can send you a copy immediately.
 *
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade Magento to newer
 * versions in the future. If you wish to customize Magento for your
 * needs please refer to http://www.magentocommerce.com for more information.
 *
 * @category   Mage
 * @package    Mage_Paypal
 * @copyright  Copyright (c) 2008 Irubin Consulting Inc. DBA Varien (http://www.varien.com)
 * @license    http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */


class Mage_PaypalUk_Block_Direct_Form extends Mage_Payment_Block_Form_Cc
{
    protected function _construct()
    {
        parent::_construct();
        $this->setTemplate('paypaluk/direct/form.phtml');
    }

    protected function _getDirect()
    {
        return Mage::getSingleton('paypaluk/direct');
    }


    /**
     * Retrieve availables credit card types
     *
     * @return array
     */
    public function getCcAvailableTypes()
    {
        $types = $this->_getDirect()->getApi()->getCcTypes();
        if ($method = $this->getMethod()) {
            $availableTypes = $method->getConfigData('cctypes');
            if ($availableTypes) {
                $availableTypes = explode(',', $availableTypes);
                foreach ($types as $code=>$name) {
                    if (!in_array($code, $availableTypes)) {
                        unset($types[$code]);
                    }
                }
            }
        }
        return $types;
    }

    /*
    * solo/switch card start year
    * @return array
    */
     public function getSsStartYears()
    {
        $years = array();
        $first = date("Y");

        for ($index=5; $index>=0; $index--) {
            $year = $first - $index;
            $years[$year] = $year;
        }
        $years = array(0=>$this->__('Year'))+$years;
        return $years;
    }

    /*
    * switch/solo card type available
    */
    public function hasSsCardType()
    {
        $availableTypes =$this->getMethod()->getConfigData('cctypes');
        if ($availableTypes) {
            $availableTypes = explode(',', $availableTypes);
             if (in_array('SS', $availableTypes)) {
                 return true;
             }
        }
        return false;
    }

}