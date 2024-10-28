<?php
/*
Plugin Name: 90Min.com
Plugin URI: http://wordpress.org/extend/plugins/90min/
Description: The 90min plugin enables WordPress users to automatically integrate 90min’s syndicated content library directly into your posts and pages. 
Author: 90Min, LLC
Version: 3.1
Author URI: http://90min.com/
License: GPLv2 or later

This program is free software; you can redistribute it and/or
modify it under the terms of the GNU General Public License
as published by the Free Software Foundation; either version 2
of the License, or (at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with this program; if not, write to the Free Software
Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA  02110-1301, USA.
*/

class MC_90Min {
	private static $instance;
	private static $basename;

	public $settings;
	public $debug;

	public static function instance() {
		if ( ! isset( self::$instance ) ) {
			self::$instance = new self;
			self::$instance->setup_constants();
			self::$instance->requirements();
			self::$instance->setup_actions();
		}
		return self::$instance;
	}

	private function setup_actions() {
		add_action( 'init', 		array( $this, 'init' ) );
		add_action( 'admin_notices', array( $this, 'action_admin_notices' ) );
		add_filter( 'plugin_action_links_' . self::$basename, array( $this, 'action_links' ), 10 );
		add_filter( 'cron_schedules', array( 'NM_Cron', 'filter_cron_schedules' ) );
		add_action( 'init', 		array( 'NM_Shortcodes', 'register'	), 20 );
	}

	private function setup_constants() {
		// Plugin's main directory
		defined( 'NMIN_PLUGIN_DIR' )
			or define( 'NMIN_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );

		// Absolute URL to plugin's dir
		defined( 'NMIN_PLUGIN_URL' )
			or define( 'NMIN_PLUGIN_URL', plugin_dir_url( __FILE__ ) );

		// Absolute URL to plugin's dir
		defined( 'NMIN_PLUGIN_BASE' )
			or define( 'NMIN_PLUGIN_BASE', plugin_basename( __FILE__ ) );

		// Plugin's main directory
		defined( 'NMIN_VERSION' )
			or define( 'NMIN_VERSION', '1.1' );

		// Plugin's main directory
		defined( 'NMIN_SETTINGS_PAGE_SLUG' )
			or define( 'NMIN_SETTINGS_PAGE_SLUG', 'nmin-settings' );



		// Set up the base name
		isset( self::$basename ) || self::$basename = plugin_basename( __FILE__ );
	}

	// @todo include only some on is_admin()
	private function requirements() {
		require_once NMIN_PLUGIN_DIR . 'includes/class-dispatcher.php';
		// settings page, creds validation
		require_once NMIN_PLUGIN_DIR . 'includes/settings.php';
		// AJAX
		require_once NMIN_PLUGIN_DIR . 'includes/class-ajax.php';
		// Utilities
		require_once NMIN_PLUGIN_DIR . 'includes/class-nm-util.php';
		// Data bridge
		require_once NMIN_PLUGIN_DIR . 'includes/class-nm-data-bridge.php';
		// Cron handler
		require_once NMIN_PLUGIN_DIR . 'includes/class-nm-cron.php';
		// Shortcode handler
		require_once NMIN_PLUGIN_DIR . 'includes/class-nm-shortcodes.php';
		// Content display settings
		require_once NMIN_PLUGIN_DIR . 'includes/class-nm-content-display.php';
	}

	public function init() {
		// enable debug mode?
		$this->debug = (bool) apply_filters( '90min_debug', false );

		// initialize settings
		if ( is_admin() )
			$this->settings = new MC_90min_Settings;

		// enqueue scripts n styles
		// @todo not on admin
		add_action( 'wp_enqueue_scripts', array( $this, 'enqueue' ) );

		// init cron
		NM_Cron::init();

		// register AJAX actions
		NM_AJAX::register();

		// setup utilities
		NM_Util::util_setup();

		// setup content display
		NM_Content_Display::setup();

		// Load our textdomain to allow multilingual translations
		load_plugin_textdomain( '90min', false, dirname( self::$basename ) . '/languages/' );
	}

	public function enqueue() {}

	public function action_links( $actions ) {
		return array_merge(
			array( 
				'settings' => sprintf( '<a href="%s">%s</a>', menu_page_url( NMIN_SETTINGS_PAGE_SLUG, false ), __( 'Settings' ) ) 
			),
			$actions
		);
	}

	static function activate() {
		// make sure cron file is included
		require_once 'includes/class-nm-cron.php';

		NM_Cron::schedule_events();
	}

	static function deactivate() {
		// make sure cron file is included
		require_once 'includes/class-nm-cron.php';

		delete_option( '90min-version' );

		NM_Cron::clear_scheduled_events();
	}

	public function action_admin_notices() {
		$screen = get_current_screen();

		if ( 'plugins' != $screen->id )
			return;

		$version = get_option( '90min-version' );

		if ( ! $version ) {
			update_option( '90min-version', NMIN_VERSION );
			?>
			<div class="updated fade">
				<p>
					<strong><?php _e( '90min is almost ready.', '90min' ); ?></strong> <?php _e( 'You must enter your 90min Partner ID &amp; API key for it to work.', '90min' ); ?> &nbsp;
					<a class="button" href="<?php menu_page_url( NMIN_SETTINGS_PAGE_SLUG ); ?>"><?php _e( 'Let\'s do it!', '90min' ); ?></a>
				</p>
			</div>
			<?php
		}
	}
}

register_activation_hook( __FILE__, array( 'MC_90Min', 'activate' ) );
register_deactivation_hook( __FILE__, array( 'MC_90Min', 'deactivate' ) );

function init_90min() {
	return MC_90Min::instance();
}
add_action( 'plugins_loaded', 'init_90min' );


