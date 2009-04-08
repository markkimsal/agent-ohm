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
 * @package    Mage_Reports
 * @copyright  Copyright (c) 2008 Irubin Consulting Inc. DBA Varien (http://www.varien.com)
 * @license    http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */

/**
 * Reports Recently Viewed Products Block
 *
 * @category   Mage
 * @package    Mage_Reports
 * @author      Magento Core Team <core@magentocommerce.com>
 */

class Mage_Reports_Block_Product_Viewed extends Mage_Reports_Block_Product_Abstract
{
    const XML_PATH_RECENTLY_VIEWED_COUNT    = 'catalog/recently_products/viewed_count';

    protected $_eventTypeId = Mage_Reports_Model_Event::EVENT_PRODUCT_VIEW;

    /**
     * Retrieve page size (count)
     *
     * @return int
     */
    protected function getPageSize()
    {
        if ($this->hasData('page_size')) {
            return $this->getData('page_size');
        }
        return Mage::getStoreConfig(self::XML_PATH_RECENTLY_VIEWED_COUNT);
    }

    /**
     * Retrieve Product Ids to skip
     *
     * @return array
     */
    protected function _getProductsToSkip()
    {
        $ids = array();
        if (($product = Mage::registry('product')) && $product->getId()) {
            $ids = (int)$product->getId();
        }
        return $ids;
    }

    /**
     * Check session has viewed products
     *
     * @return bool
     */
    protected function _hasViewedProductsBefore()
    {
        return Mage::getSingleton('reports/session')->getData('viewed_products');
    }

    /**
     * Prepare to html
     * check has viewed products
     *
     * @return string
     */
    protected function _toHtml()
    {
        if (!$this->_hasViewedProductsBefore()) {
            return '';
        }

        $collection = $this->_getRecentProductsCollection();
        $hasProducts = (bool)count($collection);
        if (is_null($this->_hasViewedProductsBefore())) {
            Mage::getSingleton('reports/session')->setData('viewed_products', $hasProducts);
        }
        if ($hasProducts) {
            $this->setRecentlyViewedProducts($collection);
        }

        return parent::_toHtml();
    }
}
