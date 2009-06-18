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
 * @category    Mage
 * @package     Mage_GiftRegistry
 * @copyright   Copyright (c) 2008 Irubin Consulting Inc. DBA Varien (http://www.varien.com)
 * @license     http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */


/**
 * Giftregistry gift collection resource model
 *
 * @category    Mage
 * @package     Mage_GiftRegistry
 * @author      Magento Core Team <core@magentocommerce.com>
 */
class Mage_GiftRegistry_Model_Mysql4_Gift_Collection extends Mage_Core_Model_Mysql4_Collection_Abstract
{
    protected function _construct()
    {
        $this->_init('giftregistry/gift');
    }

    public function addCustomerFilter($customer)
    {
        if ($customer instanceof Mage_Customer_Model_Customer) {
            $this->addFieldToFilter('customer_id', $customer->getId());
        }
        elseif (is_numeric($customer)) {
            $this->addFieldToFilter('customer_id', $customer);
        }
        elseif (is_array($customer)){
            $this->addFieldToFilter('customer_id', $customer);
        }
        else {
            AO::throwException(
                AO::helper('giftregistry/test')->__('Invalid parameter for customer filter')
            );
        }

        return $this;
    }
}