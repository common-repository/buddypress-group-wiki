<?php
/*
 * Script to handle the submission of comments to a wiki page.  Submitted comments will also trigger
 * an activity stream update to appropriate users.
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
if (!wp_verify_nonce($_REQUEST['_wpnonce'], 'wiki_unfiltered_html_comment')) {
	die('Security check');
}

global $bp;

$group_id = $_POST['group_id'];
$post_id = $_POST['comment_post_id'];
$bpgw_blog_id = groups_get_groupmeta($group_id , 'wiki_blog_id');

// Get the currently selected page
$wiki_page = get_blog_post($bpgw_blog_id, $post_id);

// Get post permalink for later redirection
$return_link = bpgw_get_permalink($group_id, $post_id); 

// Switch to blog and set the loop_post for the selected page 
switch_to_blog($bpgw_blog_id);
bpgw_start_in_the_loop($wiki_page);


// Permissions checks
$wiki_can_view = bpgw_user_can_view_wiki_page($wiki_page->ID , $group_id);
$wiki_can_comment = bpgw_wiki_page_comments_enabled($wiki_page);
$can_add_comments = false;

// TODO: Check user id vs group membership 

// Check if user may add comments
if (is_user_logged_in() && $wiki_can_view && $wiki_can_comment && ($_POST['save'] != 'Save')) { 
	$can_add_comments = true;
}


// If the user has passed above checks, run through the save process
if ($can_add_comments) {
	
	// Update the page with the new comment
	$comment_text = $_POST['comment_text'];
	
	global $current_user;
    get_currentuserinfo(); // Places result inside global '$current_user'

	$commentdata = array();
	$commentdata['comment_post_ID'] = $wiki_page->ID;
	$commentdata['comment_author'] = $current_user->user_login;
	$commentdata['comment_author_email'] = $current_user->user_email ;
	$commentdata['user_ID'] = $current_user->ID;
	$commentdata['comment_author_IP'] = $_SERVER['REMOTE_ADDR'];
	$commentdata['comment_content'] = $comment_text;
	$commentdata['comment_agent'] = $_SERVER['HTTP_USER_AGENT'];
	$commentdata['comment_type'] = 'comment';

	// Calling wp_new_comment also sanitizes the comment
	$comment_id = wp_new_comment($commentdata);
	
	// Create the return link for a successful commenting
	$return_link .= "#comment-$comment_id";
	
	
	
	// Generate the activity stream update message
	$page_name = $wiki_page->post_title;
	$comment_excerpt = bpgw_substrws($comment_text, 300) . '...';
	$activity_content= $comment_excerpt;	
	$name = '<a href="' . $bp->loggedin_user->domain . '">' . $bp->loggedin_user->fullname . '</a>';
	$group = new BP_Groups_Group($group_id, false, false);
	
	// Create the activity update
	$activity_update = $name . ' commented on the <a href="' . bp_get_group_permalink($group) . '">' .
					  attribute_escape($group->name) . '</a> wiki page <a href="' . $return_link .
					  '">'.$page_name.'</a>: <span class="time-since">%s</span>';
	
	// Issue update based on privacy settings
	$page_privacy = get_post_meta( $post_id , 'wiki_view_access' , 1 );
	
	
	/* David: the comment activity stream item should simply be on the sitewide feed based 
	on the page being public or not (with the override of the group being hidden)
	the notification email should preferably be to all group members, 
	assuming they have view or edit access on the page */

	if ($page_privacy == 'public' && $bp->groups->current_group->status != 'hidden') {
		// Record wiki creation in the activity stream, viewable by everyone
		bpgw_wiki_groups_record_activity(array(
			'action' => $activity_update,
			'content' => $activity_content,
			'primary_link' => $return_link,
			'component' => 'groups',
			'component_action' => 'new_wiki_comment',
			'type' => 'new_wiki_comment',
			'item_id' => $group->id,
			'secondary_item_id' => $bp->loggedin_user->id
		) );
	} 
	else {
		// Record wiki creation in the activity stream, hide sitewide
		bpgw_wiki_groups_record_activity(array(
			'action' => $activity_update,
			'content' => $activity_content,
			'primary_link' => $return_link,
			'component' => 'groups',
			'component_action' => 'new_wiki_comment',
			'type' => 'new_wiki_comment',
			'item_id' => $group->id,
			'secondary_item_id' => $bp->loggedin_user->id,
			'hide_sitewide' => true
		) );	
	}
	
	// Success feedback
	bp_core_add_message(__( 'Your comment has been saved.', 'buddypress' ));
	// Update the last_activity time for this group
	groups_update_groupmeta($group_id, 'last_activity', gmdate( "Y-m-d H:i:s" ));
}
else { 
	// No access feedback
	bp_core_add_message(__( 'You do not have the required access to do that.', 'buddypress' ), 'error');
}


// End the loop and restore blog
bpgw_end_in_the_loop();
restore_current_blog();

// Send the user back to the page view screen
bp_core_redirect($return_link);
?>