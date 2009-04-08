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
 * @package    Mage_Newsletter
 * @copyright  Copyright (c) 2008 Irubin Consulting Inc. DBA Varien (http://www.varien.com)
 * @license    http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */


/**
 * Newsletter problem model collection
 *
 * @category   Mage
 * @package    Mage_Newsletter
 * @author      Magento Core Team <core@magentocommerce.com>
 */
class Mage_Newsletter_Model_Mysql4_Problem_Collection extends Mage_Core_Model_Mysql4_Collection_Abstract
{

    protected $_subscribersInfoJoinedFlag = false;
    protected $_problemGrouped = false;

    protected function _construct()
    {
        $this->_init('newsletter/problem');
    }

    public function addSubscriberInfo()
    {
        $this->getSelect()
            ->joinLeft(array('subscriber'=>$this->getTable('subscriber')),'main_table.subscriber_id = subscriber.subscriber_id',
                       array('subscriber_email','customer_id','subscriber_status'));
        $this->_subscribersInfoJoinedFlag = true;

        return $this;
    }

    public function addQueueInfo()
    {
        $this->getSelect()
            ->joinLeft(array('queue'=>$this->getTable('queue')),'main_table.queue_id = queue.queue_id',
                       array('queue_start_at', 'queue_finish_at'))
            ->joinLeft(array('template'=>$this->getTable('template')),'main_table.queue_id = queue.queue_id',
                       array('template_subject','template_code','template_sender_name','template_sender_email'));
        return $this;
    }


    /**
     * Loads customers info to collection
     *
     */
    protected function _addCustomersData( )
    {
        $customersIds = array();

        foreach ($this->getItems() as $item) {
            if($item->getCustomerId()) {
                $customersIds[] = $item->getCustomerId();
            }
        }

        if(count($customersIds) == 0) {
            return;
        }

        $customers = Mage::getResourceModel('customer/customer_collection')
            ->addNameToSelect()
            ->addAttributeToFilter('entity_id', array("in"=>$customersIds));

        $customers->load();

        foreach($customers->getItems() as $customer) {
            $problems = $this->getItemsByColumnValue('customer_id', $customer->getId());
            foreach ($problems as $problem) {
                $problem->setCustomerName($customer->getName())
                    ->setCustomerFirstName($customer->getFirstName())
                    ->setCustomerLastName($customer->getLastName());
            }
        }

    }

    public function load($printQuery=false, $logQuery=false)
    {
        parent::load($printQuery, $logQuery);
        if($this->_subscribersInfoJoinedFlag && !$this->isLoaded()) {
            $this->_addCustomersData();
        }
        return $this;
    }

}
