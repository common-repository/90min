<?php

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

class MC_90min_Settings {

	public $slug;
	private $hook;
	private $nm;

	public function __construct() {
		$this->nm = init_90min(); // main 90min instance

		add_action( 'admin_menu', array( $this, 'action_admin_menu' ) );
		add_action( 'admin_init', array( $this, 'register_settings' ) );
	}

	/**
	 * Register the settings page
	 *
	 * @since 1.0
	 */
	public function action_admin_menu() {
		$this->hook = add_options_page(
			__( '90min Settings', '90min' ), 	// <title> tag
			__( '90min Settings', '90min' ), 			// menu label
			'manage_options', 								// required cap to view this page
			$this->slug = NMIN_SETTINGS_PAGE_SLUG, 			// page slug
			array( &$this, 'display_settings_page' )			// callback
		);

		add_action( "load-$this->hook", array( $this, 'page_load' ) );
	}

	public function page_load() {
		// main switch for some various maintenance processes
		if ( isset( $_GET['action'] ) ) {
			$settings = get_option( $this->slug );

			switch ( $_GET['action'] ) {
				case 'debug-run-cron':
					// run the main WP_Cron job
					do_action( '90min_fetch_new_posts' );

					// message
					add_settings_error( $this->slug, '90min-debug-cron', __( 'Debug: Posts were fetched and inserted (if any).', '90min' ), 'updated' );
					break;
			}
		}

		// set up the help tabs
		add_action( 'in_admin_header', array( $this, 'setup_help_tabs' ) );

		// enqueue the CSS for the admin
		wp_enqueue_style( 'nm-admin', plugins_url( 'css/admin.css', NMIN_PLUGIN_BASE ) );

		// admin scripts
		wp_enqueue_script( 'nm-adminjs', plugins_url( 'js/90min-admin.js', NMIN_PLUGIN_BASE ), array( 'jquery', 'underscore' ), false, true );

		// localize script, print that JSON file
		wp_localize_script( 'nm-adminjs', 'NM', array(
			'leagues' =>  array(
				'all' => NM_Util::read_json( 'teams_leagues_categories.json' ),
				'saved' => MC_90min_Settings_Controls::get_option( 'leagues' ),
			),
			'savedCategories' => MC_90min_Settings_Controls::get_option( 'categories' ),
			'strings' => array(
				'auth' => array(
					'success' => __( 'Success! Your account details are valid.', '90min' ),
					'failure' => __( 'The provided account details are not valid, please review them or reach our administrator for assistance.', '90min' ),
				),
				'leagueFeed' => __( 'League Feed', '90min' ),
			)
		) );
	}

	public function setup_help_tabs() {
		$screen = get_current_screen();

		$screen->add_help_tab( array(
			'title' => __( 'Overview', '90min' ),
			'id' => '90min-overview',
			'content' => __( '
				<h3>Instructions</h3>
				<p>Once the plugin is activated, you will be able to select your desired content type and more.</p>
				', '90min' )
		) );

		/*
		$screen->add_help_tab( array(
			'title' => __( 'Additional Help', '90min' ),
			'id' => '90min-additionalhelp',
			'content' => __( '
				<h3>More Help</h3>
				<p>OpenTracker runs several times faster than older tracker implementations and requires less memory. (For example, it runs fine with the limited resources of many embedded systems.) Several instances of the software may be run in a cluster, with all of them synchronizing with each other. Besides the Hypertext Transfer Protocol (HTTP) opentracker may also be connected to via User Datagram Protocol (UDP), which creates less than half of the tracker traffic HTTP creates.[1] It supports IPv6, gzip compression of full scrapes, and blacklists of torrents. Because there have already been cases of people being accused of copyright violation by the fact that their IP address was listed on a BitTorrent tracker,[2] opentracker may mix in random IP address numbers for the purpose of plausible deniability.</p>
				<p>Amazing! screen provides access to the tickets (or ticket types) you have created. Each ticket is has various attributes like price and quantity. The total amount of available tickets determines the maximum capacity of the event. Please note that once the ticket has been published, editing things like price or questions can break data consistency, since attendees may have already bought the ticket with the old data. Also, once a ticket has been published, please keep it published. Do not revert to draft, pending or trash.</p>
				<p>Use the <strong>Screen Options</strong> panel to show and hide the columns that matter most.</p>', '90min' ),
		) );
		*/

		$screen->set_help_sidebar( __( '
			<p><strong>For more information:</strong></p>
			<p><a href="http://www.90min.com/" target="_blank">90min.com</a></p>
			<p><a href="http://www.90min.com/contact" target="_blank">Contact 90min</a></p>
			<p><a href="http://www.90min.com/blog/" target="_blank">90min Blog</a></p>
		', '90min' ) );
	}

	private function add_settings_field( $section, $key, $field_type, $label, $description = '', $extra_args = array() ) {
		$args = wp_parse_args( $extra_args, array(
			'id' => $key,
			'page' => $this->slug,
			'description' => $description,
		) );

		return add_settings_field(
			$key,
			$label,
			array( 'MC_90min_Settings_Controls', $field_type ),
			$this->slug,
			$section,
			$args
		);
	}

	public function get_supported_languages() {
		if ( ! empty( $this->supported_languages ) )
			return $this->supported_languages;

		$decoded = NM_Util::read_json('supported_languages.json');

		if ( !$decoded )
			return array();

		return $this->supported_languages = $decoded;
	}

	public function get_supported_languages_iso() {
		$langs = array();

		foreach ( (array) $this->get_supported_languages() as $language_code => $language ) {
			$langs[ $language_code ] = $language;
		}

		return $langs;
	}

	public function register_settings() {
		global $pagenow;

		// If no options exist, create them.
		if ( ! get_option( $this->slug ) ) {
			update_option( $this->slug, apply_filters( '90min_default_options', array(
				'partner-id' => '',
				'api-key' => '',
				'post-type' => 'post',
				'tagging-tax' => 'category',
				'post-status' => 'publish',
				'language' => 'en',
				'first-save-made' => false,
				'is-authenticated' => false,
				'update-featured-image' => true,
				'show-author-name' => true,
				'index-nm-post'=>false,
			) ) );
		}

		register_setting( '90min-options', $this->slug, array( $this, 'validate' ) );

		// First, we register a section. This is necessary since all future options must belong to a 
		add_settings_section(
			'general_settings_section',
			__( 'Account Details', '90min' ),
			array( 'MC_90min_Settings_Controls', 'description' ),
			$this->slug
		);

		$this->add_settings_field(
			'general_settings_section',
			'partner-id',
			'text',
			__( 'Partner ID', '90min' )
		);

		$this->add_settings_field(
			'general_settings_section',
			'api-key',
			'text',
			__( 'API Key', '90min' )/*,
			sprintf( '<a target="_blank" href="%s">%s</a>', '#', _x( 'Where can I find my API key?', 'settings page', '90min' )  )*/
		);


		$this->add_settings_field(
			'general_settings_section',
			'auth-button-area',
			'auth_button',
			''
		);

		$this->add_settings_field(
			'general_settings_section',
			'ajax-auth-nonce',
			'hidden',
			'',
			'',
			array(
				'value' => wp_create_nonce( '90min-ajax-auth' )
			)
		);


		// Post setting section

		add_settings_section(
			'post_settings_section',
			__( 'Post Settings', '90min' ),
			array( 'MC_90min_Settings_Controls', 'description' ),
			$this->slug
		);



		$this->add_settings_field(
			'post_settings_section',
			'display-post-title',
			'checkbox',
			__( 'Post Title', '90min' ),
			'',
			array(
				'label' => __( 'Do not show title in post body', '90min' )
			)
		);

		$this->add_settings_field(
			'post_settings_section',
			'display-views-counter',
			'checkbox',
			__( 'Views Counter', '90min' ),
			'',
			array(
				'label' => __( 'Hide 90min number of views counter', '90min' )
			)
		);

		$this->add_settings_field(
			'post_settings_section',
			'update-featured-image',
			'checkbox',
			__( 'Featured Image', '90min' ),
			'',
			array(
				'label' => __( 'Set the article’s main image as your ‘Featured Image’.', '90min' )
			)
		);
		
		$_post_types_found = get_post_types( array(
			'public' => true,
			'show_ui' => true,
		) );

		// clear "attachment" post type
		if ( isset($_post_types_found['attachment']) )
			unset( $_post_types_found['attachment'] );

		$this->add_settings_field(
			'post_settings_section',
			'post-type',
			'select',
			__( 'Post Type', '90min' ),
			'',
			array(
				'options' => apply_filters( '90min_supported_post_types', $_post_types_found ) // post types
			)
		);

		$settings_saved_post_type = MC_90min_Settings_Controls::get_option( 'post-type' ); // "post" is default
		$selected_post_type = apply_filters( '90min_post_type', $settings_saved_post_type ? $settings_saved_post_type : 'post' );
		$all_post_statuses = get_available_post_statuses( $selected_post_type );


		$this->add_settings_field(
			'post_settings_section',
			'post-status',
			'select',
			__( 'Post Status', '90min' ),
			'',
			array(
				'options' => array_combine( $all_post_statuses, $all_post_statuses )
			)
		);

		// tagging taxonomy
		$supported_taxos = get_object_taxonomies( $selected_post_type, 'objects' );
		$supported_taxos_list = array();

		foreach ( $supported_taxos as $supported_tax_key => $supported_tax ) {
			// if this is post_format, ignore
			if ( 'post_format' == $supported_tax_key )
				continue;
			
			$supported_taxos_list[ $supported_tax_key ] = $supported_tax->labels->singular_name;
		}

		if ( ! empty($supported_taxos_list) ) {
			$this->add_settings_field(
				'post_settings_section',
				'tagging-tax',
				'select',
				__( 'Tagging Taxonomy', '90min' ),
				'Set 90min\'s categories as ',
				array(
					'options' => $supported_taxos_list,
					'class'=>'tagging-taxonomy'
				)
			);
		}
		/*Specific Tags*/
		$this->add_settings_field(
			'post_settings_section',
			'specific-tags',
			'checkbox',
			__( 'Tags', '90min' ),
			'',
			array(
				'label' => __( "Provide 90min's post specific tags", '90min' )
			)
		);
		/*Show Autho*/
		$this->add_settings_field(
			'post_settings_section',
			'show-author-name',
			'checkbox',
			__( 'Author', '90min' ),
			'',
			array(
				'label' => __( "Show author's name within post body", '90min' )
			)
		);
		$authors = get_users();
		$authors_option = array();
		foreach ( $authors as $author ) {
			$authors_option[ $author->ID ] = $author->display_name;
		}
		
		$this->add_settings_field(
			'post_settings_section',
			'post-author',
			'select',
			__( 'Post Author', '90min' ),
			__( 'The posts will be attributed to this author', '90min' ),
			array(
				'options' => $authors_option,
			)
		);
		
		/*Show Index 90min post option*/
		$this->add_settings_field(
			'post_settings_section',
			'index-nm-post',
			'checkbox',
			__( 'SEO', '90min' ),
			'',
			array(
				'label' => __( "Don't index posts created by the 90min plugin", '90min' )
			)
		);

		/*$this->add_settings_field(
			'post_settings_section',
			'hide-author-name',
			'checkbox',
			__( 'Author', '90min' ),
			'',
			array(
				'label' => __( 'Hide author name?', '90min' )
			)
		);*/

		// Feed Selection section

		add_settings_section(
			'feed_selection_settings_section',
			__( 'Feed Selection', '90min' ),
			array( 'MC_90min_Settings_Controls', 'description' ),
			$this->slug
		);

		// $supported_languages = $this->get_supported_languages();
		$supported_languages = $this->get_supported_languages_iso();

		$this->add_settings_field(
			'feed_selection_settings_section',
			'language',
			'select',
			__( 'Language', '90min' ),
			'',
			array(
				'options' => $supported_languages
			)
		);

		$this->add_settings_field(
			'feed_selection_settings_section',
			'leagues',
			'select_special',
			__( 'Leagues / Teams', '90min' ),
			'',
			array(
				'options' => array('a' => 'b'),
				'multi' => true
			)
		);

		$this->add_settings_field(
			'feed_selection_settings_section',
			'categories',
			'checkbox_group',
			__( 'Categories', '90min' ),
			'',
			array(
				'options' => array( 'a' => 'b'),
				'multi' => true,
			)
		);
		


		// don't register this setting on all pages
		/* if ( 'options-general.php' == $pagenow && $user_info && isset( $user_info->type ) && 'free' != $user_info->type ) {} */

		do_action( '90min_setup_settings_fields' );
	}

	public function display_settings_page() {
		?>
		<!-- Create a header in the default WordPress 'wrap' container -->
		<div class="wrap">

			<?php screen_icon(); ?>
			<h2><?php _e( '90min Settings', '90min' ); ?></h2>

			<div class="nmin-header">
				<div class="nmin-logo">
					<div class="nmin-the-logo"></div>
				</div>
				<div class="nmin-content">
					<p>90min plugin allows you to automatically integrated 90min content into WordPress posts and pages</p>
					<p><?php printf( __( 'For more information about our partnership programs visit us at %s or contact us at %s.', '90min' ), '<a href="http://90min.com/partners" target="_blank">90min.com/partners</a>', '<a target="_blank" href="mailto:wpsupport@90min.com">WPsupport@90min.com</a>' ); ?></p>
				</div>
			</div>
            <br />
            <br />
			<h3 class="nav-tab-wrapper" id="nm-tabs">
		<a class="nav-tab <?=($_REQUEST["settings-updated"]!=""?"":"nav-tab-active")?>" id="nm-started-tab" href="#top#nm-started"><?php _e( 'Get Started', 'nm' ); ?></a>
		<a class="nav-tab <?=($_REQUEST["settings-updated"]!=""?"nav-tab-active":"")?>" id="nm-setting-tab" href="#top#nm-setting"><?php echo ( 'company' === $options['company_or_person'] ) ? __( 'Company Info', 'wordpress-seo' ) : __( 'Account Details', 'nm' ); ?></a>
           </h3>
           <div id="nm-started" class="<?=($_REQUEST["settings-updated"]!=""?"":"active")?> nmtab">
           <? include_once(NMIN_PLUGIN_DIR."get-started.php")?>
           </div>
           <div id="nm-setting" class="<?=($_REQUEST["settings-updated"]!=""?"active":"")?> nmtab">
			<form method="post" action="options.php" id="nm-settings-form" class="clear <?php echo MC_90min_Settings_Controls::get_option( 'is-authenticated' ) ? 'nm-is-authed' : 'nm-not-authed'; ?>">
				<?php
				settings_fields( '90min-options' );
				do_settings_sections( $this->slug );
				submit_button( _x( 'Save Settings', 'save settings button', '90min' ) );
				?>

				<?php if ( $this->nm->debug ) : ?>

				<h3><?php _e( 'Debug', '90min' ); ?></h3>
				<p><?php _e( 'Various tools for debugging.', '90min' ); ?></p>
				<p>
					<a href="<?php echo add_query_arg( 'action', 'debug-run-cron' ); ?>" class="button-secondary"><?php _e( 'Fetch New Posts', '90min' ); ?></a>
				</p>

				<?php $debug_last_feed_fetch = get_option( '90min_last_feed_fetched' ); ?>

				<h4>Debug Information</h4>
				<ul class="nm-debug-info">
					<li>Posts are fetched every <strong><?php echo apply_filters( '90min_leap_interval', 0 ) / 60; ?></strong> minutes.</li>
					<li>Last fetch was <strong><?php echo isset($debug_last_feed_fetch['time']) ? human_time_diff( $debug_last_feed_fetch['time'] ) : '?'; ?></strong> ago.
					&mdash; <strong><?php echo isset($debug_last_feed_fetch['inserted_in_session']) ? $debug_last_feed_fetch['inserted_in_session'] : 0; ?></strong> posts inserted in that session.
					</li>
				</ul>

				<?php if ( !empty($debug_last_feed_fetch) ) : ?>
				<details>
					<summary>Stats on Last Feed Fetch (<?php echo human_time_diff( $debug_last_feed_fetch['time'] ); ?> ago)</summary>
					<pre class="nm-code"><?php print_r( $debug_last_feed_fetch['log'] ); ?></pre>
				</details>
				<?php endif; ?>

				<h4>Option <code><?php echo NMIN_SETTINGS_PAGE_SLUG; ?></code></h4>
				<pre class="nm-code"><?php print_r( get_option( NMIN_SETTINGS_PAGE_SLUG ) ); ?></pre>

				<?php endif; ?>

			</form>
            </div>

		</div><!-- /.wrap -->
		<?php
	}

	public function validate( $input ) {
		// validate creds against the API
		if ( ! ( empty( $input['partner-id'] ) || empty( $input['api-key'] ) ) ) {
			$data = empty($this->dispatcher_auth_test_result) ? NM_Dispatcher::test_auth( $input['partner-id'], $input['api-key'] ) : false;

			if ( isset($data->error) ) {
				$this->dispatcher_auth_test_result = false;

				// credentials are incorrect
				add_settings_error( $this->slug, 'invalid-creds', __( 'The credentials are incorrect! Please verify that you have entered them correctly.', '90min' ) );

				return $input; // bail

			} elseif ( isset( $data->status ) && $data->status == 'success' ) {
				$this->dispatcher_auth_test_result = true;

				// test the returned data, and let the user know she's alright!
				add_settings_error( $this->slug, 'valid-creds', __( 'Connection with 90min has been established! You\'re all set!', '90min' ), 'updated' );

				// authenticated? yes.
				$input['is-authenticated'] = true;

				// great. if this is the first save, also fetch posts now.
				if ( ! MC_90min_Settings_Controls::get_option( 'first-save-made' ) ) {
					// run the cron job
					do_action( '90min_fetch_new_posts' );

					// update setting
					$input['first-save-made'] = true;
				}
			}

		} else {
			$this->dispatcher_auth_test_result = false;

			$input['is-authenticated'] = false;

			// empty
			add_settings_error( $this->slug, 'invalid-creds', __( 'Please fill in the Partner ID and the API key first.', '90min' ) );
		}

		return $input;
	}
}


final class MC_90min_Settings_Controls {

	public static function auth_button( $args ) {
		?>
		<button type="submit" class="nm-authentication button-secondary"><?php _e( 'Authenticate', '90min' ); ?></button>
		<span class="spinner nm-spinner"></span>
		<span class="nm-authentication-results">
			<span class="nm-success"><span class="dashicons dashicons-yes"></span> <?php _e( 'Success! Your account details are valid', '90min' ); ?></span>
			<span class="nm-failure"><span class="dashicons dashicons-no"></span> <?php _e( 'The provided account details are not valid, please review them or reach our administrator for assistance.', '90min' ); ?></span>
		</span>
		<?php
	}

	public static function description($args) {
		$sections = array(
			'general_settings_section' => sprintf( __('Enter your 90min credentials in order to set up the plugin. Credentials are provided upon request, contact us at %s to receive access.', '90min'), '<a href="mailto:wpsupport@90min">WPsupport@90min</a>' ),
			'post_settings_section' => __('Customize 90min content in accordance to your editorial needs. Changes to the post layout will only take effect on new posts.', '90min'),
			'feed_selection_settings_section' => __('Select your desired content.', '90min'),
		);

		if ( array_key_exists($args['id'], $sections) ) :
		?>
		<p><?php echo $sections[ $args['id'] ]; ?></p>
		<?php
		endif;
	}

	public static function select( $args ) {
		extract( $args, EXTR_SKIP );

		if ( empty( $options ) || empty( $id ) || empty( $page ) )
			return;

		$is_multi = isset($multi) && $multi;

		?>
		<select id="<?php echo esc_attr( $id ); ?>" class="<?php echo sanitize_html_class( "$page-$id" ); ?>" name="<?php printf( '%s[%s]', esc_attr( $page ), esc_attr( $id ) ); ?><?php echo $is_multi ? '[]' : ''; ?>" <?php echo $is_multi ? 'multiple="multiple"' : ''; ?>>
			<?php foreach ( $options as $name => $label ) : ?>
			<option value="<?php echo esc_attr( $name ); ?>" <?php selected( $name, (string) self::get_option( $id ) ); ?>>
				<?php echo esc_html( $label ); ?>
			</option>
			<?php endforeach; ?>
		</select>
		<?php
		self::show_description( $args );
	}

	public static function select_special( $args ) {
		extract( $args, EXTR_SKIP );

		if ( empty( $options ) || empty( $id ) || empty( $page ) )
			return;

		$is_multi = isset($multi) && $multi;

		?>
		<div class="select-special <?php echo sanitize_html_class( "$page-$id" ); ?>" data-item-name="<?php printf( '%s[%s]', esc_attr( $page ), esc_attr( $id ) ); ?><?php echo $is_multi ? '[]' : ''; ?>">
			<?php foreach ( $options as $name => $label ) : ?>
			<div class="single-option">
				<label>
					<input type="checkbox" name="<?php printf( '%s[%s]', esc_attr( $page ), esc_attr( $id ) ); ?><?php echo $is_multi ? '[]' : ''; ?>">
					<?php echo esc_html( $label ); ?>
				</label>
			</div>
			<?php endforeach; ?>
		</div>
		<?php
		self::show_description( $args );
	}



	public static function text( $args ) {
		extract( $args, EXTR_SKIP );

		if ( empty( $id ) || empty( $page ) )
			return;

		$name = esc_attr( sprintf( '%s[%s]', esc_attr( $page ), esc_attr( $id ) ) );
		$value = esc_attr( self::get_option( $id ) );

		?>
		<input type="text" name="<?php echo $name; ?>" value="<?php echo $value; ?>" class="regular-text code <?php echo sanitize_html_class( "$page-$id" ); ?>" />
		<?php
		self::show_description( $args );
	}

	public static function hidden( $args ) {
		extract( $args, EXTR_SKIP );

		if ( empty( $id ) || empty( $page ) )
			return;

		$name = esc_attr( sprintf( '%s[%s]', esc_attr( $page ), esc_attr( $id ) ) );

		?>
		<input type="hidden" name="<?php echo $name; ?>" value="<?php echo $value; ?>" class="regular-text code <?php echo sanitize_html_class( "$page-$id" ); ?>" />
		<?php
		self::show_description( $args );
	}



	public static function checkbox( $args ) {
		extract( $args, EXTR_SKIP );

		if ( empty( $id ) || empty( $page ) )
			return;

		$name = esc_attr( sprintf( '%s[%s]', esc_attr( $page ), esc_attr( $id ) ) );
		$value = esc_attr( self::get_option( $id ) );
		$label = esc_html( isset( $label ) ? $label : '' );
		?>
		<label for="<?php echo $name; ?>">
			<input type="checkbox" name="<?php echo $name; ?>" id="<?php echo $name; ?>" value="1" <?php checked( $value ); ?> />
			<?php echo $label; ?>
		</label>
		<?php
		self::show_description( $args );
	}

	public static function checkbox_group( $args ) {
		extract( $args, EXTR_SKIP );

		if ( empty( $options ) || empty( $id ) || empty( $page ) )
			return;

		$is_multi = isset($multi) && $multi;
		$html_name = sprintf( '%s[%s]', esc_attr( $page ), esc_attr( $id ) );
		$selected_val = $is_multi ? self::get_option( $id ) : (string) self::get_option( $id );
		?>

		<div class="nm-checkbox-group <?php echo sanitize_html_class( "$page-$id" ); ?>" data-name="<?php echo $html_name; ?>">			
			<?php foreach ( $options as $name => $label ) : ?>
			<label for="<?php echo $name; ?>">
				<input type="checkbox" name="<?php echo $html_name; ?>[]" value="1" <?php checked( $is_multi ? in_array($name, $selected_val) : $name == $selected_val ); ?> />
				<?php echo $label; ?>
			</label>
			<?php endforeach; ?>
		</div>

		<?php
		self::show_description( $args );
	}

	public static function show_description( $field_args ) {
		if ( isset( $field_args['description'] ) ) : ?>
			<p class="description"><?php echo $field_args['description']; ?></p>
		<?php endif;
	}

	public static function get_option( $key = '', $default = false ) {
		$settings = get_option( NMIN_SETTINGS_PAGE_SLUG );
		return apply_filters( '90min_option', ( ! empty( $settings[ $key ] ) ) ? $settings[ $key ] : $default, $key );
	}

	public static function update_option( $key, $value = '' ) {
		$settings = get_option( NMIN_SETTINGS_PAGE_SLUG );

		if ( is_string($key) ) {
			$settings[ $key ] = $value;
		} elseif ( is_array($key) ) {
			$settings = array_merge( $settings, $key );
		}

		return update_option( NMIN_SETTINGS_PAGE_SLUG, $settings );
	}

}

function nm_get_option( $key, $default = false ) {
	return MC_90min_Settings_Controls::get_option( $key, $default );
}
