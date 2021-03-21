<?php
/**  **
 * Plugin Name: Aqua Verifier
 * Plugin URI: http://aquagraphite.com/
 * Description: Custom user registration form with Envato API verification
 * Version: 2.0.0
 * Author: Syamil MJ
 * Author URI: http://aquagraphite.com/
 *
 * @package         Requite Core
 * @author          Syamil MK.
 * @copyright       2013, Syamil MJ. All rights reserved.
 * @licence         GPL
 */

/**
 * March 20, 2021
 * Modified by Kevin Provance (kevin.provance@gmail.com) for SVL Studios (www.svlstudios.com).
 *
 * Envato API updated to v3.
 * Meets current WordPress Coding Standards.
 * Added security: sanitizing, escaping, and nonces for POST functions.
 * Completed sanitize_settings function.
 */

/** Prevent direct access **/
defined( 'ABSPATH' ) || exit;

/** Translations */
load_plugin_textdomain( 'a10e_av', false, dirname( plugin_basename( __FILE__ ) ) . '/lang' );

/**
 * AQ_Verifier class
 *
 * @since 1.0
 */
if ( ! class_exists( 'AQ_Verifier' ) ) {

	/**
	 * Class AQ_Verifier
	 */
	class AQ_Verifier {

		/**
		 * Settings page slug.
		 *
		 * @var false|mixed|void
		 */
		private $page;

		/**
		 * Global options.
		 *
		 * @var false|mixed|void
		 */
		private $options;

		/**
		 * Envato API URL.
		 *
		 * @var string
		 */
		private $api = 'https://api.envato.com/v3/market/';

		/**
		 * Envato API token.
		 *
		 * @var string
		 */
		private $envato_token = 'REa9PE3LSFCOo6NbtP4CtXd5k172tanc';

		/**
		 * User agent.
		 *
		 * @var string
		 */
		private $user_agent;

		/**
		 * Plugin URL.
		 *
		 * @var string
		 */
		public $plugin_url = '';

		/**
		 * Plugin path.
		 *
		 * @var string
		 */
		public $plugin_path = '';


		/**
		 * AQ_Verifier constructor.
		 */
		public function __construct() {
			if ( ! get_option( 'aq_verifier_slug' ) ) {
				update_option( 'aq_verifier_slug', 'settings_page_aqua-verifier' );
			}

			$slug             = get_option( 'aq_verifier_slug' );
			$this->page       = $slug;
			$this->options    = get_option( $slug );
			$this->user_agent = 'SVL Studios: ' . sanitize_text_field( wp_unslash( $_SERVER['HTTP_USER_AGENT'] ?? '' ) );
		}

		/**
		 * Init plugin.
		 */
		public function init() {
			if ( is_admin() ) {
				add_action( 'admin_menu', array( $this, 'register_settings' ) );
			} else {
				add_action( 'login_form_register', array( $this, 'view_registration_page' ) );
				add_filter( 'shake_error_codes', array( &$this, 'shaker' ), 10, 1 );
				add_filter( 'login_headerurl', array( &$this, 'modify_login_headerurl' ), 10, 1 );
			}

			add_action( 'init', array( &$this, 'plugin_info' ) );
		}

		/**
		 * Plugin info.
		 */
		public function plugin_info() {
			$file              = dirname( __FILE__ ) . '/aq-verifier.php';
			$this->plugin_url  = plugin_dir_url( $file );
			$this->plugin_path = plugin_dir_path( $file );
		}

		/**
		 * Register settings.
		 */
		public function register_settings() {
			$slug = add_options_page( 'Aqua Verifier', 'Aqua Verifier', 'manage_options', 'aqua-verifier', array( $this, 'view_admin_settings' ) );

			$this->page    = $slug;
			$this->options = get_option( $slug );

			register_setting( $slug, $slug, array( $this, 'sanitize_settings' ) );

			add_settings_section( $slug, '', '__return_false', $slug );

			add_settings_field(
				'marketplace_username',
				'Market Username',
				array( $this, 'settings_field_input' ),
				$slug,
				$slug,
				array(
					'id'   => 'marketplace_username',
					'desc' => __( 'Case sensitive', 'a10e_av' ),
				)
			);

			add_settings_field(
				'api_key',
				'API Key',
				array( $this, 'settings_field_input' ),
				$slug,
				$slug,
				array(
					'id'   => 'api_key',
					'desc' => __( 'More info about ', 'a10e_av' ) . '<a target="_blank" href="https://themeforest.net/help/api">Envato API</a>',
				)
			);

			add_settings_field(
				'custom_style',
				'Custom Styling',
				array( $this, 'settings_field_textarea' ),
				$slug,
				$slug,
				array(
					'id'   => 'custom_style',
					'desc' => __( 'Add custom inline styling to the registration page', 'a10e_av' ),
				)
			);

			add_settings_field(
				'disable_username',
				'Disable Username input',
				array( $this, 'settings_field_checkbox' ),
				$slug,
				$slug,
				array(
					'id'   => 'disable_username',
					'desc' => __( 'Disable the username field and use only the purchase code', 'a10e_av' ),
				)
			);

			add_settings_field(
				'display_credit',
				'Display "Powered by"',
				array( $this, 'settings_field_checkbox' ),
				$slug,
				$slug,
				array(
					'id'   => 'display_credit',
					'desc' => __( 'Display small credit line to help others find the plugin', 'a10e_av' ),
				)
			);

		}

		/**
		 * Settings Field input.
		 *
		 * @param array $args Args.
		 */
		public function settings_field_input( array $args ) {
			$slug    = $this->page;
			$id      = $args['id'];
			$desc    = $args['desc'];
			$options = $this->options;
			$value   = $options[ $id ] ?? '';

			echo "<input id='" . esc_attr( $id ) . "' name='" . esc_attr( $slug ) . '[' . esc_attr( $id ) . ']' . "' size='40' type='text' value='" . esc_attr( $value ) . "' />";
			echo "<p class='description'>" . wp_kses_post( $desc ) . '</div>';
		}

		/**
		 * Settings field textarea.
		 *
		 * @param array $args Args.
		 */
		public function settings_field_textarea( array $args ) {
			$slug    = $this->page;
			$id      = $args['id'];
			$desc    = $args['desc'];
			$options = $this->options;

			$default = '#login {width: 500px} .success {background-color: #F0FFF8; border: 1px solid #CEEFE1;';

			if ( ! isset( $options['custom_style'] ) ) {
				$options['custom_style'] = $default;
			}
			$text = $options['custom_style'];

			echo "<textarea id='" . esc_attr( $id ) . "' name='" . esc_attr( $slug ) . '[' . esc_attr( $id ) . ']' . "' rows='7' cols='50' class='large-text code'>" . esc_textarea( $text ) . '</textarea>';
			echo "<p class='description'>" . esc_html( $desc ) . '</div>';
		}

		/**
		 * Settings fiewld checkbox.
		 *
		 * @param array $args Args.
		 */
		public function settings_field_checkbox( array $args ) {
			$slug    = $this->page;
			$id      = $args['id'];
			$desc    = $args['desc'];
			$options = $this->options;

			echo '<label for="' . esc_attr( $id ) . '">';
			echo '<input type="checkbox" id="' . esc_attr( $id ) . '" name="' . esc_attr( $slug ) . '[' . esc_attr( $id ) . ']" value="1" ' . checked( ( $options[ $id ] ?? false ), 1, false ) . '/>';
			echo '&nbsp;' . esc_html( $desc ) . '</label>';
		}

		/**
		 * Sanitize settings.
		 *
		 * @param array $args Args.
		 *
		 * @return array
		 */
		public function sanitize_settings( array $args ): array {
			$api = str_replace( 'v3', 'v1', $this->api );

			$api_url = $api . 'user:' . $args['marketplace_username'] . '.json';

			$api_args = array(
				'timeout' => 20,
				'headers' => array(
					'Authorization' => 'Bearer ' . $this->envato_token,
					'User-Agent'    => $this->user_agent,
				),
			);

			$response = wp_remote_get( $api_url, $api_args );

			$slug = $this->page;

			if ( ! is_wp_error( $response ) && is_array( $response ) && ! empty( $response['body'] ) ) {
				if ( 200 !== wp_remote_retrieve_response_code( $response ) ) {
					add_settings_error(
						$slug,
						'invalid_author',
						esc_html__( 'That username/api-key is invalid. Please make sure that you have entered them correctly', 'a10e_av' )
					);
				}
			}

			return $args;
		}

		/**
		 * Main Settings panel
		 *
		 * @since    1.0
		 */
		public function view_admin_settings() {

			?>
			<div class="wrap">
				<div id="icon-options-general" class="icon32"></div>
				<h2><?php esc_html_e( 'Aqua Verifier Settings', 'a10e_av' ); ?></h2>
				<form action="options.php" method="post">
					<?php
					$slug = $this->page;
					settings_fields( $slug, $slug );
					do_settings_sections( $slug );
					submit_button();
					?>
				</form>
			</div>
			<?php

		}

		/**
		 * Modifies the default registration page
		 *
		 * @since    1.0
		 */
		public function view_registration_page() {
			global $errors;

			$http_post = ( 'POST' === $_SERVER['REQUEST_METHOD'] ?? '' );

			if ( $http_post ) {
				if ( isset( $_POST['verify_code_nonce'] ) && wp_verify_nonce( sanitize_key( wp_unslash( $_POST['verify_code_nonce'] ) ), 'verify_code_nonce' ) ) {
					$action               = sanitize_text_field( wp_unslash( $_POST['wp-submit'] ?? '' ) );
					$marketplace_username = sanitize_text_field( wp_unslash( $_POST['marketplace_username'] ?? '' ) );
					$purchase_code        = sanitize_text_field( wp_unslash( $_POST['purchase_code'] ?? '' ) );
					$verify               = $this->verify_purchase( $marketplace_username, $purchase_code );
					if ( 'Register' === $action ) {
						if ( ! is_wp_error( $verify ) ) {
							$user_login = sanitize_text_field( wp_unslash( $_POST['user_login'] ?? '' ) );
							$user_email = sanitize_text_field( wp_unslash( $_POST['user_email'] ?? '' ) );

							// phpcs:ignore WordPress.WP.GlobalVariablesOverride
							$errors = register_new_user( $user_login, $user_email );

							if ( ! is_wp_error( $errors ) ) {
								$user_id = $errors;

								// Change role.
								wp_update_user(
									array(
										'ID'   => $user_id,
										'role' => 'subscriber',
									)
								);

								// Update user meta.
								$items                   = array();
								$items[ $purchase_code ] = array(
									'name'          => $verify['item']['name'],
									'id'            => $verify['item']['id'],
									'date'          => $verify['sold_at'],
									'buyer'         => $verify['buyer'],
									'licence'       => $verify['licence'],
									'purchase_code' => $verify['item']['purchase_code'],
								);

								update_user_meta( $user_id, 'purchased_items', $items );

								$redirect_to = 'wp-login.php?checkemail=registered';
								wp_safe_redirect( $redirect_to );
								exit();

							} else {
								$this->view_registration_form( $errors, $verify );
							}
						} else {

							// Force to resubmit verify form.
							$this->view_verification_form( $verify );
						}
					} elseif ( 'Verify' === $action ) {

						// Verified, supply the registration form.
						if ( ! is_wp_error( $verify ) ) {

							// Purchase Item Info.
							$this->view_registration_form( $errors, $verify );

						} else {

							// Force to resubmit verify form.
							$this->view_verification_form( $verify );

						}
					}
				} else {
					$this->view_verification_form();
				}
			} else {
				$this->view_verification_form();
			}

			$this->custom_style();

			exit();
		}

		/**
		 * View verification form.
		 *
		 * @param string $errors Errors.
		 */
		public function view_verification_form( $errors = '' ) {
			login_header( __( 'Verify Purchase Form' ), '<p class="message register">' . __( 'Verify Purchase' ) . '</p>', $errors );

			?>
			<form name="registerform" id="registerform" action="<?php echo esc_url( site_url( 'wp-login.php?action=register', 'login_post' ) ); ?>" method="post">
				<?php if ( ! isset( $this->options['disable_username'] ) ) { ?>
					<p>
						<label for="marketplace_username"><?php esc_html_e( 'Market Username (case sensitive)' ); ?><br/>
							<input type="text" name="marketplace_username" id="marketplace_username" class="input" size="20" tabindex="10"/></label>
					</p>
				<?php } ?>
				<p>
					<label for="purchase_code"><?php esc_html_e( 'Purchase Code' ); ?><br/>
						<input type="text" name="purchase_code" id="purchase_code" class="input" size="20" tabindex="20"/>
					</label>
				</p>
				<p>
					<a href="<?php echo esc_url( $this->plugin_url ); ?>img/find-item-purchase-code.png" target="_blank">Where can I find my item purchase code?</a>
				</p>
				<br class="clear"/>
				<?php wp_nonce_field( 'verify_code_nonce', 'verify_code_nonce' ); ?>
				<p class="submit">
					<input type="submit" name="wp-submit" id="wp-submit" class="button button-primary button-large" value="<?php esc_attr_e( 'Verify' ); ?>" tabindex="100"/>
				</p>
			</form>
			<?php

			$this->view_credit();
			login_footer( 'user_login' );
		}

		/**
		 * Modifies the default user registration page.
		 *
		 * @param string $errors Errors.
		 * @param array  $verified Verified data.
		 */
		public function view_registration_form( $errors = '', $verified = array() ) {
			login_header( esc_html__( 'Registration Form' ), '<p class="message register">' . esc_html__( 'Register An Account' ) . '</p>', $errors );

			if ( $verified ) {

				?>
				<div class="message success">
					<h3>Purchase Information</h3><br/>
					<ul style="margin-left: 20px;">
						<li><strong>Buyer: </strong><?php echo esc_html( $verified['buyer'] ); ?></li>
						<li><strong>Item: </strong><?php echo esc_html( $verified['item']['name'] ); ?></li>
						<li><strong>Purchase Code: </strong><?php echo esc_html( $verified['item']['purchase_code'] ); ?></li>
					</ul>
				</div>
				<?php } ?>

			<form name="registerform" id="registerform" action="<?php echo esc_url( site_url( 'wp-login.php?action=register', 'login_post' ) ); ?>" method="post">
				<input type="hidden" name="marketplace_username" value="<?php echo esc_attr( $verified['buyer'] ); ?>"/>
				<input type="hidden" name="purchase_code" value="<?php echo esc_attr( $verified['item']['purchase_code'] ); ?>"/>
				<p>
					<label for="user_login"><?php esc_html_e( 'Username' ); ?><br/>
						<input type="text" name="user_login" id="user_login" class="input" value="" size="20" tabindex="10"/></label>
				</p>
				<p>
					<label for="user_email"><?php esc_html_e( 'E-mail' ); ?><br/>
						<input type="email" name="user_email" id="user_email" class="input" value="" size="25" tabindex="20"/></label>
				</p>
				<p id="reg_passmail"><?php esc_html_e( 'A password will be e-mailed to you.' ); ?></p>
				<br class="clear"/>
				<?php wp_nonce_field( 'verify_code_nonce', 'verify_code_nonce' ); ?>
				<p class="submit"><input type="submit" name="wp-submit" id="wp-submit" class="button-primary" value="<?php esc_attr_e( 'Register' ); ?>" tabindex="100"/></p>
			</form>
			<p id="nav">
				<a href="<?php echo esc_url( wp_login_url() ); ?>"><?php esc_html_e( 'Log in' ); ?></a> |
				<a href="<?php echo esc_url( wp_lostpassword_url() ); ?>" title="<?php esc_attr_e( 'Password Lost and Found' ); ?>"><?php esc_html_e( 'Lost your password?' ); ?></a>
			</p>
			<?php

			login_footer( 'user_login' );
		}

		/**
		 * Verify purchase code and checks if code already used.
		 *
		 * @param string $marketplace_username Username.
		 * @param string $purchase_code Purchase code.
		 *
		 * @return mixed|string|WP_Error
		 */
		public function verify_purchase( $marketplace_username = '', $purchase_code = '' ) {
			$errors  = new WP_Error();
			$options = $this->options;

			// Check for empty fields.
			if ( ( empty( $marketplace_username ) && ! $options['disable_username'] ) || empty( $purchase_code ) ) {
				$errors->add( 'incomplete_form', '<strong>Error</strong>: Incomplete form fields.' );

				return $errors;
			}

			// Gets author data & prepare verification vars.
			$slug          = $this->page;
			$options       = get_option( $slug );
			$author        = $options['marketplace_username'];
			$api_key       = $options['api_key'];
			$purchase_code = trim( rawurlencode( $purchase_code ) );
			$api_url       = $this->api . 'author/sale?code=' . $purchase_code;
			$verified      = false;
			$result        = '';

			// Check if purchase code already used.
			global $wpdb;

			$query = $wpdb->prepare(
				"
					SELECT umeta.user_id
					FROM $wpdb->usermeta as umeta
					WHERE umeta.meta_value LIKE %s",
				$purchase_code
			);

			// phpcs:disable
			$registered = $wpdb->get_var( $query );
			// phpcs:enable

			if ( $registered ) {
				$errors->add( 'used_purchase_code', 'Sorry, but that item purchase code has already been registered with another account. Please login to that account to continue, or create a new account with another purchase code.' );

				return $errors;
			}

			if ( preg_match( '/^([a-f0-9]{8})-(([a-f0-9]{4})-){3}([a-f0-9]{12})$/i', $purchase_code ) ) {
				$args = array(
					'timeout' => 20,
					'headers' => array(
						'Authorization' => 'Bearer ' . $this->envato_token,
						'User-Agent'    => $this->user_agent,
					),
				);

				$response = wp_remote_get( $api_url, $args );

				if ( ! is_wp_error( $response ) && is_array( $response ) && ! empty( $response['body'] ) ) {
					$result = json_decode( $response['body'], true );

					$item = $result['item']['name'];

					if ( $item ) {

						// Check if username matches the one on marketplace.
						if ( strcmp( $result['buyer'], $marketplace_username ) !== 0 && ! $options['disable_username'] ) {
							$errors->add( 'invalid_marketplace_username', 'That username is not valid for this item purchase code. Please make sure you entered the correct username (case sensitive).' );
						} else {
							// add purchase code to $result['verify_purchase'].
							$result['item']['purchase_code'] = $purchase_code;
							$verified                        = true;
						}
					} else {
						// Tell user the purchase code is invalid.
						$errors->add( 'invalid_purchase_code', 'Sorry, but that item purchase code is invalid. Please make sure you have entered the correct purchase code.' );
					}
				} else {
					$errors->add( 'server_error', 'Something went wrong, please try again.' );
				}

				if ( $verified ) {
					return $result;
				} else {
					return $errors;
				}
			}
		}

		/**
		 * Custom form stylings
		 * Adds inline stylings defined in admin options
		 *
		 * @since    1.0
		 */
		public function custom_style() {
			$options = $this->options;

			$style = $options['custom_style'] ?? '';

			if ( ! empty( $style ) ) {
				echo '<style>';
				echo $style; // phpcs:ignore WordPress.Security.EscapeOutput
				echo '</style>';
			}
		}

		/** Small credit line */
		public function view_credit() {
			$options = $this->options;

			if ( '' !== $options['display_credit'] ) {
				echo '<p style="font-size:10px; text-align:right; padding-top: 10px;">Powered by <a target="_blank" href="https://github.com/syamilmj/Aqua-Verifier">Aqua Verifier</a></p>';
			}
		}

		/**
		 * Adds custom shaker codes.  Shake that sexy red booty. (Okay, Syamil.  Thanks for that - kp).
		 *
		 * @param array $shake_error_codes Error codes.
		 *
		 * @return array|string[]
		 */
		public function shaker( array $shake_error_codes ): array {
			$extras = array( 'invalid_purchase_code', 'invalid_marketplace_username', 'server_error', 'incomplete_form', 'used_purchase_code' );

			return array_merge( $extras, $shake_error_codes );
		}

		/**
		 * Modifies login header url.
		 *
		 * @param string $login_header_url Header URL.
		 *
		 * @return string|void
		 */
		public function modify_login_headerurl( $login_header_url = null ) {
			return site_url();
		}
	}

}

$aq_verifier = new AQ_Verifier();
$aq_verifier->init();
