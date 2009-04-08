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
 * @package     Mage_Admin
 * @copyright   Copyright (c) 2008 Irubin Consulting Inc. DBA Varien (http://www.varien.com)
 * @license     http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */

/**
 * Admin user model
 *
 * @category    Mage
 * @package     Mage_Admin
 * @author      Magento Core Team <core@magentocommerce.com>
 */
class Mage_Admin_Model_User extends Mage_Core_Model_Abstract
{
    const XML_PATH_FORGOT_EMAIL_TEMPLATE    = 'admin/emails/forgot_email_template';
    const XML_PATH_FORGOT_EMAIL_IDENTITY    = 'admin/emails/forgot_email_identity';
    const XML_PATH_STARTUP_PAGE             = 'admin/startup/page';

    protected $_eventPrefix = 'admin_user';

    /**
     * @var Mage_Admin_Model_Roles
     */
    protected $_role;

    /**
     * Varien constructor
     */
    protected function _construct()
    {
        $this->_init('admin/user');
    }

    /**
     * Save user
     *
     * @return Mage_Admin_Model_User
     */
    public function save()
    {
        $this->_beforeSave();
        $data = array(
            'firstname' => $this->getFirstname(),
            'lastname'  => $this->getLastname(),
            'email'     => $this->getEmail(),
            'modified'  => now(),
            'extra'     => serialize($this->getExtra())
        );

        if($this->getId() > 0) {
            $data['user_id'] = $this->getId();
        }

        if( $this->getUsername() ) {
            $data['username'] = $this->getUsername();
        }

        if ($this->getPassword()) {
            $data['password'] = $this->_getEncodedPassword($this->getPassword());
        }

        if ($this->getNewPassword()) {
            $data['password'] = $this->_getEncodedPassword($this->getNewPassword());
        }
        elseif ($this->getPassword()) {
            $data['new_password'] = $this->getPassword();
        }

        if ( !is_null($this->getIsActive()) ) {
            $data['is_active'] = intval($this->getIsActive());
        }

        $this->addData($data);
        $this->_getResource()->save($this);
        $this->_afterSave();
        return $this;
    }

    /**
     * Delete user
     *
     * @return Mage_Admin_Model_User
     */
    public function delete()
    {
        $this->_getResource()->delete($this);
        return $this;
    }

    /**
     * Save user roles
     *
     * @return Mage_Admin_Model_User
     */
    public function saveRelations()
    {
        $this->_getResource()->_saveRelations($this);
        return $this;
    }

    public function getRoles()
    {
        return $this->_getResource()->getRoles($this);
    }

    /**
     * Get admin role model
     *
     * @return Mage_Admin_Model_Roles
     */
    public function getRole()
    {
        if (null === $this->_role) {
            $this->_role = Mage::getModel('admin/roles');
            $roles = $this->getRoles();
            if ($roles && isset($roles[0]) && $roles[0]) {
                $this->_role->load($roles[0]);
            }
        }
        return $this->_role;
    }

    public function deleteFromRole()
    {
        $this->_getResource()->deleteFromRole($this);
        return $this;
    }

    public function roleUserExists()
    {
        $result = $this->_getResource()->roleUserExists($this);
        return ( is_array($result) && count($result) > 0 ) ? true : false;
    }

    public function add()
    {
        $this->_getResource()->add($this);
        return $this;
    }

    public function userExists()
    {
        $result = $this->_getResource()->userExists($this);
        return ( is_array($result) && count($result) > 0 ) ? true : false;
    }

    public function getCollection() {
        return Mage::getResourceModel('admin/user_collection');
    }

    /**
     * Send email with new user password
     *
     * @return Mage_Admin_Model_User
     */
    public function sendNewPasswordEmail()
    {
        $translate = Mage::getSingleton('core/translate');
        /* @var $translate Mage_Core_Model_Translate */
        $translate->setTranslateInline(false);

        Mage::getModel('core/email_template')
            ->setDesignConfig(array('area' => 'adminhtml', 'store' => $this->getStoreId()))
            ->sendTransactional(
                Mage::getStoreConfig(self::XML_PATH_FORGOT_EMAIL_TEMPLATE),
                Mage::getStoreConfig(self::XML_PATH_FORGOT_EMAIL_IDENTITY),
                $this->getEmail(),
                $this->getName(),
                array('user' => $this, 'password' => $this->getPlainPassword()));

        $translate->setTranslateInline(true);

        return $this;
    }

    public function getName($separator=' ')
    {
        return $this->getFirstname() . $separator . $this->getLastname();
    }

    public function getId()
    {
        return $this->getUserId();
    }

    /**
     * Get user ACL role
     *
     * @return string
     */
    public function getAclRole()
    {
        return 'U' . $this->getUserId();
    }

    /**
     * Authenticate user name and password and save loaded record
     *
     * @param string $username
     * @param string $password
     * @return boolean
     * @throws Mage_Core_Exception
     */
    public function authenticate($username, $password)
    {
        $result = false;
        try {
            $this->loadByUsername($username);
            if ($this->getId()) {
                if ($this->getIsActive() != '1') {
                    Mage::throwException(Mage::helper('adminhtml')->__('This account is inactive.'));
                }
                if (Mage::helper('core')->validateHash($password, $this->getPassword())) {
                    $result = true;
                }
            }

            Mage::dispatchEvent('admin_user_authenticated', array(
                'username' => $username,
                'password' => $password,
                'user'     => $this,
                'result'   => $result,
            ));

            if (!$this->hasAssigned2Role($this->getId())) {
                Mage::throwException(Mage::helper('adminhtml')->__('Access Denied.'));
            }
        }
        catch (Mage_Core_Exception $e) {
            $this->unsetData();
            throw $e;
        }

        if (!$result) {
            $this->unsetData();
        }
        return $result;
    }

    /**
     * Login user
     *
     * @param   string $login
     * @param   string $password
     * @return  Mage_Admin_Model_User
     */
    public function login($username, $password)
    {
        if ($this->authenticate($username, $password)) {
            $this->getResource()->recordLogin($this);
        }

        // dispatch event regardless the user was logged in or not
        Mage::dispatchEvent('admin_user_on_login', array(
           'user'     => $this,
           'username' => $username,
           'password' => $password,
        ));

        return $this;
    }

    public function reload()
    {
        $id = $this->getId();
        $this->setId(null);
        $this->load($id);
        return $this;
    }

    public function loadByUsername($username)
    {
        $this->setData($this->getResource()->loadByUsername($username));
        return $this;
    }

    public function hasAssigned2Role($user)
    {
        return $this->getResource()->hasAssigned2Role($user);
    }

    protected function _getEncodedPassword($pwd)
    {
        return Mage::helper('core')->getHash($pwd, 2);
    }

    /**
     * Find first menu item that user is able to access
     *
     * @param Mage_Core_Model_Config_Element $parent
     * @param string $path
     * @param integer $level
     * @return string
     */
    public function findFirstAvailableMenu($parent=null, $path='', $level=0)
    {
        if ($parent == null) {
            $parent = Mage::getConfig()->getNode('adminhtml/menu');
        }
        foreach ($parent->children() as $childName=>$child) {
            $aclResource = 'admin/' . $path . $childName;
            if (Mage::getSingleton('admin/session')->isAllowed($aclResource)) {
                if (!$child->children) {
                    return (string)$child->action;
                } else if ($child->children) {
                    $action = $this->findFirstAvailableMenu($child->children, $path . $childName . '/', $level+1);
                    return $action ? $action : (string)$child->action;
                }
            }
        }
    }

    /**
     * Find admin start page url
     *
     * @deprecated Please use getStartupPageUrl() method instead
     * @see getStartupPageUrl()
     * @return string
     */
    public function getStatrupPageUrl()
    {
        return $this->getStartupPageUrl();
    }

    /**
     * Find admin start page url
     *
     * @return string
     */
    public function getStartupPageUrl()
    {
        $startupPage = Mage::getStoreConfig(self::XML_PATH_STARTUP_PAGE);
        $aclResource = 'admin/' . $startupPage;
        if (Mage::getSingleton('admin/session')->isAllowed($aclResource)) {
            $nodePath = 'adminhtml/menu/' . join('/children/', split('/', $startupPage)) . '/action';
            if ($url = Mage::getConfig()->getNode($nodePath)) {
                return $url;
            }
        }
        return $this->findFirstAvailableMenu();
    }

}
