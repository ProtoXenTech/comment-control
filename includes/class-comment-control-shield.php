<?php
/**
 * Invisible Anti-Spam Shield Module.
 *
 * @package Comment_Control
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class Comment_Control_Shield
 */
class Comment_Control_Shield {

	/**
	 * Core plugin orchestrator.
	 *
	 * @var Comment_Control
	 */
	private static $core;

	/**
	 * Honeypot field name.
	 */
	const HONEYPOT_FIELD = 'cctl_verify_hp_code';

	/**
	 * Timestamp field name.
	 */
	const TIMESTAMP_FIELD = 'cctl_verify_time_token';

	/**
	 * Initialize module.
	 *
	 * @param Comment_Control $core Core instance.
	 */
	public static function init( $core ) {
		self::$core = $core;

		// 1. Strip Author Website URL field from form
		add_filter( 'comment_form_default_fields', array( __CLASS__, 'filter_comment_form_fields' ), 20 );

		// 2. Inject invisible honeypot & timestamp verification fields
		add_action( 'comment_form_after_fields', array( __CLASS__, 'inject_shield_fields' ) );
		add_action( 'comment_form_logged_in_after', array( __CLASS__, 'inject_shield_fields' ) );

		// 3. Pre-process comment validation checks (Honeypot, Speed trap, Link limits)
		add_filter( 'preprocess_comment', array( __CLASS__, 'verify_comment_submission' ), 5, 1 );
	}

	/**
	 * Remove 'url' (Website) field from comment form if enabled.
	 *
	 * @param array $fields Form fields.
	 * @return array
	 */
	public static function filter_comment_form_fields( $fields ) {
		if ( (bool) self::$core->get_setting( 'strip_author_url', true ) ) {
			if ( isset( $fields['url'] ) ) {
				unset( $fields['url'] );
			}
		}
		return $fields;
	}

	/**
	 * Inject invisible honeypot trap and encrypted timestamp fields into comment form.
	 */
	public static function inject_shield_fields() {
		// Output honeypot trap with inline style to stay 100% zero external asset
		if ( (bool) self::$core->get_setting( 'honeypot_enabled', true ) ) {
			?>
			<p class="cctl-hp-wrap" style="position: absolute !important; left: -9999px !important; top: -9999px !important; width: 1px !important; height: 1px !important; opacity: 0 !important; overflow: hidden !important; pointer-events: none !important;" aria-hidden="true">
				<label for="<?php echo esc_attr( self::HONEYPOT_FIELD ); ?>"><?php esc_html_e( 'Do not fill this field if you are a human:', 'comment-control' ); ?></label>
				<input type="text" id="<?php echo esc_attr( self::HONEYPOT_FIELD ); ?>" name="<?php echo esc_attr( self::HONEYPOT_FIELD ); ?>" value="" tabindex="-1" autocomplete="off" />
			</p>
			<?php
		}

		// Output encrypted timestamp token
		if ( (bool) self::$core->get_setting( 'speed_trap_enabled', true ) ) {
			$timestamp = time();
			$hash      = wp_hash( $timestamp . 'cctl_shield_salt' );
			$token     = $timestamp . '|' . $hash;
			?>
			<input type="hidden" name="<?php echo esc_attr( self::TIMESTAMP_FIELD ); ?>" value="<?php echo esc_attr( $token ); ?>" />
			<?php
		}
	}

	/**
	 * Verify comment submission through Honeypot, Speed trap, and Link threshold checks.
	 *
	 * @param array $commentdata Comment data.
	 * @return array
	 */
	public static function verify_comment_submission( $commentdata ) {
		// Bypass for trackbacks/pingbacks
		if ( isset( $commentdata['comment_type'] ) && in_array( $commentdata['comment_type'], array( 'pingback', 'trackback' ), true ) ) {
			return $commentdata;
		}

		// Bypass for verified administrators and editors
		if ( current_user_can( 'moderate_comments' ) ) {
			return $commentdata;
		}

		// 1. Strip Author URL if requested
		if ( (bool) self::$core->get_setting( 'strip_author_url', true ) ) {
			$commentdata['comment_author_url'] = '';
		}

		// 2. Honeypot Verification
		if ( (bool) self::$core->get_setting( 'honeypot_enabled', true ) ) {
			// phpcs:ignore WordPress.Security.NonceVerification.Missing
			$honeypot_val = isset( $_POST[ self::HONEYPOT_FIELD ] ) ? sanitize_text_field( wp_unslash( $_POST[ self::HONEYPOT_FIELD ] ) ) : '';
			if ( ! empty( $honeypot_val ) ) {
				// Honeypot field filled: Silent drop / 403 Fast bail to save server resources
				wp_die(
					esc_html__( 'Spam detected. Comment rejected.', 'comment-control' ),
					esc_html__( 'Spam Blocked', 'comment-control' ),
					array( 'response' => 403 )
				);
			}
		}

		// 3. Speed Trap Verification
		if ( (bool) self::$core->get_setting( 'speed_trap_enabled', true ) ) {
			$min_seconds = absint( self::$core->get_setting( 'speed_trap_seconds', 3 ) );
			// phpcs:ignore WordPress.Security.NonceVerification.Missing
			$token_raw   = isset( $_POST[ self::TIMESTAMP_FIELD ] ) ? sanitize_text_field( wp_unslash( $_POST[ self::TIMESTAMP_FIELD ] ) ) : '';

			if ( ! empty( $token_raw ) && strpos( $token_raw, '|' ) !== false ) {
				list( $timestamp, $hash ) = explode( '|', $token_raw, 2 );
				$timestamp = (int) $timestamp;

				// Verify hash authenticity
				$expected_hash = wp_hash( $timestamp . 'cctl_shield_salt' );
				if ( hash_equals( $expected_hash, $hash ) ) {
					$elapsed = time() - $timestamp;
					if ( $elapsed < $min_seconds ) {
						wp_die(
							sprintf(
								/* translators: %d: Minimum required seconds to submit */
								esc_html__( 'Error: You submitted the comment too quickly (under %d seconds). Please slow down and try again.', 'comment-control' ),
								esc_html( (string) $min_seconds )
							),
							esc_html__( 'Submission Too Fast', 'comment-control' ),
							array(
								'response'  => 429,
								'back_link' => true,
							)
						);
					}
				}
			}
		}

		// 4. Hyperlink Threshold Filter
		if ( (bool) self::$core->get_setting( 'link_limit_enabled', true ) ) {
			$max_links = absint( self::$core->get_setting( 'max_links_allowed', 1 ) );
			$action    = self::$core->get_setting( 'link_action', 'moderate' );
			$content   = isset( $commentdata['comment_content'] ) ? (string) $commentdata['comment_content'] : '';

			$link_count = self::count_hyperlinks( $content );

			if ( $link_count > $max_links ) {
				if ( 'reject' === $action ) {
					wp_die(
						sprintf(
							/* translators: %d: Maximum allowed links */
							esc_html__( 'Error: Comments containing more than %d link(s) are not accepted.', 'comment-control' ),
							esc_html( (string) $max_links )
						),
						esc_html__( 'Link Limit Exceeded', 'comment-control' ),
						array(
							'response'  => 400,
							'back_link' => true,
						)
					);
				} else {
					// Force into moderation queue
					add_filter(
						'pre_comment_approved',
						function() {
							return 0; // 0 = unapproved/moderation queue
						},
						99
					);
				}
			}
		}

		return $commentdata;
	}

	/**
	 * Count hyperlinks (both HTML anchors and raw http/https URLs) inside text.
	 *
	 * @param string $content Comment text.
	 * @return int
	 */
	public static function count_hyperlinks( $content ) {
		$count = 0;

		// Count HTML <a> tags
		if ( preg_match_all( '/<a[^>]+href=([\'"])(?<href>.+?)\1[^>]*>/i', $content, $matches ) ) {
			$count += count( $matches[0] );
		}

		// Strip tags to prevent double counting
		$stripped = wp_strip_all_tags( $content );

		// Count raw URLs (http:// or https://)
		if ( preg_match_all( '#\bhttps?://[^\s()<>]+(?:\([\w\d]+\)|([^[:punct:]\s]|/))#i', $stripped, $matches_raw ) ) {
			$count += count( $matches_raw[0] );
		}

		return $count;
	}
}
