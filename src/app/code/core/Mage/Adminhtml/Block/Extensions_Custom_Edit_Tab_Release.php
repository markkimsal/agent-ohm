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
 * Convert profile edit tab
 *
 * @category   Mage
 * @package    Mage_Adminhtml
 * @author      Magento Core Team <core@magentocommerce.com>
 */
class Mage_Adminhtml_Block_Extensions_Custom_Edit_Tab_Release
    extends Mage_Adminhtml_Block_Extensions_Custom_Edit_Tab_Abstract
{
    public function __construct()
    {
        parent::__construct();
        $this->setTemplate('extensions/custom/release.phtml');
    }

    public function initForm()
    {
        $form = new Varien_Data_Form();
        $form->setHtmlIdPrefix('_release');

        $fieldset = $form->addFieldset('release_fieldset', array('legend'=>AO::helper('adminhtml')->__('Release')));

        $stabilityOptions = AO::getModel('adminhtml/extension')->getStabilityOptions();

        $fieldset->addField('release_version', 'text', array(
            'name' => 'release_version',
            'label' => AO::helper('adminhtml')->__('Release Version'),
            'required' => true,
        ));

        $fieldset->addField('api_version', 'text', array(
            'name' => 'api_version',
            'label' => AO::helper('adminhtml')->__('API Version'),
            'required' => true,
        ));

        $fieldset->addField('release_stability', 'select', array(
            'name' => 'release_stability',
            'label' => AO::helper('adminhtml')->__('Release Stability'),
            'options' => $stabilityOptions,
        ));

        $fieldset->addField('api_stability', 'select', array(
            'name' => 'api_stability',
            'label' => AO::helper('adminhtml')->__('API Stability'),
            'options' => $stabilityOptions,
        ));

    	$fieldset->addField('notes', 'textarea', array(
            'name' => 'notes',
            'label' => AO::helper('adminhtml')->__('Notes'),
            'style' => 'height:300px;',
            'required' => true,
        ));

        $form->setValues($this->getData());

        $this->setForm($form);

        return $this;
    }
}
