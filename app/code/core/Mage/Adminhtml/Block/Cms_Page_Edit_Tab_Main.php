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
 * Cms page edit form main tab
 *
 * @category   Mage
 * @package    Mage_Adminhtml
 * @author      Magento Core Team <core@magentocommerce.com>
 */

class Mage_Adminhtml_Block_Cms_Page_Edit_Tab_Main extends Mage_Adminhtml_Block_Widget_Form
{

    protected function _prepareForm()
    {/** @var Cms_Model_Page */
        $model = AO::registry('cms_page');

        $form = new Varien_Data_Form();

        $form->setHtmlIdPrefix('page_');

        $fieldset = $form->addFieldset('base_fieldset', array('legend'=>AO::helper('cms')->__('General Information'),'class'=>'fieldset-wide'));

        if ($model->getPageId()) {
        	$fieldset->addField('page_id', 'hidden', array(
                'name' => 'page_id',
            ));
        }

    	$fieldset->addField('title', 'text', array(
            'name'      => 'title',
            'label'     => AO::helper('cms')->__('Page Title'),
            'title'     => AO::helper('cms')->__('Page Title'),
            'required'  => true,
        ));

    	$fieldset->addField('identifier', 'text', array(
            'name'      => 'identifier',
            'label'     => AO::helper('cms')->__('SEF URL Identifier'),
            'title'     => AO::helper('cms')->__('SEF URL Identifier'),
            'required'  => true,
            'class'     => 'validate-identifier',
            'after_element_html' => '<p class="nm"><small>' . AO::helper('cms')->__('(eg: domain.com/identifier)') . '</small></p>',
        ));

        /**
         * Check is single store mode
         */
        if (!AO::app()->isSingleStoreMode()) {
            $fieldset->addField('store_id', 'multiselect', array(
                'name'      => 'stores[]',
                'label'     => AO::helper('cms')->__('Store View'),
                'title'     => AO::helper('cms')->__('Store View'),
                'required'  => true,
                'values'    => AO::getSingleton('adminhtml/system_store')->getStoreValuesForForm(false, true),
            ));
        }
        else {
            $fieldset->addField('store_id', 'hidden', array(
                'name'      => 'stores[]',
                'value'     => AO::app()->getStore(true)->getId()
            ));
            $model->setStoreId(AO::app()->getStore(true)->getId());
        }

    	$fieldset->addField('is_active', 'select', array(
            'label'     => AO::helper('cms')->__('Status'),
            'title'     => AO::helper('cms')->__('Page Status'),
            'name'      => 'is_active',
            'required'  => true,
            'options'   => array(
                '1' => AO::helper('cms')->__('Enabled'),
                '0' => AO::helper('cms')->__('Disabled'),
            ),
        ));

    	$fieldset->addField('content', 'editor', array(
            'name'      => 'content',
            'label'     => AO::helper('cms')->__('Content'),
            'title'     => AO::helper('cms')->__('Content'),
            'style'     => 'height:36em;',
            'wysiwyg'   => false,
            'required'  => true,
        ));


        $form->setValues($model->getData());
        $this->setForm($form);

        return parent::_prepareForm();
    }
}
