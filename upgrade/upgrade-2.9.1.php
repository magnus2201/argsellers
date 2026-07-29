<?php
/**
 * Upgrade script for argsellers v2.9.1
 * - Mail button: uses data-mailto + JS click handler (bypasses theme preventDefault)
 * - Diagnostic console.log to verify email value read from data attribute
 * - Animation fix: removes visible class before reflow so transition re-fires between cards
 */

if (!defined('_PS_VERSION_')) {
    exit;
}

function upgrade_module_2_9_1($module)
{
    return true;
}
