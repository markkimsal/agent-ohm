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
 * @package    Mage_GoogleBase
 * @copyright  Copyright (c) 2008 Irubin Consulting Inc. DBA Varien (http://www.varien.com)
 * @license    http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */

/**
 * Google Base Observer
 *
 * @category    Mage
 * @package     Mage_GoogleBase
 * @author      Magento Core Team <core@magentocommerce.com>
 */
class Mage_GoogleBase_Model_Observer
{
    /**
     * Update product item in Google Base
     *
     * @param Varien_Object $observer
     * @return Mage_GoogleBase_Model_Observer
     */
    public function saveProductItem($observer)
    {
        try {
            $product = $observer->getEvent()->getProduct();
            if (AO::getStoreConfigFlag('google/googlebase/observed', $product->getStoreId())) {
                $collection = AO::getResourceModel('googlebase/item_collection')
                    ->addProductFilterId($product->getId())
                    ->load();
                foreach ($collection as $item) {
                    $product = AO::getSingleton('catalog/product')
                        ->setStoreId($item->getStoreId())
                        ->load($item->getProductId());
                    AO::getModel('googlebase/item')->setProduct($product)->updateItem();
                }
            }
        } catch (Exception $e) {
            if (AO::app()->getStore()->isAdmin()) {
                AO::getSingleton('adminhtml/session')->addNotice(
                    AO::helper('googlebase')->__("Cannot update Google Base Item for Store '%s'", AO::app()->getStore($item->getStoreId())->getName())
                );
            } else {
                throw $e;
            }
        }
        return $this;
    }

    /**
     * Delete product item from Google Base
     *
     * @param Varien_Object $observer
     * @return Mage_GoogleBase_Model_Observer
     */
    public function deleteProductItem($observer)
    {
        try {
            $product = $observer->getEvent()->getProduct();
            if (AO::getStoreConfigFlag('google/googlebase/observed', $product->getStoreId())) {
                $collection = AO::getResourceModel('googlebase/item_collection')
                    ->addProductFilterId($product->getId())
                    ->load();
                foreach ($collection as $item) {
                    $item->deleteItem()->delete();
                }
            }
        } catch (Exception $e) {
            if (AO::app()->getStore()->isAdmin()) {
                AO::getSingleton('adminhtml/session')->addNotice(
                    AO::helper('googlebase')->__("Cannot update Google Base Item for Store '%s'", AO::app()->getStore($item->getStoreId())->getName())
                );
            } else {
                throw $e;
            }
        }
        return $this;
    }
}