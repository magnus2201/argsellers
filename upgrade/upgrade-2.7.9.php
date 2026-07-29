<?php
/**
 * Upgrade script for argsellers v2.7.9
 * - Fix mail button: use escape:'javascript' in data-email so JS reads raw email
 * - Slide-down + fade animation for floating popup (cubic-bezier spring easing)
 */

if (!defined('_PS_VERSION_')) {
    exit;
}

function upgrade_module_2_7_9($module)
{
    return true;
}
