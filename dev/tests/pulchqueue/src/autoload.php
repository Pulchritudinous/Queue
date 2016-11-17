<?php
// Include Magento libraries
require_once realpath(MAGENTO_ROOT) . DIRECTORY_SEPARATOR . 'app' . DIRECTORY_SEPARATOR . 'Mage.php';

if (!Mage::isInstalled()) {
    echo "Application is not installed yet, please complete install wizard first.";
    exit;
}

// Start the Magento application
Mage::app('admin')->setUseSessionInUrl(false);
Mage::setIsDeveloperMode(true);

// Avoid issues "Headers already send"
session_start();

