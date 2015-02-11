<?php

namespace aqVerifier;

class Settings {
	function __construct() {
		add_action( 'admin_menu', array( $this, 'register_settings' ) );
	}


	public function register_settings() {

		$slug = add_options_page(
			'Aqua Verifier',
			'Aqua Verifier',
			'manage_options',
			'aqua-verifier',
			array( $this, 'view_admin_settings' )
		);

		$this->fields  = new Settings\Fields();
		$this->slug    = $slug;
		$this->options = get_option( $this->slug );

		register_setting( $slug, $slug, array( $this, 'sanitize_settings' ) );

		add_settings_section( $slug, '', '__return_false', $slug );

		add_settings_field(
			'marketplace_username',
			'Market Username',
			array( $this->fields, 'input' ),
			$slug,
			$slug,
			array(
				'id' 	=> 'marketplace_username',
				'desc' 	=> __('Case sensitive', 'a10e_av'),
				'slug' => $this->slug,
				'options' => $this->options
			)
		);

		add_settings_field(
			'api_key',
			'API Key',
			array( $this->fields, 'input' ),
			$slug,
			$slug,
			array(
				'id' 	=> 'api_key',
				'desc' 	=> __( 'More info about ', 'a10e' ) . '<a href="http://themeforest.net/help/api">Envato API</a>',
				'slug' => $this->slug,
				'options' => $this->options
			)
		);

		add_settings_field(
			'custom_style',
			'Custom Styling',
			array( $this->fields, 'textarea' ),
			$slug,
			$slug,
			array(
				'id' 	=> 'custom_style',
				'desc' 	=> __( 'Add custom inline styling to the registration page', 'a10e_av' ),
				'default' => "#login {width: 500px} .success {background-color: #F0FFF8; border: 1px solid #CEEFE1;",
				'slug' => $this->slug,
				'options' => $this->options
			)
		);

		add_settings_field(
			'disable_username',
			'Disable Username input',
			array( $this->fields, 'checkbox' ),
			$slug,
			$slug,
			array(
				'id' 	=> 'disable_username',
				'desc' 	=> __( 'Disable the username field and use only the purchase code', 'a10e_av' ),
				'slug' => $this->slug,
				'options' => $this->options
			)
		);

		add_settings_field(
			'display_credit',
			'Display "Powered by"',
			array( $this->fields, 'checkbox' ),
			$slug,
			$slug,
			array(
				'id' 	=> 'display_credit',
				'desc' 	=> __( 'Display small credit line to help others find the plugin', 'a10e_av' ),
				'slug' => $this->slug,
				'options' => $this->options
			)
		);

	}





	/**
	 * Sanitize options
	 *
	 * @todo 	Check if author/key is valid
	 * @since 	1.0
	 */
	function sanitize_settings($args) {

		// $slug 		= $this->slug;
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
			$slug = $this->slug;
			settings_fields($slug, $slug);
			do_settings_sections($slug);
			submit_button();
			?>
			</form>

		</div>

		<?php
	}
}