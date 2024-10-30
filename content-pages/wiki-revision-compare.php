<?php
/*
 * Script to handle the form values when a user selected two revisions to compare.
 * The user is simply returned to the revisions page where they came from, but 
 * the revision IDs are appended to the URL to indicate that two page revisions
 * are to be evaluated.
 */


if ( 'POST' != $_SERVER['REQUEST_METHOD'] ) {
	header('Allow: POST');
	header('HTTP/1.1 405 Method Not Allowed');
	header('Content-Type: text/plain');
	exit;
}

/** - START - Sets up the WordPress Environment. */
$path = substr(__FILE__,0,strpos(__FILE__,"wp-content"));
require($path . '/wp-load.php' );
nocache_headers();
wp();
require_once( ABSPATH . WPINC . '/template-loader.php' );
/** - END - Sets up the WordPress Environment. */


include(WP_PLUGIN_DIR.'/buddypress-group-wiki/bp-groupwiki.inc.php');

// Validate the nonce
if (!wp_verify_nonce($_REQUEST['_wpnonce'], 'wiki_revision_compare')) {
	die('Security check');
}

global $bp;
$group_id = $_POST['group_id'];
$post_id = $_POST['post_id'];
$bpgw_blog_id = groups_get_groupmeta( $group_id , 'wiki_blog_id' );

// Get the currently selected page
$wiki_page = get_blog_post($bpgw_blog_id, $post_id);

// Get post permalink for later redirection
$return_link = bpgw_get_permalink( $group_id , $post_id ) . "revision/";

// Grab the values obtained from the post variables
$left_revision = $_POST['left'];
$right_revision = $_POST['right'];

// Loosely evaluate the given variables and report an error if a value is missing
if($left_revision == '' && $right_revision == '') {
	bp_core_add_message( __( 'You must select two different revisions and you appear to have not selected any.',
							 'buddypress' ), 'error' );
}
else if($left_revision == '') {
	bp_core_add_message( __( 'You must select two different revisions and you appear to have not selected '.
							 'a left-side revision for the comparison', 'buddypress' ), 'error' );						 
} 
else if($right_revision == '') {
	bp_core_add_message( __( 'You must select two different revisions and you appear to have not selected '.
							 'a right-side revision for the comparison', 'buddypress' ), 'error' );
}
else if($right_revision == $left_revision) {
	bp_core_add_message( __( 'You must select two different revisions and you appear to selected identical ones. '.
							 'Both versions are the same!', 'buddypress' ), 'error' );
}
else {
	// Form the URL for returning the details of the revision
	$return_link .= "$left_revision-$right_revision";
}

// Send the user back to the page view screen
bp_core_redirect($return_link);
?>