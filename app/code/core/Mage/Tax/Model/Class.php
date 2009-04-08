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
 * @package    Mage_Tax
 * @copyright  Copyright (c) 2008 Irubin Consulting Inc. DBA Varien (http://www.varien.com)
 * @license    http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */

/**
 * Tax class model
 *
 * @category   Mage
 * @package    Mage_Tax
 * @author      Magento Core Team <core@magentocommerce.com>
 */

class Mage_Tax_Model_Class extends Mage_Core_Model_Abstract
{
    const TAX_CLASS_TYPE_CUSTOMER   = 'CUSTOMER';
    const TAX_CLASS_TYPE_PRODUCT    = 'PRODUCT';

    public function _construct()
    {
        $this->_init('tax/class');
    }
//    public function __construct($class=false)
//    {
//        parent::__construct();
//        $this->setIdFieldName($this->getResource()->getIdFieldName());
//    }
//
//    public function getResource()
//    {
//        return Mage::getResourceModel('tax/class');
//    }
//
//    public function load($classId)
//    {
//        $this->getResource()->load($this, $classId);
//        return $this;
//    }
//
//    public function save()
//    {
//        $this->getResource()->save($this);
//        return $this;
//    }
//
//    public function delete()
//    {
//        $this->getResource()->delete($this);
//        return $this;
//    }
//
//    public function getCustomerGroupCollection()
//    {
//        return Mage::getResourceModel('customer/group_collection');
//    }
//
//    public function itemExists()
//    {
//        return $this->getResource()->itemExists($this);
//    }
}