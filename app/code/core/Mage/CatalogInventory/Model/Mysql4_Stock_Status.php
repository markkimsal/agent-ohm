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
 * @category    Mage
 * @package     Mage_CatalogInventory
 * @copyright   Copyright (c) 2008 Irubin Consulting Inc. DBA Varien (http://www.varien.com)
 * @license     http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */

/**
 * CatalogInventory Stock Status per website Resource Model
 *
 * @category    Mage
 * @package     Mage_CatalogInventory
 * @author      Magento Core Team <core@magentocommerce.com>
 */
class Mage_CatalogInventory_Model_Mysql4_Stock_Status extends Mage_Core_Model_Mysql4_Abstract
{
    /**
     * Resource model initialization
     *
     */
    protected function _construct()
    {
        $this->_init('cataloginventory/stock_status', 'product_id');
    }

    /**
     * Save Product Status per website
     *
     * @param Mage_CatalogInventory_Model_Stock_Status $object
     * @param int $productId
     * @param int $status
     * @param float $qty
     * @param int $stockId
     * @param int|null $websiteId
     * @return Mage_CatalogInventory_Model_Mysql4_Stock_Status
     */
    public function saveProductStatus(Mage_CatalogInventory_Model_Stock_Status $object,
        $productId, $status, $qty = 0, $stockId = 1, $websiteId = null)
    {
        $websites = array_keys($object->getWebsites($websiteId));

        foreach ($websites as $websiteId) {
            $select = $this->_getWriteAdapter()->select()
                ->from($this->getMainTable())
                ->where('product_id=?', $productId)
                ->where('website_id=?', $websiteId)
                ->where('stock_id=?', $stockId);
            if ($row = $this->_getWriteAdapter()->fetchRow($select)) {
                $bind = array(
                    'qty'           => $qty,
                    'stock_status'  => $status
                );
                $where = array(
                    $this->_getWriteAdapter()->quoteInto('product_id=?', $row['product_id']),
                    $this->_getWriteAdapter()->quoteInto('website_id=?', $row['website_id']),
                    $this->_getWriteAdapter()->quoteInto('stock_id=?', $row['stock_id']),
                );
                $this->_getWriteAdapter()->update($this->getMainTable(), $bind, $where);
            }
            else {
                $bind = array(
                    'product_id'    => $productId,
                    'website_id'    => $websiteId,
                    'stock_id'      => $stockId,
                    'qty'           => $qty,
                    'stock_status'  => $status
                );
                $this->_getWriteAdapter()->insert($this->getMainTable(), $bind);
            }
        }

        return $this;
    }

    /**
     * Retrieve product status
     * Return array as key product id, value - stock status
     *
     * @param int|array $productIds
     * @param int $websiteId
     * @param int $stockId
     *
     * @return array
     */
    public function getProductStatus($productIds, $websiteId, $stockId = 1)
    {
        if (!is_array($productIds)) {
            $productIds = array($productIds);
        }

        $select = $this->_getReadAdapter()->select()
            ->from($this->getMainTable(), array('product_id', 'stock_status'))
            ->where('product_id IN(?)', $productIds)
            ->where('stock_id=?', $stockId)
            ->where('website_id=?', $websiteId);
        return $this->_getReadAdapter()->fetchPairs($select);
    }

    /**
     * Retrieve product(s) data array
     *
     * @param int|array $productIds
     * @param int $websiteId
     * @param int $stockId
     *
     * @return array
     */
    public function getProductData($productIds, $websiteId, $stockId = 1)
    {
        if (!is_array($productIds)) {
            $productIds = array($productIds);
        }

        $data = array();

        $select = $this->_getReadAdapter()->select()
            ->from($this->getMainTable())
            ->where('product_id IN(?)', $productIds)
            ->where('stock_id=?', $stockId)
            ->where('website_id=?', $websiteId);
        $query = $this->_getReadAdapter()->query($select);
        while ($row = $query->fetch()) {
            $data[$row['product_id']] = $row;
        }
        return $data;
    }

    /**
     * Retrieve websites and default stores
     * Return array as key website_id, value store_id
     *
     * @return array
     */
    public function getWebsiteStores() {
        $select = Mage::getModel('core/website')->getDefaultStoresSelect(false);
        return $this->_getReadAdapter()->fetchPairs($select);
    }

    /**
     * Retrieve Product Type
     *
     * @param array|int $productIds
     * @return array
     */
    public function getProductsType($productIds)
    {
        if (!is_array($productIds)) {
            $productIds = array($productIds);
        }

        $select = $this->_getReadAdapter()->select()
            ->from(
                array('e' => $this->getTable('catalog/product')),
                array('entity_id', 'type_id'))
            ->where('entity_id IN(?)', $productIds);
        return $this->_getReadAdapter()->fetchPairs($select);
    }

    /**
     * Retrieve Product part Collection array
     * Return array as key product id, value product type
     *
     * @param int $lastEntityId
     * @param int $limit
     * @return array
     */
    public function getProductCollection($lastEntityId = 0, $limit = 1000) {
        $select = $this->_getReadAdapter()->select()
            ->from(
                array('e' => $this->getTable('catalog/product')),
                array('entity_id', 'type_id'))
            ->order('entity_id ASC')
            ->where('entity_id>?', $lastEntityId)
            ->limit($limit);
        return $this->_getReadAdapter()->fetchPairs($select);
    }
}

