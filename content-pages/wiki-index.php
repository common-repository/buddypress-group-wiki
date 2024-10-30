<?php
/*
 * This is the wiki group homepage screen.  This page redirects to appropriate 
 * script depending on user action.
 */

// Turn debug on or off (1 or 0) 
$wiki_debug_mode = 0;

global $bp;

bpgw_switch_to_wiki_blog($bp->groups->current_group->id);

// Set up filters to add in the various outputed elements
add_filter('the_content', 'bpgw_table_of_contents', 9);


$wiki_group_id 		= $bp->groups->current_group->id;
$wiki_blog_id 		= groups_get_groupmeta($wiki_group_id , 'wiki_blog_id');
$wiki_page 			= bpgw_get_selected_page_from_url();

bpgw_start_in_the_loop($wiki_page);

$wiki_page_content	= apply_filters('the_content' , $wiki_page->post_content);
$wiki_page_revisions= bpgw_wiki_page_revisions_summary($wiki_group_id);
$wiki_page_uri		= bpgw_get_permalink($wiki_group_id , $wiki_page->ID);
$wiki_action_page	= $bp->action_variables[0]; // Page name
$wiki_action_var 	= $bp->action_variables[1]; // Page action (edit/comment/revision)
$wiki_can_edit		= bpgw_user_can_edit_wiki_page($wiki_page->ID, $wiki_group_id);
$wiki_can_view		= bpgw_user_can_view_wiki_page($wiki_page->ID, $wiki_group_id);

if($wiki_page) {
	$wiki_page_links= bpgw_wiki_page_links($wiki_group_id , $wiki_page);
} else {
	$wiki_page_links= bpgw_wiki_page_links($wiki_group_id);
}
$wiki_can_comment	= bpgw_wiki_page_comments_enabled($wiki_page);

// Only get comments if this is a page that the user can view
if ($wiki_action_page && $wiki_can_view && $wiki_can_comment) { 
	$wiki_comments	= bpgw_get_wiki_comments($wiki_page);
	$wiki_comment_title_bar= bpgw_get_comments_titlebar($wiki_page->ID, count($wiki_comments));
}

bpgw_end_in_the_loop ();
restore_current_blog();

if ($wiki_debug_mode == 1) {
	echo '<pre>';
	echo '$wiki_group_id<br/>';
	var_dump($wiki_group_id);
	echo '$wiki_blog_id<br/>';
	var_dump($wiki_blog_id);
	echo '$wiki_page<br/>';
	var_dump($wiki_page);
	echo '$wiki_page_content<br/>';
	var_dump($wiki_page_content);
	echo '$wiki_page_uri<br/>';
	var_dump($wiki_page_uri);
	echo '$wiki_pageActionPage<br/>';
	var_dump($wiki_action_page);
	echo '$wiki_action_var<br/>';
	var_dump($wiki_action_var );
	echo '$wiki_can_edit<br/>';
	var_dump($wiki_can_edit);
	echo '$wiki_can_view<br/>';
	var_dump($wiki_can_view);
	echo '$wiki_can_comment<br/>';
	var_dump($wiki_can_comment);
	echo '$wiki_page_links<br/>';
	var_dump($wiki_page_links);
	echo '</pre>';
}

$selected_php_file = ''; 

if (!$wiki_page_links) { // User isn't allowed to view *any* of the wiki pages.  Send them packing
	bp_core_add_message(__( 'You do not have the required access to do that.', 'buddypress'), 'error');
} elseif (!$wiki_action_page) { // User is allowed to view some pages but doesn't have one selected.  Show them the index
	$selected_php_file = 'wiki-home.php';
} else { // User is trying to view a specific page or perform an action on one.  Check credentials and call appropriate file
	// Check the current page action (action_variables[1]) and load the needed action page after checking user access
	// If no action, assume this is a view request
	switch ($wiki_action_var) {
		case 'edit':
			if($wiki_can_edit) {
				$selected_php_file = 'wiki-edit.php';
			} else {
				$error_feedback = 'You are not allowed to edit this page.';
			}
			break;
		case 'revision':
			if ($wiki_can_view) {
				$selected_php_file = 'wiki-revision.php';
			} else {
				$error_feedback = 'You are not allowed to view this page.';
			}
			break;
		default:
			if ($wiki_can_view) {
				$selected_php_file = 'wiki-display.php';
			} else {
				$error_feedback = 'You are not allowed to view this page.';
			}
	}
}

?>
<div id="groupwiki">
	<?php 
	if ($wiki_can_view && !empty($wiki_page)) {
		echo $wiki_page_links;
	}
	
	if ($wiki_page_links == false && !$error_feedback) {
	?>
		<div id="message" class="info">
			<p>You do not currently have access to view any of the wiki pages of this group.</p>
		</div>
	<?php
	} elseif($error_feedback) {
	?>
		<div id="message" class="error">
			<p><?php echo $error_feedback;?></p>
		</div>
	<?php
	}
	?>
	
	<?php 
	if ($selected_php_file) {
		require $selected_php_file; // Do whatever was selected from the switch statement above 
	}
	?>
</div>