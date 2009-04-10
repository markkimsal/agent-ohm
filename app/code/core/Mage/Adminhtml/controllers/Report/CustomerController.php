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
 *
 * Customer reports admin controller
 *
 * @category   Mage
 * @package    Mage_Adminhtml
 * @author      Magento Core Team <core@magentocommerce.com>
 */
class Mage_Adminhtml_Report_CustomerController extends Mage_Adminhtml_Controller_Action
{
    public function _initAction()
    {
        $act = $this->getRequest()->getActionName();
        if(!$act)
            $act = 'default';

        $this->loadLayout()
            ->_addBreadcrumb(AO::helper('reports')->__('Reports'), AO::helper('reports')->__('Reports'))
            ->_addBreadcrumb(AO::helper('reports')->__('Customers'), AO::helper('reports')->__('Customers'));
        return $this;
    }

    public function accountsAction()
    {
        $this->_initAction()
            ->_setActiveMenu('report/customer/accounts')
            ->_addBreadcrumb(AO::helper('adminhtml')->__('New Accounts'), AO::helper('adminhtml')->__('New Accounts'))
            ->_addContent($this->getLayout()->createBlock('adminhtml/report_customer_accounts'))
            ->renderLayout();
    }

    /**
     * Export new accounts report grid to CSV format
     */
    public function exportAccountsCsvAction()
    {
        $fileName   = 'new_accounts.csv';
        $content    = $this->getLayout()->createBlock('adminhtml/report_customer_accounts_grid')
            ->getCsv();

        $this->_prepareDownloadResponse($fileName, $content);
    }

    /**
     * Export new accounts report grid to Excel XML format
     */
    public function exportAccountsExcelAction()
    {
        $fileName   = 'accounts.xml';
        $content    = $this->getLayout()->createBlock('adminhtml/report_customer_accounts_grid')
            ->getExcel($fileName);

        $this->_prepareDownloadResponse($fileName, $content);
    }

    public function ordersAction()
    {
        $this->_initAction()
            ->_setActiveMenu('report/customer/orders')
            ->_addBreadcrumb(AO::helper('reports')->__('Customers by number of orders'),
                AO::helper('reports')->__('Customers by number of orders'))
            ->_addContent($this->getLayout()->createBlock('adminhtml/report_customer_orders'))
            ->renderLayout();
    }

    /**
     * Export customers most ordered report to CSV format
     */
    public function exportOrdersCsvAction()
    {
        $fileName   = 'customers_orders.csv';
        $content    = $this->getLayout()->createBlock('adminhtml/report_customer_orders_grid')
            ->getCsv();

        $this->_prepareDownloadResponse($fileName, $content);
    }

    /**
     * Export customers most ordered report to Excel XML format
     */
    public function exportOrdersExcelAction()
    {
        $fileName   = 'customers_orders.xml';
        $content    = $this->getLayout()->createBlock('adminhtml/report_customer_orders_grid')
            ->getExcel($fileName);

        $this->_prepareDownloadResponse($fileName, $content);
    }

    public function totalsAction()
    {
        $this->_initAction()
            ->_setActiveMenu('report/customer/totals')
            ->_addBreadcrumb(AO::helper('reports')->__('Customers by orders total'),
                AO::helper('reports')->__('Customers by orders total'))
            ->_addContent($this->getLayout()->createBlock('adminhtml/report_customer_totals'))
            ->renderLayout();
    }

    /**
     * Export customers biggest totals report to CSV format
     */
    public function exportTotalsCsvAction()
    {
        $fileName   = 'cuatomer_totals.csv';
        $content    = $this->getLayout()->createBlock('adminhtml/report_customer_totals_grid')
            ->getCsv();

        $this->_prepareDownloadResponse($fileName, $content);
    }

    /**
     * Export customers biggest totals report to Excel XML format
     */
    public function exportTotalsExcelAction()
    {
        $fileName   = 'customer_totals.xml';
        $content    = $this->getLayout()->createBlock('adminhtml/report_customer_totals_grid')
            ->getExcel($fileName);

        $this->_prepareDownloadResponse($fileName, $content);
    }

    protected function _isAllowed()
    {
        switch ($this->getRequest()->getActionName()) {
            case 'accounts':
                return AO::getSingleton('admin/session')->isAllowed('report/customers/accounts');
                break;
            case 'orders':
                return AO::getSingleton('admin/session')->isAllowed('report/customers/orders');
                break;
            case 'totals':
                return AO::getSingleton('admin/session')->isAllowed('report/customers/totals');
                break;
            default:
                return AO::getSingleton('admin/session')->isAllowed('report/customers');
                break;
        }
    }
}