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
 * Poll edit form
 *
 * @category   Mage
 * @package    Mage_Adminhtml
 * @author      Magento Core Team <core@magentocommerce.com>
 */

class Mage_Adminhtml_Block_Poll_Edit_Tab_Form extends Mage_Adminhtml_Block_Widget_Form
{
    protected function _prepareForm()
    {
        $form = new Varien_Data_Form();

        $fieldset = $form->addFieldset('poll_form', array('legend'=>AO::helper('poll')->__('Poll information')));
        $fieldset->addField('poll_title', 'text', array(
            'label'     => AO::helper('poll')->__('Poll Question'),
            'class'     => 'required-entry',
            'required'  => true,
            'name'      => 'poll_title',
        ));

        $fieldset->addField('closed', 'select', array(
            'label'     => AO::helper('poll')->__('Status'),
            'name'      => 'closed',
            'values'    => array(
                array(
                    'value'     => 1,
                    'label'     => AO::helper('poll')->__('Closed'),
                ),

                array(
                    'value'     => 0,
                    'label'     => AO::helper('poll')->__('Open'),
                ),
            ),
        ));

        if (!AO::app()->isSingleStoreMode()) {
            $fieldset->addField('store_ids', 'multiselect', array(
                'label'     => AO::helper('poll')->__('Visible In'),
                'required'  => true,
                'name'      => 'store_ids[]',
                'values'    => AO::getSingleton('adminhtml/system_store')->getStoreValuesForForm(),
                'value'     => AO::registry('poll_data')->getStoreIds()
            ));
        }
        else {
            $fieldset->addField('store_ids', 'hidden', array(
                'name'      => 'store_ids[]',
                'value'     => AO::app()->getStore(true)->getId()
            ));
            AO::registry('poll_data')->setStoreIds(AO::app()->getStore(true)->getId());
        }


        if( AO::getSingleton('adminhtml/session')->getPollData() ) {
            $form->setValues(AO::getSingleton('adminhtml/session')->getPollData());
            AO::getSingleton('adminhtml/session')->setPollData(null);
        } elseif( AO::registry('poll_data') ) {
            $form->setValues(AO::registry('poll_data')->getData());

            $fieldset->addField('was_closed', 'hidden', array(
                'name'      => 'was_closed',
                'no_span'   => true,
                'value'     => AO::registry('poll_data')->getClosed()
            ));
        }

        $this->setForm($form);
        return parent::_prepareForm();
    }
}