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
class Mage_Adminhtml_Api_UserController extends Mage_Adminhtml_Controller_Action
{

    protected function _initAction()
    {
        $this->loadLayout()
            ->_setActiveMenu('api/users')
            ->_addBreadcrumb($this->__('Web Services'), $this->__('Web Services'))
            ->_addBreadcrumb($this->__('Permissions'), $this->__('Permissions'))
            ->_addBreadcrumb($this->__('Users'), $this->__('Users'))
        ;
        return $this;
    }

    public function indexAction()
    {
        $this->_initAction()
            ->_addContent($this->getLayout()->createBlock('adminhtml/api_user'))
            ->renderLayout();
    }

    public function newAction()
    {
        $this->_forward('edit');
    }

    public function editAction()
    {
        $id = $this->getRequest()->getParam('user_id');
        $model = AO::getModel('api/user');

        if ($id) {
            $model->load($id);
            if (! $model->getId()) {
                AO::getSingleton('adminhtml/session')->addError($this->__('This user no longer exists'));
                $this->_redirect('*/*/');
                return;
            }
        }
        // Restore previously entered form data from session
        $data = AO::getSingleton('adminhtml/session')->getUserData(true);
        if (!empty($data)) {
            $model->setData($data);
        }

        AO::register('api_user', $model);

        $this->_initAction()
            ->_addBreadcrumb($id ? $this->__('Edit User') : $this->__('New User'), $id ? $this->__('Edit User') : $this->__('New User'))
            ->_addContent($this->getLayout()->createBlock('adminhtml/api_user_edit')->setData('action', $this->getUrl('*/api_user/save')))
            ->_addLeft($this->getLayout()->createBlock('adminhtml/api_user_edit_tabs'));

        $this->_addJs($this->getLayout()->createBlock('adminhtml/template')->setTemplate('api/user_roles_grid_js.phtml'));
        $this->renderLayout();
    }

    public function saveAction()
    {
        if ($data = $this->getRequest()->getPost()) {
            $model = AO::getModel('api/user');
            $model->setData($data);
            try {
                $model->save();
                if ( $uRoles = $this->getRequest()->getParam('roles', false) ) {
                    /*parse_str($uRoles, $uRoles);
                    $uRoles = array_keys($uRoles);*/
                    if ( 1 == sizeof($uRoles) ) {
                        $model->setRoleIds($uRoles)
                            ->setRoleUserId($model->getUserId())
                            ->saveRelations();
                    } else if ( sizeof($uRoles) > 1 ) {
                        //@FIXME: stupid fix of previous multi-roles logic.
                        //@TODO:  make proper DB upgrade in the future revisions.
                        $rs = array();
                        $rs[0] = $uRoles[0];
                        $model->setRoleIds( $rs )->setRoleUserId( $model->getUserId() )->saveRelations();
                    }
                }
                AO::getSingleton('adminhtml/session')->addSuccess($this->__('User was successfully saved'));
                AO::getSingleton('adminhtml/session')->setUserData(false);
                $this->_redirect('*/*/edit', array('user_id' => $model->getUserId()));
                return;
            } catch (Exception $e) {
                AO::getSingleton('adminhtml/session')->addError($e->getMessage());
                AO::getSingleton('adminhtml/session')->setUserData($data);
                $this->_redirect('*/*/edit', array('user_id' => $model->getUserId()));
                return;
            }
        }
        $this->_redirect('*/*/');
    }

    public function deleteAction()
    {
        if ($id = $this->getRequest()->getParam('user_id')) {

            try {
                $model = AO::getModel('api/user');
                $model->setId($id);
                $model->delete();
                AO::getSingleton('adminhtml/session')->addSuccess($this->__('User was successfully deleted'));
                $this->_redirect('*/*/');
                return;
            }
            catch (Exception $e) {
                AO::getSingleton('adminhtml/session')->addError($e->getMessage());
                $this->_redirect('*/*/edit', array('user_id' => $this->getRequest()->getParam('user_id')));
                return;
            }
        }
        AO::getSingleton('adminhtml/session')->addError($this->__('Unable to find a user to delete'));
        $this->_redirect('*/*/');
    }

    public function rolesGridAction()
    {
        $id = $this->getRequest()->getParam('user_id');
        $model = AO::getModel('api/user');

        if ($id) {
            $model->load($id);
        }

        AO::register('api_user', $model);
        $this->getResponse()->setBody($this->getLayout()->createBlock('adminhtml/api_user_edit_tab_roles')->toHtml());
    }

    public function roleGridAction()
    {
        $this->getResponse()
            ->setBody($this->getLayout()
            ->createBlock('adminhtml/api_user_grid')
            ->toHtml()
        );
    }

    protected function _isAllowed()
    {
        return AO::getSingleton('admin/session')->isAllowed('api/users');
    }

}
