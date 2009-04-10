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
 * Customer admin controller
 *
 * @category    Mage
 * @package     Mage_Adminhtml
 * @author      Magento Core Team <core@magentocommerce.com>
 */
class Mage_Adminhtml_CustomerController extends Mage_Adminhtml_Controller_Action
{

    protected function _initCustomer($idFieldName = 'id')
    {
        $customerId = (int) $this->getRequest()->getParam($idFieldName);
        $customer = AO::getModel('customer/customer');

        if ($customerId) {
            $customer->load($customerId);
        }

        AO::register('current_customer', $customer);
        return $this;
    }

    /**
     * Customers list action
     */
    public function indexAction()
    {
        if ($this->getRequest()->getQuery('ajax')) {
            $this->_forward('grid');
            return;
        }
        $this->loadLayout();

        /**
         * Set active menu item
         */
        $this->_setActiveMenu('customer/manage');

        /**
         * Append customers block to content
         */
        $this->_addContent(
            $this->getLayout()->createBlock('adminhtml/customer', 'customer')
        );

        /**
         * Add breadcrumb item
         */
        $this->_addBreadcrumb(AO::helper('adminhtml')->__('Customers'), AO::helper('adminhtml')->__('Customers'));
        $this->_addBreadcrumb(AO::helper('adminhtml')->__('Manage Customers'), AO::helper('adminhtml')->__('Manage Customers'));

        $this->renderLayout();
    }

    public function gridAction()
    {
        $this->loadLayout();
        $this->getResponse()->setBody($this->getLayout()->createBlock('adminhtml/customer_grid')->toHtml());
    }

    /**
     * Customer edit action
     */
    public function editAction()
    {
        $this->_initCustomer();
        $this->loadLayout();

        $customer = AO::registry('current_customer');

        // set entered data if was error when we do save
        $data = AO::getSingleton('adminhtml/session')->getCustomerData(true);

        if (isset($data['account'])) {
            $customer->addData($data['account']);
        }
        if (isset($data['address']) && is_array($data['address'])) {
            foreach ($data['address'] as $addressId => $address) {
                $addressModel = AO::getModel('customer/address')->setData($address)
                    ->setId($addressId);
                $customer->addAddress($addressModel);
            }
        }

        /**
         * Set active menu item
         */
        $this->_setActiveMenu('customer/new');

        $this->renderLayout();
    }

    /**
     * Create new customer action
     */
    public function newAction()
    {
        $this->_forward('edit');
    }

    /**
     * Delete customer action
     */
    public function deleteAction()
    {
        $this->_initCustomer();
        $customer = AO::registry('current_customer');
        if ($customer->getId()) {
            try {
                $customer->load($customer->getId());
                $customer->delete();
                AO::getSingleton('adminhtml/session')->addSuccess(AO::helper('adminhtml')->__('Customer was deleted'));
            }
            catch (Exception $e){
                AO::getSingleton('adminhtml/session')->addError($e->getMessage());
            }
        }
        $this->_redirect('*/customer');
    }

    /**
     * Save customer action
     */
    public function saveAction()
    {
        if ($data = $this->getRequest()->getPost()) {
        	$redirectBack   = $this->getRequest()->getParam('back', false);
            $this->_initCustomer('customer_id');
            $customer = AO::registry('current_customer');

            // Prepare customer saving data
            if (isset($data['account'])) {
                $customer->addData($data['account']);
            }

            if (isset($data['address'])) {
                // unset template data
                if (isset($data['address']['_template_'])) {
                    unset($data['address']['_template_']);
                }

                foreach ($data['address'] as $index => $addressData) {
                    $address = AO::getModel('customer/address');
                    $address->setData($addressData);

                    if ($addressId = (int) $index) {
                        $address->setId($addressId);
                    }
                    /**
                     * We need set post_index for detect default addresses
                     */
                    $address->setPostIndex($index);
                    $customer->addAddress($address);
                }
            }

            if(isset($data['subscription'])) {
                $customer->setIsSubscribed(true);
            } else {
                $customer->setIsSubscribed(false);
            }

            $isNewCustomer = !$customer->getId();
            try {
                if ($customer->getPassword() == 'auto') {
                    $customer->setPassword($customer->generatePassword());
                }

                // force new customer active
                if ($isNewCustomer) {
                    $customer->setForceConfirmed(true);
                }

                $customer->save();

                // send welcome email
                if ($customer->getWebsiteId() && $customer->hasData('sendemail')) {
                    if ($isNewCustomer) {
                        $customer->sendNewAccountEmail();
                    }
                    // confirm not confirmed customer
                    elseif ((!$customer->getConfirmation())) {
                        $customer->sendNewAccountEmail('confirmed');
                    }
                }

                // TODO? Send confirmation link, if deactivating account

                if ($newPassword = $customer->getNewPassword()) {
                    if ($newPassword == 'auto') {
                        $newPassword = $customer->generatePassword();
                    }
                    $customer->changePassword($newPassword);
                    $customer->sendPasswordReminderEmail();
                }

                AO::getSingleton('adminhtml/session')->addSuccess(AO::helper('adminhtml')->__('Customer was successfully saved'));
                AO::dispatchEvent('adminhtml_customer_save_after', array('customer' => $customer));

                if ($redirectBack) {
	                $this->_redirect('*/*/edit', array(
	                    'id'    => $customer->getId(),
	                    '_current'=>true
	                ));
	                return;
                }
            }
            catch (Exception $e){
                AO::getSingleton('adminhtml/session')->addError($e->getMessage());
                AO::getSingleton('adminhtml/session')->setCustomerData($data);
                $this->getResponse()->setRedirect($this->getUrl('*/customer/edit', array('id'=>$customer->getId())));
                return;
            }
        }
        $this->getResponse()->setRedirect($this->getUrl('*/customer'));
    }

    /**
     * Export customer grid to CSV format
     */
    public function exportCsvAction()
    {
        $fileName   = 'customers.csv';
        $content    = $this->getLayout()->createBlock('adminhtml/customer_grid')
            ->getCsv();

        $this->_prepareDownloadResponse($fileName, $content);
    }

    /**
     * Export customer grid to XML format
     */
    public function exportXmlAction()
    {
        $fileName   = 'customers.xml';
        $content    = $this->getLayout()->createBlock('adminhtml/customer_grid')
            ->getXml();

        $this->_prepareDownloadResponse($fileName, $content);
    }

    /**
     * Prepare file download response
     *
     * @todo remove in 1.3
     * @deprecated please use $this->_prepareDownloadResponse()
     * @see Mage_Adminhtml_Controller_Action::_prepareDownloadResponse()
     * @param string $fileName
     * @param string $content
     * @param string $contentType
     */
    protected function _sendUploadResponse($fileName, $content, $contentType='application/octet-stream')
    {
        $this->_prepareDownloadResponse($fileName, $content, $contentType);
    }

    /**
     * Customer orders grid
     *
     */
    public function ordersAction() {
        $this->_initCustomer();
        $this->getResponse()->setBody($this->getLayout()->createBlock('adminhtml/customer_edit_tab_orders')->toHtml());
    }

    /**
     * Customer last orders grid for ajax
     *
     */
    public function lastOrdersAction() {
        $this->_initCustomer();
        $this->getResponse()->setBody($this->getLayout()->createBlock('adminhtml/customer_edit_tab_view_orders')->toHtml());
    }

    /**
     * Customer newsletter grid
     *
     */
    public function newsletterAction()
    {
        $this->_initCustomer();
        $subscriber = AO::getModel('newsletter/subscriber')
            ->loadByCustomer(AO::registry('current_customer'));

        AO::register('subscriber', $subscriber);
        $this->getResponse()->setBody($this->getLayout()->createBlock('adminhtml/customer_edit_tab_newsletter_grid')->toHtml());
    }

    public function wishlistAction()
    {
        $this->_initCustomer();
        $customer = AO::registry('current_customer');
        if ($customer->getId()) {
            if($itemId = (int) $this->getRequest()->getParam('delete')) {
                try {
                    AO::getModel('wishlist/item')->load($itemId)
                        ->delete();
                }
                catch (Exception $e) {
                    //
                }
            }
        }
        $this->getResponse()->setBody($this->getLayout()->createBlock('adminhtml/customer_edit_tab_wishlist')->toHtml());
    }

    /**
     * Customer last view wishlist for ajax
     *
     */
    public function viewWishlistAction()
    {
        $this->_initCustomer();
        $this->getResponse()->setBody($this->getLayout()->createBlock('adminhtml/customer_edit_tab_view_wishlist')->toHtml());
    }

    /**
     * [Handle and then] get a cart grid contents
     *
     * @return string
     */
    public function cartAction()
    {
        $this->_initCustomer();
        $websiteId = $this->getRequest()->getParam('website_id');

        // delete an item from cart
        if ($deleteItemId = $this->getRequest()->getPost('delete')) {
            $quote = AO::getModel('sales/quote')
                ->setWebsite(AO::app()->getWebsite($websiteId))
                ->loadByCustomer(AO::registry('current_customer'));
            $item = $quote->getItemById($deleteItemId);
            $quote->removeItem($deleteItemId);
            $quote->save();
        }

        $this->getResponse()->setBody(
            $this->getLayout()->createBlock('adminhtml/customer_edit_tab_cart', '', array('website_id'=>$websiteId))
                ->toHtml()
        );
    }

    /**
     * Get shopping cart to view only
     *
     */
    public function viewCartAction()
    {
        $this->_initCustomer();

        $this->getResponse()->setBody(
            $this->getLayout()->createBlock('adminhtml/customer_edit_tab_view_cart')
                ->setWebsiteId($this->getRequest()->getParam('website_id'))
                ->toHtml()
        );
    }

    /**
     * Get shopping carts from all websites for specified client
     *
     * @return string
     */
    public function cartsAction()
    {
        $this->_initCustomer();
        $this->getResponse()->setBody(
            $this->getLayout()->createBlock('adminhtml/customer_edit_tab_carts')->toHtml()
        );
    }

    public function productReviewsAction()
    {
        $this->_initCustomer();
        $this->getResponse()->setBody(
            $this->getLayout()->createBlock('adminhtml/review_grid', 'admin.customer.reviews')
                ->setCustomerId(AO::registry('current_customer')->getId())
                ->setUseAjax(true)
                ->toHtml()
        );
    }

    public function productTagsAction()
    {
        $this->_initCustomer();
        $this->getResponse()->setBody(
            $this->getLayout()->createBlock('adminhtml/customer_edit_tab_tag', 'admin.customer.tags')
                ->setCustomerId(AO::registry('current_customer')->getId())
                ->setUseAjax(true)
                ->toHtml()
        );
    }

    public function tagGridAction()
    {
        $this->_initCustomer();
        $this->getResponse()->setBody(
            $this->getLayout()->createBlock('adminhtml/customer_edit_tab_tag', 'admin.customer.tags')
                ->setCustomerId(AO::registry('current_customer'))
                ->toHtml()
        );
    }

    public function validateAction()
    {
        $response = new Varien_Object();
        $response->setError(0);
        $websiteId = AO::app()->getStore()->getWebsiteId();
        $accountData = $this->getRequest()->getPost('account');


        $customer = AO::getModel('customer/customer');
        if ($id = $this->getRequest()->getParam('id')) {
            $customer->load($id);
            $websiteId = $customer->getWebsiteId();
        }
        if (isset($accountData['website_id'])) {
            $websiteId = $accountData['website_id'];
        }

        # Checking if we received email. If not - ERROR
        if( !($accountData['email']) ) {
            $response->setError(1);
            AO::getSingleton('adminhtml/session')->addError(AO::helper('adminhtml')->__("Please fill in 'email' field."));
            $this->_initLayoutMessages('adminhtml/session');
            $response->setMessage($this->getLayout()->getMessagesBlock()->getGroupedHtml());
        } else {
            # Trying to load customer with the same email and return error message
            # if customer with the same email address exisits
            $checkCustomer = AO::getModel('customer/customer')
                ->setWebsiteId($websiteId);
            $checkCustomer->loadByEmail($accountData['email']);
            if( $checkCustomer->getId() && ($checkCustomer->getId() != $customer->getId()) ) {
                $response->setError(1);
                AO::getSingleton('adminhtml/session')->addError(AO::helper('adminhtml')->__('Customer with the same email already exists.'));
                $this->_initLayoutMessages('adminhtml/session');
                $response->setMessage($this->getLayout()->getMessagesBlock()->getGroupedHtml());
            }
        }
        $this->getResponse()->setBody($response->toJson());
    }

    public function massSubscribeAction()
    {
        $customersIds = $this->getRequest()->getParam('customer');
        if(!is_array($customersIds)) {
             AO::getSingleton('adminhtml/session')->addError(AO::helper('adminhtml')->__('Please select customer(s)'));

        } else {
            try {
                foreach ($customersIds as $customerId) {
                    $customer = AO::getModel('customer/customer')->load($customerId);
                    $customer->setIsSubscribed(true);
                    $customer->save();
                }
                AO::getSingleton('adminhtml/session')->addSuccess(
                    AO::helper('adminhtml')->__(
                        'Total of %d record(s) were successfully updated', count($customersIds)
                    )
                );
            } catch (Exception $e) {
                AO::getSingleton('adminhtml/session')->addError($e->getMessage());
            }
        }
        $this->_redirect('*/*/index');
    }

    public function massUnsubscribeAction()
    {
        $customersIds = $this->getRequest()->getParam('customer');
        if(!is_array($customersIds)) {
             AO::getSingleton('adminhtml/session')->addError(AO::helper('adminhtml')->__('Please select customer(s)'));
        } else {
            try {
                foreach ($customersIds as $customerId) {
                    $customer = AO::getModel('customer/customer')->load($customerId);
                    $customer->setIsSubscribed(false);
                    $customer->save();
                }
                AO::getSingleton('adminhtml/session')->addSuccess(
                    AO::helper('adminhtml')->__(
                        'Total of %d record(s) were successfully updated', count($customersIds)
                    )
                );
            } catch (Exception $e) {
                AO::getSingleton('adminhtml/session')->addError($e->getMessage());
            }
        }

        $this->_redirect('*/*/index');
    }

    public function massDeleteAction()
    {
        $customersIds = $this->getRequest()->getParam('customer');
        if(!is_array($customersIds)) {
             AO::getSingleton('adminhtml/session')->addError(AO::helper('adminhtml')->__('Please select customer(s)'));
        } else {
            try {
                foreach ($customersIds as $customerId) {
                    $customer = AO::getModel('customer/customer')->load($customerId);
                    $customer->delete();
                }
                AO::getSingleton('adminhtml/session')->addSuccess(
                    AO::helper('adminhtml')->__(
                        'Total of %d record(s) were successfully deleted', count($customersIds)
                    )
                );
            } catch (Exception $e) {
                AO::getSingleton('adminhtml/session')->addError($e->getMessage());
            }
        }

        $this->_redirect('*/*/index');
    }

    public function massAssignGroupAction()
    {
        $customersIds = $this->getRequest()->getParam('customer');
        if(!is_array($customersIds)) {
             AO::getSingleton('adminhtml/session')->addError(AO::helper('adminhtml')->__('Please select customer(s)'));
        } else {
            try {
                foreach ($customersIds as $customerId) {
                    $customer = AO::getModel('customer/customer')->load($customerId);
                    $customer->setGroupId($this->getRequest()->getParam('group'));
                    $customer->save();
                }
                AO::getSingleton('adminhtml/session')->addSuccess(
                    AO::helper('adminhtml')->__(
                        'Total of %d record(s) were successfully updated', count($customersIds)
                    )
                );
            } catch (Exception $e) {
                AO::getSingleton('adminhtml/session')->addError($e->getMessage());
            }
        }

        $this->_redirect('*/*/index');
    }

    protected function _isAllowed()
    {
        return AO::getSingleton('admin/session')->isAllowed('customer/manage');
    }
}
