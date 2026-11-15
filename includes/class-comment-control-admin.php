<?php
/**
 * Admin Governance Settings Module.
 *
 * @package Comment_Control
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class Comment_Control_Admin
 */
class Comment_Control_Admin {

	/**
	 * Core plugin orchestrator.
	 *
	 * @var Comment_Control
	 */
	private static $core;

	/**
	 * Settings option name.
	 */
	const OPTION_NAME = 'comment_control_settings';

	/**
	 * Initialize module.
	 *
	 * @param Comment_Control $core Core instance.
	 */
	public static function init( $core ) {
		self::$core = $core;

		add_action( 'admin_menu', array( __CLASS__, 'register_admin_menu' ) );
		add_action( 'admin_init', array( __CLASS__, 'register_settings' ) );
		add_action( 'admin_enqueue_scripts', array( __CLASS__, 'enqueue_admin_assets' ) );
		add_filter( 'plugin_action_links_' . COMMENT_CONTROL_BASENAME, array( __CLASS__, 'add_settings_link' ) );
	}

	/**
	 * Register settings menu page under 'Settings' in WP Admin.
	 */
	public static function register_admin_menu() {
		add_options_page(
			esc_html__( 'Comment Control Settings', 'comment-control' ),
			esc_html__( 'Comment Control', 'comment-control' ),
			'manage_options',
			'comment-control',
			array( __CLASS__, 'render_settings_page' )
		);
	}

	/**
	 * Register settings in WordPress settings API with validation.
	 */
	public static function register_settings() {
		register_setting(
			'comment_control_group',
			self::OPTION_NAME,
			array(
				'type'              => 'array',
				'sanitize_callback' => array( __CLASS__, 'sanitize_settings' ),
				'default'           => self::$core->get_defaults(),
			)
		);
	}

	/**
	 * Sanitize and validate all incoming settings securely.
	 *
	 * @param array $input Raw input.
	 * @return array
	 */
	public static function sanitize_settings( $input ) {
		$sanitized = array();
		$defaults  = self::$core->get_defaults();

		// Governance
		$sanitized['disable_comments_globally'] = ! empty( $input['disable_comments_globally'] );
		$sanitized['disable_on_pages']          = ! empty( $input['disable_on_pages'] );
		$sanitized['disable_on_attachments']    = ! empty( $input['disable_on_attachments'] );

		// Custom Post Types
		$sanitized['disabled_post_types'] = array();
		if ( ! empty( $input['disabled_post_types'] ) && is_array( $input['disabled_post_types'] ) ) {
			foreach ( $input['disabled_post_types'] as $cpt ) {
				$clean_cpt = sanitize_key( $cpt );
				if ( post_type_exists( $clean_cpt ) ) {
					$sanitized['disabled_post_types'][] = $clean_cpt;
				}
			}
		}

		$sanitized['auto_close_age_enabled'] = ! empty( $input['auto_close_age_enabled'] );
		$sanitized['auto_close_days']        = isset( $input['auto_close_days'] ) ? absint( $input['auto_close_days'] ) : $defaults['auto_close_days'];

		$sanitized['min_length_enabled'] = ! empty( $input['min_length_enabled'] );
		$sanitized['min_length_chars']   = isset( $input['min_length_chars'] ) ? absint( $input['min_length_chars'] ) : $defaults['min_length_chars'];

		$sanitized['max_length_enabled'] = ! empty( $input['max_length_enabled'] );
		$sanitized['max_length_chars']   = isset( $input['max_length_chars'] ) ? absint( $input['max_length_chars'] ) : $defaults['max_length_chars'];

		// Shield & Anti-Spam
		$sanitized['honeypot_enabled']   = ! empty( $input['honeypot_enabled'] );
		$sanitized['speed_trap_enabled'] = ! empty( $input['speed_trap_enabled'] );
		$sanitized['speed_trap_seconds'] = isset( $input['speed_trap_seconds'] ) ? absint( $input['speed_trap_seconds'] ) : $defaults['speed_trap_seconds'];
		$sanitized['strip_author_url']   = ! empty( $input['strip_author_url'] );

		$sanitized['link_limit_enabled'] = ! empty( $input['link_limit_enabled'] );
		$sanitized['max_links_allowed']  = isset( $input['max_links_allowed'] ) ? absint( $input['max_links_allowed'] ) : $defaults['max_links_allowed'];
		$sanitized['link_action']        = isset( $input['link_action'] ) && in_array( $input['link_action'], array( 'moderate', 'reject' ), true ) ? $input['link_action'] : 'moderate';

		// Privacy & Performance
		$sanitized['anonymize_ip']      = ! empty( $input['anonymize_ip'] );
		$sanitized['clean_replytocom']  = ! empty( $input['clean_replytocom'] );
		$sanitized['disable_gravatars'] = ! empty( $input['disable_gravatars'] );

		return $sanitized;
	}

	/**
	 * Add direct 'Settings' link to plugin entry on Plugins page.
	 *
	 * @param array $links Action links.
	 * @return array
	 */
	public static function add_settings_link( $links ) {
		$settings_url  = admin_url( 'options-general.php?page=comment-control' );
		$settings_link = '<a href="' . esc_url( $settings_url ) . '">' . esc_html__( 'Settings', 'comment-control' ) . '</a>';
		array_unshift( $links, $settings_link );
		return $links;
	}

	/**
	 * Enqueue admin CSS and JS strictly on plugin settings page.
	 *
	 * @param string $hook_suffix Admin page hook.
	 */
	public static function enqueue_admin_assets( $hook_suffix ) {
		if ( 'settings_page_comment-control' !== $hook_suffix ) {
			return;
		}

		wp_enqueue_style(
			'comment-control-admin',
			COMMENT_CONTROL_URL . 'admin/css/admin.css',
			array(),
			COMMENT_CONTROL_VERSION
		);

		wp_enqueue_script(
			'comment-control-admin',
			COMMENT_CONTROL_URL . 'admin/js/admin.js',
			array(),
			COMMENT_CONTROL_VERSION,
			true
		);
	}

	/**
	 * Render plugin settings page.
	 */
	public static function render_settings_page() {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		$settings = self::$core->get_setting();
		$logo_url = COMMENT_CONTROL_URL . 'admin/images/protoxen-logo.webp';
		$icon_url = COMMENT_CONTROL_URL . 'admin/images/icon-128x128.png';

		// Get all public post types excluding core non-relevant ones
		$all_post_types = get_post_types( array( 'public' => true ), 'objects' );
		unset( $all_post_types['attachment'], $all_post_types['page'] );
		?>
		<div class="wrap cctl-wrap">
			<div class="cctl-header">
				<div class="cctl-brand">
					<div class="cctl-logo-badge">
						<img src="<?php echo esc_url( $icon_url ); ?>" alt="Comment Control Icon" width="44" height="44" class="cctl-header-icon" />
					</div>
					<div>
						<h1><?php esc_html_e( 'Comment Control Studio', 'comment-control' ); ?> <span class="cctl-badge">v<?php echo esc_html( COMMENT_CONTROL_VERSION ); ?></span></h1>
						<p class="cctl-subtitle"><?php esc_html_e( 'Zero-bloat comment governance, invisible anti-spam, and privacy optimization.', 'comment-control' ); ?></p>
					</div>
				</div>
				<div class="cctl-author-tag">
					<span class="cctl-by-text"><?php esc_html_e( 'Crafted by', 'comment-control' ); ?></span>
					<a href="https://protoxen.com/" target="_blank" rel="noopener noreferrer" class="cctl-brand-link">
						<img src="<?php echo esc_url( $logo_url ); ?>" alt="ProtoXen" width="88" height="20" class="cctl-protoxen-logo" />
					</a>
				</div>
			</div>

			<form method="post" action="options.php" class="cctl-form">
				<?php
				settings_fields( 'comment_control_group' );
				?>

				<div class="cctl-tabs-nav">
					<button type="button" class="cctl-tab-btn active" data-tab="tab-governance">
						<span class="dashicons dashicons-admin-settings"></span>
						<?php esc_html_e( 'Lifecycle & Rules', 'comment-control' ); ?>
					</button>
					<button type="button" class="cctl-tab-btn" data-tab="tab-shield">
						<span class="dashicons dashicons-shield"></span>
						<?php esc_html_e( 'Anti-Spam Shield', 'comment-control' ); ?>
					</button>
					<button type="button" class="cctl-tab-btn" data-tab="tab-privacy">
						<span class="dashicons dashicons-lock"></span>
						<?php esc_html_e( 'Privacy & Speed', 'comment-control' ); ?>
					</button>
				</div>

				<div class="cctl-tabs-content">
					<!-- TAB 1: Governance & Rules -->
					<div class="cctl-tab-pane active" id="tab-governance">
						<div class="cctl-card">
							<h3><?php esc_html_e( 'Global & Post Type Rules', 'comment-control' ); ?></h3>
							
							<div class="cctl-toggle-row">
								<label class="cctl-switch">
									<input type="checkbox" name="<?php echo esc_attr( self::OPTION_NAME ); ?>[disable_comments_globally]" value="1" <?php checked( $settings['disable_comments_globally'] ); ?> />
									<span class="cctl-slider"></span>
								</label>
								<div class="cctl-toggle-text">
									<strong><?php esc_html_e( 'Global Killswitch (Disable Everywhere)', 'comment-control' ); ?></strong>
									<p><?php esc_html_e( 'Completely turns off comment templates and comment forms site-wide.', 'comment-control' ); ?></p>
								</div>
							</div>

							<div class="cctl-toggle-row">
								<label class="cctl-switch">
									<input type="checkbox" name="<?php echo esc_attr( self::OPTION_NAME ); ?>[disable_on_pages]" value="1" <?php checked( $settings['disable_on_pages'] ); ?> />
									<span class="cctl-slider"></span>
								</label>
								<div class="cctl-toggle-text">
									<strong><?php esc_html_e( 'Disable Comments on Pages', 'comment-control' ); ?></strong>
									<p><?php esc_html_e( 'Standard static pages rarely need discussion sections. Keeps pages clean.', 'comment-control' ); ?></p>
								</div>
							</div>

							<div class="cctl-toggle-row">
								<label class="cctl-switch">
									<input type="checkbox" name="<?php echo esc_attr( self::OPTION_NAME ); ?>[disable_on_attachments]" value="1" <?php checked( $settings['disable_on_attachments'] ); ?> />
									<span class="cctl-slider"></span>
								</label>
								<div class="cctl-toggle-text">
									<strong><?php esc_html_e( 'Disable Comments on Media / Attachments', 'comment-control' ); ?></strong>
									<p><?php esc_html_e( 'Prevents spammers from targeting forgotten image attachment pages.', 'comment-control' ); ?></p>
								</div>
							</div>

							<?php if ( ! empty( $all_post_types ) ) : ?>
								<div class="cctl-input-group">
									<label class="cctl-label"><strong><?php esc_html_e( 'Disable on Specific Custom Post Types:', 'comment-control' ); ?></strong></label>
									<div class="cctl-checkbox-grid">
										<?php foreach ( $all_post_types as $pt ) : ?>
											<?php if ( 'post' === $pt->name ) continue; ?>
											<label class="cctl-checkbox-item">
												<input type="checkbox" name="<?php echo esc_attr( self::OPTION_NAME ); ?>[disabled_post_types][]" value="<?php echo esc_attr( $pt->name ); ?>" <?php checked( in_array( $pt->name, (array) $settings['disabled_post_types'], true ) ); ?> />
												<span><?php echo esc_html( $pt->labels->singular_name ); ?> (<code><?php echo esc_html( $pt->name ); ?></code>)</span>
											</label>
										<?php endforeach; ?>
									</div>
								</div>
							<?php endif; ?>
						</div>

						<div class="cctl-card">
							<h3><?php esc_html_e( 'Comment Lifecycle & Character Budget', 'comment-control' ); ?></h3>

							<div class="cctl-toggle-row">
								<label class="cctl-switch">
									<input type="checkbox" name="<?php echo esc_attr( self::OPTION_NAME ); ?>[auto_close_age_enabled]" value="1" <?php checked( $settings['auto_close_age_enabled'] ); ?> />
									<span class="cctl-slider"></span>
								</label>
								<div class="cctl-toggle-text">
									<strong><?php esc_html_e( 'Auto-Close Comments on Old Posts', 'comment-control' ); ?></strong>
									<p><?php esc_html_e( 'Automatically close discussion after posts reach a specific age.', 'comment-control' ); ?></p>
								</div>
							</div>
							<div class="cctl-inline-setting">
								<label><?php esc_html_e( 'Close after days:', 'comment-control' ); ?></label>
								<input type="number" name="<?php echo esc_attr( self::OPTION_NAME ); ?>[auto_close_days]" value="<?php echo esc_attr( $settings['auto_close_days'] ); ?>" min="1" max="3650" class="small-text" />
							</div>

							<hr class="cctl-divider" />

							<div class="cctl-toggle-row">
								<label class="cctl-switch">
									<input type="checkbox" name="<?php echo esc_attr( self::OPTION_NAME ); ?>[min_length_enabled]" value="1" <?php checked( $settings['min_length_enabled'] ); ?> />
									<span class="cctl-slider"></span>
								</label>
								<div class="cctl-toggle-text">
									<strong><?php esc_html_e( 'Enforce Minimum Comment Length', 'comment-control' ); ?></strong>
									<p><?php esc_html_e( 'Eliminates 1-word low effort responses (e.g. "Thanks!", "Great post!").', 'comment-control' ); ?></p>
								</div>
							</div>
							<div class="cctl-inline-setting">
								<label><?php esc_html_e( 'Minimum characters required:', 'comment-control' ); ?></label>
								<input type="number" name="<?php echo esc_attr( self::OPTION_NAME ); ?>[min_length_chars]" value="<?php echo esc_attr( $settings['min_length_chars'] ); ?>" min="5" max="500" class="small-text" />
							</div>

							<hr class="cctl-divider" />

							<div class="cctl-toggle-row">
								<label class="cctl-switch">
									<input type="checkbox" name="<?php echo esc_attr( self::OPTION_NAME ); ?>[max_length_enabled]" value="1" <?php checked( $settings['max_length_enabled'] ); ?> />
									<span class="cctl-slider"></span>
								</label>
								<div class="cctl-toggle-text">
									<strong><?php esc_html_e( 'Enforce Maximum Comment Length', 'comment-control' ); ?></strong>
									<p><?php esc_html_e( 'Prevents malicious paste dumps and oversized bot payloads.', 'comment-control' ); ?></p>
								</div>
							</div>
							<div class="cctl-inline-setting">
								<label><?php esc_html_e( 'Maximum characters allowed:', 'comment-control' ); ?></label>
								<input type="number" name="<?php echo esc_attr( self::OPTION_NAME ); ?>[max_length_chars]" value="<?php echo esc_attr( $settings['max_length_chars'] ); ?>" min="100" max="10000" class="small-text" />
							</div>
						</div>
					</div>

					<!-- TAB 2: Anti-Spam Shield -->
					<div class="cctl-tab-pane" id="tab-shield">
						<div class="cctl-card">
							<h3><?php esc_html_e( 'Invisible Anti-Spam Protection (Zero-Asset, No CAPTCHA)', 'comment-control' ); ?></h3>

							<div class="cctl-toggle-row">
								<label class="cctl-switch">
									<input type="checkbox" name="<?php echo esc_attr( self::OPTION_NAME ); ?>[honeypot_enabled]" value="1" <?php checked( $settings['honeypot_enabled'] ); ?> />
									<span class="cctl-slider"></span>
								</label>
								<div class="cctl-toggle-text">
									<strong><?php esc_html_e( 'Invisible Honeypot Trap', 'comment-control' ); ?></strong>
									<p><?php esc_html_e( 'Injects an invisible CSS-masked field. Bots fill it out automatically; real humans cannot see it. Instantly terminates bot attacks with 0ms server overhead.', 'comment-control' ); ?></p>
								</div>
							</div>

							<div class="cctl-toggle-row">
								<label class="cctl-switch">
									<input type="checkbox" name="<?php echo esc_attr( self::OPTION_NAME ); ?>[speed_trap_enabled]" value="1" <?php checked( $settings['speed_trap_enabled'] ); ?> />
									<span class="cctl-slider"></span>
								</label>
								<div class="cctl-toggle-text">
									<strong><?php esc_html_e( 'Cryptographic Speed Trap', 'comment-control' ); ?></strong>
									<p><?php esc_html_e( 'Blocks instant automated form submissions. Real humans require time to read and type.', 'comment-control' ); ?></p>
								</div>
							</div>
							<div class="cctl-inline-setting">
								<label><?php esc_html_e( 'Minimum typing time before submit (seconds):', 'comment-control' ); ?></label>
								<input type="number" name="<?php echo esc_attr( self::OPTION_NAME ); ?>[speed_trap_seconds]" value="<?php echo esc_attr( $settings['speed_trap_seconds'] ); ?>" min="1" max="10" class="small-text" />
							</div>

							<hr class="cctl-divider" />

							<div class="cctl-toggle-row">
								<label class="cctl-switch">
									<input type="checkbox" name="<?php echo esc_attr( self::OPTION_NAME ); ?>[strip_author_url]" value="1" <?php checked( $settings['strip_author_url'] ); ?> />
									<span class="cctl-slider"></span>
								</label>
								<div class="cctl-toggle-text">
									<strong><?php esc_html_e( 'Remove "Website / Author URL" Field', 'comment-control' ); ?></strong>
									<p><?php esc_html_e( 'Completely removes the URL input field. Eliminates ~85% of comment spammers attempting to build SEO backlinks.', 'comment-control' ); ?></p>
								</div>
							</div>

							<hr class="cctl-divider" />

							<div class="cctl-toggle-row">
								<label class="cctl-switch">
									<input type="checkbox" name="<?php echo esc_attr( self::OPTION_NAME ); ?>[link_limit_enabled]" value="1" <?php checked( $settings['link_limit_enabled'] ); ?> />
									<span class="cctl-slider"></span>
								</label>
								<div class="cctl-toggle-text">
									<strong><?php esc_html_e( 'Hyperlink Count Gatekeeper', 'comment-control' ); ?></strong>
									<p><?php esc_html_e( 'Monitors the number of URLs embedded within the comment text body.', 'comment-control' ); ?></p>
								</div>
							</div>
							<div class="cctl-inline-setting">
								<label><?php esc_html_e( 'Max permitted links in comment:', 'comment-control' ); ?></label>
								<input type="number" name="<?php echo esc_attr( self::OPTION_NAME ); ?>[max_links_allowed]" value="<?php echo esc_attr( $settings['max_links_allowed'] ); ?>" min="0" max="10" class="small-text" />
							</div>
							<div class="cctl-inline-setting">
								<label><?php esc_html_e( 'Action when limit exceeded:', 'comment-control' ); ?></label>
								<select name="<?php echo esc_attr( self::OPTION_NAME ); ?>[link_action]">
									<option value="moderate" <?php selected( $settings['link_action'], 'moderate' ); ?>><?php esc_html_e( 'Hold for manual moderation queue', 'comment-control' ); ?></option>
									<option value="reject" <?php selected( $settings['link_action'], 'reject' ); ?>><?php esc_html_e( 'Instantly reject comment submission', 'comment-control' ); ?></option>
								</select>
							</div>
						</div>
					</div>

					<!-- TAB 3: Privacy & Speed -->
					<div class="cctl-tab-pane" id="tab-privacy">
						<div class="cctl-card">
							<h3><?php esc_html_e( 'Privacy (GDPR / CCPA) & Speed Enhancements', 'comment-control' ); ?></h3>

							<div class="cctl-toggle-row">
								<label class="cctl-switch">
									<input type="checkbox" name="<?php echo esc_attr( self::OPTION_NAME ); ?>[anonymize_ip]" value="1" <?php checked( $settings['anonymize_ip'] ); ?> />
									<span class="cctl-slider"></span>
								</label>
								<div class="cctl-toggle-text">
									<strong><?php esc_html_e( 'Anonymize Commenter IP Address (100% GDPR Compliance)', 'comment-control' ); ?></strong>
									<p><?php esc_html_e( 'Replaces visitor IP addresses with 127.0.0.1 in the database so no personally identifiable information (PII) is stored.', 'comment-control' ); ?></p>
								</div>
							</div>

							<div class="cctl-toggle-row">
								<label class="cctl-switch">
									<input type="checkbox" name="<?php echo esc_attr( self::OPTION_NAME ); ?>[clean_replytocom]" value="1" <?php checked( $settings['clean_replytocom'] ); ?> />
									<span class="cctl-slider"></span>
								</label>
								<div class="cctl-toggle-text">
									<strong><?php esc_html_e( 'Clean ?replytocom Query Loop (Google Crawl Budget Saver)', 'comment-control' ); ?></strong>
									<p><?php esc_html_e( 'Replaces reply query strings with clean in-page anchors and 301 redirects crawlers to the canonical post URL to save crawl budget.', 'comment-control' ); ?></p>
								</div>
							</div>

							<div class="cctl-toggle-row">
								<label class="cctl-switch">
									<input type="checkbox" name="<?php echo esc_attr( self::OPTION_NAME ); ?>[disable_gravatars]" value="1" <?php checked( $settings['disable_gravatars'] ); ?> />
									<span class="cctl-slider"></span>
								</label>
								<div class="cctl-toggle-text">
									<strong><?php esc_html_e( 'Disable External Gravatars', 'comment-control' ); ?></strong>
									<p><?php esc_html_e( 'Prevents 20–100+ external DNS lookups to Gravatar.com on discussion-heavy posts. Massive boost to PageSpeed and Core Web Vitals.', 'comment-control' ); ?></p>
								</div>
							</div>
						</div>
					</div>
				</div>

				<div class="cctl-footer-save">
					<?php submit_button( esc_html__( 'Save All Changes', 'comment-control' ), 'primary large', 'submit', false ); ?>
				</div>
			</form>
		</div>
		<?php
	}
}
