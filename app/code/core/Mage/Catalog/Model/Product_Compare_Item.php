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
 * @copyright  Copyright (c) 2009 Irubin Consulting Inc. DBA Varien (http://www.varien.com)
 * @license    http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */


/**
 * Catalog compare item model
 *
 * @category   Mage
 * @package    Mage_Catalog
 * @author     Magento Core Team <core@magentocommerce.com>
 */
class Mage_Catalog_Model_Product_Compare_Item extends Mage_Core_Model_Abstract
{
    /**
     * Initialize resourse model
     *
     */
    protected function _construct()
    {
        $this->_init('catalog/product_compare_item');
    }

    /**
     * Add customer data from customer object
     *
     * @param Mage_Customer_Model_Customer $customer
     * @return Mage_Catalog_Model_Product_Compare_Item
     */
    public function addCustomerData(Mage_Customer_Model_Customer $customer)
    {
        $this->setCustomerId($customer->getId());
        $this->setVisitorId(0);
        return $this;
    }

    /**
     * Set visitor
     *
     * @param int $visitorId
     * @return Mage_Catalog_Model_Product_Compare_Item
     */
    public function addVisitorId($visitorId)
    {
        $this->setVisitorId($visitorId);
        return $this;
    }

    /**
     * Load compare item by product
     *
     * @param mixed $product
     * @return Mage_Catalog_Model_Product_Compare_Item
     */
    public function loadByProduct($product)
    {
        $this->_getResource()->loadByProduct($this, $product);
        return $this;
    }

    /**
     * Set product data
     *
     * @param mixed $product
     * @return Mage_Catalog_Model_Product_Compare_Item
     */
    public function addProductData($product)
    {
        if ($product instanceof Mage_Catalog_Model_Product) {
            $this->setProductId($product->getId());
        }
        elseif(intval($product)) {
            $this->setProductId(intval($product));
        }

        return $this;
    }

    /**
     * Retrieve data for save
     *
     * @return array
     */
    public function getDataForSave()
    {
        $data = array();
        $data['customer_id'] = $this->getCustomerId();
        $data['visitor_id']  = $this->getVisitorId();
        $data['product_id']  = $this->getProductId();

        return $data;
    }

    /**
     * Customer login bind process
     *
     * @return Mage_Catalog_Model_Product_Compare_Item
     */
    public function bindCustomerLogin()
    {
        $customer = Mage::getSingleton('customer/session')->getCustomer();
        $visitorItemCollection = Mage::getResourceModel('catalog/product_compare_item_collection')
            ->setObject('catalog/product_compare_item')
            ->setVisitorId(Mage::getSingleton('log/visitor')->getId())
            ->load();

        $customerItemCollection = $this->getResourceCollection()
            ->setCustomerId($customer->getId())
            ->useProductItem(true)
            ->load();

        $customerProductIds = $customerItemCollection->getProductIds();

        foreach ($visitorItemCollection as $item) {
            if (in_array($item->getProductId(), $customerProductIds)) {
                $item->delete();
            }
            else {
                $item->setCustomerId($customer->getId())
                    ->setVisitorId(0)
                    ->save();
            }
        }

        Mage::helper('catalog/product_compare')->calculate();
        return $this;
    }

    /**
     * Clean compare items
     *
     * @return Mage_Catalog_Model_Product_Compare_Item
     */
    public function clean()
    {
        $this->_getResource()->clean($this);
        return $this;
    }
}
