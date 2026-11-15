<?php
/**
 * Privacy, Performance & SEO Module.
 *
 * @package Comment_Control
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class Comment_Control_Privacy
 */
class Comment_Control_Privacy {

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

		// 1. IP Address Anonymizer (100% GDPR / CCPA compliance)
		add_filter( 'pre_comment_user_ip', array( __CLASS__, 'anonymize_user_ip' ) );

		// 2. SEO Optimizer: Clean ?replytocom parameters & 301 canonical redirect
		add_filter( 'comment_reply_link', array( __CLASS__, 'clean_reply_link_anchor' ), 20, 3 );
		add_action( 'template_redirect', array( __CLASS__, 'redirect_replytocom_queries' ), 1 );

		// 3. Performance: Disable Gravatars (eliminates external DNS thrashing)
		add_filter( 'pre_option_show_avatars', array( __CLASS__, 'filter_show_avatars' ) );
	}

	/**
	 * Anonymize commenter IP address so no PII is saved into MySQL.
	 *
	 * @param string $comment_author_ip IP address.
	 * @return string
	 */
	public static function anonymize_user_ip( $comment_author_ip ) {
		if ( (bool) self::$core->get_setting( 'anonymize_ip', true ) ) {
			return '127.0.0.1';
		}
		return $comment_author_ip;
	}

	/**
	 * Replace ?replytocom=ID with a clean in-page #respond or #comment-ID link.
	 *
	 * @param string $link The HTML markup for the comment reply link.
	 * @param array  $args An array of arguments overriding the defaults.
	 * @param object $comment The object of the comment being replied to.
	 * @return string
	 */
	public static function clean_reply_link_anchor( $link, $args, $comment ) {
		if ( ! (bool) self::$core->get_setting( 'clean_replytocom', true ) ) {
			return $link;
		}

		// Rewrite reply link query to jump to respond anchor cleanly
		return preg_replace( '/href=[\'"][^\'"]*[\?&]replytocom=(\d+)#([^\'"]*)[\'"]/i', 'href="#respond" data-replyto="$1"', $link );
	}

	/**
	 * Redirect any bot or crawler hitting a ?replytocom query URL back to the canonical post with 301.
	 */
	public static function redirect_replytocom_queries() {
		if ( ! (bool) self::$core->get_setting( 'clean_replytocom', true ) ) {
			return;
		}

		// phpcs:ignore WordPress.Security.NonceVerification.Recommended
		if ( isset( $_GET['replytocom'] ) && is_singular() ) {
			// phpcs:ignore WordPress.Security.NonceVerification.Recommended
			$reply_id = absint( $_GET['replytocom'] );
			$post_id  = get_the_ID();
			$url      = get_permalink( $post_id );

			if ( $url ) {
				if ( $reply_id > 0 ) {
					$url = $url . '#comment-' . $reply_id;
				}
				wp_safe_redirect( $url, 301 );
				exit;
			}
		}
	}

	/**
	 * Filter whether Gravatars are enabled.
	 *
	 * @param mixed $value Pre-option value.
	 * @return mixed
	 */
	public static function filter_show_avatars( $value ) {
		if ( (bool) self::$core->get_setting( 'disable_gravatars', false ) ) {
			return '0';
		}
		return $value;
	}
}
