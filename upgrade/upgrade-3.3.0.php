<?php
/**
 * Upgrade script for argsellers v3.3.0
 * - Registers dynamic shortcode configuration ARGSELLERS_SHORTCODES
 */

if (!defined('_PS_VERSION_')) {
    exit;
}

function upgrade_module_3_3_0($module)
{
    $module->registerHook('displayHeader');
    $module->registerHook('filterHtmlContent');

    $existing = Configuration::get('ARGSELLERS_SHORTCODES');
    if (!$existing) {
        Configuration::updateValue('ARGSELLERS_SHORTCODES', json_encode(array('vendedores')));
    }

    try {
        Tools::clearSmartyCache();
        Tools::clearXMLCache();
        Media::clearCache();
    } catch (Exception $e) {}

    return true;
}
