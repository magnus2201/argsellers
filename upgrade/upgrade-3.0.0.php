<?php
/**
 * Upgrade script for argsellers v3.0.0
 * - Registers IqitElementor Custom Widget template argsellers.tpl
 * - Registers Home & Column hooks and clears Smarty cache
 */

if (!defined('_PS_VERSION_')) {
    exit;
}

function upgrade_module_3_0_0($module)
{
    $module->registerHook('displayHeader');
    $module->registerHook('displayHome');
    $module->registerHook('displayLeftColumn');
    $module->registerHook('displayRightColumn');
    $module->registerHook('filterHtmlContent');

    if (method_exists($module, 'registerIqitElementorCustomWidget')) {
        $module->registerIqitElementorCustomWidget();
    }

    try {
        Tools::clearSmartyCache();
        Tools::clearXMLCache();
        Media::clearCache();
    } catch (Exception $e) {}

    return true;
}
