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
 * @package    Mage
 * @copyright  Copyright (c) 2008 Irubin Consulting Inc. DBA Varien (http://www.varien.com)
 * @license    http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */

$start = microtime(1);
/*
if (version_compare(phpversion(), '5.2.0', '<')===true) {
    echo  '<div style="font:12px/1.35em arial, helvetica, sans-serif;"><div style="margin:0 0 25px 0; border-bottom:1px solid #ccc;"><h3 style="margin:0; font-size:1.7em; font-weight:normal; text-transform:none; text-align:left; color:#2f2f2f;">Whoops, it looks like you have an invalid PHP version.</h3></div><p>Magento supports PHP 5.2.0 or newer. <a href="http://www.magentocommerce.com/install" target="">Find out</a> how to install</a> Magento using PHP-CGI as a work-around.</p></div>';
    exit;
}
 */

$AOFilename = 'app/AO.php';

if (!file_exists($AOFilename)) {
    if (is_dir('downloader')) {
        header("Location: downloader");
    } else {
        echo $AOFilename." was not found";
    }
    exit;
}

require_once $AOFilename;

#Varien_Profiler::enable();

AO::setIsDeveloperMode(true);

#ini_set('display_errors', 1);


/*
register_tick_function('tick_profiler');
declare(ticks=2);
// */


umask(0);
AO::run();


if (AO::getIsDeveloperMode(true)) {
$end = microtime(1);
if (! strstr($_SERVER['PHP_SELF'], 'ajax')) {
	echo '<div style="position:absolute;top:0px;background-color:#cfc">';
	echo sprintf('%.2f',($end - $start)*1000). 'ms';
	//echo ' - '. number_format(memory_get_peak_usage()).' b';
	echo ' - '. number_format(memory_get_peak_usage()/1024).' kb';
	echo '</div>';
}
}



/*
function tick_profiler($return=false) {
	   static $m=0;
	   static $lm=0;
//	   if ($return) return "$m bytes";
	   if (($mem=memory_get_usage())>$m) $m = $mem;
	   if ($mem >10300000) {echo "diff = ". (sprintf("%.2f", ($mem-$lm)/1024))." last = ".$lm." mem = ".$mem ." "; bt_die();}
	   $lm = $mem;
}


echo tick_profiler(true);
// */
