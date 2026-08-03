<?php
/**
 * Upgrade script for argsellers v3.3.1
 * - Consolidated UI & shortcode deletion validation
 */

if (!defined('_PS_VERSION_')) {
    exit;
}

function upgrade_module_3_3_1($module)
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
