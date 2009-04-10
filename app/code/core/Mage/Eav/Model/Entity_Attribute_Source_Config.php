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
 * @package    Mage_Eav
 * @copyright  Copyright (c) 2008 Irubin Consulting Inc. DBA Varien (http://www.varien.com)
 * @license    http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */


/**
 * Entity/Attribute/Model - attribute selection source from configuration
 *
 * this class should be abstract, but kept usual for legacy purposes
 *
 * @category   Mage
 * @package    Mage_Eav
 * @author     Magento Core Team <core@magentocommerce.com>
 */
class Mage_Eav_Model_Entity_Attribute_Source_Config extends Mage_Eav_Model_Entity_Attribute_Source_Abstract
{
    protected $_configNodePath;

    /**
     * Retrieve all options for the source from configuration
     *
     * @return array
     */
    public function getAllOptions()
    {
        if (is_null($this->_options)) {
            $this->_options = array();
            if ($this->_configNodePath) {
                $rootNode = AO::getConfig()->getNode($this->_configNodePath);
            }
            if (!$rootNode) {
                throw AO::exception('Mage_Eav', AO::helper('eav')->__('Failed to load node %s from config.', $this->_configNodePath));
            }
            $options = $rootNode->children();
            if (empty($options)) {
                throw AO::exception('Mage_Eav', AO::helper('eav')->__('No options found in config node %s', $this->_configNodePath));
            }
            foreach ($options as $option) {
                $this->_options[] = array(
                    'value' => (string)$option->value,
                    'label' => (string)$option->label
                );
            }
        }

        return $this->_options;
    }
}
