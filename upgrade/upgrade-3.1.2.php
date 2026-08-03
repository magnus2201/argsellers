<?php
/**
 * Upgrade script for argsellers v3.1.2
 * - Registers Shortcode %vendedores% and clears cache
 */

if (!defined('_PS_VERSION_')) {
    exit;
}

function upgrade_module_3_1_2($module)
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
