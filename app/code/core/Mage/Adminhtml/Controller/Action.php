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
 * @category    Mage
 * @package     Mage_Adminhtml
 * @copyright   Copyright (c) 2008 Irubin Consulting Inc. DBA Varien (http://www.varien.com)
 * @license     http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */

/**
 * Base adminhtml controller
 *
 * @category    Mage
 * @package     Mage_Adminhtml
 * @author      Magento Core Team <core@magentocommerce.com>
 */
class Mage_Adminhtml_Controller_Action extends Mage_Core_Controller_Varien_Action
{

    const FLAG_IS_URLS_CHECKED = 'check_url_settings';

    /**
     * Used module name in current adminhtml controller
     */
    protected $_usedModuleName = 'adminhtml';

    protected function _isAllowed()
    {
        return true;
    }

    /**
     * Retrieve adminhtml session model object
     *
     * @return Mage_Adminhtml_Model_Session
     */
    protected function _getSession()
    {
        return Mage::getSingleton('adminhtml/session');
    }

    /**
     * Retrieve base admihtml helper
     *
     * @return Mage_Adminhtml_Helper_Data
     */
    protected function _getHelper()
    {
        return Mage::helper('adminhtml');
    }

    /**
     * Define active menu item in menu block
     *
     * @return Mage_Adminhtml_Controller_Action
     */
    protected function _setActiveMenu($menuPath)
    {
        $this->getLayout()->getBlock('menu')->setActive($menuPath);
        return $this;
    }

    /**
     * @return Mage_Adminhtml_Controller_Action
     */
    protected function _addBreadcrumb($label, $title, $link=null)
    {
        $this->getLayout()->getBlock('breadcrumbs')->addLink($label, $title, $link);
        return $this;
    }

    /**
     * @return Mage_Adminhtml_Controller_Action
     */
    protected function _addContent(Mage_Core_Block_Abstract $block)
    {
        $this->getLayout()->getBlock('content')->append($block);
        return $this;
    }

    protected function _addLeft(Mage_Core_Block_Abstract $block)
    {
        $this->getLayout()->getBlock('left')->append($block);
        return $this;
    }

    protected function _addJs(Mage_Core_Block_Abstract $block)
    {
        $this->getLayout()->getBlock('js')->append($block);
        return $this;
    }

    public function hasAction($action)
    {
        return true;
    }

    /**
     * Controller predispatch method
     *
     * @return Mage_Adminhtml_Controller_Action
     */
    public function preDispatch()
    {
        Mage::getDesign()->setArea('adminhtml')
            ->setPackageName((string)Mage::getConfig()->getNode('stores/admin/design/package/name'))
            ->setTheme((string)Mage::getConfig()->getNode('stores/admin/design/theme/default'));

        $this->getLayout()->setArea('adminhtml');

        Mage::dispatchEvent('adminhtml_controller_action_predispatch_start', array());

        parent::preDispatch();

        $_isValidFormKey = true;
        $_isValidSecretKey = true;
        $_keyErrorMsg = '';
        if (Mage::getSingleton('admin/session')->isLoggedIn()) {
            if ($this->getRequest()->isPost()) {
                $_isValidFormKey = $this->_validateFormKey();
                $_keyErrorMsg = 'Invalid Form Key';
            } elseif (Mage::getSingleton('adminhtml/url')->useSecretKey()) {
                $_isValidSecretKey = $this->_validateSecretKey();
                $_keyErrorMsg = 'Invalid Secret Key';
            }
        }
        if (!$_isValidFormKey || !$_isValidSecretKey) {
            $this->setFlag('', self::FLAG_NO_DISPATCH, true);
            $this->setFlag('', self::FLAG_NO_POST_DISPATCH, true);
            if ($this->getRequest()->getQuery('isAjax', false) || $this->getRequest()->getQuery('ajax', false)) {
                $this->getResponse()->setBody(Zend_Json::encode(array(
                    'error' => true,
                    'error_msg' => Mage::helper('adminhtml')->__($_keyErrorMsg)
                )));
            } else {
                $this->_redirect('*/index/index');
            }
            return $this;
        }

        if ($this->getRequest()->isDispatched()
            && $this->getRequest()->getActionName() !== 'denied'
            && !$this->_isAllowed()) {
            $this->_forward('denied');
            $this->setFlag('', self::FLAG_NO_DISPATCH, true);
            return $this;
        }

        if (!$this->getFlag('', self::FLAG_IS_URLS_CHECKED)
            && !$this->getRequest()->getParam('forwarded')
            && !$this->_getSession()->getIsUrlNotice(true)
            && !Mage::getConfig()->getNode('global/can_use_base_url')) {
            $this->_checkUrlSettings();
            $this->setFlag('', self::FLAG_IS_URLS_CHECKED, true);
        }
        if (is_null(Mage::getSingleton('adminhtml/session')->getLocale())) {
            Mage::getSingleton('adminhtml/session')->setLocale(Mage::app()->getLocale()->getLocaleCode());
        }

        return $this;
    }

    protected function _checkUrlSettings()
    {
        /**
         * Don't check for data saving actions
         */
        if ($this->getRequest()->getPost() || $this->getRequest()->getQuery('isAjax')) {
            return $this;
        }

        $configData = Mage::getModel('core/config_data');

        $defaultUnsecure= (string) Mage::getConfig()->getNode('default/'.Mage_Core_Model_Store::XML_PATH_UNSECURE_BASE_URL);
        $defaultSecure  = (string) Mage::getConfig()->getNode('default/'.Mage_Core_Model_Store::XML_PATH_SECURE_BASE_URL);

        if ($defaultSecure == '{{base_url}}' || $defaultUnsecure == '{{base_url}}') {
            $this->_getSession()->addNotice(
                $this->__('{{base_url}} is not recommended to use in a production environment to declare the Base Unsecure Url / Base Secure Url. It is highly recommended to change this value in your Magento <a href="%s">configuration</a>.', $this->getUrl('adminhtml/system_config/edit', array('section'=>'web')))
            );
            return $this;
        }

        $dataCollection = $configData->getCollection()
            ->addValueFilter('{{base_url}}');

        $url = false;
        foreach ($dataCollection as $data) {
            if ($data->getScope() == 'stores') {
                $code = Mage::app()->getStore($data->getScopeId())->getCode();
                $url = $this->getUrl('adminhtml/system_config/edit', array('section'=>'web', 'store'=>$code));
            }
            if ($data->getScope() == 'websites') {
                $code = Mage::app()->getWebsite($data->getScopeId())->getCode();
                $url = $this->getUrl('adminhtml/system_config/edit', array('section'=>'web', 'website'=>$code));
            }

            if ($url) {
                $this->_getSession()->addNotice(
                    $this->__('{{base_url}} is not recommended to use in a production environment to declare the Base Unsecure Url / Base Secure Url. It is highly recommended to change this value in your Magento <a href="%s">configuration</a>.', $url)
                );
                return $this;
            }
        }
        return $this;
    }

    public function deniedAction()
    {
        $this->getResponse()->setHeader('HTTP/1.1','403 Forbidden');
        if (!Mage::getSingleton('admin/session')->isLoggedIn()) {
            $this->_redirect('*/index/login');
            return;
        }
        $this->loadLayout(array('default', 'adminhtml_denied'));
        $this->renderLayout();
    }

    public function loadLayout($ids=null, $generateBlocks=true, $generateXml=true)
    {
        parent::loadLayout($ids, $generateBlocks, $generateXml);
        $this->_initLayoutMessages('adminhtml/session');
        return $this;
    }

    public function norouteAction($coreRoute = null)
    {
        $this->getResponse()->setHeader('HTTP/1.1','404 Not Found');
        $this->getResponse()->setHeader('Status','404 File not found');
        $this->loadLayout(array('default', 'adminhtml_noroute'));
        $this->renderLayout();
    }


    /**
     * Retrieve currently used module name
     *
     * @return string
     */
    public function getUsedModuleName()
    {
        return $this->_usedModuleName;
    }

    /**
     * Set currently used module name
     *
     * @param string $moduleName
     * @return Mage_Adminhtml_Controller_Action
     */
    public function setUsedModuleName($moduleName)
    {
        $this->_usedModuleName = $moduleName;
        return $this;
    }

    /**
     * Translate a phrase
     *
     * @return string
     */
    public function __()
    {
        $args = func_get_args();
        $expr = new Mage_Core_Model_Translate_Expr(array_shift($args), $this->getUsedModuleName());
        array_unshift($args, $expr);
        return Mage::app()->getTranslator()->translate($args);
    }

    /**
     * Set referer url for redirect in responce
     *
     * Is overriden here to set defaultUrl to admin url
     *
     * @param   string $defaultUrl
     * @return  Mage_Adminhtml_Controller_Action
     */
    protected function _redirectReferer($defaultUrl=null)
    {
        $defaultUrl = empty($defaultUrl) ? $this->getUrl('*') : $defaultUrl;
        parent::_redirectReferer($defaultUrl);
        return $this;
    }

    /**
     * Declare headers and content file in responce for file download
     *
     * @param string $fileName
     * @param string $content set to null to avoid starting output, $contentLength should be set explicitly in that case
     * @param string $contentType
     * @param int $contentLength explicit content length, if strlen($content) isn't applicable
     * @return Mage_Adminhtml_Controller_Action
     */
    protected function _prepareDownloadResponse($fileName, $content, $contentType = 'application/octet-stream', $contentLength = null)
    {
        $session = Mage::getSingleton('admin/session');
        if ($session->isFirstPageAfterLogin()) {
            $this->_redirect($session->getUser()->getStartupPageUrl());
            return $this;
        }
        $this->getResponse()
            ->setHttpResponseCode(200)
            ->setHeader('Pragma', 'public', true)
            ->setHeader('Cache-Control', 'must-revalidate, post-check=0, pre-check=0', true)
            ->setHeader('Content-type', $contentType, true)
            ->setHeader('Content-Length', is_null($contentLength) ? strlen($content) : $contentLength)
            ->setHeader('Content-Disposition', 'attachment; filename=' . $fileName)
            ->setHeader('Last-Modified', date('r'));
        if (!is_null($content)) {
            $this->getResponse()->setBody($content);
        }
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
        $this->_getSession()->setIsUrlNotice($this->getFlag('', self::FLAG_IS_URLS_CHECKED));
        $this->getResponse()->setRedirect($this->getUrl($path, $arguments));
        return $this;
    }

    protected function _forward($action, $controller = null, $module = null, array $params = null)
    {
        $this->_getSession()->setIsUrlNotice($this->getFlag('', self::FLAG_IS_URLS_CHECKED));

        // Save original values for controller and action included in secret key Urls
        $_urlModel = Mage::getSingleton('adminhtml/url');
        if (!$_urlModel->getOriginalControllerName()) {
            $_urlModel->setOriginalControllerName($this->getRequest()->getControllerName());
        }
        if (!$_urlModel->getOriginalActionName()) {
            $_urlModel->setOriginalActionName($this->getRequest()->getActionName());
        }

        return parent::_forward($action, $controller, $module, $params);
    }

    /**
     * Generate url by route and parameters
     *
     * @param   string $route
     * @param   array $params
     * @return  string
     */
    public function getUrl($route='', $params=array())
    {
        return Mage::helper('adminhtml')->getUrl($route, $params);
    }


    /**
     * Validate Secret Key
     *
     * @return bool
     */
    protected function _validateSecretKey()
    {
        $url = Mage::getSingleton('adminhtml/url');

        if (!($secretKey = $this->getRequest()->getParam(Mage_Adminhtml_Model_Url::SECRET_KEY_PARAM_NAME, null))
            || $secretKey != $url->getSecretKey($url->getOriginalControllerName(), $url->getOriginalActionName())) {
            return false;
        }
        return true;
    }
}
