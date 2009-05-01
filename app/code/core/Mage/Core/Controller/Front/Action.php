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
 * Custom Zend_Controller_Action class (formally)
 *
 * Allows dispatching before and after events for each controller action
 *
 * @category   Mage
 * @package    Mage_Core
 * @author      Magento Core Team <core@magentocommerce.com>
 */
abstract class Mage_Core_Controller_Front_Action
{
    const FLAG_NO_CHECK_INSTALLATION    = 'no-install-check';
    const FLAG_NO_DISPATCH              = 'no-dispatch';
    const FLAG_NO_PRE_DISPATCH          = 'no-preDispatch';
    const FLAG_NO_POST_DISPATCH         = 'no-postDispatch';
    const FLAG_NO_START_SESSION         = 'no-startSession';
    const FLAG_NO_DISPATCH_BLOCK_EVENT  = 'no-beforeGenerateLayoutBlocksDispatch';

    const PARAM_NAME_SUCCESS_URL        = 'success_url';
    const PARAM_NAME_ERROR_URL          = 'error_url';
    const PARAM_NAME_REFERER_URL        = 'referer_url';
    const PARAM_NAME_BASE64_URL         = 'r64';
    const PARAM_NAME_URL_ENCODED        = 'uenc';

    const PROFILER_KEY                  = 'mage::dispatch::controller::action';

    /**
     * Request object
     *
     * @var Zend_Controller_Request_Abstract
     */
    protected $_request;

    /**
     * Response object
     *
     * @var Zend_Controller_Response_Abstract
     */
    protected $_response;

    /**
     * Real module name (like 'Mage_Module')
     *
     * @var string
     */
    protected $_realModuleName;

    /**
     * Action flags
     *
     * for example used to disable rendering default layout
     *
     * @var array
     */
    protected $_flags = array();


    public $outputHandler = 'output'; //the default view function
    public $defaultArea   = 'frontend';

    /**
     * Constructor
     *
     * @param Zend_Controller_Request_Abstract $request
     * @param Zend_Controller_Response_Abstract $response
     * @param array $invokeArgs
     */
    public function __construct(
            Zend_Controller_Request_Abstract $request, 
            Zend_Controller_Response_Abstract $response, 
            array $invokeArgs = array()
    ) {
        $this->_request = $request;
        $this->_response= $response;

        AO::app()->getFrontController()->setAction($this);

        $this->_construct();
    }

    protected function _construct()
    {

    }

    public function hasAction($action)
    {
        return is_callable(array($this, $this->getActionMethodName($action)));
    }

    /**
     * Retrieve request object
     *
     * @return Mage_Core_Controller_Request_Http
     */
    public function getRequest()
    {
        return $this->_request;
    }

    /**
     * Retrieve response object
     *
     * @return Mage_Core_Controller_Response_Http
     */
    public function getResponse()
    {
        return $this->_response;
    }

    /**
     * Retrieve flag value
     *
     * @param   string $action
     * @param   string $flag
     * @return  bool
     */
    public function getFlag($action, $flag='')
    {
        if (''===$action) {
            $action = $this->getRequest()->getActionName();
        }
        if (''===$flag) {
            return $this->_flags;
        }
        elseif (isset($this->_flags[$action][$flag])) {
            return $this->_flags[$action][$flag];
        }
        else {
            return false;
        }
    }

    /**
     * Setting flag value
     *
     * @param   string $action
     * @param   string $flag
     * @param   string $value
     * @return  Mage_Core_Controller_Varien_Action
     */
    public function setFlag($action, $flag, $value)
    {
        if (''===$action) {
            $action = $this->getRequest()->getActionName();
        }
        $this->_flags[$action][$flag] = $value;
        return $this;
    }

    /**
     * Retrieve full bane of current action current controller and
     * current module
     *
     * @param   string $delimiter
     * @return  string
     */
    public function getFullActionName($delimiter='_')
    {
        return $this->getRequest()->getRouteName().$delimiter.
            $this->getRequest()->getControllerName().$delimiter.
            $this->getRequest()->getActionName();
    }

    /**
     * Retrieve current layout object
     *
     * @return Mage_Core_Model_Layout
     */
    public function getLayout()
    {
        return AO::getSingleton('core/layout');
    }

    /**
     * Load layout by handles(s)
     *
     * @param   string $handles
     * @param   string $cacheId
     * @param   boolean $generateBlocks
     * @return  Mage_Core_Controller_Varien_Action
     */
    public function loadLayout($handles=null, $generateBlocks=true, $generateXml=true)
    {
        // if handles were specified in arguments load them first
        if (false!==$handles && ''!==$handles) {
            $this->getLayout()->getUpdate()->addHandle($handles ? $handles : 'default');
        }

        // add default layout handles for this action
        $this->addActionLayoutHandles();

        $this->loadLayoutUpdates();

        if (!$generateXml) {
            return $this;
        }
        $this->generateLayoutXml();

        if (!$generateBlocks) {
            return $this;
        }
        $this->generateLayoutBlocks();

        return $this;
    }

    public function addActionLayoutHandles()
    {
        $update = $this->getLayout()->getUpdate();

        // load store handle
        $update->addHandle('STORE_'.AO::app()->getStore()->getCode());

        // load theme handle
        $package = Mage_Core_Model_Design_Package::getDesign();
        //$package = AO::getSingleton('core/design_package');
        $update->addHandle('THEME_'.$package->getArea().'_'.$package->getPackageName().'_'.$package->getTheme('layout'));

        // load action handle
        $update->addHandle(strtolower($this->getFullActionName()));

        return $this;
    }

    public function loadLayoutUpdates()
    {
        $_profilerKey = self::PROFILER_KEY . '::' .$this->getFullActionName();

        // dispatch event for adding handles to layout update
        AO::dispatchEvent(
            'controller_action_layout_load_before',
            array('action'=>$this, 'layout'=>$this->getLayout())
        );

        // load layout updates by specified handles
        if (VPROF) Varien_Profiler::start("$_profilerKey::layout_load");
        $this->getLayout()->getUpdate()->load();
        if (VPROF) Varien_Profiler::stop("$_profilerKey::layout_load");

        return $this;
    }

    public function generateLayoutXml()
    {
        $_profilerKey = self::PROFILER_KEY . '::' . $this->getFullActionName();
        // dispatch event for adding text layouts
        if(!$this->getFlag('', self::FLAG_NO_DISPATCH_BLOCK_EVENT)) {
            AO::dispatchEvent(
                'controller_action_layout_generate_xml_before',
                array('action'=>$this, 'layout'=>$this->getLayout())
            );
        }

        // generate xml from collected text updates
        if (VPROF) Varien_Profiler::start("$_profilerKey::layout_generate_xml");
        $this->getLayout()->generateXml();
        if (VPROF) Varien_Profiler::stop("$_profilerKey::layout_generate_xml");

        return $this;
    }

    public function generateLayoutBlocks()
    {
        $_profilerKey = self::PROFILER_KEY . '::' . $this->getFullActionName();
        // dispatch event for adding xml layout elements
        if(!$this->getFlag('', self::FLAG_NO_DISPATCH_BLOCK_EVENT)) {
            AO::dispatchEvent(
                'controller_action_layout_generate_blocks_before',
                array('action'=>$this, 'layout'=>$this->getLayout())
            );
        }

        // generate blocks from xml layout
        if (VPROF) Varien_Profiler::start("$_profilerKey::layout_generate_blocks");
        $this->getLayout()->generateBlocks();
        if (VPROF) Varien_Profiler::stop("$_profilerKey::layout_generate_blocks");

        if(!$this->getFlag('', self::FLAG_NO_DISPATCH_BLOCK_EVENT)) {
            AO::dispatchEvent(
                'controller_action_layout_generate_blocks_after',
                array('action'=>$this, 'layout'=>$this->getLayout())
            );
        }

        return $this;
    }

    /**
     * Rendering layout
     *
     * @param   string $output
     * @return  Mage_Core_Controller_Varien_Action
     */
    public function renderLayout($output='')
    {
        $_profilerKey = self::PROFILER_KEY . '::' . $this->getFullActionName();

        if ($this->getFlag('', 'no-renderLayout')) {
            return;
        }

        if (AO::app()->getFrontController()->getNoRender()) {
            return;
        }

        if (VPROF) Varien_Profiler::start("$_profilerKey::layout_render");


        if (''!==$output) {
            $this->getLayout()->addOutputBlock($output);
        }

        AO::dispatchEvent('controller_action_layout_render_before');
        AO::dispatchEvent('controller_action_layout_render_before_'.$this->getFullActionName());

        #ob_implicit_flush();
        $this->getLayout()->setDirectOutput(false);

        $output = $this->getLayout()->getOutput();

        $this->_response->appendBody($output);
        if (VPROF) Varien_Profiler::stop("$_profilerKey::layout_render");

        return $this;
    }

    public function dispatch($action)
    {
        $actionMethodName = $this->getActionMethodName($action);

        if (!is_callable(array($this, $actionMethodName))) {
            $actionMethodName = 'norouteAction';
        }

        if (VPROF) Varien_Profiler::start(self::PROFILER_KEY.'::predispatch');
        $this->preDispatch();
        if (VPROF) Varien_Profiler::stop(self::PROFILER_KEY.'::predispatch');

        if ($this->getRequest()->isDispatched()) {
            /**
             * preDispatch() didn't change the action, so we can continue
             */
            if (!$this->getFlag('', self::FLAG_NO_DISPATCH)) {
                $_profilerKey = self::PROFILER_KEY.'::'.$this->getFullActionName();

                if (VPROF) Varien_Profiler::start($_profilerKey);
                $this->$actionMethodName();
                if (VPROF) Varien_Profiler::stop($_profilerKey);

                if (VPROF) Varien_Profiler::start(self::PROFILER_KEY.'::postdispatch');
                $this->postDispatch();
                if (VPROF) Varien_Profiler::stop(self::PROFILER_KEY.'::postdispatch');
            }
        }
    }

    public function getActionMethodName($action)
    {
        $method = $action.'Action';
        return $method;
    }

    /**
     * Dispatches event before action
     */
    public function preDispatch()
    {
        $this->getLayout()->setArea($this->defaultArea);
        if (!$this->getFlag('', self::FLAG_NO_CHECK_INSTALLATION)) {
            if (!AO::isInstalled()) {
                $this->setFlag('', self::FLAG_NO_DISPATCH, true);
                $this->_redirect('install');
                return;
            }
        }

        if ($this->_rewrite()) {
            return;
        }

        if (!$this->getFlag('', self::FLAG_NO_START_SESSION)) {
            AO::getSingleton('core/session', array('name'=>$this->getLayout()->getArea()))->start();
        }

        AO::app()->loadArea($this->getLayout()->getArea());

        if ($this->getFlag('', self::FLAG_NO_PRE_DISPATCH)) {
            return;
        }

        AO::dispatchEvent('controller_action_predispatch', array('controller_action'=>$this));
        AO::dispatchEvent(
            'controller_action_predispatch_'.$this->getRequest()->getRouteName(),
            array('controller_action'=>$this)
        );
        AO::dispatchEvent(
            'controller_action_predispatch_'.$this->getFullActionName(),
            array('controller_action'=>$this)
        );
    }

    /**
     * Dispatches event after action
     */
    public function postDispatch()
    {
        if ($this->getFlag('', self::FLAG_NO_POST_DISPATCH)) {
            return;
        }

        AO::dispatchEvent(
            'controller_action_postdispatch_'.$this->getFullActionName(),
            array('controller_action'=>$this)
        );
        AO::dispatchEvent(
            'controller_action_postdispatch_'.$this->getRequest()->getRouteName(),
            array('controller_action'=>$this)
        );
        AO::dispatchEvent('controller_action_postdispatch', array('controller_action'=>$this));

        if (!$this->getFlag('', self::FLAG_NO_START_SESSION )) {
            AO::getSingleton('core/session')->setLastUrl(AO::getUrl('*/*/*'), array('_current'=>true));
        }
    }

    /**
     * Translate a phrase
     *
     * @return string
     */
    public function __()
    {
        $args = func_get_args();
        $expr = new Mage_Core_Model_Translate_Expr(array_shift($args), $this->_getRealModuleName());
        array_unshift($args, $expr);
        return AO::app()->getTranslator()->translate($args);
    }

    public function norouteAction($coreRoute = null)
    {
        $status = ( $this->getRequest()->getParam('__status__') )
            ? $this->getRequest()->getParam('__status__')
            : new Varien_Object();

        AO::dispatchEvent('controller_action_noroute', array('action'=>$this, 'status'=>$status));
        if ($status->getLoaded() !== true
            || $status->getForwarded() === true
            || !is_null($coreRoute) ) {
            $this->loadLayout(array('default', 'noRoute'));
            $this->renderLayout();
        } else {
            $status->setForwarded(true);
            #$this->_forward('cmsNoRoute', 'index', 'cms');
            $this->_forward(
                $status->getForwardAction(),
                $status->getForwardController(),
                $status->getForwardModule(),
                array('__status__' => $status));
        }
    }

    protected function _forward($action, $controller = null, $module = null, array $params = null)
    {
        $request = $this->getRequest();

        if (!is_null($params)) {
            $request->setParams($params);
        }

        if (!is_null($controller)) {
            $request->setControllerName($controller);

            // Module should only be reset if controller has been specified
            if (!is_null($module)) {
                $request->setModuleName($module);
            }
        }

        $request->setActionName($action)
                ->setDispatched(false);
    }

    protected function _initLayoutMessages($messagesStorage)
    {
        if ($storage = AO::getSingleton($messagesStorage)) {
            $this->getLayout()->getMessagesBlock()->addMessages($storage->getMessages(true));
        }
        else {
            AO::throwException(
                 AO::helper('core')->__('Invalid messages storage "%s" for layout messages initialization', (string)$messagesStorage)
            );
        }
        return $this;
    }

    /**
     * Set redirect url into response
     *
     * @param   string $url
     * @return  Mage_Core_Controller_Varien_Action
     */
    protected function _redirectUrl($url)
    {
        $this->getResponse()->setRedirect($url);
        return $this;
    }

    /**
     * Set redirect into responce
     *
     * @param   string $path
     * @param   array $arguments
     */
    protected function _redirect($path, $arguments=array())
    {
        $this->getResponse()->setRedirect(AO::getUrl($path, $arguments));
        return $this;
    }

    /**
     * Redirect to success page
     *
     * @param string $defaultUrl
     */
    protected function _redirectSuccess($defaultUrl)
    {
        $successUrl = $this->getRequest()->getParam(self::PARAM_NAME_SUCCESS_URL);
        if (empty($successUrl)) {
            $successUrl = $defaultUrl;
        }
        if (!$this->_isUrlInternal($successUrl)) {
            $successUrl = AO::app()->getStore()->getBaseUrl();
        }
        $this->getResponse()->setRedirect($successUrl);
        return $this;
    }

    /**
     * Redirect to error page
     *
     * @param string $defaultUrl
     */
    protected function _redirectError($defaultUrl)
    {
        $errorUrl = $this->getRequest()->getParam(self::PARAM_NAME_ERROR_URL);
        if (empty($errorUrl)) {
            $errorUrl = $defaultUrl;
        }
        if (!$this->_isUrlInternal($errorUrl)) {
            $errorUrl = AO::app()->getStore()->getBaseUrl();
        }
        $this->getResponse()->setRedirect($errorUrl);
        return $this;
    }

    /**
     * Set referer url for redirect in responce
     *
     * @param   string $defaultUrl
     * @return  Mage_Core_Controller_Varien_Action
     */
    protected function _redirectReferer($defaultUrl=null)
    {

        $refererUrl = $this->_getRefererUrl();
        if (empty($refererUrl)) {
            $refererUrl = empty($defaultUrl) ? AO::getBaseUrl() : $defaultUrl;
        }

        $this->getResponse()->setRedirect($refererUrl);
        return $this;
    }

    /**
     * Identify referer url via all accepted methods (HTTP_REFERER, regular or base64-encoded request param)
     *
     * @return string
     */
    protected function _getRefererUrl()
    {
        $refererUrl = $this->getRequest()->getServer('HTTP_REFERER');
        if ($url = $this->getRequest()->getParam(self::PARAM_NAME_REFERER_URL)) {
            $refererUrl = $url;
        }
        if ($url = $this->getRequest()->getParam(self::PARAM_NAME_BASE64_URL)) {
            $refererUrl = AO::helper('core')->urlDecode($url);
        }
        if ($url = $this->getRequest()->getParam(self::PARAM_NAME_URL_ENCODED)) {
            $refererUrl = AO::helper('core')->urlDecode($url);
        }

        if (!$this->_isUrlInternal($refererUrl)) {
            $refererUrl = AO::app()->getStore()->getBaseUrl();
        }
        return $refererUrl;
    }

    /**
     * Check url to be used as internal
     *
     * @param   string $url
     * @return  bool
     */
    protected function _isUrlInternal($url)
    {
        if (strpos($url, 'http') !== false) {
            /**
             * Url must start from base secure or base unsecure url
             */
            if ((strpos($url, AO::app()->getStore()->getBaseUrl()) === 0)
                || (strpos($url, AO::app()->getStore()->getBaseUrl(Mage_Core_Model_Store::URL_TYPE_LINK, true)) === 0)) {
                return true;
            }
        }
        return false;
    }

    /**
     * Get real module name (like 'Mage_Module')
     *
     * @return  string
     */
    protected function _getRealModuleName()
    {
        if (empty($this->_realModuleName)) {
            $class = get_class($this);
            $this->_realModuleName = substr(
                $class,
                0,
                strpos(strtolower($class), '_' . strtolower($this->getRequest()->getControllerName() . 'Controller'))
            );
        }
        return $this->_realModuleName;
    }

    /**
     * Support for controllers rewrites
     *
     * Example of configuration:
     * <global>
     *   <routers>
     *     <core_module>
     *       <rewrite>
     *         <core_controller>
     *           <to>new_route/new_controller</to>
     *           <override_actions>true</override_actions>
     *           <actions>
     *             <core_action><to>new_module/new_controller/new_action</core_action>
     *           </actions>
     *         <core_controller>
     *       </rewrite>
     *     </core_module>
     *   </routers>
     * </global>
     *
     * This will override:
     * 1. core_module/core_controller/core_action to new_module/new_controller/new_action
     * 2. all other actions of core_module/core_controller to new_module/new_controller
     *
     * @return boolean true if rewrite happened
     */
    protected function _rewrite()
    {
        $route = $this->getRequest()->getRouteName();
        $controller = $this->getRequest()->getControllerName();
        $action = $this->getRequest()->getActionName();

        $rewrite = AO::getConfig()->getNode('global/routers/'.$route.'/rewrite/'.$controller);
        if (!$rewrite) {
            return false;
        }

        if (!($rewrite->actions && $rewrite->actions->$action) || $rewrite->is('override_actions')) {
            $t = explode('/', (string)$rewrite->to);
            if (sizeof($t)!==2 || empty($t[0]) || empty($t[1])) {
                return false;
            }
            $t[2] = $action;
        } else {
            $t = explode('/', (string)$rewrite->actions->$action->to);
            if (sizeof($t)!==3 || empty($t[0]) || empty($t[1]) || empty($t[2])) {
                return false;
            }
        }

        $this->_forward(
            $t[2]==='*' ? $action : $t[2],
            $t[1]==='*' ? $controller : $t[1],
            $t[0]==='*' ? $route : $t[0]
        );

        return true;
    }

    /**
     * Validate Form Key
     *
     * @return bool
     */
    protected function _validateFormKey()
    {
        if (!($formKey = $this->getRequest()->getParam('form_key', null))
            || $formKey != AO::getSingleton('core/session')->getFormKey()) {
            return false;
        }
        return true;
    }
}

# vim: set expandtab:
# vim: set sw=4:
# vim: set ts=4:

