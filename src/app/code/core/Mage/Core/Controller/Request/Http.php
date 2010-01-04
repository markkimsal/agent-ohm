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

AO::includeLibFile('Zend/Controller/Request/Abstract');
AO::includeLibFile('Zend/Controller/Request/Http');
AO::includeLibFile('Zend/Controller/Response/Abstract');

/**
 * Custom Zend_Controller_Request_Http class (formally)
 *
 * Allows dispatching before and after events for each controller action
 *
 * @author      Magento Core Team <core@magentocommerce.com>
 */
class Mage_Core_Controller_Request_Http extends Zend_Controller_Request_Http
{
    const XML_NODE_DIRECT_FRONT_NAMES = 'global/request/direct_front_name';

    /**
     * ORIGINAL_PATH_INFO
     * @var string
     */
    protected $_originalPathInfo= '';
    protected $_storeCode       = null;
    protected $_requestString   = '';

    /**
     * Path info array used before applying rewrite from config
     *
     * @var null || array
     */
    protected $_rewritedPathInfo= null;
    protected $_requestedRouteName = null;

    protected $_route;

    protected $_directFrontNames = array();
    protected $_controllerModule = null;

    public function __construct($uri = null)
    {
        parent::__construct($uri);
        $names = AO::getConfig()->getNode(self::XML_NODE_DIRECT_FRONT_NAMES);
        if ($names) {
            $this->_directFrontNames = $names->asArray();
        }
    }

    /**
     * Returns ORIGINAL_PATH_INFO.
     * This value is calculated instead of reading PATH_INFO
     * directly from $_SERVER due to cross-platform differences.
     *
     * @return string
     */
    public function getOriginalPathInfo()
    {
        if (empty($this->_originalPathInfo)) {
            $this->setPathInfo();
        }
        return $this->_originalPathInfo;
    }

    public function getStoreCodeFromPath()
    {
        if (!$this->_storeCode) {
            // get store view code
            if ($this->_canBeStoreCodeInUrl()) {
                $p = explode('/', trim($this->getPathInfo(), '/'));
                $storeCode = $p[0];

                $stores = AO::app()->getStores(true, true);

                if ($storeCode !== '' && isset($stores[$storeCode])) {
                    array_shift($p);
                    $this->setPathInfo(implode('/', $p));
                    $this->_storeCode = $storeCode;
					AO::app()->setCurrentStore($storeCode);
                }
                else {
                    $this->_storeCode = AO::app()->getStore()->getCode();
                }
            } else {
                $this->_storeCode = AO::app()->getStore()->getCode();
            }

        }
        return $this->_storeCode;
    }

    /**
     * Set the PATH_INFO string
     * Set the ORIGINAL_PATH_INFO string
     *
     * @param string|null $pathInfo
     * @return Zend_Controller_Request_Http
     */
    public function setPathInfo($pathInfo = null)
    {
        if ($pathInfo === null) {
            $requestUri = $this->getRequestUri();
            if (null === $requestUri) {
                return $this;
            }

            // Remove the query string from REQUEST_URI
            $pos = strpos($requestUri, '?');
            if ($pos) {
                $requestUri = substr($requestUri, 0, $pos);
            }

            $baseUrl = $this->getBaseUrl();
            $pathInfo = substr($requestUri, strlen($baseUrl));

            if ((null !== $baseUrl) && (false === $pathInfo)) {
                $pathInfo = '';
            } elseif (null === $baseUrl) {
                $pathInfo = $requestUri;
            }

            if ($this->_canBeStoreCodeInUrl()) {
                $pathParts = explode('/', ltrim($pathInfo, '/'), 2);
                $storeCode = $pathParts[0];

                if (!$this->isDirectAccessFrontendName($storeCode)) {
                    $stores = AO::app()->getStores(true, true);
                    if ($storeCode!=='' && isset($stores[$storeCode])) {
						AO::app()->setCurrentStore($storeCode);
                        $pathInfo = '/'.(isset($pathParts[1]) ? $pathParts[1] : '');
                    }
                    elseif ($storeCode !== '') {
                        $this->setActionName('noRoute');
                    }
                }
            }

            $this->_originalPathInfo = (string) $pathInfo;

            $this->_requestString = $pathInfo . ($pos!==false ? substr($requestUri, $pos) : '');
        }

        $this->_pathInfo = (string) $pathInfo;
        return $this;
    }

    /**
     * Specify new path info
     * It happen when occur rewrite based on configuration
     *
     * @param   string $pathInfo
     * @return  Mage_Core_Controller_Request_Http
     */
    public function rewritePathInfo($pathInfo)
    {
        if (($pathInfo != $this->getPathInfo()) && ($this->_rewritedPathInfo === null)) {
            $this->_rewritedPathInfo = explode('/', trim($this->getPathInfo(), '/'));
        }
        $this->setPathInfo($pathInfo);
        return $this;
    }

    /**
     * Check if can be store code as part of url
     *
     * @return bool
     */
    protected function _canBeStoreCodeInUrl()
    {
        return AO::isInstalled() && AO::getStoreConfigFlag(Mage_Core_Model_Store::XML_PATH_STORE_IN_URL);
    }

    /**
     * Check if code declared as direct access frontend name
     * this mean what this url can be used without store code
     *
     * @param   string $code
     * @return  bool
     */
    public function isDirectAccessFrontendName($code)
    {
        $names = $this->getDirectFrontNames();
        return isset($names[$code]);
    }

    /**
     * Get list of front names available with access without store code
     *
     * @return array
     */
    public function getDirectFrontNames()
    {
        return $this->_directFrontNames;
    }

    public function getOriginalRequest()
    {
        $request = new Zend_Controller_Request_Http();
        $request->setPathInfo($this->getOriginalPathInfo());
        return $request;
    }

    public function getRequestString()
    {
        return $this->_requestString;
    }

    public function getBasePath()
    {
        $path = parent::getBasePath();
        if (empty($path)) {
            $path = '/';
        } else {
            $path = str_replace('\\', '/', $path);
        }
        return $path;
    }

    public function getBaseUrl()
    {
        $url = parent::getBaseUrl();
        $url = str_replace('\\', '/', $url);
        return $url;
    }

    public function setRouteName($route)
    {
        $this->_route = $route;
        $router = AO::app()->getFrontController()->getRouterByRoute($route);
        if (!$router) return $this;
        $module = $router->getFrontNameByRoute($route);
        if ($module) {
            $this->setModuleName($module);
        }
        return $this;
    }

    public function getRouteName()
    {
        return $this->_route;
    }

    /**
     * Retrieve HTTP HOST
     *
     * @param bool $trimPort
     * @return string
     */
    public function getHttpHost($trimPort = true)
    {
        if (!isset($_SERVER['HTTP_HOST'])) {
            return false;
        }
        if ($trimPort) {
            $host = split(':', $_SERVER['HTTP_HOST']);
            return $host[0];
        }
        return $_SERVER['HTTP_HOST'];
    }

    /**
     * Set a member of the $_POST superglobal
     *
     * @param striing|array $key
     * @param mixed $value
     *
     * @return Mage_Core_Controller_Request_Http
     */
    public function setPost($key, $value = null)
    {
        if (is_array($key)) {
            $_POST = $key;
        }
        else {
            $_POST[$key] = $value;
        }
        return $this;
    }

    /**
     * Specify module name where was found currently used controller
     *
     * @param   string $module
     * @return  Mage_Core_Controller_Request_Http
     */
    public function setControllerModule($module)
    {
        $this->_controllerModule = $module;
        return $this;
    }

    /**
     * Get module name of currently used controller
     *
     * @return  string
     */
    public function getControllerModule()
    {
        return $this->_controllerModule;
    }

    /**
     * Retrieve the module name
     *
     * @return string
     */
    public function getModuleName()
    {
        return $this->_module;
    }
    /**
     * Retrieve the controller name
     *
     * @return string
     */
    public function getControllerName()
    {
        return $this->_controller;
    }
    /**
     * Retrieve the action name
     *
     * @return string
     */
    public function getActionName()
    {
        return $this->_action;
    }

    /**
     * Get route name used in request (ignore rewrite)
     *
     * @return string
     */
    public function getRequestedRouteName()
    {
        if ($this->_requestedRouteName === null) {
            if ($this->_rewritedPathInfo !== null && isset($this->_rewritedPathInfo[0])) {
                $fronName = $this->_rewritedPathInfo[0];
                $router = AO::app()->getFrontController()->getRouterByFrontName($fronName);
                $this->_requestedRouteName = $router->getRouteByFrontName($fronName);
            } else {
                // no rewritten path found, use default route name
                return $this->getRouteName();
            }
        }
        return $this->_requestedRouteName;
    }

    /**
     * Get controller name used in request (ignore rewrite)
     *
     * @return string
     */
    public function getRequestedControllerName()
    {
        if (($this->_rewritedPathInfo !== null) && isset($this->_rewritedPathInfo[1])) {
            return $this->_rewritedPathInfo[1];
        }
        return $this->getControllerName();
    }

    /**
     * Get action name used in request (ignore rewrite)
     *
     * @return string
     */
    public function getRequestedActionName()
    {
        if (($this->_rewritedPathInfo !== null) && isset($this->_rewritedPathInfo[2])) {
            return $this->_rewritedPathInfo[2];
        }
        return $this->getActionName();
    }
}
