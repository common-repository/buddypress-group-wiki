<?php
/*
 * This file collects all the core functions which drive the group-wiki
 * plugin.  They are all centrally located in this one file to allow them
 * to be easily shared between scripts.
 */
 
 
 
 
/********************************************
 *** General wiki utility functions       ***
 ********************************************/ 

/**
 * Switches the current blog to the wiki blog (if present)
 */
function bpgw_switch_to_wiki_blog( $group_id ){
	$meta_key = 'wiki_blog_id';
	$bpgw_blog_id = groups_get_groupmeta($group_id, $meta_key);
	switch_to_blog($bpgw_blog_id);
}

/**
 * Sets up wp to act in loop mode for a single post i.e. avoiding the need
 * to set up a loop to loop through many posts when only one needs to be 
 * handled.
 *
 * @param array Post to be set up as the postdata
 */ 
function bpgw_start_in_the_loop($loop_post) {
	global $post, $wp_query;
	$wp_query->in_the_loop = true;
	$post = $loop_post;
	setup_postdata($post);
}
/**
 * Ends a wp in loop mode.
 */
function bpgw_end_in_the_loop() {
	global $wp_query;
	$wp_query->in_the_loop = false;
}

/**
 * Creates and returns a string containing the HTML of a horizontal list of links of 
 * pages in a wiki blog.  Optionally a specific post can be set with CSS style
 * (using the CSS class selector).
 *
 * @param array Highlight the given post with the style 'selected-page'.
 *
 * @return string The HTML unordered list of links.
 */
function bpgw_wiki_page_links($group_id, $highlight_post = null) {

	global $bp;
	
	query_posts('post_type=page&orderby=menu_order&order=ASC');
	
	// This will be set to true if any of the pages pass the bpgw_user_can_view_wiki_page check
	$any_pages_visible = false;
	
	$output .= "<div id='wiki-nav'>";
	$output .= "<ul id='wiki-pages'>";
	while (have_posts()) : the_post();
		$current_post = get_post(get_the_ID());
		if (bpgw_user_can_view_wiki_page( get_the_ID() , $group_id ) ) {
			$is_selected = '';
			if( ( $current_post->post_name == $highlight_post->post_name ) && ( $bp->action_variables[0] != '' ) ) {
				$is_selected = ' class="selected-page"';
			}
			
			$output .= "<li".$is_selected."><a href='".bpgw_get_permalink( $group_id , $current_post->ID )."'>";
			$output .= "".$current_post->post_title;
			$output .= "</a></li>";
			// Set $any_pages_visible to true so that we know to return a non-false value
			$any_pages_visible = true;
		}
	endwhile;
	
	$output .= "</ul>";
	$output .= "<div class='clean'></div>";
	$output .= "</div>";
	
	// If user can see any of these pages, return the html for the page links, else return false
	if ( $any_pages_visible ) {
		return $output;
	} else {
		return false;
	}
}
/**
 * Inspects the URL and attempts to identify and return the currently selected
 * page using the $bp->action_variable array.
 *
 * @return array The selected page or null if none found.
 */
function bpgw_get_selected_page_from_url() {
	global $bp;
	// This will be returned
	$selected_page = null;
	
	// Grab all pages for the wiki-blog that exist sorted by menu_order
	$all_pages = get_pages(array('sort_column' => 'menu_order'));

	// Get the last part of the slug, which may be the selected page_name
	$selected_page_name = $bp->action_variables[0];

	// Check if the action_variable has returned a valid page_name
	foreach($all_pages as $page) {
		if($page->post_name  == $selected_page_name)
		{
			$selected_page = $page;
		}
	}
	
	// If no matching page found return nothing.  
	// User should see home page instead
	
	return $selected_page;
}


/******************************************************
 *** Utility functions to help support pagination   ***
 ******************************************************/ 

/**
 * A pagination helper function to allow pages of items to be pulled out of an array in 
 * set block sizes.
 * 
 * @param array $array_of_items The array of all items
 * @param int $page The page number of items that should be returned.
 * @param int $items_per_page The total number of items permitted per page.
 * 
 * @return array An array containing only the items that should be included for the 
 * given page number.
 */
function bpgw_wiki_get_page_of_items($array_of_items, $page, $items_per_page) {
		$total_items = count($array_of_items);
		$last_item = $page*$items_per_page;
		$first_item = $last_item-$items_per_page;
		
		$page_of_items = array();
		for($i = 0; $i < $items_per_page; $i++) {
			if(isset($array_of_items[$first_item + $i])) {
				$page_of_items[$i] = $array_of_items[$first_item + $i];
			}
		}
		return $page_of_items;
}

/**
 * Calculate the total number of pages need to display items in an array 
 * (pages, comments etc), based on a given per page quota.
 * Based on WordPress get_comment_pages_count()
 *
 * @param array $items Optional array of objects. 
 * @param int $per_page Optional items per page. Defaults to 10
 *
 * @return int Number of pages.
 */
function bpgw_wiki_get_total_pages_count($items = null, $items_per_page) {
	 
	if (empty($items) )
		return 0;

	$count = ceil( count( $items ) / $items_per_page );
	return $count;
}



/******************************************************
 *** Utility functions related to handling dates    ***
 ******************************************************/ 

/**
 * Formats a given time stamp string into a standard
 * date format to give a consistent format across the 
 * group wiki tool.
 *
 * @param string A time stamp string.
 *
 * @return string The formatted time stamp.
 */ 
function bpgw_to_wiki_date_format($time_stamp) {
	$formated_time_stamp = sprintf('%1$s at %2$s',
				 bpgw_format_date($time_stamp,'d F, Y'), 
				 bpgw_format_date($time_stamp,'H:i'));
	return $formated_time_stamp;
}
/**
 * Formats a given time stamp string.
 *
 * @param string A time stamp string.
 * @param string A given date format in PHP date formating notation.
 *
 * @return string The formatted time stamp.
 */ 
function bpgw_format_date($time_stamp, $format) {
	$date = mysql2date($format, $time_stamp);
	return apply_filters('wiki_bpgw_format_date', $date, $format);
}



/******************************************************
 *** Utility functions related to handling strings  ***
 ******************************************************/ 

/**
 * Looks for the first occurence of $needle in $haystack and replaces it with $replace. 
 *
 * @param string $needle The search string to look for the first instance of.
 * @param string $replace The string to replace the string we search for with.
 * @param string $haystack The content string we seach for the first instance within.
 *
 * @return string The new string with the first instance of $needle replaces with $replace.
 */
function bpgw_str_replace_once($needle, $replace, $haystack) { 
    $pos = strpos($haystack, $needle); 
    if ($pos === false) { 
       // Nothing found 
       return $haystack; 
    } 
    return substr_replace($haystack, $replace, $pos, strlen($needle)); 
} 

/**
 * Checks to see if a given string begins with a given search string.
 *
 * @param string $target The target string to inspect
 * @param string $search The string to search the beginning of target for.
 *
 * @return boolean True if the target begins with the search string, false otherwise.
 */
function bpgw_string_begins_with($target, $search) {
    return (strncmp($target, $search, strlen($search)) == 0);
}


/***************************************************
 *** Action privilege functions                  ***
 ***************************************************/ 

 
/**
 * Checks current user against current group and $post_id wiki meta settings.  Returns false if user not 
 * allowed to edit page.  True if they are allowed.
 *
 * @param int   $post_id ID of the post
 * @param int   $group_id ID of the group
 *
 * @returns true if able to edit, false if not
 */
 function bpgw_user_can_edit_wiki_page( $post_id, $group_id ) {
	if(empty($post_id) || empty($group_id)) 
		return false;
	
	global $bp;
	
	$bpgw_blog_id = groups_get_groupmeta( $group_id , 'wiki_blog_id' );
	
	// If the page is set to disabled, no edit or viewing is allowed
	if ( bpgw_get_blog_post_metadata( $bpgw_blog_id , $post_id , 'wiki_page_enabled' ) == 0 ) {
		return false;
	}
	
	$post_edit_settings = bpgw_get_blog_post_metadata( $bpgw_blog_id , $post_id , 'wiki_edit_access' );
	
	// If user is banned, return false straight away.  Otherwise, do the full user access checks
	if (groups_is_user_banned( $bp->loggedin_user->id , $group_id )) return false;

	switch ( $post_edit_settings ) {
		case 'all-members':
			if 
			(
				groups_is_user_admin( $bp->loggedin_user->id , $group_id )	||
				groups_is_user_mod( $bp->loggedin_user->id , $group_id )		||
				groups_is_user_member( $bp->loggedin_user->id , $group_id ) 	
			) 
			return true;
			break;
		case 'moderator-only':
			if 
			(
				groups_is_user_admin( $bp->loggedin_user->id , $group_id )	||
				groups_is_user_mod( $bp->loggedin_user->id , $group_id ) 	
			) 
			return true;
			break;
		case 'admin-only':
			if 
			(
				groups_is_user_admin( $bp->loggedin_user->id , $group_id )	
			) 
			return true;
			break;
		default:
			return false;
	}
}



 
/**
 * Checks current user against current group and $post_id wiki meta settings.  Returns false if user not 
 * allowed to view page.  True if they are allowed.
 *
 * @param int   $post_id ID of the post
 * @param int   $group_id ID of the group
 *
 * @returns true if able to view, false if not
 */
function bpgw_user_can_view_wiki_page( $post_id, $group_id ) {

	global $bp;
	
	$bpgw_blog_id = groups_get_groupmeta( $group_id , 'wiki_blog_id' );

	// If the page is set to disabled, no edit or viewing is allowed
	if ( bpgw_get_blog_post_metadata( $bpgw_blog_id , $post_id , 'wiki_page_enabled' ) == 0 ) {
		return false;
	}
	
	$post_view_settings = bpgw_get_blog_post_metadata( $bpgw_blog_id , $post_id , 'wiki_view_access' , 1 );
	
	// If user is banned, return false straight away.  Otherwise, do the full user access checks
	if ( groups_is_user_banned( $bp->loggedin_user->id , $group_id ) ) return false;

	switch ( $post_view_settings ) {
		case 'public':
			return true;
			break;
		case 'member-only':
			if 
			(
				groups_is_user_admin( $bp->loggedin_user->id , $group_id )	||
				groups_is_user_mod( $bp->loggedin_user->id , $group_id )		||
				groups_is_user_member( $bp->loggedin_user->id , $group_id ) 	
			) 
			return true;
			break;
		default:
			return false;
	}
}

/**
 * Are comments enabled for the given post?
 *
 * @param array the wiki post to which comments may or may not be enabled.
 *
 * @return True if comments are enabled, false otherwise.
 */
function bpgw_wiki_page_comments_enabled($wiki_post) {

	if ( $wiki_post->comment_status == 'open') {
		return true;
	} else {
		return false;
	}
}

/**
 * Checks to see if user is allowed to delete a comment.  Allows admins
 * and moderators to delete comments or current user to delete own comments.
 *
 * @param array $comment The comment to check
 * @param int $group_id The group ID the logged in user belongs to
 * @return boolean True if the user is allowed to delete the comment, false otherwise.
 */
function bpgw_can_moderate_comment($comment, $group_id) {
	global $bp;
	if(!is_user_logged_in()) 
		return false;
	else
		return ( groups_is_user_admin( $bp->loggedin_user->id , $group_id )	||
				 groups_is_user_mod( $bp->loggedin_user->id , $group_id ) ||
				 $bp->loggedin_user->id == $comment->user_id );
} 



/********************************************
 *** Wiki display functions               ***
 ********************************************/ 

 
/**
 * Generates the contents list box for a displayed wiki page from header <h#> tags.
 * Intended to be used with a filter on the the_contents tag.
 * 
 * Styling taken from wordpress_wiki plugin by Dan Milward, Thomas Howard, Allen Han.
 * See: http://wordpress.org/extend/plugins/wordpress-wiki/
 *
 * Contents list building based on code from:
 * http://www.wait-till-i.com/2010/01/06/the-table-of-contents-script-my-old-nemesis/
 *
 * @param string The content of the wiki page
 *
 * @returns string The contents with a contents table placing the <div id="toc"></div> tag.
 */
function bpgw_table_of_contents($content = '') {
	// If nothing to do, return nothing
	if(empty($content)) return $content;
	
	// Prepend our content with the place holder for the toc
	$content = '<div id="toc"></div>'.$content;

	// Grab all header tags
	preg_match_all("/<h([1-6])[^>]*>.*<\/h.>/Us",$content,$headlines);
	
	// If no headlines to index, don't bother displaying a contents table.
	if(empty($headlines[0])) return $content;
	
	// The class must match the javascript show/hide parameter
	$out = '<ul class="contents-index">'; 
	
	foreach($headlines[0] as $k=>$h) {
		// DEBUGING
		//echo("LEVEL: " . $headlines[1][$k] . "<br />\n");

		// Insert IDs into header tags of content to act as anchors
		// But check for any pre-exisitng id's inside the header, try to match VERY carefully
		if(strstr($h,' id=')===false){
			$x = preg_replace('/>/',' id="head'.$k.'">',$h,1);
			$content = bpgw_str_replace_once($h,$x,$content);
			$h = $x;
		}
		 
		// Strip out any tags nested inside the header so we are left with just text 
		// (this gets rid of undesirable extras such as images which wreck the contents table)
		$h = strip_tags($h, '<h1><h2><h3><h4><h5><h6>');
		 
		// Create the link for the table of contents
		$link = preg_replace('/<(\/)?h\d/','<$1a',$h);
		$link = str_replace('id="','href="#',$link);

		 
		/*** Handling of nested lists when generating the table of contents ***/
		 
		// Add additional nesting for each higher level if the FIRST H tag isn't H1.
		if($k==0 && $headlines[1][$k] > 1){
			for($i = $headlines[1][$k]; $i>1; $i--) {
				$out.='<ul>'."\n";
			}
		}
		 
		// Add additional nesting where we jump more than one level where we aren't at first tag
		if($k>0 && ($headlines[1][$k] - $headlines[1][$k-1]) > 1) {
			for($i = $headlines[1][$k]; $i>($headlines[1][$k-1]+1); $i--) {
				$out.='<ul>'."\n";
			}
		}
		 
		// If the previous H is less than the current H start an new list
		if($k>0 && $headlines[1][$k-1]<$headlines[1][$k]){
			$out.="\n".'<ul>'."\n";
		}
		 
		// Always start a new entry with a new list item
		$out .= '<li>'.$link.'';
		 
		// If next H exists and next H is less than current H, close list item
		if($headlines[1][$k+1] && $headlines[1][$k+1]<$headlines[1][$k]){
			$out.='</li>'."\n";
			// Then close all nested lists until we reach the level of the next H 
			// (e.g. If going from H4 to H2 it will loop twice)
			$current_level = $headlines[1][$k];
			$next_level = $headlines[1][$k+1];
			for($i = $current_level; $i>$next_level; $i--) {
				$out.='</ul>'."\n".'</li>'."\n";
			}
		}
		 
		// If next H exists and next H is same level as current H, close current list item
		if($headlines[1][$k+1] && $headlines[1][$k+1] == $headlines[1][$k]) {
			$out.='</li>'."\n";
		}
		
	} // End of foreach headline loop
	
	// Always end by closing the first list item
	$out.='</li>'."\n".'</ul>'."\n";
	
	// Wrap the whole contents table in a tags for presentation
	$out = '<div class="toc"><h3>Contents</h3>'.
	'<p> &#91; <a class="show" onclick="toggle_hide_show(this, \'contents-index\')">hide</a> &#93; </p>'.
	"\n".$out.'</div>'."\n";
	
	return str_replace('<div id="toc"></div>', $out, $content);
}


	
	
/********************************************
 *** Wiki revisions functions             ***
 ********************************************/ 
	
/**
 * Gets a number of revisions for the current post or returns all 
 * if $max_revisions is set to less than zero.  For this to work correctly,
 * this must be called from 'within the loop'.
 *
 * @param int $max_revisions The number of revisions to return or -1 to return all
 *
 * @return array The revision posts.
 */
function bpgw_get_revisions($max_revisions = 5) {
	global $post;
	
	// Check for any abnomalities with current post and if so return empty array
	if (!$post = get_post($post->ID)){
		return array();
	}
	if($post->post_type == 'revision' && ($post->post_parent > 0)) {
		if(!$post = get_post($post->post_parent)) {
			return array();
		}
	}
	
	// Setup the args
	$revision_args = array(
		'numberposts' => $max_revisions, 
		'post_type' => 'revision',
		'order' => 'DESC', 
		'orderby' => 'date');
	
	if ( !$revisions = wp_get_post_revisions( $post->ID, $revision_args)) {
		return array();
	}
	return array_values($revisions);
}

/**
 * Returns an HTML title bar to display above the revisions list.
 *
 * @param array $args For specifying how to format the title bar
 *
 * @return Formated HTML containg the revisions title bar.
 */ 
function bpgw_get_revisions_titlebar( $group_id , $args = array()) {
	global $post;
	$defaults = array(
		'title_text' => 'Post Revisions',
		'restore_button' => false,
		'history_button' => false,
		'restore_link' => '',
		'restore_link_attr' => '',
		'history_link' => bpgw_get_permalink( $group_id , $post->ID ).'revision/',
		'history_link_attr' => '',		
		'echo' => false
	);
	$args = wp_parse_args( $args, $defaults );
	
	$button_html = '';
	if($args['history_button']) {
		$button_html .= 	'<div class="generic-button group-button public">'.
							'<a href="'.$args['history_link'].'" '.$args['history_link_attr'].'>'.
							'<span class="history">Revision History</span></a></div>';
	}
	if($args['restore_button']) {
		$button_html .= 	'<div class="generic-button group-button public">'.
							'<a href="'.$args['restore_link'].'" '.$args['restore_link_attr'].'>'.
							'<span class="restore">Restore Version</span></a></div>';
	}
	$output = "<div class='titlebar'><h4>{$args['title_text']}</h4>{$button_html}</div>";
	
	if($args['echo'])
		echo $output;
		
	return $output;
}


/**
 * Returns a formated list of links to revisions of the current post.  
 * By default, only the first 5 revisions are displayed.
 * Intended to be used with a filter on the the_contents tag. For this to 
 * work correctly, this must be called from 'within the loop'.
 *
 * @param int $group_id The group ID the user is part of
 * @param int $max_revisions The total number of revisions to return or -1 if 
 * all available revisions should be returned.
 *
 * @return string Content with the revisions list appended to the end.
 */
function bpgw_wiki_page_revisions_summary($group_id , $max_revisions = 5){
	global $post;

	$revisions = bpgw_get_revisions(-1);
	$revisions_total_count = count($revisions) + 1; // Add 1 for current revision
	if(empty($revisions)) {
		return $content;
	}
	
	$title_bar_args = array(
		'title_text' => 'Post Revisions Summary <span>('.$revisions_total_count.')</span>',
		'history_button' => true,
		'history_link' => bpgw_get_permalink( $group_id , $post->ID ).'revision/', 
		'echo' => false
	);
	
	// Remove revisions beyond our maximum number
	$i = 0;
	foreach ($revisions as $key => $revision) {
		if($max_revisions > 0 && $i >= $max_revisions) {
			unset($revisions[$key]);
		}
		$i++;
	}
	$revisions = array_values($revisions);
	$revisions_summary_count = count($revisions) + 1; // Add 1 for current revision
	
	// Create the HTML fragment
	$output .= "<div class='wiki-revisions'>";
	$output .= bpgw_get_revisions_titlebar( $group_id , $title_bar_args);
	if($revisions_total_count > $revisions_summary_count) {
	$output .= "<p class='wiki-meta'>Only $revisions_summary_count of $revisions_total_count revisions shown. ".
			   "Select the 'Revision History' button to view a list of all revisions.";
	}
	$output .= bpgw_format_revisions_summary_list( $group_id , $revisions);
	$output .= "</div>";

	return $content.$output;
}

/**
 * Formats a given array of revision posts into an HTML list of links.
 *
 * @param array $revisions The revision posts to format.
 *
 * @return string A HTML unordered list containing formated links to the revisions.
 */
function bpgw_format_revisions_summary_list($group_id, $revisions) {
	if(empty($revisions)) {
		return '';
	}
	
	global $post;
	$rows = '';

	foreach ($revisions as $revision) {
		$title = bpgw_format_revision_as_title($revision);
		$rows .= "\t<li>$title</li>\n";
	}	
	$link = bpgw_get_permalink( $group_id , $post->ID );
	
	$output = "<ul class='post-revisions'>\n";
	$output .= "\t<li>".bpgw_format_current_revision_as_title($group_id)." - <a href='".$link."edit/'>Edit this page</a></li>\n";
	$output .= $rows;
	$output .= "</ul>";
	return $output;
}

/**
 * Generates a formatted HTML table containing more detailed information about all revisions of a wiki page.
 *
 * @param int $group_id The group ID of the user
 * @param array $current_page The parent wiki page to generate information for
 * @param array $revisions An array of all revisions of the current page.
 *
 * @return string HTML output ready for display.
 */
function bpgw_format_revisions_as_comparison_table($group_id, $current_page, $revisions) {
	if(empty($revisions)) {
		return '';
	}
	
	// Get details of the current revision
	$revisionRows = bpgw_get_revision_table_row($group_id, $current_page, $revisions, $current_page);
	// Get details of all other revisions
	foreach ($revisions as $revision) {	
		$revisionRows .= bpgw_get_revision_table_row($group_id, $current_page, $revisions, $revision);
	}	
	
	// Build the table
	$output .= '<table class="revisions-compare">'.$new_line;
	$output .= '<thead>'.$new_line;
	$output .= '<tr>'.$new_line;
	$output .= '<th colspan="2" class="col-radio"></th>'.$new_line;
	$output .= '<th class="col-revision">Revision Date</th>'.$new_line;
	$output .= '<th class="col-author">Author</th>'.$new_line;
	$output .= '<th class="col-notes">Notes</th>'.$new_line;
	$output .= '</tr>'.$new_line;
	$output .= '</thead>'.$new_line;
	$output .= '<tbody>'.$new_line;
	$output .= $revisionRows;
	$output .= '</tbody>'.$new_line;
	$output .= '</table>'.$new_line;
	
	return $output;
}
/**
 * Generates a HTML table row containing information about a specific wiki page revision.
 *
 * @see bpgw_format_revisions_as_comparison_table();
 * @param int $group_id The group ID of the user
 * @param array $current_page The parent wiki page to generate information for
 * @param array $revisions An array of all revisions of the current page.
 * @param array $target_revision The revision to generate information about
 *
 * @return string HTML table row output.
 */
function bpgw_get_revision_table_row($group_id, $current_page, $revisions, $target_revision) {
	// Declared for convenience
	$new_line = "\n";
	$page_id = $current_page->ID;
	$revision_id = $target_revision->ID;
	
	// Create data for current page
	$post_reference = bpgw_wiki_revision_date_reference($target_revision, true);
	$post_author = bp_core_get_userlink($target_revision->post_author);
	$version_num = bpgw_get_version_number($current_page, $revisions, $revision_id);
	$notes = "Version ".$version_num;
	
	if($page_id == $revision_id)
		$notes .= " [ Current revision ]";
	else if($version_num == 1) 
		$notes .= " [ Initial version ]";
	
	// Get any restore history to add to the notes
	$restored_from_id = bpgw_get_restored_revision_source_id($page_id, $revision_id);
	if(isset($restored_from_id)) {
		$baseLink = bpgw_get_permalink($group_id, $page_id);
		$restored_from_link = $baseLink."revision/$restored_from_id#item-nav";
		$restored_from_link = bpgw_get_version_number($current_page, $revisions, $restored_from_id);
		$notes .= " - Restored from <a href='$restored_from_link'>version $restored_from_link</a>";
	} 
	
	// Create the table row
	$alt_style = '';
	// Need to ensure alt style is added based on count from first table row
	if((count($revisions)-($version_num-1)) %2 == 0) {
		$alt_style = ' class="alt"';
	}
	$new_line = "\n";
	$output = "<tr$alt_style>".$new_line;
	$output .= '<td class="col-radio"><input type="radio" name="left" value="'.$revision_id.'" /></td>'.$new_line;
	$output .= '<td class="col-radio"><input type="radio" name="right" value="'.$revision_id.'" /></td>'.$new_line;
	$output .= '<td class="col-revision">'.$post_reference.'</td>'.$new_line;
	$output .= '<td class="col-author">'.$post_author.'</td>'.$new_line;
	$output .= '<td class="col-notes">'.$notes.'</td>'.$new_line;
	$output .= '</tr>'.$new_line;
	
	return $output;
}



/**
 * Gets the sequential version number of a revision from a set of revisions.  
 * This number is based on the order the revisions in the given array.  Lower positions
 * in the revisions array are assumed to be older and therefore revision[0] is version 1.
 * The current revision is assumed to be one more than the number of revisions.
 *
 * @param array $current_page The parent wiki page to generate information for
 * @param array $revisions An array of all revisions of the current page.
 * @param array $target_revision_id The ID of the revision to get the version number for
 *
 * @return int A whole number representing the sequential position of the target revision.
 */
$revision_to_version_map = null;
function bpgw_get_version_number($current_page, $revisions, $target_revision_id = null) {
	if($revision_to_version_map == null) {
		$version_num = count($revisions);
		$revision_to_version_map = array();
		// Add in current version
		$revision_to_version_map["".$current_page->ID] = $version_num+1;
		// Add in all revision versions
		foreach ($revisions as $revision) {
			$revision_to_version_map["".$revision->ID] = $version_num;
			$version_num--;
		}
	}
	if($target_revision_id == null)
		return $revision_to_version_map[$current_page->ID];
	else
		return $revision_to_version_map[$target_revision_id];
}

/**
 * Looks for any restored version information for a given wiki page revision to check 
 * if a given revision is a restored copy of another revision.
 *
 * @param int $page_id The parent wiki page to check the restore history for
 * @param int $revision_id The ID of the revision to check if it is a restored 
 * copy of another revision.
 *
 * @return int The ID of the revision the given revision was restored from 
 * or null if nothings was found.
 */
$restore_points_map = null;
function bpgw_get_restored_revision_source_id($page_id, $revision_id) {
	if($restore_points_map == null) {
		$restore_meta = get_post_meta($page_id, WIKI_M_PAGE_RESTORED, false);
		$restore_points_map = array();
		foreach($restore_meta as $key=>$value) {
			$restore_pair = explode('-', $value, 2);
			$restore_points_map["".$restore_pair[0]] = $restore_pair[1];
		}
	}
	return $restore_points_map[$revision_id];
}


/**
 * Validates whether a page being used in a wiki comparison is valid.
 * Valid pages are (1) The current page revision itself, or (2) any
 * revision of the current page i.e. its parent is the current page.
 *
 * @param array $current_page The current page of the wiki.
 * @param array $revision_for_compare The candidate revision in the compare.
 * @return boolean True if valid, false otherwise.
 */
function bpgw_validate_revision_for_compare($current_page,$revision_for_compare) {
	if( $revision_for_compare == null || empty($revision_for_compare)) {
		// No post retrieved
		return false;
	}
	if($current_page->ID == $revision_for_compare->ID) {
		// The revision IS the current page so must be OK
		return true;
	}
	if( $revision_for_compare->post_type == 'revision' && 
		$revision_for_compare->post_parent == $current_page->ID) {
			// Revision belongs to current page
			return true;
	}
	// Assume all other cases are false
	return false;
}	


/**
 * Formats a given revision post into an HTML title which can be optionally 
 * hyperlinked to the revision text.
 *
 * @param array $revision The revision post to format into a title.
 * @param boolean $hyperlink Whether or not to make the title 
 * hyperlinked to the revision text.
 *
 * @return string A HTML title for the revision.
 */
function bpgw_format_revision_as_title($revision, $hyperlink = true) {
	$title_f = _c( '%1$s by %2$s|post revision 1:datetime, 2:name' );
	$date = bpgw_wiki_revision_reference($revision, $hyperlink);
	if($hyperlink) {
		$name = bp_core_get_userlink($revision->post_author);
	}
	else {
		$userdata = get_userdata($revision->post_author);
		$name = $userdata->display_name;
	}
	
	return sprintf( $title_f, $date, $name );
}
/**
 * Formats the current post into an HTML title which can be optionally 
 * hyperlinked to the revision text.  For this to work correctly,
 * this must be called from 'within the loop'.
 *
 * @param boolean $hyperlink Whether or not to make the title 
 * hyperlinked to the revision text.
 *
 * @return string A HTML title for the revision.
 */ 
function bpgw_format_current_revision_as_title($group_id, $hyperlink = true) {
	global $post;
	$link = bpgw_get_permalink($group_id, $post->ID);
	$name = bp_core_get_userlink($post->post_author);
		
	$output = 'Current revision';
	if($hyperlink) {
		$output = "<a href='".$link."'>".$output."</a>";
	}
	$output .= " by ".$name;

	return $output;
}


/**
 * Formats the title/link of a wiki revision page reference as a version date.
 *
 * Taken from wordpress_wiki plugin by Dan Milward, Thomas Howard, Allen Han.
 * See: http://wordpress.org/extend/plugins/wordpress-wiki/
 *
 * @param array $revision The revision post the generate a title/link for.
 * @param boolean $hyperlink Whether or not to format text as a hyperlink to 
 * display the wiki revision.  Defaults to true.
 *
 * @return A formated HTML link to the revision post.
 */
function bpgw_wiki_revision_reference($revision, $hyperlink = true) {
	if (!$revision = get_post($revision))
		return $revision;

	if (!in_array($revision->post_type, array( 'post', 'page', 'revision' )))
		return false;

	$autosavef = __( '%s [Autosave]' );
	$currentf	= __( '%s [Current Revision]' );
	$date = bpgw_wiki_revision_date_reference($revision, $hyperlink);

	if ( !wp_is_post_revision( $revision ) )
		$date = sprintf( $currentf, $date );
	elseif ( wp_is_post_autosave( $revision ) )
		$date = sprintf( $autosavef, $date );

	return $date;
}

/**
 * Returns the formated date of the revision optionally formatted as an HTML 
 * link to the revision itself.
 *
 * @param array $revision The revision in question.
 * @param boolean $hyperlink True if returned HTML should be as a hyperlink 
 * to the revision itself, false otherwise.
 *
 * @return string The revision date stamp as HTML.
 */
function bpgw_wiki_revision_date_reference($revision, $hyperlink = true) {
	global $bp;
	$group_id = $bp->groups->current_group->id;
	
	$date = bpgw_to_wiki_date_format($revision->post_modified_gmt . ' +0000' );
	if($revision->post_parent == 0){
		$post_id = $revision->ID;
	} else {
		$post_id = $revision->post_parent;
	}
	$link = bpgw_get_permalink($group_id, $post_id)."revision/".$revision->ID;
	if($hyperlink)
		$date = "<a href='$link#item-nav'>$date</a>";
	return $date;
}

/**
 * Carries over any metadata information on a restored version of a wiki page from the
 * current version of the wiki page, to the most recent revision.  This function is
 * intended to be called immediated after any new wiki page revision is created so that
 * the restore infomation is passed onto the new revision to maintain a correct history
 * of restore points.
 *
 * @param int The post ID of the wiki page which retore info should be copied onwards to the
 * next most recent revision version.
 */
function bpgw_carry_over_restore_postmeta($post_id) {
	// Check to see if the current version of the page has a restore flag
	$restored_from_id = bpgw_get_restored_revision_source_id($post_id, $post_id);

	// Do we need to copy across any restore point history into the meta data
	if(!empty($restored_from_id)) {
	
		// Immediately grab the next lastest revision which should be what previously was the current version
		$revisions = wp_get_post_revisions($post_id, array('numberposts' => 1, 'post_type' => 'revision',
														  'order' => 'DESC', 'orderby' => 'date'));
		if(!empty($revisions)) {
			$latest_revision_id = $revisions[key($revisions)]->ID;
			// Delete the old metadata arecord for the current post
			delete_post_meta($post_id, WIKI_M_PAGE_RESTORED, $post_id.'-'.$restored_from_id); 
			// Update the post meta restore history to assign restored ID to the new revision
			add_post_meta($post_id, WIKI_M_PAGE_RESTORED, $latest_revision_id.'-'.$restored_from_id); 
		}
	}	
}




/********************************************
 *** Wiki comments functions              ***
 ********************************************/ 
 

 /**
 * Gets the comments attached to the given wiki page that have given status or all 
 * comments if no status is provided.
 *
 * @param string $status Only return comments with this status.
 * 'hold' - unapproved comments
 * 'approve' - approved comments
 * 'spam' - spam comments
 * [empty value] all comments.
 * 
 * @return An array of any comments.
 */
 function bpgw_get_wiki_comments($wiki_page, $status = '') {

	if($wiki_page->post_type == 'revision' && ($post->post_parent > 0)) {
		if(!$wiki_page = get_post( $wiki_page->post_parent )) {
			return array();
		}
	}
	$args = array(
    'status' => $status,
    'orderby' => 'comment_date_gmt',
    'order' => 'DESC',
    'post_id' => $wiki_page->ID);
	if ( !$comments = get_comments($args)) {
		return array();
	}
	return $comments;
}



 /**
 * Returns an HTML title bar to display above the comments list.
 *
 * @param int $wiki_page_id The wiki page that is commented.
 * @param int $comments_count The number of comments being displayed or 
 * blank if no comments count should be displayed.
 * @param string $title_text The text to insert at the title in the title bar.  Defaults to "Comments".
 *
 * @return Formated HTML containg the revisions title bar.
 */ 
function bpgw_get_comments_titlebar($wiki_page_id, $comments_count = '', $title_text = 'Comments') {
	if(is_numeric($comments_count)) {
		$comments_count = 	'<span>('.$comments_count.')</span>';
	}
	return "<div class='titlebar'><h4>{$title_text} {$comments_count}</h4></div>";
}
 
 
 /**
 * Formats a given array of comments posts into a pretty HTML list which uses 
 * pagination information stored in the page URL.
 *
 * @param array $comments The comments  to format.
 * @param int $items_per_page The number of comments to display 
 * @param int $group_id The group ID the wiki belongs to.  Used to determine 
 * permission associated with options permitted on specific comments.
 *
 * @return string A paged HTML unordered list containing formated comments.
 */
function bpgw_format_comments_as_list($comments, $items_per_page, $group_id) {
	if(empty($comments)) {
		return '';
	}
	
	$total_pages = bpgw_wiki_get_total_pages_count($comments, $items_per_page);
	$current_page = bpgw_wiki_get_comments_page_from_url($total_pages);
	$comments = bpgw_wiki_get_page_of_items($comments, $current_page, $items_per_page);
	
	$rows = '';
	$i = 0;
	foreach ($comments as $comment) {
		$odd_even = ($i%2) ? "odd" : "even";
		$id = $comment->comment_ID ;
		$user_id = $comment->user_id;
		$formated_comment = bpgw_format_comment($comment, $group_id);
		$rows .= "\t<li id='comment-{$id}' class='comment byuser comment-author-{$user_id} {$odd_even} alt'>{$formated_comment}</li>\n";
		$i++;
	}	
	
	$output = "<div id='comments'><ul class='commentlist'>\n";
	$output .= $rows;
	$output .= "</ul></div>";
	return $output;
}

/** 
 * Determines the current page of comments to be displayed based on the URL.  If the URL 
 * does not obey a predermined syntax, this will default to either the first or last page
 * of comments.
 *
 * @param int The total number of pages that are possible for the number of 
 * comments that exist for the wiki page.
 *
 * @return int The page number of the comments to be displayed.
 */
function bpgw_wiki_get_comments_page_from_url($total_pages) {
	global $bp;
	$wiki_action_var = $bp->action_variables[1];
	$comments_paged_prefix = "comment-page-";
	
	if(bpgw_string_begins_with($wiki_action_var, $comments_paged_prefix)) {
		$page_number = substr($wiki_action_var, strlen($comments_paged_prefix));

		// Sanity check the parsed value
		if(!is_numeric($page_number) || $page_number < 0) return 1; // Default to first page
		if($page_number > $total_pages) return $total_pages; // Default to last page
		
		return $page_number;
	}
	// Nothing viable found so default to first page
	return 1;
}

/**
 * Formats an individual comment with HTML markup for display.
 * 
 * @param $comment The comment to format.
 *
 * @return string HTML formated content.
 */
function bpgw_format_comment($comment, $group_id) {
	global $bp;
	$comment_id = $comment->comment_ID;
	$author_id = $comment->user_id;
	$comment_time_stamp = $comment->comment_date;
	
	$user_data = get_userdata($author_id);
	$user_display_name = $user_data->display_name;
	$user_profile_link = bp_core_get_userlink($author_id);
	
	$post_time_stamp = bpgw_to_wiki_date_format($comment_time_stamp);
	$comment_admin_options = bpgw_get_comment_moderator_options($comment, $group_id);
	
	$output = "<div id='div-comment-$comment_id' class='comment-body'>";
	$output .= "<div class='columns-two'>\n<div class='left'>\n";
	$output .= "<div class='comment-author vcard'>";
	$output .= get_avatar($author_id, 16, $default, $user_display_name);
	$output .= "<cite class='fn'>$user_profile_link</cite> <span class='says'>says:</span></div>";
	$output .= "</div>\n";
	$output .= "<div class='right'>\n";
	$output .= "<div class='comment-date'>$post_time_stamp</div>";
	$output .= $comment_admin_options;
	$output .= "</div>\n</div>\n";
	$output .= convert_smilies($comment->comment_content);
	$output .= "</div>";

	return $output;
}

/** 
 * Gets the HTML markup for any comment moderator options that the current user is allowed to perform.
 *
 * @param array The comment to get moderator options for.
 * @param int The group ID the wiki belongs to.
 *
 * @return string The HTML string for any moderator option controls the user has or an empty string if
 * none are permitted for the given comment.
 */
function bpgw_get_comment_moderator_options($comment, $group_id) {
	global $bp;
	
	$comment_id = $comment->comment_ID;
	$options_html = '';
	// Allow admins, mods or current user to delete own comments
	if(!bpgw_can_moderate_comment($comment, $group_id)) {
		return $options_html;
	}
	$deleted_comment_list = bpgw_get_deleted_comment_ids($group_id);
	if(in_array($comment_id, $deleted_comment_list)) {
		return $options_html;
	}
	$delete_confirm = 'return confirm(\''.
					 'Really delete this comment?\n\n'.
					 'To proceed with deleting this comment, please select OK to confirm.\')';
	
	$options_html .= '<div class="comment-options">';
	$options_html .= '<form id="comment-mod-form" class="standard-form" method="post" action="'. WIKI_S_COMMENT_OPTIONS .'">';
	if ( function_exists('wp_nonce_field') )
		$options_html .= wp_nonce_field('wiki_comment_options');
	$options_html .= '<input type="hidden" id="group_id" name="group_id" value="'.$group_id.'" />';
	$options_html .= '<input type="hidden" id="post_id" name="post_id" value="'.$comment->comment_post_ID.'" />';
	$options_html .= '<input type="hidden" id="comment_id" name="comment_id" value="'.$comment_id.'"/>';
	$options_html .= '<input id="comment-delete" class="comment-delete button" '.
						 'type="submit" value="Delete" name="submit" onclick="'.$delete_confirm.'" />';
	$options_html .= '</form>';
	$options_html .= '</div>';
	
	return $options_html;
}

/**
 * Inserts commenta meta data into the comments meta data table.  This function is necessary at time
 * of writing as it is missing from the WordPress/BuddyPress APIs.
 *
 * @param int $bpgw_blog_id The blog ID the wiki belongs to.
 * @param int $comment_id The ID of the comment to add metadata about
 * @param string $meta_key The key to be used to lookup the metadata value
 * @param string $meta_value The value to be stored against the metadata key.
 * @param boolean $unique Optional, default is false. Whether the same key should not be added.
 *
 * @return boolean False for failure. True for success.
 */
function bpgw_add_comment_meta($bpgw_blog_id, $comment_id, $meta_key, $meta_value, $unique = false) {
	if ( !$meta_key )
		return false;

	global $wpdb;

	// expected_slashed ($meta_key)
	$meta_key = stripslashes($meta_key);

	if ( $unique && $wpdb->get_var( $wpdb->prepare( "SELECT meta_key ".
													"FROM {$wpdb->base_prefix}%d_commentmeta ".
													"WHERE meta_key = %s AND ".
													"comment_id = %d", 
													$bpgw_blog_id, $meta_key, $comment_id ) ) ) {
		return false;
	}

	$meta_value = maybe_serialize( stripslashes_deep($meta_value) );

	$sql = $wpdb->prepare( "INSERT INTO {$wpdb->base_prefix}%d_commentmeta ".
						   "( comment_id, meta_key, meta_value ) ".
						   "VALUES ( %d, %s, %s )", 
						   $bpgw_blog_id, $comment_id, $meta_key, $meta_value );
	$wpdb->query($sql);
	wp_cache_delete($comment_id, 'comment_meta');

	return true;
}

/**
 * Checks the comment meta data to see if there are any records of deleted comments 
 * in the wiki for the current group.
 * 
 * @param int $group_id The group ID for the wiki.
 *
 * @return array A list of IDs for deleted comments in the current wiki
 */
$_deleted_comment_ids = null; // Should really use wp cache
function bpgw_get_deleted_comment_ids($group_id) {
	if($_deleted_comment_ids == null) {
		global $wpdb;
		$bpgw_blog_id = groups_get_groupmeta( $group_id , 'wiki_blog_id' );
		$rows = $wpdb->get_results( $wpdb->prepare("SELECT comment_id ".
												"FROM {$wpdb->base_prefix}%d_commentmeta ".
												"WHERE meta_key = %s ",
												$bpgw_blog_id, WIKI_M_COMMENT_DELETED_CONTENT ), ARRAY_A ); 
		$_deleted_comment_ids = array();
		if(empty($rows)) {
			return $_deleted_comment_ids;
		}
		// Otherwise, populated with what we pulled out the database
		$i = 0;
		foreach($rows as $k=>$id) {
			$_deleted_comment_ids[$i] = $id['comment_id'];
			$i++;
		}
	}
	return $_deleted_comment_ids;
}

/**
 * Returns the HTML for pagination links for a series of comments.
 * 
 * @param string $base_uri The URI for the current page.
 * @param array $comments An array of the comments to be paged.
 * @param int $comments_per_page The number of comments that should appear per page.
 * @param array $args Optional.  An array of arguments to be passed into 
 * the WordPress paginate_links function which this uses.
 *
 * @return string The HTML markup of the pagination links.
 * @uses paginate_links() A WordPress helper function to handle the pagination.
 */
function bpgw_paginate_comment_links($base_uri, $comments, $comments_per_page, $args = array()) {
	$total_page_count = bpgw_wiki_get_total_pages_count($comments, $comments_per_page);
	$defaults = array(
		'base' => $base_uri,
		'format' => '',
		'total' => $total_page_count, // total number of pages to output
		'current' => bpgw_wiki_get_comments_page_from_url($total_page_count), 
		'echo' => true,
		'add_fragment' => '#comments'
	);
	$defaults['base'] = user_trailingslashit(trailingslashit($base_uri) . 'comment-page-%#%', 'commentpaged');

	$args = wp_parse_args( $args, $defaults );
	$page_links = paginate_links( $args );
		
	if ( $args['echo'] )
		echo $page_links;
	else
		return $page_links;
	
}



/********************************************
 *** Activity Stream Functions              ***
 ********************************************/ 
 


/**
 * Word-safe and tag-safe string truncation
 *
 * @param string    $text The original string
 * @param int       $len The maximum length of the string (might be extended to avoid word/tag truncation)
 *
 * @return string   $text The truncated string
 */
function bpgw_substrws( $text, $len=180 ) {

	if( (strlen($text) > $len) ) {
		$white_space_position = strpos($text," ",$len)-1;
		if( $white_space_position > 0 )
			$text = substr($text, 0, ($white_space_position+1));
			// close unclosed html tags
			if( preg_match_all("|<([a-zA-Z]+)>|",$text,$a_buffer) ) {
				if( !empty($a_buffer[1]) ) {
					preg_match_all("|</([a-zA-Z]+)>|",$text,$a_buffer_2);
					if( count($a_buffer[1]) != count($a_buffer_2[1]) ) {
						foreach( $a_buffer[1] as $index => $tag ) {
							if( empty($a_buffer_2[1][$index]) || $a_buffer_2[1][$index] != $tag)
								$text .= '</'.$tag.'>';
						}
					}
				}
			}
		}

	return $text;
} 



/**
 * Mimics the BP activity recording functionality but allows for additional control in regards to the viewability of
 * the recorded activity.  Hide sitewide is set before calling this function via the $args.
 *
 * @param array     $args The activity to be recorded
 *
 * @return bool     bp_activity_add() True or false depending on whether or not recording was successful
 */
function bpgw_wiki_groups_record_activity( $args = '' ) {
	global $bp;

	if ( !function_exists( 'bp_activity_add' ) )
		return false;

	$defaults = array(
		'user_id' => $bp->loggedin_user->id,
		'content' => false,
		'action' => false,
		'primary_link' => false,
		'component' => 'groups',
		'component_name' => $bp->groups->id,
		'component_action' => false,
		'type' => false,
		'item_id' => false,
		'secondary_item_id' => false,
		'recorded_time' => gmdate( "Y-m-d H:i:s" ),
		'hide_sitewide' => $hide_sitewide
	);

	$r = wp_parse_args( $args, $defaults );
	extract( $r, EXTR_SKIP );

	return bp_activity_add( array( 'user_id' => $user_id, 'content' => $content, 'action' => $action, 'primary_link' => $primary_link, 'component' => 'groups', 'component_name' => $component_name, 'type' => $type, 'component_action' => $component_action, 'item_id' => $item_id, 'secondary_item_id' => $secondary_item_id, 'recorded_time' => $recorded_time, 'hide_sitewide' => $hide_sitewide ) );
}



/**
 * Returns a 'permalink' to a wiki page based on the group slug + page slug.
 *
 * @param int       $group_id ID of the group we're looking at
 * @param int       $post_id ID of the page of the wiki we want the link to
 *
 * @return string   $wiki_post_url The full URL to the wiki page requested
 */
function bpgw_get_permalink($group_id , $post_id) {
	global $bp;
	
	$wiki_group = new BP_Groups_Group( $group_id , false , false );	 	
	$wiki_url = bp_get_group_permalink ( $wiki_group ); 				
	$bpgw_blog_id = groups_get_groupmeta( $group_id, 'wiki_blog_id' ); 		
	$wiki_post = get_blog_post ( $bpgw_blog_id, $post_id ); 				
	$wiki_post_url = $wiki_url . 'wiki/' . $wiki_post->post_name . '/';	
	
	return $wiki_post_url; 											
}


/**
 * Returns the buddypress group id for the currently viewed wiki blog
 *
 * @return string     $group_meta['group_id'] The group ID for the blog/wiki being viewed
 */
function bpgw_get_group_id_for_current_blog() {
	global $wpdb, $blog_id, $bp;

	$meta_key = 'wiki_blog_id';
	$group_meta = $wpdb->get_row( $wpdb->prepare( "SELECT group_id FROM " . $bp->groups->table_name_groupmeta . " WHERE meta_key = %s AND meta_value = %d", $meta_key , $blog_id ) , ARRAY_A ); 

	return $group_meta['group_id'];
}



/**
 * This function is added as an action during the WP admin_init action.
 * It performs two functions:
 * 1. Kills access to the wp-admin backend for a wiki blog
 * 2. Grants file upload rights to users based on their edit rights on a particular wiki
 */
function bpgw_wp_admin_overrides() {
	global $current_user, $bp, $pagenow;
	
	if ( get_option( 'this_is_a_wiki_blog' , 0 ) == 1 ) { 
		$group_id = bpgw_get_group_id_for_current_blog();
		$user_can_edit_this_page = bpgw_user_can_edit_wiki_page( $_REQUEST['post_id'] , $group_id ); 		
		if ( $user_can_edit_this_page && ( $pagenow == 'media-upload.php' || $pagenow == 'async-upload.php' ) ) {	
				$current_user->allcaps['upload_files'] = true; 
			} else {
				die ( "No access" ); 
			}
	}
}
add_action('admin_init', 'bpgw_wp_admin_overrides');




/**
 * Get metadata for a specific blog/post without using 'the enemy' - switch_to_blog
 * Based on get_blog_post (wpmu function)
 * NOTE: Assumes that this is a unique meta key!
 *
 * @return object containing meta_value for requested meta_data
 * @uses "bpgw_get_blog_post_metadata($args)->meta_value" to get the meta value that was got
 */
function bpgw_get_blog_post_metadata( $bpgw_blog_id, $post_id, $meta_key ) {
	global $wpdb;

	$key = $bpgw_blog_id."-".$post_id."-".$meta_key."-blog_post_meta"; 
	$meta_value = wp_cache_get( $key, "bpgw-post-meta" );
	if( $meta_value == false ) { // If there's nothing for it in the cache do a db lookup
		$meta_value = $wpdb->get_row( $wpdb->prepare("SELECT meta_value FROM {$wpdb->base_prefix}%d_postmeta WHERE post_id = %d && meta_key= %s", $bpgw_blog_id, $post_id,  $meta_key ) ); 
		wp_cache_add( $key, $meta_value, "bpgw-post-meta", 120 ); // Expires from cache in 120 seconds
	}

	return $meta_value->meta_value;
}



/**
 * This function is added as a filter to the WP query_vars action.  This is needed to enable support for 
 * our own form variables for submission via post/get.
 *
 * @param array     $public_query_vars WP's internal array of get/post vars to be parsed during page load
 *
 * @return array    $public_query_vars The modified array of permitted get/post vars
 */
add_filter('query_vars', 'bpgw_form_vars');
function bpgw_form_vars($public_query_vars) {

	$public_query_vars[] = 'bpgw_group_id';
	$public_query_vars[] = 'bpgw_post_id';
	$public_query_vars[] = 'wiki_edit_lock';
	$public_query_vars[] = 'wiki_frontend_page_create';	$public_query_vars[] = 'bpgw_newpage_title';		$public_query_vars[] = 'bpgw_clean_blog_lists';

	return ($public_query_vars);
}






/** 
 * Called at the start of any of our form submission catching functions.  This populates our bpgw_form_vars
 * with data in a form easily accessible by our functions.
 *
 * @return array  $bpgw_form_vars An array of all the form variables we need
 */
function bpgw_get_form_vars(){  
    global $bpgw_form_vars;  
	
    if(get_query_var('bpgw_group_id')) {  
        $bpgw_form_vars['group_id'] = mysql_real_escape_string(get_query_var('bpgw_group_id'));  
    }
	
    if(get_query_var('bpgw_post_id')) {  
        $bpgw_form_vars['post_id'] = mysql_real_escape_string(get_query_var('bpgw_post_id'));  
    }
	
    if(get_query_var('wiki_edit_lock')) {  
        $bpgw_form_vars['wiki_edit_lock'] = mysql_real_escape_string(get_query_var('wiki_edit_lock'));  
    }
	
    if(get_query_var('wiki_frontend_page_create')) {  
        $bpgw_form_vars['wiki_frontend_page_create'] = mysql_real_escape_string(get_query_var('wiki_frontend_page_create'));  
    }
	    if(get_query_var('bpgw_newpage_title')) {          $bpgw_form_vars['bpgw_newpage_title'] = mysql_real_escape_string(get_query_var('bpgw_newpage_title'));      }	    if(get_query_var('bpgw_clean_blog_lists')) {          $bpgw_form_vars['bpgw_clean_blog_lists'] = mysql_real_escape_string(get_query_var('bpgw_clean_blog_lists'));      }	
    return $bpgw_form_vars;  
} 
// Dodgy function to allow cleanup of group wikis showing in blog lists
add_action( 'wp_ajax_bpgw_clean_blog_lists', 'bpgw_clean_blog_lists' );function bpgw_clean_blog_lists() {    global $bp, $wpdb;		$group_wiki_blog_list = $wpdb->get_results( "SELECT meta_value FROM {$bp->groups->table_name_groupmeta} WHERE meta_key = 'wiki_blog_id'" );		if ( $group_wiki_blog_list ) {				foreach ( $group_wiki_blog_list as $bpgw_blog ) {					$wpdb->query( $wpdb->prepare( "DELETE FROM {$bp->blogs->table_name} WHERE blog_id = %d", $bpgw_blog->meta_value ) );						}			}		return false;
}
/** 
 * This function is hooked into the WP template_redirect action and checks to see if the current request
 * is a "editing now" form submission.
 * The function is called by an ajax form POST every 10 seconds whilst a user is editing a wiki page
 * Updates the post meta with an entry showing the time of edit and user id/name (with a timeout of 15 seconds)
 * This will mean that after 15 seconds of not viewing the edit page the person will not appear in the 
 * 'also editing this document' list.
 *
 * @output string  $content Details on who else is editing this post and warnings if other saves are detected
 */
add_action('template_redirect', 'bpgw_editing_now');  
function bpgw_editing_now() {
    global $bpgw_form_vars, $bp, $wpdb;
	bpgw_get_form_vars();  
	if ( $bpgw_form_vars['group_id'] && $bpgw_form_vars['post_id'] && $bpgw_form_vars['wiki_edit_lock'] ) {
		/* IMPORTANT - WE NEED TO STICK A NONCE VERIFY HERE!!!! */
		$group_id = $bpgw_form_vars['group_id'];
		$post_id  = $bpgw_form_vars['post_id'];
		$bpgw_blog_id  = groups_get_groupmeta( $group_id , 'wiki_blog_id' );
		
		if ( bpgw_user_can_edit_wiki_page( $post_id, $group_id ) ) {
			$row_already_exists = 0;
			$output = '';
			// Add this person to the cache/list of people editing the wikipage, echo the list
			$post_edit_data = $wpdb->get_results( $wpdb->prepare("SELECT meta_id, meta_value FROM {$wpdb->base_prefix}%d_postmeta WHERE post_id = %d && meta_key = 'bpgw_edit_lock'", $bpgw_blog_id, $post_id ) , ARRAY_A );
			if ( $post_edit_data ) {
				$first_output=true;
				foreach ( $post_edit_data as $post_edit ) {
		//explode the string with &
		//$edit_data['meta_value'][0]==user_id, $edit_data['meta_value'][1]==time_stamp, 
		//$edit_data['meta_value'][2]==Authorname, $edit_data['meta_value'][3]==warningflag (usually not set)
		//if the time is within 15 seconds of now add them to the $output string		
		// when save, set other people's warningflag to zero
		// do check when user id is current user id - if warningflag is set and time_stamp <30s ago, this post has been saved
		// also do a check - if time_stamp >120, delete this row
					$edit_data = explode( "&" , $post_edit['meta_value'] );
					if ( $edit_data[1] != $bp->loggedin_user->id ) { // If this wasn't the current user ...
						if ( time() - $edit_data[2] < 15 ) { // ...AND time less than 15 seconds ago, add them to the list of people editing
							if ( $first_output ) {
								$output = '<div class="editing-now">Also editing this page: </div>';
								$first_output = false;
							}
							$output .= '<div class="author-editing-now">' . $edit_data[3] . '</div>';
						}
					} else {
						// If there's an entry for the current user we'll need to update the entry rather than create a new one
						$row_already_exists = $post_edit['meta_id']; 
						// Check to see if the warning flag is set for this user and if their current time_stamp is within last 15 seconds
						if ( ( time() - $edit_data[2] < 15 ) && ( $edit_data[4] == 1 ) ) { 
							$output .= '<div id="page-save-warning"></div>';
						}						
					}
				}
				if(!empty($output)) {
					$output = '<div id="editing-now-warnings">'.$output.
							  '<div class="edit-now-advice"><strong>Warning:</strong> If another user saves after you, they may overwrite your changes.</div></div>';
				}
				echo $output; // echo out the author names
			}
		// Now we need to update the meta data to reflect the fact that the user is editing this wiki page
		if ( $row_already_exists ) { // There's already an entry for this user so we need to update it
			$meta_value = '&' . $bp->loggedin_user->id . '&' . time() . '&' . $bp->loggedin_user->fullname;
			$wpdb->query( $wpdb->prepare( "	UPDATE {$wpdb->base_prefix}%d_postmeta
											SET meta_value = '%s'
											WHERE meta_id = %d", 
												$bpgw_blog_id, $meta_value, $row_already_exists ) );
		
		} else { // There is no previous entry for the user so we can create a new one
			$meta_value = '&' . $bp->loggedin_user->id . '&' . time() . '&' . $bp->loggedin_user->fullname;
			$wpdb->query( $wpdb->prepare( "	INSERT INTO {$wpdb->base_prefix}%d_postmeta
											( post_id, meta_key, meta_value )
											VALUES ( %d, 'bpgw_edit_lock', %s )", 
												$bpgw_blog_id, $post_id, $meta_value ) );
		}
		die; // kill the process so the rest of WP doesn't load
		}
	} // If we get to this point the page request isn't our submitted form.  The rest of WP will load normally now
}




/**
 * This function is hooked into the WP template_redirect action and checks to see if the current request
 * is a form submit for frontend page creation. If it is, this catches the form data and uses it to create 
 * that page.
 *
 * @die on completion
 */
add_action('template_redirect', 'bpgw_frontend_page_create');  
function bpgw_frontend_page_create() {
    global $bpgw_form_vars, $bp, $wpdb;
	bpgw_get_form_vars();  
	if ( $bpgw_form_vars['group_id'] && $bpgw_form_vars['bpgw_newpage_title'] && $bpgw_form_vars['wiki_frontend_page_create'] ) {
		/* IMPORTANT - WE NEED TO STICK A NONCE VERIFY HERE!!!! */
		$group_id = $bpgw_form_vars['group_id'];
		$bpgw_blog_id  = groups_get_groupmeta( $group_id , 'wiki_blog_id' );
		if ( groups_get_groupmeta( $group_id , 'wiki_member_page_create' ) == 1 && groups_is_user_member( $bp->loggedin_user->id , $group_id ) ) {
				switch_to_blog($bpgw_blog_id);
				// Create post object
				$wiki_post = array(
					'post_content' => '', //The full text of the post.
					'post_excerpt' => '', //For all your post excerpt needs.
					'post_status' => 'publish', //Set the status of the new post
					'post_title' => 'Post Title', //The title of the post
					'post_type' => 'page', //Wiki only uses pages, not posts
					'menu_order' => '0' //Order page will appear in nav menu - lower number appears first
				); 
				$wiki_post['post_title'] = mysql_real_escape_string($bpgw_form_vars['bpgw_newpage_title']);
				$wiki_post['menu_order'] = 9999; // work around to make the page appear at the end of the page listing
				// insert the wiki page
				$wikipost_id = wp_insert_post( $wiki_post );
				// Set the meta data for the page to control view/edit access
				add_post_meta( $wikipost_id , 'wiki_view_access' , 'public' , 1 );
				add_post_meta( $wikipost_id , 'wiki_edit_access' , 'all-members' , 1 );
				add_post_meta( $wikipost_id , 'wiki_page_enabled' , 1 , 1 );
				echo 'done';
		}
		die; // kill the process so the rest of WP doesn't load
	} // If we get to this point the page request isn't our submitted form.  The rest of WP will load normally now
}








/**
 * This function is called as a filter for post revision content.  
 * It simply adds an extra ../ to the start of relative links which navigates back up from the /groups/
 * directory that buddypress uses for groups.
 *
 * @param string   $content The original post content
 *
 * @return string  $content The post content after we have replaced the relative file links
 */
function bpgw_revision_image_link_fix( $content ) {
	$content=str_ireplace('../../../../','../../../../../',$content);
	return $content;
}




/**
 * Slugifies a string passed to it via $text and returns that string
 *
 * @param string   $text String to be slugified
 *
 * @return string  $text The slugified string
 */
function bpgw_slugified_title ($text) {
	// replace non letter or digits by -
	$text = preg_replace('~[^\\pL\d]+~u', '-', $text);
	// trim
	$text = trim($text, '-');
	// transliterate
	if (function_exists('iconv')) {
		$text = iconv('utf-8', 'us-ascii//TRANSLIT', $text);
	}
	// lowercase
	$text = strtolower($text);
	// remove unwanted characters
	$text = preg_replace('~[^-\w]+~', '', $text);
	if (empty($text)) {
		return 'n-a';
	}
	return $text;
}








/**
 * Creates a wordpress blog to be used to store the wiki pages and files
 * We disable access to some of the normal blog areas and use only our own
 * methods to access pages/files/etc.
 *
 * @param array   $group_wiki_data_array Optional array of wiki page titles to be created
 * @param string  $wiki_view_access View access level requirement to be applied to all created pages
 * @param string  $wiki_edit_access Edit access level requirement to be applied to all created pages
 * @param int     $wiki_member_page_create_allowed 1 or 0 frontend member page creation on or off
 *
 * @return int    $wiki_id Wordpress blog ID of the created wiki/blog
 */
function bpgw_create_wiki( $group_wiki_data_array , $wiki_view_access , $wiki_edit_access , $wiki_member_page_create_allowed ) {
	global $bp, $wpdb, $current_site;
	
	// Title only really seen in the site admin backend.  Not used in the buddypress frontend at all.
	$wiki_title = 'GroupWiki_'.$bp->groups->current_group->id;
	$wiki_meta = '';
	$domain = preg_replace("/^https?:\/\/(.+)$/i","\\1", $bp->root_domain);
	$wiki_path = $current_site->path . $bp->groups->current_group->slug . '-wiki/';

	// Create the blog to be used for wiki
	$wiki_id = wpmu_create_blog( $domain, $wiki_path, $wiki_title, $bp->loggedin_user->id, $wiki_meta, $wpdb->siteid );
	
	// $wiki_id will be non-zero if blog created successfully
	if ( $wiki_id ) {
		// Add groupmeta to attach blog id to group id
		groups_update_groupmeta( $bp->groups->current_group->id , 'wiki_blog_id' , $wiki_id );
		// Add groupmeta to set wiki blog thing to 'enabled'
		groups_update_groupmeta( $bp->groups->current_group->id , 'wiki_enabled' , $wiki_id );
		// Add groupmeta to set allow standard members to create pages
		groups_update_groupmeta( $bp->groups->new_group_id , 'wiki_member_page_create' , $wiki_member_page_create_allowed );
		
		// Create post object
		$wiki_post = array(
			'post_content' => '', //The full text of the post.
			'post_excerpt' => '', //For all your post excerpt needs.
			'post_name' => '', // The URI/URL/slug of the post (based on title + slugified)
			'post_status' => 'publish', //Set the status of the new post
			'post_title' => '', //The title of the post
			'post_type' => 'page', //Wiki only uses pages, not posts
			'menu_order' => '0' //Order page will appear in nav menu - lower number appears first
		); 

		// Select the blog/wiki and insert pages/posts for each wiki page
		switch_to_blog($wiki_id);

		// but first, delete all the default created pages
		$all_pages = get_all_page_ids();
		if ($all_pages) {
			foreach( $all_pages as $page_id ) {
				wp_delete_post( $page_id , true );
			}
		}

		// and delete all the default created posts
		$all_posts = get_posts( 'orderby=title' );
		if ($all_posts) {
			foreach( $all_posts as $onePost ) {
				wp_delete_post( $onePost->ID , true );
			}
		}

		update_option( 'this_is_a_wiki_blog' , 1 ); 
		update_option( 'template' , 'wikioverride' ); 
		update_option( 'stylesheet' , 'wikioverride' ); 

		$wiki_pageCount = 1;

		foreach ($group_wiki_data_array as $group_wiki_data) {
			
			// Modify page data for this post
			$wiki_post['post_name'] = bpgw_slugified_title($group_wiki_data);
			if ( $wiki_post['post_name'] == '' ) continue; // Skip the page creation if the user didn't fill in a title
			$wiki_post['post_title'] = esc_attr($group_wiki_data);
			$wiki_post['menu_order'] = $wiki_pageCount;
			$wiki_pageCount++;
			// insert the wiki page
			$wikipost_id = wp_insert_post( $wiki_post );
			// Set the meta data for the page to control view/edit access
			add_post_meta( $wikipost_id , 'wiki_view_access' , $wiki_view_access , 1 );
			add_post_meta( $wikipost_id , 'wiki_edit_access' , $wiki_edit_access , 1 );
			add_post_meta( $wikipost_id , 'wiki_page_enabled' , 1 , 1 );
		}

		// Record wiki creation in the activity stream
		$group = new BP_Groups_Group( $bp->groups->current_group->id , false , false );
		groups_record_activity( array(
			'content' => '<a href="' . bp_get_group_permalink( $group ) . '">' . attribute_escape( $group->name ) . '</a> activated their <a href="'.bp_get_group_permalink( $group ).'wiki">group wiki</a>:<span class="time-since">%s</span></p>',
			'primary_link' => bp_get_group_permalink( $group ),
			'component_action' => 'new_wiki_created',
			'item_id' => $group->id
		) );	

		groups_update_groupmeta($bp->groups->current_group->id, 'last_activity', gmdate( "Y-m-d H:i:s" ));

		// Remove any usermeta for the blog/wiki - otherwise it will appear in various blog menus
		$meta_key_caps  = $wpdb->base_prefix . $wiki_id . '_capabilities';
		$meta_key_level = $wpdb->base_prefix . $wiki_id . '_user_level';
		delete_usermeta( $bp->loggedin_user->id, $meta_key_caps );
		delete_usermeta( $bp->loggedin_user->id, $meta_key_level );		
		$wpdb->query( $wpdb->prepare( "DELETE FROM {$bp->blogs->table_name} WHERE user_id = %d AND blog_id = %d", $bp->loggedin_user->id , $wiki_id ) );	

		// Take it back mahhn..take it back to the original blog.
		restore_current_blog();
	}
	
	return $wiki_id;
}
add_action('admin_menu', 'bpgw_admin_menu');function bpgw_admin_menu() {	add_submenu_page( 'bp-general-settings', 'Wiki Site-Admin', 'Wiki Site-Admin', 'manage_options', 'bpgw-wiki-settings', 'bpgw_admin_menu_options' );}function bpgw_admin_menu_options() {	if (!current_user_can('manage_options'))  {		wp_die( __('You do not have sufficient permissions to access this page.') );	}	echo '<div class="wrap">';	echo '<h2>Remove Group Wikis from Blog Lists</h2>';	echo '<p>Use button this if you have upgraded from an older version of the Group Wiki plugin if you have group wikis appearing in the various blog lists in your site.  This will remove all such references.</p>';	echo '<p class="submit"><input class="button-primary" onclick="cleanBlogLists();return false;" value="Clean Blog Lists"></p>';	echo '</div>';}
/**
 * This function is added to various actions that output lists of possible filters for activity stream items.
 */
add_action( 'bp_activity_filter_options' , 'activityStreamWikiFilterAdd', 10 );
add_action( 'bp_group_activity_filter_options' , 'activityStreamWikiFilterAdd', 10 );
add_action( 'bp_member_activity_filter_options' , 'activityStreamWikiFilterAdd', 10 );
function activityStreamWikiFilterAdd() {?>
	<option value="new_wiki_edit"><?php _e( 'Show Wiki Edits', 'buddypress' ); ?></option>
	<option value="new_wiki_comment,deleted_wiki_comment"><?php _e( 'Show Wiki Comments', 'buddypress' ); ?></option><?php
}
?>