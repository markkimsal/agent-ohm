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

AO::includeFile('Mage/Core/Controller/Varien/Router/Abstract');
AO::includeFile('Mage/Core/Controller/Front/Action');

class Mage_Core_Controller_Varien_Front 
{
    protected $_defaults = array();

    /**
     * Available routers array
     *
     * @var array
     */
    protected $_routers = array();

    protected $_urlCache = array();

	protected $_action   = '';

    const XML_STORE_ROUTERS_PATH = 'default/web/routers';
    //const XML_STORE_ROUTERS_PATH = 'web/routers';

	public function setAction($a) {
		$this->_action = $a;
	}

	public function getAction() {
		return $this->_action;
	}

	public function getNoRender() {
		return NULL;
	}

    public function setDefault($key, $value=null)
    {
        if (is_array($key)) {
            $this->_defaults = $key;
        } else {
            $this->_defaults[$key] = $value;
        }
        return $this;
    }

    public function getDefault($key=null)
    {
        if (is_null($key)) {
            return $this->_defaults;
        } elseif (isset($this->_defaults[$key])) {
            return $this->_defaults[$key];
        }
        return false;
    }

    /**
     * Retrieve request object
     *
     * @return Mage_Core_Controller_Request_Http
     */
    public function getRequest()
    {
        return AO::app()->getRequest();
    }

    /**
     * Retrieve response object
     *
     * @return Zend_Controller_Response_Http
     */
    public function getResponse()
    {
        return AO::app()->getResponse();
    }

    /**
     * Adding new router
     *
     * @param   string $name
     * @param   Mage_Core_Controller_Varien_Router_Abstract $router
     * @return  Mage_Core_Controller_Varien_Front
     */
    public function addRouter($name, Mage_Core_Controller_Varien_Router_Abstract $router)
    {
        $this->_routers[$name] = $router;
        return $this;
    }

    /**
     * Retrieve router by name
     *
     * @param   string $name
     * @return  Mage_Core_Controller_Varien_Router_Abstract
     */
    public function getRouter($name)
    {
        if (isset($this->_routers[$name])) {
            return $this->_routers[$name];
        }
        return false;
    }

    public function init()
    {
        AO::dispatchEvent('controller_front_init_before', array('front'=>$this));

        $routersInfo = AO::app()->getConfig()->getNode(self::XML_STORE_ROUTERS_PATH);
		$routersInfo = $routersInfo->children();

        if (VPROF) Varien_Profiler::start('mage::app::init_front_controller::collect_routers');
        foreach ($routersInfo as $routerCode => $routerTag) {
            if (isset($routerTag->disabled) && $routerTag->disabled) {
                continue;
            }
            if (isset($routerTag->class)) {
				$className = (string)$routerTag->class;
                $router = new $className();
                if (isset($routerTag->area)) {
                    $router->collectRoutes($routerTag->area, $routerCode);
                }
                $this->addRouter($routerCode, $router);
            }
        }
        if (VPROF) Varien_Profiler::stop('mage::app::init_front_controller::collect_routers');

        AO::dispatchEvent('controller_front_init_routers', array('front'=>$this));

        // Add default router at the last
        $default = new Mage_Core_Controller_Varien_Router_Default();
        $this->addRouter('default', $default);

        return $this;
    }

    public function output() {
		//if not no_dispatch, then do regular templating
		if(!$this->_action->getFlag('', Mage_Core_Controller_Front_Action::FLAG_NO_DISPATCH)) {
			if ($this->_action->outputHandler == 'output') {
				$this->_action->loadLayout();

				//this command should really be in a separate front controller only 
				//for the admin.

				//the active menu function needs the layout loaded
				if (isset($this->_action->activeMenu) && $this->_action->activeMenu != '') {
			        $this->_action->_setActiveMenu($this->_action->activeMenu);
				}
				//the breadcrumbs feature  needs the layout loaded
				if (isset($this->_action->breadCrumbs)) {
					$bc = $this->_action->breadCrumbs;
					foreach ($bc as $_bc) {
				        $this->_action->_addBreadcrumb(AO::helper('adminhtml')->__($_bc), AO::helper('adminhtml')->__($_bc));
					}
				}
				$this->_action->renderLayout();
			} elseif ($this->_action->outputHandler == 'redirect') {
				$this->_action->getResponse()->sendResponse();
				die('redir');
			} elseif ($this->_action->outputHandler == 'none') {
				//do nothing
			} else {
				$this->_action->{$this->_action->outputHandler}();
			}
		}
		//otherwise, send output, probably 304 redirect headers
        if (VPROF) Varien_Profiler::start('mage::app::dispatch::send_response');
        $this->_action->getResponse()->sendResponse();
        if (VPROF) Varien_Profiler::stop('mage::app::dispatch::send_response');
    }

    public function dispatch()
    {
        $request = $this->getRequest();
        $request->setPathInfo()->setDispatched(false);

        if (VPROF) Varien_Profiler::start('mage::dispatch::db_url_rewrite');
        AO::getModel('core/url_rewrite')->rewrite();
        if (VPROF) Varien_Profiler::stop('mage::dispatch::db_url_rewrite');

        if (VPROF) Varien_Profiler::start('mage::dispatch::config_url_rewrite');
        $this->rewrite();
        if (VPROF) Varien_Profiler::stop('mage::dispatch::config_url_rewrite');

        if (VPROF) Varien_Profiler::start('mage::dispatch::routers_match');
        $i = 0;
        while (!$request->isDispatched() && $i++<100) {
            foreach ($this->_routers as $router) {
                if ($router->match($request)) {
                    break;
                }
            }
        }
        if (VPROF) Varien_Profiler::stop('mage::dispatch::routers_match');
        if ($i>100) {
            AO::throwException('Front controller reached 100 router match iterations');
        }

        return $this;
    }

    public function getRouterByRoute($routeName)
    {
        // empty route supplied - return base url
        if (empty($routeName)) {
            $router = $this->getRouter('standard');
        } elseif ($this->getRouter('admin')->getFrontNameByRoute($routeName)) {
            // try standard router url assembly
            $router = $this->getRouter('admin');
        } elseif ($this->getRouter('standard')->getFrontNameByRoute($routeName)) {
            // try standard router url assembly
            $router = $this->getRouter('standard');
        } elseif ($router = $this->getRouter($routeName)) {
            // try custom router url assembly
        } else {
            // get default router url
            $router = $this->getRouter('default');
        }

        return $router;
    }

    public function getRouterByFrontName($frontName)
    {
        // empty route supplied - return base url
        if (empty($frontName)) {
            $router = $this->getRouter('standard');
        } elseif ($this->getRouter('admin')->getRouteByFrontName($frontName)) {
            // try standard router url assembly
            $router = $this->getRouter('admin');
        } elseif ($this->getRouter('standard')->getRouteByFrontName($frontName)) {
            // try standard router url assembly
            $router = $this->getRouter('standard');
        } elseif ($router = $this->getRouter($frontName)) {
            // try custom router url assembly
        } else {
            // get default router url
            $router = $this->getRouter('default');
        }

        return $router;
    }

    public function rewrite()
    {
        $request = $this->getRequest();
        $config = AO::getConfig()->getNode('global/rewrite');
        if (!$config) {
            return;
        }
        foreach ($config->children() as $rewrite) {
            $from = (string)$rewrite->from;
            $to = (string)$rewrite->to;
            if (empty($from) || empty($to)) {
                continue;
            }
            $pathInfo = preg_replace($from, $to, $request->getPathInfo());
            $request->setPathInfo($pathInfo);
        }
    }
}
