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
 * @package    Mage_Review
 * @copyright  Copyright (c) 2008 Irubin Consulting Inc. DBA Varien (http://www.varien.com)
 * @license    http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */

/**
 * Review controller
 *
 * @category   Mage
 * @package    Mage_Review
 * @author     Magento Core Team <core@magentocommerce.com>
 */
class Mage_Review_ProductController extends Mage_Core_Controller_Front_Action
{

    public function preDispatch()
    {
        parent::preDispatch();

        $allowGuest = AO::helper('review')->getIsGuestAllowToWrite();
        if (!$this->getRequest()->isDispatched()) {
            return;
        }

        $action = $this->getRequest()->getActionName();
        if (!$allowGuest && $action == 'post' && $this->getRequest()->isPost()) {
            if (!AO::getSingleton('customer/session')->isLoggedIn()) {
                $this->setFlag('', self::FLAG_NO_DISPATCH, true);
                AO::getSingleton('customer/session')->setBeforeAuthUrl(AO::getUrl('*/*/*', array('_current' => true)));
                AO::getSingleton('review/session')->setFormData($this->getRequest()->getPost())
                    ->setRedirectUrl($this->_getRefererUrl());
                $this->_redirectUrl(AO::helper('customer')->getLoginUrl());
            }
        }

        return $this;
    }
    /**
     * Initialize and check product
     *
     * @return Mage_Catalog_Model_Product
     */
	protected function _initProduct()
    {
        AO::dispatchEvent('review_controller_product_init_before', array('controller_action'=>$this));
        $categoryId = (int) $this->getRequest()->getParam('category', false);
        $productId  = (int) $this->getRequest()->getParam('id');

        if (!$productId) {
            return false;
        }

        $product = AO::getModel('catalog/product')
            ->setStoreId(AO::app()->getStore()->getId())
            ->load($productId);
        /* @var $product Mage_Catalog_Model_Product */
        if (!$product->getId() || !$product->isVisibleInCatalog() || !$product->isVisibleInSiteVisibility()) {
            return false;
        }
        if ($categoryId) {
            $category = AO::getModel('catalog/category')->load($categoryId);
            AO::register('current_category', $category);
        }
        AO::register('current_product', $product);
        AO::register('product', $product);

        try {
            AO::dispatchEvent('review_controller_product_init', array('product'=>$product));
            AO::dispatchEvent('review_controller_product_init_after', array('product'=>$product, 'controller_action' => $this));
        } catch (Mage_Core_Exception $e) {
            AO::logException($e);
            return false;
        }

        return $product;
    }

    public function postAction()
    {
        if ($data = AO::getSingleton('review/session')->getFormData(true)) {
            $rating = array();
            if (isset($data['ratings']) && is_array($data['ratings'])) {
                $rating = $data['ratings'];
            }
        } else {
            $data   = $this->getRequest()->getPost();
            $rating = $this->getRequest()->getParam('ratings', array());
        }

        if (($product = $this->_initProduct()) && !empty($data)) {
            $session    = AO::getSingleton('core/session');
            /* @var $session Mage_Core_Model_Session */
            $review     = AO::getModel('review/review')->setData($data);
            /* @var $review Mage_Review_Model_Review */

            $validate = $review->validate();
            if ($validate === true) {
                try {
                    $review->setEntityId(Mage_Review_Model_Review::ENTITY_PRODUCT)
                        ->setEntityPkValue($product->getId())
                        ->setStatusId(Mage_Review_Model_Review::STATUS_PENDING)
                        ->setCustomerId(AO::getSingleton('customer/session')->getCustomerId())
                        ->setStoreId(AO::app()->getStore()->getId())
                        ->setStores(array(AO::app()->getStore()->getId()))
                        ->save();

                    foreach ($rating as $ratingId => $optionId) {
                        AO::getModel('rating/rating')
                    	   ->setRatingId($ratingId)
                    	   ->setReviewId($review->getId())
                    	   ->setCustomerId(AO::getSingleton('customer/session')->getCustomerId())
                    	   ->addOptionVote($optionId, $product->getId());
                    }

                    $review->aggregate();
                    $session->addSuccess($this->__('Your review has been accepted for moderation'));
                }
                catch (Exception $e) {
                    $session->setFormData($data);
                    $session->addError($this->__('Unable to post review. Please, try again later.'));
                }
            }
            else {
                $session->setFormData($data);
                if (is_array($validate)) {
                    foreach ($validate as $errorMessage) {
                        $session->addError($errorMessage);
                    }
                }
                else {
                    $session->addError($this->__('Unable to post review. Please, try again later.'));
                }
            }
        }

        if ($redirectUrl = AO::getSingleton('review/session')->getRedirectUrl(true)) {
            $this->_redirectUrl($redirectUrl);
            return;
        }
        $this->_redirectReferer();
    }

    public function listAction()
    {
        if ($product = $this->_initProduct()) {
            AO::register('productId', $product->getId());
            AO::getModel('catalog/design')->applyDesign($product, Mage_Catalog_Model_Design::APPLY_FOR_PRODUCT);
            $this->_initProductLayout($product);

            // update breadcrumbs
            if ($breadcrumbsBlock = $this->getLayout()->getBlock('breadcrumbs')) {
                $breadcrumbsBlock->addCrumb('product', array(
                    'label'    => $product->getName(),
                    'link'     => $product->getProductUrl(),
                    'readonly' => true,
                ));
                $breadcrumbsBlock->addCrumb('reviews', array('label' => AO::helper('review')->__('Product Reviews')));
            }

//            $this->renderLayout();
        } elseif ($this->getRequest()->isDispatched()) {
            $this->_forward('noRoute');
        }
    }

    public function viewAction()
    {
        $this->loadLayout();
        $this->_initLayoutMessages('review/session');
        $this->renderLayout();
    }

    protected function _initProductLayout($product)
    {
        $update = $this->getLayout()->getUpdate();

        $update->addHandle('default');
        $this->addActionLayoutHandles();

        $update->addHandle('PRODUCT_TYPE_'.$product->getTypeId());
        $this->loadLayoutUpdates();

        $update->addUpdate($product->getCustomLayoutUpdate());
        $this->generateLayoutXml()->generateLayoutBlocks();
    }
}
