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
		//add_action( 'automatic_updates_complete', array( $this, 'send_info_about_blocked_updates' ) );
		add_action( 'wp_maybe_auto_update', array( $this, 'send_info_about_blocked_updates' ), 100 );

	}


	// Add the filter that will check whether a plugin should be updated.
	public function add_auto_update_filter() {
		add_filter( 'auto_update_plugin', array( $this, 'should_update_check' ), 10, 2 );
	}


	function should_update_check( $update, $item ) {
		//error_log( 'should_update_check: current_filter: ' . var_export( current_filter(), true ) );
		//error_log( 'should_update_check: is_admin() :' .var_export( is_admin(), true ) );
		//error_log( 'should_update_check: wp_doing_cron(): ' .var_export( wp_doing_cron(), true ) );
		//error_log( 'should_update_check: $item: ' . var_export( $item, true ) );
		error_log( 'Checking whether to update: ' . $item->slug );
		// Note: is_admin()==false and wp_doing_cron()==true, if such checks are needed.

		if ( 'woocommerce' == $item->slug && !empty( $item->new_version ) ) {
			//error_log( var_export( $item, true ) );
			//if ( $item->new_version != $item->Version ) {  // It seems that ->Version is not available when run in wp_cron.
				$version = explode( '.', $item->new_version );
				if ( $version[ count( $version ) - 1 ] == 0 ) {
					$message = 'WooCommerce patch version 0 so do not update.' . "\n======\n" . 'Item:' . "\n" . var_export( $item, true );
					wp_mail( 'damien.carbery@gmail.com', 'Woo HPOS - Auto Updates', $message );

					return false;
				}
			//}
		}

		$plugins_blocked = array( 'cf7-conditional-fields', 'woocommerce-pdf-invoices-packing-slips', 'simply-static');
		if ( in_array( $item->slug, $plugins_blocked ) ) {
			error_log( 'Blocking update of ' . $item->slug );
			$plugin_data = get_plugin_data( WP_PLUGIN_DIR . '/' . $item->plugin );
			//error_log( var_export( $item, true ) );
			if ( property_exists( $item, 'Version' ) && !empty( $item->Version ) ) {
				error_log( sprintf( 'Block updating "%s" (%s) from version %s to %s.', $plugin_data['Name'], $item->slug, $item->Version, $item->new_version ) );
				$this->email_content[] = sprintf( 'Block updating "%s" from version %s to %s.', $plugin_data['Name'], $item->Version, $item->new_version );
			}
			else {
				error_log( sprintf( 'Block updating "%s" (%s) to version %s.', $plugin_data['Name'], $item->slug, $item->new_version ) );
				$this->email_content[] = sprintf( 'Block updating "%s" to version %s.', $plugin_data['Name'], $item->new_version );
			}

			return false;
		}
		else {
			error_log( 'NOT blocking ' . $item->slug );
		}

/*		
		// Try block updating this plugin from 5.12.1 to 5.12.2.
		$plugin_slug = 'woocommerce-pdf-invoices-packing-slips';
		if ( $plugin_slug == $item->slug ) {
			//error_log( var_export( $item, true ) );
			if ( property_exists( $item, 'Version' ) && !empty( $item->Version ) ) {
				error_log( sprintf( 'Block updating "%s" from version %s to %s.', $plugin_slug, $item->Version, $item->new_version ) );
			}
			else {
				error_log( sprintf( 'Block updating "%s" to version %s.', $plugin_slug, $item->new_version ) );
			}

			return false;
		}
*/
/*
		// Totally block updating simply-static plugin.
		$plugin_slug = 'simply-static';
		if ( $plugin_slug == $item->slug ) {
			//error_log( var_export( $item, true ) );
			if ( property_exists( $item, 'Version' ) && !empty( $item->Version ) ) {
				error_log( sprintf( 'Block updating "%s" from version %s to %s.', $plugin_slug, $item->Version, $item->new_version ) );
				$this->email_content[] = sprintf( 'Block updating "%s" from version %s to %s.', $plugin_slug, $item->Version, $item->new_version );
			}
			else {
				error_log( sprintf( 'Block updating "%s" to version %s.', $plugin_slug, $item->new_version ) );
				$this->email_content[] = sprintf( 'Block updating "%s" to version %s.', $plugin_slug, $item->new_version );
			}

			return false;
		}
*/
	/*
		else {
			//wp_mail( 'damien.carbery@gmail.com', 'Woo HPOS - Auto Updates', 'Update check for : ' . $item->slug );
		}
	*/

		return $update;
	}


	// Send email if any updates were blocked.
	function send_info_about_blocked_updates( $update_results ) {
error_log( 'In send_info_about_blocked_updates.' );
error_log( '$this->email_content: ' . var_export( $this->email_content, true ) );

		if ( !empty( $this->email_content ) ) {
			$to = get_site_option( 'admin_email' );
$to = 'damien.carbery@gmail.com';
			$subject = 'Some plugin updates blocked ';
			$body = sprintf( 'Some plugin updates on your site at %s have been blocked. The plugins and reasons are listed below:%s%s', home_url(), "\n\n", implode( "\n", $this->email_content ) );
			wp_mail( $to, $subject, $body );
error_log( 'Sent email with content: ' . var_export( $body, true ) );
		}
	}
}
$ConditionalAutoUpdateBlocking = new ConditionalAutoUpdateBlocking();

/*
add_action( 'admin_head', 'dcwd_test_email_send' );
add_action( 'wp_head', 'dcwd_test_email_send' );
function dcwd_test_email_send() {
	error_log( 'In dcwd_test_email_send().' );
	wp_mail( 'damien.carbery@gmail.com', 'Woo HPOS - Auto Updates', 'Email test send.' );
}
*/


/*add_filter( 'plugins_update_check_locales', 'dcwd_add_auto_update_plugin_filter' );
function dcwd_add_auto_update_plugin_filter( $locales ) {
	add_filter( 'auto_update_plugin', 'dcwd_auto_update_plugin', 10, 2 );

	error_log( 'plugins_update_check_locales: add_filter auto_update_plugin dcwd_auto_update_plugin' );
	error_log( 'plugins_update_check_locales: current_filter: ' . var_export( current_filter(), true ) );

	return $locales;
}*/


/*add_action( 'wp_maybe_auto_update', 'dcwd_wp_maybe_auto_update', 5 );
function dcwd_wp_maybe_auto_update() {
	add_filter( 'auto_update_plugin', 'dcwd_auto_update_plugin', 10, 2 );
}*/

// TODO: Convert this all to a class and note plugins that were blocked.
// When the auto update process is finished then send an email to the admin.
// This can be done in : do_action( 'automatic_updates_complete', $this->update_results );

/*add_filter( 'auto_update_plugin', 'dcwd_auto_update_plugin', 10, 2 );
function dcwd_auto_update_plugin( $update, $item ) {
	error_log( 'dcwd_auto_update_plugin: current_filter: ' . var_export( current_filter(), true ) );
	error_log( 'dcwd_auto_update_plugin: is_admin() :' .var_export( is_admin(), true ) );
	error_log( 'dcwd_auto_update_plugin: wp_doing_cron(): ' .var_export( wp_doing_cron(), true ) );
	error_log( 'dcwd_auto_update_plugin: $item: ' . var_export( $item, true ) );

	if ( 'woocommerce' == $item->slug && !empty( $item->new_version ) ) {
		//error_log( var_export( $item, true ) );
		//if ( $item->new_version != $item->Version ) {  // It seems that ->Version is not available when run in wp_cron.
			$version = explode( '.', $item->new_version );
			if ( $version[ count( $version ) - 1 ] == 0 ) {
				$message = 'WooCommerce patch version 0 so do not update.' . "\n======\n" . 'Item:' . "\n" . var_export( $item, true );
				wp_mail( 'damien.carbery@gmail.com', 'Woo HPOS - Auto Updates', $message );

				return false;
			}
		//}
	}
	
	// Try block updating this plugin from 5.12.1 to 5.12.2.
	$plugin_slug = 'woocommerce-pdf-invoices-packing-slips';
	if ( $plugin_slug == $item->slug ) {
		//error_log( var_export( $item, true ) );
		if ( property_exists( $item, 'Version' ) && !empty( $item->Version ) ) {
			error_log( sprintf( 'Block updating "%s" from version %s to %s.', $plugin_slug, $item->Version, $item->new_version ) );
		}
		else {
			error_log( sprintf( 'Block updating "%s" to version %s.', $plugin_slug, $item->Version ) );
		}

		return false;
	}

	// Totally block updating simply-static plugin.
	$plugin_slug = 'simply-static';
	if ( $plugin_slug == $item->slug ) {
		//error_log( var_export( $item, true ) );
		if ( property_exists( $item, 'Version' ) && !empty( $item->Version ) ) {
			error_log( sprintf( 'Block updating "%s" from version %s to %s.', $plugin_slug, $item->Version, $item->new_version ) );
		}
		else {
			error_log( sprintf( 'Block updating "%s" to version %s.', $plugin_slug, $item->Version ) );
		}

		return false;
	}
	else {
		//wp_mail( 'damien.carbery@gmail.com', 'Woo HPOS - Auto Updates', 'Update check for : ' . $item->slug );
	}

	return $update;
}*/
