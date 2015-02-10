<?php
/** بسم الله الرحمن الرحيم **

Plugin Name: Aqua Verifier
Plugin URI: http://aquagraphite.com/
Description: Custom user registration form with Envato API verification
Version: 1.1.1
Author: Syamil MJ
Author URI: http://aquagraphite.com/

*/

/**
 * Copyright (c) 2013 Syamil MJ. All rights reserved.
 *
 * Released under the GPL license
 * http://www.opensource.org/licenses/gpl-license.php
 *
 * This is an add-on for WordPress
 * http://wordpress.org/
 *
 * **********************************************************************
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 * **********************************************************************
 */

namespace aqVerifier;

/** Prevent direct access **/
if ( !defined( 'ABSPATH' ) ) exit;

/** Translations */
load_plugin_textdomain( 'a10e_av', false, dirname( plugin_basename( __FILE__ ) ) . '/lang/' );

require_once( 'vendor/autoload.php' );


/**
 * AQ_Verifier class
 *
 * @since 1.0
 */
if(!class_exists('AQ_Verifier')) {

	class AQ_Verifier {

		private $page; // settings page slug
		private $options; // global options
		private $api = 'http://marketplace.envato.com/api/edge/'; // base URL to envato api
		public $plugin_url;
		public $plugin_path;

		/** Constructor */
		function __construct() {

			if(!get_option('aq_verifier_slug')) update_option( 'aq_verifier_slug', 'settings_page_aqua-verifier' );

			$slug 			= get_option('aq_verifier_slug');
			$this->page 	= $slug;
			$this->options 	= get_option($slug);

		}

		function init() {

			if(is_admin()) {
				add_action( 'admin_menu', array($this, 'register_settings') );
			} else {
				add_action( 'login_form_register', array($this, 'view_registration_page') );
				add_filter( 'shake_error_codes', array(&$this, 'shaker'), 10, 1 );
				add_filter( 'login_headerurl', array(&$this, 'modify_login_headerurl'), 10, 1);
			}

			add_action('init', array(&$this, 'plugin_info'));

		}

		function plugin_info() {

			$file = dirname(__FILE__) . '/aq-verifier.php';
			$this->plugin_url = plugin_dir_url($file);
			$this->plugin_path = plugin_dir_path($file);

		}

		function register_settings() {

			$slug = add_options_page( 'Aqua Verifier', 'Aqua Verifier', 'manage_options', 'aqua-verifier', array($this, 'view_admin_settings') );

			$this->page 	= $slug;
			$this->options 	= get_option($slug);

			register_setting($slug, $slug, array($this, 'sanitize_settings') );

			add_settings_section( $slug, '', '__return_false', $slug );

			add_settings_field(
				'marketplace_username',
				'Market Username',
				array($this, 'settings_field_input'),
				$slug,
				$slug,
				array(
					'id' 	=> 'marketplace_username',
					'desc' 	=> __('Case sensitive', 'a10e_av')
				)
			);

			add_settings_field(
				'api_key',
				'API Key',
				array($this, 'settings_field_input'),
				$slug,
				$slug,
				array(
					'id' 	=> 'api_key',
					'desc' 	=> __('More info about ', 'a10e') . '<a href="http://themeforest.net/help/api">Envato API</a>'
				)
			);

			add_settings_field(
				'custom_style',
				'Custom Styling',
				array($this, 'settings_field_textarea'),
				$slug,
				$slug,
				array(
					'id' 	=> 'custom_style',
					'desc' 	=> __('Add custom inline styling to the registration page', 'a10e_av')
				)
			);

			add_settings_field(
				'disable_username',
				'Disable Username input',
				array($this, 'settings_field_checkbox'),
				$slug,
				$slug,
				array(
					'id' 	=> 'disable_username',
					'desc' 	=> __('Disable the username field and use only the purchase code', 'a10e_av')
				)
			);

			add_settings_field(
				'display_credit',
				'Display "Powered by"',
				array($this, 'settings_field_checkbox'),
				$slug,
				$slug,
				array(
					'id' 	=> 'display_credit',
					'desc' 	=> __('Display small credit line to help others find the plugin', 'a10e_av')
				)
			);

		}

			function settings_field_input($args) {

				$slug = $this->page;
				$id = $args['id'];
				$desc = $args['desc'];
				$options = $this->options;
				$value = isset($options[$id]) ? $options[$id] : '';

				echo "<input id='$id' name='{$slug}[{$id}]' size='40' type='text' value='{$value}' />";
				echo "<p class='description'>$desc</div>";

			}

			function settings_field_textarea($args) {

				$slug = $this->page;
				$id = $args['id'];
				$desc = $args['desc'];
				$options = $this->options;

				$default = "#login {width: 500px} .success {background-color: #F0FFF8; border: 1px solid #CEEFE1;";

				if(!isset($options['custom_style'])) $options['custom_style'] = $default;
				$text = $options['custom_style'];

				echo "<textarea id='{$id}' name='{$slug}[{$id}]' rows='7' cols='50' class='large-text code'>{$text}</textarea>";
				echo "<p class='description'>$desc</div>";

			}

			function settings_field_checkbox($args) {

				$slug = $this->page;
				$id = $args['id'];
				$desc = $args['desc'];
				$options = $this->options;

				echo '<label for="'. $id .'">';
					echo '<input type="checkbox" id="'.$id.'" name="'. $slug .'['. $id .']" value="1" '. checked( $options[$id], 1, false ) .'/>';
				echo '&nbsp;' . $desc .'</label>';

			}

			/**
			 * Sanitize options
			 *
			 * @todo 	Check if author/key is valid
			 * @since 	1.0
			 */
			function sanitize_settings($args) {

				// $slug 		= $this->page;
				// $author 	= $args['marketplace_username'];
				// $api_key 	= $args['api_key'];

				// add_settings_error(
				// 	$slug,
				// 	'invalid_author',
				// 	__('That username/api-key is invalid. Please make sure that you have entered them correctly', 'a10e_av'),
				// 	'error'
				// );

				return $args;
			}

		/**
		 * Main Settings panel
		 *
		 * @since 	1.0
		 */
		function view_admin_settings() {
			?>

			<div class="wrap">

				<div id="icon-options-general" class="icon32"></div>
				<h2><?php _e( 'Aqua Verifier Settings', 'a10e_av' ); ?></h2>

				<form action="options.php" method="post">
				<?php
				$slug = $this->page;
				settings_fields($slug, $slug);
				do_settings_sections($slug);
				submit_button();
				?>
				</form>

			</div>

			<?php
		}

		/**
		 * Modifies the default registration page
		 *
		 * @since 	1.0
		 */
		function view_registration_page() {

			global $errors;
			$http_post = ('POST' == $_SERVER['REQUEST_METHOD']);

			if($http_post) {

				$action = $_POST['wp-submit'];
				$marketplace_username = isset($_POST['marketplace_username']) ? esc_attr($_POST['marketplace_username']) : '';
				$purchase_code = esc_attr($_POST['purchase_code']);
				$verify = $this->verify_purchase($marketplace_username, $purchase_code);

				if($action == 'Register') {

					if(!is_wp_error($verify)) {

						$user_login = $_POST['user_login'];
						$user_email = $_POST['user_email'];
						$errors = register_new_user($user_login, $user_email);

						if ( !is_wp_error($errors) ) {

							$user_id = $errors;

							// Change role
				            wp_update_user( array ('ID' => $user_id, 'role' => 'participant') ) ;

				            // Update user meta
				            $items = array();
				            $items[$purchase_code] = array (
				            	'name' => $verify['item_name'],
				            	'id' => $verify['item_id'],
				            	'date' => $verify['created_at'],
				            	'buyer' => $verify['buyer'],
				            	'licence' => $verify['licence'],
				            	'purchase_code' => $verify['purchase_code']
				            );

				            update_user_meta( $user_id, 'purchased_items', $items );

							$redirect_to = 'wp-login.php?checkemail=registered';
							wp_safe_redirect( $redirect_to );
							exit();

						} else {
							$this->view_registration_form($errors, $verify);
						}

					} else {
						// Force to resubmit verify form
						$this->view_verification_form($verify);
					}


				} elseif($action == 'Verify') {

					// Verified, supply the registration form
					if(!is_wp_error($verify)) {

						// Purchase Item Info
						$this->view_registration_form($errors, $verify);

					} else {

						// Force to resubmit verify form
						$this->view_verification_form($verify);

					}

				}

			} else {

				$this->view_verification_form();

			}

			$this->custom_style();

			exit();

		}

		function view_verification_form($errors = '') {

			login_header(__('Verify Purchase Form'), '<p class="message register">' . __('Verify Purchase') . '</p>', $errors); ?>

			<form name="registerform" id="registerform" action="<?php echo esc_url( site_url('wp-login.php?action=register', 'login_post') ); ?>" method="post">
				<?php if(!isset($this->options['disable_username'])) : ?>
				<p>
					<label for="marketplace_username"><?php _e('Market Username (case sensitive)') ?><br />
					<input type="text" name="marketplace_username" id="marketplace_username" class="input" size="20" tabindex="10" /></label>
				</p>
				<?php endif; ?>
				<p>
					<label for="purchase_code"><?php _e('Purchase Code') ?><br />
					<input type="text" name="purchase_code" id="purchase_code" class="input" size="20" tabindex="20" /></label>
					<p><a href="<?php echo $this->plugin_url; ?>img/find-item-purchase-code.png" target="_blank">Where can I find my item purchase code?</a></p>
				</p>
				<br class="clear" />
				<p class="submit"><input type="submit" name="wp-submit" id="wp-submit" class="button button-primary button-large" value="<?php esc_attr_e('Verify'); ?>" tabindex="100" /></p>
			</form>

			<?php
			$this->view_credit();
			login_footer('user_login');

		}


		/**
		 * Modifies the default user registration page
		 *
		 * @since 	1.0
		 */
		function view_registration_form( $errors = '', $verified = array() ) {

			login_header(__('Registration Form'), '<p class="message register">' . __('Register An Account') . '</p>', $errors);


			if($verified) {
				?>
				<div class="message success">

					<h3>Purchase Information</h3><br/>
					<ul>
					<li><strong>Buyer: </strong><?php echo $verified['buyer']; ?></li>
					<li><strong>Item: </strong><?php echo $verified['item_name']; ?></li>
					<li><strong>Purchase Code: </strong><?php echo $verified['purchase_code']; ?></li>
					</ul>

				</div>
				<?php
			}

			?>

			<form name="registerform" id="registerform" action="<?php echo esc_url( site_url('wp-login.php?action=register', 'login_post') ); ?>" method="post">

				<input type="hidden" name="marketplace_username" value="<?php echo $verified['buyer']; ?>" />
				<input type="hidden" name="purchase_code" value="<?php echo $verified['purchase_code']; ?>" />

				<p>
					<label for="user_login"><?php _e('Username') ?><br />
					<input type="text" name="user_login" id="user_login" class="input" value="" size="20" tabindex="10" /></label>
				</p>
				<p>
					<label for="user_email"><?php _e('E-mail') ?><br />
					<input type="email" name="user_email" id="user_email" class="input" value="" size="25" tabindex="20" /></label>
				</p>

				<p id="reg_passmail"><?php _e('A password will be e-mailed to you.') ?></p>
				<br class="clear" />
				<p class="submit"><input type="submit" name="wp-submit" id="wp-submit" class="button-primary" value="<?php esc_attr_e('Register'); ?>" tabindex="100" /></p>

			</form>

			<p id="nav">
			<a href="<?php echo esc_url( wp_login_url() ); ?>"><?php _e( 'Log in' ); ?></a> |
			<a href="<?php echo esc_url( wp_lostpassword_url() ); ?>" title="<?php esc_attr_e( 'Password Lost and Found' ) ?>"><?php _e( 'Lost your password?' ); ?></a>
			</p>

			<?php
			login_footer('user_login');

		}

		/**
		 * Verify purchase code and checks if
		 * code already used
		 *
		 * @since 	1.0
		 *
		 * @return 	array - purchase data
		 */
		function verify_purchase($marketplace_username = '', $purchase_code = '') {

			$errors = new WP_Error;

			$options = $this->options;

			// Check for empty fields
			if((empty($marketplace_username) && !$options['disable_username'] ) || empty($purchase_code)) {
				$errors->add('incomplete_form', '<strong>Error</strong>: Incomplete form fields.');
				return $errors;
			}

			// Gets author data & prepare verification vars
			$slug 			= $this->page;
			$options 		= get_option($slug);
			$author 		= $options['marketplace_username'];
			$api_key		= $options['api_key'];
			$purchase_code 	= urlencode($purchase_code);
			$api_url 		= $this->api .$author.'/'.$api_key.'/verify-purchase:'.$purchase_code.'.json';
			$verified 		= false;
			$result 		= '';

			// Check if purchase code already used
			global $wpdb;
			$query = $wpdb->prepare(
				"
					SELECT umeta.user_id
					FROM $wpdb->usermeta as umeta
					WHERE umeta.meta_value LIKE '%%%s%%'
				",
				$purchase_code
			);

			$registered = $wpdb->get_var($query);

			if($registered) {
				$errors->add('used_purchase_code', 'Sorry, but that item purchase code has already been registered with another account. Please login to that account to continue, or create a new account with another purchase code.');
				return $errors;
			}

			// Send request to envato to verify purchase
			$response 	= wp_remote_get($api_url);

			if( !is_wp_error($response) ) {

				$result = json_decode($response['body'], true);
				$item 	= @$result['verify-purchase']['item_name'];

				if( $item ) {

					// Check if username matches the one on marketplace
					if( strcmp( $result['verify-purchase']['buyer'] , $marketplace_username ) !== 0 && !$options['disable_username'] ) {
						$errors->add('invalid_marketplace_username', 'That username is not valid for this item purchase code. Please make sure you entered the correct username (case sensitive).' );
					} else {
						// add purchase code to $result['verify_purchase']
						$result['verify-purchase']['purchase_code'] = $purchase_code;
						$verified = true;
					}

				} else {
					// Tell user the purchase code is invalid
					$errors->add('invalid_purchase_code', 'Sorry, but that item purchase code is invalid. Please make sure you have entered the correct purchase code.');
				}

			} else {
				$errors->add('server_error', 'Something went wrong, please try again.');
			}

			if( $verified ) {
				return $result['verify-purchase'];
			} else {
				return $errors;
			}

		}

		/**
		 * Custom form stylings
		 *
		 * Adds inline stylings defined in admin options
		 * @since 	1.0
		 */
		function custom_style() {

			$options = $this->options;

			$style = isset($options['custom_style']) ? $options['custom_style'] : '';

			if(!empty($style)) {

				echo '<style>';
					echo $style;
				echo '</style>';

			}

		}

		/** Small credit line */
		function view_credit() {

			$options = $this->options;

			if(@$options['display_credit']) {
				echo '<p style="font-size:10px; text-align:right; padding-top: 10px;">Powered by <a target="_blank" href="http://aquagraphite.com/aqua-verifier/">Aqua Verifier</a></p>';
			}

		}

		/**
		 * Adds custom shaker codes
		 *
		 * Shake that sexy red booty
		 * @since 	1.0
		 */
		function shaker( $shake_error_codes ) {
			$extras = array('invalid_purchase_code', 'invalid_marketplace_username', 'server_error', 'incomplete_form', 'used_purchase_code');
			$shake_error_codes = array_merge($extras, $shake_error_codes);
			return $shake_error_codes;
		}

		/** (sugar) Modifies login header url */
		function modify_login_headerurl($login_header_url = null) {
			$login_header_url = site_url();
			return $login_header_url;
		}
	}

}

$aq_verifier = new AQ_Verifier;
$aq_verifier->init();
