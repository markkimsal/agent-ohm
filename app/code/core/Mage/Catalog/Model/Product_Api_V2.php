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
 * Catalog product api V2
 *
 * @category   Mage
 * @package    Mage_Catalog
 * @author     Magento Core Team <core@magentocommerce.com>
 */
class Mage_Catalog_Model_Product_Api_V2 extends Mage_Catalog_Model_Product_Api
{

        /**
     * Retrieve list of products with basic info (id, sku, type, set, name)
     *
     * @param array $filters
     * @param string|int $store
     * @return array
     */
    public function items($filters = null, $store = null)
    {
        $collection = Mage::getModel('catalog/product')->getCollection()
            ->setStoreId($this->_getStoreId($store))
            ->addAttributeToSelect('name');

        $preparedFilters = array();
        if (isset($filters->filter)) {
            foreach ($filters->filter as $_filter) {
                $preparedFilters[$_filter->key] = $_filter->value;
            }
        }
        if (isset($filters->complex_filter)) {
            foreach ($filters->complex_filter as $_filter) {
                $_value = $_filter->value;
                $preparedFilters[$_filter->key] = array(
                    $_value->key => $_value->value
                );
            }
        }

        if (!empty($preparedFilters)) {
            try {
                foreach ($preparedFilters as $field => $value) {
                    if (isset($this->_filtersMap[$field])) {
                        $field = $this->_filtersMap[$field];
                    }

                    $collection->addFieldToFilter($field, $value);
                }
            } catch (Mage_Core_Exception $e) {
                $this->_fault('filters_invalid', $e->getMessage());
            }
        }

        $result = array();

        foreach ($collection as $product) {
//            $result[] = $product->getData();
            $result[] = array( // Basic product data
                'product_id' => $product->getId(),
                'sku'        => $product->getSku(),
                'name'       => $product->getName(),
                'set'        => $product->getAttributeSetId(),
                'type'       => $product->getTypeId(),
                'category_ids'       => $product->getCategoryIds()
            );
        }

        return $result;
    }

    /**
     * Retrieve product info
     *
     * @param int|string $productId
     * @param string|int $store
     * @param stdClass $attributes
     * @return array
     */
    public function info($productId, $store = null, $attributes = null)
    {
        $product = $this->_getProduct($productId, $store);

        if (!$product->getId()) {
            $this->_fault('not_exists');
        }

        $result = array( // Basic product data
            'product_id' => $product->getId(),
            'sku'        => $product->getSku(),
            'set'        => $product->getAttributeSetId(),
            'type'       => $product->getTypeId(),
            'categories' => $product->getCategoryIds(),
            'websites'   => $product->getWebsiteIds()
        );

        $allAttributes = array();
        if (isset($attributes['attributes'])) {
            $allAttributes += array_merge($allAttributes, $attributes['attributes']);
        }

        $_additionalAttributeCodes = array();
        if (isset($attributes['additional_attributes'])) {
            foreach ($attributes['additional_attributes'] as $k => $_attributeCode) {
                $allAttributes[] = $_attributeCode;
                $_additionalAttributeCodes[] = $_attributeCode;
            }
        }

        $_additionalAttribute = 0;
        foreach ($product->getTypeInstance(true)->getEditableAttributes($product) as $attribute) {
            if ($this->_isAllowedAttribute($attribute, $allAttributes)) {
                if (in_array($attribute->getAttributeCode(), $_additionalAttributeCodes)) {
                    $result['additional_attributes'][$_additionalAttribute]['key'] = $attribute->getAttributeCode();
                    $result['additional_attributes'][$_additionalAttribute]['value'] = $product->getData($attribute->getAttributeCode());
                    $_additionalAttribute++;
                } else {
                    $result[$attribute->getAttributeCode()] = $product->getData($attribute->getAttributeCode());
                }
            }
        }

        return $result;
    }

    /**
     * Create new product.
     *
     * @param string $type
     * @param int $set
     * @param array $productData
     * @return int
     */
    public function create($type, $set, $sku, $productData)
    {
        if (!$type || !$set || !$sku) {
            $this->_fault('data_invalid');
        }
        $store = null;
        $product = Mage::getModel('catalog/product');
        /* @var $product Mage_Catalog_Model_Product */
        $product->setStoreId($this->_getStoreId($store))
            ->setAttributeSetId($set)
            ->setTypeId($type)
            ->setSku($sku);

        if (property_exists($productData, 'additional_attributes')) {
            foreach ($productData->additional_attributes as $_attribute) {
                $_attrCode = $_attribute->key;
                $productData->$_attrCode = $_attribute->value;
            }
            unset($productData->additional_attributes);
        }
        foreach ($product->getTypeInstance(true)->getEditableAttributes($product) as $attribute) {
            $_attrCode = $attribute->getAttributeCode();
            if ($this->_isAllowedAttribute($attribute)
                && isset($productData->$_attrCode)) {
                $product->setData(
                    $attribute->getAttributeCode(),
                    $productData->$_attrCode
                );
            }
        }

        $this->_prepareDataForSave($product, $productData);

        if (is_array($errors = $product->validate())) {
            $this->_fault('data_invalid', implode("\n", $errors));
        }

        try {
            $product->save();
        } catch (Mage_Core_Exception $e) {
            $this->_fault('data_invalid', $e->getMessage());
        }

        return $product->getId();
    }

    /**
     * Update product data
     *
     * @param int|string $productId
     * @param array $productData
     * @param string|int $store
     * @return boolean
     */
    public function update($productId, $productData, $store = null)
    {
        $product = $this->_getProduct($productId, $store);

        if (!$product->getId()) {
            $this->_fault('not_exists');
        }

        if (property_exists($productData, 'additional_attributes')) {
            foreach ($productData->additional_attributes as $_attribute) {
                $_attrCode = $_attribute->key;
                $productData->$_attrCode = $_attribute->value;
            }
            unset($productData->additional_attributes);
        }

        foreach ($product->getTypeInstance(true)->getEditableAttributes($product) as $attribute) {
            $_attrCode = $attribute->getAttributeCode();
            if ($this->_isAllowedAttribute($attribute)
                && isset($productData->$_attrCode)) {
                $product->setData(
                    $attribute->getAttributeCode(),
                    $productData->$_attrCode
                );
            }
        }

        $this->_prepareDataForSave($product, $productData);

        try {
            if (is_array($errors = $product->validate())) {
                $this->_fault('data_invalid', implode("\n", $errors));
            }
        } catch (Mage_Core_Exception $e) {
            $this->_fault('data_invalid', $e->getMessage());
        }

        try {
            $product->save();
        } catch (Mage_Core_Exception $e) {
            $this->_fault('data_invalid', $e->getMessage());
        }

        return true;
    }

    /**
     *  Set additional data before product saved
     *
     *  @param    Mage_Catalog_Model_Product $product
     *  @param    array $productData
     *  @return	  object
     */
    protected function _prepareDataForSave ($product, $productData)
    {
        if (property_exists($productData, 'categories') && is_array($productData->categories)) {
            $product->setCategoryIds($productData->categories);
        }

        if (property_exists($productData, 'websites') && is_array($productData->websites)) {
            foreach ($productData->websites as &$website) {
                if (is_string($website)) {
                    try {
                        $website = Mage::app()->getWebsite($website)->getId();
                    } catch (Exception $e) { }
                }
            }
            $product->setWebsiteIds($productData->websites);
        }

        if (property_exists($productData, 'stock_data') && is_array($productData->stock_data)) {
            $product->setStockData($productData->stock_data);
        }
    }

    /**
     * Update product special price
     *
     * @param int|string $productId
     * @param float $specialPrice
     * @param string $fromDate
     * @param string $toDate
     * @param string|int $store
     * @return boolean
     */
    public function setSpecialPrice($productId, $specialPrice = null, $fromDate = null, $toDate = null, $store = null)
    {
        $obj = new stdClass();
        $obj->special_price = $specialPrice;
        $obj->special_from_date = $fromDate;
        $obj->special_to_date = $toDate;
        return $this->update($productId, $obj, $store);
    }

    /**
     * Retrieve product special price
     *
     * @param int|string $productId
     * @param string|int $store
     * @return array
     */
    public function getSpecialPrice($productId, $store = null)
    {
        return $this->info($productId, $store, array(
            'attributes' => array(
                'special_price',
                'special_from_date',
                'special_to_date'
                )
            )
        );
    }

}