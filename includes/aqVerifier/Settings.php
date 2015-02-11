<?php

namespace aqVerifier;

class Settings {
	function __construct( $fields ) {
		$this->fields  = $fields;
		add_action( 'admin_menu', array( $this, 'register_settings' ) );
	}


	public function register_settings() {
		$this->slug = add_options_page(
			'Aqua Verifier',
			'Aqua Verifier',
			'manage_options',
			'aqua-verifier',
			array( $this, 'form' )
		);

		$this->options = get_option( $this->slug );

		register_setting( $this->slug, $this->slug, array( $this, 'sanitize_settings' ) );

		add_settings_section( $this->slug, '', '__return_false', $this->slug );

		add_settings_field(
			'marketplace_username',
			'Envato Market Username',
			array( $this->fields, 'input' ),
			$this->slug,
			$this->slug,
			array(
				'id' 	=> 'marketplace_username',
				'desc' 	=> __('Case sensitive', 'a10e_av'),
				'slug' => $this->slug,
				'options' => $this->options
			)
		);

		add_settings_field(
			'api_key',
			'Envato API Key',
			array( $this->fields, 'input' ),
			$this->slug,
			$this->slug,
			array(
				'id' 	=> 'api_key',
				'desc' 	=> __( 'More info about ', 'a10e' ) . '<a href="http://themeforest.net/help/api" target="_blank">Envato API</a>',
				'slug' => $this->slug,
				'options' => $this->options
			)
		);

		add_settings_field(
			'custom_style',
			'Custom Styling',
			array( $this->fields, 'textarea' ),
			$this->slug,
			$this->slug,
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
			$this->slug,
			$this->slug,
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
			$this->slug,
			$this->slug,
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
	function sanitize_settings( $fields ) {

		foreach( $fields as $field => $value ){
			$fields[ $field ] = trim( $value  );
		}

		// add_settings_error(
		// 	$this->slug,
		// 	'invalid_author',
		// 	__('That username/api-key is invalid. Please make sure that you have entered them correctly', 'a10e_av'),
		// 	'error'
		// );

		return $fields;
	}



	/**
	 * Main Settings panel
	 *
	 * @since 	1.0
	 */
	function form() {
		?>
		<div class="wrap">

			<div id="icon-options-general" class="icon32"></div>
			<h2><?php _e( 'Aqua Verifier Settings', 'a10e_av' ); ?></h2>

			<form action="options.php" method="post">
			<?php
				settings_fields( $this->slug, $this->slug );
				do_settings_sections( $this->slug );
				submit_button();
			?>
			</form>
		</div>
		<?php
	}
}