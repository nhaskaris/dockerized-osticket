<?php
/*********************************************************************
    logo.php

    Simple logo to facilitate serving a customized client-side logo from
    osTicet. The logo is configurable in Admin Panel -> Settings -> Pages

    Peter Rotich <peter@osticket.com>
    Jared Hancock <jared@osticket.com>
    Copyright (c)  2006-2013 osTicket
    http://www.osticket.com

    Released under the GNU General Public License WITHOUT ANY WARRANTY.
    See LICENSE.TXT for details.

    vim: expandtab sw=4 ts=4 sts=4:
**********************************************************************/
// Use Noop Session Handler
define('NOOP_SESSION', true);
require('client.inc.php');

// Check for language-specific logo URL
$current_lang = Internationalization::getCurrentLanguage();
$lang_logos = $ost->getConfig()->get('lang_logos');
$lang_logos = $lang_logos ? json_decode($lang_logos, true) : array();

// If a language-specific URL is configured, redirect to it
if ($current_lang && isset($lang_logos[$current_lang]) && !empty($lang_logos[$current_lang])) {
    $logo_url = $lang_logos[$current_lang];
    header('Location: ' . $logo_url);
    exit;
}

// Otherwise use the default logo
$ttl = 86400; // max-age
if (($logo = $ost->getConfig()->getClientLogo())) {
    $logo->display(false, $ttl);
}

header("Cache-Control: private, max-age=$ttl");
header('Pragma: private');
header('Location: '.ASSETS_PATH.'images/logo.png');
?>
