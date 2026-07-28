<?php
/**
 * Diagnostic loader for AdminArgsSellersController v1.8
 */
error_reporting(E_ALL);
ini_set('display_errors', 1);

define('_PS_MODE_DEV_', true);

$config = dirname(__FILE__) . '/../config/config.inc.php';
if (file_exists($config)) {
    require_once($config);
    echo "<p style='color:green'>PrestaShop Config loaded!</p>";
} else {
    echo "<p style='color:red'>Config not found at $config</p>";
    exit;
}

try {
    require_once(dirname(__FILE__) . '/classes/ArgsellerModel.php');
    echo "<p style='color:green'>ArgsellerModel loaded!</p>";
} catch (Exception $e) {
    echo "<p style='color:red'>Model error: " . $e->getMessage() . "</p>";
}

try {
    require_once(dirname(__FILE__) . '/controllers/admin/AdminArgsSellersController.php');
    echo "<p style='color:green'>AdminArgsSellersController loaded!</p>";

    $ctrl = new AdminArgsSellersController();
    echo "<p style='color:green'>AdminArgsSellersController instantiated successfully!</p>";
} catch (Exception $e) {
    echo "<p style='color:red'>Controller error: " . $e->getMessage() . "</p>";
}
