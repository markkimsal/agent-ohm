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
 * Adminhtml edit admin user account form
 *
 * @category   Mage
 * @package    Mage_Adminhtml
 * @author      Magento Core Team <core@magentocommerce.com>
 */

class Mage_Adminhtml_Block_System_Account_Edit_Form extends Mage_Adminhtml_Block_Widget_Form
{
    protected function _prepareForm()
    {
        $userId = AO::getSingleton('admin/session')->getUser()->getId();
        $user = AO::getModel('admin/user')
            ->load($userId);
        $user->unsetData('password');

        $form = new Varien_Data_Form();

        $fieldset = $form->addFieldset('base_fieldset', array('legend'=>AO::helper('adminhtml')->__('Account Information')));

        $fieldset->addField('username', 'text', array(
                'name'  => 'username',
                'label' => AO::helper('adminhtml')->__('User Name'),
                'title' => AO::helper('adminhtml')->__('User Name'),
                'required' => true,
            )
        );

        $fieldset->addField('firstname', 'text', array(
                'name'  => 'firstname',
                'label' => AO::helper('adminhtml')->__('First Name'),
                'title' => AO::helper('adminhtml')->__('First Name'),
                'required' => true,
            )
        );

        $fieldset->addField('lastname', 'text', array(
                'name'  => 'lastname',
                'label' => AO::helper('adminhtml')->__('Last Name'),
                'title' => AO::helper('adminhtml')->__('Last Name'),
                'required' => true,
            )
        );

        $fieldset->addField('user_id', 'hidden', array(
                'name'  => 'user_id',
            )
        );

        $fieldset->addField('email', 'text', array(
                'name'  => 'email',
                'label' => AO::helper('adminhtml')->__('Email'),
                'title' => AO::helper('adminhtml')->__('User Email'),
                'required' => true,
            )
        );

        $fieldset->addField('password', 'password', array(
                'name'  => 'password',
                'label' => AO::helper('adminhtml')->__('New Password'),
                'title' => AO::helper('adminhtml')->__('New Password'),
                'class' => 'input-text validate-password',
            )
        );

        $fieldset->addField('confirmation', 'password', array(
                'name'  => 'password_confirmation',
                'label' => AO::helper('adminhtml')->__('Password Confirmation'),
                'class' => 'input-text validate-cpassword',
            )
        );

        $form->setValues($user->getData());
        $form->setAction($this->getUrl('*/system_account/save'));
        $form->setMethod('post');
        $form->setUseContainer(true);
        $form->setId('edit_form');

        $this->setForm($form);

        return parent::_prepareForm();
    }
}
