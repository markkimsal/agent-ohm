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
 * Category controller
 *
 * @category   Mage
 * @package    Mage_Catalog
 * @author      Magento Core Team <core@magentocommerce.com>
 */
class Mage_Catalog_CategoryController extends Mage_Core_Controller_Front_Action
{
    /**
     * Initialize requested category object
     *
     * @return Mage_Catalog_Model_Category
     */
    protected function _initCatagory()
    {
        AO::dispatchEvent('catalog_controller_category_init_before', array('controller_action'=>$this));
        $categoryId = (int) $this->getRequest()->getParam('id', false);
        if (!$categoryId) {
            return false;
        }

        $category = AO::getModel('catalog/category')
            ->setStoreId(AO::app()->getStore()->getId())
            ->load($categoryId);

        if (!AO::helper('catalog/category')->canShow($category)) {
            return false;
        }
        AO::getSingleton('catalog/session')->setLastVisitedCategoryId($category->getId());
        AO::register('current_category', $category);
        try {
            AO::dispatchEvent('catalog_controller_category_init_after', array('category'=>$category, 'controller_action'=>$this));
        } catch (Mage_Core_Exception $e) {
            AO::logException($e);
            return false;
        }
        return $category;
    }

    /**
     * Category view action
     */
    public function viewAction()
    {

        if ($category = $this->_initCatagory()) {

            AO::getModel('catalog/design')->applyDesign($category, Mage_Catalog_Model_Design::APPLY_FOR_CATEGORY);
            AO::getSingleton('catalog/session')->setLastViewedCategoryId($category->getId());

            $update = $this->getLayout()->getUpdate();
            $update->addHandle('default');

            if (!$category->hasChildren()) {
                $update->addHandle('catalog_category_layered_nochildren');
            }

            $this->addActionLayoutHandles();

            $update->addHandle($category->getLayoutUpdateHandle());
            $update->addHandle('CATEGORY_'.$category->getId());

//            $this->loadLayoutUpdates();

            $update->addUpdate($category->getCustomLayoutUpdate());

            $this->generateLayoutXml()->generateLayoutBlocks();

            if ($root = $this->getLayout()->getBlock('root')) {
                $root->addBodyClass('categorypath-'.$category->getUrlPath())
                    ->addBodyClass('category-'.$category->getUrlKey());
            }

            $this->_initLayoutMessages('catalog/session');
            $this->_initLayoutMessages('checkout/session');
//            $this->renderLayout();
        }
        else {
            $this->_forward('noRoute');
        }
    }
}
