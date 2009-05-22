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


class Mage_Adminhtml_Promo_CatalogController extends Mage_Adminhtml_Controller_Action
{
    protected function _initAction()
    {
        $this->loadLayout()
            ->_setActiveMenu('promo/catalog')
            ->_addBreadcrumb(AO::helper('catalogrule')->__('Promotions'), AO::helper('catalogrule')->__('Promotions'))
        ;
        return $this;
    }

    public function indexAction()
    {
        if (AO::app()->loadCache('catalog_rules_dirty')) {
            AO::getSingleton('adminhtml/session')->addNotice(AO::helper('catalogrule')->__('There are rules that have been changed but not applied. Please, click Apply Rules in order to see immediate effect in catalog.'));
        }

        $this->_initAction()
            ->_addBreadcrumb(AO::helper('catalogrule')->__('Catalog'), AO::helper('catalogrule')->__('Catalog'))
            ->_addContent($this->getLayout()->createBlock('adminhtml/promo_catalog'))
            ->renderLayout();
    }

    public function newAction()
    {
        $this->_forward('edit');
    }

    public function editAction()
    {
        $id = $this->getRequest()->getParam('id');
        $model = AO::getModel('catalogrule/rule');

        if ($id) {
            $model->load($id);
            if (! $model->getRuleId()) {
                AO::getSingleton('adminhtml/session')->addError(AO::helper('catalogrule')->__('This rule no longer exists'));
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

        AO::register('current_promo_catalog_rule', $model);

        $block = $this->getLayout()->createBlock('adminhtml/promo_catalog_edit')
            ->setData('action', $this->getUrl('*/promo_catalog/save'));

        $this->_initAction();

        $this->getLayout()->getBlock('head')
            ->setCanLoadExtJs(true)
            ->setCanLoadRulesJs(true);

        $this
            ->_addBreadcrumb($id ? AO::helper('catalogrule')->__('Edit Rule') : AO::helper('catalogrule')->__('New Rule'), $id ? AO::helper('catalogrule')->__('Edit Rule') : AO::helper('catalogrule')->__('New Rule'))
            ->_addContent($block)
            ->_addLeft($this->getLayout()->createBlock('adminhtml/promo_catalog_edit_tabs'))
            ->renderLayout();

    }

    public function saveAction()
    {
        if ($data = $this->getRequest()->getPost()) {
            $model = AO::getModel('catalogrule/rule');
//            if ($id = $this->getRequest()->getParam('page_id')) {
//                $model->load($id);
//                if ($id != $model->getId()) {
//                    AO::getSingleton('adminhtml/session')->addError(AO::helper('catalogrule')->__('The page you are trying to save no longer exists'));
//                    AO::getSingleton('adminhtml/session')->setPageData($data);
//                    $this->_redirect('*/*/edit', array('page_id' => $this->getRequest()->getParam('page_id')));
//                    return;
//                }
//            }
            $data['conditions'] = $data['rule']['conditions'];
            //$data['actions'] = $data['rule']['actions'];
            unset($data['rule']);

            if (!empty($data['auto_apply'])) {
                $autoApply = true;
                unset($data['auto_apply']);
            } else {
                $autoApply = false;
            }

            $model->loadPost($data);
            AO::getSingleton('adminhtml/session')->setPageData($model->getData());
            try {
                $model->save();

                AO::getSingleton('adminhtml/session')->addSuccess(AO::helper('catalogrule')->__('Rule was successfully saved'));
                AO::getSingleton('adminhtml/session')->setPageData(false);
                if ($autoApply) {
                    $this->_forward('applyRules');
                } else {
                    AO::app()->saveCache(1, 'catalog_rules_dirty');
                    $this->_redirect('*/*/');
                }
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
                $model = AO::getModel('catalogrule/rule');
                $model->load($id);
                $model->delete();
                AO::app()->saveCache(1, 'catalog_rules_dirty');
                AO::getSingleton('adminhtml/session')->addSuccess(AO::helper('catalogrule')->__('Rule was successfully deleted'));
                $this->_redirect('*/*/');
                return;
            }
            catch (Exception $e) {
                AO::getSingleton('adminhtml/session')->addError($e->getMessage());
                $this->_redirect('*/*/edit', array('id' => $this->getRequest()->getParam('id')));
                return;
            }
        }
        AO::getSingleton('adminhtml/session')->addError(AO::helper('catalogrule')->__('Unable to find a page to delete'));
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
            ->setRule(AO::getModel('catalogrule/rule'))
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

    public function chooserAction()
    {
        switch ($this->getRequest()->getParam('attribute')) {
            case 'sku':
                $type = 'adminhtml/promo_widget_chooser_sku';
                break;

            case 'categories':
                $type = 'adminhtml/promo_widget_chooser_categories';
                break;
        }
        if (!empty($type)) {
            $block = $this->getLayout()->createBlock($type);
            if ($block) {
                $this->getResponse()->setBody($block->toHtml());
            }
        }
    }

    public function newActionHtmlAction()
    {
        $id = $this->getRequest()->getParam('id');
        $typeArr = explode('|', str_replace('-', '/', $this->getRequest()->getParam('type')));
        $type = $typeArr[0];

        $model = AO::getModel($type)
            ->setId($id)
            ->setType($type)
            ->setRule(AO::getModel('catalogrule/rule'))
            ->setPrefix('actions');
        if (!empty($typeArr[1])) {
            $model->setAttribute($typeArr[1]);
        }

        if ($model instanceof Mage_Rule_Model_Action_Abstract) {
            $model->setJsFormObject($this->getRequest()->getParam('form'));
            $html = $model->asHtmlRecursive();
        } else {
            $html = '';
        }
        $this->getResponse()->setBody($html);
    }

    /**
     * Apply all active catalog price rules
     */
    public function applyRulesAction()
    {
        try {
            $resource = AO::getResourceSingleton('catalogrule/rule');
            $resource->applyAllRulesForDateRange();
            AO::app()->removeCache('catalog_rules_dirty');
            AO::getSingleton('adminhtml/session')->addSuccess(
                AO::helper('catalogrule')->__('Rules were successfully applied')
            );
        } catch (Exception $e) {
            AO::getSingleton('adminhtml/session')->addError(
                AO::helper('catalogrule')->__('Unable to apply rules')
            );
            throw $e;
        }

        $this->_redirect('*/*');
    }

    public function addToAlersAction()
    {
        $alerts = AO::getResourceModel('customeralert/type')->getAlertsForCronChecking();
        foreach ($alerts as $val) {
            AO::getSingleton('customeralert/config')->getAlertByType('price_is_changed')
                ->setParamValues($val)
                ->updateForPriceRule();
        }
    }

    protected function _isAllowed()
    {
        return AO::getSingleton('admin/session')->isAllowed('promo/catalog');
    }
}
