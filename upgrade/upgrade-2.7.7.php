<?php
/**
 * Upgrade script for argsellers v2.7.7
 * - Bigger seller photos (130px), larger text sizes
 * - Fixed dropdown transparency with solid background + isolation
 * - Update banner shows version before/after and download error details
 */

if (!defined('_PS_VERSION_')) {
    exit;
}

function upgrade_module_2_7_7($module)
{
    return true;
}
