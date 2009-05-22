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
 * Urlrewrites adminhtml controller
 *
 * @category   Mage
 * @package    Mage_Adminhtml
 * @author      Magento Core Team <core@magentocommerce.com>
 */
class Mage_Adminhtml_UrlrewriteController extends Mage_Adminhtml_Controller_Action
{
    /**
     * Instantiate urlrewrite, product and category
     *
     * @return Mage_Adminhtml_UrlrewriteController
     */
    protected function _initRegistry()
    {
        // initialize urlrewrite, product and category models
        AO::register('current_urlrewrite', AO::getModel('core/url_rewrite')
            ->load($this->getRequest()->getParam('id', 0))
        );
        $productId  = $this->getRequest()->getParam('product', 0);
        $categoryId = $this->getRequest()->getParam('category', 0);
        if (AO::registry('current_urlrewrite')->getId()) {
            $productId  = AO::registry('current_urlrewrite')->getProductId();
            $categoryId = AO::registry('current_urlrewrite')->getCategoryId();
        }

        AO::register('current_product', AO::getModel('catalog/product')->load($productId));
        AO::register('current_category', AO::getModel('catalog/category')->load($categoryId));

        return $this;
    }

    /**
     * Show urlrewrites index page
     *
     */
    public function indexAction()
    {
        $this->loadLayout();
        $this->_setActiveMenu('catalog/urlrewrite');
        $this->_initRegistry();
        $this->_addContent(
            $this->getLayout()->createBlock('adminhtml/urlrewrite')
        );
        $this->renderLayout();
    }

    /**
     * Show urlrewrite edit/create page
     *
     */
    public function editAction()
    {
        $this->loadLayout();
        $this->_setActiveMenu('catalog/urlrewrite');
        $this->_initRegistry();
        $this->_addContent($this->getLayout()->createBlock('adminhtml/urlrewrite_edit'));
        $this->getLayout()->getBlock('head')->setCanLoadExtJs(true);
        $this->renderLayout();
    }

    /**
     * Ajax products grid action
     *
     */
    public function productGridAction()
    {
        $this->getResponse()->setBody($this->getLayout()->createBlock('adminhtml/urlrewrite_product_grid')->toHtml());
    }

    /**
     * Ajax categories tree loader action
     *
     */
    public function categoriesJsonAction()
    {
        $id = $this->getRequest()->getParam('id', null);
        $this->getResponse()->setBody(AO::getBlockSingleton('adminhtml/urlrewrite_category_tree')
            ->getTreeArray($id, true, 1)
        );
    }

    /**
     * Urlrewrite save action
     *
     */
    public function saveAction()
    {
        $this->_initRegistry();

        if ($data = $this->getRequest()->getPost()) {
            try {
                // set basic urlrewrite data
                $model = AO::registry('current_urlrewrite');

                $model->setIdPath($this->getRequest()->getParam('id_path'))
                    ->setTargetPath($this->getRequest()->getParam('target_path'))
                    ->setOptions($this->getRequest()->getParam('options'))
                    ->setDescription($this->getRequest()->getParam('description'))
                    ->setRequestPath($this->getRequest()->getParam('request_path'))
                ;
                if (!$model->getId()) {
                    $model->setIsSystem(0);
                }
                if (!$model->getIsSystem()) {
                    $model->setStoreId($this->getRequest()->getParam('store_id', 0));
                }

                // override urlrewrite data, basing on current registry combination
                $category = AO::registry('current_category')->getId() ? AO::registry('current_category') : null;
                if ($category) {
                    $model->setCategoryId($category->getId());
                }
                $product  = AO::registry('current_product')->getId() ? AO::registry('current_product') : null;
                if ($product) {
                    $model->setProductId($product->getId());
                }
                if ($product || $category) {
                    $catalogUrlModel = AO::getSingleton('catalog/url');
                    $model->setIdPath($catalogUrlModel->generatePath('id', $product, $category));
                    $model->setTargetPath($catalogUrlModel->generatePath('target', $product, $category));
                }

                // save and redirect
                $model->save();
                AO::getSingleton('adminhtml/session')->addSuccess(AO::helper('adminhtml')->__(
                    'Urlrewrite has been successfully saved'
                ));
                $this->_redirect('*/*/');
                return;
            }
            catch (Exception $e) {
                AO::getSingleton('adminhtml/session')
                    ->addError($e->getMessage())
                    ->setUrlrewriteData($data)
                ;
                // return intentionally omitted
            }
        }
        $this->_redirectReferer();
    }

    /**
     * Urlrewrite delete action
     *
     */
    public function deleteAction()
    {
        $this->_initRegistry();

        if (AO::registry('current_urlrewrite')->getId()) {
            try {
                AO::registry('current_urlrewrite')->delete();
                AO::getSingleton('adminhtml/session')->addSuccess(AO::helper('adminhtml')->__(
                    'Urlrewrite has been successfully deleted'
                ));
            }
            catch (Exception $e) {
                AO::getSingleton('adminhtml/session')->addError($e->getMessage());
                $this->_redirectReferer();
                return;
            }
        }
        $this->_redirect('*/*/');
    }

    /**
     * Check whether this contoller is allowed in admin permissions
     *
     * @return bool
     */
    protected function _isAllowed()
    {
        return AO::getSingleton('admin/session')->isAllowed('catalog/urlrewrite');
    }
}
