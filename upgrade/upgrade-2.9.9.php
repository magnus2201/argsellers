<?php
/**
 * Upgrade script for argsellers v2.9.9
 * - Reverted contact tabs code from argsellers.tpl to keep argsellers single-purpose
 */

if (!defined('_PS_VERSION_')) {
    exit;
}

function upgrade_module_2_9_9($module)
{
    return true;
}
