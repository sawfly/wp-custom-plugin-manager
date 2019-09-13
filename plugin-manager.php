<?php
/**
 * Plugin Name: Custom Plugin Manager
 * Description: Plugin provides possibility to install custom plugins from zip archive via API
 * Version: 1.0.0
 */

if(!class_exists('ZipArchive')){
    die('Plugin requires package ext-zip');
}

require_once ('_inc/plugin-manager-plugin-name-finder-trait.php');
require_once ('_inc/plugin-manager-api.php');
require_once ('_inc/plugin-manager-manager.php');
require_once ('_inc/plugin-manager-active-plugins.php');
require_once ('_inc/plugin-manager-active-plugin.php');

$controller = new Plugin_Installer_API();

add_action(
    'rest_api_init',
    function () use ($controller) {
        $controller->register_routes();
    }
);