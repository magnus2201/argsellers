<?php
/**
 * Upgrade script for argsellers v3.3.2
 * - Supports %vendedores% and [argsellers] simultaneously & clears cache
 */

if (!defined('_PS_VERSION_')) {
    exit;
}

function upgrade_module_3_3_2($module)
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
