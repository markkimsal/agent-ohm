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
 * Layer attribute filter
 *
 * @category   Mage
 * @package    Mage_Catalog
 * @author     Magento Core Team <core@magentocommerce.com>
 */
class Mage_Catalog_Model_Layer_Filter_Attribute extends Mage_Catalog_Model_Layer_Filter_Abstract
{
    const OPTIONS_ONLY_WITH_RESULTS = 1;

    /**
     * Construct attribute filter
     *
     */
    public function __construct()
    {
        parent::__construct();
        $this->_requestVar = 'attribute';
    }

    /**
     * Get option text from frontend model by option id
     *
     * @param   int $optionId
     * @return  unknown
     */
    protected function _getOptionText($optionId)
    {
        return $this->getAttributeModel()->getFrontend()->getOption($optionId);
    }

    /**
     * Apply attribute option filter to product collection
     *
     * @param   Zend_Controller_Request_Abstract $request
     * @param   Varien_Object $filterBlock
     * @return  Mage_Catalog_Model_Layer_Filter_Attribute
     */
    public function apply(Zend_Controller_Request_Abstract $request, $filterBlock)
    {
        $filter = $request->getParam($this->_requestVar);
        if (is_array($filter)) {
            return $this;
        }
        $text = $this->_getOptionText($filter);
        if ($filter && $text) {
            Mage::getSingleton('catalogindex/attribute')->applyFilterToCollection(
                $this->getLayer()->getProductCollection(),
                $this->getAttributeModel(),
                $filter
            );
            $this->getLayer()->getState()->addFilter($this->_createItem($text, $filter));
            $this->_items = array();
        }
        return $this;
    }

    /**
     * Get data array for building attribute filter items
     *
     * @return array
     */
    protected function _getItemsData()
    {
        $attribute = $this->getAttributeModel();
        $this->_requestVar = $attribute->getAttributeCode();

        $key = $this->getLayer()->getStateKey().'_'.$this->_requestVar;
        $data = $this->getLayer()->getAggregator()->getCacheData($key);

        if ($data === null) {
            $options = $attribute->getFrontend()->getSelectOptions();
            $optionsCount = Mage::getSingleton('catalogindex/attribute')->getCount(
                $attribute,
                $this->_getBaseCollectionSql()
            );
            $data = array();

            foreach ($options as $option) {
                if (is_array($option['value'])) {
                    continue;
                }
                if (Mage::helper('core/string')->strlen($option['value'])) {
                    // Check filter type
                    if ($attribute->getIsFilterable() == self::OPTIONS_ONLY_WITH_RESULTS) {
                        if (!empty($optionsCount[$option['value']])) {
                            $data[] = array(
                                'label' => $option['label'],
                                'value' => $option['value'],
                                'count' => $optionsCount[$option['value']],
                            );
                        }
                    }
                    else {
                        $data[] = array(
                            'label' => $option['label'],
                            'value' => $option['value'],
                            'count' => isset($optionsCount[$option['value']]) ? $optionsCount[$option['value']] : 0,
                        );
                    }
                }
            }

            $tags = array(
                Mage_Eav_Model_Entity_Attribute::CACHE_TAG.':'.$attribute->getId()
            );

            $tags = $this->getLayer()->getStateTags($tags);
            $this->getLayer()->getAggregator()->saveCacheData($data, $key, $tags);
        }
        return $data;
    }
}
