<?php
global $bp;

$all_pages = null;
// Switch to the current blog for the group
$bpgw_blog_id = groups_get_groupmeta($bp->groups->current_group->id, 'wiki_blog_id');
switch_to_blog($bpgw_blog_id);
// Grab all pages for the wiki-blog that exist sorted by menu_order
$all_pages = get_pages(array('sort_column' => 'menu_order'));
//If group wiki HAS EVER BEEN enabled for this group, output the edit screens.  Else output something like the creation step.
if (groups_get_groupmeta($bp->groups->current_group->id, 'wiki_blog_id')) { ?> 

	<script type="text/javascript">
	add_wiki_create_page_buttons();
	numericInputOnly();
	</script>

	<div id="groupwiki"><!-- Added to control our custom groupwiki styles -->

		<div id="wiki-enable">
			<label><input type="checkbox" <?php if (groups_get_groupmeta($bp->groups->current_group->id, 'wiki_enabled')) echo 'checked="1"'; ?> id="groupwiki-enable-wiki" name="groupwiki-enable-wiki"/> Enable group wiki</label>	
		</div>	

		<table id="wiki-pages-admin">
			<thead>
				<tr>
					<th class="wiki-page-order">#</th>
					<th class="wiki-page-title">Page Title</th>
					<th class="wiki-page-privacy">Privacy</th>
					<th class="wiki-page-editing">Editing</th>
					<th class="wiki-page-commenting">Comments</th>
					<th class="wiki-page-enabled">Show</th>
				</tr>
			</thead>
		<tbody>
			<?php
			// Build the table rows
			$page_counter = 1;
			foreach ($all_pages as $page) {
				// Get current options for each wiki page.  
				$privacy_settings = get_post_meta($page->ID, 'wiki_view_access', 1);
				$edit_settings = get_post_meta($page->ID, 'wiki_edit_access', 1);
				$comment_settings = $page->comment_status;
				$page_enabled = get_post_meta($page->ID, 'wiki_page_enabled', 1);
				// Creates a row for each wiki page with current options selected 
				$alt_style = '';
				if($page_counter != 0) {
					$alt_style = ' class="stripe"';
				}
				?>
				<tr<?php echo $alt_style; ?>>
					<!-- Hidden fields to store the page ID, ordering, etc -->
					<input type='hidden' id='wikiPage[<?php echo $page_counter; ?>][id]' name='wikiPage[<?php echo $page_counter; ?>][id]' value='<?php echo $page->ID; ?>' />
					<td class='wiki-page-order'>
						<input type='textarea' id='wikiPage[<?php echo $page_counter; ?>][order]' name='wikiPage[<?php echo $page_counter; ?>][order]' value='<?php echo $page->menu_order; ?>' />
					</td>
					<td class='wiki-page-title'>
						<input type='textarea' id='wikiPage[<?php echo $page_counter; ?>][title]' name='wikiPage[<?php echo $page_counter; ?>][title]' value='<?php echo $page->post_title; ?>' />
					</td>
					<td class='wiki-page-privacy'>
						<input type='radio' id='wikiPage[<?php echo $page_counter; ?>][privacy]' name='wikiPage[<?php echo $page_counter; ?>][privacy]' value='public' <?php if ($privacy_settings == 'public') echo "checked='checked'"; ?> />Public
						<input type='radio' id='wikiPage[<?php echo $page_counter; ?>][privacy]' name='wikiPage[<?php echo $page_counter; ?>][privacy]' value='member-only' <?php  if ($privacy_settings == 'member-only') echo "checked='checked'"; ?> />Private
					</td>	   
					<td class='wiki-page-editing'>
						<input type='radio' id='wikiPage[<?php echo $page_counter; ?>][edit]' name='wikiPage[<?php echo $page_counter; ?>][edit]' value='all-members' <?php if ($edit_settings == 'all-members') echo "checked='checked'"; ?> />All
						<input type='radio' id='wikiPage[<?php echo $page_counter; ?>][edit]' name='wikiPage[<?php echo $page_counter; ?>][edit]' value='moderator-only' <?php if ($edit_settings == 'moderator-only') echo "checked='checked'"; ?> />Mods 
						<input type='radio' id='wikiPage[<?php echo $page_counter; ?>][edit]' name='wikiPage[<?php echo $page_counter; ?>][edit]' value='admin-only' <?php  if ($edit_settings == 'admin-only') echo "checked='checked'"; ?> />Admins
					</td>
					<td class='wiki-page-commenting'>
						<input type='checkbox' id='wikiPage[<?php echo $page_counter; ?>][comments]' name='wikiPage[<?php echo $page_counter; ?>][comments]' value='1' <?php  if ($comment_settings == 'open') echo "checked='checked'"; ?> />
					</td>  
					<td class='wiki-page-enabled'>
						<input type='checkbox' id='wikiPage[<?php echo $page_counter; ?>][comments]' name='wikiPage[<?php echo $page_counter; ?>][enabled]' value='1' <?php  if ($page_enabled) echo "checked='checked'";?> />
					</td>	   
				</tr>
				<?php
				$page_counter++;
			}
			?>
			</tbody>
		</table>

		<div style="clear: left;"></div>
			
		<br/>
			<h3>Create Additional Pages</h3>
				
			<p>Click the (+) to create a new page.  Click the (-) to remove the page you've created.</p>
			
			<div id="1" class="wiki-form-element" style="opacity: 1">
				<div id="1" class="spacer-wiki-title-input-field"></div>
				<div id="1" class="add-wiki-title-input-field"></div>
				<div style="clear: left;"></div>
			</div>
			
		<br/>
			<h3>Wiki Options</h3>
			
			<p>
				<strong>Public</strong> - Wiki pages may be viewed by site visitors even if they aren't in the group.  NOTE: This setting overrides group privacy settings!<br/>
				<strong>Private</strong> - Wiki pages may only be viewed by members of the group.
			</p>
			
			<p>
				<strong>All</strong> (Members) - All members of the group can edit wiki pages.<br/>
				<strong>Moderators</strong> - Only group moderators (and admins) can edit wiki pages .<br/>
				<strong>Administrators</strong> - Only group admins can edit wiki pages.
			</p>
			
		<div class="checkbox">
			<label><input type="checkbox" <?php if ( groups_get_groupmeta( $bp->groups->current_group->id , 'wiki_member_page_create' ) ) echo 'checked="1"'; ?> id="groupwiki-member-page-create" name="groupwiki-member-page-create"/> Click here to allow <strong>all</strong> group members to create their own wiki pages.<br/>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;Use with caution - members can create pages but currently only admins can remove them.</label>	
		</div>	

		<br/>
		<input type="submit" name="save" value="Save" />
		
	</div>
<?php 
} else { // Group wiki not enabled, so show creation-like steps?>
	<div id="groupwiki"><!-- Added to control our custom groupwiki styles -->
		<?php require('wiki-create.php'); ?>

		<input type="submit" name="save" value="Save" />
	</div>
<?php 
} 
restore_current_blog();
?>