<?php

// if uninstall not called from WordPress exit
if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) )
	die;

// delete option
delete_option( 'nmin-settings' );