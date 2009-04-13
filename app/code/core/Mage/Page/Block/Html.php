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
 * @package    Mage_Page
 * @copyright  Copyright (c) 2008 Irubin Consulting Inc. DBA Varien (http://www.varien.com)
 * @license    http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */

/**
 * Html page block
 *
 * @category   Mage
 * @package    Mage_Page
 * @author      Magento Core Team <core@magentocommerce.com>
 */
class Mage_Page_Block_Html extends Mage_Core_Block_Template
{
    protected $_urls = array();
    protected $_title = '';

	public function __call($name, $args) {
		if (strstr($name, 'add')) { return false;}
		return false;
		if (strstr($name, 'get')) { bt_die();}
//		return parent::__call($name, $args);
	}

    public function __construct($blockType)
    {
        parent::__construct();
        $this->_urls = array(
            'base'      => AO::getBaseUrl('web'),
            'baseSecure'=> AO::getBaseUrl('web', true),
            'current'   => AO::app()->getRequest()->getRequestUri()
        );

        $action = AO::app()->getFrontController()->getAction();
        if ($action) {
            $this->addBodyClass($action->getFullActionName('-'));
        }

		$c = strtolower($blockType);
		$cp = explode('_', $c);
		array_shift($cp);
//		var_dump($cp);
		$n  = array_shift($cp).'/';
		if ($n == 'page/') {
			//only page tags need special help setting the template
			array_shift($cp).'/';
			$n .= implode('_', $cp).'.phtml';
			$this->setTemplate($n);
		}

		//var_dump($n);
		
//        $this->setTemplate('page/html_head.phtml');
        $this->_beforeCacheUrl();
    }

    public function getBaseUrl()
    {
        return $this->_urls['base'];
    }

    public function getBaseSecureUrl()
    {
        return $this->_urls['baseSecure'];
    }

    public function getCurrentUrl()
    {
        return $this->_urls['current'];
    }

    /**
     *  Print Logo URL (Conf -> Sales -> Invoice and Packing Slip Design)
     *
     *  @return	  string
     */
    public function getPrintLogoUrl ()
    {
        // load html logo
        $logo = AO::getStoreConfig('sales/identity/logo_html');
        if (!empty($logo)) {
            $logo = 'sales/store/logo_html/' . $logo;
        }

        // load default logo
        if (empty($logo)) {
            $logo = AO::getStoreConfig('sales/identity/logo');
            if (!empty($logo)) {
                // prevent tiff format displaying in html
                if (strtolower(substr($logo, -5)) === '.tiff' || strtolower(substr($logo, -4)) === '.tif') {
                    $logo = '';
                }
                else {
                    $logo = 'sales/store/logo/' . $logo;
                }
            }
        }

        // buld url
        if (!empty($logo)) {
            $logo = AO::getStoreConfig('web/unsecure/base_media_url') . $logo;
        }
        else {
            $logo = '';
        }

        return $logo;
    }

    public function getPrintLogoText()
    {
        return AO::getStoreConfig('sales/identity/address');
    }

    public function setHeaderTitle($title)
    {
        $this->_title = $title;
        return $this;
    }

    public function getHeaderTitle()
    {
        return $this->_title;
    }

    /**
     * Add CSS class to page body tag
     *
     * @param string $className
     * @return Mage_Page_Block_Html
     */
    public function addBodyClass($className)
    {
        $className = preg_replace('#[^a-z0-9]+#', '-', strtolower($className));
        $this->setBodyClass($this->getBodyClass() . ' ' . $className);
        return $this;
    }

    public function getLang()
    {
        if (!$this->hasData('lang')) {
            $this->setData('lang', substr(AO::app()->getLocale()->getLocaleCode(), 0, 2));
        }
        return $this->getData('lang');
    }

    public function setTheme($theme)
    {
        $arr = explode('/', $theme);
        if (isset($arr[1])) {
            Mage_Core_Model_Design_Package::getDesign()->setPackageName($arr[0])->setTheme($arr[1]);
        } else {
            Mage_Core_Model_Design_Package::getDesign()->setTheme($theme);
        }
        return $this;
    }

    public function getBodyClass()
    {
        return $this->_getData('body_class');
    }

    public function getAbsoluteFooter()
    {
        return AO::getStoreConfig('design/footer/absolute_footer');
    }

    /**
     * Processing block html after rendering
     *
     * @param   string $html
     * @return  string
     */
    protected function _afterToHtml($html)
    {
        return $this->_afterCacheUrl($html);
    }















    public function addCss($name, $params="")
    {
        $this->addItem('skin_css', $name, $params);
        return $this;
    }

    public function addJs($name, $params="")
    {
        $this->addItem('js', $name, $params);
        return $this;
    }

    public function addCssIe($name, $params="")
    {
        $this->addItem('skin_css', $name, $params, 'IE');
        return $this;
    }

    public function addJsIe($name, $params="")
    {
        $this->addItem('js', $name, $params, 'IE');
        return $this;
    }

    public function addItem($type, $name, $params=null, $if=null, $cond=null)
    {
        if ($type==='skin_css' && empty($params)) {
            $params = 'media="all"';
        }
        $this->_data['items'][$type.'/'.$name] = array(
            'type'   => $type,
            'name'   => $name,
            'params' => $params,
            'if'     => $if,
            'cond'   => $cond,
       );
        return $this;
    }

    public function removeItem($type, $name)
    {
        unset($this->_data['items'][$type.'/'.$name]);
        return $this;
    }

    public function getCssJsHtml()
    {
//        return '';
        $lines = array();
        $baseJs = AO::getBaseUrl('js');
        $html = '';

        $script = '<script type="text/javascript" src="%s" %s></script>';
        $stylesheet = '<link rel="stylesheet" type="text/css" href="%s" %s />';
        $alternate = '<link rel="alternate" type="%s" href="%s" %s />';

        foreach ($this->_data['items'] as $item) {
            if (!is_null($item['cond']) && !$this->getData($item['cond'])) {
                continue;
            }
            $if = !empty($item['if']) ? $item['if'] : '';
            switch ($item['type']) {
                case 'js':
                    #$lines[$if]['other'][] = sprintf($script, $baseJs.$item['name'], $item['params']);
                    $lines[$if]['script'][] = $item['name'];
                    break;

                case 'js_css':
                    //proxying css will require real-time prepending path to all image urls, should we do it?
                    $lines[$if]['other'][] = sprintf($stylesheet, $baseJs.$item['name'], $item['params']);
                    #$lines[$if]['stylesheet'][] = $item['name'];
                    break;

                case 'skin_js':
                    $lines[$if]['other'][] = sprintf($script, $this->getSkinUrl($item['name']), $item['params']);
                    break;

                case 'skin_css':
                    $lines[$if]['other'][] = sprintf($stylesheet, $this->getSkinUrl($item['name']), $item['params']);
                    break;

                case 'rss':
                    $lines[$if]['other'][] = sprintf($alternate, 'application/rss+xml'/*'text/xml' for IE?*/, $item['name'], $item['params']);
                    break;
            }
        }

        foreach ($lines as $if=>$items) {
            if (!empty($if)) {
                $html .= '<!--[if '.$if.']>'."\n";
            }
            if (!empty($items['script'])) {
                $scriptItems = array();
                if (!AO::getStoreConfigFlag('dev/js/deprecation')) {
                    $scriptItems = $this->getChunkedItems($items['script'], 'index.php?c=auto&amp;f=');
                } else {
                    $scriptItems = $items['script'];
                }
                foreach ($scriptItems as $item) {
                    $html .= sprintf($script, $baseJs.$item, '') . "\n";
                }
//                foreach (array_chunk($items['script'], 15) as $chunk) {
//                    $html .= sprintf($script, $baseJs.'index.php/x.js?f='.join(',',$chunk), '')."\n";
//                }
            }
            if (!empty($items['stylesheet'])) {
                foreach ($this->getChunkedItems($items['stylesheet'], $baseJs.'index.php?c=auto&amp;f=') as $item) {
                    $html .= sprintf($stylesheet, $item, '')."\n";
                }
//                foreach (array_chunk($items['stylesheet'], 15) as $chunk) {
//                    $html .= sprintf($stylesheet, $baseJs.'index.php/x.css?f='.join(',',$chunk), '')."\n";
//                }
            }
            if (!empty($items['other'])) {
                $html .= join("\n", $items['other'])."\n";
            }
            if (!empty($if)) {
                $html .= '<![endif]-->'."\n";
            }
        }

        return $html;
    }

    public function getChunkedItems($items, $prefix='', $maxLen=450)
    {
        $chunks = array();
        $chunk = $prefix;
        foreach ($items as $i=>$item) {
            if (strlen($chunk.','.$item)>$maxLen) {
                $chunks[] = $chunk;
                $chunk = $prefix;
            }
            $chunk .= ','.$item;
        }
        $chunks[] = $chunk;
        return $chunks;
    }


    /**
     * Add link to the list
     *
     * @param string $label
     * @param string $url
     * @param string $title
     * @param boolean $prepare
     * @param array $urlParams
     * @param int $position
     * @param string|array $liParams
     * @param string|array $aParams
     * @param string $beforeText
     * @param string $afterText
     * @return Mage_Page_Block_Template_Links
     */
    public function addLink($label, $url='', $title='', $prepare=false, $urlParams=array(),
        $position=null, $liParams=null, $aParams=null, $beforeText='', $afterText='')
    {
        if (is_null($label) || false===$label) {
            return $this;
        }
        $link = new Varien_Object(array(
            'label'         => $label,
            'url'           => ($prepare ? $this->getUrl($url, (is_array($urlParams) ? $urlParams : array())) : $url),
            'title'         => $title,
            'li_params'     => $this->_prepareParams($liParams),
            'a_params'      => $this->_prepareParams($aParams),
            'before_text'   => $beforeText,
            'after_text'    => $afterText,
        ));

        if (intval($position) > 0) {
            while (isset($this->_links[$position])) {
                $position++;
            }
            $this->_links[$position] = $link;
            ksort($this->_links);
        } else {
            $position = 0;
            foreach ($this->_links as $k=>$v) {
                $position = $k;
            }
            $this->_links[$position+10] = $link;
        }

        return $this;
    }


    /**
     * Prepare tag attributes
     *
     * @param string|array $params
     * @return string
     */
    protected function _prepareParams($params)
    {
        if (is_string($params)) {
            return $params;
        } elseif (is_array($params)) {
            $result = '';
            foreach ($params as $key=>$value) {
                $result .= ' ' . $key . '="' . addslashes($value) . '"';
            }
            return $result;
        }
        return '';
    }
}
