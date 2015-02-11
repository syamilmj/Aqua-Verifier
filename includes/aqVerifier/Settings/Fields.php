<?php

namespace aqVerifier\Settings;

class Fields {
	function __construct() {
	}


	protected function parse_args( $args ){
		$id = $args['id'];

		$args['type']    = !empty( $args['type'] ) ? $args['type'] : 'text';
		$args['value']   = $args['options'][$id];

		if( !empty( $args['default'] ) && is_null( $args['value'] ) ){
			$args['value'] = $args['default'];
		}

		return $args;
	}


	protected function get_description( $args ){
		if( !empty( $args['desc'] ) ){
			return sprintf( '<p class="description">%s</p>', $args['desc'] );
		}
	}


	function input( $args ) {
		$args = $this->parse_args( $args );

		$field = sprintf( '<input id="%1$s" name="%2$s[%1$s]" type="%3$s" value="%4$s" class="widefat" >',
			$args['id'],
			$args['slug'],
			$args['type'],
			esc_attr( $args['value'] )
		);

		$field .= $this->get_description( $args );

		echo $field;
	}

	function textarea( $args ) {
		$args = $this->parse_args( $args );
		$field = sprintf( '<textarea id="%1$s" name="%2$s[%1$s]" class="large-text code">%3$s</textarea>',
			$args['id'],
			$args['slug'],
			$args['value']
		);

		$field .= $this->get_description( $args );

		echo $field;
	}

	function checkbox( $args ) {
		$args = $this->parse_args( $args );
		$field = sprintf( '<label for="%1$s"><input type="checkbox" id="%1$s" name="%2$s[%1$s]" value="1" %3$s> %4$s</label>',
			$args['id'],
			$args['slug'],
			checked( $args['value'], 1, false ),
			$args['desc']
		);

		echo $field;
	}

}