<?php
/**
 * Plugin Name:       Past Chairs History Timeline
 * Description:       An interactive timeline of past chairs and milestones. Add the timeline to any page with the [flhist_timeline] shortcode.
 * Version:           0.1.0
 * Requires at least: 6.0
 * Requires PHP:      7.4
 * Text Domain:       flhist
 *
 * @package flhist
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'FLHIST_VERSION', '0.1.0' );
define( 'FLHIST_FILE', __FILE__ );
define( 'FLHIST_DIR', plugin_dir_path( __FILE__ ) );
define( 'FLHIST_URL', plugin_dir_url( __FILE__ ) );

require_once FLHIST_DIR . 'includes/class-flhist-post-types.php';
require_once FLHIST_DIR . 'includes/class-flhist-fields.php';
require_once FLHIST_DIR . 'includes/class-flhist-render.php';
require_once FLHIST_DIR . 'includes/class-flhist-importer.php';

/**
 * Start the plugin.
 */
function flhist_bootstrap() {
	FLHist_Post_Types::init();
	FLHist_Fields::init();
	FLHist_Render::init();
	FLHist_Importer::init();
}
add_action( 'plugins_loaded', 'flhist_bootstrap' );

/**
 * On activation: register the types, create the Chair and Milestone terms,
 * and set the decade on any entries that already exist.
 */
function flhist_activate() {
	FLHist_Post_Types::register();
	FLHist_Post_Types::create_type_terms();
	FLHist_Post_Types::assign_all_decades();
}
register_activation_hook( __FILE__, 'flhist_activate' );
