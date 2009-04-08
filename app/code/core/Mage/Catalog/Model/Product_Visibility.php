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
 * Catalog Product visibilite model and attribute source model
 *
 * @category   Mage
 * @package    Mage_Catalog
 * @author     Magento Core Team <core@magentocommerce.com>
 */
class Mage_Catalog_Model_Product_Visibility extends Varien_Object
{
    const VISIBILITY_NOT_VISIBLE    = 1;
    const VISIBILITY_IN_CATALOG     = 2;
    const VISIBILITY_IN_SEARCH      = 3;
    const VISIBILITY_BOTH           = 4;

    /**
     * Initialize object
     *
     */
    public function __construct()
    {
        parent::__construct();
        $this->setIdFieldName('visibility_id');
    }

    /**
     * Add visible in catalog filter to collection
     *
     * @deprecated
     * @param Mage_Eav_Model_Entity_Collection_Abstract $collection
     * @return Mage_Catalog_Model_Product_Visibility
     */
    public function addVisibleInCatalogFilterToCollection(Mage_Eav_Model_Entity_Collection_Abstract $collection)
    {
        $collection->setVisibility($this->getVisibleInCatalogIds());
//        $collection->addAttributeToFilter('visibility', array('in'=>$this->getVisibleInCatalogIds()));
        return $this;
    }

    /**
     * Add visibility in searchfilter to collection
     *
     * @deprecated
     * @param Mage_Eav_Model_Entity_Collection_Abstract $collection
     * @return Mage_Catalog_Model_Product_Visibility
     */
    public function addVisibleInSearchFilterToCollection(Mage_Eav_Model_Entity_Collection_Abstract $collection)
    {
        $collection->setVisibility($this->getVisibleInSearchIds());
        //$collection->addAttributeToFilter('visibility', array('in'=>$this->getVisibleInSearchIds()));
        return $this;
    }

    /**
     * Add visibility in site filter to collection
     *
     * @deprecated
     * @param Mage_Eav_Model_Entity_Collection_Abstract $collection
     * @return Mage_Catalog_Model_Product_Visibility
     */
    public function addVisibleInSiteFilterToCollection(Mage_Eav_Model_Entity_Collection_Abstract $collection)
    {
        $collection->setVisibility($this->getVisibleInSiteIds());
        //$collection->addAttributeToFilter('visibility', array('in'=>$this->getVisibleInSiteIds()));
        return $this;
    }

    /**
     * Retrieve visible in catalog ids array
     *
     * @return array
     */
    public function getVisibleInCatalogIds()
    {
        return array(self::VISIBILITY_IN_CATALOG, self::VISIBILITY_BOTH);
    }

    /**
     * Retrieve visible in search ids array
     *
     * @return array
     */
    public function getVisibleInSearchIds()
    {
        return array(self::VISIBILITY_IN_SEARCH, self::VISIBILITY_BOTH);
    }

    /**
     * Retrieve visible in site ids array
     *
     * @return array
     */
    public function getVisibleInSiteIds()
    {
        return array(self::VISIBILITY_IN_SEARCH, self::VISIBILITY_IN_CATALOG, self::VISIBILITY_BOTH);
    }

    /**
     * Retrieve option array
     *
     * @return array
     */
    static public function getOptionArray()
    {
        return array(
            self::VISIBILITY_NOT_VISIBLE=> Mage::helper('catalog')->__('Nowhere'),
            self::VISIBILITY_IN_CATALOG => Mage::helper('catalog')->__('Catalog'),
            self::VISIBILITY_IN_SEARCH  => Mage::helper('catalog')->__('Search'),
            self::VISIBILITY_BOTH       => Mage::helper('catalog')->__('Catalog, Search')
        );
    }

    /**
     * Retrieve all options
     *
     * @return array
     */
    static public function getAllOption()
    {
        $options = self::getOptionArray();
        array_unshift($options, array('value'=>'', 'label'=>''));
        return $options;
    }

    /**
     * Retireve all options
     *
     * @return array
     */
    static public function getAllOptions()
    {
        $res = array();
        $res[] = array('value'=>'', 'label'=> Mage::helper('catalog')->__('-- Please Select --'));
        foreach (self::getOptionArray() as $index => $value) {
            $res[] = array(
               'value' => $index,
               'label' => $value
            );
        }
        return $res;
    }

    /**
     * Retrieve option text
     *
     * @param int $optionId
     * @return string
     */
    static public function getOptionText($optionId)
    {
        $options = self::getOptionArray();
        return isset($options[$optionId]) ? $options[$optionId] : null;
    }

    /**
     * Retrieve Column(s) for Flat
     *
     * @return array
     */
    public function getFlatColums()
    {
        return array();
    }

    /**
     * Retrieve Indexes for Flat
     *
     * @return array
     */
    public function getFlatIndexes()
    {
        return array();
    }

    /**
     * Retrieve Select For Flat Attribute update
     *
     * @param Mage_Eav_Model_Entity_Attribute_Abstract $attribute
     * @param int $store
     * @return Varien_Db_Select|null
     */
    public function getFlatUpdateSelect($store)
    {
        return null;
    }
}
