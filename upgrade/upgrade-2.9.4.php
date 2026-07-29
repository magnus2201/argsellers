<?php
/**
 * Upgrade script for argsellers v2.9.4
 * - Fix destroyTimer race condition: now tracked and cleared in showPopup/mouseenter
 * - Smart showPopup: only animates when popup was hidden; if already visible, just updates content+position
 * - Never destroys popup DOM while switching between cards
 */

if (!defined('_PS_VERSION_')) {
    exit;
}

function upgrade_module_2_9_4($module)
{
    return true;
}
