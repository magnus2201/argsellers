<?php
/**
 * Upgrade script for argsellers v3.1.0
 * - Registers Shortcode {vendedores} and cleans up obsolete elementor files
 */

if (!defined('_PS_VERSION_')) {
    exit;
}

function upgrade_module_3_1_0($module)
{
    $module->registerHook('displayHeader');
    $module->registerHook('filterHtmlContent');

    $elementor_widget_file = _PS_MODULE_DIR_ . 'argsellers/classes/ElementorWidgetArgsellers.php';
    if (file_exists($elementor_widget_file)) {
        @unlink($elementor_widget_file);
    }

    try {
        Tools::clearSmartyCache();
        Tools::clearXMLCache();
        Media::clearCache();
    } catch (Exception $e) {}

    return true;
}
