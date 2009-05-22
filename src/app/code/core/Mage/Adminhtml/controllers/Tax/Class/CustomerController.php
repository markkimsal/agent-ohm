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
 * Adminhtml customer tax class controller
 *
 * @category   Mage
 * @package    Mage_Adminhtml
 * @author      Magento Core Team <core@magentocommerce.com>
 */

class Mage_Adminhtml_Tax_Class_CustomerController extends Mage_Adminhtml_Controller_Action
{
    /**
     * grid view
     *
     */
    public function indexAction()
    {
        $this->_initAction()
            ->_addContent($this->getLayout()->createBlock('adminhtml/tax_class')->setClassType('CUSTOMER'))
            ->renderLayout();
    }

    /**
     * new class action
     *
     */
    public function newAction()
    {
        $this->_forward('edit');
    }

    /**
     * edit class action
     *
     */
    public function editAction()
    {
        $classId    = $this->getRequest()->getParam('id');
        $model      = AO::getModel('tax/class');
        if ($classId) {
            $model->load($classId);
            if (!$model->getId() || $model->getClassType() != 'CUSTOMER') {
                AO::getSingleton('adminhtml/session')->addError(AO::helper('tax')->__('This class no longer exists'));
                $this->_redirect('*/*/');
                return;
            }
        }

        $data = AO::getSingleton('adminhtml/session')->getClassData(true);
        if (!empty($data)) {
            $model->setData($data);
        }

        AO::register('tax_class', $model);

        $this->_initAction()
            ->_addBreadcrumb($classId ? AO::helper('tax')->__('Edit Class') :  AO::helper('tax')->__('New Class'), $classId ?  AO::helper('tax')->__('Edit Class') :  AO::helper('tax')->__('New Class'))
            ->_addContent($this->getLayout()->createBlock('adminhtml/tax_class_edit')->setData('action', $this->getUrl('*/tax_class/save'))->setClassType('CUSTOMER'))
            ->renderLayout();
    }

    /**
     * delete class action
     *
     */
    public function deleteAction()
    {
        $classId    = $this->getRequest()->getParam('id');
        $classModel = AO::getModel('tax/class')
            ->load($classId);

        if (!$classModel->getId() || $classModel->getClassType() != 'CUSTOMER') {
            AO::getSingleton('adminhtml/session')->addError(AO::helper('tax')->__('This class no longer exists'));
            $this->_redirect('*/*/');
            return;
        }

        $ruleCollection = AO::getModel('tax/calculation_rule')
            ->getCollection()
            ->setClassTypeFilter('CUSTOMER', $classId);

        if ($ruleCollection->getSize() > 0) {
            AO::getSingleton('adminhtml/session')->addError(AO::helper('tax')->__('You cannot delete this tax class as it is used in Tax Rules. You have to delete the rules it is used in first.'));
            $this->_redirectReferer();
            return;
        }

        $customerGroupCollection = AO::getModel('customer/group')
            ->getCollection()
            ->addFieldToFilter('tax_class_id', $classId);
        $groupCount = $customerGroupCollection->getSize();

        if ($groupCount > 0) {
            AO::getSingleton('adminhtml/session')->addError(AO::helper('tax')->__('You cannot delete this tax class as it is used for %d customer groups.', $groupCount));
            $this->_redirectReferer();
            return;
        }

        try {
            $classModel->delete();

            AO::getSingleton('adminhtml/session')->addSuccess(AO::helper('tax')->__('Tax class was successfully deleted'));
            $this->getResponse()->setRedirect($this->getUrl("*/*/"));
            return ;
        }
        catch (Mage_Core_Exception $e) {
            AO::getSingleton('adminhtml/session')->addError($e->getMessage());
        }
        catch (Exception $e) {
            AO::getSingleton('adminhtml/session')->addError(AO::helper('tax')->__('Error while deleting this class. Please try again later.'));
        }

        $this->_redirectReferer();
    }

    /**
     * Initialize action
     *
     * @return Mage_Adminhtml_Controller_Action
     */
    protected function _initAction()
    {
        $this->loadLayout()
            ->_setActiveMenu('sales/tax/tax_class_customer')
            ->_addBreadcrumb(AO::helper('tax')->__('Sales'), AO::helper('tax')->__('Sales'))
            ->_addBreadcrumb(AO::helper('tax')->__('Tax'), AO::helper('tax')->__('Tax'))
            ->_addBreadcrumb(AO::helper('tax')->__('Manage Customer Tax Classes'), AO::helper('tax')->__('Manage Customer Tax Classes'))
        ;
        return $this;
    }

    /**
     * Check current user permission on resource and privilege
     *
     * @return bool
     */
    protected function _isAllowed()
    {
	    return AO::getSingleton('admin/session')->isAllowed('sales/tax/classes_customer');
    }
}