<?php

/**
 * Main scheduler class
 */
class NM_Cron {
	/**
	 * This runs only on plugin activation
	 */
	public static function schedule_events() {
		// force schedules to appear
		add_filter( 'cron_schedules', array( 'NM_Cron', 'filter_cron_schedules' ) );

		// Is the event is already scheduled?
		$timestamp = wp_next_scheduled( '90min_fetch_new_posts' );

		if ( $timestamp === false ) {
			// Schedule the event for right now, then to repeat daily using the hook '90min_fetch_new_posts'
			wp_schedule_event( time(), '90min_leap', '90min_fetch_new_posts' );
		}
	}

	/**
	 * This runs only on plugin deactivation
	 */
	public static function clear_scheduled_events() {
		wp_clear_scheduled_hook( '90min_fetch_new_posts' );
	}

	public static function filter_cron_schedules( $schedules ) {
		$schedules['90min_leap'] = array(
			/* <http://codex.wordpress.org/Transients_API#Using_Time_Constants> */
			'interval' => apply_filters( '90min_leap_interval', MINUTE_IN_SECONDS * 30 ),
			'display' => __( '90Min Leap', '90min' ),
		);

		return $schedules;
	}

	public static function init() {
		add_action( '90min_fetch_new_posts', array( __CLASS__, 'do_fetch_new_posts' ) );

		// leap interval default
		add_filter( '90min_leap_interval', array( __CLASS__, 'filter_90min_leap_interval' ) );
	}

	public static function filter_90min_leap_interval() {
		return MINUTE_IN_SECONDS * 10;
	}

	public static function do_fetch_new_posts() {
		// figure out what calls we need to make
		$filtered = NM_Util::sepearate_leagues_teams( MC_90min_Settings_Controls::get_option( 'leagues' ) );

		$categories = MC_90min_Settings_Controls::get_option( 'categories' );

		if ( !empty($categories) ) {
			$filtered['category'] = $categories;
		}

		// fetch all feeds
		if ( empty($filtered) )
			return;

		$log = array();
		$inserted_in_session = 0;

		foreach ( $filtered as $type => $list ) {
			if ( empty($list) )
				continue;

			foreach ( $list as $id ) {
				$feed_posts = NM_Dispatcher::fetch_feed( $type, $id );
				$feed_posts_count = count($feed_posts);
				$total_inserted = NM_Data_Bridge::process_feed( $feed_posts );

				$inserted_in_session += $total_inserted;

				$_feed_or_category_label = ( $type === 'category' ) ? 'Category' : 'Feed';

				$log[] = "$_feed_or_category_label ID $id fetched. Inserted: $total_inserted/$feed_posts_count ";
			}
		}

		// last updated feed time
		update_option( '90min_last_feed_fetched', array(
			'time' => time(),
			'log' => $log,
			'inserted_in_session' => $inserted_in_session,
		) );

		NM_Util::log_errors($log);
	}
}