<?php

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

class NM_Dispatcher {

	/**
	 * API's base URL
	 */
	const BASE_API = 'http://api.90min.com/api/partners/v1/feed/';

	private static $ok_codes = array( 200, 304 );

	public static function test_auth( $partner_id, $api_key = false ) {
		if ( ! ( $partner_id && $api_key ) ) {
			$partner_id = MC_90min_Settings_Controls::get_option( 'partner-id' );
			$api_key = MC_90min_Settings_Controls::get_option( 'api-key' );
		}

		$auth = array(
			'key' => $api_key,
			'test_flag' => 'yes'
		);

		// Prepare the URL that includes our credentials
		$response = wp_remote_get( self::get_method_url( 'testauth', false, $auth ), array(
			'timeout' => 10,
		) );
		
		// credentials are incorrect
		if ( ! in_array( wp_remote_retrieve_response_code( $response ), self::$ok_codes ) )
			return false;

		return json_decode( wp_remote_retrieve_body( $response ) );
	}


	public static function fetch_feed( $feed_type = 'league', $id = '' ) {
		$partner_id = MC_90min_Settings_Controls::get_option( 'partner-id' );
		$api_key = MC_90min_Settings_Controls::get_option( 'api-key' );

		$auth = array(
			'key' => $api_key,
		);

		$method_url = self::get_method_url( "feed_$feed_type", array(
			'id' => $id,
			'lang' => MC_90min_Settings_Controls::get_option( 'language' ),
		), $auth );

		// Prepare the URL that includes our credentials
		$response = wp_remote_get( $method_url, array(
			'timeout' => 10,
		) );

		// credentials are incorrect
		if ( ! in_array( wp_remote_retrieve_response_code( $response ), self::$ok_codes ) )
			return false;

		$decoded_response = json_decode( wp_remote_retrieve_body( $response ) );
		//echo "<pre>";print_r($decoded_response); exit;
		return ! empty($decoded_response->data->feed) ? $decoded_response->data->feed : false;
	}

	/**
	 * Utility function for getting a URL for various API methods
	 *
	 * @param string $method The short of the API method
	 * @param array $params Extra parameters to pass on with the request
	 * @param bool $auth Autentication array including API key and username
	 *
	 * @return string The final URL to use for the request
	 */
	public static function get_method_url( $method, $params = array(), $auth = false ) {
		$auth = $auth ? $auth : array(
			'api_key' => MC_90min_Settings_Controls::get_option( 'api-key' ),
		);

		$path = '';

		switch ( $method ) {
			case 'testauth':
				$path = add_query_arg( $auth, 'vn/league/1' );
				break;
			case 'feed_team':
				$path = add_query_arg( $auth, "{$params['lang']}/team/{$params['id']}" );
				break;
			case 'feed_league':
				$path = add_query_arg( $auth, "{$params['lang']}/league/{$params['id']}" );
				break;
			case 'feed_category':
				$path = add_query_arg( $auth, "{$params['lang']}/category/{$params['id']}" );
				break;

			case 'fields':
				$path = add_query_arg( $auth, "signups/{$params['id']}.json" );
				break;
			case 'account':
				$path = add_query_arg( $auth, 'user/account_status' );
				break;
			case 'signin':
				$path = add_query_arg( $auth, 'sessions/single_signon_token' );
				break;
			case 'signin_redirect':
				$path = add_query_arg( array(
					'token' => $params['token'],
					'partner-id' => $auth['partner-id'],
				), 'sessions/single_signon' );
				break;
		}

		return self::BASE_API . $path;
	}

	public static function is_response_ok( &$request ) {
		return (
			! is_wp_error( $request )
			&& in_array( wp_remote_retrieve_response_code( $request ), self::$ok_codes )
		);
	}
}