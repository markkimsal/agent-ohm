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
 * Categories tree block for urlrewrites
 *
 * @category   Mage
 * @package    Mage_Adminhtml
 * @author      Magento Core Team <core@magentocommerce.com>
 */
class Mage_Adminhtml_Block_Urlrewrite_Category_Tree extends Mage_Adminhtml_Block_Catalog_Category_Abstract
{
    /**
     * Set custom template for the block
     *
     */
    public function __construct()
    {
        parent::__construct();
        $this->setTemplate('urlrewrite/categories.phtml');
    }

    /**
     * Get categories tree as recursive array
     *
     * @param int $parentId
     * @param bool $asJson
     * @param int $recursionLevel
     * @return array
     */
    public function getTreeArray($parentId = null, $asJson = false, $recursionLevel = 3)
    {
        $result = array();
        if ($parentId) {
            $category = Mage::getModel('catalog/category')->load($parentId);
            if (!empty($category)) {
                $tree = $this->_getNodesArray($this->getNode($category, $recursionLevel));
                if (!empty($tree) && !empty($tree['children'])) {
                    $result = $tree['children'];
                }
            }
        }
        else {
            $result = $this->_getNodesArray($this->getRoot(null, $recursionLevel));
        }
        if ($asJson) {
            return Zend_Json::encode($result);
        }
        return $result;
    }

    /**
     * Get categories collection
     *
     * @return Mage_Catalog_Model_Resource_Eav_Mysql4_Category_Collection
     */
    public function getCategoryCollection()
    {
        $collection = $this->_getData('category_collection');
        if (is_null($collection)) {
            $collection = Mage::getModel('catalog/category')->getCollection()
                ->addAttributeToSelect(array('name', 'is_active'))
                ->setLoadProductCount(true)
            ;
            $this->setData('category_collection', $collection);
        }
        return $collection;
    }

    /**
     * Convert categories tree to array recursively
     *
     * @return array
     */
    protected function _getNodesArray($node)
    {
        $result = array(
            'id'             => (int)$node->getId(),
            'parent_id'      => (int)$node->getParentId(),
            'children_count' => (int)$node->getChildrenCount(),
            'is_active'      => (bool)$node->getIsActive(),
            'name'           => $node->getName(),
            'level'          => (int)$node->getLevel(),
            'product_count'  => (int)$node->getProductCount(),
        );
        if ($node->hasChildren()) {
            $result['children'] = array();
            foreach ($node->getChildren() as $childNode) {
                $result['children'][] = $this->_getNodesArray($childNode);
            }
        }
        $result['cls'] = ($result['is_active'] ? '' : 'no-') . 'active-category';
        $result['expanded'] = false;
        if (!empty($result['children'])) {
            $result['expanded'] = true;
        }
        return $result;
    }

    /**
     * Get URL for categories tree ajax loader
     *
     * @return string
     */
    public function getLoadTreeUrl()
    {
        return Mage::helper('adminhtml')->getUrl('*/*/categoriesJson');
    }
}
