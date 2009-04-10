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
 * Admin system config sturtup page
 *
 * @category   Mage
 * @package    Mage_Adminhtml
 * @author     Magento Core Team <core@magentocommerce.com>
 */
class Mage_Adminhtml_Model_System_Config_Source_Admin_Page
{
    protected $_url;

    public function toOptionArray()
    {
        $options = array();
        $menu    = $this->_buildMenuArray();

        $this->_createOptions($options, $menu);

        return $options;
    }

    protected function _createOptions(&$optionArray, $menuNode)
    {
        foreach ($menuNode as $menu) {

            if (!empty($menu['url'])) {
                $optionArray[] = array(
                    'label' => str_repeat('&nbsp;', ($menu['level'] * 4)) . $menu['label'],
                    'value' => $menu['path'],
                );

                if (isset($menu['children'])) {
                    $this->_createOptions($optionArray, $menu['children']);
                }
            }
            else {
                $children = array();
                $this->_createOptions($children, $menu['children']);

                $optionArray[] = array(
                    'label' => str_repeat('&nbsp;', ($menu['level'] * 4)) . $menu['label'],
                    'value' => $children,
                );
            }
        }
    }

    protected function _getUrlModel()
    {
        if (is_null($this->_url)) {
            $this->_url = AO::getModel('adminhtml/url');
        }
        return $this->_url;
    }

    protected function _buildMenuArray(Varien_Simplexml_Element $parent=null, $path='', $level=0)
    {
        if (is_null($parent)) {
            $parent = AO::getConfig()->getNode('adminhtml/menu');
        }

        $parentArr = array();
        $sortOrder = 0;
        foreach ($parent->children() as $childName=>$child) {

            if ($child->depends && !$this->_checkDepends($child->depends)) {
                continue;
            }

            $menuArr = array();
            $menuArr['label'] = $this->_getHelperValue($child);

            $menuArr['sort_order'] = $child->sort_order ? (int)$child->sort_order : $sortOrder;

            if ($child->action) {
                $menuArr['url'] = (string)$child->action;
            } else {
                $menuArr['url'] = '';
            }

            $menuArr['level'] = $level;
            $menuArr['path'] = $path . $childName;

            if ($child->children) {
                $menuArr['children'] = $this->_buildMenuArray($child->children, $path.$childName.'/', $level+1);
            }
            $parentArr[$childName] = $menuArr;

            $sortOrder++;
        }

        uasort($parentArr, array($this, '_sortMenu'));

        while (list($key, $value) = each($parentArr)) {
            $last = $key;
        }
        if (isset($last)) {
            $parentArr[$last]['last'] = true;
        }

        return $parentArr;
    }

    protected function _sortMenu($a, $b)
    {
        return $a['sort_order']<$b['sort_order'] ? -1 : ($a['sort_order']>$b['sort_order'] ? 1 : 0);
    }

    protected function _checkDepends(Varien_Simplexml_Element $depends)
    {
        if ($depends->module) {
            $modulesConfig = AO::getConfig()->getNode('modules');
            foreach ($depends->module as $module) {
                if (!$modulesConfig->$module || !$modulesConfig->$module->is('active')) {
                    return false;
                }
            }
        }

        return true;
    }

    protected function _getHelperValue(Varien_Simplexml_Element $child)
    {
        $helperName         = 'adminhtml';
        $titleNodeName      = 'title';
        $childAttributes    = $child->attributes();
        if (isset($childAttributes['module'])) {
            $helperName     = (string)$childAttributes['module'];
        }

        $titleNodeName = 'title';

        return AO::helper($helperName)->__((string)$child->$titleNodeName);
    }
}