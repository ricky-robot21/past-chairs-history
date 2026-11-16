<?php
/**
 * Runs when the plugin is deleted from the Plugins screen.
 *
 * It does nothing on purpose. History entries, their terms, and their media
 * stay in place so the committee never loses content by removing the plugin.
 *
 * @package flhist
 */

if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}
