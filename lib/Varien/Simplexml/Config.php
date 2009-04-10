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
 * @category   Varien
 * @package    Varien_Simplexml
 * @copyright  Copyright (c) 2008 Irubin Consulting Inc. DBA Varien (http://www.varien.com)
 * @license    http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 *
 * @ao-modified
 * @ao-copyright 2009 Mark Kimsal
 */


/**
 * Base class for simplexml based configurations
 *
 * @category   Varien
 * @package    Varien_Simplexml
 * @author      Magento Core Team <core@magentocommerce.com>
 */
class Varien_Simplexml_Config
{

    /**
     * Configuration xml
     *
     * @var Varien_Simplexml_Element
     */
    public $_xml = null;

    /**
     * Enter description here...
     *
     * @var string
     */
    protected $_cacheId = null;

    /**
     * Enter description here...
     *
     * @var array
     */
    protected $_cacheTags = array();

    /**
     * Enter description here...
     *
     * @var int
     */
    protected $_cacheLifetime = null;

    /**
     * Enter description here...
     *
     * @var unknown_type
     */
    protected $_cacheChecksum = false;

    /**
     * Enter description here...
     *
     * @var boolean
     */
    protected $_cacheSaved = false;

    /**
     * Cache resource object
     *
     * @var Varien_Simplexml_Config_Cache_Abstract
     */
    protected $_cache = null;

    /**
     * Class name of simplexml elements for this configuration
     *
     * @var string
     */
    protected $_elementClass = 'Varien_Simplexml_Element';

    /**
     * Xpath describing nodes in configuration that need to be extended
     *
     * @example <allResources extends="/config/modules//resource"/>
     */
    protected $_xpathExtends = "//*[@extends]";

    /**
     * Constructor
     *
     * Initializes XML for this configuration
     *
     * @see self::setXml
     * @param string|Varien_Simplexml_Element $sourceData
     * @param string $sourceType
     */
    public function __construct($sourceData=null) {
        if (is_null($sourceData)) {
            return;
        }
        if ($sourceData instanceof SimplexmlElement) {
           $this->setXml($sourceData);
        } elseif (is_string($sourceData) && !empty($sourceData)) {
            if (strlen($sourceData)<1000 && is_readable($sourceData)) {
                $this->loadFile($sourceData);
            } else {
                $this->loadString($sourceData);
            }
        }
        #$this->setCache(new Varien_Simplexml_Config_Cache_File());
        #$this->getCache()->setConfig($this);
    }

    /**
     * Sets xml for this configuration
     *
     * @param Varien_Simplexml_Element $sourceData
     * @return Varien_Simplexml_Config
     */
    public function setXml(SimplexmlElement $node)
    {
        $this->_xml = $node;
        return $this;
    }

    /**
     * Returns node found by the $path
     *
     * @see     Varien_Simplexml_Element::descend
     * @param   string $path
     * @return  Varien_Simplexml_Element
     */
    public function getNode($path=null)
    {
		if ($path === null)
            return $this->_xml;

		return $this->descend($this->_xml, $path);
		/*
		if (!isset($answer[0])) {
			return false;
		}
		return $answer[0];
		 */
    }

    /**
     * Returns nodes found by xpath expression
     *
     * @param string $xpath
     * @return array
     */
    public function getXpath($xpath)
    {
        if (empty($this->_xml)) {
            return false;
        }

        if (!$result = @$this->_xml->xpath($xpath)) {
            return false;
        }

        return $result;
    }

    /**
     * Enter description here...
     *
     * @param Varien_Simplexml_Config_Cache_Abstract $cache
     * @return Varien_Simplexml_Config
     */
    public function setCache($cache)
    {
        $this->_cache = $cache;
        return $this;
    }

    /**
     * Enter description here...
     *
     * @return Varien_Simplexml_Config_Cache_Abstract
     */
    public function getCache()
    {
        return $this->_cache;
    }

    /**
     * Enter description here...
     *
     * @param boolean $flag
     * @return Varien_Simplexml_Config
     */
    public function setCacheSaved($flag)
    {
        $this->_cacheSaved = $flag;
        return $this;
    }

    /**
     * Enter description here...
     *
     * @return boolean
     */
    public function getCacheSaved()
    {
        return $this->_cacheSaved;
    }

    /**
     * Enter description here...
     *
     * @param string $id
     * @return Varien_Simplexml_Config
     */
    public function setCacheId($id)
    {
        $this->_cacheId = $id;
        return $this;
    }

    /**
     * Enter description here...
     *
     * @return string
     */
    public function getCacheId()
    {
        return $this->_cacheId;
    }

    /**
     * Enter description here...
     *
     * @param array $tags
     * @return Varien_Simplexml_Config
     */
    public function setCacheTags($tags)
    {
        $this->_cacheTags = $tags;
        return $this;
    }

    /**
     * Enter description here...
     *
     * @return array
     */
    public function getCacheTags()
    {
        return $this->_cacheTags;
    }

    /**
     * Enter description here...
     *
     * @param int $lifetime
     * @return Varien_Simplexml_Config
     */
    public function setCacheLifetime($lifetime)
    {
        $this->_cacheLifetime = $lifetime;
        return $this;
    }

    /**
     * Enter description here...
     *
     * @return int
     */
    public function getCacheLifetime()
    {
        return $this->_cacheLifetime;
    }

    /**
     * Enter description here...
     *
     * @param string $data
     * @return Varien_Simplexml_Config
     */
    public function setCacheChecksum($data)
    {
        if (is_null($data)) {
            $this->_cacheChecksum = null;
        } elseif (false===$data || 0===$data) {
            $this->_cacheChecksum = false;
        } else {
            $this->_cacheChecksum = md5($data);
        }
        return $this;
    }

    /**
     * Enter description here...
     *
     * @param string $data
     * @return Varien_Simplexml_Config
     */
    public function updateCacheChecksum($data)
    {
        if (false===$this->getCacheChecksum()) {
            return $this;
        }
        if (false===$data || 0===$data) {
            $this->_cacheChecksum = false;
        } else {
            $this->setCacheChecksum($this->getCacheChecksum().':'.$data);
        }
        return $this;
    }

    /**
     * Enter description here...
     *
     * @return string
     */
    public function getCacheChecksum()
    {
        return $this->_cacheChecksum;
    }

    /**
     * Enter description here...
     *
     * @return string
     */
    public function getCacheChecksumId()
    {
        return $this->getCacheId().'__CHECKSUM';
    }

    /**
     * Enter description here...
     *
     * @return boolean
     */
    public function fetchCacheChecksum()
    {
        return false;
    }

    /**
     * Enter description here...
     *
     * @return boolean
     */
    public function validateCacheChecksum()
    {
        $newChecksum = $this->getCacheChecksum();
        if (false===$newChecksum) {
            return false;
        }
        if (is_null($newChecksum)) {
            return true;
        }
        $cachedChecksum = $this->getCache()->load($this->getCacheChecksumId());
        return $newChecksum===false && $cachedChecksum===false || $newChecksum===$cachedChecksum;
    }

    /**
     * Enter description here...
     *
     * @return boolean
     */
    public function loadCache()
    {
        if (!$this->validateCacheChecksum()) {
            return false;
        }

        $xmlString = $this->_loadCache($this->getCacheId());
//        $xml = simplexml_load_string($xmlString, $this->_elementClass);
        $xml = simplexml_load_string($xmlString);
        if ($xml) {
            $this->_xml = $xml;
            $this->setCacheSaved(true);
            return true;
        }

        return false;
    }

    /**
     * Enter description here...
     *
     * @param array $tags
     * @return Varien_Simplexml_Config
     */
    public function saveCache($tags=null)
    {
        if ($this->getCacheSaved()) {
            return $this;
        }
        if (false===$this->getCacheChecksum()) {
            return $this;
        }

        if (is_null($tags)) {
            $tags = $this->_cacheTags;
        }

        if (!is_null($this->getCacheChecksum())) {
            $this->_saveCache($this->getCacheChecksum(), $this->getCacheChecksumId(), $tags, $this->getCacheLifetime());
        }

        $xmlString = $this->asNiceXml($this->_xml, '', false);
        $this->_saveCache($xmlString, $this->getCacheId(), $tags, $this->getCacheLifetime());

        $this->setCacheSaved(true);

        return $this;
    }

    /**
     * Enter description here...
     *
     * @return Varien_Simplexml_Config
     */
    public function removeCache()
    {
        $this->_removeCache($this->getCacheId());
        $this->_removeCache($this->getCacheChecksumId());
        return $this;
    }

    /**
     * Enter description here...
     *
     * @param string $id
     * @return boolean
     */
    protected function _loadCache($id)
    {
        return $this->getCache()->load($id);
    }

    /**
     * Enter description here...
     *
     * @param string $data
     * @param string $id
     * @param array $tags
     * @param int|boolean $lifetime
     * @return boolean
     */
    protected function _saveCache($data, $id, $tags=array(), $lifetime=false)
    {
        return $this->getCache()->save($data, $id, $tags, $lifetime);
    }

    /**
     * Enter description here...
     *
     * @todo check this, as there are no caches that implement remove() method
     * @param string $id
     * @return unknown
     */
    protected function _removeCache($id)
    {
        return $this->getCache()->remove($id);
    }

    /**
     * Imports XML file
     *
     * @param string $filePath
     * @return Varien_Simplexml_Element
     */
    public function loadFile($filePath)
    {
        if (!is_readable($filePath)) {
            //throw new Exception('Can not read xml file '.$filePath);
            return false;
        }

        $fileData = file_get_contents($filePath);
        $fileData = $this->processFileData($fileData);
//		die('loadString param error in '.__FILE__);
        return $this->loadString($fileData);
    }

    /**
     * Imports XML string
     *
     * @param string $string
     * @return Varien_Simplexml_Element
     */
    public function loadString($string)
    {
        if (!empty($string)) {
            //$xml = simplexml_load_string($string, $this->_elementClass);
            $xml = simplexml_load_string($string);
        }
        else {
            throw new Exception('"$string" parameter for simplexml_load_string is empty');
        }

        if ($xml instanceof SimpleXMLElement) {
            $this->_xml = $xml;
            return true;
        }

        return false;
    }

    /**
     * Imports DOM node
     *
     * @param DOMNode $dom
     * @return Varien_Simplexml_Element
     */
    public function loadDom($dom)
    {
//        $xml = simplexml_import_dom($dom, $this->_elementClass);
        $xml = simplexml_import_dom($dom);

        if ($xml) {
            $this->_xml = $xml;
            return true;
        }

        return false;
    }

    /**
     * Create node by $path and set its value.
     *
     * @param string $path separated by slashes
     * @param string $value
     * @param boolean $overwrite
     * @return Varien_Simplexml_Config
     */
    public function setNode($path, $value, $overwrite=true)
    {
        $xml = $this->setNodeNode($this->_xml, $path, $value, $overwrite);
        return $this;
    }

    /**
     * Process configuration xml
     *
     * @return Varien_Simplexml_Config
     */
    public function applyExtends()
    {
        $targets = $this->getXpath($this->_xpathExtends);
        if (!$targets) {
            return $this;
        }

        foreach ($targets as $target) {
            $sources = $this->getXpath((string)$target['extends']);
            if ($sources) {
                foreach ($sources as $source) {
                    $target->extend($source);
                }
            } else {
                #echo "Not found extend: ".(string)$target['extends'];
            }
            #unset($target['extends']);
        }
        return $this;
    }

    /**
     * Stub method for processing file data right after loading the file text
     *
     * @param string $text
     * @return string
     */
    public function processFileData($text)
    {
        return $text;
    }

    /**
     * Enter description here...
     *
     * @param Varien_Simplexml_Config $config
     * @param boolean $overwrite
     * @return Varien_Simplexml_Config
     */
    public function extend(Varien_Simplexml_Config $config, $overwrite=true)
    {
        $this->extendNode($this->getNode(), $config->getNode(), $overwrite);
        return $this;
    }



    /**
     * Find a descendant of a node by path
     *
     * @todo    Do we need to make it xpath look-a-like?
     * @todo    param string $path Subset of xpath. Example: "child/grand[@attrName='attrValue']/subGrand"
     * @param   string $path Example: "child/grand@attrName=attrValue/subGrand" (to make it faster without regex)
     * @return  Varien_Simplexml_Element
     */
    public function descend($node, $path)
    {
        #$node = $this->xpath($path);
        #return $node[0];
        if (is_array($path)) {
            $pathArr = $path;
        } else {
            $pathArr = explode('/', $path);
        }
        try {
            //short cut 1,2,3 depth searches, they are the most common
			// Only shortcut 1 and 2, there are too many 3 deep paths
			// which don't exist, resulting in an exception, which slows
			// down the site.
            $c = count($pathArr);
            switch ($c) {
                case 1:
                return $node->{$pathArr[0]};
                case 2:
                return $node->{$pathArr[0]}->{$pathArr[1]};
            }
        } catch (Exception $e) {
            return false;
        }


        $desc = $node;
        foreach ($pathArr as $nodeName) {
			//I haven't evern seen this XSL like syntax in use.
			//  it's probably not been tested, it probably doesn't work.
            /*
            if (strpos($nodeName, '@')!==false) {
                $a = explode('@', $nodeName);
                $b = explode('=', $a[1]);
                $nodeName = $a[0];
                $attributeName = $b[0];
                $attributeValue = $b[1];
                $found = false;
                foreach ($node->$nodeName as $desc) {
                    if ((string)$nodeChild[$attributeName]===$attributeValue) {
                        $found = true;
                        break;
                    }
                }
                if (!$found) {
                    $desc = false;
                }
            } else {
                $desc = $desc->$nodeName;
            }
             */
            $desc = $desc->$nodeName;
            if (!$desc) {
                return false;
            }
        }
        return $desc;
    }



    /**
     * Extends current node with xml from $source
     *
     * If $overwrite is false will merge only missing nodes
     * Otherwise will overwrite existing nodes
     *
     * @param Varien_Simplexml_Element $source
     * @param boolean $overwrite
     * @return Varien_Simplexml_Element
     */
    public function extendNode($node, $source, $overwrite=false)
    {
        if (!$source instanceof SimplexmlElement) {
            return $node;
        }

        foreach ($source->children() as $child) {
            $this->extendChild($node, $child, $overwrite);
        }

        return $node;
    }

    /**
     * Extends one node
     *
     * @param Varien_Simplexml_Element $source
     * @param boolean $overwrite
     * @return Varien_Simplexml_Element
     */
    public function extendChild($node, $source, $overwrite=false)
    {
        // this will be our new target node
        $targetChild = null;

        // name of the source node
        $sourceName = $source->getName();

        // here we have children of our source node
        $sourceChildren = $source->children();

        if (!$source->children()) {
            // handle string node
            if (isset($node->$sourceName)) {
                // if target already has children return without regard
                if ($node->$sourceName->children()) {
                    return $node;
                }
                if ($overwrite) {
                    unset($node->$sourceName);
                } else {
                    return $node;
                }
            }

            $targetChild = $node->addChild($sourceName, $this->xmlentities($source));
            foreach ($source->attributes() as $key=>$value) {
                $targetChild->addAttribute($key, $this->xmlentities($value));
            }
            return $node;
        }

        if (isset($node->$sourceName)) {
            $targetChild = $node->$sourceName;
        }

        if (is_null($targetChild)) {
            // if child target is not found create new and descend
            $targetChild = $node->addChild($sourceName);
            foreach ($source->attributes() as $key=>$value) {
                $targetChild->addAttribute($key, $this->xmlentities($value));
            }
        }

        // finally add our source node children to resulting new target node
        foreach ($sourceChildren as $childKey=>$childNode) {
            $this->extendChild($targetChild, $childNode, $overwrite);
        }

        return $node;
    }


    /**
     * Converts meaningful xml characters to xml entities
     *
     * @param  string
     * @return string
     */
    public function xmlentities($n)
    {
        $value = (string)$n;

        $value = str_replace(array('&', '"', "'", '<', '>'), array('&amp;', '&quot;', '&apos;', '&lt;', '&gt;'), $value);

        return $value;
    }


    /**
     * Appends $source to current node
     *
     * @param Varien_Simplexml_Element $source
     * @return Varien_Simplexml_Element
     */
    public function appendChild($n, $source)
    {
        if ($source->children()) {
            /**
             * @see http://bugs.php.net/bug.php?id=41867 , fixed in 5.2.4
             */
            if (version_compare(phpversion(), '5.2.4', '<')===true) {
                $name = $source->children()->getName();
            }
            else {
                $name = $source->getName();
            }
            $child = $n->addChild($name);
        } else {
            $child = $n->addChild($source->getName(), $this->xmlentities($source));
        }

        $attributes = $source->attributes();
        foreach ($attributes as $key=>$value) {
            $child->addAttribute($key, $this->xmlentities($value));
        }

        foreach ($source->children() as $sourceChild) {
            $this->appendChild($child, $sourceChild);
        }
        return $n;
    }

    public function setNodeNode($n, $path, $value, $overwrite=true)
    {
        $arr1 = explode('/', $path);
        $arr = array();
        foreach ($arr1 as $v) {
            if (!empty($v)) $arr[] = $v;
        }
        $last = sizeof($arr)-1;
        $node = $n;
        foreach ($arr as $i=>$nodeName) {
            if ($last===$i) {
                    if (!isset($node->$nodeName) || $overwrite) {
                    // http://bugs.php.net/bug.php?id=36795
                    // comment on [8 Feb 8:09pm UTC]
                    if (isset($node->$nodeName) && (version_compare(phpversion(), '5.2.6', '<')===true)) {
                        $node->$nodeName = $this->xmlentities($value);
                    } else {
                        $node->$nodeName = $value;
                    }
                }
            } else {
                if (!isset($node->$nodeName)) {
                    $node = $node->addChild($nodeName);
                } else {
                    $node = $node->$nodeName;
                }
            }

        }
        return $n;
    }


    /**
     * Makes nicely formatted XML from the node
     *
     * @param string $filename
     * @param int|boolean $level if false
     * @return string
     */
    public function asNiceXml($n, $filename='', $level=0)
    {
        if (is_numeric($level)) {
            $pad = str_pad('', $level*3, ' ', STR_PAD_LEFT);
            $nl = "\n";
        } else {
            $pad = '';
            $nl = '';
        }

        $out = $pad.'<'.$n->getName();

        if ($attributes = $n->attributes()) {
            foreach ($attributes as $key=>$value) {
                $out .= ' '.$key.'="'.str_replace('"', '\"', (string)$value).'"';
            }
        }

        if ($n->children()) {
            $out .= '>'.$nl;
            foreach ($n->children() as $child) {
                $out .= $this->asNiceXml($child, '', is_numeric($level) ? $level+1 : true);
            }
            $out .= $pad.'</'.$n->getName().'>'.$nl;
        } else {
            $value = (string)$n;
            if (strlen($value)) {
                $out .= '>'.$this->xmlentities($value).'</'.$n->getName().'>'.$nl;
            } else {
                $out .= '/>'.$nl;
            }
        }

        if ((0===$level || false===$level) && !empty($filename)) {
            file_put_contents($filename, $out);
        }

        return $out;
    }

    /**
     * Returns the node and children as an array
     *
     * @return array
     */
    public function asArray($n)
    {
        $r = array();

        $attributes = $n->attributes();
        foreach($attributes as $k=>$v) {
            if ($v) $r['@'][$k] = (string) $v;
        }

        if (!($children = $n->children())) {
            $r = (string) $n;
            return $r;
        }

        foreach($children as $childName=>$child) {
            $r[$childName] = array();
            foreach ($child as $index=>$element) {
                $r[$childName][$index] = $this->asArray($element);
            }
        }
        return $r;
    }


    /**
     * Returns attribute value by attribute name
     *
     * @return string
     */
    public function getAttribute($n, $name){
        $attrs = $n->attributes();
        return isset($attrs[$name]) ? (string)$attrs[$name] : null;
    }



    /**
     * Enter description here...
     *
     * @param int $level
     * @return string
     */
    public function innerXml($n, $level=0)
    {
        $out = '';
        foreach ($n->children() as $child) {
            $out .= $this->asNiceXml($child, $level);
        }
        return $out;
    }
}
