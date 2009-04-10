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

/**
 * Cms block content
 *
 * @category   Mage
 * @package    Mage_Cms
 * @author      Magento Core Team <core@magentocommerce.com>
 */
class Mage_Cms_Block_Block extends Mage_Core_Block_Abstract
{
    protected function _toHtml()
    {
		if (!$this->_beforeToHtml()) {
			return '';
		}
        $html = '';
        if ($blockId = $this->getBlockId()) {
            $block = AO::getModel('cms/block')
                ->setStoreId(AO::app()->getStore()->getId())
                ->load($blockId);
            if (!$block->getIsActive()) {
                $html = '';
            } else {
                $content = $block->getContent();

                $processor = AO::getModel('core/email_template_filter');
                $html = $processor->filter($content);
            }
        }
        return $html;
    }
}
