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
 * @package    Mage_Sales
 * @copyright  Copyright (c) 2008 Irubin Consulting Inc. DBA Varien (http://www.varien.com)
 * @license    http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */

/**
 * Sales orders controller
 *
 * @category   Mage
 * @package    Mage_Sales
 * @author      Magento Core Team <core@magentocommerce.com>
 */

class Mage_Sales_OrderController extends Mage_Core_Controller_Front_Action
{

    /**
     * Action predispatch
     *
     * Check customer authentication for some actions
     */
    public function preDispatch()
    {
        parent::preDispatch();
        $action = $this->getRequest()->getActionName();
        $loginUrl = AO::helper('customer')->getLoginUrl();

        if (!AO::getSingleton('customer/session')->authenticate($this, $loginUrl)) {
            $this->setFlag('', self::FLAG_NO_DISPATCH, true);
        }
    }

    /**
     * Customer order history
     */
    public function historyAction()
    {
        $this->loadLayout();
        $this->_initLayoutMessages('catalog/session');

        $this->getLayout()->getBlock('head')->setTitle($this->__('My Orders'));

        if ($block = $this->getLayout()->getBlock('customer.account.link.back')) {
            $block->setRefererUrl($this->_getRefererUrl());
        }
        $this->renderLayout();
    }

    /**
     * Check order view availability
     *
     * @param   Mage_Sales_Model_Order $order
     * @return  bool
     */
    protected function _canViewOrder($order)
    {
        $customerId = AO::getSingleton('customer/session')->getCustomerId();
        $availableStates = AO::getSingleton('sales/order_config')->getVisibleOnFrontStates();
        if ($order->getId() && $order->getCustomerId() && ($order->getCustomerId() == $customerId)
            && in_array($order->getState(), $availableStates, $strict = true)
            ) {
            return true;
        }
        return false;
    }

    protected function _viewAction()
    {
        if (!$this->_loadValidOrder()) {
            return;
        }

        $this->loadLayout();
        $this->_initLayoutMessages('catalog/session');

        if ($navigationBlock = $this->getLayout()->getBlock('customer_account_navigation')) {
            $navigationBlock->setActive('sales/order/history');
        }
        $this->renderLayout();
    }

    /**
     * Try to load valid order by order_id and register it
     *
     * @param int $orderId
     * @return bool
     */
    protected function _loadValidOrder($orderId = null)
    {
        if (null === $orderId) {
            $orderId = (int) $this->getRequest()->getParam('order_id');
        }
        if (!$orderId) {
            $this->_forward('noRoute');
            return false;
        }

        $order = AO::getModel('sales/order')->load($orderId);

        if ($this->_canViewOrder($order)) {
            AO::register('current_order', $order);
            return true;
        }
        else {
            $this->_redirect('*/*/history');
        }
        return false;
    }

    /**
     * Order view page
     */
    public function viewAction()
    {
        $this->_viewAction();
    }

    /**
     * Check osCommerce order view availability
     *
     * @param   array $order
     * @return  bool
     */
    protected function _canViewOscommerceOrder($order)
    {
        $customerId = AO::getSingleton('customer/session')->getCustomerId();
        if (isset($order['osc_magento_id']) && isset($order['magento_customers_id']) && $order['magento_customers_id'] == $customerId) {
            return true;
        }
        return false;
    }

    /**
     * osCommerce Order view page
     */
    public function viewOldAction()
    {

        $orderId = (int) $this->getRequest()->getParam('order_id');
        if (!$orderId) {
            $this->_forward('noRoute');
            return;
        }

        $order = AO::getModel('oscommerce/oscommerce')->loadOrderById($orderId);
        if ($this->_canViewOscommerceOrder($order['order'])) {
            AO::register('current_oscommerce_order', $order);
           $this->loadLayout();
            if ($navigationBlock = $this->getLayout()->getBlock('customer_account_navigation')) {
                $navigationBlock->setActive('sales/order/history');
            }

            $this->renderLayout();
        }
        else {
            $this->_redirect('*/*/history');
        }

    }

    public function invoiceAction()
    {
        $this->_viewAction();
    }

    public function shipmentAction()
    {
        $this->_viewAction();
    }

    public function creditmemoAction()
    {
        $this->_viewAction();
    }

    public function reorderAction()
    {
        if (!$this->_loadValidOrder()) {
            return;
        }
        $order = AO::registry('current_order');

        $cart = AO::getSingleton('checkout/cart');
        $cartTruncated = false;
        /* @var $cart Mage_Checkout_Model_Cart */

        $items = $order->getItemsCollection();
        foreach ($items as $item) {
            try {
                $cart->addOrderItem($item);
            } catch (Mage_Core_Exception $e){
                if (AO::getSingleton('checkout/session')->getUseNotice(true)) {
                    AO::getSingleton('checkout/session')->addNotice($e->getMessage());
                }
                else {
                    AO::getSingleton('checkout/session')->addError($e->getMessage());
                }
                $this->_redirect('*/*/history');
            } catch (Exception $e) {
                AO::getSingleton('checkout/session')->addException($e,
                    AO::helper('checkout')->__('Can not add item to shopping cart')
                );
                $this->_redirect('checkout/cart');
            }
        }

        $cart->save();
        $this->_redirect('checkout/cart');
    }

    public function printAction()
    {
        if (!$this->_loadValidOrder()) {
            return;
        }
        $this->loadLayout('print');
        $this->renderLayout();
    }

    public function printInvoiceAction()
    {
        $invoiceId = (int) $this->getRequest()->getParam('invoice_id');
        if ($invoiceId) {
            $invoice = AO::getModel('sales/order_invoice')->load($invoiceId);
            $order = $invoice->getOrder();
        } else {
            $orderId = (int) $this->getRequest()->getParam('order_id');
            $order = AO::getModel('sales/order')->load($orderId);
        }

        if ($this->_canViewOrder($order)) {
            AO::register('current_order', $order);
            if (isset($invoice)) {
            	AO::register('current_invoice', $invoice);
            }
            $this->loadLayout('print');
            $this->renderLayout();
        } else {
            $this->_redirect('*/*/history');
        }
    }

    public function printShipmentAction()
    {
        $shipmentId = (int) $this->getRequest()->getParam('shipment_id');
        if ($shipmentId) {
            $shipment = AO::getModel('sales/order_shipment')->load($shipmentId);
            $order = $shipment->getOrder();
        } else {
            $orderId = (int) $this->getRequest()->getParam('order_id');
            $order = AO::getModel('sales/order')->load($orderId);
        }
        if ($this->_canViewOrder($order)) {
            AO::register('current_order', $order);
            if (isset($shipment)) {
            	AO::register('current_shipment', $shipment);
            }
            $this->loadLayout('print');
            $this->renderLayout();
        } else {
            $this->_redirect('*/*/history');
        }
    }

    public function printCreditmemoAction()
    {
        $creditmemoId = (int) $this->getRequest()->getParam('creditmemo_id');
        if ($creditmemoId) {
            $creditmemo = AO::getModel('sales/order_creditmemo')->load($creditmemoId);
            $order = $creditmemo->getOrder();
        } else {
            $orderId = (int) $this->getRequest()->getParam('order_id');
            $order = AO::getModel('sales/order')->load($orderId);
        }

        if ($this->_canViewOrder($order)) {
            AO::register('current_order', $order);
            if (isset($creditmemo)) {
            	AO::register('current_creditmemo', $creditmemo);
            }
            $this->loadLayout('print');
            $this->renderLayout();
        } else {
            $this->_redirect('*/*/history');
        }
    }
}