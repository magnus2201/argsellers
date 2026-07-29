<?php
/**
 * Upgrade script for argsellers v2.9.2
 * - Mail button: uses hidden <a> element .click() to bypass theme JS blockers
 * - Animation between cards: uses double requestAnimationFrame instead of offsetHeight reflow
 */

if (!defined('_PS_VERSION_')) {
    exit;
}

function upgrade_module_2_9_2($module)
{
    return true;
}
