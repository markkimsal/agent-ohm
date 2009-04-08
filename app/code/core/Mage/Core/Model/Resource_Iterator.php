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
 * @package    Mage_Core
 * @copyright  Copyright (c) 2008 Irubin Consulting Inc. DBA Varien (http://www.varien.com)
 * @license    http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */

/**
 * Active record implementation
 *
 * @author      Magento Core Team <core@magentocommerce.com>
 */
class Mage_Core_Model_Resource_Iterator extends Varien_Object
{
    /**
     * Walk over records fetched from query one by one using callback function
     *
     * @param Zend_Db_Statement_Interface|Zend_Db_Select|string $query
     * @param array|string $callback
     * @param array $args
     * @return Mage_Core_Model_Resource_Activerecord
     */
    public function walk($query, array $callbacks, array $args=array())
    {
        $stmt = $this->_getStatement($query);

        $args['idx'] = 0;
        while ($row = $stmt->fetch()) {
            $args['row'] = $row;
            foreach ($callbacks as $callback) {
                $result = call_user_func($callback, $args);
                if (!empty($result)) {
                    $args = array_merge($args, $result);
                }
            }
            $args['idx']++;
        }

        return $this;
    }

    /**
     * Fetch Zend statement instance
     *
     * @param Zend_Db_Statement_Interface|Zend_Db_Select|string $query
     * @param Zend_Db_Adapter_Abstract $conn
     * @return Zend_Db_Statement_Interface
     */
    protected function _getStatement($query, $conn=null)
    {
        if ($query instanceof Zend_Db_Statement_Interface) {
            return $query;
        }

        if ($query instanceof Zend_Db_Select) {
            return $query->query();
        }

        $hlp = Mage::helper('core');

        if (is_string($query)) {
            if (!$conn instanceof Zend_Db_Adapter_Abstract) {
                Mage::throwException($hlp->__('Invalid connection'));
            }
            return $conn->query($query);
        }

        Mage::throwException($hlp->__('Invalid query'));
    }
}