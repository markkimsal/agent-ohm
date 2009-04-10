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


class Mage_Adminhtml_Promo_QuoteController extends Mage_Adminhtml_Controller_Action
{
    protected function _initRule()
    {
        AO::register('current_promo_quote_rule', AO::getModel('salesrule/rule'));
        if ($id = (int) $this->getRequest()->getParam('id')) {
            AO::registry('current_promo_quote_rule')
                ->load($id);
        }
    }

    protected function _initAction()
    {
        $this->loadLayout()
            ->_setActiveMenu('promo/quote')
            ->_addBreadcrumb(AO::helper('salesrule')->__('Promotions'), AO::helper('salesrule')->__('Promotions'))
        ;
        return $this;
    }

    public function indexAction()
    {
        $this->_initAction()
            ->_addBreadcrumb(AO::helper('salesrule')->__('Catalog'), AO::helper('salesrule')->__('Catalog'))
            ->_addContent($this->getLayout()->createBlock('adminhtml/promo_quote'))
            ->renderLayout();
    }

    public function newAction()
    {
        $this->_forward('edit');
    }

    public function editAction()
    {
        $id = $this->getRequest()->getParam('id');
        $model = AO::getModel('salesrule/rule');

        if ($id) {
            $model->load($id);
            if (! $model->getRuleId()) {
                AO::getSingleton('adminhtml/session')->addError(AO::helper('salesrule')->__('This rule no longer exists'));
                $this->_redirect('*/*');
                return;
            }
        }

        // set entered data if was error when we do save
        $data = AO::getSingleton('adminhtml/session')->getPageData(true);
        if (!empty($data)) {
            $model->addData($data);
        }

        $model->getConditions()->setJsFormObject('rule_conditions_fieldset');
        $model->getActions()->setJsFormObject('rule_actions_fieldset');

        AO::register('current_promo_quote_rule', $model);

        $block = $this->getLayout()->createBlock('adminhtml/promo_quote_edit')
            ->setData('action', $this->getUrl('*/*/save'));

        $this->_initAction();
        $this->getLayout()->getBlock('head')
            ->setCanLoadExtJs(true)
            ->setCanLoadRulesJs(true);

        $this
            ->_addBreadcrumb($id ? AO::helper('salesrule')->__('Edit Rule') : AO::helper('salesrule')->__('New Rule'), $id ? AO::helper('salesrule')->__('Edit Rule') : AO::helper('salesrule')->__('New Rule'))
            ->_addContent($block)
            ->_addLeft($this->getLayout()->createBlock('adminhtml/promo_quote_edit_tabs'))
            ->renderLayout();

    }

    public function saveAction()
    {
        if ($data = $this->getRequest()->getPost()) {
            $model = AO::getModel('salesrule/rule');

            if ($id = $this->getRequest()->getParam('id')) {
                $model->load($id);
                if ($id != $model->getId()) {
                    AO::getSingleton('adminhtml/session')->addError(AO::helper('salesrule')->__('The page you are trying to save no longer exists'));
                    AO::getSingleton('adminhtml/session')->setPageData($data);
                    $this->_redirect('*/*/edit', array('page_id' => $this->getRequest()->getParam('page_id')));
                    return;
                }
            }
            if (isset($data['simple_action']) && $data['simple_action'] == 'by_percent' && isset($data['discount_amount'])) {
                $data['discount_amount'] = min(100,$data['discount_amount']);
            }
            if (isset($data['rule']['conditions'])) {
                $data['conditions'] = $data['rule']['conditions'];
            }
            if (isset($data['rule']['actions'])) {
                $data['actions'] = $data['rule']['actions'];
            }
            unset($data['rule']);

            $model->loadPost($data);

            AO::getSingleton('adminhtml/session')->setPageData($model->getData());
            try {
                $model->save();
                AO::getSingleton('adminhtml/session')->addSuccess(AO::helper('salesrule')->__('Rule was successfully saved'));
                AO::getSingleton('adminhtml/session')->setPageData(false);
                $this->_redirect('*/*/');
                return;
            } catch (Exception $e) {
                AO::getSingleton('adminhtml/session')->addError($e->getMessage());
                AO::getSingleton('adminhtml/session')->setPageData($data);
                $this->_redirect('*/*/edit', array('id' => $this->getRequest()->getParam('rule_id')));
                return;
            }
        }
        $this->_redirect('*/*/');
    }

    public function deleteAction()
    {
        if ($id = $this->getRequest()->getParam('id')) {
            try {
                $model = AO::getModel('salesrule/rule');
                $model->load($id);
                $model->delete();
                AO::getSingleton('adminhtml/session')->addSuccess(AO::helper('salesrule')->__('Rule was successfully deleted'));
                $this->_redirect('*/*/');
                return;
            }
            catch (Exception $e) {
                AO::getSingleton('adminhtml/session')->addError($e->getMessage());
                $this->_redirect('*/*/edit', array('id' => $this->getRequest()->getParam('id')));
                return;
            }
        }
        AO::getSingleton('adminhtml/session')->addError(AO::helper('salesrule')->__('Unable to find a page to delete'));
        $this->_redirect('*/*/');
    }

    public function newConditionHtmlAction()
    {
        $id = $this->getRequest()->getParam('id');
        $typeArr = explode('|', str_replace('-', '/', $this->getRequest()->getParam('type')));
        $type = $typeArr[0];

        $model = AO::getModel($type)
            ->setId($id)
            ->setType($type)
            ->setRule(AO::getModel('salesrule/rule'))
            ->setPrefix('conditions');
        if (!empty($typeArr[1])) {
            $model->setAttribute($typeArr[1]);
        }

        if ($model instanceof Mage_Rule_Model_Condition_Abstract) {
            $model->setJsFormObject($this->getRequest()->getParam('form'));
            $html = $model->asHtmlRecursive();
        } else {
            $html = '';
        }
        $this->getResponse()->setBody($html);
    }

    public function newActionHtmlAction()
    {
        $id = $this->getRequest()->getParam('id');
        $typeArr = explode('|', str_replace('-', '/', $this->getRequest()->getParam('type')));
        $type = $typeArr[0];

        $model = AO::getModel($type)
            ->setId($id)
            ->setType($type)
            ->setRule(AO::getModel('salesrule/rule'))
            ->setPrefix('actions');
        if (!empty($typeArr[1])) {
            $model->setAttribute($typeArr[1]);
        }

        if ($model instanceof Mage_Rule_Model_Condition_Abstract) {
            $model->setJsFormObject($this->getRequest()->getParam('form'));
            $html = $model->asHtmlRecursive();
        } else {
            $html = '';
        }
        $this->getResponse()->setBody($html);
    }

    public function applyRulesAction()
    {
        $this->_initAction();

        $this->renderLayout();
    }

    public function gridAction()
    {
        $this->_initRule();
        $this->getResponse()->setBody(
            $this->getLayout()->createBlock('adminhtml/promo_quote_edit_tab_product')->toHtml()
        );
    }

    protected function _isAllowed()
    {
        return AO::getSingleton('admin/session')->isAllowed('promo/quote');
    }

}
