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
 * Adminhtml customer orders grid block
 *
 * @category   Mage
 * @package    Mage_Adminhtml
 * @author      Magento Core Team <core@magentocommerce.com>
 */
class Mage_Adminhtml_Block_Customer_Edit_Tab_Reviews extends Mage_Adminhtml_Block_Widget_Grid
{

    public function __construct()
    {
        parent::__construct();
        $this->setId('reviewsGrid');
        $this->setUseAjax(true);
    }

    protected function _prepareCollection()
    {
        $collection = AO::getResourceModel('customer/customer_collection')
            ->addNameToSelect()
            ->addAttributeToSelect('email')
            ->addAttributeToSelect('created_at')
            ->joinAttribute('billing_postcode', 'customer_address/postcode', 'default_billing')
            ->joinAttribute('billing_city', 'customer_address/city', 'default_billing')
            ->joinAttribute('billing_telephone', 'customer_address/telephone', 'default_billing')
            ->joinAttribute('billing_country_id', 'customer_address/country_id', 'default_billing');

        $this->setCollection($collection);

        return parent::_prepareCollection();
    }

    protected function _prepareColumns()
    {
        $this->addColumn('entity_id', array(
            'header'    => AO::helper('customer')->__('ID'),
            'width'     => 5,
            'align'     => 'center',
            'sortable'  => true,
            'index'     => 'entity_id'
        ));

        $this->addColumn('name', array(
            'header'    => AO::helper('customer')->__('Name'),
            'index'     => 'name'
        ));

        $this->addColumn('email', array(
            'header'    => AO::helper('customer')->__('Email'),
            'width'     => 40,
            'align'     => 'center',
            'index'     => 'email'
        ));

        $this->addColumn('telephone', array(
            'header'    => AO::helper('customer')->__('Telephone'),
            'align'     => 'center',
            'index'     => 'billing_telephone'
        ));

        $this->addColumn('billing_postcode', array(
            'header'    => AO::helper('customer')->__('ZIP/Postal Code'),
            'index'     => 'billing_postcode',
        ));

        $this->addColumn('billing_country_id', array(
            'header'    => AO::helper('customer')->__('Country'),
            'type'      => 'country',
            'index'     => 'billing_country_id',
        ));

        $this->addColumn('customer_since', array(
            'header'    => AO::helper('customer')->__('Customer Since'),
            'type'      => 'date',
            'format'    => 'Y.m.d',
            'index'     => 'created_at',
        ));

        $this->addColumn('action',
            array(
                'header'    => AO::helper('customer')->__('Action'),
                'width'     => '50px',
                'type'      => 'action',
                'getter'     => 'getId',
                'actions'   => array(
                    array(
                        'caption' => AO::helper('customer')->__('Edit'),
                        'url'     => array(
                            'base'=>'*/catalog_product_review/edit'
                        ),
                        'field'   => 'id'
                    )
                ),
                'filter'    => false,
                'sortable'  => false,
        ));

        $this->setColumnFilter('entity_id')
            ->setColumnFilter('email')
            ->setColumnFilter('name');

        $this->addExportType('*/*/exportCsv', AO::helper('customer')->__('CSV'));
        $this->addExportType('*/*/exportXml', AO::helper('customer')->__('XML'));
        return parent::_prepareColumns();
    }

    public function getGridUrl()
    {
        return $this->getUrl('*/*/index', array('_current'=> true));
    }

    public function getRowUrl($row)
    {
        return $this->getUrl('*/catalog_product_review/edit', array('id'=>$row->getId()));
    }


}