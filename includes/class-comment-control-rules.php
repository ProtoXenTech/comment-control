<?php
/**
 * Comment Lifecycle & Governance Rules Module.
 *
 * @package Comment_Control
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class Comment_Control_Rules
 */
class Comment_Control_Rules {

	/**
	 * Core plugin orchestrator.
	 *
	 * @var Comment_Control
	 */
	private static $core;

	/**
	 * Initialize module.
	 *
	 * @param Comment_Control $core Core instance.
	 */
	public static function init( $core ) {
		self::$core = $core;

		// Filter whether comments are open for a given post.
		add_filter( 'comments_open', array( __CLASS__, 'filter_comments_open' ), 20, 2 );

		// Filter whether comments template should be overridden/hidden.
		add_filter( 'comments_template', array( __CLASS__, 'filter_comments_template' ), 20 );

		// Enforce character limits before comment is processed.
		add_filter( 'preprocess_comment', array( __CLASS__, 'validate_comment_length' ), 10, 1 );

		// Close comments on unmaintained media attachments.
		add_action( 'wp_loaded', array( __CLASS__, 'disable_media_comments_support' ) );
	}

	/**
	 * Filter whether comments are open for a post based on rules.
	 *
	 * @param bool $open Whether comments are currently open.
	 * @param int  $post_id Post ID.
	 * @return bool
	 */
	public static function filter_comments_open( $open, $post_id ) {
		if ( ! $open ) {
			return false;
		}

		// 1. Global Killswitch
		if ( (bool) self::$core->get_setting( 'disable_comments_globally', false ) ) {
			return false;
		}

		$post = get_post( $post_id );
		if ( ! $post ) {
			return $open;
		}

		// 2. Disable on Pages
		if ( 'page' === $post->post_type && (bool) self::$core->get_setting( 'disable_on_pages', true ) ) {
			return false;
		}

		// 3. Disable on Attachments / Media
		if ( 'attachment' === $post->post_type && (bool) self::$core->get_setting( 'disable_on_attachments', true ) ) {
			return false;
		}

		// 4. Custom Post Types filter
		$disabled_cpts = (array) self::$core->get_setting( 'disabled_post_types', array() );
		if ( in_array( $post->post_type, $disabled_cpts, true ) ) {
			return false;
		}

		// 5. Auto-close by publication age
		if ( (bool) self::$core->get_setting( 'auto_close_age_enabled', false ) ) {
			$days_limit = absint( self::$core->get_setting( 'auto_close_days', 60 ) );
			if ( $days_limit > 0 ) {
				$post_timestamp = get_post_time( 'U', false, $post );
				$current_time   = current_time( 'timestamp' );
				$post_age_days  = ( $current_time - $post_timestamp ) / DAY_IN_SECONDS;

				if ( $post_age_days > $days_limit ) {
					return false;
				}
			}
		}

		return $open;
	}

	/**
	 * Remove comment form template completely if global disable is enabled.
	 *
	 * @param string $template Current template file.
	 * @return string
	 */
	public static function filter_comments_template( $template ) {
		if ( (bool) self::$core->get_setting( 'disable_comments_globally', false ) ) {
			return COMMENT_CONTROL_PATH . 'index.php'; // Empty file to prevent template rendering.
		}
		return $template;
	}

	/**
	 * Remove 'comments' post-type feature from media attachment posts at runtime.
	 */
	public static function disable_media_comments_support() {
		if ( (bool) self::$core->get_setting( 'disable_on_attachments', true ) ) {
			remove_post_type_support( 'attachment', 'comments' );
		}
	}

	/**
	 * Validate comment length against min/max character limits.
	 *
	 * @param array $commentdata Raw comment submission data.
	 * @return array
	 */
	public static function validate_comment_length( $commentdata ) {
		// Ignore pingbacks and trackbacks.
		if ( isset( $commentdata['comment_type'] ) && in_array( $commentdata['comment_type'], array( 'pingback', 'trackback' ), true ) ) {
			return $commentdata;
		}

		// Bypass for administrators and editors.
		if ( current_user_can( 'moderate_comments' ) ) {
			return $commentdata;
		}

		$content = isset( $commentdata['comment_content'] ) ? trim( (string) $commentdata['comment_content'] ) : '';
		$length  = mb_strlen( $content );

		// Check Minimum Length
		if ( (bool) self::$core->get_setting( 'min_length_enabled', true ) ) {
			$min_chars = absint( self::$core->get_setting( 'min_length_chars', 15 ) );
			if ( $min_chars > 0 && $length < $min_chars ) {
				wp_die(
					sprintf(
						/* translators: %d: Minimum required character count */
						esc_html__( 'Error: Your comment is too short. Please provide a more detailed comment with at least %d characters.', 'comment-control' ),
						esc_html( (string) $min_chars )
					),
					esc_html__( 'Comment Too Short', 'comment-control' ),
					array(
						'response'  => 400,
						'back_link' => true,
					)
				);
			}
		}

		// Check Maximum Length
		if ( (bool) self::$core->get_setting( 'max_length_enabled', false ) ) {
			$max_chars = absint( self::$core->get_setting( 'max_length_chars', 2000 ) );
			if ( $max_chars > 0 && $length > $max_chars ) {
				wp_die(
					sprintf(
						/* translators: %d: Maximum allowed character count */
						esc_html__( 'Error: Your comment is too long. Please shorten your comment to under %d characters.', 'comment-control' ),
						esc_html( (string) $max_chars )
					),
					esc_html__( 'Comment Too Long', 'comment-control' ),
					array(
						'response'  => 400,
						'back_link' => true,
					)
				);
			}
		}

		return $commentdata;
	}
}
