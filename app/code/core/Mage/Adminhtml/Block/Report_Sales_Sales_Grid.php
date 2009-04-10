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
 * Adminhtml sales report grid block
 *
 * @category   Mage
 * @package    Mage_Adminhtml
 * @author      Magento Core Team <core@magentocommerce.com>
 */
class Mage_Adminhtml_Block_Report_Sales_Sales_Grid extends Mage_Adminhtml_Block_Report_Grid
{

    public function __construct()
    {
        parent::__construct();
        $this->setId('gridSales');
    }

    protected function _prepareCollection()
    {
        parent::_prepareCollection();
        $this->getCollection()->initReport('reports/order_collection');
    }

    protected function _prepareColumns()
    {
        $this->addColumn('orders', array(
            'header'    =>AO::helper('reports')->__('Number of Orders'),
            'index'     =>'orders',
            'total'     =>'sum',
            'type'      =>'number'
        ));

        $this->addColumn('items', array(
            'header'    =>AO::helper('reports')->__('Items Ordered'),
            'index'     =>'items',
            'total'     =>'sum',
            'type'      =>'number'
        ));

        $currency_code = $this->getCurrentCurrencyCode();

        $this->addColumn('subtotal', array(
            'header'    =>AO::helper('reports')->__('Subtotal'),
            'type'      =>'currency',
            'currency_code' => $currency_code,
            'index'     =>'subtotal',
            'total'     =>'sum',
            'renderer'  =>'adminhtml/report_grid_column_renderer_currency'
        ));

        $this->addColumn('tax', array(
            'header'    =>AO::helper('reports')->__('Tax'),
            'type'      =>'currency',
            'currency_code' => $currency_code,
            'index'     =>'tax',
            'total'     =>'sum',
            'renderer'  =>'adminhtml/report_grid_column_renderer_currency'
        ));

        $this->addColumn('shipping', array(
            'header'    =>AO::helper('reports')->__('Shipping'),
            'type'      =>'currency',
            'currency_code' => $currency_code,
            'index'     =>'shipping',
            'total'     =>'sum',
            'renderer'  =>'adminhtml/report_grid_column_renderer_currency'
        ));

        $this->addColumn('discount', array(
            'header'    =>AO::helper('reports')->__('Discounts'),
            'type'      =>'currency',
            'currency_code' => $currency_code,
            'index'     =>'discount',
            'total'     =>'sum',
            'renderer'  =>'adminhtml/report_grid_column_renderer_currency'
        ));

        $this->addColumn('total', array(
            'header'    =>AO::helper('reports')->__('Total'),
            'type'      =>'currency',
            'currency_code' => $currency_code,
            'index'     =>'total',
            'total'     =>'sum',
            'renderer'  =>'adminhtml/report_grid_column_renderer_currency'
        ));

        $this->addColumn('invoiced', array(
            'header'    =>AO::helper('reports')->__('Invoiced'),
            'type'      =>'currency',
            'currency_code' => $currency_code,
            'index'     =>'invoiced',
            'total'     =>'sum',
            'renderer'  =>'adminhtml/report_grid_column_renderer_currency'
        ));

        $this->addColumn('refunded', array(
            'header'    =>AO::helper('reports')->__('Refunded'),
            'type'      =>'currency',
            'currency_code' => $currency_code,
            'index'     =>'refunded',
            'total'     =>'sum',
            'renderer'  =>'adminhtml/report_grid_column_renderer_currency'
        ));

        $this->addExportType('*/*/exportSalesCsv', AO::helper('reports')->__('CSV'));
        $this->addExportType('*/*/exportSalesExcel', AO::helper('reports')->__('Excel'));

        return parent::_prepareColumns();
    }
}