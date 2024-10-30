<?php 
/*
 * Displays a summary list of the current wiki pages the user can see within the group wiki.
 * This page acts as the initial group wiki 'home page'. 
 */

bpgw_switch_to_wiki_blog( $bp->groups->current_group->id );

query_posts('post_type=page&orderby=menu_order&order=ASC');
remove_filter('the_content', 'bpgw_table_of_contents', 9);

// This will be set to true if any of the pages pass the bpgw_user_can_view_wiki_page check
$any_pages_visible = false;

// Build the summary rows
$summary_rows = '';
$new_line = "\n";

$i = 0;
while (have_posts()) : the_post();
	$current_post = get_post(get_the_ID());
	if (bpgw_user_can_view_wiki_page( get_the_ID() , $wiki_group_id ) ) {
		
		$pagename = '<a href="'.bpgw_get_permalink( $wiki_group_id , $current_post->ID ).'">'.$current_post->post_title.'</a>';
		$excerpt = wp_trim_excerpt($current_post->content);
		$author = bp_core_get_userlink($current_post->post_author);
		$date = bpgw_to_wiki_date_format($current_post->post_modified );
		
		$alt_style = '';
		if($i %2 == 0) $alt_style = ' class="alt"';
		$summary_row = "<tr$alt_style>".$new_line;
		$summary_row .= '<td class="col-pagename">'.$pagename.'</td>'.$new_line;
		$summary_row .= '<td class="col-excerpt">'.$excerpt.'</td>'.$new_line;
		$summary_row .= '<td class="col-author">'.$author.'</td>'.$new_line;
		$summary_row .= '<td class="col-date">'.$date.'</td>'.$new_line;
		$summary_row .= '</tr>'.$new_line;
		
		// Set $any_pages_visible to true so that we know to return a non-false value
		$any_pages_visible = true;
		$summary_rows .= $summary_row;
		$i++;
	}
endwhile;


// If user can see any of these pages, return the html for the page links, else return false
if ($any_pages_visible) {
	// Build the table
	$output .= '<table id="wiki-summary">'.$new_line;
	$output .= '<thead>'.$new_line;
	$output .= '<tr>'.$new_line;
	$output .= '<th class="col-pagename">Page name</th>'.$new_line;
	$output .= '<th class="col-excerpt">Page excerpt</th>'.$new_line;
	$output .= '<th class="col-author">Last edited by</th>'.$new_line;
	$output .= '<th class="col-date">Last edited date</th>'.$new_line;
	$output .= '</tr>'.$new_line;
	$output .= '</thead>'.$new_line;
	$output .= '<tbody>'.$new_line;
	$output .= $summary_rows;
	$output .= '</tbody>'.$new_line;
	$output .= '</table>'.$new_line;
} 
else {
	$output .= '<p>You do not have permission to view any of the pages in this group wiki.</p>';
}
	
	
?>
<div id="blog-page" class="bp-widget">
	<div class="wiki-title">
		<h1>Group Wiki</h1>
		<?php if ($any_pages_visible) { ?>
			<p class="wiki-meta">This table provides a summary of the current state of each wiki page you have access to.  To access a wiki page select a page 
			title link.</p>
		<?php } ?>
	</div>
	<?php echo $output; ?>
	<?php
	if (groups_get_groupmeta($bp->groups->current_group->id, 'wiki_member_page_create') == 1 && groups_is_user_member($bp->loggedin_user->id, $bp->groups->current_group->id)) {
		?>
		<script type="text/javascript">	
			var frontendWikiPageCreateButtonAlreadyPressed = false;
			// Submits the frontend page creation stuff in wiki-index
			function frontendWikiPageCreate() {
				// 1. Set form stats = "clicked"
				// 2. Get the page title from form
				// 3. Clear the form data
				// 4. Submit the data via ajax
				// 5. Onsuccess: reload the page (future update:we'll make it update the page without a refresh)
				if (!frontendWikiPageCreateButtonAlreadyPressed ) {
					var wikiPageTitle = jQuery("#wikiPageCreate").val();
					if (wikiPageTitle != '') {
						frontendWikiPageCreateButtonAlreadyPressed = true;
						jQuery("#wikiPageCreate").val("loading...");
						jQuery.post("<?php echo $bp->root_domain; ?>?bpgw_group_id=<?php echo $wiki_group_id;?>&bpgw_newpage_title=" + wikiPageTitle + "&wiki_frontend_page_create=1", function(data){
							location.reload(true);
						});
					}
				}
			}
		</script>
		
		<br/>
		<form>
			<?php wp_nonce_field( 'wiki_frontend_newpage_'  );?> 
			<div id="wiki-frontend-page-create">
				<div class="instruction">Create a new wiki page:</div>
				<input type="textarea" id="wikiPageCreate" name="wikiPageCreate" class="wiki-title-textarea" />
				<div class="generic-button group-button public" onclick="frontendWikiPageCreate();">
					<a href="#"><span>Create Page</span></a>
				</div>
			</div>		
		</form>
		<?php
	}
?>
</div>

<?php restore_current_blog(); ?>