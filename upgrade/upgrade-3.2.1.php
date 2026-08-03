<?php
/**
 * Upgrade script for argsellers v3.2.1
 * - Registers Shortcode %vendedores% and flushes cache
 */

if (!defined('_PS_VERSION_')) {
    exit;
}

function upgrade_module_3_2_1($module)
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
