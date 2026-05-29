<?php
/*
Plugin Name: Conditional Auto Update blocking
Plugin URI: https://www.damiencarbery.com/
Description: Allow for conditionally blocking the auto update of a plugin e.g. limit days one can be updated or any condition you can think of.
Author: Damien Carbery
Version: 0.1.20260529
*/


defined( 'ABSPATH' ) || exit;


class ConditionalAutoUpdateBlocking {
	private $email_content;


	// Returns an instance of this class. 
	public static function get_instance() {
		if ( null == self::$instance ) {
			self::$instance = new self;
		} 
		return self::$instance;
	}


	// Initialize the plugin variables.
	public function __construct() {
		// Initialise the email content.
		$this->email_content = array();

		$this->init();
	}


	// Set up WordPress specfic actions.
	public function init() {
		// Add the filter that will check whether a plugin should be updated.
		add_action( 'wp_maybe_auto_update', array( $this, 'add_auto_update_filter' ), 5 );
		// Send email if any updates were blocked.
		add_action( 'wp_maybe_auto_update', array( $this, 'send_info_about_blocked_updates' ), 100 );

	}


	// Add the filter that will check whether a plugin should be updated.
	public function add_auto_update_filter() {
		add_filter( 'auto_update_plugin', array( $this, 'should_update_check' ), 10, 2 );
	}


	public function should_update_check( $update, $item ) {
		//error_log( 'Checking whether to update: ' . $item->slug );
		// Note: false==is_admin() and true==wp_doing_cron(), if such checks are needed.

		return apply_filters( 'should_update_check', $update, $item );
	}


	// Allow code add to the email content, to list the reason an update was blocked.
	public function add_to_email_content( $line ) {
		if ( !empty( $line ) ) {
			$this->email_content[] = $line;
		}
	}

	// Send email if any updates were blocked.
	public function send_info_about_blocked_updates( $update_results ) {
		if ( !empty( $this->email_content ) ) {
			$to = get_site_option( 'admin_email' );
			$subject = 'Some plugin updates blocked ';
			$body = sprintf( 'Some plugin updates on your site at %s have been blocked. The plugins and reasons are listed below:%s* %s', home_url(), "\n\n", implode( "\n* ", $this->email_content ) );
			wp_mail( $to, $subject, $body );
		}
	}
}
$ConditionalAutoUpdateBlocking = new ConditionalAutoUpdateBlocking();


// Example usage of the ConditionalAutoUpdateBlocking code to prevent updates on Fridays and at weekends.
add_filter( 'should_update_check', 'dcwd_no_weekend_updates', 10, 2 );
function dcwd_no_weekend_updates( $update, $item ) {
	$day_of_week = date( 'w' );  // 1 == Monday, 7 == Sunday.

	// No updates on Friday, Saturday or Sunday.
	if ( in_array( $day_of_week, array( 5, 6, 7 ) ) ) {
		$plugin_data = get_plugin_data( WP_PLUGIN_DIR . '/' . $item->plugin );
		global $ConditionalAutoUpdateBlocking;
		$ConditionalAutoUpdateBlocking->add_to_email_content( sprintf( 'Block updating "%s" to %s as updates are only allowed Monday - Thursday.', $plugin_data['Name'], $item->new_version ) );

		return false;
	}

	return $update;
}


// Example usage of the ConditionalAutoUpdateBlocking code to prevent WooCommerce updates for .0 versions.
add_filter( 'should_update_check', 'dcwd_no_zero_version_woocommerce', 5, 2 );
function dcwd_no_zero_version_woocommerce( $update, $item ) {
	if ( 'woocommerce' == $item->slug && !empty( $item->new_version ) ) {
		$version = explode( '.', $item->new_version );

		if ( $version[ count( $version ) - 1 ] == 0 ) {
			global $ConditionalAutoUpdateBlocking;
			$ConditionalAutoUpdateBlocking->add_to_email_content( 'WooCommerce patch version is 0 so do not update. Will wait for .1 version.' );
			return false;
		}
	}

	return $update;
}
