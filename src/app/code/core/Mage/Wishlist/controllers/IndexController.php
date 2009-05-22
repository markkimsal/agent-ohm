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
 * @package    Mage_Wishlist
 * @copyright  Copyright (c) 2008 Irubin Consulting Inc. DBA Varien (http://www.varien.com)
 * @license    http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */


/**
 * Wishlist front controller
 *
 * @category   Mage
 * @package    Mage_Wishlist
 * @author     Magento Core Team <core@magentocommerce.com>
 */
class Mage_Wishlist_IndexController extends Mage_Core_Controller_Front_Action
{
    public function preDispatch()
    {
        parent::preDispatch();

        if (!AO::getSingleton('customer/session')->authenticate($this)) {
            $this->setFlag('', 'no-dispatch', true);
            if(!AO::getSingleton('customer/session')->getBeforeWishlistUrl()) {
                AO::getSingleton('customer/session')->setBeforeWishlistUrl($this->_getRefererUrl());
            }
        }
        if (!AO::getStoreConfigFlag('wishlist/general/active')) {
            $this->norouteAction();
            return;
        }
    }

    /**
     * Retrieve wishlist object
     *
     * @return Mage_Wishlist_Model_Wishlist
     */
    protected function _getWishlist()
    {
        try {
            $wishlist = AO::getModel('wishlist/wishlist')
                ->loadByCustomer(AO::getSingleton('customer/session')->getCustomer(), true);
            AO::register('wishlist', $wishlist);
        }
        catch (Exception $e) {
            AO::getSingleton('wishlist/session')->addError($this->__('Cannot create wishlist'));
            return false;
        }
        return $wishlist;
    }

    /**
     * Display customer wishlist
     */
    public function indexAction()
    {
        $this->_getWishlist();
        $this->loadLayout();
        $this->_initLayoutMessages('customer/session');
        $this->_initLayoutMessages('catalog/session');

        if ($block = $this->getLayout()->getBlock('customer.wishlist')) {
            $block->setRefererUrl($this->_getRefererUrl());
        }
        $this->_initLayoutMessages('customer/session');
        $this->_initLayoutMessages('checkout/session');
        $this->renderLayout();
    }

    /**
     * Adding new item
     */
    public function addAction()
    {
        $session = AO::getSingleton('customer/session');
        $wishlist = $this->_getWishlist();
        if (!$wishlist) {
            $this->_redirect('*/');
            return;
        }

        $productId = (int) $this->getRequest()->getParam('product');
        if (!$productId) {
            $this->_redirect('*/');
            return;
        }

        $product = AO::getModel('catalog/product')->load($productId);
        if (!$product->getId() || !$product->isVisibleInCatalog()) {
            $session->addError($this->__('Cannot specify product'));
            $this->_redirect('*/');
            return;
        }

        try {
            $wishlist->addNewItem($product->getId());
            AO::dispatchEvent('wishlist_add_product', array('wishlist'=>$wishlist, 'product'=>$product));

            if ($referer = $session->getBeforeWishlistUrl()) {
                $session->setBeforeWishlistUrl(null);
            }
            else {
                $referer = $this->_getRefererUrl();
            }
            $message = $this->__('%1$s was successfully added to your wishlist. Click <a href="%2$s">here</a> to continue shopping', $product->getName(), $referer);
            $session->addSuccess($message);
        }
        catch (Mage_Core_Exception $e) {
            $session->addError($this->__('There was an error while adding item to wishlist: %s', $e->getMessage()));
        }
        catch (Exception $e) {
            $session->addError($this->__('There was an error while adding item to wishlist.'));
        }
        $this->_redirect('*');
    }

    /**
     * Update wishlist item comments
     */
    public function updateAction()
    {
        if (!$this->_validateFormKey()) {
            return $this->_redirect('*/*/');
        }
        $post = $this->getRequest()->getPost();
        if($post && isset($post['description']) && is_array($post['description'])) {
            $wishlist = $this->_getWishlist();

            foreach ($post['description'] as $itemId => $description) {
                $item = AO::getModel('wishlist/item')->load($itemId);
                $description = (string) $description;
                if(!strlen($description) || $item->getWishlistId()!=$wishlist->getId()) {
                    continue;
                }
                try {
                    $item->setDescription($description)
                        ->save();
                }
                catch (Exception $e) {
                    AO::getSingleton('customer/session')->addError(
                        $this->__('Can\'t save description %s', AO::helper('core')->htmlEscape($description))
                    );
                }
            }
        }
        $this->_redirect('*');
    }

    /**
     * Remove item
     */
    public function removeAction()
    {
        $wishlist = $this->_getWishlist();
        $id = (int) $this->getRequest()->getParam('item');
        $item = AO::getModel('wishlist/item')->load($id);

        if($item->getWishlistId()==$wishlist->getId()) {
            try {
                $item->delete();
            }
            catch (Mage_Core_Exception $e) {
                AO::getSingleton('customer/session')->addError(
                    $this->__('There was an error while deleting item from wishlist: %s', $e->getMessage())
                );
            }
            catch(Exception $e) {
                AO::getSingleton('customer/session')->addError(
                    $this->__('There was an error while deleting item from wishlist.')
                );
            }
        }
        $this->_redirectReferer(AO::getUrl('*/*'));
    }

    /**
     * Add wishlist item to shopping cart
     */
    public function cartAction()
    {
        $wishlist   = $this->_getWishlist();
        $id         = (int) $this->getRequest()->getParam('item');
        $item       = AO::getModel('wishlist/item')->load($id);

        if($item->getWishlistId()==$wishlist->getId()) {
            try {
                $product = AO::getModel('catalog/product')->load($item->getProductId())->setQty(1);
                $quote = AO::getSingleton('checkout/cart')
                   ->addProduct($product)
                   ->save();
                $item->delete();
            }
            catch(Exception $e) {
                AO::getSingleton('checkout/session')->addError($e->getMessage());
                $url = AO::getSingleton('checkout/session')->getRedirectUrl(true);
                if ($url) {
                    $url = AO::getModel('core/url')->getUrl('catalog/product/view', array(
                        'id'=>$item->getProductId(),
                        'wishlist_next'=>1
                    ));
                    AO::getSingleton('checkout/session')->setSingleWishlistId($item->getId());
                    $this->getResponse()->setRedirect($url);
                }
                else {
                    $this->_redirect('*/*/');
                }
                return;
            }
        }

        if (AO::getStoreConfig('checkout/cart/redirect_to_cart')) {
            $this->_redirect('checkout/cart');
        } else {
            if ($this->getRequest()->getParam(self::PARAM_NAME_BASE64_URL)) {
                $this->getResponse()->setRedirect(
                    AO::helper('core')->urlDecode($this->getRequest()->getParam(self::PARAM_NAME_BASE64_URL))
                );
            } else {
                $this->_redirect('*/*/');
            }
        }
    }

    /**
     * Add all items to shoping cart
     *
     */
    public function allcartAction() {
        $messages           = array();
        $urls               = array();
        $wishlistIds        = array();
        $notSalableNames    = array(); // Out of stock products message

        $wishlist           = $this->_getWishlist();
        $wishlist->getItemCollection()->load();

        foreach ($wishlist->getItemCollection() as $item) {
            try {
                $product = AO::getModel('catalog/product')
                    ->load($item->getProductId())
                    ->setQty(1);
                if ($product->isSalable()) {
                    AO::getSingleton('checkout/cart')->addProduct($product);
                    $item->delete();
                }
                else {
                    $notSalableNames[] = $product->getName();
                }
            } catch(Exception $e) {
                $url = AO::getSingleton('checkout/session')
                    ->getRedirectUrl(true);
                if ($url) {
                    $url = AO::getModel('core/url')
                        ->getUrl('catalog/product/view', array(
                            'id'            => $item->getProductId(),
                            'wishlist_next' => 1
                        ));

                    $urls[]         = $url;
                    $messages[]     = $e->getMessage();
                    $wishlistIds[]  = $item->getId();
                } else {
                    $item->delete();
                }
            }
            AO::getSingleton('checkout/cart')->save();
        }

        if (count($notSalableNames) > 0) {
            AO::getSingleton('checkout/session')
                ->addNotice($this->__('This product(s) is currently out of stock:'));
            array_map(array(AO::getSingleton('checkout/session'), 'addNotice'), $notSalableNames);
        }

        if ($urls) {
            AO::getSingleton('checkout/session')->addError(array_shift($messages));
            $this->getResponse()->setRedirect(array_shift($urls));

            AO::getSingleton('checkout/session')->setWishlistPendingUrls($urls);
            AO::getSingleton('checkout/session')->setWishlistPendingMessages($messages);
            AO::getSingleton('checkout/session')->setWishlistIds($wishlistIds);
        }
        else {
            $this->_redirect('checkout/cart');
        }
    }

    public function shareAction()
    {
        $this->loadLayout();
        $this->_initLayoutMessages('customer/session');
        $this->_initLayoutMessages('wishlist/session');
        $this->renderLayout();
    }

    public function sendAction()
    {
        if (!$this->_validateFormKey()) {
            return $this->_redirect('*/*/');
        }

        $emails = explode(',', $this->getRequest()->getPost('emails'));
        $message= nl2br(htmlspecialchars((string) $this->getRequest()->getPost('message')));
        $error  = false;
        if (empty($emails)) {
            $error = $this->__('Email address can\'t be empty.');
        }
        else {
            foreach ($emails as $index => $email) {
                $email = trim($email);
                if (!Zend_Validate::is($email, 'EmailAddress')) {
                    $error = $this->__('You input not valid email address.');
                    break;
                }
                $emails[$index] = $email;
            }
        }
        if ($error) {
            AO::getSingleton('wishlist/session')->addError($error);
            AO::getSingleton('wishlist/session')->setSharingForm($this->getRequest()->getPost());
            $this->_redirect('*/*/share');
            return;
        }

        $translate = AO::getSingleton('core/translate');
        /* @var $translate Mage_Core_Model_Translate */
        $translate->setTranslateInline(false);

        try {
            $customer = AO::getSingleton('customer/session')->getCustomer();
            $wishlist = $this->_getWishlist();

            /*if share rss added rss feed to email template*/
            if ($this->getRequest()->getParam('rss_url')) {
                $rss_url = $this->getLayout()->createBlock('wishlist/share_email_rss')->toHtml();
                $message .=$rss_url;
            }
            $wishlistBlock = $this->getLayout()->createBlock('wishlist/share_email_items')->toHtml();

            $emails = array_unique($emails);
            $emailModel = AO::getModel('core/email_template');

            foreach($emails as $email) {
                $emailModel->sendTransactional(
                    AO::getStoreConfig('wishlist/email/email_template'),
                    AO::getStoreConfig('wishlist/email/email_identity'),
                    $email,
                    null,
                    array(
                        'customer'      => $customer,
                        'salable'       => $wishlist->isSalable() ? 'yes' : '',
                        'items'         => &$wishlistBlock,
                        'addAllLink'    => AO::getUrl('*/shared/allcart',array('code'=>$wishlist->getSharingCode())),
                        'viewOnSiteLink'=> AO::getUrl('*/shared/index',array('code'=>$wishlist->getSharingCode())),
                        'message'       => $message
                    ));
            }

            $wishlist->setShared(1);
            $wishlist->save();

            $translate->setTranslateInline(true);

            AO::dispatchEvent('wishlist_share', array('wishlist'=>$wishlist));
            AO::getSingleton('customer/session')->addSuccess(
                $this->__('Your Wishlist was successfully shared')
            );
            $this->_redirect('*/*');
        }
        catch (Exception $e) {
            $translate->setTranslateInline(true);

            AO::getSingleton('wishlist/session')->addError($e->getMessage());
            AO::getSingleton('wishlist/session')->setSharingForm($this->getRequest()->getPost());
            $this->_redirect('*/*/share');
        }
    }
}
