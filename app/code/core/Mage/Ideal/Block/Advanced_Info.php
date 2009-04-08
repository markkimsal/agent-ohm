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
 * @package    Mage_Ideal
 * @copyright  Copyright (c) 2008 Irubin Consulting Inc. DBA Varien (http://www.varien.com)
 * @license    http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */

/**
 * iDEAL Advanced Info Block
 *
 * @category    Mage
 * @package     Mage_Ideal
 * @author      Magento Core Team <core@magentocommerce.com>
 */
class Mage_Ideal_Block_Advanced_Info extends Mage_Payment_Block_Info
{
    protected function _construct()
    {
        parent::_construct();
        $this->setTemplate('ideal/advanced/info.phtml');
    }

    public function toPdf()
    {
        $this->setTemplate('ideal/advanced/pdf/info.phtml');
        return $this->toHtml();
    }

    /**
     * Gets Issuer Title from Payment Attribute
     *
     * @return string
     */
    public function getIssuerTitle()
    {
        if ($this->getInfo() instanceof Mage_Sales_Model_Quote_Payment) {
            $issuerList = unserialize($this->getInfo()->getIdealIssuerList());
            return $issuerList[$this->getInfo()->getIdealIssuerId()];
        } else {
            return $this->getInfo()->getIdealIssuerTitle();
        }
    }

}