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
 * Product attribute add/edit form main tab
 *
 * @category   Mage
 * @package    Mage_Adminhtml
 * @author      Magento Core Team <core@magentocommerce.com>
 */
class Mage_Adminhtml_Block_Catalog_Product_Attribute_Edit_Tab_Main extends Mage_Adminhtml_Block_Widget_Form
{

    protected function _prepareForm()
    {
        $model = AO::registry('entity_attribute');

        $form = new Varien_Data_Form(array(
            'id' => 'edit_form',
            'action' => $this->getData('action'),
            'method' => 'post'
        ));

        $disableAttributeFields = array(
            'sku'       => array(
                'is_global',
                'is_unique',
            ),
            'url_key'   => array(
                'is_unique',
            ),
        );

        $fieldset = $form->addFieldset('base_fieldset',
            array('legend'=>AO::helper('catalog')->__('Attribute Properties'))
        );
        if ($model->getAttributeId()) {
            $fieldset->addField('attribute_id', 'hidden', array(
                'name' => 'attribute_id',
            ));
        }

        $this->_addElementTypes($fieldset);

        $yesno = array(
            array(
                'value' => 0,
                'label' => AO::helper('catalog')->__('No')
            ),
            array(
                'value' => 1,
                'label' => AO::helper('catalog')->__('Yes')
            ));

        $fieldset->addField('attribute_code', 'text', array(
            'name'  => 'attribute_code',
            'label' => AO::helper('catalog')->__('Attribute Code'),
            'title' => AO::helper('catalog')->__('Attribute Code'),
            'note'  => AO::helper('catalog')->__('For internal use. Must be unique with no spaces'),
            'class' => 'validate-code',
            'required' => true,
        ));

        $scopes = array(
            Mage_Catalog_Model_Resource_Eav_Attribute::SCOPE_STORE =>AO::helper('catalog')->__('Store View'),
            Mage_Catalog_Model_Resource_Eav_Attribute::SCOPE_WEBSITE =>AO::helper('catalog')->__('Website'),
            Mage_Catalog_Model_Resource_Eav_Attribute::SCOPE_GLOBAL =>AO::helper('catalog')->__('Global'),
        );

        if ($model->getAttributeCode() == 'status' || $model->getAttributeCode() == 'tax_class_id') {
            unset($scopes[Mage_Catalog_Model_Resource_Eav_Attribute::SCOPE_STORE]);
        }

        $fieldset->addField('is_global', 'select', array(
            'name'  => 'is_global',
            'label' => AO::helper('catalog')->__('Scope'),
            'title' => AO::helper('catalog')->__('Scope'),
            'note'  => AO::helper('catalog')->__('Declare attribute value saving scope'),
            'values'=> $scopes
        ));

        $inputTypes = array(
            array(
                'value' => 'text',
                'label' => AO::helper('catalog')->__('Text Field')
            ),
            array(
                'value' => 'textarea',
                'label' => AO::helper('catalog')->__('Text Area')
            ),
            array(
                'value' => 'date',
                'label' => AO::helper('catalog')->__('Date')
            ),
            array(
                'value' => 'boolean',
                'label' => AO::helper('catalog')->__('Yes/No')
            ),
            array(
                'value' => 'multiselect',
                'label' => AO::helper('catalog')->__('Multiple Select')
            ),
            array(
                'value' => 'select',
                'label' => AO::helper('catalog')->__('Dropdown')
            ),
            array(
                'value' => 'price',
                'label' => AO::helper('catalog')->__('Price')
            ),
            array(
                'value' => 'gallery',
                'label' => AO::helper('catalog')->__('Gallery')
            ),
            array(
                'value' => 'media_image',
                'label' => AO::helper('catalog')->__('Media Image')
            ),
        );

        $response = new Varien_Object();
        $response->setTypes(array());
        AO::dispatchEvent('adminhtml_product_attribute_types', array('response'=>$response));

        $_disabledTypes = array();
        $_hiddenFields = array();
        foreach ($response->getTypes() as $type) {
            $inputTypes[] = $type;
            if (isset($type['hide_fields'])) {
                $_hiddenFields[$type['value']] = $type['hide_fields'];
            }
            if (isset($type['disabled_types'])) {
                $_disabledTypes[$type['value']] = $type['disabled_types'];
            }
        }
        AO::register('attribute_type_hidden_fields', $_hiddenFields);
        AO::register('attribute_type_disabled_types', $_disabledTypes);


        $fieldset->addField('frontend_input', 'select', array(
            'name' => 'frontend_input',
            'label' => AO::helper('catalog')->__('Catalog Input Type for Store Owner'),
            'title' => AO::helper('catalog')->__('Catalog Input Type for Store Owner'),
            'value' => 'text',
            'values'=> $inputTypes
        ));

        $fieldset->addField('default_value_text', 'text', array(
            'name' => 'default_value_text',
            'label' => AO::helper('catalog')->__('Default value'),
            'title' => AO::helper('catalog')->__('Default value'),
            'value' => $model->getDefaultValue(),
        ));

        $fieldset->addField('default_value_yesno', 'select', array(
            'name' => 'default_value_yesno',
            'label' => AO::helper('catalog')->__('Default value'),
            'title' => AO::helper('catalog')->__('Default value'),
            'values' => $yesno,
            'value' => $model->getDefaultValue(),
        ));

        $dateFormatIso = AO::app()->getLocale()->getDateFormat(Mage_Core_Model_Locale::FORMAT_TYPE_SHORT);
        $fieldset->addField('default_value_date', 'date', array(
            'name'   => 'default_value_date',
            'label'  => AO::helper('catalog')->__('Default value'),
            'title'  => AO::helper('catalog')->__('Default value'),
            'image'  => $this->getSkinUrl('images/grid-cal.gif'),
            'value'  => $model->getDefaultValue(),
            'format'       => $dateFormatIso
        ));

        $fieldset->addField('default_value_textarea', 'textarea', array(
            'name' => 'default_value_textarea',
            'label' => AO::helper('catalog')->__('Default value'),
            'title' => AO::helper('catalog')->__('Default value'),
            'value' => $model->getDefaultValue(),
        ));

        $fieldset->addField('is_unique', 'select', array(
            'name' => 'is_unique',
            'label' => AO::helper('catalog')->__('Unique Value'),
            'title' => AO::helper('catalog')->__('Unique Value (not shared with other products)'),
            'note'  => AO::helper('catalog')->__('Not shared with other products'),
            'values' => $yesno,
        ));

        $fieldset->addField('is_required', 'select', array(
            'name' => 'is_required',
            'label' => AO::helper('catalog')->__('Values Required'),
            'title' => AO::helper('catalog')->__('Values Required'),
            'values' => $yesno,
        ));

        $fieldset->addField('frontend_class', 'select', array(
            'name'  => 'frontend_class',
            'label' => AO::helper('catalog')->__('Input Validation for Store Owner'),
            'title' => AO::helper('catalog')->__('Input Validation for Store Owner'),
            'values'=>  array(
                array(
                    'value' => '',
                    'label' => AO::helper('catalog')->__('None')
                ),
                array(
                    'value' => 'validate-number',
                    'label' => AO::helper('catalog')->__('Decimal Number')
                ),
                array(
                    'value' => 'validate-digits',
                    'label' => AO::helper('catalog')->__('Integer Number')
                ),
                array(
                    'value' => 'validate-email',
                    'label' => AO::helper('catalog')->__('Email')
                ),
                array(
                    'value' => 'validate-url',
                    'label' => AO::helper('catalog')->__('Url')
                ),
                array(
                    'value' => 'validate-alpha',
                    'label' => AO::helper('catalog')->__('Letters')
                ),
                array(
                    'value' => 'validate-alphanum',
                    'label' => AO::helper('catalog')->__('Letters(a-zA-Z) or Numbers(0-9)')
                ),
            )
        ));
/*
        $fieldset->addField('use_in_super_product', 'select', array(
            'name' => 'use_in_super_product',
            'label' => AO::helper('catalog')->__('Apply To Configurable/Grouped Product'),
            'values' => $yesno,
        )); */

        $fieldset->addField('apply_to', 'apply', array(
            'name'        => 'apply_to[]',
            'label'       => AO::helper('catalog')->__('Apply To'),
            'values'      => Mage_Catalog_Model_Product_Type::getOptions(),
            'mode_labels' => array(
                'all'     => AO::helper('catalog')->__('All Product Types'),
                'custom'  => AO::helper('catalog')->__('Selected Product Types')
            ),
            'required'    => true
        ));

        $fieldset->addField('is_configurable', 'select', array(
            'name' => 'is_configurable',
            'label' => AO::helper('catalog')->__('Use To Create Configurable Product'),
            'values' => $yesno,
        ));
        // -----


        // frontend properties fieldset
        $fieldset = $form->addFieldset('front_fieldset', array('legend'=>AO::helper('catalog')->__('Frontend Properties')));

        $fieldset->addField('is_searchable', 'select', array(
            'name' => 'is_searchable',
            'label' => AO::helper('catalog')->__('Use in quick search'),
            'title' => AO::helper('catalog')->__('Use in quick search'),
            'values' => $yesno,
        ));

        $fieldset->addField('is_visible_in_advanced_search', 'select', array(
            'name' => 'is_visible_in_advanced_search',
            'label' => AO::helper('catalog')->__('Use in advanced search'),
            'title' => AO::helper('catalog')->__('Use in advanced search'),
            'values' => $yesno,
        ));

        $fieldset->addField('is_comparable', 'select', array(
            'name' => 'is_comparable',
            'label' => AO::helper('catalog')->__('Comparable on Front-end'),
            'title' => AO::helper('catalog')->__('Comparable on Front-end'),
            'values' => $yesno,
        ));


        $fieldset->addField('is_filterable', 'select', array(
            'name' => 'is_filterable',
            'label' => AO::helper('catalog')->__("Use In Layered Navigation"),
            'title' => AO::helper('catalog')->__('Can be used only with catalog input type Dropdown, Multiple Select and Price'),
            'note' => AO::helper('catalog')->__('Can be used only with catalog input type Dropdown, Multiple Select and Price'),
            'values' => array(
                array('value' => '0', 'label' => AO::helper('catalog')->__('No')),
                array('value' => '1', 'label' => AO::helper('catalog')->__('Filterable (with results)')),
                array('value' => '2', 'label' => AO::helper('catalog')->__('Filterable (no results)')),
            ),
        ));

        $fieldset->addField('is_filterable_in_search', 'select', array(
            'name' => 'is_filterable_in_search',
            'label' => AO::helper('catalog')->__("Use In Search Results Layered Navigation"),
            'title' => AO::helper('catalog')->__('Can be used only with catalog input type Dropdown, Multiple Select and Price'),
            'note' => AO::helper('catalog')->__('Can be used only with catalog input type Dropdown, Multiple Select and Price'),
            'values' => $yesno,
        ));

        $fieldset->addField('position', 'text', array(
            'name' => 'position',
            'label' => AO::helper('catalog')->__('Position'),
            'title' => AO::helper('catalog')->__('Position In Layered Navigation'),
            'note' => AO::helper('catalog')->__('Position of attribute in layered navigation block'),
            'class' => 'validate-digits',
        ));

        $htmlAllowed = $fieldset->addField('is_html_allowed_on_front', 'select', array(
            'name' => 'is_html_allowed_on_front',
            'label' => AO::helper('catalog')->__('Allow HTML-tags on Front-end'),
            'title' => AO::helper('catalog')->__('Allow HTML-tags on Front-end'),
            'values' => $yesno,
        ));
        if (!$model->getId()) {
            $htmlAllowed->setValue(1);
        }

        $fieldset->addField('is_visible_on_front', 'select', array(
            'name'      => 'is_visible_on_front',
            'label'     => AO::helper('catalog')->__('Visible on Product View Page on Front-end'),
            'title'     => AO::helper('catalog')->__('Visible on Product View Page on Front-end'),
            'values'    => $yesno,
        ));

        $fieldset->addField('used_in_product_listing', 'select', array(
            'name'      => 'used_in_product_listing',
            'label'     => AO::helper('catalog')->__('Used in product listing'),
            'title'     => AO::helper('catalog')->__('Used in product listing'),
            'note'      => AO::helper('catalog')->__('Depends on design theme'),
            'values'    => $yesno,
        ));
        $fieldset->addField('used_for_sort_by', 'select', array(
            'name'      => 'used_for_sort_by',
            'label'     => AO::helper('catalog')->__('Used for sorting in product listing'),
            'title'     => AO::helper('catalog')->__('Used for sorting in product listing'),
            'note'      => AO::helper('catalog')->__('Depends on design theme'),
            'values'    => $yesno,
        ));

        if ($model->getId()) {
            $form->getElement('attribute_code')->setDisabled(1);
            $form->getElement('frontend_input')->setDisabled(1);

            if (isset($disableAttributeFields[$model->getAttributeCode()])) {
                foreach ($disableAttributeFields[$model->getAttributeCode()] as $field) {
                    $form->getElement($field)->setDisabled(1);
                }
            }
        }
        if (!$model->getIsUserDefined() && $model->getId()) {
            $form->getElement('is_unique')->setDisabled(1);
        }

        $form->addValues($model->getData());

        $form->getElement('apply_to')->setSize(5);

        if ($applyTo = $model->getApplyTo()) {
            $applyTo = is_array($applyTo) ? $applyTo : explode(',', $applyTo);
            $form->getElement('apply_to')->setValue($applyTo);
        } else {
            $form->getElement('apply_to')->addClass('no-display ignore-validate');
        }

        $this->setForm($form);

        return parent::_prepareForm();
    }

    protected function _getAdditionalElementTypes()
    {
        return array(
            'apply' => AO::getConfig()->getBlockClassName('adminhtml/catalog_product_helper_form_apply')
        );
    }

}
