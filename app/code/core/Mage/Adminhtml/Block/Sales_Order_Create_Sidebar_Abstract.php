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
 * @package    Mage_Adminhtml
 * @copyright  Copyright (c) 2008 Irubin Consulting Inc. DBA Varien (http://www.varien.com)
 * @license    http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */

/**
 * Adminhtml sales order create sidebar block
 *
 * @category   Mage
 * @package    Mage_Adminhtml
 * @author      Magento Core Team <core@magentocommerce.com>
 */
class Mage_Adminhtml_Block_Sales_Order_Create_Sidebar_Abstract extends Mage_Adminhtml_Block_Sales_Order_Create_Abstract
{
    protected $_sidebarStorageAction = 'add';

    public function __construct()
    {
        parent::__construct();
        $this->setTemplate('sales/order/create/sidebar/items.phtml');
    }

    /**
     * Return name of sidebar storage action
     *
     * @return string
     */
    public function getSidebarStorageAction()
    {
        return $this->_sidebarStorageAction;
    }

    /**
     * Retrieve display block availability
     *
     * @return bool
     */
    public function canDisplay()
    {
        return $this->getCustomerId();
    }

    public function canDisplayItemQty()
    {
        return false;
    }

    /**
     * Retrieve availability removing items in block
     *
     * @return bool
     */
    public function canRemoveItems()
    {
        return true;
    }

    /**
     * Retrieve identifier of block item
     *
     * @param   Varien_Object $item
     * @return  int
     */
    public function getIdentifierId($item)
    {
        return $item->getProductId();
    }

    /**
     * Retrieve item identifier of block item
     *
     * @param   mixed $item
     * @return  int
     */
    public function getItemId($item)
    {
        return $item->getId();
    }

    /**
     * Retreive item count
     *
     * @return int
     */
    public function getItemCount()
    {
        $count = $this->getData('item_count');
        if (is_null($count)) {
            $count = count($this->getItems());
            $this->setData('item_count', $count);
        }
        return $count;
    }

    /**
     * Retrieve all items
     *
     * @return array
     */
    public function getItems()
    {
        if ($collection = $this->getItemCollection()) {
            $productTypes = Mage::getConfig()->getNode('adminhtml/sales/order/create/available_product_types')->asArray();
            $productTypes = array_keys($productTypes);
            if (is_array($collection)) {
                $items = $collection;
            } else {
                $items = $collection->getItems();
            }
            /*
             * filtering items by product type
             */
            foreach($items as $key=>$item) {
                if ($item instanceof Mage_Catalog_Model_Product) {
                    $type = $item->getTypeId();
                } else if ($item instanceof Mage_Sales_Model_Order_Item) {
                    $type = $item->getProductType();
                } else if ($item instanceof Mage_Sales_Model_Quote_Item) {
                    $type = $item->getProductType();
                } else {
                    $type = '';
                }
                if (!in_array($type, $productTypes)) {
                    unset($items[$key]);
                }
            }
            return $items;
        }
        return array();
    }

    /**
     * Retrieve item collection
     *
     * @return mixed
     */
    public function getItemCollection()
    {
        return false;
    }

    public function canDisplayPrice()
    {
        return true;
    }

}
