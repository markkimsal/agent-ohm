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
 * Wishlist shared items controllers
 *
 * @category   Mage
 * @package    Mage_Wishlist
 * @author      Magento Core Team <core@magentocommerce.com>
 */
class Mage_Wishlist_SharedController extends Mage_Core_Controller_Front_Action
{

    public function indexAction()
    {
        $code = (string) $this->getRequest()->getParam('code');
        if (empty($code)) {
            $this->_forward('noRoute');
            return;
        }
        $wishlist = AO::getModel('wishlist/wishlist')->loadByCode($code);

        if ($wishlist->getCustomerId() && $wishlist->getCustomerId() == AO::getSingleton('customer/session')->getCustomerId()) {
            $this->_redirectUrl(AO::helper('wishlist')->getListUrl());
            return;
        }

        if(!$wishlist->getId()) {
            $this->_forward('noRoute');
            return;
        } else {
            AO::register('shared_wishlist', $wishlist);
            $this->loadLayout();
            $this->_initLayoutMessages('wishlist/session');
            $this->renderLayout();
        }

    }

    public function allcartAction()
    {
        $code = (string) $this->getRequest()->getParam('code');
        if (empty($code)) {
            $this->_forward('noRoute');
            return;
        }

        $wishlist = AO::getModel('wishlist/wishlist')->loadByCode($code);
        AO::getSingleton('checkout/session')->setSharedWishlist($code);

        if (!$wishlist->getId()) {
            $this->_forward('noRoute');
            return;
        } else {
            $urls = false;
            foreach ($wishlist->getProductCollection() as $item) {
                try {
                    $product = AO::getModel('catalog/product')
                        ->load($item->getProductId());
                    if ($product->isSalable()){
                        AO::getSingleton('checkout/cart')->addProduct($product);
                    }
                }
                catch (Exception $e) {
                    $url = AO::getSingleton('checkout/session')->getRedirectUrl(true);
                    if ($url){
                        $url = AO::getModel('core/url')->getUrl('catalog/product/view', array(
                            'id'=>$item->getProductId(),
                            'wishlist_next'=>1
                        ));

                        $urls[] = $url;
                        $messages[] = $e->getMessage();
                        $wishlistIds[] = $item->getId();
                    }
                }

                AO::getSingleton('checkout/cart')->save();
            }
            if ($urls) {
                AO::getSingleton('checkout/session')->addError(array_shift($messages));
                $this->getResponse()->setRedirect(array_shift($urls));

                AO::getSingleton('checkout/session')->setWishlistPendingUrls($urls);
                AO::getSingleton('checkout/session')->setWishlistPendingMessages($messages);
                AO::getSingleton('checkout/session')->setWishlistIds($wishlistIds);
            } else {
                $this->_redirect('checkout/cart');
            }
        }
    }
}