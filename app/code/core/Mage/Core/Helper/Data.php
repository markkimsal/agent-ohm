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
 * Core data helper
 *
 * @author      Magento Core Team <core@magentocommerce.com>
 */
class Mage_Core_Helper_Data extends Mage_Core_Helper_Abstract
{
    /**
     * @var Mage_Core_Model_Encryption
     */
    protected $_encryptor = null;

    /**
     * @return Mage_Core_Model_Encryption
     */
    public function getEncryptor()
    {
        if ($this->_encryptor === null) {
            $encryptionModel = (string)Mage::getConfig()->getNode('global/helpers/core/encryption_model');
            if ($encryptionModel) {
                $this->_encryptor = new $encryptionModel;
            } else {
                $this->_encryptor = Mage::getModel('core/encryption');
            }

            $this->_encryptor->setHelper($this);
        }
        return $this->_encryptor;
    }

    /**
     * Convert and format price value for current application store
     *
     * @param   float $value
     * @param   bool $format
     * @param   bool $includeContainer
     * @return  mixed
     */
    public static function currency($value, $format=true, $includeContainer = true)
    {
        try {
            $value = Mage::app()->getStore()->convertPrice($value, $format, $includeContainer);
        }
        catch (Exception $e){
            $value = $e->getMessage();
        }
        return $value;
    }

    /**
     * Format and convert currency using current store option
     *
     * @param   float $value
     * @param   bool $includeContainer
     * @return  string
     */
    public function formatCurrency($value, $includeContainer = true)
    {
        return $this->currency($value, true, $includeContainer);
    }

    /**
     * Formats price
     *
     * @param float $price
     * @param bool $includeContainer
     * @return string
     */
    public function formatPrice($price, $includeContainer = true)
    {
        return Mage::app()->getStore()->formatPrice($price, $includeContainer);
    }

    /**
     * Format date using current locale options
     *
     * @param   date|Zend_Date|null $date in GMT timezone
     * @param   string $format
     * @param   bool $showTime
     * @return  string
     */
    public function formatDate($date=null, $format='short', $showTime=false)
    {
        if (Mage_Core_Model_Locale::FORMAT_TYPE_FULL    !==$format &&
            Mage_Core_Model_Locale::FORMAT_TYPE_LONG    !==$format &&
            Mage_Core_Model_Locale::FORMAT_TYPE_MEDIUM  !==$format &&
            Mage_Core_Model_Locale::FORMAT_TYPE_SHORT   !==$format) {
            return $date;
        }
        if (!($date instanceof Zend_Date) && $date && !strtotime($date)) {
            return '';
        }
        if (is_null($date)) {
            $date = Mage::app()->getLocale()->date(Mage::getSingleton('core/date')->gmtTimestamp(), null, null);
        }
        elseif (!$date instanceof Zend_Date) {
            $date = Mage::app()->getLocale()->date(strtotime($date), null, null, $showTime);
        }

        if ($showTime) {
            $format = Mage::app()->getLocale()->getDateTimeFormat($format);
        }
        else {
            $format = Mage::app()->getLocale()->getDateFormat($format);
        }

        return $date->toString($format);
    }

    /**
     * Format time using current locale options
     *
     * @param   date|Zend_Date|null $time
     * @param   string $format
     * @param   bool $showTime
     * @return  string
     */
    public function formatTime($time=null, $format='short', $showDate=false)
    {
        if (Mage_Core_Model_Locale::FORMAT_TYPE_FULL    !==$format &&
            Mage_Core_Model_Locale::FORMAT_TYPE_LONG    !==$format &&
            Mage_Core_Model_Locale::FORMAT_TYPE_MEDIUM  !==$format &&
            Mage_Core_Model_Locale::FORMAT_TYPE_SHORT   !==$format) {
            return $time;
        }

        if (is_null($time)) {
            $date = Mage::app()->getLocale()->date(time());
        }
        elseif ($time instanceof Zend_Date) {
            $date = $time;
        }
        else {
            $date = Mage::app()->getLocale()->date(strtotime($time));
        }

        if ($showDate) {
            $format = Mage::app()->getLocale()->getDateTimeFormat($format);
        }
        else {
            $format = Mage::app()->getLocale()->getTimeFormat($format);
        }

        return $date->toString($format);
    }

    /**
     * Encrypt data using application key
     *
     * @param   string $data
     * @return  string
     */
    public function encrypt($data)
    {
        if (!Mage::isInstalled()) {
            return $data;
        }
        return $this->getEncryptor()->encrypt($data);
    }

    /**
     * Decrypt data using application key
     *
     * @param   string $data
     * @return  string
     */
    public function decrypt($data)
    {
        if (!Mage::isInstalled()) {
            return $data;
        }
        return $this->getEncryptor()->decrypt($data);
    }

    public function validateKey($key)
    {
        return $this->getEncryptor()->validateKey($key);
    }

    public function getRandomString($len, $chars=null)
    {
        if (is_null($chars)) {
            $chars = "abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789";
        }
        mt_srand(10000000*(double)microtime());
        for ($i = 0, $str = '', $lc = strlen($chars)-1; $i < $len; $i++) {
            $str .= $chars[mt_rand(0, $lc)];
        }
        return $str;
    }

    /**
     * Generate salted hash from password
     *
     * @param string $password
     * @param string|integer|boolean $salt
     */
    public function getHash($password, $salt = false)
    {
        return $this->getEncryptor()->getHash($password, $salt);
    }

    public function validateHash($password, $hash)
    {
        return $this->getEncryptor()->validateHash($password, $hash);
    }

    /**
     * Retrieve store identifier
     *
     * @param   mixed $store
     * @return  int
     */
    public function getStoreId($store=null)
    {
        return Mage::app()->getStore($store)->getId();
    }

    public function removeAccents($string, $german=false)
    {
        static $replacements;

        if (empty($replacements[$german])) {
            $subst = array(
                // single ISO-8859-1 letters
                192=>'A', 193=>'A', 194=>'A', 195=>'A', 196=>'A', 197=>'A', 199=>'C', 208=>'D', 200=>'E', 201=>'E', 202=>'E', 203=>'E', 204=>'I', 205=>'I', 206=>'I', 207=>'I', 209=>'N', 210=>'O', 211=>'O', 212=>'O', 213=>'O', 214=>'O', 216=>'O', 138=>'S', 217=>'U', 218=>'U', 219=>'U', 220=>'U', 221=>'Y', 142=>'Z', 224=>'a', 225=>'a', 226=>'a', 227=>'a', 228=>'a', 229=>'a', 231=>'c', 232=>'e', 233=>'e', 234=>'e', 235=>'e', 236=>'i', 237=>'i', 238=>'i', 239=>'i', 241=>'n', 240=>'o', 242=>'o', 243=>'o', 244=>'o', 245=>'o', 246=>'o', 248=>'o', 154=>'s', 249=>'u', 250=>'u', 251=>'u', 252=>'u', 253=>'y', 255=>'y', 158=>'z',
                // HTML entities
                258=>'A', 260=>'A', 262=>'C', 268=>'C', 270=>'D', 272=>'D', 280=>'E', 282=>'E', 286=>'G', 304=>'I', 313=>'L', 317=>'L', 321=>'L', 323=>'N', 327=>'N', 336=>'O', 340=>'R', 344=>'R', 346=>'S', 350=>'S', 354=>'T', 356=>'T', 366=>'U', 368=>'U', 377=>'Z', 379=>'Z', 259=>'a', 261=>'a', 263=>'c', 269=>'c', 271=>'d', 273=>'d', 281=>'e', 283=>'e', 287=>'g', 305=>'i', 322=>'l', 314=>'l', 318=>'l', 324=>'n', 328=>'n', 337=>'o', 341=>'r', 345=>'r', 347=>'s', 351=>'s', 357=>'t', 355=>'t', 367=>'u', 369=>'u', 378=>'z', 380=>'z',
                // ligatures
                198=>'Ae', 230=>'ae', 140=>'Oe', 156=>'oe', 223=>'ss',
            );

            if ($german) {
                // umlauts
                $subst = array_merge($subst, array(196=>'Ae', 228=>'ae', 214=>'Oe', 246=>'oe', 220=>'Ue', 252=>'ue'));
            }

            $replacements[$german] = array();
            foreach ($subst as $k=>$v) {
                $replacements[$german][$k<256 ? chr($k) : '&#'.$k.';'] = $v;
            }
        }

        // convert string from default database format (UTF-8)
        // to encoding which replacement arrays made with (ISO-8859-1)
        if ($s = @iconv('UTF-8', 'ISO-8859-1', $string)) {
            $string = $s;
        }

        // Replace
        $string = strtr($string, $replacements[$german]);

        return $string;
    }

    public function isDevAllowed($storeId=null)
    {
        $allow = true;

        $allowedIps = Mage::getStoreConfig('dev/restrict/allow_ips', $storeId);
        if (!empty($allowedIps) && isset($_SERVER['REMOTE_ADDR'])) {
            $allowedIps = preg_split('#\s*,\s*#', $allowedIps, null, PREG_SPLIT_NO_EMPTY);
            if (array_search($_SERVER['REMOTE_ADDR'], $allowedIps)===false
                && array_search($_SERVER['HTTP_HOST'], $allowedIps)===false) {
                $allow = false;
            }
        }

        return $allow;
    }

    /**
     * Get information about available cache types
     *
     * @return array
     */
    public function getCacheTypes()
    {
        $types = array();
        $config = Mage::getConfig()->getNode('global/cache/types');
        foreach ($config->children() as $type=>$node) {
            $types[$type] = (string)$node->label;
        }
        return $types;
    }

    /**
     * Get information about available cache beta types
     *
     * @return array
     */
    public function getCacheBetaTypes()
    {
        $types = array();
        $config = Mage::getConfig()->getNode('global/cache/betatypes');
        foreach ($config->children() as $type=>$node) {
            $types[$type] = (string)$node->label;
        }
        return $types;
    }

    /**
     * Copy data from object|array to object|array containing fields
     * from fieldset matching an aspect.
     *
     * Contents of $aspect are a field name in target object or array.
     * If '*' - will be used the same name as in the source object or array.
     *
     * @param string $fieldset
     * @param string $aspect
     * @param array|Varien_Object $source
     * @param array|Varien_Object $target
     * @param string $root
     * @return boolean
     */
    public function copyFieldset($fieldset, $aspect, $source, $target, $root='global')
    {
        if (!(is_array($source) || $source instanceof Varien_Object)
            || !(is_array($target) || $target instanceof Varien_Object)) {

            return false;
        }
        $fields = Mage::getConfig()->getFieldset($fieldset, $root);
        if (!$fields) {
            return false;
        }

        $sourceIsArray = is_array($source);
        $targetIsArray = is_array($target);

        $result = false;
        foreach ($fields as $code=>$node) {
            if (empty($node->$aspect)) {
                continue;
            }

            if ($sourceIsArray) {
                $value = isset($source[$code]) ? $source[$code] : null;
            } else {
                $value = $source->getDataUsingMethod($code);
            }

            $targetCode = (string)$node->$aspect;
            $targetCode = $targetCode == '*' ? $code : $targetCode;

            if ($targetIsArray) {
                $target[$targetCode] = $value;
            } else {
                $target->setDataUsingMethod($targetCode, $value);
            }

            $result = true;
        }

        return $result;
    }

    /**
     * Decorate a plain array of arrays or objects
     * The array actually can be an object with Iterator interface
     *
     * Keys with prefix_* will be set:
     * *_is_first - if the element is first
     * *_is_odd / *_is_even - for odd/even elements
     * *_is_last - if the element is last
     *
     * The respective key/attribute will be set to element, depending on object it is or array.
     * Varien_Object is supported.
     *
     * $forceSetAll true will cause to set all possible values for all elements.
     * When false (default), only non-empty values will be set.
     *
     * @param mixed $array
     * @param string $prefix
     * @param bool $forceSetAll
     * @return mixed
     */
    public function decorateArray($array, $prefix = 'decorated_', $forceSetAll = false)
    {
        // check if array or an object to be iterated given
        if (!(is_array($array) || is_object($array))) {
            return $array;
        }

        $keyIsFirst = "{$prefix}is_first";
        $keyIsOdd   = "{$prefix}is_odd";
        $keyIsEven  = "{$prefix}is_even";
        $keyIsLast  = "{$prefix}is_last";

        $count  = count($array); // this will force Iterator to load
        $i      = 0;
        $isEven = false;
        foreach ($array as $key => $element) {
            if (is_object($element)) {
                $this->_decorateArrayObject($element, $keyIsFirst, (0 === $i), $forceSetAll || (0 === $i));
                $this->_decorateArrayObject($element, $keyIsOdd, !$isEven, $forceSetAll || !$isEven);
                $this->_decorateArrayObject($element, $keyIsEven, $isEven, $forceSetAll || $isEven);
                $isEven = !$isEven;
                $i++;
                $this->_decorateArrayObject($element, $keyIsLast, ($i === $count), $forceSetAll || ($i === $count));
            }
            elseif (is_array($element)) {
                if ($forceSetAll || (0 === $i)) {
                    $array[$key][$keyIsFirst] = (0 === $i);
                }
                if ($forceSetAll || !$isEven) {
                    $array[$key][$keyIsOdd] = !$isEven;
                }
                if ($forceSetAll || $isEven) {
                    $array[$key][$keyIsEven] = $isEven;
                }
                $isEven = !$isEven;
                $i++;
                if ($forceSetAll || ($i === $count)) {
                    $array[$key][$keyIsLast] = ($i === $count);
                }
            }
        }

        return $array;
    }

    private function _decorateArrayObject($element, $key, $value, $dontSkip) {
        if ($dontSkip) {
            if ($element instanceof Varien_Object) {
                $element->setData($key, $value);
            }
            else {
                $element->$key = $value;
            }
        }
    }

    /**
     * Transform an assoc array to SimpleXMLElement object
     * Array has some limitations. Appropriate exceptions will be thrown
     *
     * @param array $array
     * @param string $rootName
     * @return SimpleXMLElement
     * @throws Exception
     */
    public function assocToXml(array $array, $rootName = '_')
    {
        if (empty($rootName) || is_numeric($rootName)) {
            throw new Exception('Root element must not be empty or numeric');
        }

        $xmlstr = <<<XML
<?xml version='1.0' encoding='UTF-8' standalone='yes'?>
<$rootName></$rootName>
XML;
        $xml = new SimpleXMLElement($xmlstr);
        foreach ($array as $key => $value) {
            if (is_numeric($key)) {
                throw new Exception('Array root keys must not be numeric.');
            }
        }
        return self::_assocToXml($array, $rootName, $xml);
    }

    /**
     * Function, that actually recursively transforms array to xml
     *
     * @param array $array
     * @param string $rootName
     * @param SimpleXMLElement $xml
     * @return SimpleXMLElement
     * @throws Exception
     */
    private function _assocToXml(array $array, $rootName, SimpleXMLElement &$xml)
    {
        $hasNumericKey = false;
        $hasStringKey  = false;
        foreach ($array as $key => $value) {
            if (!is_array($value)) {
                if (is_string($key)) {
                    if ($key === $rootName) {
                        throw new Exception('Associative key must not be the same as its parent associative key.');
                    }
                    $hasStringKey = true;
                    $xml->$key = $value;
                }
                elseif (is_int($key)) {
                    $hasNumericKey = true;
                    $xml->{$rootName}[$key] = $value;
                }
            }
            else {
                self::_assocToXml($value, $key, $xml->$key);
            }
        }
        if ($hasNumericKey && $hasStringKey) {
            throw new Exception('Associative and numeric keys must not be mixed at one level.');
        }
        return $xml;
    }

    /**
     * Transform SimpleXMLElement to associative array
     * SimpleXMLElement must be conform structure, generated by assocToXml()
     *
     * @param SimpleXMLElement $xml
     * @return array
     */
    public function xmlToAssoc(SimpleXMLElement $xml)
    {
        $array = array();
        foreach ($xml as $key => $value) {
            if (isset($value->$key)) {
                $i = 0;
                foreach ($value->$key as $v) {
                    $array[$key][$i++] = (string)$v;
                }
            }
            else {
                // try to transform it into string value, trimming spaces between elements
                $array[$key] = trim((string)$value);
                if (empty($array[$key]) && !empty($value)) {
                    $array[$key] = self::xmlToAssoc($value);
                }
                // untrim strings values
                else {
                    $array[$key] = (string)$value;
                }
            }
        }
        return $array;
    }
}