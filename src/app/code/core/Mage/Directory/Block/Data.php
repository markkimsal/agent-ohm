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
 * @package    Mage_Directory
 * @copyright  Copyright (c) 2008 Irubin Consulting Inc. DBA Varien (http://www.varien.com)
 * @license    http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */

/**
 * Directory data block
 *
 * @category   Mage
 * @package    Mage_Directory
 * @author      Magento Core Team <core@magentocommerce.com>
 */
class Mage_Directory_Block_Data extends Mage_Core_Block_Template
{
    public function getLoadrRegionUrl()
    {
        return $this->getUrl('directory/json/childRegion');
    }

    public function getCountryCollection()
    {
        $collection = $this->getData('country_collection');
        if (is_null($collection)) {
            $collection = AO::getModel('directory/country')->getResourceCollection()
                ->loadByStore();
            $this->setData('country_collection', $collection);
        }

        return $collection;
    }

    public function getCountryHtmlSelect($defValue=null, $name='country_id', $id='country', $title='Country')
    {
        if (VPROF) Varien_Profiler::start('TEST: '.__METHOD__);
		if (is_null($defValue)) {
			$defValue = $this->getCountryId();
		}
		$cacheKey = 'DIRECTORY_COUNTRY_SELECT_STORE_'.AO::app()->getStore()->getCode();
		if (AO::app()->useCache('config') && $cache = AO::app()->loadCache($cacheKey)) {
		    $options = unserialize($cache);
		} else {
		    $options = $this->getCountryCollection()->toOptionArray();
		    if (AO::app()->useCache('config')) {
		        AO::app()->saveCache(serialize($options), $cacheKey, array('config'));
		    }
		}
        $html = $this->getLayout()->createBlock('core/html_select')
            ->setName($name)
            ->setId($id)
            ->setTitle(AO::helper('directory')->__($title))
            ->setClass('validate-select')
            ->setValue($defValue)
            ->setOptions($options)
            ->getHtml();

        if (VPROF) Varien_Profiler::stop('TEST: '.__METHOD__);
        return $html;
    }

    public function getRegionCollection()
    {
        $collection = $this->getData('region_collection');
        if (is_null($collection)) {
            $collection = AO::getModel('directory/region')->getResourceCollection()
                ->addCountryFilter($this->getCountryId())
                ->load();

            $this->setData('region_collection', $collection);
        }
        return $collection;
    }


    public function getRegionHtmlSelect()
    {
        if (VPROF) Varien_Profiler::start('TEST: '.__METHOD__);
		$cacheKey = 'DIRECTORY_REGION_SELECT_STORE'.AO::app()->getStore()->getId();
		if (AO::app()->useCache('config') && $cache = AO::app()->loadCache($cacheKey)) {
		    $options = unserialize($cache);
		} else {
		    $options = $this->getRegionCollection()->toOptionArray();
		    if (AO::app()->useCache('config')) {
		        AO::app()->saveCache(serialize($options), $cacheKey, array('config'));
		    }
		}
        $html = $this->getLayout()->createBlock('core/html_select')
            ->setName('region')
            ->setTitle(AO::helper('directory')->__('State/Province'))
            ->setId('state')
            ->setClass('required-entry validate-state')
            ->setValue($this->getRegionId())
            ->setOptions($options)
            ->getHtml();
        if (VPROF) Varien_Profiler::start('TEST: '.__METHOD__);
        return $html;
    }

    public function getCountryId()
    {
        $countryId = $this->getData('country_id');
        if (is_null($countryId)) {
            $countryId = AO::getStoreConfig('general/country/default');
        }
        return $countryId;
    }

    public function getRegionsJs()
    {
        if (VPROF) Varien_Profiler::start('TEST: '.__METHOD__);
    	$regionsJs = $this->getData('regions_js');
    	if (!$regionsJs) {
	    	$countryIds = array();
	    	foreach ($this->getCountryCollection() as $country) {
	    		$countryIds[] = $country->getCountryId();
	    	}
    		$collection = AO::getModel('directory/region')->getResourceCollection()
    			->addCountryFilter($countryIds)
    			->load();
	    	$regions = array();
	    	foreach ($collection as $region) {
	    		if (!$region->getRegionId()) {
	    			continue;
	    		}
	    		$regions[$region->getCountryId()][$region->getRegionId()] = array(
	    			'code'=>$region->getCode(),
	    			'name'=>$region->getName()
	    		);
	    	}
	    	$regionsJs = Zend_Json::encode($regions);
    	}
    	if (VPROF) Varien_Profiler::stop('TEST: '.__METHOD__);
    	return $regionsJs;
    }
}
