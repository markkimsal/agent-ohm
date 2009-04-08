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
 * Template model
 *
 * Example:
 *
 * // Loading of template
 * $emailTemplate  = Mage::getModel('core/email_template')
 *    ->load(Mage::getStoreConfig('path_to_email_template_id_config'));
 * $variables = array(
 *    'someObject' => Mage::getSingleton('some_model')
 *    'someString' => 'Some string value'
 * );
 * $emailTemplate->send('some@domain.com', 'Name Of User', $variables);
 *
 * @category   Mage
 * @package    Mage_Core
 * @author      Magento Core Team <core@magentocommerce.com>
 */
class Mage_Core_Model_Email_Template extends Varien_Object
{
    /**
     * Types of template
     */
    const TYPE_TEXT = 1;
    const TYPE_HTML = 2;

    /**
     * Configuration path for default email templates
     *
     */
    const XML_PATH_TEMPLATE_EMAIL = 'global/template/email';

    protected $_templateFilter;
    protected $_preprocessFlag = false;
    protected $_mail;

    static protected $_defaultTemplates;

    /**
     * Configuration of desing package for template
     *
     * @var Varien_Object
     */
    protected $_designConfig;

    /**
     * Return resource of template model.
     *
     * @return Mage_Newsletter_Model_Mysql4_Template
     */
    public function getResource()
    {
        return Mage::getResourceSingleton('core/email_template');
    }

    /**
     * Retrieve mail object instance
     *
     * @return Zend_Mail
     */
    public function getMail()
    {
        if (is_null($this->_mail)) {
            $this->_mail = new Zend_Mail('utf-8');
        }
        return $this->_mail;
    }

    /**
     * Declare template processing filter
     *
     * @param   Varien_Filter_Template $filter
     * @return  Mage_Core_Model_Email_Template
     */
    public function setTemplateFilter(Varien_Filter_Template $filter)
    {
        $this->_templateFilter = $filter;
        return $this;
    }

    /**
     * Get filter object for template processing logi
     *
     * @return Mage_Core_Model_Email_Template_Filter
     */
    public function getTemplateFilter()
    {
        if (empty($this->_templateFilter)) {
            $this->_templateFilter = Mage::getModel('core/email_template_filter');
            $this->_templateFilter->setUseAbsoluteLinks($this->getUseAbsoluteLinks());
        }
        return $this->_templateFilter;
    }

    /**
     * Load template by id
     *
     * @param   int $templateId
     * return   Mage_Newsletter_Model_Template
     */
    public function load($templateId)
    {
        $this->addData($this->getResource()->load($templateId));
        return $this;
    }

    /**
     * Load template by code
     *
     * @param   string $templateCode
     * return   Mage_Newsletter_Model_Template
     */
    public function loadByCode($templateCode)
    {
        $this->addData($this->getResource()->loadByCode($templateCode));
        return $this;
    }

    /**
     * Load default email template from locale translate
     *
     * @param string $templateId
     * @param string $locale
     */
    public function loadDefault($templateId, $locale=null)
    {
        $defaultTemplates = self::getDefaultTemplates();
        if (!isset($defaultTemplates[$templateId])) {
            return $this;
        }

        $data = &$defaultTemplates[$templateId];
        $this->setTemplateType($data['type']=='html' ? self::TYPE_HTML : self::TYPE_TEXT);

        $templateText = Mage::app()->getTranslator()->getTemplateFile(
            $data['file'], 'email', $locale
        );

        if (preg_match('/<!--@subject\s*(.*?)\s*@-->/', $templateText, $matches)) {
           $this->setTemplateSubject($matches[1]);
           $templateText = str_replace($matches[0], '', $templateText);
        }

        /**
         * Remove comment lines
         */
        $templateText = preg_replace('#\{\*.*\*\}#suU', '', $templateText);

        $this->setTemplateText($templateText);
        $this->setId($templateId);

        return $this;
    }

    /**
     * Retrive default templates from config
     *
     * @return array
     */
    static public function getDefaultTemplates()
    {
        if(is_null(self::$_defaultTemplates)) {
            self::$_defaultTemplates = Mage::getConfig()->getNode(self::XML_PATH_TEMPLATE_EMAIL)->asArray();
        }

        return self::$_defaultTemplates;
    }

    /**
     * Retrive default templates as options array
     *
     * @return array
     */
    static public function getDefaultTemplatesAsOptionsArray()
    {
        $options = array(
            array('value'=>'', 'label'=> '')
        );

        foreach (self::getDefaultTemplates() as $templateId=>$value) {
            $options[] = array('value'=>$templateId, 'label'=>$value['label']);
        }

        return $options;
    }

    /**
     * Return template id
     * return int|null
     */
    public function getId()
    {
        return $this->getTemplateId();
    }

    /**
     * Set id of template
     * @param int $value
     */
    public function setId($value)
    {
        return $this->setTemplateId($value);
    }

    /**
     * Return true if this template can be used for sending queue as main template
     *
     * @return boolean
     */
    public function isValidForSend()
    {
        return !Mage::getStoreConfigFlag('system/smtp/disable')
            && $this->getSenderName()
            && $this->getSenderEmail()
            && $this->getTemplateSubject();
    }

    /**
     * Return true if template type eq text
     *
     * @return boolean
     */
    public function isPlain()
    {
        return $this->getTemplateType() == self::TYPE_TEXT;
    }

    /**
     * Save template
     */
    public function save()
    {
        $this->getResource()->save($this);
        return $this;
    }

    /**
     * Process email template code
     *
     * @param   array $variables
     * @return  string
     */
    public function getProcessedTemplate(array $variables = array())
    {
        $processor = $this->getTemplateFilter();

        if(!$this->_preprocessFlag) {
            $variables['this'] = $this;
        }

        $processor->setIncludeProcessor(array($this, 'getInclude'))
            ->setVariables($variables);

        $this->_applyDesignConfig();
        try{
            $processedResult = $processor->filter($this->getTemplateText());
        }
        catch ( Exception $e)   {
            $this->_cancelDesignConfig();
            throw $e;
        }
        $this->_cancelDesignConfig();
        return $processedResult;
    }

    /**
     * Get template code for include directive
     *
     * @param   string $template
     * @param   array $variables
     * @return  string
     */
    public function getInclude($template, array $variables)
    {
        $thisClass = __CLASS__;
        $includeTemplate = new $thisClass();

        $includeTemplate->loadByCode($template);

        return $includeTemplate->getProcessedTemplate($variables);
    }

    /**
     * Send mail to recipient
     *
     * @param   string      $email		  E-mail
     * @param   string|null $name         receiver name
     * @param   array       $variables    template variables
     * @return  boolean
     **/
    public function send($email, $name=null, array $variables = array())
    {
        if(!$this->isValidForSend()) {
            return false;
        }

        if (is_null($name)) {
            $name = substr($email, 0, strpos($email, '@'));
        }

        $variables['email'] = $email;
        $variables['name'] = $name;

        ini_set('SMTP', Mage::getStoreConfig('system/smtp/host'));
        ini_set('smtp_port', Mage::getStoreConfig('system/smtp/port'));

        $mail = $this->getMail();
        if (is_array($email)) {
            foreach ($email as $emailOne) {
                $mail->addTo($emailOne, $name);
            }
        } else {
            $mail->addTo($email, '=?utf-8?B?'.base64_encode($name).'?=');
        }

        $this->setUseAbsoluteLinks(true);
        $text = $this->getProcessedTemplate($variables, true);

        if($this->isPlain()) {
            $mail->setBodyText($text);
        } else {
            $mail->setBodyHTML($text);
        }



        $mail->setSubject('=?utf-8?B?'.base64_encode($this->getProcessedTemplateSubject($variables)).'?=');
        $mail->setFrom($this->getSenderEmail(), $this->getSenderName());

        try {
            $mail->send(); // Zend_Mail warning..
            $this->_mail = null;
        }
        catch (Exception $e) {
            return false;
        }

        return true;
    }

    /**
     * Send transactional email to recipient
     *
     * @param   int $templateId
     * @param   string|array $sender sneder informatio, can be declared as part of config path
     * @param   string $email recipient email
     * @param   string $name recipient name
     * @param   array $vars varianles which can be used in template
     * @param   int|null $storeId
     * @return  Mage_Core_Model_Email_Template
     */
    public function sendTransactional($templateId, $sender, $email, $name, $vars=array(), $storeId=null)
    {
        $this->setSentSuccess(false);
        if (($storeId === null) && $this->getDesignConfig()->getStore()) {
            $storeId = $this->getDesignConfig()->getStore();
        }

        if (is_numeric($templateId)) {
            $this->load($templateId);
        } else {
            $localeCode = Mage::getStoreConfig('general/locale/code', $storeId);
            $this->loadDefault($templateId, $localeCode);
        }

        if (!$this->getId()) {
            throw Mage::exception('Mage_Core', Mage::helper('core')->__('Invalid transactional email code: '.$templateId));
        }

        if (!is_array($sender)) {
            $this->setSenderName(Mage::getStoreConfig('trans_email/ident_'.$sender.'/name', $storeId));
            $this->setSenderEmail(Mage::getStoreConfig('trans_email/ident_'.$sender.'/email', $storeId));
        } else {
            $this->setSenderName($sender['name']);
            $this->setSenderEmail($sender['email']);
        }

        $this->setSentSuccess($this->send($email, $name, $vars));
        return $this;
    }

    /**
     * Delete template from DB
     */
    public function delete()
    {
        $this->getResource()->delete($this->getId());
        $this->setId(null);
        return $this;
    }

    /**
     * Process email subject
     *
     * @param   array $variables
     * @return  string
     */
    public function getProcessedTemplateSubject(array $variables)
    {
        $processor = $this->getTemplateFilter();

        if(!$this->_preprocessFlag) {
            $variables['this'] = $this;
        }

        $processor->setVariables($variables);

        $this->_applyDesignConfig();
        try{
            $processedResult = $processor->filter($this->getTemplateSubject());
        }
        catch ( Exception $e)   {
            $this->_cancelDesignConfig();
            throw $e;
        }
        $this->_cancelDesignConfig();
        return $processedResult;
    }

    /**
     * Initialize design information for email template and subject processing
     *
     * @param   array $config
     * @return  Mage_Core_Model_Email_Template
     */
    public function setDesignConfig(array $config)
    {
        $this->getDesignConfig()->setData($config);
        return $this;
    }

    /**
     * Get design configuration data
     *
     * @return Varien_Object
     */
    public function getDesignConfig()
    {
        if(is_null($this->_designConfig)) {
            $this->_designConfig = new Varien_Object();
        }
        return $this->_designConfig;
    }

    /**
     * Apply declared configuration for design
     *
     * @return Mage_Core_Model_Email_Template
     */
    protected function _applyDesignConfig()
    {
        if ($this->getDesignConfig()) {
            $design = Mage::getDesign();
            $this->getDesignConfig()
                ->setOldArea($design->getArea())
                ->setOldStore($design->getStore());

            if ($this->getDesignConfig()->getArea()) {
                Mage::getDesign()->setArea($this->getDesignConfig()->getArea());
            }

            if ($this->getDesignConfig()->getStore()) {
                Mage::app()->getLocale()->emulate($this->getDesignConfig()->getStore());
                $design->setStore($this->getDesignConfig()->getStore());
                $design->setTheme('');
                $design->setPackageName('');
            }

        }
        return $this;
    }

    /**
     * Revert design settings to previous
     *
     * @return Mage_Core_Model_Email_Template
     */
    protected function _cancelDesignConfig()
    {
        if ($this->getDesignConfig()) {
            if ($this->getDesignConfig()->getOldArea()) {
                Mage::getDesign()->setArea($this->getDesignConfig()->getOldArea());
            }

            if ($this->getDesignConfig()->getOldStore()) {
                Mage::getDesign()->setStore($this->getDesignConfig()->getOldStore());
                Mage::getDesign()->setTheme('');
                Mage::getDesign()->setPackageName('');
            }
        }
        Mage::app()->getLocale()->revert();
        return $this;
    }

    public function addBcc($bcc)
    {
        if (is_array($bcc)) {
            foreach ($bcc as $email) {
                $this->getMail()->addBcc($email);
            }
        }
        elseif ($bcc) {
            $this->getMail()->addBcc($bcc);
        }
        return $this;
    }

    /**
     * Set Return Path
     *
     * @param string $email
     * @return Mage_Core_Model_Email_Template
     */
    public  function setReturnPath($email)
    {
        $this->getMail()->setReturnPath($email);
        return $this;
    }

    /**
     * Add Reply-To header
     *
     * @param string $email
     * @return Mage_Core_Model_Email_Template
     */
    public function setReplyTo($email)
    {
        $this->getMail()->addHeader('Reply-To', $email);
        return $this;
    }
}