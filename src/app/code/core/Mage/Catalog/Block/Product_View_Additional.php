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
 * Product additional info block
 *
 * @category   Mage
 * @package    Mage_Catalog
 * @author      Magento Core Team <core@magentocommerce.com>
 */
class Mage_Catalog_Block_Product_View_Additional extends Mage_Core_Block_Template
{

    protected $_list;

    public function __construct()
    {
        parent::__construct();
        $this->setTemplate('catalog/product/view/additional.phtml');
    }

    public function getChildHtmlList()
    {
        if (is_null($this->_list)) {
            $this->_list = array();
            foreach ($this->getSortedChildren() as $name) {
                $block = $this->getLayout()->getBlock($name);
                if (!$block) {
                    AO::exception(AO::helper('catalog')->__('Invalid block: %s', $name));
                }
                $this->_list[] = $block->toHtml();
            }
        }
        return $this->_list;
    }

}