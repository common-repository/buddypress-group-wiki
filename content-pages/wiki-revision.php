<?php
/*
 * Displays a complete list of all page revisions for the current wiki page and
 * indicates the date the revision was made, the author, and any notable comments
 * about the revision.
 */


// Define a series of constants which will be used to indicate the 
// action to be taken based on how the URL string is parsed.
define('REVISIONS_SYNTAX_ERROR', -1);
define('REVISIONS_ALL', 0);
define('REVISIONS_SINGLE', 1);
define('REVISIONS_COMPARE', 2);


// Get the group ID
$group_id = $bp->groups->current_group->id;

// Switch to the current blog for the group
bpgw_switch_to_wiki_blog( $group_id );

// Get the currently selected page
$current_page = bpgw_get_selected_page_from_url();

$post_ID = $current_page->ID;
$can_view_wiki = bpgw_user_can_view_wiki_page($post_ID, $group_id);
$can_edit_wiki = bpgw_user_can_edit_wiki_page($post_ID, $group_id);

// Grab the action variable from the URL
$action_var = $bp->action_variables[2];

// Split it into an array of characters
$chars = str_split($action_var);	
// Declare variables to help parse and store result
$revisions_op = null;
$id1 = null;
$id2 = null;
$comparison = false;

// if the action variable is empty then set op to show all revisions
if(empty($chars[0]) || empty($chars)) {
	$revisions_op = REVISIONS_ALL;
}
// else parse the action variable string char by char
else {
	for($i = 0; $i < count($chars); $i++) {
		$char = $chars[$i];
		
		// Grab IDs in values are numeric
		if(is_numeric($char)) {
			if(!$comparison) {
				$id1 .= $char;
			} else {
				$id2 .= $char;
			}
		}
		// If character is not numeric, evaluate what are we dealing with
		if(!is_numeric($char)) {
			// Only the '-' char is allowed 
			// It must appear after the first ID has been collected
			// But must be followed by more characters (hopefully another ID)
			if($char == '-' && !empty($id1) && empty($comparison) && $i < count($chars)-1) {
				$comparison = true;
			} else {
				// Incorrect URL syntax, nullify all values and assume user is being naughty
				$revisions_op = REVISIONS_SYNTAX_ERROR;
				$id1 = null;
				$id2 = null;
			}
		}
		// If a syntax error was found, break out of the loop early
		if($revisions_op == REVISIONS_SYNTAX_ERROR) {
			break;
		}
	}
	// If two IDs were correctly parsed, must be a compare
	if($id1 != null && $id2 != null) {
			$revisions_op = REVISIONS_COMPARE;
	}
	// If only one ID was found, must be viewing of a single revision
	elseif($id1 != null) {
		$revisions_op = REVISIONS_SINGLE;
	}
}



// Begin layout for operations
?>
<div id="blog-page" class="bp-widget">
	<div id='wiki-revisions' class='wiki-revisions'>
<?php	

// Select the output via a switch on the revision operation
switch($revisions_op) {

	case REVISIONS_SYNTAX_ERROR:
		echo '<div id="message" class="error"><p>The an incorrect URL appears to have been used to select revisions.  '.
			'Please select revisions via the interface rather than directly typing them in to your web browser.</p></div>';
		// Fall through...

		
		
	case REVISIONS_ALL:
		bpgw_start_in_the_loop($current_page);
		global $post;
		$title_bar_args = array(
			'title_text' => "Full revision history for: ".$post->post_title,
			'restore_button' => false,
			'history_button' => false,
		);
		echo bpgw_get_revisions_titlebar($group_id , $title_bar_args);
		echo "<p class='wiki-meta'>Select a revision date to view a previous version of the wiki page. " .
			 "Alternatively select <em>two</em> revisions using the radio buttons followed by the " .
			 "'Compare Revisions' button to view only the differences.</p>";
		
		// Grab all the revisions
		$revisions = bpgw_get_revisions(-1);
		$comparison_table_html = bpgw_format_revisions_as_comparison_table($group_id, $current_page, $revisions);
		
		// Must restore current blog before we create the nonce else will cause error
		bpgw_end_in_the_loop();
		restore_current_blog();
		
		?>
		<form id="revisionform" class="standard-form" method="post" action="<?php echo WIKI_S_REVISION_COMPARE ?>">
			<?php
			if (function_exists('wp_nonce_field'))
				wp_nonce_field('wiki_revision_compare');
			?>
			<div class="form-submit">
				<input id="submit" class="submit-revision button" type="submit" value="Compare Revisions" tabindex="5" name="submit"/>
			</div>
			<?php echo $comparison_table_html; ?>
			<div class="form-submit">
				<input id="submit" class="submit-revision button" type="submit" value="Compare Revisions" tabindex="5" name="submit"/>
				<input type="hidden" id="group_id" name="group_id" value="<?php echo $wiki_group_id; ?>"/>
				<input type="hidden" id="post_id" name="post_id" value="<?php echo $wiki_page->ID; ?>"/>
			</div>
		</form>
		<?php
		break; // End of REVISIONS_ALL case statement
		

		
	case REVISIONS_SINGLE:
		// Set the loop_post for the selected page
		bpgw_start_in_the_loop($current_page);
		
		// This will store our selected revision
		$selected_revision = null;
		$is_revision_current_version = false;
		
		if($id1 == $current_page->ID) {
			$selected_revision = $current_page;
			$is_revision_current_version = true;
		}
		else {
			$selected_revision = get_post($id1);
			// Check we have retrieved a valid revision post for this page
			if( $selected_revision == null || // No post retrieved
				empty($selected_revision) || // No post retrieved
				$selected_revision->post_type != 'revision' || // Not a revision
				$selected_revision->post_parent != $current_page->ID ) // Not a revision belonging to the current page
			{
					echo "<p class='warning'>The selected revision ID doesn't appear to be valid for this page.</p>";
					break; // Exit early and only display the error message
			}
		}
		
		// If we get to this point, it should be safe to display the revision
		
		// Grab all the revisions for selected page
		$revisions = bpgw_get_revisions(-1);
		
		// Calculate older and newer revisions
		$older_revision = null;
		$newer_revision = null;
		$total_revisions = count($revisions); // Get a count of the total number of revisions found

		if($is_revision_current_version) {
			$older_revision = $revisions[0]; // The latest revision before the current one
		}
		else {
			$i=0;
			foreach($revisions as $revision) {
				if($revision->ID == $selected_revision->ID) {
					if($i<$total_revisions-1) {
						$older_revision = $revisions[$i+1];
					}
					if($i>0) {
						$newer_revision = $revisions[($i-1)];
					}
					break;
				}
				$i++;
			}

		}

		// Older revision link
		if($older_revision != null) {
			$older_text = bpgw_format_revision_as_title($older_revision, true);
		} else {
			$older_text = "There are no older versions of the document.";
		}
		// Newer revision link
		if($newer_revision != null) {
			$newer_text = bpgw_format_revision_as_title($newer_revision, true);
		} 
		else if($is_revision_current_version) {
		}
		else {
			// The newer revision link must point to current page
			$newer_text = bpgw_format_revision_as_title($current_page, true);
		}
		
		// Gather the data we need to output before we end the loop
		$selected_revision_content = apply_filters('the_content' , $selected_revision->post_content);
		
		// Fix the broken image/file links caused by us being within the group directory.
		// This doesn't use apply_filters as I didn't want to hook this into the_content/etc, just use it in this one place
		$selected_revision_content = bpgw_revision_image_link_fix($selected_revision_content);
		
		$full_history_link = bpgw_get_permalink($group_id , $current_page->ID).'revision/';
		$revision_text = "Revision: ".bpgw_format_revision_as_title($selected_revision, false);
		$restore_url = WIKI_S_REVISION_RESTORE."?group_id=$wiki_group_id&post_id=".$current_page->ID."&revision_id=".$selected_revision->ID;
		
		// Must restore current blog before we create the nonce else will cause error
		bpgw_end_in_the_loop();
		restore_current_blog();
		
		// Append the nonce to the URL
		$restore_url  = wp_nonce_url($restore_url, 'wiki_revision_restore_');
		
		// Create all the arguments for the revisions title bar
		$title_bar_args = array(
			'title_text' => $revision_text,
			'history_button' => true,
			'history_link' => $full_history_link 
		);

		if($can_view_wiki && $can_edit_wiki) {
			$title_bar_args['restore_button'] = !$is_revision_current_version; // Only show if not viewing current page
			$title_bar_args['restore_link'] = $restore_url;
			$title_bar_args['restore_link_attr'] = 'onclick="return confirm(\''.
									'Really restore this revision of the wiki to the current version?\n\n'.
									'To proceed with restoring this version, please select OK to confirm.\')"';
		}

		// Begin HTML page layout
		?>
		<div class='columns-two'>
			<div class='left'>
				<h5>View older revision</h5>
				<?php echo $older_text; ?>
			</div>
			<div class='right' style='text-align: right;'>
				<h5>View newer revision</h5>
				<?php echo $newer_text; ?>
			</div>
		</div>
		<div class='clean'></div>
		<?php
		echo bpgw_get_revisions_titlebar($group_id , $title_bar_args);
		?>
		<h2><?php echo $selected_revision->post_title; ?></h2>
		<div class="wiki-post">
			<?php 
			// If the post contains no content, display a message to inform user
			if($selected_revision->post_content == "") {
				echo "<p>No content has been added to this page revision.</p>";
			}
			// Output the contents
			echo $selected_revision_content;
			?>
		</div>
		<?php
		break; // End of REVISIONS_SINGLE case statement
		
		
		
		
		
		
	case REVISIONS_COMPARE:
	
		// Check if IDs are identical
		if($id1 == $id2){
			echo '<div id="message" class="error"><p>You must select two different revisions and you appear to selected identical ones. '.
						 'Both versions are the same!</p></div>';
			break; // Exit early and only display the error message
		}

		// Set the loop_post for the selected page
		bpgw_start_in_the_loop($current_page);
					
		// This will store our selected revision
		$left_revision = get_post($id1);
		$right_revision = get_post($id2);

		if( !bpgw_validate_revision_for_compare($current_page, $left_revision) ||
			!bpgw_validate_revision_for_compare($current_page, $right_revision) )
		{
			echo "<p class='warning'>One or more of the selected revision IDs doesn't appear to be valid for this page.</p>";
			break; // Exit early and only display the error message
		}
			
		// If we get to this point, it should be safe to display the revision comparison
		
		// Create all the arguments for the revisions title bar
		$full_history_link = bpgw_get_permalink( $group_id , $current_page->ID).'revision/';
		$title_bar_args = array(
			'title_text' => "Comparison of wiki page content",
			'history_button' => true,
			'history_link' => $full_history_link 
		);
		
		// Get the content to compare
		$left_content   = $left_revision->post_content;
		$right_content  = $right_revision->post_content;
		
		$left_title		= bpgw_format_revision_as_title($left_revision, true);
		$right_title	= bpgw_format_revision_as_title($right_revision, true);
		
		// Tidy the HTML and then generate the diff
		$left_content   = str_replace('&nbsp;','',$left_content);
		$right_content  = str_replace('&nbsp;','',$right_content);
		$text_diff_args 	= array('title_left' => $left_title, 'title_right' => $right_title);
		$output			= wp_text_diff( $left_content, $right_content, $text_diff_args);
		
		if(empty($output)) {
			$output  = "<p>Both these versions are <em>identical</em>. There are no differences to display.</p>";
			$output .= "<ul>\n<li>$left_title</li>\n<li>$right_title</li>\n</ul>";
		}
		
		// Display
		echo bpgw_get_revisions_titlebar($group_id , $title_bar_args); 
		echo $output;

		break; // End of REVISIONS_COPARE case statement
		
		
		
	default:
		die("Logic error when calculating revision operation");
		break;
}
?>
	</div>
</div>