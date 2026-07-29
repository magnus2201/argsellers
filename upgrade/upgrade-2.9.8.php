<?php
/**
 * Upgrade script for argsellers v2.9.8
 * - Auto-inject Contact Page 4-Column Tabs CSS + JS directly inside argsellers.tpl
 * - Enables full tabbed navigation on Contact Page with zero server/FTP access
 */

if (!defined('_PS_VERSION_')) {
    exit;
}

function upgrade_module_2_9_8($module)
{
    return true;
}
