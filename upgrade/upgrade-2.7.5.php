<?php
/**
 * 2026 ARGSEGURIDAD
 * PrestaShop Upgrade Script for version 2.7.5
 */

if (!defined('_PS_VERSION_')) {
    exit;
}

function upgrade_module_2_7_5($module)
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

    // Auto-repair missing Backoffice Tab entry for AdminArgsSellers
    $id_tab = (int)Tab::getIdFromClassName('AdminArgsSellers');
    if (!$id_tab) {
        $tab = new Tab();
        $tab->active = 1;
        $tab->class_name = 'AdminArgsSellers';
        $tab->name = array();
        foreach (Language::getLanguages(true) as $lang) {
            $tab->name[$lang['id_lang']] = 'Vendedores';
        }
        $tab->id_parent = (int)Tab::getIdFromClassName('AdminParentThemes');
        $tab->module = 'argsellers';
        $tab->add();
    } else {
        // Ensure Tab is active and mapped to parent
        $tab = new Tab($id_tab);
        $tab->active = 1;
        $tab->id_parent = (int)Tab::getIdFromClassName('AdminParentThemes');
        $tab->save();
    }

    // Register essential hooks if missing
    $module->registerHook('displayHeader');
    $module->registerHook('filterHtmlContent');

    // Clear caches
    try {
        Tools::clearSmartyCache();
        Tools::clearXMLCache();
        Media::clearCache();
    } catch (Exception $e) {
        // Ignore cache clear exceptions
    }

    return true;
}
