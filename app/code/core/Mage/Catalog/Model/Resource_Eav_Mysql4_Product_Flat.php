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
 * Catalog Product Flat resource model
 *
 * @category   Mage
 * @package    Mage_Catalog
 * @author     Magento Core Team <core@magentocommerce.com>
 */
class Mage_Catalog_Model_Resource_Eav_Mysql4_Product_Flat
    extends Mage_Core_Model_Mysql4_Abstract
{
    /**
     * Store scope Id
     *
     * @var int
     */
    protected $_storeId;

    /**
     * Init connection and resource table
     *
     */
    protected function _construct()
    {
        $this->_init('catalog/product_flat', 'entity_id');
        $this->_storeId = Mage::app()->getStore()->getId();
    }

    /**
     * Set store scope for resource model
     *
     * @param mixed $store
     * @return Mage_Catalog_Model_Resource_Eav_Mysql4_Product_Flat
     */
    public function setStoreId($store)
    {
        $this->_storeId = Mage::app()->getStore($store)->getId();
        return $this;
    }

    /**
     * Retrieve Flat Table name
     *
     * @param mixed $store
     * @return string
     */
    public function getFlatTableName($store = null)
    {
        if (!is_numeric($store)) {
            $store = Mage::app()->getStore($store)->getId();
        }
        return $this->getTable('catalog/product_flat') . '_' . $store;
    }

    /**
     * Retrieve entity type id
     *
     * @return int
     */
    public function getTypeId()
    {
        return Mage::getSingleton('eav/config')
            ->getEntityType('catalog_product')
            ->getEntityTypeId();
    }

    /**
     * Retrieve attribute columns for collection select
     *
     * @param string $attributeCode
     * @return array|null
     */
    public function getAttributeForSelect($attributeCode)
    {
        $describe = $this->_getWriteAdapter()->describeTable($this->getFlatTableName());
        if (!isset($describe[$attributeCode])) {
            return null;
        }
        $columns = array($attributeCode => $attributeCode);

        if (isset($describe[$attributeCode . '_value'])) {
            $columns[$attributeCode . '_value'] = $attributeCode . '_value';
        }

        return $columns;
    }

    /**
     * Retrieve Attribute Sort column name
     *
     * @param string $attributeCode
     * @return string
     */
    public function getAttributeSortColumn($attributeCode)
    {
        $describe = $this->_getWriteAdapter()->describeTable($this->getFlatTableName());
        if (!isset($describe[$attributeCode])) {
            return null;
        }
        if (isset($describe[$attributeCode . '_value'])) {
            return $attributeCode . '_value';
        }
        return $attributeCode;
    }

    /**
     * Retrieve Flat Table columns list
     *
     * @return array
     */
    public function getAllTableColumns()
    {
        $describe = $this->_getWriteAdapter()->describeTable($this->getFlatTableName());
        return array_keys($describe);
    }
}
