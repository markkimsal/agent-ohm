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
 * @package    Mage_CatalogIndex
 * @copyright  Copyright (c) 2008 Irubin Consulting Inc. DBA Varien (http://www.varien.com)
 * @license    http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */


/**
 * Price index resource model
 *
 * @author      Magento Core Team <core@magentocommerce.com>
 */
class Mage_CatalogIndex_Model_Mysql4_Price extends Mage_CatalogIndex_Model_Mysql4_Abstract
{
    protected $_rate = 1;
    protected $_customerGroupId;
    protected $_taxRates = null;

    protected function _construct()
    {
        $this->_init('catalogindex/price', 'index_id');
    }

    public function setRate($rate)
    {
        $this->_rate = $rate;
    }

    public function getRate()
    {
        if (!$this->_rate) {
            $this->_rate = 1;
        }
        return $this->_rate;
    }

    public function setCustomerGroupId($customerGroupId)
    {
        $this->_customerGroupId = $customerGroupId;
    }

    public function getCustomerGroupId()
    {
        return $this->_customerGroupId;
    }

    public function getMaxValue($attribute = null, $entitySelect)
    {
        $select = clone $entitySelect;
        $select->reset(Zend_Db_Select::COLUMNS);
        $select->reset(Zend_Db_Select::ORDER);
        $select->reset(Zend_Db_Select::LIMIT_COUNT);
        $select->reset(Zend_Db_Select::LIMIT_OFFSET);

        if ($attribute->getAttributeCode() == 'price') {
            $select->where('price_table.customer_group_id = ?', $this->getCustomerGroupId());
        }

        $select->join(array('price_table'=>$this->getMainTable()), 'price_table.entity_id=e.entity_id', array());

        $response = new Varien_Object();
        $response->setAdditionalCalculations(array());
        $args = array(
            'select'=>$select,
            'table'=>'price_table',
            'store_id'=>$this->getStoreId(),
            'response_object'=>$response,
        );
        Mage::dispatchEvent('catalogindex_prepare_price_select', $args);

        $select
            ->from('', "MAX(price_table.value".implode('', $response->getAdditionalCalculations()).")")
            ->where('price_table.website_id = ?', $this->getWebsiteId())
            ->where('price_table.attribute_id = ?', $attribute->getId());

        return $this->_getReadAdapter()->fetchOne($select)*$this->getRate();
    }

    public function getCount($range, $attribute, $entitySelect)
    {
        $select = clone $entitySelect;
        $select->reset(Zend_Db_Select::COLUMNS);
        $select->reset(Zend_Db_Select::ORDER);
        $select->reset(Zend_Db_Select::LIMIT_COUNT);
        $select->reset(Zend_Db_Select::LIMIT_OFFSET);

        $select->join(array('price_table'=>$this->getMainTable()), 'price_table.entity_id=e.entity_id', array());

        if ($attribute->getAttributeCode() == 'price') {
            $select->where('price_table.customer_group_id = ?', $this->getCustomerGroupId());
        }

        $response = new Varien_Object();
        $response->setAdditionalCalculations(array());
        $args = array(
            'select'=>$select,
            'table'=>'price_table',
            'store_id'=>$this->getStoreId(),
            'response_object'=>$response,
        );
        Mage::dispatchEvent('catalogindex_prepare_price_select', $args);

        $fields = array('count'=>'COUNT(DISTINCT price_table.entity_id)', 'range'=>"FLOOR(((price_table.value".implode('', $response->getAdditionalCalculations()).")*{$this->getRate()})/{$range})+1");

        $select->from('', $fields)
            ->group('range')
            ->where('price_table.website_id = ?', $this->getWebsiteId())
            ->where('price_table.attribute_id = ?', $attribute->getId());

        $result = $this->_getReadAdapter()->fetchAll($select);

        $counts = array();
        foreach ($result as $row) {
            $counts[$row['range']] = $row['count'];
        }

        return $counts;
    }

    public function getFilteredEntities($range, $index, $attribute, $entityIdsFilter, $tableName = 'price_table')
    {
        $select = $this->_getReadAdapter()->select();
        $select->from(array($tableName=>$this->getMainTable()), $tableName . '.entity_id');

        $response = new Varien_Object();
        $response->setAdditionalCalculations(array());
        $args = array(
            'select'=>$select,
            'table'=>$tableName,
            'store_id'=>$this->getStoreId(),
            'response_object'=>$response,
        );
        Mage::dispatchEvent('catalogindex_prepare_price_select', $args);

        $select
            ->distinct(true)
            ->where($tableName . '.entity_id in (?)', $entityIdsFilter)
            ->where($tableName . '.website_id = ?', $this->getWebsiteId())
            ->where($tableName . '.attribute_id = ?', $attribute->getId());

        if ($attribute->getAttributeCode() == 'price') {
            $select->where($tableName . '.customer_group_id = ?', $this->getCustomerGroupId());
        }

        $select->where("(({$tableName}.value".implode('', $response->getAdditionalCalculations()).")*{$this->getRate()}) >= ?", ($index-1)*$range);
        $select->where("(({$tableName}.value".implode('', $response->getAdditionalCalculations()).")*{$this->getRate()}) < ?", $index*$range);

        return $this->_getReadAdapter()->fetchCol($select);
    }

    public function getMinimalPrices($ids)
    {
        if (!$ids) {
            return array();
        }
        $select = $this->_getReadAdapter()->select();
        $select->from(array('price_table'=>$this->getTable('catalogindex/minimal_price')),
            array('price_table.entity_id', 'value'=>"(price_table.value)", 'tax_class_id'=>'(price_table.tax_class_id)'))
            ->where('price_table.entity_id in (?)', $ids)
            ->where('price_table.website_id = ?', $this->getWebsiteId())
            ->where('price_table.customer_group_id = ?', $this->getCustomerGroupId());
        return $this->_getReadAdapter()->fetchAll($select);
    }
}
