<?php
/*
Plugin Name: Conditional Auto-Update blocking examples
Plugin URI: https://www.damiencarbery.com/
Description: Examples using the ConditionalAutoUpdateBlocking code to blocking the auto-update of a plugin e.g. limit days one can be updated or any condition you can think of.
Author: Damien Carbery
Author URI: https://www.damiencarbery.com
Version: 0.2.20260608
License: GPL v3 or later
License URI: https://www.gnu.org/licenses/gpl-3.0.html
*/


defined( 'ABSPATH' ) || exit;



// Example usage of the ConditionalAutoUpdateBlocking code to prevent updates on Fridays and at weekends.
add_filter( 'should_update_check', 'dcwd_no_weekend_updates', 10, 2 );
function dcwd_no_weekend_updates( $update, $item ) {
	$day_of_week = gmdate( 'w' );  // 1 == Monday, 7 == Sunday.

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


// Example usage of the ConditionalAutoUpdateBlocking code to prevent WooCommerce updates until the release is at least two weeks old.
add_filter( 'should_update_check', 'dcwd_woocommerce_two_weeks_old', 5, 2 );
function dcwd_woocommerce_two_weeks_old( $update, $item ) {
	if ( 'woocommerce' == $item->slug ) {
		$url = 'https://api.wordpress.org/plugins/info/1.2/?action=plugin_information&slug=' . $item->slug;

		$res = wp_remote_get( $url );
		if ( true === is_wp_error( $res ) ) {
			return $update;
		}

		$body = wp_remote_retrieve_body( $res );
		$body = json_decode( $body, true );

		if ( JSON_ERROR_NONE !== json_last_error() ) {
			return $update;
		}

		$last_updated_age = time() - strtotime( $body['last_updated'] );
		if ( $last_updated_age > (2 * WEEK_IN_SECONDS) ) {
			return $update;
		}
		else {
			global $ConditionalAutoUpdateBlocking;
			$ConditionalAutoUpdateBlocking->add_to_email_content( 'WooCommerce update is less than two weeks old so do not update. Will wait until it is two weeks old.' );
			return false;
		}
	}

	return $update;
}
