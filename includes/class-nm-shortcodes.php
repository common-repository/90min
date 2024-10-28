<?php

class NM_Shortcodes {
	static function register() {
		add_shortcode( '90min-post-embed', array( __CLASS__, 'post_embed' ) );
	}

	static function post_embed( $atts ) {
		// if this is not a 90min post, bail.
		if ( ! NM_Data_Bridge::is_90min_post() )
			return;

		// get original API post object
		$feed_post = get_post_meta( get_the_id(), '90min_post_original_ref', true );

		// if original feed object isn't there, quit with no content
		if ( ! is_object($feed_post) )
			return;
		
		extract( shortcode_atts( array(
			'id' => false,
		), $atts ) );

		$content = isset($feed_post->embed_code) ? $feed_post->embed_code : '';

		$attributes = array();
		$attr_settings_map = array(
			'display-post-title' => array( "data-show-title='1'", true ),
			'display-views-counter' => array( "data-show-reads-counter='1'", true ),
		);

		foreach ( $attr_settings_map as $settings_key => $attr ) {
			$attr_setting_value = nm_get_option( $settings_key );

			list( $attr_html, $attr_remove_the_attr ) = $attr;

			if ( '1' === $attr_setting_value ) {

				// now check if this is a reversed value thing
				if ( $attr_remove_the_attr )
					$content = str_replace( $attr_html, '', $content );
			}
		}

		return $content;
	}
}