<?php
/**
 * Upgrade script for argsellers v2.9.6
 * - Fix mailto leading space (%20) bug by strictly trimming email strings in PHP & JS
 */

if (!defined('_PS_VERSION_')) {
    exit;
}

function upgrade_module_2_9_6($module)
{
    return true;
}
