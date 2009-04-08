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
 * Catalog product option select type
 *
 * @category   Mage
 * @package    Mage_Catalog
 * @author     Magento Core Team <core@magentocommerce.com>
 */
class Mage_Catalog_Model_Product_Option_Type_Select extends Mage_Catalog_Model_Product_Option_Type_Default
{
    /**
     * Validate user input for option
     *
     * @throws Mage_Core_Exception
     * @param array $values All product option values, i.e. array (option_id => mixed, option_id => mixed...)
     * @return Mage_Catalog_Model_Product_Option_Type_Default
     */
    public function validateUserValue($values)
    {
        parent::validateUserValue($values);

        $option = $this->getOption();
        $value = $this->getUserValue();

        if (empty($value) && $option->getIsRequire() && !$this->getProduct()->getSkipCheckRequiredOption()) {
            $this->setIsValid(false);
            Mage::throwException(Mage::helper('catalog')->__('Please specify the product required option(s)'));
        }
        if (!$this->_isSingleSelection()) {
            $valuesCollection = $option->getOptionValuesByOptionId($value, $this->getProduct()->getStoreId())
                ->load();
            if ($valuesCollection->count() != count($value)) {
                $this->setIsValid(false);
                Mage::throwException(Mage::helper('catalog')->__('Please specify the product required option(s)'));
            }
        }
        return $this;
    }

    /**
     * Prepare option value for cart
     *
     * @throws Mage_Core_Exception
     * @return mixed Prepared option value
     */
    public function prepareForCart()
    {
        if ($this->getIsValid()) {
            return is_array($this->getUserValue()) ? implode(',', $this->getUserValue()) : $this->getUserValue();
        }
        Mage::throwException(Mage::helper('catalog')->__('Option validation failed to add product to cart'));
    }

    /**
     * Return formatted option value for quote option
     *
     * @param string $optionValue Prepared for cart option value
     * @return string
     */
    public function getFormattedOptionValue($optionValue)
    {
        $result = $this->getEditableOptionValue($optionValue);
        return Mage::helper('core')->htmlEscape($result);
    }

    /**
     * Return formatted option value ready to edit, ready to parse
     *
     * @param string $optionValue Prepared for cart option value
     * @return string
     */
    public function getEditableOptionValue($optionValue)
    {
        $option = $this->getOption();
        $result = '';
        if (!$this->_isSingleSelection()) {
            foreach (explode(',', $optionValue) as $_value) {
                $result .= $option->getValueById($_value)->getTitle() . ', ';
            }
            $result = Mage::helper('core/string')->substr($result, 0, -2);
        } elseif ($this->_isSingleSelection()) {
            $result = $option->getValueById($optionValue)->getTitle();
        } else {
            $result = $optionValue;
        }
        return $result;
    }

    /**
     * Parse user input value and return cart prepared value, i.e. "one, two" => "1,2"
     *
     * @param string $optionValue
     * @param array $productOptionValues Values for product option
     * @return string|null
     */
    public function parseOptionValue($optionValue, $productOptionValues)
    {
        $_values = array();
        if (!$this->_isSingleSelection()) {
            foreach (explode(',', $optionValue) as $_value) {
                $_value = trim($_value);
                if (array_key_exists($_value, $productOptionValues)) {
                    $_values[] = $productOptionValues[$_value];
                }
            }
        } elseif ($this->_isSingleSelection() && array_key_exists($optionValue, $productOptionValues)) {
            $_values[] = $productOptionValues[$optionValue];
        }
        if (count($_values)) {
            return implode(',', $_values);
        } else {
            return null;
        }
    }

    /**
     * Prepare option value for info buy request
     *
     * @param string $optionValue
     * @return mixed
     */
    public function prepareOptionValueForRequest($optionValue)
    {
        if (!$this->_isSingleSelection()) {
            return explode(',', $optionValue);
        }
        return $optionValue;
    }

    /**
     * Return Price for selected option
     *
     * @param string $optionValue Prepared for cart option value
     * @return float
     */
    public function getOptionPrice($optionValue, $basePrice)
    {
        $option = $this->getOption();
        $result = 0;

        if (!$this->_isSingleSelection()) {
            foreach(explode(',', $optionValue) as $value) {
                $result += $this->_getChargableOptionPrice(
                    $option->getValueById($value)->getPrice(),
                    $option->getValueById($value)->getPriceType() == 'percent',
                    $basePrice
                );
            }
        } elseif ($this->_isSingleSelection()) {
            $result = $this->_getChargableOptionPrice(
                $option->getValueById($optionValue)->getPrice(),
                $option->getValueById($optionValue)->getPriceType() == 'percent',
                $basePrice
            );
        }

        return $result;
    }

    /**
     * Return SKU for selected option
     *
     * @param string $optionValue Prepared for cart option value
     * @param string $skuDelimiter Delimiter for Sku parts
     * @return string
     */
    public function getOptionSku($optionValue, $skuDelimiter)
    {
        $option = $this->getOption();

        if (!$this->_isSingleSelection()) {
            $skus = array();
            foreach(explode(',', $optionValue) as $value) {
                if ($optionSku = $option->getValueById($value)->getSku()) {
                    $skus[] = $optionSku;
                }
            }
            $result = implode($skuDelimiter, $skus);
        } elseif ($this->_isSingleSelection()) {
            $result = $option->getValueById($optionValue)->getSku();
        } else {
            $result = parent::getOptionSku($optionValue, $skuDelimiter);
        }

        return $result;
    }

    /**
     * Check if option has single or multiple values selection
     *
     * @return boolean
     */
    protected function _isSingleSelection()
    {
        $_single = array(
            Mage_Catalog_Model_Product_Option::OPTION_TYPE_DROP_DOWN,
            Mage_Catalog_Model_Product_Option::OPTION_TYPE_RADIO
        );
        return in_array($this->getOption()->getType(), $_single);
    }
}