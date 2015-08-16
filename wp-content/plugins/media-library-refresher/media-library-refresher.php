<?php

/**
 * The plugin bootstrap file
 *
 * This file is read by WordPress to generate the plugin information in the plugin
 * Dashboard. This file also includes all of the dependencies used by the plugin,
 * registers the activation and deactivation functions, and defines a function
 * that starts the plugin.
 *
 * @link              http://zach-adams.com
 * @since             1.0.0
 * @package           Media_Library_Refresher
 *
 * @wordpress-plugin
 * Plugin Name:       Media Library Refresher
 * Plugin URI:        http://zach-adams.com
 * Description:       This plugin will refresh your images if they are showing up as blank thumbnails.
 * Version:           1.0.0
 * Author:            Zach Adams
 * Author URI:        http://zach-adams.com
 * License:           GPL-2.0+
 * License URI:       http://www.gnu.org/licenses/gpl-2.0.txt
 * Text Domain:       media-library-refresher
 * Domain Path:       /languages
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

/**
 * The code that runs during plugin activation.
 * This action is documented in includes/class-media-library-refresher-activator.php
 */
function activate_media_library_refresher() {
	require_once plugin_dir_path( __FILE__ ) . 'includes/class-media-library-refresher-activator.php';
	Media_Library_Refresher_Activator::activate();
}

/**
 * The code that runs during plugin deactivation.
 * This action is documented in includes/class-media-library-refresher-deactivator.php
 */
function deactivate_media_library_refresher() {
	require_once plugin_dir_path( __FILE__ ) . 'includes/class-media-library-refresher-deactivator.php';
	Media_Library_Refresher_Deactivator::deactivate();
}

register_activation_hook( __FILE__, 'activate_media_library_refresher' );
register_deactivation_hook( __FILE__, 'deactivate_media_library_refresher' );

/**
 * The core plugin class that is used to define internationalization,
 * dashboard-specific hooks, and public-facing site hooks.
 */
require plugin_dir_path( __FILE__ ) . 'includes/class-media-library-refresher.php';

/**
 * Begins execution of the plugin.
 *
 * Since everything within the plugin is registered via hooks,
 * then kicking off the plugin from this point in the file does
 * not affect the page life cycle.
 *
 * @since    1.0.0
 */
function run_media_library_refresher() {

	$plugin = new Media_Library_Refresher();
	$plugin->run();

}
run_media_library_refresher();
