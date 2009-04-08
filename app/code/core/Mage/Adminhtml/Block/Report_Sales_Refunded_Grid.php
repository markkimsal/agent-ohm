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
 * Adminhtml refunded report grid block
 *
 * @category   Mage
 * @package    Mage_Adminhtml
 * @author      Magento Core Team <core@magentocommerce.com>
 */
class Mage_Adminhtml_Block_Report_Sales_Refunded_Grid extends Mage_Adminhtml_Block_Report_Grid
{

    public function __construct()
    {
        parent::__construct();
        $this->setId('gridRefunded');
    }

    protected function _prepareCollection()
    {
        parent::_prepareCollection();
        $this->getCollection()->initReport('reports/refunded_collection');
    }

    protected function _prepareColumns()
    {
        $this->addColumn('orders', array(
            'header'    =>Mage::helper('reports')->__('Number of Refunded Orders'),
            'index'     =>'orders',
            'total'     =>'sum',
            'type'      =>'number'
        ));

        $currency_code = $this->getCurrentCurrencyCode();

        $this->addColumn('refunded', array(
            'header'    =>Mage::helper('reports')->__('Total Refunded'),
            'type'      =>'currency',
            'currency_code'=>$currency_code,
            'index'     =>'refunded',
            'total'     =>'sum',
            'renderer'  =>'adminhtml/report_grid_column_renderer_currency'
        ));

        $this->addColumn('online_refunded', array(
            'header'    =>Mage::helper('reports')->__('Online Refunded'),
            'type'      =>'currency',
            'currency_code'=>$currency_code,
            'index'     =>'online_refunded',
            'total'     =>'sum',
            'renderer'  =>'adminhtml/report_grid_column_renderer_currency'
        ));

        $this->addColumn('offline_refunded', array(
            'header'    =>Mage::helper('reports')->__('Offline Refunded'),
            'type'      =>'currency',
            'currency_code'=>$currency_code,
            'index'     =>'offline_refunded',
            'total'     =>'sum',
            'renderer'  =>'adminhtml/report_grid_column_renderer_currency'
        ));

        $this->addExportType('*/*/exportRefundedCsv', Mage::helper('reports')->__('CSV'));
        $this->addExportType('*/*/exportRefundedExcel', Mage::helper('reports')->__('Excel'));

        return parent::_prepareColumns();
    }
}