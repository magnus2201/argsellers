<?php
/**
 * Upgrade script for argsellers v3.0.1
 * - Registers Elementor init hooks & registers custom IqitElementor widget template
 */

if (!defined('_PS_VERSION_')) {
    exit;
}

function upgrade_module_3_0_1($module)
{
    $module->registerHook('displayHeader');
    $module->registerHook('displayHome');
    $module->registerHook('displayLeftColumn');
    $module->registerHook('displayRightColumn');
    $module->registerHook('actionElementorInit');
    $module->registerHook('actionElementorWidgetInit');
    $module->registerHook('actionAdminControllerSetMedia');
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
