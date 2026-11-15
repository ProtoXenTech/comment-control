<?php
/**
 * Master Orchestrator for Comment Control.
 *
 * @package Comment_Control
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class Comment_Control
 */
class Comment_Control {

	/**
	 * Singleton instance.
	 *
	 * @var Comment_Control|null
	 */
	private static $instance = null;

	/**
	 * Active settings array.
	 *
	 * @var array
	 */
	private $settings = array();

	/**
	 * Default settings map.
	 *
	 * @var array
	 */
	private $defaults = array(
		// Governance / Lifecycle
		'disable_comments_globally' => false,
		'disable_on_pages'          => true,
		'disable_on_attachments'    => true,
		'disabled_post_types'       => array(),
		'auto_close_age_enabled'    => false,
		'auto_close_days'           => 60,
		'min_length_enabled'        => true,
		'min_length_chars'          => 15,
		'max_length_enabled'        => false,
		'max_length_chars'          => 2000,

		// Shield & Anti-Spam
		'honeypot_enabled'          => true,
		'speed_trap_enabled'        => true,
		'speed_trap_seconds'        => 3,
		'strip_author_url'          => true,
		'link_limit_enabled'        => true,
		'max_links_allowed'         => 1,
		'link_action'               => 'moderate', // 'moderate' or 'reject'

		// Privacy & Performance
		'anonymize_ip'              => true,
		'clean_replytocom'          => true,
		'disable_gravatars'         => false,
	);

	/**
	 * Get singleton instance.
	 *
	 * @return Comment_Control
	 */
	public static function get_instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	/**
	 * Constructor.
	 */
	private function __construct() {
		$this->load_settings();
		$this->load_dependencies();
		$this->init_hooks();
	}

	/**
	 * Load settings from wp_options and merge with defaults.
	 */
	private function load_settings() {
		$saved          = get_option( 'comment_control_settings', array() );
		$this->settings = wp_parse_args( is_array( $saved ) ? $saved : array(), $this->defaults );
	}

	/**
	 * Retrieve a single setting or all settings.
	 *
	 * @param string|null $key Setting key.
	 * @param mixed       $default Fallback value.
	 * @return mixed
	 */
	public function get_setting( $key = null, $default = null ) {
		if ( null === $key ) {
			return $this->settings;
		}

		if ( isset( $this->settings[ $key ] ) ) {
			return $this->settings[ $key ];
		}

		return isset( $this->defaults[ $key ] ) ? $this->defaults[ $key ] : $default;
	}

	/**
	 * Return plugin defaults.
	 *
	 * @return array
	 */
	public function get_defaults() {
		return $this->defaults;
	}

	/**
	 * Require plugin submodules.
	 */
	private function load_dependencies() {
		require_once COMMENT_CONTROL_PATH . 'includes/class-comment-control-rules.php';
		require_once COMMENT_CONTROL_PATH . 'includes/class-comment-control-shield.php';
		require_once COMMENT_CONTROL_PATH . 'includes/class-comment-control-privacy.php';

		if ( is_admin() ) {
			require_once COMMENT_CONTROL_PATH . 'includes/class-comment-control-admin.php';
		}
	}

	/**
	 * Initialize plugin hooks and subsystems.
	 */
	private function init_hooks() {
		// Instantiate modules
		Comment_Control_Rules::init( $this );
		Comment_Control_Shield::init( $this );
		Comment_Control_Privacy::init( $this );

		if ( is_admin() ) {
			Comment_Control_Admin::init( $this );
		}
	}
}
