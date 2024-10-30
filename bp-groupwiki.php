<?php

/*

Plugin Name: Group Wiki Extension

Plugin URI: http://wordpress.org/extend/plugins/buddypress-group-wiki/

Description: Simple Group Wikis for BuddyPress

Author: David Cartwright, Stuart Anderson, Ryo Seo-Zindy

Version: 1.8

Author URI: http://www.namoo.co.uk

Site Wide Only: true

*/



/**

 *	THIS PLUGIN IS LICENSED UNDER THE GNU AGPL

 *	AGPL DETAILS: http://www.fsf.org/licensing/licenses/agpl-3.0.html

 */



/**

 * This function is added to the WP plugins_loaded action and ensures that the BuddyPress plugin 

 * code is loaded before the plugin.

 */

add_action( 'plugins_loaded', 'wiki_load_buddypress', 1 );

function wiki_load_buddypress() {

	if ( function_exists( 'bp_core_setup_globals' ) ) {

		require_once ('bp-groupwiki-main.php');

		return true;

	}

	/* Get the list of active sitewide plugins */

	$active_sitewide_plugins = maybe_unserialize( get_site_option( 'active_sitewide_plugins' ) );



	if ( !isset( $active_sidewide_plugins['buddypress/bp-loader.php'] ) )

		return false;



	if ( isset( $active_sidewide_plugins['buddypress/bp-loader.php'] ) && !function_exists( 'bp_core_setup_globals' ) ) {

		require_once( WP_PLUGIN_DIR . '/buddypress/bp-loader.php' );

		require_once ('bp-groupwiki-main.php');

		return true;

	}



	return false;

}

?>