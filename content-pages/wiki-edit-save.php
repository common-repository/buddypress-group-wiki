<?php

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

if (!wp_verify_nonce($_REQUEST['_wpnonce'], 'wiki_edit_save_')) {
	die('Security check');
}

global $bp, $wpdb;

$group_id = $_POST['group_id'];
$post_id = $_POST['post_id'];
$bpgw_blog_id = groups_get_groupmeta($group_id, 'wiki_blog_id');

// Get the currently selected page
$selected_page = get_blog_post($bpgw_blog_id, $post_id);
// Get post permalink for later redirection
$return_link = bpgw_get_permalink($group_id, $post_id);
// Set the loop_post for the selected page
switch_to_blog($bpgw_blog_id);
bpgw_start_in_the_loop($selected_page);

$page_privacy = get_post_meta($post_id, 'wiki_view_access', 1);
$page_name = $selected_page->post_title;
$old_author = $selected_page->post_author;

// Update the post if the user is allowed to
if (bpgw_user_can_edit_wiki_page($post_id, $group_id)) {
	// Update the page with the new data
	$new_post_content = $_POST['wiki-post-edit'];
	$new_post = array();
		$new_post['ID'] = $post_id;
		$new_post['post_content'] = $new_post_content;
		$new_post['post_author'] = $selected_page->post_author;
	wp_update_post($new_post);
	// Fix the author problem - need to manually set the author for the old revision
	$revisions = wp_get_post_revisions($post_id, array('numberposts' => 1, 'post_type' => 'revision', 'order' => 'DESC', 'orderby' => 'date'));
	if(!empty($revisions)) {
		// Get the post_id for the latest revision
		$latest_revision_id = $revisions[key($revisions)]->ID;
		// Update the revision with the author id of whoever was the previous current rev author
		$wpdb->query($wpdb->prepare("UPDATE {$wpdb->base_prefix}%d_posts SET post_author = %d WHERE id =%s", $bpgw_blog_id, $old_author, $latest_revision_id));
		// Now update the current rev (the parent post) with the current user's id
		$wpdb->query($wpdb->prepare("UPDATE {$wpdb->base_prefix}%d_posts SET post_author = %d WHERE id =%s", $bpgw_blog_id, $bp->loggedin_user->id, $post_id));
	}
	
	// Update the page restore history in the post metadata
	bpgw_carry_over_restore_postmeta($post_id);
	
	// Success feedback
	bp_core_add_message( __( 'Your update has been saved.', 'buddypress'));
	groups_update_groupmeta($group_id, 'last_activity', gmdate( "Y-m-d H:i:s" ));
	
	// Get all the editing post meta for this wikipage
	$post_edit_data = $wpdb->get_results( $wpdb->prepare("SELECT meta_id, meta_value FROM {$wpdb->base_prefix}%d_postmeta WHERE post_id = %d && meta_key = 'bpgw_edit_lock'", $bpgw_blog_id, $post_id ) , ARRAY_A );
	
	if ($post_edit_data) { // Only do this if there's anything in there - there should always be at least the current user
		// Remove anyone else's editing postmeta that is older than 120seconds
		// Remove own editing postmeta
		// Set a warning flag on all remaining other editors postmeta so they'll get a one-time warning about this save
		foreach ($post_edit_data as $post_edit) {		
			//explode the string with &
			//$edit_data['meta_value'][1]==userID, $edit_data['meta_value'][2]==timestamp, 
			//$edit_data['meta_value'][3]==Authorname, $edit_data['meta_value'][4]==warningflag (usually not set)
			$edit_data = explode("&", $post_edit['meta_value']);
			if ( $edit_data[1] != $bp->loggedin_user->id ) {
				// This is someone else's editing meta.  If it's older than 15 seconds, delete it.  If it's newer than that, append save warning
				if (time() - $edit_data[2] > 20) { // Old edit data.  Clean it from the db
					$wpdb->query( $wpdb->prepare( "	DELETE FROM {$wpdb->base_prefix}%d_postmeta
													WHERE meta_id = %d", 
													$bpgw_blog_id, $post_edit['meta_id'] ) );
				} else { // This is recent edit data, append the warning
					$meta_value = '&' . $edit_data[1] . '&' . time() . '&' . $edit_data[3] . '&1';
					$wpdb->query( $wpdb->prepare( "	UPDATE {$wpdb->base_prefix}%d_postmeta
													SET meta_value = '%s'
													WHERE meta_id = %d", 
													$bpgw_blog_id, $meta_value, $post_edit['meta_id'] ) );
				}
			} else { // This is the users own editing post meta - delete it
				$wpdb->query( $wpdb->prepare( "	DELETE FROM {$wpdb->base_prefix}%d_postmeta
												WHERE meta_id = %d", 
												$bpgw_blog_id, $post_edit['meta_id'] ) );
			}
		}
	}
	
	// Get last post revision so can do a diff to work out what to put in activity stream
	$previous_revisions = wp_get_post_revisions($selected_page->ID);

	// Do this bit only if any revisions returned (should always be true due to the way wiki pages are created)
	if ($previous_revisions) {
		reset($previous_revisions);
		$rev_id = key($previous_revisions); 
		$last_revision = $previous_revisions[$rev_id]; // Revision sorting defaults to newest > oldest so we just grab the first revision result
		$left_content = apply_filters( '_wp_post_revision_fields', $last_revision->post_content  );  // Need these or things break
		$right_content  = apply_filters( '_wp_post_revision_fields',  get_post($post_id)->post_content  );  // Need these or things break
		$left_content   = str_replace('&nbsp;','',$left_content);
		$right_content  = str_replace('&nbsp;','',$right_content);
		$rev_diff_content = wp_text_diff( $left_content, $right_content );
		if (preg_match_all("/<td class=\'diff-addedline\'>(.*?)<\/td>/si", $rev_diff_content, $line_text))
		{
			$added_content = $line_text[0];
		}
		$there_is_content_added = false;
		if (isset($added_content)) {
			if (count($added_content) > 2) {
				$added_content = array_slice($added_content, -3, 3); // Get the last three elements as this is probably more useful info
			}
			foreach ($added_content as $added_item) {
					$add_item = str_replace(array("\r\n", "\r", "\n"), '', strip_tags(html_entity_decode($added_item)));
					if (strlen($add_item) > 175) {
						$add_item = '...' . substr($add_item, strlen($add_item)-175, 175);
					} else {
						$add_item = substr($add_item, 0, 175);				
					}
					if ($add_item != '') {
						$added_text .= '<div>' . $add_item . '...</div>';
						$there_is_content_added = true;
					}
			}
		} 
		if ($there_is_content_added) {
			$activity_content = $added_text;	
			$name = '<a href="' . $bp->loggedin_user->domain . '">' . $bp->loggedin_user->fullname . '</a>';
			$group = new BP_Groups_Group($group_id , false , false);
			$activity_update = $name . ' edited the <a href="' . bp_get_group_permalink($group) . '">' . attribute_escape($group->name) . '</a> wiki page <a href="' . $return_link . '">' . $page_name . '</a>: <span class="time-since">%s</span>';
			if ($page_privacy == 'public' && $bp->groups->current_group->status != 'hidden') {
				// Record wiki creation in the activity stream, viewable by everyone
				bpgw_wiki_groups_record_activity(array(
					'action' => $activity_update,
					'content' => $activity_content,
					'primary_link' => $return_link,
					'component' => 'groups',
					'component_action' => 'new_wiki_edit',
					'type' => 'new_wiki_edit',
					'item_id' => $group->id,
					'secondary_item_id' => $bp->loggedin_user->id
				) );
			} else {
				// Record wiki creation in the activity stream, hide sitewide
				bpgw_wiki_groups_record_activity(array(
					'content' => $activity_update,
					'primary_link' => $return_link,
					'component' => 'groups',
					'component_action' => 'new_wiki_edit',
					'type' => 'new_wiki_edit',
					'item_id' => $group->id,
					'secondary_item_id' => $bp->loggedin_user->id,
					'hide_sitewide' => 1
				) );	
			}
		} 
	}
} else {
	// User isn't allowed to do the edit
	bp_core_add_message( __( 'You do not have the required access to do that.', 'buddypress'), 'error');
}
bpgw_end_in_the_loop();
restore_current_blog();
// Send the user back to the page view screen
bp_core_redirect($return_link);
?>