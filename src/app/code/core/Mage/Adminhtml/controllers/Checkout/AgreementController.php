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
 * Tax rule controller
 *
 * @category   Mage
 * @package    Mage_Adminhtml
 * @author      Magento Core Team <core@magentocommerce.com>
 */
class Mage_Adminhtml_Checkout_AgreementController extends Mage_Adminhtml_Controller_Action
{
    public function indexAction()
    {
        $this->_initAction()
            ->_addContent($this->getLayout()->createBlock('adminhtml/checkout_agreement'))
            ->renderLayout();
        return $this;
    }

    public function newAction()
    {
        $this->_forward('edit');
    }

    public function editAction()
    {
        $id  = $this->getRequest()->getParam('id');
        $agreementModel  = AO::getModel('checkout/agreement');
        $hlp = AO::helper('checkout');
        if ($id) {
            $agreementModel->load($id);
            if (!$agreementModel->getId()) {
                AO::getSingleton('adminhtml/session')->addError($hlp->__('This condition no longer exists'));
                $this->_redirect('*/*/');
                return;
            }
        }

        $data = AO::getSingleton('adminhtml/session')->getAgreementData(true);
        if (!empty($data)) {
            $agreementModel->setData($data);
        }

        AO::register('checkout_agreement', $agreementModel);

        $this->_initAction()
            ->_addBreadcrumb($id ? $hlp->__('Edit Condition') :  $hlp->__('New Condition'), $id ?  $hlp->__('Edit Condition') :  $hlp->__('New Condition'))
            ->_addContent($this->getLayout()->createBlock('adminhtml/checkout_agreement_edit')->setData('action', $this->getUrl('*/*/save')))
            ->renderLayout();
    }

    public function saveAction()
    {
        if ($postData = $this->getRequest()->getPost()) {
            $model = AO::getSingleton('checkout/agreement');
            $model->setData($postData);

            try {
                $model->save();

                AO::getSingleton('adminhtml/session')->addSuccess(AO::helper('checkout')->__('Condition was successfully saved'));
                $this->_redirect('*/*/');

                return;
            }
            catch (Mage_Core_Exception $e) {
                AO::getSingleton('adminhtml/session')->addError($e->getMessage());
            }
            catch (Exception $e) {
                AO::getSingleton('adminhtml/session')->addError(AO::helper('checkout')->__('Error while saving this condition. Please try again later.'));
            }

            AO::getSingleton('adminhtml/session')->setAgreementData($postData);
            $this->_redirectReferer();
        }
    }

    public function deleteAction()
    {
        $id = (int)$this->getRequest()->getParam('id');
        $model = AO::getSingleton('checkout/agreement')
            ->load($id);
        if (!$model->getId()) {
            AO::getSingleton('adminhtml/session')->addError(AO::helper('checkout')->__('This condition no longer exists'));
            $this->_redirect('*/*/');
            return;
        }

        try {
            $model->delete();

            AO::getSingleton('adminhtml/session')->addSuccess(AO::helper('checkout')->__('Condition was successfully deleted'));
            $this->_redirect('*/*/');

            return;
        }
        catch (Mage_Core_Exception $e) {
            AO::getSingleton('adminhtml/session')->addError($e->getMessage());
        }
        catch (Exception $e) {
            AO::getSingleton('adminhtml/session')->addError(AO::helper('checkout')->__('Error while deleting this condition. Please try again later.'));
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
            ->_setActiveMenu('sales/checkoutagreement')
            ->_addBreadcrumb(AO::helper('checkout')->__('Sales'), AO::helper('checkout')->__('Sales'))
            ->_addBreadcrumb(AO::helper('checkout')->__('Checkout Conditions'), AO::helper('checkout')->__('Checkout Terms and Conditions'))
        ;
        return $this;
    }

    protected function _isAllowed()
    {
	    return AO::getSingleton('admin/session')->isAllowed('sales/checkoutagreement');
    }
}
