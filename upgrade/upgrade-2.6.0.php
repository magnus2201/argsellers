<?php
/**
 * 2026 ARGSEGURIDAD
 * PrestaShop Upgrade Script for version 2.6.0
 */

if (!defined('_PS_VERSION_')) {
    exit;
}

function upgrade_module_2_6_0($module)
{
    // Ensure database table structure is compliant
    $sql = 'CREATE TABLE IF NOT EXISTS `' . _DB_PREFIX_ . 'argsellers` (
        `id_seller` INT(11) NOT NULL AUTO_INCREMENT,
        `name` VARCHAR(255) NOT NULL,
        `role` VARCHAR(255) NOT NULL,
        `phone` VARCHAR(32) NOT NULL,
        `email` VARCHAR(255) NOT NULL,
        `image` VARCHAR(255) NOT NULL,
        `active` TINYINT(1) NOT NULL DEFAULT 1,
        `position` INT(11) NOT NULL DEFAULT 0,
        PRIMARY KEY (`id_seller`)
    ) ENGINE=' . _MYSQL_ENGINE_ . ' DEFAULT CHARSET=utf8;';

    Db::getInstance()->execute($sql);

    // Register essential hooks if missing
    $module->registerHook('displayHeader');
    $module->registerHook('filterHtmlContent');

    // Clear caches
    Tools::clearSmartyCache();
    Tools::clearXMLCache();
    Media::clearCache();

    return true;
}
