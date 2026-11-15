<?php
/**
 * Plugin Name:       Comment Control
 * Plugin URI:        https://protoxen.com/comment-control/
 * Description:       Ultra-fast, zero-bloat comment governance, invisible anti-spam shield, privacy protection, and SEO optimization.
 * Version:           1.0.0
 * Requires at least: 6.0
 * Requires PHP:      7.4
 * Author:            ProtoXen
 * Author URI:        https://protoxen.com/
 * License:           GPL v2 or later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       comment-control
 * Domain Path:       /languages
 *
 * @package           Comment_Control
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

/**
 * Current plugin version.
 */
define( 'COMMENT_CONTROL_VERSION', '1.0.0' );

/**
 * Plugin directory path with trailing slash.
 */
define( 'COMMENT_CONTROL_PATH', plugin_dir_path( __FILE__ ) );

/**
 * Plugin directory URL with trailing slash.
 */
define( 'COMMENT_CONTROL_URL', plugin_dir_url( __FILE__ ) );

/**
 * Main plugin basename.
 */
define( 'COMMENT_CONTROL_BASENAME', plugin_basename( __FILE__ ) );

/**
 * Load core orchestrator class.
 */
require_once COMMENT_CONTROL_PATH . 'includes/class-comment-control.php';

/**
 * Execution initialization hook.
 */
function comment_control_run() {
	Comment_Control::get_instance();
}
add_action( 'plugins_loaded', 'comment_control_run' );
