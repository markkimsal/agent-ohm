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
 * Admin ratings controller
 *
 * @category   Mage
 * @package    Mage_Adminhtml
 * @author      Magento Core Team <core@magentocommerce.com>
 */

class Mage_Adminhtml_RatingController extends Mage_Adminhtml_Controller_Action
{
    public function indexAction()
    {
        $this->_initEnityId();
        $this->loadLayout();

        $this->_setActiveMenu('catalog/ratings');
        $this->_addBreadcrumb(AO::helper('adminhtml')->__('Manage Ratings'), AO::helper('adminhtml')->__('Manage Ratings'));
        $this->_addContent($this->getLayout()->createBlock('adminhtml/rating_rating'));

        $this->renderLayout();
    }

    public function editAction()
    {
        $this->_initEnityId();
        $this->loadLayout();

        $this->_setActiveMenu('catalog/ratings');
        $this->_addBreadcrumb(AO::helper('adminhtml')->__('Manage Ratings'), AO::helper('adminhtml')->__('Manage Ratings'));

        $this->_addContent($this->getLayout()->createBlock('adminhtml/rating_edit'))
            ->_addLeft($this->getLayout()->createBlock('adminhtml/rating_edit_tabs'));
        $this->renderLayout();
    }

    public function newAction()
    {
        $this->_forward('edit');
    }

    public function saveAction()
    {
        $this->_initEnityId();

        if ( $this->getRequest()->getPost() ) {
            try {
                $ratingModel = AO::getModel('rating/rating');

                $stores = $this->getRequest()->getParam('stores');
                $stores[] = 0;
                $ratingModel->setRatingCode($this->getRequest()->getParam('rating_code'))
                      ->setRatingCodes($this->getRequest()->getParam('rating_codes'))
                      ->setStores($stores)
                      ->setId($this->getRequest()->getParam('id'))
                      ->setEntityId(AO::registry('entityId'))
                      ->save();

                $options = $this->getRequest()->getParam('option_title');

                if( is_array($options) ) {
                    $i = 1;
                    foreach( $options as $key => $optionCode ) {
                        $optionModel = AO::getModel('rating/rating_option');
                        if( !preg_match("/^add_([0-9]*?)$/", $key) ) {
                            $optionModel->setId($key);
                        }

                        $optionModel->setCode($optionCode)
                            ->setValue($i)
                            ->setRatingId($ratingModel->getId())
                            ->setPosition($i)
                            ->save();
                        $i++;
                    }
                }

                AO::getSingleton('adminhtml/session')->addSuccess(AO::helper('adminhtml')->__('Rating was successfully saved'));
                AO::getSingleton('adminhtml/session')->setRatingData(false);

                $this->_redirect('*/*/');
                return;
            } catch (Exception $e) {
                AO::getSingleton('adminhtml/session')->addError($e->getMessage());
                AO::getSingleton('adminhtml/session')->setRatingData($this->getRequest()->getPost());
                $this->_redirect('*/*/edit', array('id' => $this->getRequest()->getParam('id')));
                return;
            }
        }
        $this->_redirect('*/*/');
    }

    public function deleteAction()
    {
        if( $this->getRequest()->getParam('id') > 0 ) {
            try {
                $model = AO::getModel('rating/rating');
                /* @var $model Mage_Rating_Model_Rating */
                $model->setId($this->getRequest()->getParam('id'))
                    ->delete();
                AO::getSingleton('adminhtml/session')->addSuccess(AO::helper('adminhtml')->__('Rating was successfully deleted'));
                $this->_redirect('*/*/');
            } catch (Exception $e) {
                AO::getSingleton('adminhtml/session')->addError($e->getMessage());
                $this->_redirect('*/*/edit', array('id' => $this->getRequest()->getParam('id')));
            }
        }
        $this->_redirect('*/*/');
    }

    protected function _initEnityId()
    {
        AO::register('entityId', AO::getModel('rating/rating_entity')->getIdByCode('product'));
    }

    protected function _isAllowed()
    {
	    return AO::getSingleton('admin/session')->isAllowed('catalog/reviews_ratings/ratings');
    }

}
