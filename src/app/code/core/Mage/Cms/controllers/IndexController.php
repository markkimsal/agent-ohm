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
 * @package    Mage_Cms
 * @copyright  Copyright (c) 2008 Irubin Consulting Inc. DBA Varien (http://www.varien.com)
 * @license    http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */

class Mage_Cms_IndexController extends Mage_Core_Controller_Front_Action
{
    public $outputHandler = 'output';
    public $cmsPage = null;

    public function indexAction($coreRoute = null)
    {
    	$pageId = AO::getStoreConfig('web/default/cms_home_page');
        if ($page = AO::helper('cms/page')->hasPage($this, $pageId)) {
            $this->outputHandler = 'cmsOutput';
            $this->cmsPage = $page;
        } else {
            $this->_forward('defaultIndex');
        }
    }

    public function cmsOutput() {
        $this->loadLayout(array('default', 'cms_page'), false, false);
        $this->getLayout()->getUpdate()->addUpdate($this->cmsPage->getLayoutUpdateXml());
        $this->generateLayoutXml()->generateLayoutBlocks();

        if ($storage = AO::getSingleton('catalog/session')) {
            $this->getLayout()->getMessagesBlock()->addMessages($storage->getMessages(true));
        }

        if ($storage = AO::getSingleton('checkout/session')) {
            $this->getLayout()->getMessagesBlock()->addMessages($storage->getMessages(true));
        }
        $this->renderLayout();
        return true;
    }

    public function defaultIndexAction()
    {
        $this->getResponse()->setHeader('HTTP/1.1','404 Not Found');
        $this->getResponse()->setHeader('Status','404 File not found');
    }

    public function noRouteAction($coreRoute = null)
    {
        $this->getResponse()->setHeader('HTTP/1.1','404 Not Found');
        $this->getResponse()->setHeader('Status','404 File not found');

        $pageId = AO::getStoreConfig('web/default/cms_no_route');
        if ($page = AO::helper('cms/page')->hasPage($this, $pageId)) {
        } else {
            $this->_forward('defaultNoRoute');
        }
    }

    public function defaultNoRouteAction()
    {
        $this->getResponse()->setHeader('HTTP/1.1','404 Not Found');
        $this->getResponse()->setHeader('Status','404 File not found');
    }
}
# vim: set expandtab:
# vim: set sw=4:
# vim: set ts=4:

