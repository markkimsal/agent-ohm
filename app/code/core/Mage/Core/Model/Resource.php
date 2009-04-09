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
 *
 * @ao-modified
 * @ao-copyright 2009 Mark Kimsal
 */


/**
 * Resources and connections registry and factory
 *
 */
class Mage_Core_Model_Resource
{

    const AUTO_UPDATE_CACHE_KEY = 'DB_AUTOUPDATE';
    const AUTO_UPDATE_ONCE      = 0;
    const AUTO_UPDATE_NEVER     = -1;
    const AUTO_UPDATE_ALWAYS    = 1;

    /**
     * Instances of classes for connection types
     *
     * @var array
     */
    protected $_connectionTypes = array();

    /**
     * Instances of actual connections
     *
     * @var array
     */
    protected $_connections = array();

    /**
     * Registry of resource entities
     *
     * @var array
     */
    protected $_entities = array();
    
    protected $_mappedTableNames;

    /**
     * Creates a connection to resource whenever needed
     *
     * @param string $name
     * @return mixed
     */
    public function getConnection($name)
    {
        if (isset($this->_connections[$name])) {
            return $this->_connections[$name];
        }
        // Trying to remove custom simplexml element classes.:app/code/core/Mage/Core/Model/Resource.php
        $c = AO::getConfig();
        $connConfig = $c->getResourceConnectionConfig($name);
        if (!$connConfig || !$c->configElementIs($connConfig, 'active', 1)) {
            return false;
        }
        //$origName = $connConfig->getParent()->getName();
		//this is always 'resources'
		$origName = 'resources';

        if (isset($this->_connections[$origName])) {
            return $this->_connections[$origName];
        }

        $typeInstance = $this->getConnectionTypeInstance((string)$connConfig->type);
        $conn = $typeInstance->getConnection($connConfig);

        $this->_connections[$name] = $conn;
        if ($origName!==$name) {
            $this->_connections[$origName] = $conn;
        }
        return $conn;
    }

    /**
     * Get connection type instance
     *
     * Creates new if doesn't exist
     *
     * @param string $type
     * @return Mage_Core_Model_Resource_Type_Abstract
     */
    public function getConnectionTypeInstance($type)
    {
        if (!isset($this->_connectionTypes[$type])) {
            // Trying to remove custom simplexml element classes.:app/code/core/Mage/Core/Model/Resource.php
            $c      = AO::getConfig();
            $config = $c->getResourceTypeConfig($type);
            $typeClass = $c->getConfigElementClassName($config);
            $this->_connectionTypes[$type] = new $typeClass();
        }
        return $this->_connectionTypes[$type];
    }

    /**
     * Get resource entity
     *
     * @param string $resource
     * @param string $entity
     * @return Varien_Simplexml_Config
     */
    public function getEntity($model, $entity)
    {
        //return AO::getConfig()->getNode("global/models/$model/entities/$entity");
        return AO::getConfig()->getNode()->global->models->{$model}->entities->{$entity};
    }

    /**
     * Get resource table name
     *
     * @param   string $modelEntity
     * @return  string
     */
    public function getTableName($modelEntity)
    {
        $arr = explode('/', $modelEntity);
        if (isset($arr[1])) {
            list($model, $entity) = $arr;
            //$resourceModel = (string)AO::getConfig()->getNode('global/models/'.$model.'/resourceModel');
            $resourceModel = (string) AO::getConfig()->getNode()->global->models->{$model}->resourceModel;
            $entityConfig = $this->getEntity($resourceModel, $entity);
            if ($entityConfig) {
                $tableName = (string)$entityConfig->table;
            } else {
                AO::throwException(AO::helper('core')->__('Can\'t retrieve entity config: %s', $modelEntity));
            }
        } else {
            $tableName = $modelEntity;
        }
        
        AO::dispatchEvent('resource_get_tablename', array('resource' => $this, 'model_entity' => $modelEntity, 'table_name' => $tableName));
        $mappedTableName = $this->getMappedTableName($tableName);
        if ($mappedTableName) {
        	$tableName = $mappedTableName;
        } else {
        	$tablePrefix = (string)AO::getConfig()->getTablePrefix();
        	$tableName = $tablePrefix . $tableName;
        }

        return $tableName;
    }

    public function setMappedTableName($tableName, $mappedName)
    {
    	$this->_mappedTableNames[$tableName] = $mappedName;
    	return $this;
    }
    
    public function getMappedTableName($tableName)
    {
    	if (isset($this->_mappedTableNames[$tableName])) {
    		return $this->_mappedTableNames[$tableName];
    	} else {
    		return false;
    	}
    }
    
    public function cleanDbRow(&$row)
    {
        if (!empty($row) && is_array($row)) {
            foreach ($row as $key=>&$value) {
                if (is_string($value) && $value==='0000-00-00 00:00:00') {
                    $value = '';
                }
            }
        }
        return $this;
    }

    public function createConnection($name, $type, $config)
    {
        if (!isset($this->_connections[$name])) {
            $typeObj = $this->getConnectionTypeInstance($type);
            $this->_connections[$name] = $typeObj->getConnection($config);
        }
        return $this->_connections[$name];
    }

    public function checkDbConnection()
    {
    	if (!$this->getConnection('core_read')) {
    		//AO::app()->getResponse()->setRedirect(AO::getUrl('install'));
    	}
    }

    public function getAutoUpdate()
    {
        return self::AUTO_UPDATE_ALWAYS;
        #return AO::app()->loadCache(self::AUTO_UPDATE_CACHE_KEY);
    }

    public function setAutoUpdate($value)
    {
        #AO::app()->saveCache($value, self::AUTO_UPDATE_CACHE_KEY);
        return $this;
    }

}
