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
 * @package    Mage_Catalog
 * @copyright  Copyright (c) 2008 Irubin Consulting Inc. DBA Varien (http://www.varien.com)
 * @license    http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */


/**
 * Catalog comapare controller
 *
 * @category   Mage
 * @package    Mage_Catalog
 * @author     Magento Core Team <core@magentocommerce.com>
 */
class Mage_Catalog_Product_CompareController extends Mage_Core_Controller_Front_Action
{
    public function indexAction()
    {
        $items = $this->getRequest()->getParam('items');

        if($this->getRequest()->getParam(self::PARAM_NAME_BASE64_URL)) {
            AO::getSingleton('catalog/session')->setBeforeCompareUrl(
                AO::helper('core')->urlDecode($this->getRequest()->getParam(self::PARAM_NAME_BASE64_URL))
            );
        }

        if ($items) {
            $items = explode(',', $items);
            $list = AO::getSingleton('catalog/product_compare_list');
            $list->addProducts($items);
            $this->_redirect('*/*/*');
            return;
        }

        $this->loadLayout();
        $this->renderLayout();
    }

    /**
     * Add item to compare list
     */
    public function addAction()
    {
        if ($productId = (int) $this->getRequest()->getParam('product')) {
            $product = AO::getModel('catalog/product')
                ->setStoreId(AO::app()->getStore()->getId())
                ->load($productId);

            if ($product->getId()/* && !$product->isSuper()*/) {
                AO::getSingleton('catalog/product_compare_list')->addProduct($product);
                AO::getSingleton('catalog/session')->addSuccess(
                    $this->__('Product %s successfully added to compare list', $product->getName())
                );
                AO::dispatchEvent('catalog_product_compare_add_product', array('product'=>$product));
            }

            AO::helper('catalog/product_compare')->calculate();
        }

        $this->_redirectReferer();
    }

    /**
     * Remove item from compare list
     */
    public function removeAction()
    {
        if ($productId = (int) $this->getRequest()->getParam('product')) {
            $product = AO::getModel('catalog/product')
                ->setStoreId(AO::app()->getStore()->getId())
                ->load($productId);

            if($product->getId()) {
                $item = AO::getModel('catalog/product_compare_item');
                if(AO::getSingleton('customer/session')->isLoggedIn()) {
                    $item->addCustomerData(AO::getSingleton('customer/session')->getCustomer());
                } else {
                    $item->addVisitorId(AO::getSingleton('log/visitor')->getId());
                }

                $item->loadByProduct($product);

                if($item->getId()) {
                    $item->delete();
                    AO::getSingleton('catalog/session')->addSuccess(
                        $this->__('Product %s successfully removed from compare list', $product->getName())
                    );
                    AO::dispatchEvent('catalog_product_compare_remove_product', array('product'=>$item));
                    AO::helper('catalog/product_compare')->calculate();
                }
            }
        }
        $this->_redirectReferer();
    }

    public function clearAction()
    {
        $items = AO::getResourceModel('catalog/product_compare_item_collection')
            //->useProductItem(true)
            //->setStoreId(AO::app()->getStore()->getId())
            ;

        if (AO::getSingleton('customer/session')->isLoggedIn()) {
            $items->setCustomerId(AO::getSingleton('customer/session')->getCustomerId());
        }
        else {
            $items->setVisitorId(AO::getSingleton('log/visitor')->getId());
        }

        $session = AO::getSingleton('catalog/session');
        /* @var $session Mage_Catalog_Model_Session */

        try {
            $items->clear();
            $session->addSuccess($this->__('Compare list successfully cleared'));
            AO::helper('catalog/product_compare')->calculate();
        }
        catch (Mage_Core_Exception $e) {
            $session->addError($e->getMessage());
        }
        catch (Exception $e) {
            $session->addException($e, $this->__('There was an error while cleared compare list'));
        }

        $this->_redirectReferer();
    }
}