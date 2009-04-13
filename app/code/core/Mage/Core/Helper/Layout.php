<?php

class Mage_Core_Helper_Layout {

	public static function getTitle() {
        $x = AO::getStoreConfig('design/head/default_title');
        return htmlspecialchars(html_entity_decode($x, ENT_QUOTES, 'UTF-8'));
	}


    public static function getSkinUrl($forceSecure=false) {
		$secure = ($forceSecure || ( isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] == 'on'));
		return AO::app()->getStore()->getBaseUrl(Mage_Core_Model_Store::URL_TYPE_SKIN, $secure);
    }

    /**
     * Get miscellanious scripts/styles to be included in head before head closing tag
     *
     * @return string
     */
    public static function getIncludes()
    {
       return AO::getStoreConfig('design/head/includes');
    }

    public function getLogoSrc() {
        return Mage_Core_Model_Design_Package::getDesign()->getSkinUrl(AO::getStoreConfig('design/header/logo_src'),array());
        //return self::getSkinUrl(). AO::getStoreConfig('design/header/logo_src') ;
	}

    public function getLogoAlt() {
		return AO::getStoreConfig('design/header/logo_alt');
	}
}
