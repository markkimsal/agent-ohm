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


class Mage_Core_Model_Session_Abstract extends Mage_Core_Model_Session_Abstract_Varien
{
    const XML_PATH_COOKIE_DOMAIN    = 'web/cookie/cookie_domain';
    const XML_PATH_COOKIE_PATH      = 'web/cookie/cookie_path';
    const XML_PATH_COOKIE_LIFETIME  = 'web/cookie/cookie_lifetime';
    const XML_NODE_SESSION_SAVE     = 'global/session_save';
    const XML_NODE_SESSION_SAVE_PATH    = 'global/session_save_path';

    const XML_PATH_USE_REMOTE_ADDR  = 'web/session/use_remote_addr';
    const XML_PATH_USE_HTTP_VIA     = 'web/session/use_http_via';
    const XML_PATH_USE_X_FORWARDED  = 'web/session/use_http_x_forwarded_for';
    const XML_PATH_USE_USER_AGENT   = 'web/session/use_http_user_agent';

    const XML_NODE_USET_AGENT_SKIP  = 'global/session/validation/http_user_agent_skip';

    const SESSION_ID_QUERY_PARAM = 'SID';

    protected static $_urlHostCache = array();

    protected static $_encryptedSessionId;

    protected $_skipSessionIdFlag = false;

    public function init($namespace, $sessionName=null)
    {
        parent::init($namespace, $sessionName);
        $this->addHost(true);
        return $this;
    }

    /**
     * Retrieve Cookie domain
     *
     * @return string
     */
    public function getCookieDomain()
    {
        return $this->getCookie()->getDomain();
    }

    /**
     * Retrieve cookie path
     *
     * @return string
     */
    public function getCookiePath()
    {
        return $this->getCookie()->getPath();
    }

    /**
     * Retrieve cookie lifetime
     *
     * @return int
     */
    public function getCookieLifetime()
    {
        return $this->getCookie()->getLifetime();
    }

/**
     * Use REMOTE_ADDR in validator key
     *
     * @return bool
     */
    public function useValidateRemoteAddr()
    {
        $use = Mage::getStoreConfig(self::XML_PATH_USE_REMOTE_ADDR);
        if (is_null($use)) {
            return parent::useValidateRemoteAddr();
        }
        return (bool)$use;
    }

    /**
     * Use HTTP_VIA in validator key
     *
     * @return bool
     */
    public function useValidateHttpVia()
    {
        $use = Mage::getStoreConfig(self::XML_PATH_USE_HTTP_VIA);
        if (is_null($use)) {
            return parent::useValidateHttpVia();
        }
        return (bool)$use;
    }

    /**
     * Use HTTP_X_FORWARDED_FOR in validator key
     *
     * @return bool
     */
    public function useValidateHttpXForwardedFor()
    {
        $use = Mage::getStoreConfig(self::XML_PATH_USE_X_FORWARDED);
        if (is_null($use)) {
            return parent::useValidateHttpXForwardedFor();
        }
        return (bool)$use;
    }

    /**
     * Use HTTP_USER_AGENT in validator key
     *
     * @return bool
     */
    public function useValidateHttpUserAgent()
    {
        $use = Mage::getStoreConfig(self::XML_PATH_USE_USER_AGENT);
        if (is_null($use)) {
            return parent::useValidateHttpUserAgent();
        }
        return (bool)$use;
    }

    /**
     * Retrieve skip User Agent validation strings (Flash etc)
     *
     * @return array
     */
    public function getValidateHttpUserAgentSkip()
    {
        $userAgents = array();
        $skip = Mage::getConfig()->getNode(self::XML_NODE_USET_AGENT_SKIP);
        foreach ($skip->children() as $userAgent) {
            $userAgents[] = (string)$userAgent;
        }
        return $userAgents;
    }

    /**
     * Retrieve messages from session
     *
     * @param   bool $clear
     * @return  Mage_Core_Model_Message_Collection
     */
    public function getMessages($clear=false)
    {
        if (!$this->getData('messages')) {
            $this->setMessages(Mage::getModel('core/message_collection'));
        }

        if ($clear) {
            $messages = clone $this->getData('messages');
            $this->getData('messages')->clear();
            return $messages;
        }
        return $this->getData('messages');
    }

    /**
     * Not Mage exeption handling
     *
     * @param   Exception $exception
     * @param   string $alternativeText
     * @return  Mage_Core_Model_Session_Abstract
     */
    public function addException(Exception $exception, $alternativeText)
    {
        $this->addMessage(Mage::getSingleton('core/message')->error($alternativeText));
        return $this;
    }

    /**
     * Adding new message to message collection
     *
     * @param   Mage_Core_Model_Message_Abstract $message
     * @return  Mage_Core_Model_Session_Abstract
     */
    public function addMessage(Mage_Core_Model_Message_Abstract $message)
    {
        $this->getMessages()->add($message);
        return $this;
    }

    /**
     * Adding new error message
     *
     * @param   string $message
     * @return  Mage_Core_Model_Session_Abstract
     */
    public function addError($message)
    {
        $this->addMessage(Mage::getSingleton('core/message')->error($message));
        return $this;
    }

    /**
     * Adding new warning message
     *
     * @param   string $message
     * @return  Mage_Core_Model_Session_Abstract
     */
    public function addWarning($message)
    {
        $this->addMessage(Mage::getSingleton('core/message')->warning($message));
        return $this;
    }

    /**
     * Adding new nitice message
     *
     * @param   string $message
     * @return  Mage_Core_Model_Session_Abstract
     */
    public function addNotice($message)
    {
        $this->addMessage(Mage::getSingleton('core/message')->notice($message));
        return $this;
    }

    /**
     * Adding new success message
     *
     * @param   string $message
     * @return  Mage_Core_Model_Session_Abstract
     */
    public function addSuccess($message)
    {
        $this->addMessage(Mage::getSingleton('core/message')->success($message));
        return $this;
    }

    /**
     * Adding messages array to message collection
     *
     * @param   array $messages
     * @return  Mage_Core_Model_Session_Abstract
     */
    public function addMessages($messages)
    {
        if (is_array($messages)) {
            foreach ($messages as $message) {
                $this->addMessage($message);
            }
        }
        return $this;
    }

    /**
     * Specify session identifier
     *
     * @param   string|null $id
     * @return  Mage_Core_Model_Session_Abstract
     */
    public function setSessionId($id=null)
    {
        if (is_null($id)) {
            if (isset($_GET[self::SESSION_ID_QUERY_PARAM])) {
                $id = $_GET[self::SESSION_ID_QUERY_PARAM];
                /**
                 * No reason use crypt key for session
                 */
//                if ($tryId = Mage::helper('core')->decrypt($_GET[self::SESSION_ID_QUERY_PARAM])) {
//                    $id = $tryId;
//                }
            }
        }

        $this->addHost(true);
        return parent::setSessionId($id);
    }

    /**
     * Get ecrypted session identifuer
     * No reason use crypt key for session id encryption
     * we can use session identifier as is
     *
     * @return string
     */
    public function getEncryptedSessionId()
    {
        if (!self::$_encryptedSessionId) {
//            $helper = Mage::helper('core');
//            if (!$helper) {
//                return $this;
//            }
//            self::$_encryptedSessionId = $helper->encrypt($this->getSessionId());
            self::$_encryptedSessionId = $this->getSessionId();
        }
        return self::$_encryptedSessionId;
    }

    public function getSessionIdQueryParam()
    {
        return self::SESSION_ID_QUERY_PARAM;
    }

    /**
     * Set skip flag if need skip generating of _GET session_id_key param
     *
     * @param bool $flag
     * @return Mage_Core_Model_Session_Abstract
     */
    public function setSkipSessionIdFlag($flag)
    {
        $this->_skipSessionIdFlag = $flag;
        return $this;
    }

    /**
     * Retrieve session id skip flag
     *
     * @return bool
     */
    public function getSkipSessionIdFlag()
    {
        return $this->_skipSessionIdFlag;
    }

    /**
     * If the host was switched but session cookie won't recognize it - add session id to query
     *
     * @param string $urlHost can be host or url
     * @return string {session_id_key}={session_id_encrypted}
     */
    public function getSessionIdForHost($urlHost)
    {
        if ($this->getSkipSessionIdFlag() === true) {
            return '';
        }

        if (!$httpHost = Mage::app()->getFrontController()->getRequest()->getHttpHost()) {
            return '';
        }

        $urlHostArr = explode('/', $urlHost, 4);
        if (!empty($urlHostArr[2])) {
            $urlHost = $urlHostArr[2];
        }

        if (!isset(self::$_urlHostCache[$urlHost])) {
            $urlHostArr = explode(':', $urlHost);
            $urlHost = $urlHostArr[0];

            if ($httpHost !== $urlHost && !$this->isValidForHost($urlHost)) {
                $sessionId = $this->getEncryptedSessionId();
            } else {
                $sessionId = '';
            }
            self::$_urlHostCache[$urlHost] = $sessionId;
        }
        return self::$_urlHostCache[$urlHost];
    }

    public function isValidForHost($host)
    {
        $hostArr = explode(':', $host);
        $hosts = $this->getSessionHosts();
        return (!empty($hosts[$hostArr[0]]));
    }

    public function addHost($host)
    {
        if ($host === true) {
            if (!$host = Mage::app()->getFrontController()->getRequest()->getHttpHost()) {
                return $this;
            }
        }

        if (!$host) {
            return $this;
        }

        $hosts = $this->getSessionHosts();
        $hosts[$host] = true;
        $this->setSessionHosts($hosts);
        return $this;
    }

    public function getSessionHosts()
    {
        return $this->getData('session_hosts');
    }

    /**
     * Retrieve session save method
     *
     * @return string
     */
    public function getSessionSaveMethod()
    {
        if (Mage::isInstalled() && $sessionSave = Mage::getConfig()->getNode(self::XML_NODE_SESSION_SAVE)) {
            return $sessionSave;
        }
        return parent::getSessionSaveMethod();
    }

    /**
     * Get sesssion save path
     *
     * @return string
     */
    public function getSessionSavePath()
    {
        if (Mage::isInstalled() && $sessionSavePath = Mage::getConfig()->getNode(self::XML_NODE_SESSION_SAVE_PATH)) {
            return $sessionSavePath;
        }
        return parent::getSessionSavePath();
    }

}
