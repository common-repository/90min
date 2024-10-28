<?php

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

class NM_AJAX {

	public static function register() {
		$actions = array(
			// access-slug => array( local_callback, is_private_only )
			'90min-debug' => array( 'debug', true ),
			'90min-auth' => array( 'auth', true ),
		);

		foreach ( $actions as $handle => $options ) {
			list( $local_callback, $is_private_only ) = $options;

			// should this AJAX method be publicly available?
			if ( $is_private_only ) {
				add_action( "wp_ajax_$handle", 			array( __CLASS__, $local_callback ) );
				continue;
			}

			add_action( "wp_ajax_nopriv_$handle", 	array( __CLASS__, $local_callback ) );
		}
	}

	public static function debug() {
		do_action( '90min_fetch_new_posts' );

		die;
	}

	public static function auth() {
		$p = &$_POST;

		if ( empty($p['partner_id']) || empty($p['api_key']) || ! is_user_logged_in() )
			wp_send_json_error();

		// validate nonce
		if ( ! wp_verify_nonce( $p['nonce'], '90min-ajax-auth' ) )
			wp_send_json_error( __( 'Cheating.', '90min' ) );

		$response = array();

		// check if user is valid
		$data = NM_Dispatcher::test_auth( $p['partner_id'], $p['api_key'] );

		// is successful?
		if ( isset( $data->status ) && $data->status == 'success' ) {	
			// update these settings
			MC_90min_Settings_Controls::update_option( array(
				'api-key' => $p['api_key'],
				'partner-id' => $p['partner_id'],
			) );

			MC_90min_Settings_Controls::update_option( 'is-authenticated', true );

			// let the user know they're authed
			wp_send_json_success();
		} else {
			MC_90min_Settings_Controls::update_option( 'is-authenticated', false );

			// something failed
			wp_send_json_error();
		}

		die;
	}
}