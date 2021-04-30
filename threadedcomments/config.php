<?php

if ( ! defined('THREADEDCOMMENTS_ADDON_NAME'))
{
	define('THREADEDCOMMENTS_ADDON_NAME',         'Threaded Comments');
	define('THREADEDCOMMENTS_ADDON_VERSION',      '3.1.1');
}

$config['name'] = THREADEDCOMMENTS_ADDON_NAME;
$config['version'] = THREADEDCOMMENTS_ADDON_VERSION;

$config['nsm_addon_updater']['versions_xml']='http://www.intoeetive.com/index.php/update.rss/386';
