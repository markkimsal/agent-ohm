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
 * Sales module base helper
 *
 * @author      Magento Core Team <core@magentocommerce.com>
 */
class Mage_Sales_Helper_Data extends Mage_Core_Helper_Data
{
    const MAXIMUM_AVAILABLE_NUMBER = 99999999;

    public function checkQuoteAmount(Mage_Sales_Model_Quote $quote, $amount)
    {
        if (!$quote->getHasError() && ($amount>=self::MAXIMUM_AVAILABLE_NUMBER)) {
            $quote->setHasError(true);
            $quote->addMessage(
                $this->__('Some items have quantities exceeding allowed quantities. Please select a lower quantity to checkout.')
            );
        }
        return $this;
    }

    public function canSendNewOrderConfirmationEmail($store = null)
    {
        return Mage::getStoreConfigFlag(Mage_Sales_Model_Order::XML_PATH_EMAIL_ENABLED, $store);
    }

    public function canSendNewOrderEmail($store = null)
    {
        return $this->canSendNewOrderConfirmationEmail($store);
    }

    public function canSendOrderCommentEmail($store = null)
    {
        return Mage::getStoreConfigFlag(Mage_Sales_Model_Order::XML_PATH_UPDATE_EMAIL_ENABLED, $store);
    }

    public function canSendNewShipmentEmail($store = null)
    {
        return Mage::getStoreConfigFlag(Mage_Sales_Model_Order_Shipment::XML_PATH_EMAIL_ENABLED, $store);
    }

    public function canSendShipmentCommentEmail($store = null)
    {
        return Mage::getStoreConfigFlag(Mage_Sales_Model_Order_Shipment::XML_PATH_UPDATE_EMAIL_ENABLED, $store);
    }

    public function canSendNewInvoiceEmail($store = null)
    {
        return Mage::getStoreConfigFlag(Mage_Sales_Model_Order_Invoice::XML_PATH_EMAIL_ENABLED, $store);
    }

    public function canSendInvoiceCommentEmail($store = null)
    {
        return Mage::getStoreConfigFlag(Mage_Sales_Model_Order_Invoice::XML_PATH_UPDATE_EMAIL_ENABLED, $store);
    }

    public function canSendNewCreditmemoEmail($store = null)
    {
        return Mage::getStoreConfigFlag(Mage_Sales_Model_Order_Creditmemo::XML_PATH_EMAIL_ENABLED, $store);
    }

    public function canSendCreditmemoCommentEmail($store = null)
    {
        return Mage::getStoreConfigFlag(Mage_Sales_Model_Order_Creditmemo::XML_PATH_UPDATE_EMAIL_ENABLED, $store);
    }

}