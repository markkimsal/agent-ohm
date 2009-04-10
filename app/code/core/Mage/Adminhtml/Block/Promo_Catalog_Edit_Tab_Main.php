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
 * description
 *
 * @category    Mage
 * @category   Mage
 * @package    Mage_Adminhtml
 * @author      Magento Core Team <core@magentocommerce.com>
 */
class Mage_Adminhtml_Block_Promo_Catalog_Edit_Tab_Main extends Mage_Adminhtml_Block_Widget_Form
{

    protected function _prepareForm()
    {
        $model = AO::registry('current_promo_catalog_rule');

        //$form = new Varien_Data_Form(array('id' => 'edit_form1', 'action' => $this->getData('action'), 'method' => 'post'));
        $form = new Varien_Data_Form();

        $form->setHtmlIdPrefix('rule_');

        $fieldset = $form->addFieldset('base_fieldset', array('legend'=>AO::helper('catalogrule')->__('General Information')));

        $fieldset->addField('auto_apply', 'hidden', array(
        	'name' => 'auto_apply',
        ));

        if ($model->getId()) {
        	$fieldset->addField('rule_id', 'hidden', array(
                'name' => 'rule_id',
            ));
        }

    	$fieldset->addField('name', 'text', array(
            'name' => 'name',
            'label' => AO::helper('catalogrule')->__('Rule Name'),
            'title' => AO::helper('catalogrule')->__('Rule Name'),
            'required' => true,
        ));

        $fieldset->addField('description', 'textarea', array(
            'name' => 'description',
            'label' => AO::helper('catalogrule')->__('Description'),
            'title' => AO::helper('catalogrule')->__('Description'),
            'style' => 'width: 98%; height: 100px;',
        ));

    	$fieldset->addField('is_active', 'select', array(
            'label'     => AO::helper('catalogrule')->__('Status'),
            'title'     => AO::helper('catalogrule')->__('Status'),
            'name'      => 'is_active',
            'required' => true,
            'options'    => array(
                '1' => AO::helper('catalogrule')->__('Active'),
                '0' => AO::helper('catalogrule')->__('Inactive'),
            ),
        ));

        if (!AO::app()->isSingleStoreMode()) {
            $fieldset->addField('website_ids', 'multiselect', array(
                'name'      => 'website_ids[]',
                'label'     => AO::helper('catalogrule')->__('Websites'),
                'title'     => AO::helper('catalogrule')->__('Websites'),
                'required'  => true,
                'values'    => AO::getSingleton('adminhtml/system_config_source_website')->toOptionArray(),
            ));
        }
        else {
            $fieldset->addField('website_ids', 'hidden', array(
                'name'      => 'website_ids[]',
                'value'     => AO::app()->getStore(true)->getWebsiteId()
            ));
            $model->setWebsiteIds(AO::app()->getStore(true)->getWebsiteId());
        }

        $customerGroups = AO::getResourceModel('customer/group_collection')
            ->load()->toOptionArray();

        $found = false;
        foreach ($customerGroups as $group) {
        	if ($group['value']==0) {
        		$found = true;
        	}
        }
        if (!$found) {
        	array_unshift($customerGroups, array('value'=>0, 'label'=>AO::helper('catalogrule')->__('NOT LOGGED IN')));
        }

    	$fieldset->addField('customer_group_ids', 'multiselect', array(
            'name'      => 'customer_group_ids[]',
            'label'     => AO::helper('catalogrule')->__('Customer Groups'),
            'title'     => AO::helper('catalogrule')->__('Customer Groups'),
            'required'  => true,
            'values'    => $customerGroups,
        ));

        $dateFormatIso = AO::app()->getLocale()->getDateFormat(Mage_Core_Model_Locale::FORMAT_TYPE_SHORT);
    	$fieldset->addField('from_date', 'date', array(
            'name'   => 'from_date',
            'label'  => AO::helper('catalogrule')->__('From Date'),
            'title'  => AO::helper('catalogrule')->__('From Date'),
            'image'  => $this->getSkinUrl('images/grid-cal.gif'),
            'input_format' => Varien_Date::DATE_INTERNAL_FORMAT,
            'format'       => $dateFormatIso
        ));
    	$fieldset->addField('to_date', 'date', array(
            'name'   => 'to_date',
            'label'  => AO::helper('catalogrule')->__('To Date'),
            'title'  => AO::helper('catalogrule')->__('To Date'),
            'image'  => $this->getSkinUrl('images/grid-cal.gif'),
            'input_format' => Varien_Date::DATE_INTERNAL_FORMAT,
            'format'       => $dateFormatIso
        ));

        $fieldset->addField('sort_order', 'text', array(
            'name' => 'sort_order',
            'label' => AO::helper('catalogrule')->__('Priority'),
        ));

        $form->setValues($model->getData());

        //$form->setUseContainer(true);

        $this->setForm($form);

        return parent::_prepareForm();
    }
}