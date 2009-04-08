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
 * @copyright  Copyright (c) 2008 Irubin Consulting Inc. DBA Varien (http://www.varien.com)
 * @license    http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */

/**
 * Catalog category helper
 *
 * @author      Magento Core Team <core@magentocommerce.com>
 */
class Mage_Catalog_Helper_Product extends Mage_Core_Helper_Url
{
    const XML_PATH_PRODUCT_URL_SUFFIX   = 'catalog/seo/product_url_suffix';

    /**
     * Cache for product rewrite suffix
     *
     * @var array
     */
    protected $_productUrlSuffix = array();

    protected $_statuses;

    protected $_priceBlock;

    /**
     * Retrieve product view page url
     *
     * @param   mixed $product
     * @return  string
     */
    public function getProductUrl($product)
    {
        if ($product instanceof Mage_Catalog_Model_Product) {
            return $product->getProductUrl();
        }
        elseif (is_numeric($product)) {
            return Mage::getModel('catalog/product')->load($product)->getProductUrl();
        }
        return false;
    }

    /**
     * Retrieve product price
     *
     * @param   Mage_Catalog_Model_Product $product
     * @return  float
     */
    public function getPrice($product)
    {
        return $product->getPrice();
    }

    /**
     * Retrieve product final price
     *
     * @param   Mage_Catalog_Model_Product $product
     * @return  float
     */
    public function getFinalPrice($product)
    {
        return $product->getFinalPrice();
    }

    /**
     * Retrieve base image url
     *
     * @return string
     */
    public function getImageUrl($product)
    {
        $url = false;
        if (!$product->getImage()) {
            $url = Mage::getDesign()->getSkinUrl('images/no_image.jpg');
        }
        elseif ($attribute = $product->getResource()->getAttribute('image')) {
            $url = $attribute->getFrontend()->getUrl($product);
        }
        return $url;
    }

    /**
     * Retrieve small image url
     *
     * @return unknown
     */
    public function getSmallImageUrl($product)
    {
        $url = false;
        if (!$product->getSmallImage()) {
            $url = Mage::getDesign()->getSkinUrl('images/no_image.jpg');
        }
        elseif ($attribute = $product->getResource()->getAttribute('small_image')) {
            $url = $attribute->getFrontend()->getUrl($product);
        }
        return $url;
    }

    /**
     * Retrieve thumbnail image url
     *
     * @return unknown
     */
    public function getThumbnailUrl($product)
    {
        return '';
    }

    public function getEmailToFriendUrl($product)
    {
        $categoryId = null;
        if ($category = Mage::registry('current_category')) {
            $categoryId = $category->getId();
        }
        return $this->_getUrl('sendfriend/product/send', array(
            'id' => $product->getId(),
            'cat_id' => $categoryId
        ));
    }

    public function getStatuses()
    {
        if(is_null($this->_statuses)) {
            $this->_statuses = array();//Mage::getModel('catalog/product_status')->getResourceCollection()->load();
        }

        return $this->_statuses;
    }

    /**
     * Check if a product can be shown
     *
     * @param  Mage_Catalog_Model_Product|int $product
     * @return boolean
     */
    public function canShow($product, $where = 'catalog')
    {
        if (is_int($product)) {
            $product = Mage::getModel('catalog/product')->load($product);
        }

        /* @var $product Mage_Catalog_Model_Product */

        if (!$product->getId()) {
            return false;
        }

        return $product->isVisibleInCatalog() && $product->isVisibleInSiteVisibility();
    }

    /**
     * Retrieve product rewrite sufix for store
     *
     * @param int $storeId
     * @return string
     */
    public function getProductUrlSuffix($storeId = null)
    {
        if (is_null($storeId)) {
            $storeId = Mage::app()->getStore()->getId();
        }

        if (!isset($this->_productUrlSuffix[$storeId])) {
            $this->_productUrlSuffix[$storeId] = Mage::getStoreConfig(self::XML_PATH_PRODUCT_URL_SUFFIX, $storeId);
        }
        return $this->_productUrlSuffix[$storeId];
    }
}