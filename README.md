# Conditional Auto Update blocking
Contributors: daymobrew  
Tags: updates, auto-updates  
Requires at least: 6.7  
Tested up to: 7.0  
Requires PHP: 7.4  
Stable tag: 0.1.20260529  
License: GPLv3  
License URI: https://www.gnu.org/licenses/gpl-3.0.html  

Allow for conditionally blocking the auto update of a plugin e.g. limit days one can be updated or any condition you can think of.

## Description
When auto-updates are enabled for a plugin this code can be used to allow or block the update. For example, you could prevent updates on Friday, Saturday and Sunday so that critical error will not bring your site down over the weekend.

Note that the plugin as-is does not block any updates. This must be done with additional code written by a developer (using the `should_update_check` filter). There are examples in the [Developer information](#developer-information) section.

If you find a bug, have a feature request, please create an issue via the plugin's [GitHub repository](https://github.com/damiencarbery/conditional-autoupdate-blocking/issues).

You can also [contact me on my website](https://www.damiencarbery.com/contact/).

## Frequently Asked Questions
None yet.

## Upgrade Notice
None yet.

## ToDo
- Change how content is added to the email without needing to use "global $ConditionalAutoUpdateBlocking;".

## Developer information

Example usage of blocking updates on Fridays and at weekends.


The access can be changed with the '*liudownload_check_perms*' filter, returning true to allow the download.
For example:

	<?php
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


Example usage of blocking WooCommerce updates for .0 versions.

	<?php
	add_filter( 'should_update_check', 'dcwd_no_zero_version_woocommerce', 5, 2 );
	function dcwd_no_zero_version_woocommerce( $update, $item ) {
		if ( 'woocommerce' == $item->slug && !empty( $item->new_version ) ) {
			$version = explode( '.', $item->new_version );
      if ( $version[ count( $version ) - 1 ] == 0 ) {
        global $ConditionalAutoUpdateBlocking;
        $ConditionalAutoUpdateBlocking->add_to_email_content( 'WooCommerce patch version is 0 so do not update. Will wait for .1 version.' );
        return false
		  }
    }

    return $update;
	}


== Changelog ==

= 0.1.20260529 =
* Initial release.
