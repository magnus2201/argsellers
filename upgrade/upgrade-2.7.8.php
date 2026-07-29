<?php
/**
 * Upgrade script for argsellers v2.7.8
 * - Floating popup architecture: popup lives in <body> via JS appendChild
 * - Uses getBoundingClientRect() for pixel-perfect positioning
 * - Completely escapes stacking context of brands carousel and any other module
 * - Fade-in animation preserved via CSS transition on opacity
 */

if (!defined('_PS_VERSION_')) {
    exit;
}

function upgrade_module_2_7_8($module)
{
    return true;
}
