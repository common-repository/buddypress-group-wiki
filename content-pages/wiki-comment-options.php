<?php
/*
 * Script to handle the requests to delete a specific comment on a wiki page.  
 * Comment owners can delete their own comments as can those with higher 
 * privledges than the comment author. 
 *
 * No comments are completely deleted. The content of deleted comments is 
 * recorded in the comment metadata table and the original content of the
 * comment in the comments table is replaces with a message to indicate that
 * the comment was deleted.
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
if (!wp_verify_nonce($_REQUEST['_wpnonce'], 'wiki_comment_options')) {
	die('Security check');
}

global $bp, $wpdb;

$group_id = $_POST['group_id'];
$post_id = $_POST['post_id'];
$bpgw_blog_id = groups_get_groupmeta( $group_id , 'wiki_blog_id' );

$comment_id = $_POST['comment_id'];
$user_id = $bp->current_user->id;
$option = $_POST['submit'];

// Get the currently selected page
$wiki_page = get_blog_post( $bpgw_blog_id , $post_id );

// Switch to blog and set the loop_post for the selected page 
switch_to_blog($bpgw_blog_id);
bpgw_start_in_the_loop($wiki_page);

// Get post permalink for later redirection
$return_link = bpgw_get_permalink( $group_id , $post_id );

$success = false;
$fail_message = 'You do not have the required access to do that.';

// We are using a switch, despite there only currently being one option, 
// in case the range of options needs to be expanded upon
switch ($option) {
	case ('Delete'): 

		// Grab comment content
		// NOTE: get_comment() fails as cannot get WP to pick up the current blog.  
		$comment = $wpdb->get_row($wpdb->prepare("SELECT * ".
												 "FROM  {$wpdb->base_prefix}%d_comments ".
												 "WHERE comment_ID = %d LIMIT 1", 
												 $bpgw_blog_id, $comment_id));
												 
		$comment = apply_filters('get_comment', $comment);
		$old_comment_content = $comment->comment_content;
		
		// Replace with message indicating that moderator {NAME} deleted it
		global $current_user;
		get_currentuserinfo(); // Places result inside global '$current_user'
		$user_id = $current_user->ID;
		$display_name = $current_user->display_name;
		
		$moderated_content .= '<div class="comment-deleted">This comment was deleted by ';
		$moderated_content .= $display_name.' on '.bpgw_to_wiki_date_format(time());
		$moderated_content .= '</div>';
		
		// Update the comment
		// NOTE: update_comment() fails as cannot get WP to pick up the current blog.  
		$success = $wpdb->query( $wpdb->prepare("UPDATE ".
												 "{$wpdb->base_prefix}%d_comments ".
												 "SET comment_content = %s ".
												 "WHERE comment_ID =%d", 
												 $bpgw_blog_id, 
												 $moderated_content, 
												 $comment_id  ) );
		if( $success) {
			// Put wiped comment content into comments meta table to keep historical record
			bpgw_add_comment_meta($bpgw_blog_id, $comment_id, WIKI_M_COMMENT_DELETED_CONTENT, $old_comment_content, true); 
			bpgw_add_comment_meta($bpgw_blog_id, $comment_id, WIKI_M_COMMENT_DELETED_BY, $user_id, true);
			bpgw_add_comment_meta($bpgw_blog_id, $comment_id, WIKI_M_COMMENT_DELETED_DATE, current_time('mysql'), true);
			
			$success_message = 'The comment has been been deleted.';
			$return_link .= "#comment-$comment_id";
			
			
			// Generate the activity stream update message
			
			$page_name = $wiki_page->post_title;
			$original_author_id = $comment->user_id ;
			if($original_author_id != $user_id) {
				$user_info = get_userdata($original_author_id);
				$author_display_name = $user_info->display_name;
				$update_message = $display_name." deleted a comment originally added by ".$author_display_name.".";
			}
			else {
				$update_message = $display_name." deleted one of their own comments.";
			}
			
			
			$activity_content='<div class="activity-inner deleted-wiki-comment">'.$update_message.'</div>';	
			$name = '<a href="' . $bp->loggedin_user->domain . '">' . $bp->loggedin_user->fullname . '</a>';
			$group = new BP_Groups_Group($group_id, false, false);
			
			// Create the activity update
			$activity_update = $name . ' deleted a comment on the <a href="' . bp_get_group_permalink($group) . '">' .
							  attribute_escape($group->name) . '</a> wiki page <a href="' . $return_link .
							  '">'.$page_name.'</a>: <span class="time-since">%s</span>'. $activity_content;
			
			// Issue update based on privacy settings
			$page_privacy = get_post_meta($post_id, 'wiki_view_access', 1);
			

			if ($page_privacy == 'public' && $bp->groups->current_group->status != 'hidden') {
				// Record wiki creation in the activity stream, viewable by everyone
				bpgw_wiki_groups_record_activity(array(
					'content' => $activity_update,
					'primary_link' => $return_link,
					'component_action' => 'deleted_wiki_comment',
					'item_id' => $group->id,
					'secondary_item_id' => $bp->loggedin_user->id
				));
			} 
			else {
				// Record wiki creation in the activity stream, hide sitewide
				bpgw_wiki_groups_record_activity(array(
					'content' => $activity_update,
					'primary_link' => $return_link,
					'component_action' => 'deleted_wiki_comment',
					'item_id' => $group->id,
					'secondary_item_id' => $bp->loggedin_user->id,
					'hide_sitewide' => true
				));	
			}
			
			// Update the last_activity time for this group
			groups_update_groupmeta($group_id, 'last_activity', gmdate( "Y-m-d H:i:s" ));
	
		}
		else {
			$fail_message = 'There was an error when trying to delete the comment.';
		}
		break; 
		
	default:
		die('Unknown option');
}

if($success) {
	// Success feedback
	bp_core_add_message(__( $success_message, 'buddypress' ));

}
else { 
	// No access feedback
	bp_core_add_message(__( $fail_message, 'buddypress' ), 'error');
}

// End the loop and restore blog
bpgw_end_in_the_loop();
restore_current_blog();

// Send the user back to the page view screen
bp_core_redirect($return_link);
?>