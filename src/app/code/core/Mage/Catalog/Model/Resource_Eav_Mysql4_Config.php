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
 * Catalog Config Resource Model
 *
 * @category   Mage
 * @package    Mage_Catalog
 * @author     Magento Core Team <core@magentocommerce.com>
 */
class Mage_Catalog_Model_Resource_Eav_Mysql4_Config extends Mage_Core_Model_Mysql4_Abstract
{
    /**
     * catalog_product entity type id
     *
     * @var int
     */
    protected $_entityTypeId;

    /**
     * Initialize connection
     *
     */
    protected function _construct() {
        $this->_init('eav/attribute', 'attribute_id');
    }

    /**
     * Retrieve catalog_product entity type id
     *
     * @return int
     */
    public function getEntityTypeId()
    {
        if (is_null($this->_entityTypeId)) {
            $select = $this->_getReadAdapter()->select()
                ->from($this->getTable('eav/entity_type'), 'entity_type_id')
                ->where('entity_type_code=?', 'catalog_product');
            $this->_entityTypeId = $this->_getReadAdapter()->fetchOne($select);
        }
        return $this->_entityTypeId;
    }

    /**
     * Retrieve Product Attributes Used in Catalog Product listing
     *
     * @return array
     */
    public function getAttributesUsedInListing() {
        $select = $this->_getReadAdapter()->select()
            ->from($this->getTable('eav/attribute'))
            ->where('entity_type_id=?', $this->getEntityTypeId())
            ->where('used_in_product_listing=?', 1);
        return $this->_getReadAdapter()->fetchAll($select);
    }

    /**
     * Retrieve Used Product Attributes for Catalog Product Listing Sort By
     *
     * @return array
     */
    public function getAttributesUsedForSortBy() {
        $select = $this->_getReadAdapter()->select()
            ->from($this->getTable('eav/attribute'))
            ->where('entity_type_id=?', $this->getEntityTypeId())
            ->where('used_for_sort_by=?', 1);
        return $this->_getReadAdapter()->fetchAll($select);
    }

}
