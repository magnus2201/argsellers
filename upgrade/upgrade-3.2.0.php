<?php
/**
 * Upgrade script for argsellers v3.2.0
 * - Restores exact clean working v2.9.9 / v2.9.7 architecture with [argsellers] shortcode
 */

if (!defined('_PS_VERSION_')) {
    exit;
}

function upgrade_module_3_2_0($module)
{
    $module->registerHook('displayHeader');
    $module->registerHook('filterHtmlContent');

    try {
        Tools::clearSmartyCache();
        Tools::clearXMLCache();
        Media::clearCache();
    } catch (Exception $e) {}

    return true;
}
