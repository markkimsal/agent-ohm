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
 * @package    Mage_Sendfriend
 * @copyright  Copyright (c) 2008 Irubin Consulting Inc. DBA Varien (http://www.varien.com)
 * @license    http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */

class Mage_Sendfriend_ProductController extends Mage_Core_Controller_Front_Action
{
    /**
     * Initialize product instance
     *
     * @return Mage_Catalog_Model_Product
     */
    protected function _initProduct()
    {
        $productId  = (int) $this->getRequest()->getParam('id');
        if (!$productId) {
            return false;
        }
        $product = AO::getModel('catalog/product')
            ->load($productId);
        if (!$product->getId()) {
            return false;
        }
        AO::register('product', $product);
        return $product;
    }

    /**
     * Initialize send friend model
     *
     * @return Mage_Sendfriend_Model_Sendfriend
     */
    protected function _initSendToFriendModel(){
        $sendToFriendModel = AO::getModel('sendfriend/sendfriend');
        AO::register('send_to_friend_model', $sendToFriendModel);
        return $sendToFriendModel;
    }

    public function sendAction(){
        $product = $this->_initProduct();
        $this->_initSendToFriendModel();

        if (!$product || !$product->isVisibleInCatalog()) {
            $this->_forward('noRoute');
            return;
        }

        $productHelper = AO::helper('catalog/product');
        $sendToFriendModel = AO::registry('send_to_friend_model');

        /**
         * check if user is allowed to send product to a friend
         */
        if (!$sendToFriendModel->canEmailToFriend()) {
            AO::getSingleton('catalog/session')->addError(
                $this->__('You cannot email this product to a friend')
            );
            $this->_redirectReferer($product->getProductUrl());
            return;
        }

        $maxSendsToFriend = $sendToFriendModel->getMaxSendsToFriend();
        if ($maxSendsToFriend){
            AO::getSingleton('catalog/session')->addNotice(
                $this->__('You cannot send more than %d times in an hour', $maxSendsToFriend)
            );
        }

        $this->loadLayout();
        $this->_initLayoutMessages('catalog/session');
        $this->renderLayout();
        AO::dispatchEvent('sendfriend_product', array('product'=>$product));
    }

    public function sendmailAction()
    {
        $product = $this->_initProduct();
        $sendToFriendModel = $this->_initSendToFriendModel();
        $data = $this->getRequest()->getPost();

        if (!$product || !$product->isVisibleInCatalog() || !$data) {
            $this->_forward('noRoute');
            return;
        }

        $categoryId = $this->getRequest()->getParam('cat_id', null);
        if ($categoryId && $category = AO::getModel('catalog/category')->load($categoryId)) {
            AO::register('current_category', $category);
        }

        $sendToFriendModel->setSender($this->getRequest()->getPost('sender'));
        $sendToFriendModel->setRecipients($this->getRequest()->getPost('recipients'));
        $sendToFriendModel->setIp(AO::getSingleton('log/visitor')->getRemoteAddr());
        $sendToFriendModel->setProduct($product);

        try {
            $validateRes = $sendToFriendModel->validate();
            if (true === $validateRes) {
                $sendToFriendModel->send();
                AO::getSingleton('catalog/session')->addSuccess($this->__('Link to a friend was sent.'));
                $this->_redirectSuccess($product->getProductUrl());
                return;
            }
            else {
                AO::getSingleton('catalog/session')->setFormData($data);
                if (is_array($validateRes)) {
                    foreach ($validateRes as $errorMessage) {
                    	AO::getSingleton('catalog/session')->addError($errorMessage);
                    }
                } else {
                    AO::getSingleton('catalog/session')->addError($this->__('Some problems with data.'));
                }
            }
        } catch (Mage_Core_Exception $e) {
            AO::getSingleton('catalog/session')->addError($e->getMessage());
        } catch (Exception $e) {
            AO::getSingleton('catalog/session')
                ->addException($e, $this->__('Some emails was not sent'));
        }

        $this->_redirectError(AO::getURL('*/*/send',array('id'=>$product->getId())));
    }
}