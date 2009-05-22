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
 * GoogleBase Item Types collection
 *
 * @category   Mage
 * @package    Mage_GoogleBase
 * @author     Magento Core Team <core@magentocommerce.com>
 */
class Mage_GoogleBase_Model_Mysql4_Type_Collection extends Mage_Core_Model_Mysql4_Collection_Abstract
{

	protected function _construct()
	{
		$this->_init('googlebase/type');
	}

	protected function _initSelect()
	{
	    parent::_initSelect();
	    $this->_joinAttributeSet();
	    return $this;
	}

	/**
	 * Add total count of Items for each type
	 *
	 * @return Mage_GoogleBase_Model_Mysql4_Type_Collection
	 */
	public function addItemsCount()
	{
        $this->getSelect()
            ->joinLeft(
                array('items'=>$this->getTable('googlebase/items')),
                'main_table.type_id=items.type_id',
                array('items_total' => 'COUNT(items.item_id)'))
            ->group('main_table.type_id');
        return $this;
	}

	/**
	 * Add country ISO filter to collection
	 *
	 * @param string $iso Two-letter country ISO code
	 * @return Mage_GoogleBase_Model_Mysql4_Type_Collection
	 */
	public function addCountryFilter($iso)
	{
        $this->getSelect()->where('target_country=?', $iso);
        return $this;
	}

	/**
	 * Join Attribute Set data
	 *
	 * @return Mage_GoogleBase_Model_Mysql4_Type_Collection
	 */
	protected function _joinAttributeSet()
	{
        $this->getSelect()
            ->join(
                array('set'=>$this->getTable('eav/attribute_set')),
                'main_table.attribute_set_id=set.attribute_set_id',
                array('attribute_set_name' => 'set.attribute_set_name'));
        return $this;
	}
}