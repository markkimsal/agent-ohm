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
 * Catalog products compare block
 *
 * @category   Mage
 * @package    Mage_Catalog
 * @author     Magento Core Team <core@magentocommerce.com>
 */
class Mage_Catalog_Block_Product_Compare_List extends Mage_Catalog_Block_Product_Abstract
{
    /**
     * Product Compare Items Collection
     *
     * @var Mage_Catalog_Model_Resource_Eav_Mysql4_Product_Compare_Item_Collection
     */
    protected $_items = null;

    /**
     * Product Compare attributes array
     *
     * @var array
     */
    protected $_attributes = null;

    /**
     * Preparing layout
     *
     * @return Mage_Catalog_Block_Product_Compare_List
     */
    protected function _prepareLayout()
    {
        if ($headBlock = $this->getLayout()->getBlock('head')) {
            $headBlock->setTitle(Mage::helper('catalog')->__('Compare Products List') . ' - ' . $headBlock->getDefaultTitle());
        }
        return parent::_prepareLayout();
    }

    /**
     * Retrieve Product Compare items collection
     *
     * @return Mage_Catalog_Model_Resource_Eav_Mysql4_Product_Compare_Item_Collection
     */
    public function getItems()
    {
        if (is_null($this->_items)) {
            Mage::helper('catalog/product_compare')->setAllowUsedFlat(false);
            $this->_items = Mage::getResourceModel('catalog/product_compare_item_collection')
                ->useProductItem(true)
                ->setStoreId(Mage::app()->getStore()->getId());

            if (Mage::getSingleton('customer/session')->isLoggedIn()) {
                $this->_items
                    ->setCustomerId(Mage::getSingleton('customer/session')->getCustomerId());
            }
            else {
                $this->_items
                    ->setVisitorId(Mage::getSingleton('log/visitor')->getId());
            }

            $this->_items
                ->loadComaparableAttributes()
                ->addAttributeToSelect(Mage::getSingleton('catalog/config')->getProductAttributes())
                ->addMinimalPrice()
                ->addTaxPercents();

            Mage::getSingleton('catalog/product_visibility')
                ->addVisibleInSiteFilterToCollection($this->_items);
        }

        return $this->_items;
    }

    /**
     * Retrieve Product Compare Attributes
     *
     * @return array
     */
    public function getAttributes()
    {
        if (is_null($this->_attributes)) {
            $this->_setAttributesFromProducts();
        }

        return $this->_attributes;
    }

    /**
     * Initialize Product Compare Attributes Process
     *
     * @return Mage_Catalog_Block_Product_Compare_List
     */
    protected function _setAttributesFromProducts()
    {
        $this->_attributes = array();
        foreach ($this->getItems() as $item) {
            foreach ($item->getTypeInstance(true)->getSetAttributes($item) as $attribute) {
                if ($attribute->getIsComparable()
                    && !isset($this->_attributes[$attribute->getAttributeCode()])
                    && $item->getData($attribute->getAttributeCode())!==null) {
                    $this->_attributes[$attribute->getAttributeCode()] = $attribute;
                }
            }
        }
        return $this;
    }

    /**
     * Retrieve Product Attribute Value
     *
     * @param Mage_Catalog_Model_Product $product
     * @param Mage_Catalog_Model_Resource_Eav_Attribute $attribute
     * @return string
     */
    public function getProductAttributeValue($product, $attribute)
    {
        if (!$product->hasData($attribute->getAttributeCode())) {
            return '&nbsp;';
        }

        if ($attribute->getSourceModel() || in_array($attribute->getFrontendInput(), array('select','boolean','multiselect'))) {
            //$value = $attribute->getSource()->getOptionText($product->getData($attribute->getAttributeCode()));
            $value = $attribute->getFrontend()->getValue($product);
        }
        else {
            $value = $product->getData($attribute->getAttributeCode());
        }
        return $value ? $value : '&nbsp;';
    }

    /**
     * Retrieve Print URL
     *
     * @return string
     */
    public function getPrintUrl()
    {
        return $this->getUrl('*/*/*', array('_current'=>true, 'print'=>1));
    }
}
