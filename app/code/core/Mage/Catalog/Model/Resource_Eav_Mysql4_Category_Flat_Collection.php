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
 * Catalog category flat collection
 *
 * @category   Mage
 * @package    Mage_Catalog
 * @author     Magento Core Team <core@magentocommerce.com>
 */
class Mage_Catalog_Model_Resource_Eav_Mysql4_Category_Flat_Collection extends Mage_Core_Model_Mysql4_Collection_Abstract
{
    /**
     * Event prefix
     *
     * @var string
     */
    protected $_eventPrefix = 'catalog_category_collection';

    /**
     * Event object name
     *
     * @var string
     */
    protected $_eventObject = 'category_collection';


    /**
     * Store id of application
     *
     * @var integer
     */
    protected $_storeId = null;

    protected function _construct()
    {
        $this->_init('catalog/category_flat');
        $this->setModel('catalog/category');
    }

    protected function _initSelect()
    {
        $this->getSelect()->from(
            array('main_table' => $this->getResource()->getMainStoreTable($this->getStoreId())),
            array('entity_id', 'level', 'path', 'position', 'is_active', 'is_anchor')
        );
        return $this;
    }

    /**
     * Add filter by entity id(s).
     *
     * @param mixed $categoryIds
     * @return Mage_Catalog_Model_Resource_Eav_Mysql4_Category_Flat_Collection
     */
    public function addIdFilter($categoryIds)
    {
        if (is_array($categoryIds)) {
            if (empty($categoryIds)) {
                $condition = '';
            } else {
                $condition = array('in' => $categoryIds);
            }
        } elseif (is_numeric($categoryIds)) {
            $condition = $categoryIds;
        } elseif (is_string($categoryIds)) {
            $ids = explode(',', $categoryIds);
            if (empty($ids)) {
                $condition = $categoryIds;
            } else {
                $condition = array('in' => $ids);
            }
        }
        $this->addFieldToFilter('entity_id', $condition);
        return $this;
    }

    /**
     * Before collection load
     *
     * @return Mage_Catalog_Model_Resource_Eav_Mysql4_Category_Flat_Collection
     */
    protected function _beforeLoad()
    {
        Mage::dispatchEvent($this->_eventPrefix . '_load_before',
                            array($this->_eventObject => $this));
        return parent::_beforeLoad();
    }

    /**
     * After collection load
     *
     * @return Mage_Catalog_Model_Resource_Eav_Mysql4_Category_Flat_Collection
     */
    protected function _afterLoad()
    {
        Mage::dispatchEvent($this->_eventPrefix . '_load_after',
                            array($this->_eventObject => $this));

        return parent::_afterLoad();
    }


    /**
     * Set store id
     *
     * @param integer $storeId
     * @return Mage_Catalog_Model_Resource_Eav_Mysql4_Category_Flat_Collection
     */
    public function setStoreId($storeId)
    {
        $this->_storeId = $storeId;
        return $this;
    }

    /**
     * Return store id.
     * If store id is not set yet, return store of application
     *
     * @return integer
     */
    public function getStoreId()
    {
        if (is_null($this->_storeId)) {
            return Mage::app()->getStore()->getId();
        }
        return $this->_storeId;
    }

    /**
     * Add filter by path to collection
     *
     * @param string $parent
     * @return Mage_Catalog_Model_Resource_Eav_Mysql4_Category_Flat_Collection
     */
    public function addParentPathFilter($parent)
    {
        $this->addFieldToFilter('path', array('like' => "{$parent}/%"));
        return $this;
    }

    /**
     * Add store filter
     *
     * @return Mage_Catalog_Model_Resource_Eav_Mysql4_Category_Flat_Collection
     */
    public function addStoreFilter()
    {
        $this->addFieldToFilter('main_table.store_id', $this->getStoreId());
        return $this;
    }

    /**
     * Set field to sort by
     *
     * @param string $sorted
     * @return Mage_Catalog_Model_Resource_Eav_Mysql4_Category_Flat_Collection
     */
    public function addSortedField($sorted)
    {
        if (is_string($sorted)) {
            $this->addOrder($sorted, 'ASC');
        } else {
            $this->addOrder('name', 'ASC');
        }
        return $this;
    }

    public function addIsActiveFilter()
    {
        $this->addFieldToFilter('is_active', 1);
        return $this;
    }

    public function addNameToResult()
    {
        $this->addAttributeToSelect('name');
        return $this;
    }

    /**
     * Add attribute to select
     *
     * @param array|string $attribute
     * @return Mage_Catalog_Model_Resource_Eav_Mysql4_Category_Flat_Collection
     */
    public function addAttributeToSelect($attribute = '*')
    {
        if ($attribute == '*') {
            // Save previous selected columns
            $columns = $this->getSelect()->getPart(Zend_Db_Select::COLUMNS);
            $this->getSelect()->reset(Zend_Db_Select::COLUMNS);
            foreach ($columns as $column) {
                if ($column[0] == 'main_table') {
                    // If column selected from main table,
                    // no need to select it again
                    continue;
                }

                // Joined columns
                if ($column[2] !== null) {
                    $expression = array($column[2] => $column[1]);
                } else {
                    $expression = $column[2];
                }
                $this->getSelect()->columns($expression, $column[0]);
            }

            $this->getSelect()->columns('*', 'main_table');
            return $this;
        }

        if (!is_array($attribute)) {
            $attribute = array($attribute);
        }

        $this->getSelect()->columns($attribute, 'main_table');
        return $this;
    }

    public function addUrlRewriteToResult()
    {
        $storeId = Mage::app()->getStore()->getId();
        $this->getSelect()->joinLeft(
            array('url_rewrite' => $this->getTable('core/url_rewrite')),
            'url_rewrite.category_id=main_table.entity_id AND url_rewrite.is_system=1 AND url_rewrite.product_id IS NULL AND url_rewrite.store_id="'.$storeId.'" AND url_rewrite.id_path LIKE "category/%"',
            array('request_path')
        );
        return $this;
    }

    public function addPathsFilter($paths)
    {
        if (!is_array($paths)) {
            $paths = array($paths);
        }
        $select = $this->getSelect();
        $orWhere = false;
        foreach ($paths as $path) {
            if ($orWhere) {
                $select->orWhere('main_table.path LIKE ?', "$path%");
            } else {
                $select->where('main_table.path LIKE ?', "$path%");
                $orWhere = true;
            }
        }
        return $this;
    }

    public function addLevelFilter($level)
    {
        $this->getSelect()->where('main_table.level <= ?', $level);
        return $this;
    }

    public function addOrderField($field)
    {
        $this->setOrder('main_table.' . $field, 'ASC');
        return $this;
    }
}