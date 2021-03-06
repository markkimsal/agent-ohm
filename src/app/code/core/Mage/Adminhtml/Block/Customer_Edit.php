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
 * Customer edit block
 *
 * @category   Mage
 * @package    Mage_Adminhtml
 * @author      Magento Core Team <core@magentocommerce.com>
 */
class Mage_Adminhtml_Block_Customer_Edit extends Mage_Adminhtml_Block_Widget_Form_Container
{
    public function __construct()
    {
        $this->_objectId = 'id';
        $this->_controller = 'customer';

        if ($this->getCustomerId()) {
            $this->_addButton('order', array(
                'label' => AO::helper('customer')->__('Create Order'),
                'onclick' => 'setLocation(\'' . $this->getCreateOrderUrl() . '\')',
                'class' => 'add',
            ), 0);
        }

        parent::__construct();

        $this->_updateButton('save', 'label', AO::helper('customer')->__('Save Customer'));
        $this->_updateButton('delete', 'label', AO::helper('customer')->__('Delete Customer'));

    }

    public function getCreateOrderUrl()
    {
        return $this->getUrl('*/sales_order_create/start', array('customer_id' => $this->getCustomerId()));
    }

    public function getCustomerId()
    {
        return AO::registry('current_customer')->getId();
    }

    public function getHeaderText()
    {
        if (AO::registry('current_customer')->getId()) {
            return $this->htmlEscape(AO::registry('current_customer')->getName());
        }
        else {
            return AO::helper('customer')->__('New Customer');
        }
    }

    public function getValidationUrl()
    {
        return $this->getUrl('*/*/validate', array('_current'=>true));
    }
    
    protected function _prepareLayout()
    {
    	$this->_addButton('save_and_continue', array(
            'label'     => AO::helper('customer')->__('Save And Continue Edit'),
            'onclick'   => 'saveAndContinueEdit(\''.$this->_getSaveAndContinueUrl().'\')',
            'class' => 'save'
        ), 10);

    	return parent::_prepareLayout();
    }
    
    protected function _getSaveAndContinueUrl()
    {
    	return $this->getUrl('*/*/save', array(
            '_current'  => true,
            'back'      => 'edit',
    	    'tab'       => '{{tab_id}}'
        ));
    }
}
