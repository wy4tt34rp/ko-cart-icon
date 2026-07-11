<?php
/**
 * Plugin Name: KO – Cart Icon
 * Description: Adds a Divi-friendly DEX cart icon shortcode with an overlayed cart product count.
 * Version: 1.0.6
 * Author: KO
 * Author URI: mailto:6822858@kevinoneill.us
 * License: GPLv2 or later
 * Requires at least: 6.0
 * Requires PHP: 7.4
 * Text Domain: ko-cart-icon
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'KO_Cart_Icon' ) ) {
	final class KO_Cart_Icon {
		const VERSION         = '1.0.6';
		const OPTION_KEY      = 'ko_cart_icon_settings';
		const VERSION_KEY     = 'ko_cart_icon_version';
		const SHORTCODE       = 'ko_cart_icon';
		const ADMIN_PAGE_SLUG = 'ko-cart-icon';
		const ADMIN_NONCE     = 'ko_cart_icon_admin';
		const AJAX_ACTION     = 'ko_cart_icon_count';

		public static function init() {
			register_activation_hook( __FILE__, array( __CLASS__, 'activate' ) );

			add_action( 'plugins_loaded', array( __CLASS__, 'maybe_upgrade' ) );
			add_action( 'admin_menu', array( __CLASS__, 'register_admin_menu' ) );
			add_action( 'admin_init', array( __CLASS__, 'handle_admin_post' ) );
			add_action( 'admin_enqueue_scripts', array( __CLASS__, 'enqueue_admin_assets' ) );
			add_action( 'wp_ajax_' . self::AJAX_ACTION, array( __CLASS__, 'ajax_cart_count' ) );
			add_action( 'wp_ajax_nopriv_' . self::AJAX_ACTION, array( __CLASS__, 'ajax_cart_count' ) );

			add_shortcode( self::SHORTCODE, array( __CLASS__, 'render_shortcode' ) );
		}

		public static function activate() {
			if ( false === get_option( self::OPTION_KEY, false ) ) {
				add_option( self::OPTION_KEY, self::default_settings(), '', false );
			}

			update_option( self::VERSION_KEY, self::VERSION, false );
		}

		public static function maybe_upgrade() {
			$installed_version = (string) get_option( self::VERSION_KEY, '' );

			if ( self::VERSION === $installed_version ) {
				return;
			}

			$settings = get_option( self::OPTION_KEY, false );

			if ( false === $settings || ! is_array( $settings ) ) {
				update_option( self::OPTION_KEY, self::default_settings(), false );
			} else {
				if ( version_compare( $installed_version, '1.0.3', '<' ) ) {
					$library = isset( $settings['icon_library'] ) ? sanitize_key( (string) $settings['icon_library'] ) : '';

					if ( '' === $library || 'fontawesome' === $library ) {
						$settings['icon_library'] = 'dexsvg';
					}
				}

				if ( version_compare( $installed_version, '1.0.5', '<' ) ) {
					$settings['badge_background_color'] = $settings['badge_background_color'] ?? ( $settings['active_color'] ?? '#0f6b37' );
					$settings['badge_background_transparent'] = $settings['badge_background_transparent'] ?? 0;
					$settings['badge_border_color'] = $settings['badge_border_color'] ?? '#ffffff';
					$settings['badge_border_width'] = $settings['badge_border_width'] ?? 2;
				}

				if ( version_compare( $installed_version, '1.0.6', '<' ) ) {
					$settings['badge_font_size'] = $settings['badge_font_size'] ?? 0;
				}

				update_option( self::OPTION_KEY, self::sanitize_settings( $settings ), false );
			}

			update_option( self::VERSION_KEY, self::VERSION, false );
		}

		private static function default_settings() {
			return array(
				'icon_library'    => 'dexsvg',
				'builtin_icon'    => 'dex-cart',
				'fontawesome_icon' => 'fa-solid fa-cart-shopping',
				'etmodules_icon'  => 'e015',
				'media_icon_id'   => 0,
				'media_icon_url'  => '',
				'empty_color'     => '#9ca3af',
				'active_color'    => '#0f6b37',
				'text_color'      => '#ffffff',
				'badge_background_color' => '#0f6b37',
				'badge_background_transparent' => 0,
				'badge_border_color' => '#ffffff',
				'badge_border_width' => 2,
				'badge_font_size' => 0,
				'icon_size'       => 28,
			);
		}

		private static function get_settings() {
			$saved = get_option( self::OPTION_KEY, array() );

			return self::sanitize_settings( is_array( $saved ) ? $saved : array() );
		}

		private static function sanitize_settings( $input ) {
			$input    = is_array( $input ) ? $input : array();
			$defaults = self::default_settings();
			$output   = array();

			$output['icon_library']    = self::sanitize_icon_library( $input['icon_library'] ?? $defaults['icon_library'] );
			$output['builtin_icon']    = self::sanitize_builtin_icon( $input['builtin_icon'] ?? $defaults['builtin_icon'] );
			$output['fontawesome_icon'] = self::sanitize_fontawesome_icon( $input['fontawesome_icon'] ?? $defaults['fontawesome_icon'] );
			$output['etmodules_icon']  = self::sanitize_etmodules_icon( $input['etmodules_icon'] ?? $defaults['etmodules_icon'] );
			$output['media_icon_id']   = self::clamp_int( $input['media_icon_id'] ?? $defaults['media_icon_id'], 0, PHP_INT_MAX, $defaults['media_icon_id'] );
			$output['media_icon_url']  = self::sanitize_media_icon_url( $input['media_icon_url'] ?? $defaults['media_icon_url'] );
			$output['empty_color']     = self::sanitize_color( $input['empty_color'] ?? $defaults['empty_color'], $defaults['empty_color'] );
			$output['active_color']    = self::sanitize_color( $input['active_color'] ?? $defaults['active_color'], $defaults['active_color'] );
			$output['text_color']      = self::sanitize_color( $input['text_color'] ?? $defaults['text_color'], $defaults['text_color'] );
			$output['badge_background_color'] = self::sanitize_color( $input['badge_background_color'] ?? $defaults['badge_background_color'], $defaults['badge_background_color'] );
			$output['badge_background_transparent'] = self::sanitize_bool( $input['badge_background_transparent'] ?? $defaults['badge_background_transparent'] );
			$output['badge_border_color'] = self::sanitize_color( $input['badge_border_color'] ?? $defaults['badge_border_color'], $defaults['badge_border_color'] );
			$output['badge_border_width'] = self::clamp_int( $input['badge_border_width'] ?? $defaults['badge_border_width'], 0, 8, $defaults['badge_border_width'] );
			$output['badge_font_size'] = self::clamp_int( $input['badge_font_size'] ?? $defaults['badge_font_size'], 0, 32, $defaults['badge_font_size'] );
			$output['icon_size']       = self::clamp_int( $input['icon_size'] ?? $defaults['icon_size'], 12, 96, $defaults['icon_size'] );

			return $output;
		}

		private static function sanitize_icon_library( $value ) {
			$value = sanitize_key( (string) $value );

			return in_array( $value, array( 'dexsvg', 'media', 'etmodules', 'fontawesome' ), true ) ? $value : 'dexsvg';
		}

		private static function sanitize_builtin_icon( $value ) {
			$value = sanitize_key( (string) $value );

			return in_array( $value, array( 'dex-cart' ), true ) ? $value : 'dex-cart';
		}

		private static function sanitize_fontawesome_icon( $value ) {
			$classes = self::sanitize_class_list( (string) $value );

			return '' !== $classes ? $classes : 'fa-solid fa-cart-shopping';
		}

		private static function sanitize_etmodules_icon( $value ) {
			$value = strtolower( preg_replace( '/[^a-f0-9]/', '', (string) $value ) );

			return '' !== $value ? substr( $value, 0, 6 ) : 'e015';
		}

		private static function sanitize_media_icon_url( $value ) {
			$url = esc_url_raw( (string) $value );

			return $url ? $url : '';
		}

		private static function sanitize_color( $value, $fallback ) {
			$color = sanitize_hex_color( (string) $value );

			return $color ? $color : $fallback;
		}

		private static function sanitize_bool( $value ) {
			$value = strtolower( trim( (string) $value ) );

			return in_array( $value, array( '1', 'true', 'yes', 'on' ), true ) ? 1 : 0;
		}

		private static function clamp_int( $value, $min, $max, $fallback ) {
			$value = is_numeric( $value ) ? absint( $value ) : $fallback;

			return max( $min, min( $max, $value ) );
		}

		public static function register_admin_menu() {
			add_options_page(
				__( 'KO Cart Icon', 'ko-cart-icon' ),
				__( 'KO Cart Icon', 'ko-cart-icon' ),
				'manage_options',
				self::ADMIN_PAGE_SLUG,
				array( __CLASS__, 'render_admin_page' )
			);
		}

		public static function enqueue_admin_assets( $hook ) {
			if ( false === strpos( (string) $hook, self::ADMIN_PAGE_SLUG ) ) {
				return;
			}

			wp_enqueue_media();

			wp_enqueue_style(
				'ko-cart-icon-frontend',
				plugins_url( 'assets/css/frontend.css', __FILE__ ),
				array(),
				self::VERSION
			);

			wp_enqueue_style(
				'ko-cart-icon-admin',
				plugins_url( 'assets/css/admin.css', __FILE__ ),
				array( 'ko-cart-icon-frontend' ),
				self::VERSION
			);

			wp_enqueue_script(
				'ko-cart-icon-admin',
				plugins_url( 'assets/js/admin.js', __FILE__ ),
				array(),
				self::VERSION,
				true
			);

			wp_localize_script(
				'ko-cart-icon-admin',
				'koCartIconAdmin',
				array(
					'mediaTitle'  => __( 'Select Cart Icon Image', 'ko-cart-icon' ),
					'mediaButton' => __( 'Use This Image', 'ko-cart-icon' ),
				)
			);
		}

		private static function enqueue_frontend_assets() {
			wp_enqueue_style(
				'ko-cart-icon-frontend',
				plugins_url( 'assets/css/frontend.css', __FILE__ ),
				array(),
				self::VERSION
			);

			wp_enqueue_script(
				'ko-cart-icon-frontend',
				plugins_url( 'assets/js/frontend.js', __FILE__ ),
				array(),
				self::VERSION,
				true
			);

			wp_localize_script(
				'ko-cart-icon-frontend',
				'koCartIcon',
				array(
					'ajaxUrl' => admin_url( 'admin-ajax.php' ),
					'action'  => self::AJAX_ACTION,
					'i18n'    => array(
						'cartEmpty'     => __( '%s, cart is empty', 'ko-cart-icon' ),
						'cartOneItem'   => __( '%1$s, %2$d product in cart', 'ko-cart-icon' ),
						'cartManyItems' => __( '%1$s, %2$d products in cart', 'ko-cart-icon' ),
					),
				)
			);
		}

		public static function handle_admin_post() {
			if ( ! is_admin() || ! current_user_can( 'manage_options' ) ) {
				return;
			}

			if ( empty( $_POST['ko_cart_icon_action'] ) ) {
				return;
			}

			check_admin_referer( self::ADMIN_NONCE );

			$action = sanitize_key( wp_unslash( $_POST['ko_cart_icon_action'] ) );

			if ( 'reset' === $action ) {
				update_option( self::OPTION_KEY, self::default_settings(), false );
				self::redirect_with_notice( 'reset' );
			}

			$raw_input = isset( $_POST[ self::OPTION_KEY ] ) ? wp_unslash( $_POST[ self::OPTION_KEY ] ) : array();
			update_option( self::OPTION_KEY, self::sanitize_settings( $raw_input ), false );
			self::redirect_with_notice( 'saved' );
		}

		private static function settings_url( $notice = '' ) {
			$url = admin_url( 'options-general.php?page=' . self::ADMIN_PAGE_SLUG );

			if ( '' !== $notice ) {
				$url = add_query_arg( 'ko_cart_icon_notice', rawurlencode( $notice ), $url );
			}

			return $url;
		}

		private static function redirect_with_notice( $notice ) {
			wp_safe_redirect( self::settings_url( $notice ) );
			exit;
		}

		public static function render_admin_page() {
			if ( ! current_user_can( 'manage_options' ) ) {
				return;
			}

			$settings        = self::get_settings();
			$count           = self::get_cart_count();
			$dex_cart_active = self::dex_cart_is_available();
			$media_icon_url  = self::get_media_icon_url( $settings );
			$notice          = isset( $_GET['ko_cart_icon_notice'] ) ? sanitize_key( wp_unslash( $_GET['ko_cart_icon_notice'] ) ) : '';
			?>
			<div class="wrap ko-ci-admin">
				<div class="ko-ci-shell">
					<?php if ( 'saved' === $notice ) : ?>
						<div class="notice notice-success is-dismissible"><p><?php esc_html_e( 'Cart icon settings saved.', 'ko-cart-icon' ); ?></p></div>
					<?php elseif ( 'reset' === $notice ) : ?>
						<div class="notice notice-success is-dismissible"><p><?php esc_html_e( 'Cart icon settings reset.', 'ko-cart-icon' ); ?></p></div>
					<?php endif; ?>

					<section class="ko-ci-hero">
						<h1><?php esc_html_e( 'KO – Cart Icon', 'ko-cart-icon' ); ?></h1>
						<p><?php esc_html_e( 'A shortcode-ready cart icon for Divi layouts. It reads the legacy DEX part cart count, shows a badge only when products are in the cart, and keeps the empty state grayed out by default.', 'ko-cart-icon' ); ?></p>
						<div class="ko-ci-badges">
							<span class="ko-ci-badge-pill"><?php esc_html_e( 'DEX Part Cart Count', 'ko-cart-icon' ); ?></span>
							<span class="ko-ci-badge-pill"><?php esc_html_e( 'Divi Shortcode', 'ko-cart-icon' ); ?></span>
							<span class="ko-ci-badge-pill"><?php esc_html_e( 'DEX SVG Cart', 'ko-cart-icon' ); ?></span>
						</div>
						<?php if ( ! $dex_cart_active ) : ?>
							<div class="ko-ci-alert ko-ci-alert-danger"><strong><?php esc_html_e( 'DEX cart helpers not detected:', 'ko-cart-icon' ); ?></strong> <?php esc_html_e( 'The shortcode can still read the ko_part_cart cookie, but the preferred DEX cart classes/functions are not loaded on this request.', 'ko-cart-icon' ); ?></div>
						<?php endif; ?>
					</section>

					<div class="ko-ci-grid">
						<div>
							<section class="ko-ci-card">
								<h2><?php esc_html_e( 'Icon Settings', 'ko-cart-icon' ); ?></h2>
								<p><?php esc_html_e( 'Set the cart icon source, empty and active cart colors, badge text color, and icon size.', 'ko-cart-icon' ); ?></p>

								<form method="post" action="<?php echo esc_url( self::settings_url() ); ?>" data-ko-cart-icon-admin-form>
									<?php wp_nonce_field( self::ADMIN_NONCE ); ?>

									<div class="ko-ci-form-grid">
										<div class="ko-ci-field">
											<label class="ko-ci-label" for="ko-ci-icon-library"><?php esc_html_e( 'Icon Library', 'ko-cart-icon' ); ?></label>
											<select id="ko-ci-icon-library" name="<?php echo esc_attr( self::OPTION_KEY ); ?>[icon_library]" data-ko-preview-library>
												<option value="dexsvg" <?php selected( $settings['icon_library'], 'dexsvg' ); ?>><?php esc_html_e( 'DEX SVG Cart', 'ko-cart-icon' ); ?></option>
												<option value="media" <?php selected( $settings['icon_library'], 'media' ); ?>><?php esc_html_e( 'Media Library Image', 'ko-cart-icon' ); ?></option>
												<option value="etmodules" <?php selected( $settings['icon_library'], 'etmodules' ); ?>><?php esc_html_e( 'Divi ETModules', 'ko-cart-icon' ); ?></option>
												<option value="fontawesome" <?php selected( $settings['icon_library'], 'fontawesome' ); ?>><?php esc_html_e( 'Font Awesome', 'ko-cart-icon' ); ?></option>
											</select>
											<p class="ko-ci-help"><?php esc_html_e( 'Choose the icon source first, then choose the specific icon or image below.', 'ko-cart-icon' ); ?></p>
										</div>

										<div class="ko-ci-field ko-ci-field-wide ko-ci-icon-panel" data-ko-icon-panel="dexsvg">
											<label class="ko-ci-label" for="ko-ci-builtin-icon"><?php esc_html_e( 'Built-in SVG Icon', 'ko-cart-icon' ); ?></label>
											<select id="ko-ci-builtin-icon" name="<?php echo esc_attr( self::OPTION_KEY ); ?>[builtin_icon]" data-ko-preview-builtin-icon>
												<option value="dex-cart" <?php selected( $settings['builtin_icon'], 'dex-cart' ); ?>><?php esc_html_e( 'DEX Shopping Cart', 'ko-cart-icon' ); ?></option>
											</select>
											<p class="ko-ci-help"><?php esc_html_e( 'Default cart mark based on the DEX shopping-cart SVG asset. It follows the empty and added color settings.', 'ko-cart-icon' ); ?></p>
										</div>

										<div class="ko-ci-field ko-ci-field-wide ko-ci-icon-panel" data-ko-icon-panel="media">
											<label class="ko-ci-label" for="ko-ci-media-icon-url"><?php esc_html_e( 'Media Library Image', 'ko-cart-icon' ); ?></label>
											<input type="hidden" name="<?php echo esc_attr( self::OPTION_KEY ); ?>[media_icon_id]" value="<?php echo esc_attr( $settings['media_icon_id'] ); ?>" data-ko-preview-media-id>
											<div class="ko-ci-media-control">
												<input id="ko-ci-media-icon-url" name="<?php echo esc_attr( self::OPTION_KEY ); ?>[media_icon_url]" type="text" value="<?php echo esc_url( $media_icon_url ); ?>" readonly data-ko-preview-media-url>
												<div class="ko-ci-media-actions">
													<button type="button" class="button button-secondary" data-ko-select-media><?php esc_html_e( 'Select Image', 'ko-cart-icon' ); ?></button>
													<button type="button" class="button button-secondary" data-ko-remove-media><?php esc_html_e( 'Remove', 'ko-cart-icon' ); ?></button>
												</div>
												<div class="ko-ci-media-preview" data-ko-media-preview>
													<?php if ( $media_icon_url ) : ?>
														<img src="<?php echo esc_url( $media_icon_url ); ?>" alt="">
													<?php endif; ?>
												</div>
											</div>
											<p class="ko-ci-help"><?php esc_html_e( 'The selected image is used as a mask so the empty and added cart colors still apply.', 'ko-cart-icon' ); ?></p>
										</div>

										<div class="ko-ci-field ko-ci-field-wide ko-ci-icon-panel" data-ko-icon-panel="etmodules">
											<label class="ko-ci-label" for="ko-ci-etmodules-icon"><?php esc_html_e( 'ETModules Glyph Code', 'ko-cart-icon' ); ?></label>
											<input id="ko-ci-etmodules-icon" name="<?php echo esc_attr( self::OPTION_KEY ); ?>[etmodules_icon]" type="text" value="<?php echo esc_attr( $settings['etmodules_icon'] ); ?>" data-ko-preview-etmodules-icon>
											<p class="ko-ci-help"><?php esc_html_e( 'Enter the Divi ETModules hexadecimal glyph code, without the leading slash. The default is e015.', 'ko-cart-icon' ); ?></p>
										</div>

										<div class="ko-ci-field ko-ci-field-wide ko-ci-icon-panel" data-ko-icon-panel="fontawesome">
											<label class="ko-ci-label" for="ko-ci-fontawesome-icon"><?php esc_html_e( 'Font Awesome Icon Class', 'ko-cart-icon' ); ?></label>
											<input id="ko-ci-fontawesome-icon" name="<?php echo esc_attr( self::OPTION_KEY ); ?>[fontawesome_icon]" type="text" value="<?php echo esc_attr( $settings['fontawesome_icon'] ); ?>" data-ko-preview-fontawesome-icon>
											<p class="ko-ci-help"><?php esc_html_e( 'The default cart-shopping icon is self-rendered. Custom Font Awesome classes require Font Awesome to be loaded by the theme/site.', 'ko-cart-icon' ); ?></p>
										</div>

										<div class="ko-ci-field">
											<label class="ko-ci-label" for="ko-ci-icon-size"><?php esc_html_e( 'Icon Size', 'ko-cart-icon' ); ?></label>
											<div class="ko-ci-size-control">
												<input id="ko-ci-icon-size" name="<?php echo esc_attr( self::OPTION_KEY ); ?>[icon_size]" type="number" min="12" max="96" step="1" value="<?php echo esc_attr( $settings['icon_size'] ); ?>" data-ko-preview-size>
												<span><?php esc_html_e( 'px', 'ko-cart-icon' ); ?></span>
											</div>
											<p class="ko-ci-help"><?php esc_html_e( 'Controls the base icon size. The badge scales with this value.', 'ko-cart-icon' ); ?></p>
										</div>

										<div class="ko-ci-field">
											<label class="ko-ci-label" for="ko-ci-empty-color"><?php esc_html_e( 'Empty Cart Color', 'ko-cart-icon' ); ?></label>
											<input id="ko-ci-empty-color" name="<?php echo esc_attr( self::OPTION_KEY ); ?>[empty_color]" type="color" value="<?php echo esc_attr( $settings['empty_color'] ); ?>" data-ko-preview-empty-color>
											<p class="ko-ci-help"><?php esc_html_e( 'Used when the cart has no products.', 'ko-cart-icon' ); ?></p>
										</div>

										<div class="ko-ci-field">
											<label class="ko-ci-label" for="ko-ci-active-color"><?php esc_html_e( 'Added Cart Color', 'ko-cart-icon' ); ?></label>
											<input id="ko-ci-active-color" name="<?php echo esc_attr( self::OPTION_KEY ); ?>[active_color]" type="color" value="<?php echo esc_attr( $settings['active_color'] ); ?>" data-ko-preview-active-color>
											<p class="ko-ci-help"><?php esc_html_e( 'Used for the icon and count badge when the cart has products.', 'ko-cart-icon' ); ?></p>
										</div>

										<div class="ko-ci-field">
											<label class="ko-ci-label" for="ko-ci-text-color"><?php esc_html_e( 'Badge Text Color', 'ko-cart-icon' ); ?></label>
											<input id="ko-ci-text-color" name="<?php echo esc_attr( self::OPTION_KEY ); ?>[text_color]" type="color" value="<?php echo esc_attr( $settings['text_color'] ); ?>" data-ko-preview-text-color>
											<p class="ko-ci-help"><?php esc_html_e( 'Controls the number text shown over the cart icon.', 'ko-cart-icon' ); ?></p>
										</div>

										<div class="ko-ci-field">
											<label class="ko-ci-label" for="ko-ci-badge-font-size"><?php esc_html_e( 'Badge Font Size', 'ko-cart-icon' ); ?></label>
											<div class="ko-ci-size-control">
												<input id="ko-ci-badge-font-size" name="<?php echo esc_attr( self::OPTION_KEY ); ?>[badge_font_size]" type="number" min="0" max="32" step="1" value="<?php echo esc_attr( $settings['badge_font_size'] ); ?>" data-ko-preview-badge-font-size>
												<span><?php esc_html_e( 'px', 'ko-cart-icon' ); ?></span>
											</div>
											<p class="ko-ci-help"><?php esc_html_e( 'Controls the number font size. Use 0 for automatic sizing.', 'ko-cart-icon' ); ?></p>
										</div>

										<div class="ko-ci-field">
											<label class="ko-ci-label" for="ko-ci-badge-background-color"><?php esc_html_e( 'Badge Background Color', 'ko-cart-icon' ); ?></label>
											<input id="ko-ci-badge-background-color" name="<?php echo esc_attr( self::OPTION_KEY ); ?>[badge_background_color]" type="color" value="<?php echo esc_attr( $settings['badge_background_color'] ); ?>" data-ko-preview-badge-background-color>
											<label class="ko-ci-checkbox-control">
												<input name="<?php echo esc_attr( self::OPTION_KEY ); ?>[badge_background_transparent]" type="checkbox" value="1" <?php checked( $settings['badge_background_transparent'], 1 ); ?> data-ko-preview-badge-background-transparent>
												<span><?php esc_html_e( 'Transparent background', 'ko-cart-icon' ); ?></span>
											</label>
											<p class="ko-ci-help"><?php esc_html_e( 'Controls the circle behind the number. Enable transparent for a border-only badge.', 'ko-cart-icon' ); ?></p>
										</div>

										<div class="ko-ci-field">
											<label class="ko-ci-label" for="ko-ci-badge-border-color"><?php esc_html_e( 'Badge Border Color', 'ko-cart-icon' ); ?></label>
											<input id="ko-ci-badge-border-color" name="<?php echo esc_attr( self::OPTION_KEY ); ?>[badge_border_color]" type="color" value="<?php echo esc_attr( $settings['badge_border_color'] ); ?>" data-ko-preview-badge-border-color>
											<p class="ko-ci-help"><?php esc_html_e( 'Controls the ring around the badge.', 'ko-cart-icon' ); ?></p>
										</div>

										<div class="ko-ci-field">
											<label class="ko-ci-label" for="ko-ci-badge-border-width"><?php esc_html_e( 'Badge Border Thickness', 'ko-cart-icon' ); ?></label>
											<div class="ko-ci-size-control">
												<input id="ko-ci-badge-border-width" name="<?php echo esc_attr( self::OPTION_KEY ); ?>[badge_border_width]" type="number" min="0" max="8" step="1" value="<?php echo esc_attr( $settings['badge_border_width'] ); ?>" data-ko-preview-badge-border-width>
												<span><?php esc_html_e( 'px', 'ko-cart-icon' ); ?></span>
											</div>
											<p class="ko-ci-help"><?php esc_html_e( 'Use 0 for no border.', 'ko-cart-icon' ); ?></p>
										</div>
									</div>

									<div class="ko-ci-actions">
										<button type="submit" name="ko_cart_icon_action" value="save" class="button button-primary"><?php esc_html_e( 'Save Settings', 'ko-cart-icon' ); ?></button>
										<button type="submit" name="ko_cart_icon_action" value="reset" class="button button-secondary" onclick="return confirm('Reset KO Cart Icon settings?');"><?php esc_html_e( 'Reset Settings', 'ko-cart-icon' ); ?></button>
									</div>
								</form>
							</section>
						</div>

						<aside class="ko-ci-panel-stack">
							<section class="ko-ci-card">
								<h2><?php esc_html_e( 'Status Snapshot', 'ko-cart-icon' ); ?></h2>
								<div class="ko-ci-stats">
									<div class="ko-ci-stat">
										<div class="ko-ci-stat-label"><?php esc_html_e( 'DEX Cart', 'ko-cart-icon' ); ?></div>
										<div class="ko-ci-stat-value <?php echo $dex_cart_active ? 'ko-ci-status-ok' : 'ko-ci-status-bad'; ?>"><?php echo $dex_cart_active ? esc_html__( 'Detected', 'ko-cart-icon' ) : esc_html__( 'Cookie Fallback', 'ko-cart-icon' ); ?></div>
									</div>
									<div class="ko-ci-stat">
										<div class="ko-ci-stat-label"><?php esc_html_e( 'Current Count', 'ko-cart-icon' ); ?></div>
										<div class="ko-ci-stat-value"><?php echo esc_html( (string) $count ); ?></div>
									</div>
									<div class="ko-ci-stat">
										<div class="ko-ci-stat-label"><?php esc_html_e( 'Icon Set', 'ko-cart-icon' ); ?></div>
										<div class="ko-ci-stat-value"><?php echo esc_html( self::icon_library_label( $settings['icon_library'] ) ); ?></div>
									</div>
								</div>
							</section>

							<section class="ko-ci-card">
								<h2><?php esc_html_e( 'Preview', 'ko-cart-icon' ); ?></h2>
								<div class="ko-ci-preview-row">
									<div>
										<div class="ko-ci-preview-label"><?php esc_html_e( 'Empty', 'ko-cart-icon' ); ?></div>
										<?php echo self::render_icon_markup( 0, $settings, array( 'class' => 'ko-ci-admin-preview', 'url' => '#', 'label' => __( 'View cart', 'ko-cart-icon' ) ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
									</div>
									<div>
										<div class="ko-ci-preview-label"><?php esc_html_e( 'With Products', 'ko-cart-icon' ); ?></div>
										<?php echo self::render_icon_markup( max( 3, $count ), $settings, array( 'class' => 'ko-ci-admin-preview', 'url' => '#', 'label' => __( 'View cart', 'ko-cart-icon' ) ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
									</div>
								</div>
							</section>

							<section class="ko-ci-card">
								<h2><?php esc_html_e( 'Shortcodes', 'ko-cart-icon' ); ?></h2>
								<div class="ko-ci-shortcode-list">
									<code>[ko_cart_icon]</code>
									<code>[ko_cart_icon icon_library="dexsvg"]</code>
									<code>[ko_cart_icon icon_library="media" media_icon_url="/wp-content/uploads/cart.svg"]</code>
									<code>[ko_cart_icon icon_library="fontawesome"]</code>
									<code>[ko_cart_icon size="32" class="header-cart"]</code>
									<code>[ko_cart_icon url="/cart/" label="Open cart"]</code>
								</div>
							</section>
						</aside>
					</div>

					<div class="ko-ci-footer-card">
						<p><?php echo esc_html( sprintf( __( 'KO – Cart Icon v%s', 'ko-cart-icon' ), self::VERSION ) ); ?></p>
					</div>
				</div>
			</div>
			<?php
		}

		private static function icon_library_label( $library ) {
			if ( 'fontawesome' === $library ) {
				return __( 'Font Awesome', 'ko-cart-icon' );
			}

			if ( 'etmodules' === $library ) {
				return __( 'ETModules', 'ko-cart-icon' );
			}

			if ( 'media' === $library ) {
				return __( 'Media Library Image', 'ko-cart-icon' );
			}

			return __( 'DEX SVG Cart', 'ko-cart-icon' );
		}

		public static function render_shortcode( $atts ) {
			self::enqueue_frontend_assets();

			$settings = self::get_settings();
			$atts     = shortcode_atts(
				array(
					'icon_library' => '',
					'builtin_icon' => '',
					'fontawesome_icon' => '',
					'etmodules_icon' => '',
					'media_icon_id' => '',
					'media_icon_url' => '',
					'empty_color'  => '',
					'active_color' => '',
					'text_color'   => '',
					'badge_background_color' => '',
					'badge_bg_color' => '',
					'badge_background_transparent' => '',
					'badge_border_color' => '',
					'badge_border_width' => '',
					'badge_font_size' => '',
					'size'         => '',
					'icon_size'    => '',
					'url'          => '',
					'label'        => __( 'View cart', 'ko-cart-icon' ),
					'class'        => '',
				),
				$atts,
				self::SHORTCODE
			);

			$settings = self::settings_with_shortcode_overrides( $settings, $atts );
			$count    = self::get_cart_count();

			return self::render_icon_markup(
				$count,
				$settings,
				array(
					'class' => $atts['class'],
					'url'   => $atts['url'],
					'label' => $atts['label'],
				)
			);
		}

		private static function settings_with_shortcode_overrides( $settings, $atts ) {
			$defaults = self::default_settings();

			if ( '' !== trim( (string) $atts['icon_library'] ) ) {
				$settings['icon_library'] = self::sanitize_icon_library( $atts['icon_library'] );
			}

			if ( '' !== trim( (string) $atts['builtin_icon'] ) ) {
				$settings['builtin_icon'] = self::sanitize_builtin_icon( $atts['builtin_icon'] );
			}

			if ( '' !== trim( (string) $atts['fontawesome_icon'] ) ) {
				$settings['fontawesome_icon'] = self::sanitize_fontawesome_icon( $atts['fontawesome_icon'] );
			}

			if ( '' !== trim( (string) $atts['etmodules_icon'] ) ) {
				$settings['etmodules_icon'] = self::sanitize_etmodules_icon( $atts['etmodules_icon'] );
			}

			if ( '' !== trim( (string) $atts['media_icon_id'] ) ) {
				$settings['media_icon_id'] = self::clamp_int( $atts['media_icon_id'], 0, PHP_INT_MAX, 0 );
			}

			if ( '' !== trim( (string) $atts['media_icon_url'] ) ) {
				$settings['media_icon_url'] = self::sanitize_media_icon_url( $atts['media_icon_url'] );
			}

			if ( '' !== trim( (string) $atts['empty_color'] ) ) {
				$settings['empty_color'] = self::sanitize_color( $atts['empty_color'], $settings['empty_color'] );
			}

			if ( '' !== trim( (string) $atts['active_color'] ) ) {
				$settings['active_color'] = self::sanitize_color( $atts['active_color'], $settings['active_color'] );
			}

			if ( '' !== trim( (string) $atts['text_color'] ) ) {
				$settings['text_color'] = self::sanitize_color( $atts['text_color'], $settings['text_color'] );
			}

			$badge_background_color = '' !== trim( (string) $atts['badge_background_color'] ) ? $atts['badge_background_color'] : $atts['badge_bg_color'];
			if ( '' !== trim( (string) $badge_background_color ) ) {
				if ( 'transparent' === strtolower( trim( (string) $badge_background_color ) ) ) {
					$settings['badge_background_transparent'] = 1;
				} else {
					$settings['badge_background_color'] = self::sanitize_color( $badge_background_color, $settings['badge_background_color'] );
				}
			}

			if ( '' !== trim( (string) $atts['badge_background_transparent'] ) ) {
				$settings['badge_background_transparent'] = self::sanitize_bool( $atts['badge_background_transparent'] );
			}

			if ( '' !== trim( (string) $atts['badge_border_color'] ) ) {
				$settings['badge_border_color'] = self::sanitize_color( $atts['badge_border_color'], $settings['badge_border_color'] );
			}

			if ( '' !== trim( (string) $atts['badge_border_width'] ) ) {
				$settings['badge_border_width'] = self::clamp_int( $atts['badge_border_width'], 0, 8, $settings['badge_border_width'] );
			}

			if ( '' !== trim( (string) $atts['badge_font_size'] ) ) {
				$settings['badge_font_size'] = self::clamp_int( $atts['badge_font_size'], 0, 32, $settings['badge_font_size'] );
			}

			$size = '' !== trim( (string) $atts['size'] ) ? $atts['size'] : $atts['icon_size'];
			if ( '' !== trim( (string) $size ) ) {
				$settings['icon_size'] = self::clamp_int( $size, 12, 96, $defaults['icon_size'] );
			}

			return self::sanitize_settings( $settings );
		}

		private static function render_icon_markup( $count, $settings, $args = array() ) {
			$count        = max( 0, absint( $count ) );
			$settings     = self::sanitize_settings( $settings );
			$args         = wp_parse_args(
				$args,
				array(
					'class' => '',
					'url'   => '',
					'label' => __( 'View cart', 'ko-cart-icon' ),
				)
			);
			$state_class  = $count > 0 ? 'ko-ci-has-items' : 'ko-ci-is-empty';
			$extra_class  = self::sanitize_class_list( $args['class'] );
			$library      = self::resolve_icon_library( $settings );
			$display      = self::format_count( $count );
			$label        = sanitize_text_field( (string) $args['label'] );
			$label        = '' !== $label ? $label : __( 'View cart', 'ko-cart-icon' );
			$url          = self::resolve_cart_url( $args['url'] );
			$aria_label   = self::get_aria_label( $label, $count );
			$fa_class     = self::sanitize_fontawesome_icon( $settings['fontawesome_icon'] );
			$fa_mode      = 'fa-solid fa-cart-shopping' === $fa_class ? 'ko-ci-fontawesome-default' : 'ko-ci-fontawesome-custom';
			$inline_style = self::build_icon_style( $settings, $library );
			$classes      = trim( sprintf( 'ko-ci-cart-icon ko-ci-icon-library-%1$s ko-ci-builtin-icon-%2$s %3$s %4$s %5$s', $library, $settings['builtin_icon'], $fa_mode, $state_class, $extra_class ) );

			ob_start();
			?>
			<a href="<?php echo esc_url( $url ); ?>" class="<?php echo esc_attr( $classes ); ?>" style="<?php echo esc_attr( $inline_style ); ?>" data-ko-cart-icon data-ko-cart-count="<?php echo esc_attr( $count ); ?>" data-ko-cart-label="<?php echo esc_attr( $label ); ?>" aria-label="<?php echo esc_attr( $aria_label ); ?>">
				<span class="ko-ci-icon" aria-hidden="true">
					<?php if ( 'fontawesome' === $library ) : ?>
						<span class="ko-ci-fontawesome-glyph <?php echo esc_attr( $fa_class ); ?>"></span>
					<?php endif; ?>
				</span>
				<span class="ko-ci-count-badge" aria-hidden="true"><?php echo esc_html( $display ); ?></span>
				<span class="screen-reader-text"><?php echo esc_html( $aria_label ); ?></span>
			</a>
			<?php

			return trim( ob_get_clean() );
		}

		private static function sanitize_class_list( $classes ) {
			$parts = preg_split( '/\s+/', (string) $classes );
			$parts = array_filter( array_map( 'sanitize_html_class', $parts ) );

			return implode( ' ', $parts );
		}

		private static function resolve_cart_url( $url ) {
			$url = trim( (string) $url );

			if ( '' !== $url ) {
				return $url;
			}

			if ( class_exists( 'KO_Page_Part_Cart' ) && method_exists( 'KO_Page_Part_Cart', 'get_post_id' ) ) {
				$post_id = KO_Page_Part_Cart::get_post_id();
				$link    = $post_id ? get_permalink( $post_id ) : '';

				if ( $link ) {
					return $link;
				}
			}

			return home_url( '/part-cart/' );
		}

		private static function resolve_icon_library( $settings ) {
			$library = self::sanitize_icon_library( $settings['icon_library'] ?? 'dexsvg' );

			if ( 'media' === $library && '' === self::get_media_icon_url( $settings ) ) {
				return 'dexsvg';
			}

			return $library;
		}

		private static function get_media_icon_url( $settings ) {
			$settings = is_array( $settings ) ? $settings : array();
			$icon_id  = isset( $settings['media_icon_id'] ) ? absint( $settings['media_icon_id'] ) : 0;

			if ( $icon_id ) {
				$attachment_url = wp_get_attachment_url( $icon_id );

				if ( $attachment_url ) {
					return $attachment_url;
				}
			}

			return isset( $settings['media_icon_url'] ) ? self::sanitize_media_icon_url( $settings['media_icon_url'] ) : '';
		}

		private static function build_icon_style( $settings, $library ) {
			$badge_background_color = ! empty( $settings['badge_background_transparent'] ) ? 'transparent' : $settings['badge_background_color'];
			$badge_shadow           = ! empty( $settings['badge_background_transparent'] ) ? 'none' : '0 3px 8px rgba(15,23,42,0.18)';
			$style = sprintf(
				'--ko-ci-empty-color:%1$s;--ko-ci-active-color:%2$s;--ko-ci-text-color:%3$s;--ko-ci-icon-size:%4$dpx;--ko-ci-badge-background-color:%5$s;--ko-ci-badge-border-color:%6$s;--ko-ci-badge-border-width:%7$dpx;--ko-ci-badge-shadow:%8$s;',
				$settings['empty_color'],
				$settings['active_color'],
				$settings['text_color'],
				absint( $settings['icon_size'] ),
				$badge_background_color,
				$settings['badge_border_color'],
				absint( $settings['badge_border_width'] ),
				$badge_shadow
			);

			if ( 'etmodules' === $library ) {
				$style .= sprintf( '--ko-ci-etmodules-glyph:"\%s";', self::sanitize_etmodules_icon( $settings['etmodules_icon'] ) );
			}

			if ( 'media' === $library ) {
				$style .= sprintf( '--ko-ci-icon-image:%s;', self::css_url_value( self::get_media_icon_url( $settings ) ) );
			}

			if ( ! empty( $settings['badge_font_size'] ) ) {
				$style .= sprintf( '--ko-ci-badge-font-size:%dpx;', absint( $settings['badge_font_size'] ) );
			}

			return $style;
		}

		private static function css_url_value( $url ) {
			$url = str_replace( array( '\\', '"' ), array( '\\\\', '%22' ), self::sanitize_media_icon_url( $url ) );

			return $url ? sprintf( 'url("%s")', $url ) : 'none';
		}

		private static function format_count( $count ) {
			$count = absint( $count );

			return $count > 999 ? '999+' : (string) $count;
		}

		private static function get_aria_label( $label, $count ) {
			$count = absint( $count );

			if ( $count <= 0 ) {
				return sprintf( __( '%s, cart is empty', 'ko-cart-icon' ), $label );
			}

			return sprintf(
				_n( '%1$s, %2$d product in cart', '%1$s, %2$d products in cart', $count, 'ko-cart-icon' ),
				$label,
				$count
			);
		}

		private static function dex_cart_is_available() {
			return class_exists( 'KO_Page_Part_Cart' ) || function_exists( 'ko_get_part_cart' );
		}

		private static function get_cart_count() {
			$counts = array();

			if ( class_exists( 'KO_Page_Part_Cart' ) && method_exists( 'KO_Page_Part_Cart', 'item_count' ) ) {
				$counts[] = max( 0, absint( KO_Page_Part_Cart::item_count() ) );
			}

			if ( function_exists( 'ko_get_part_cart' ) ) {
				$counts[] = self::count_cart_items( ko_get_part_cart() );
			}

			if ( isset( $_COOKIE['ko_part_cart'] ) ) {
				$counts[] = self::count_cart_items( self::maybe_unserialize_part_cart_cookie( wp_unslash( $_COOKIE['ko_part_cart'] ) ) );
			}

			return $counts ? max( $counts ) : 0;
		}

		private static function count_cart_items( $cart ) {
			if ( is_object( $cart ) ) {
				$cart = get_object_vars( $cart );
			}

			if ( ! is_array( $cart ) ) {
				return 0;
			}

			if ( self::is_flat_quantity_map( $cart ) ) {
				return array_sum( array_map( 'absint', $cart ) );
			}

			$count = 0;
			$item_container_keys = array(
				'items',
				'cart_items',
				'contents',
				'products',
				'parts',
				'cart',
			);

			foreach ( $cart as $key => $item ) {
				if ( in_array( strtolower( (string) $key ), $item_container_keys, true ) && ( is_array( $item ) || is_object( $item ) ) ) {
					$count += self::count_cart_items( $item );
				}
			}

			if ( $count > 0 ) {
				return $count;
			}

			$quantity = self::extract_cart_entry_quantity( $cart );

			if ( null !== $quantity ) {
				return max( 0, absint( $quantity ) );
			}

			foreach ( $cart as $item ) {
				if ( is_array( $item ) || is_object( $item ) ) {
					$count += self::count_cart_entry( $item );
				}
			}

			if ( $count > 0 ) {
				return $count;
			}

			return self::looks_like_cart_item( $cart ) ? 1 : 0;
		}

		private static function count_cart_entry( $entry ) {
			if ( is_numeric( $entry ) ) {
				return max( 0, absint( $entry ) );
			}

			if ( is_object( $entry ) ) {
				$entry = get_object_vars( $entry );
			}

			if ( ! is_array( $entry ) ) {
				return 0;
			}

			$quantity = self::extract_cart_entry_quantity( $entry );

			if ( null !== $quantity ) {
				return max( 0, absint( $quantity ) );
			}

			if ( self::is_flat_quantity_map( $entry ) ) {
				return array_sum( array_map( 'absint', $entry ) );
			}

			$count = 0;

			foreach ( $entry as $value ) {
				if ( is_array( $value ) || is_object( $value ) ) {
					$count += self::count_cart_entry( $value );
				}
			}

			if ( $count > 0 ) {
				return $count;
			}

			return self::looks_like_cart_item( $entry ) ? 1 : 0;
		}

		private static function extract_cart_entry_quantity( $entry ) {
			$quantity_keys = array(
				'quantity',
				'qty',
				'item_quantity',
				'cart_quantity',
				'count',
			);

			foreach ( $entry as $key => $value ) {
				if ( in_array( strtolower( (string) $key ), $quantity_keys, true ) && is_numeric( $value ) ) {
					return $value;
				}
			}

			return null;
		}

		private static function is_flat_quantity_map( $entry ) {
			if ( array() === $entry ) {
				return false;
			}

			foreach ( $entry as $value ) {
				if ( ! is_numeric( $value ) ) {
					return false;
				}
			}

			return true;
		}

		private static function looks_like_cart_item( $entry ) {
			$item_keys = array(
				'inventory_item_id',
				'inventory-id',
				'vendor_code',
				'vendor_store',
				'part_number',
				'part',
				'sku',
				'tag',
			);

			foreach ( $entry as $key => $value ) {
				if ( in_array( strtolower( (string) $key ), $item_keys, true ) && '' !== (string) $value ) {
					return true;
				}
			}

			return false;
		}

		private static function maybe_unserialize_part_cart_cookie( $raw_cookie ) {
			$raw_cookie = stripslashes( (string) $raw_cookie );
			$candidates = array(
				$raw_cookie,
				rawurldecode( $raw_cookie ),
			);

			foreach ( $candidates as $candidate ) {
				$value = maybe_unserialize( $candidate );

				if ( is_array( $value ) || is_object( $value ) ) {
					return $value;
				}

				$json = json_decode( $candidate, true );

				if ( is_array( $json ) ) {
					return $json;
				}
			}

			return array();
		}

		public static function ajax_cart_count() {
			$count = self::get_cart_count();

			wp_send_json_success(
				array(
					'count'   => $count,
					'display' => self::format_count( $count ),
					'label'   => self::get_aria_label( __( 'View cart', 'ko-cart-icon' ), $count ),
				)
			);
		}

	}

	KO_Cart_Icon::init();
}
