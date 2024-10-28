<?php

class NM_Util {
	private static $log_dir_path = '';
	private static $log_dir_url  = '';

	public static function util_setup() {
		$upload_dir = wp_upload_dir();
		self::$log_dir_path = trailingslashit( $upload_dir['basedir'] );
		self::$log_dir_url  = trailingslashit( $upload_dir['baseurl'] );
	}

	/**
	 * Read any file
	 */
	public static function readfile( $filename ) {
		$path = NMIN_PLUGIN_DIR . "includes/data/$filename";

		if ( ! file_exists( $path ) )
			return false;

		return file_get_contents( $path );
	}

	public static function read_json( $filename ) {
		return json_decode( self::readfile($filename) );
	}

	public static function sepearate_leagues_teams( $list ) {
		if ( empty($list) || ! $list )
			return array();

		$sorted = array();

		foreach ( $list as $item ) {
			list($type, $id) = explode(':', $item);

			$sorted[ $type ][] = $id;
		}

		return $sorted;
	}

	/**
	 * Log errors to a file
	 *
	 * @since 0.2
	 **/
	public static function log_errors( $errors ) {
		if ( empty( $errors ) )
			return;

		$log = @fopen( self::$log_dir_path . '90min_errors.log', 'a' );
		@fwrite( $log, sprintf( __( 'BEGIN %s' , 'import-users-from-csv'), date( 'Y-m-d H:i:s', time() ) ) . "\n" );

		foreach ( $errors as $key => $error ) {
			$line = $key + 1;
			$message = is_wp_error( $error ) ? $error->get_error_message() : (string) $error;
			@fwrite( $log, sprintf( __( '[Line %1$s] %2$s' , 'import-users-from-csv'), $line, $message ) . "\n" );
		}

		@fclose( $log );
	}
}