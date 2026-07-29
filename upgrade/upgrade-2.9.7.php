<?php
/**
 * Upgrade script for argsellers v2.9.7
 * - Mail button now opens Gmail Web Compose directly in a new tab
 *   URL: https://mail.google.com/mail/?view=cm&fs=1&to={email}
 */

if (!defined('_PS_VERSION_')) {
    exit;
}

function upgrade_module_2_9_7($module)
{
    return true;
}
