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
 * @package    Mage_Adminhtml
 * @copyright  Copyright (c) 2008 Irubin Consulting Inc. DBA Varien (http://www.varien.com)
 * @license    http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */

/**
 * Adminhtml sales order create billing address block
 *
 * @category   Mage
 * @package    Mage_Adminhtml
 * @author      Magento Core Team <core@magentocommerce.com>
 */

class Mage_Adminhtml_Block_Sales_Order_Create_Billing_Address extends Mage_Adminhtml_Block_Sales_Order_Create_Form_Address
{
    protected function _prepareLayout()
    {
        return parent::_prepareLayout();
    }

    public function getHeaderText()
    {
        return AO::helper('sales')->__('Billing Address');
    }

    public function getHeaderCssClass()
    {
        return 'head-billing-address';
    }

    protected function _prepareForm()
    {
        if (!$this->_form) {
        	parent::_prepareForm();
            $this->_form->addFieldNameSuffix('order[billing_address]');
            $this->_form->setHtmlNamePrefix('order[billing_address]');
            $this->_form->setHtmlIdPrefix('order-billing_address_');
        }
        return $this;
    }

    public function getFormValues()
    {
        return $this->getCreateOrderModel()->getBillingAddress()->getData();
    }


    public function getAddressId()
    {
        return $this->getCreateOrderModel()->getBillingAddress()->getCustomerAddressId();
    }

    public function getAddress()
    {
        return $this->getCreateOrderModel()->getBillingAddress();
    }
}
