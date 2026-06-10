<?php
/*
Plugin Name: Conditional Auto-Update blocking
Plugin URI: https://www.damiencarbery.com/
Description: Allow for conditionally blocking the auto-update of a plugin e.g. limit days one can be updated or any condition you can think of.
Author: Damien Carbery
Author URI: https://www.damiencarbery.com
Version: 0.3.20260610
License: GPL v3 or later
License URI: https://www.gnu.org/licenses/gpl-3.0.html
*/


defined( 'ABSPATH' ) || exit;


class ConditionalAutoUpdateBlocking {
	// The single instance of the class.
	public static $instance = null;

	// The content of the email sent to the admin.
	private $email_content;


	// Returns an instance of this class. 
	public static function instance() {
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
			$to = get_site_option( 'admin_email' );  // ToDo: Filter the $to value.
			$subject = 'Some plugin auto-updates blocked ';
			$body = sprintf( 'Some plugin auto-updates on your site at %s have been blocked. The plugins and reasons are listed below:%s* %s', home_url(), "\n\n", implode( "\n* ", $this->email_content ) );
			wp_mail( $to, $subject, $body );
		}
	}
}


// Returns the main instance of the ConditionalAutoUpdateBlocking class.
function CAUB() { // phpcs:ignore WordPress.NamingConventions.ValidFunctionName.FunctionNameInvalid
	return ConditionalAutoUpdateBlocking::instance();
}


$ConditionalAutoUpdateBlocking = CAUB();