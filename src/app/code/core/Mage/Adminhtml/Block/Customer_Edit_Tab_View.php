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
 * Customer account form block
 *
 * @category   Mage
 * @package    Mage_Adminhtml
 * @author      Magento Core Team <core@magentocommerce.com>
 */
class Mage_Adminhtml_Block_Customer_Edit_Tab_View extends Mage_Adminhtml_Block_Template
{

    protected $_customer;

    protected $_customerLog;

    public function __construct()
    {
        parent::__construct();
        $this->setTemplate('customer/tab/view.phtml');
    }

    protected function _prepareLayout()
    {
        $customer = AO::registry('current_customer');

        $this->setChild('sales', $this->getLayout()->createBlock('adminhtml/customer_edit_tab_view_sales'));

        $accordion = $this->getLayout()->createBlock('adminhtml/widget_accordion')
            ->setId('customerViewAccordion');
            //->setShowOnlyOne(0)

        /* @var $accordion Mage_Adminhtml_Block_Widget_Accordion */
        $accordion->addItem('lastOrders', array(
            'title'       => AO::helper('customer')->__('Recent Orders'),
            'ajax'        => true,
            'content_url' => $this->getUrl('*/*/lastOrders', array('_current' => true)),
        ));

        // add shopping cart block of each website
        foreach (AO::registry('current_customer')->getSharedWebsiteIds() as $websiteId) {
            $website = AO::app()->getWebsite($websiteId);

            // count cart items
            $cartItemsCount = AO::getModel('sales/quote')
                ->setWebsite($website)->loadByCustomer($customer)
                ->getItemsCollection(false)->getSize();
            // prepare title for cart
            $title = AO::helper('customer')->__('Shopping Cart - %d item(s)', $cartItemsCount);
            if (count($customer->getSharedWebsiteIds()) > 1) {
                $title = AO::helper('customer')->__('Shopping Cart of %1$s - %2$d item(s)',
                    $website->getName(), $cartItemsCount
                );
            }

            // add cart ajax accordion
            $accordion->addItem('shopingCart' . $websiteId, array(
                'title'   => $title,
                'ajax'    => true,
                'content_url' => $this->getUrl('*/*/viewCart', array('_current' => true, 'website_id' => $websiteId)),
            ));
        }

        // count wishlist items
        $wishlistCount = AO::getModel('wishlist/wishlist')->loadByCustomer($customer)
            ->getProductCollection()
            ->addStoreData()
            ->getSize();
        // add wishlist ajax accordion
        $accordion->addItem('wishlist', array(
            'title' => AO::helper('customer')->__('Wishlist - %d item(s)', $wishlistCount),
            'ajax'  => true,
            'content_url' => $this->getUrl('*/*/viewWishlist', array('_current' => true)),
        ));

        $this->setChild('accordion', $accordion);
        return parent::_prepareLayout();
    }

    public function getCustomer()
    {
        if (!$this->_customer) {
            $this->_customer = AO::registry('current_customer');
        }
        return $this->_customer;
    }

    public function getGroupName()
    {
        if ($groupId = $this->getCustomer()->getGroupId()) {
            return AO::getModel('customer/group')
                ->load($groupId)
                ->getCustomerGroupCode();
        }
    }

    public function getCustomerLog()
    {
        if (!$this->_customerLog) {
            $this->_customerLog = AO::getModel('log/customer')
                ->load($this->getCustomer()->getId());

        }
        return $this->_customerLog;
    }

    public function getCreateDate()
    {
        $date = AO::app()->getLocale()->date($this->getCustomer()->getCreatedAtTimestamp());
        return $this->formatDate($date, Mage_Core_Model_Locale::FORMAT_TYPE_MEDIUM, true);
    }

    public function getStoreCreateDate()
    {
        $date = AO::app()->getLocale()->storeDate(
            $this->getCustomer()->getStoreId(),
            $this->getCustomer()->getCreatedAtTimestamp(),
            true
        );
        return $this->formatDate($date, Mage_Core_Model_Locale::FORMAT_TYPE_MEDIUM, true);
    }

    public function getStoreCreateDateTimezone()
    {
        return AO::app()->getStore($this->getCustomer()->getStoreId())
            ->getConfig(Mage_Core_Model_Locale::XML_PATH_DEFAULT_TIMEZONE);
    }

    public function getLastLoginDate()
    {
        if ($date = $this->getCustomerLog()->getLoginAtTimestamp()) {
            $date = AO::app()->getLocale()->date($date);
            return $this->formatDate($date, Mage_Core_Model_Locale::FORMAT_TYPE_MEDIUM, true);
        }
        return AO::helper('customer')->__('Never');
    }

    public function getStoreLastLoginDate()
    {
        if ($date = $this->getCustomerLog()->getLoginAtTimestamp()) {
            $date = AO::app()->getLocale()->storeDate(
                $this->getCustomer()->getStoreId(),
                $date,
                true
            );
            return $this->formatDate($date, Mage_Core_Model_Locale::FORMAT_TYPE_MEDIUM, true);
        }
        return AO::helper('customer')->__('Never');
    }

    public function getStoreLastLoginDateTimezone()
    {
        return AO::app()->getStore($this->getCustomer()->getStoreId())
            ->getConfig(Mage_Core_Model_Locale::XML_PATH_DEFAULT_TIMEZONE);
    }

    public function getCurrentStatus()
    {
        $log = $this->getCustomerLog();
        if ($log->getLogoutAt() ||
            strtotime(now())-strtotime($log->getLastVisitAt())>Mage_Log_Model_Visitor::getOnlineMinutesInterval()*60) {
            return AO::helper('customer')->__('Offline');
        }
        return AO::helper('customer')->__('Online');
    }

    public function getIsConfirmedStatus()
    {
        $this->getCustomer();
        if (!$this->_customer->getConfirmation()) {
            return AO::helper('customer')->__('Confirmed');
        }
        if ($this->_customer->isConfirmationRequired()) {
            return AO::helper('customer')->__('Not confirmed, cannot login');
        }
        return AO::helper('customer')->__('Not confirmed, can login');
    }

    public function getCreatedInStore()
    {
        return AO::app()->getStore($this->getCustomer()->getStoreId())->getName();
    }

    public function getStoreId()
    {
        return $this->getCustomer()->getStoreId();
    }

    public function getBillingAddressHtml()
    {
        $html = '';
        if ($address = $this->getCustomer()->getPrimaryBillingAddress()) {
            $html = $address->format('html');
        }
        else {
            $html = AO::helper('customer')->__("Customer doesn't have primary billing address");
        }
        return $html;
    }

    public function getAccordionHtml()
    {
        return $this->getChildHtml('accordion');
    }

    public function getSalesHtml()
    {
        return $this->getChildHtml('sales');
    }

}
