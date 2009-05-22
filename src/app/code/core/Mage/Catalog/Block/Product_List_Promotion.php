<?php

class Mage_Catalog_Block_Product_List_Promotion extends Mage_Catalog_Block_Product_List
{
    protected function _getProductCollection()
    {
        if (is_null($this->_productCollection)) {
            $collection = AO::getResourceModel('catalog/product_collection');
            AO::getModel('catalog/layer')->prepareProductCollection($collection);
// your custom filter
            $collection->addAttributeToFilter('promotion', 1)
                ->addStoreFilter();

            $this->_productCollection = $collection;
        }
        return $this->_productCollection;
    }
}