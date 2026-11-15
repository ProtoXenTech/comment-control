<?php
/**
 * Fired when the plugin is uninstalled.
 *
 * @package Comment_Control
 */

// If uninstall not called from WordPress, exit.
if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}

// Delete main options.
delete_option( 'comment_control_settings' );
delete_site_option( 'comment_control_settings' );

// Clean up any remaining transients if created by speed checks.
global $wpdb;
// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
$wpdb->query( "DELETE FROM {$wpdb->options} WHERE option_name LIKE '_transient_cctl_%' OR option_name LIKE '_transient_timeout_cctl_%'" );
