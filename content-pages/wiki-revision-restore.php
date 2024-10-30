<?php 
/*
 * Script to handle requests to restore a previous wiki page revision to become
 * the current version.  
 *
 * As part of this process, the original auther of a restored
 * revision is recorded in the post_metadata and this information is displated in the 
 * notes column of the revision history.
 */



// ToDo - convert to use POST instead of GET
/*if ( 'POST' != $_SERVER['REQUEST_METHOD'] ) {
	header('Allow: POST');
	header('HTTP/1.1 405 Method Not Allowed');
	header('Content-Type: text/plain');
	exit;
}*/

// - START - Sets up the WordPress Environment. 
$path = substr(__FILE__,0,strpos(__FILE__,"wp-content"));
require($path . '/wp-load.php' );
nocache_headers();
wp();
require_once( ABSPATH . WPINC . '/template-loader.php' );
// - END - Sets up the WordPress Environment. 


include(WP_PLUGIN_DIR.'/buddypress-group-wiki/bp-groupwiki.inc.php');

// Validate the nonce
if (!wp_verify_nonce($_REQUEST['_wpnonce'], 'wiki_revision_restore_')) {
	die('Security check');
}

global $bp, $wpdb;

$group_id = $_GET['group_id'];
$post_id = $_GET['post_id'];
$revision_id = $_GET['revision_id'];
$bpgw_blog_id = groups_get_groupmeta($group_id , 'wiki_blog_id');


// Get the currently selected page
$wiki_page = get_blog_post($bpgw_blog_id , $post_id);
$revision_page = get_blog_post($bpgw_blog_id , $revision_id);

// Get post permalink for later redirection - we return user to the revisions section
$return_link = bpgw_get_permalink($group_id, $post_id); 

// Switch to blog and set the loop_post for the selected page 
switch_to_blog($bpgw_blog_id);
bpgw_start_in_the_loop($wiki_page);

// Add security here
$can_restore_revisions = false;

$wiki_can_view 	= bpgw_user_can_view_wiki_page($post_id, $group_id);
$wiki_can_edit	= bpgw_user_can_edit_wiki_page($post_id, $group_id);
if($wiki_can_view && $wiki_can_edit) {
	$can_restore_revisions = true;
}

if($can_restore_revisions) {
	$old_author = $wiki_page->post_author;
	
	// Restore the revision... 
	

	// Set up args for the update
	$new_post_args = array();
	$new_post_args['ID'] = $post_id;
	$new_post_args['post_content'] = $revision_page->post_content;
	$new_post_args['post_author'] = $bp->current_user->id;
	// Update
	$new_post_id = wp_update_post ($new_post_args);
	
	
	// Update the page restore history in the post metadata
	bpgw_carry_over_restore_postmeta($post_id);
	/* Add in the restore record for our restored revision.
	 * NOTE: This call must come AFTER the carrying over of any previous 
	 * restore meta data on the page post ID else we risk deleting what we
	 * have just added if user restores the same version more than once successively. 
	 */
	add_post_meta($post_id, WIKI_M_PAGE_RESTORED, $post_id.'-'.$revision_id); // Add new post metadata
	
	// NOTE: wordpress assigns author IDs wrongly, current user is carried over to new revision copy)
	// Fix the author problem - need to manually set the author for the old revision
	$revisions = wp_get_post_revisions($post_id, array('numberposts' => 1, 'post_type' => 'revision',
												  'order' => 'DESC', 'orderby' => 'date'));
	if(!empty($revisions)) {
		// Get the post_id for the latest revision
		$latest_revision_id = $revisions[key($revisions)]->ID;
		// Update the revision with the author id of whoever was the previous current revision author
		$wpdb->query( $wpdb->prepare("UPDATE {$wpdb->base_prefix}%d_posts ".
									 "SET post_author = %d ".
									 "WHERE id =%s",
									 $bpgw_blog_id , $old_author, $latest_revision_id ) );
		// Now update the current rev (the parent post) with the current user's id
		$wpdb->query( $wpdb->prepare("UPDATE {$wpdb->base_prefix}%d_posts ".
									 "SET post_author = %d ".
									 "WHERE id =%s", 
									 $bpgw_blog_id , $bp->loggedin_user->id , $post_id ) );
	}

	// Check the result returned 
	if(is_numeric($new_post_id) && $new_post_id > 0) {
	
		// Check to see if the page is being edited at the same time as restore occurred
		$page_is_being_edited = false;
		$editing_author = '';
		
		$post_edit_data = $wpdb->get_results( $wpdb->prepare("SELECT meta_id, meta_value ".
														   "FROM {$wpdb->base_prefix}%d_postmeta ".
														   "WHERE post_id = %d && meta_key = 'bpgw_edit_lock'", 
														   $bpgw_blog_id, $post_id ), ARRAY_A );
		if ($post_edit_data) {
			foreach( $post_edit_data as $post_edit) {
				/* explode the string with &
				 * Resulting array will be:
				 * $edit_data['meta_value'][0]==userID, $edit_data['meta_value'][1]==timestamp, $edit_data['meta_value'][2]==Authorname
				 * if the time is within 15 seconds of now add them to the $output string
				 */
				$edit_data = explode( "&" , $post_edit['meta_value'] );
				if($edit_data[0] != $bp->loggedin_user->id ) { 
				// If this isn't the current user AND time less than 15 seconds ago
					if ((time() - $edit_data[1]) < 15) {
						$page_is_being_edited = true;
						$editing_author = $edit_data['meta_value'][2];
					}
				} 
			}
			
		}
		
		// Success feedback
		if(!$page_is_being_edited) {
			$success_message = 'The revision was successfully restored and is now the \'current\' revision.';
		}
		else {
			$success_message = 'It appears that the user \''.$editing_author.'\' is currently editing this document. '.
							  'The revision you selected has been successfully restored, but once '.$editing_author.' '.
							  'finishes editing the document and Saves their work, their version will overwrite '.
							  'your restored version.';
		}
		bp_core_add_message( __($success_message, 'buddypress' ) );
	} 
	else {
		$return_link .= "revision/$revision_id";
		bp_core_add_message( __( 'There was a error when attempting to restore this revision. '.
								 'Please report the problem to your systems administrator', 'buddypress' ), 'error');
	}
} 
else {
	$return_link .= "revision/$revision_id";
	// User isn't allowed to restore revisions
	bp_core_add_message( __( 'You do not have the required access to do that.', 'buddypress' ), 'error' );
}


// End the loop and restore blog
bpgw_end_in_the_loop();
restore_current_blog();

// Send the user back to the page view screen
bp_core_redirect($return_link);
?>