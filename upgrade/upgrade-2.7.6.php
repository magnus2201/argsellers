<?php
/**
 * Upgrade script for argsellers v2.7.6
 * - Improved update banner: shows version before/after and download error reason
 */

if (!defined('_PS_VERSION_')) {
    exit;
}

function upgrade_module_2_7_6($module)
{
    // No DB changes needed, just return true
    return true;
}
