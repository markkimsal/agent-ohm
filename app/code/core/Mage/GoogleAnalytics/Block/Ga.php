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
 * @package    Mage_GoogleAnalytics
 * @copyright  Copyright (c) 2008 Irubin Consulting Inc. DBA Varien (http://www.varien.com)
 * @license    http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */

/**
 * Google Analytics block
 *
 * @category   Mage
 * @package    Mage_GoogleAnalytics
 */
class Mage_GoogleAnalytics_Block_Ga extends Mage_Core_Block_Text
{
    /**
     * Retrieve Quote Data HTML
     *
     * @return unknown
     */
    public function getQuoteOrdersHtml()
    {
        $quote = $this->getQuote();
        if (!$quote) {
            return '';
        }

        if ($quote instanceof Mage_Sales_Model_Quote) {
            $quoteId = $quote->getId();
        } else {
            $quoteId = $quote;
        }

        if (!$quoteId) {
            return '';
        }

        $orders = Mage::getResourceModel('sales/order_collection')
            ->addAttributeToFilter('quote_id', $quoteId)
            ->load();

        $html = '';
        foreach ($orders as $order) {
            $html .= $this->setOrder($order)->getOrderHtml();
        }

        return $html;
    }

    /**
     * Retrieve Order Data HTML
     *
     * @return string
     */
    public function getOrderHtml()
    {

        $order = $this->getOrder();
        if (!$order) {
            return '';
        }

        if (!$order instanceof Mage_Sales_Model_Order) {
            $order = Mage::getModel('sales/order')->load($order);
        }

        if (!$order) {
            return '';
        }

        $address = $order->getBillingAddress();

        $html  = '<script type="text/javascript">' . "\n";
        $html .= "//<![CDATA[\n";
        $html .= 'pageTracker._addTrans(';
        $html .= '"' . $order->getIncrementId() . '",';
        $html .= '"' . $order->getAffiliation() . '",';
        $html .= '"' . $order->getGrandTotal() . '",';
        $html .= '"' . $order->getTaxAmount() . '",';
        $html .= '"' . $order->getShippingAmount() . '",';
        $html .= '"' . $address->getCity() . '",';
        $html .= '"' . $address->getRegion() . '",';
        $html .= '"' . $address->getCountry() . '"';
        $html .= ');' . "\n";

        foreach ($order->getAllItems() as $item) {
            if ($item->getParentItemId()) {
                continue;
            }

            $html .= 'pageTracker._addItem(';
            $html .= '"' . $order->getIncrementId() . '",';
            $html .= '"' . $item->getSku() . '",';
            $html .= '"' . $item->getName() . '",';
            $html .= '"' . $item->getCategory() . '",';
            $html .= '"' . $item->getPrice() . '",';
            $html .= '"' . $item->getQtyOrdered() . '"';
            $html .= ');' . "\n";
        }

        $html .= 'pageTracker._trackTrans();' . "\n";
        $html .= '//]]>';
        $html .= '</script>';

        return $html;
    }

    /**
     * Retrieve Google Account Identifier
     *
     * @return string
     */
    public function getAccount()
    {
        if (!$this->hasData('account')) {
            $this->setAccount(Mage::getStoreConfig('google/analytics/account'));
        }
        return $this->getData('account');
    }

    /**
     * Retrieve current page URL
     *
     * @return string
     */
    public function getPageName()
    {
        if (!$this->hasData('page_name')) {
            //$queryStr = '';
            //if ($this->getRequest() && $this->getRequest()->getQuery()) {
            //    $queryStr = '?' . http_build_query($this->getRequest()->getQuery());
            //}
            $this->setPageName(Mage::getSingleton('core/url')->escape($_SERVER['REQUEST_URI']));
        }
        return $this->getData('page_name');
    }

    /**
     * Prepare and return block's html output
     *
     * @return string
     */
    protected function _toHtml()
    {
        if (!Mage::getStoreConfigFlag('google/analytics/active')) {
            return '';
        }

        $this->addText('
<!-- BEGIN GOOGLE ANALYTICS CODE -->
<script type="text/javascript">
//<![CDATA[
var gaJsHost = (("https:" == document.location.protocol) ? "https://ssl." : "http://www.");
document.write(unescape("%3Cscript src=\'" + gaJsHost + "google-analytics.com/ga.js\' type=\'text/javascript\'%3E%3C/script%3E"));
//]]>
</script>
<script type="text/javascript">
//<![CDATA[
var pageTracker = _gat._getTracker("' . $this->getAccount() . '");
pageTracker._trackPageview("'.$this->getPageName().'");
//]]>
</script>
<!-- END GOOGLE ANALYTICS CODE -->
        ');

        $this->addText($this->getQuoteOrdersHtml());

        if ($this->getGoogleCheckout()) {
            $protocol = Mage::app()->getStore()->isCurrentlySecure() ? 'https' : 'http';
            $this->addText('<script src="'.$protocol.'://checkout.google.com/files/digital/ga_post.js" type="text/javascript"></script>');
        }

        return parent::_toHtml();
    }
}
