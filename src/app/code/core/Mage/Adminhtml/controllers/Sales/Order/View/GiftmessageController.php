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
 * @package    Mage_Adminhtml
 * @copyright  Copyright (c) 2008 Irubin Consulting Inc. DBA Varien (http://www.varien.com)
 * @license    http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */


/**
 * Adminhtml sales order view gift messages controller
 *
 * @category   Mage
 * @package    Mage_Adminhtml
 * @author      Magento Core Team <core@magentocommerce.com>
 */
class Mage_Adminhtml_Sales_Order_View_GiftmessageController extends Mage_Adminhtml_Controller_Action
{
    /**
     * Additional initialization
     *
     */
    protected function _construct()
    {
        $this->setUsedModuleName('Mage_Sales');
    }

    public function saveAction()
    {
        try {
            $this->_getGiftmessageSaveModel()
                ->setGiftmessages($this->getRequest()->getParam('giftmessage'))
                ->saveAllInOrder();
        } catch (Mage_Core_Exception $e) {
            $this->_getSession()->addError($e->getMessage());
        } catch (Exception $e) {
            $this->_getSession()->addError(AO::helper('giftmessage')->__('Error while saving gift message'));
        }

        if($this->getRequest()->getParam('type')=='order_item') {
            $this->getResponse()->setBody(
                 $this->_getGiftmessageSaveModel()->getSaved() ? 'YES' : 'NO'
            );
        } else {
            $this->getResponse()->setBody(
                AO::helper('giftmessage')->__('Gift message has been successfully saved')
            );
        }
    }

    /**
     * Retrieve gift message save model
     *
     * @return Mage_Adminhtml_Model_Giftmessage_Save
     */
    protected function _getGiftmessageSaveModel()
    {
        return AO::getSingleton('adminhtml/giftmessage_save');
    }

}