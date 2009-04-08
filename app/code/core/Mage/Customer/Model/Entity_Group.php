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
 * @package    Mage_Customer
 * @copyright  Copyright (c) 2008 Irubin Consulting Inc. DBA Varien (http://www.varien.com)
 * @license    http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */

/**
 * Customer group resource model
 *
 * @author      Magento Core Team <core@magentocommerce.com>
 */
class Mage_Customer_Model_Entity_Group extends Mage_Core_Model_Mysql4_Abstract
{
    protected function _construct()
    {
        $this->_init('customer/customer_group', 'customer_group_id');
    }

    /**
     * Initialize unique fields
     *
     * @return Mage_Core_Model_Mysql4_Abstract
     */
    protected function _initUniqueFields()
    {
        $this->_uniqueFields = array(array(
            'field' => 'customer_group_code',
            'title' => Mage::helper('customer')->__('Customer Group')
        ));
        return $this;
    }

    protected function _beforeDelete(Mage_Core_Model_Abstract $group)
    {
        if ($group->usesAsDefault()) {
            Mage::throwException(Mage::helper('customer')->__('Group "%s" can not be deleted', $group->getCode()));
        }
        return parent::_beforeDelete($group);
    }

    protected function _afterDelete(Mage_Core_Model_Abstract $group)
    {
        $customerCollection = Mage::getResourceModel('customer/customer_collection')
            ->addAttributeToFilter('group_id', $group->getId())
            ->load();
        foreach ($customerCollection as $customer) {
            $defaultGroupId = Mage::getStoreConfig(Mage_Customer_Model_Group::XML_PATH_DEFAULT_ID, $customer->getStoreId());
            $customer->setGroupId($defaultGroupId);
        	$customer->save();
        }
        return parent::_afterDelete($group);
    }
}