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
 * @package    Mage_Cms
 * @copyright  Copyright (c) 2008 Irubin Consulting Inc. DBA Varien (http://www.varien.com)
 * @license    http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */


/**
 * Cms manage pages controller
 *
 * @category   Mage
 * @package    Mage_Cms
 * @author      Magento Core Team <core@magentocommerce.com>
 */
class Mage_Adminhtml_Cms_PageController extends Mage_Adminhtml_Controller_Action
{

    /**
     * Init actions
     *
     * @return Mage_Adminhtml_Cms_PageController
     */
    protected function _initAction()
    {
        // load layout, set active menu and breadcrumbs
        $this->loadLayout()
            ->_setActiveMenu('cms/page')
            ->_addBreadcrumb(AO::helper('cms')->__('CMS'), AO::helper('cms')->__('CMS'))
            ->_addBreadcrumb(AO::helper('cms')->__('Manage Pages'), AO::helper('cms')->__('Manage Pages'))
        ;
        return $this;
    }

    /**
     * Index action
     */
    public function indexAction()
    {
        $this->_initAction()
            ->_addContent($this->getLayout()->createBlock('adminhtml/cms_page'))
            ->renderLayout();
    }

    /**
     * Create new CMS page
     */
    public function newAction()
    {
        // the same form is used to create and edit
        $this->_forward('edit');
    }

    /**
     * Edit CMS page
     */
    public function editAction()
    {
        // 1. Get ID and create model
        $id = $this->getRequest()->getParam('page_id');
        $model = AO::getModel('cms/page');

        // 2. Initial checking
        if ($id) {
            $model->load($id);/*die('<br>#stop');*/
            if (! $model->getId()) {
                AO::getSingleton('adminhtml/session')->addError(AO::helper('cms')->__('This page no longer exists'));
                $this->_redirect('*/*/');
                return;
            }
        }
//print '<pre>';var_dump($model->getData());
        // 3. Set entered data if was error when we do save
        $data = AO::getSingleton('adminhtml/session')->getFormData(true);
        if (! empty($data)) {
            $model->setData($data);
        }

        // 4. Register model to use later in blocks
        AO::register('cms_page', $model);

        // 5. Build edit form
        $this->_initAction()
            ->_addBreadcrumb($id ? AO::helper('cms')->__('Edit Page') : AO::helper('cms')->__('New Page'), $id ? AO::helper('cms')->__('Edit Page') : AO::helper('cms')->__('New Page'))
            ->_addContent($this->getLayout()->createBlock('adminhtml/cms_page_edit')->setData('action', $this->getUrl('*/cms_page/save')))
            ->_addLeft($this->getLayout()->createBlock('adminhtml/cms_page_edit_tabs'));

        if (AO::app()->getConfig()->getModuleConfig('Mage_GoogleOptimizer')->is('active', true)
            && AO::helper('googleoptimizer')->isOptimizerActive()) {
            $this->_addJs($this->getLayout()->createBlock('googleoptimizer/js')->setTemplate('googleoptimizer/js.phtml'));
        }
        $this->renderLayout();
    }

    /**
     * Save action
     */
    public function saveAction()
    {
        // check if data sent
        if ($data = $this->getRequest()->getPost()) {
            // init model and set data
            $model = AO::getModel('cms/page');

//            if ($id = $this->getRequest()->getParam('page_id')) {
//                $model->load($id);
//                if ($id != $model->getId()) {
//                    AO::getSingleton('adminhtml/session')->addError(AO::helper('cms')->__('The page you are trying to save no longer exists'));
//                    AO::getSingleton('adminhtml/session')->setFormData($data);
//                    $this->_redirect('*/*/edit', array('page_id' => $this->getRequest()->getParam('page_id')));
//                    return;
//                }
//            }

            $model->setData($data);

            AO::dispatchEvent('cms_page_prepare_save', array('page' => $model, 'request' => $this->getRequest()));

            // try to save it
            try {
                // save the data
                $model->save();

                // display success message
                AO::getSingleton('adminhtml/session')->addSuccess(AO::helper('cms')->__('Page was successfully saved'));
                // clear previously saved data from session
                AO::getSingleton('adminhtml/session')->setFormData(false);
                // check if 'Save and Continue'
                if ($this->getRequest()->getParam('back')) {
                    $this->_redirect('*/*/edit', array('page_id' => $model->getId()));
                    return;
                }
                // go to grid
                $this->_redirect('*/*/');
                return;

            } catch (Exception $e) {
                // display error message
                AO::getSingleton('adminhtml/session')->addError($e->getMessage());
                // save data in session
                AO::getSingleton('adminhtml/session')->setFormData($data);
                // redirect to edit form
                $this->_redirect('*/*/edit', array('page_id' => $this->getRequest()->getParam('page_id')));
                return;
            }
        }
        $this->_redirect('*/*/');
    }

    /**
     * Delete action
     */
    public function deleteAction()
    {
        // check if we know what should be deleted
        if ($id = $this->getRequest()->getParam('page_id')) {
            $title = "";
            try {
                // init model and delete
                $model = AO::getModel('cms/page');
                $model->load($id);
                $title = $model->getTitle();
                $model->delete();
                // display success message
                AO::getSingleton('adminhtml/session')->addSuccess(AO::helper('cms')->__('Page was successfully deleted'));
                // go to grid
                AO::dispatchEvent('adminhtml_cmspage_on_delete', array('title' => $title, 'status' => 'success'));
                $this->_redirect('*/*/');
                return;

            } catch (Exception $e) {
                AO::dispatchEvent('adminhtml_cmspage_on_delete', array('title' => $title, 'status' => 'fail'));
                // display error message
                AO::getSingleton('adminhtml/session')->addError($e->getMessage());
                // go back to edit form
                $this->_redirect('*/*/edit', array('page_id' => $id));
                return;
            }
        }
        // display error message
        AO::getSingleton('adminhtml/session')->addError(AO::helper('cms')->__('Unable to find a page to delete'));
        // go to grid
        $this->_redirect('*/*/');
    }

    /**
     * Check the permission to run it
     *
     * @return boolean
     */
    protected function _isAllowed()
    {
        return AO::getSingleton('admin/session')->isAllowed('cms/page');
    }
}