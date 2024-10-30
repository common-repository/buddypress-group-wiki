<script type="text/javascript">
add_wiki_create_page_buttons();
</script>

<div class="checkbox">
	<label><input type="checkbox" value="1" id="groupwiki-enable-wiki" name="groupwiki-enable-wiki" onclick="toggleWikiCreateFields()"/> Enable group wiki</label>	
	<hr>
</div>


<p id="wiki-create-unticked">Wiki creation options will be displayed once you have ticked to enable the group wiki.</p>

<div id="wiki-create-fields" class="wiki-create-creen-hidden">
	<h3>Page Privacy Settings</h3>
	<p>Please select the privacy levels for your wiki.  These are separate to your group privacy settings and you can later apply settings to individual pages through the group admin menu.</p>

	<input type="radio" name="wiki-privacy" value="public" checked="checked"/>
	<strong>Public</strong> - Wiki pages may be viewed by site visitors even if they aren't in the group.  NOTE: This setting overrides group privacy settings!<br/>
	<input type="radio" name="wiki-privacy" value="member-only" />
	<strong>Private</strong> - Wiki pages may only be viewed by members of the group.<br/>

	<h3>Member Page Editing Privileges</h3>
	<p>Please select the edit privileges for your members.  Once again, this can be further fine tuned on a page-by-page basis later in the group admin menu.</p>
	
	<input type="radio" name="wiki-edit-rights" value="all-members" checked="checked"/>
	<strong>All Members</strong> - All members of the group can edit wiki pages.<br/>
	<input type="radio" name="wiki-edit-rights" value="moderator-only" />
	<strong>Moderators</strong> - Only group moderators (and admins) can edit wiki pages .<br/>
	<input type="radio" name="wiki-edit-rights" value="admin-only" />
	<strong>Administrators</strong> - Only group admins can edit wiki pages.<br/>

	<h3>Page Creation</h3>
	<p>Please enter the titles for your group wiki pages here.  You can click on the (+) icon to the left of a title to add extra wiki pages and the (-) to delete that page.<br/>
	NB 1: Any duplicate page titles will be ignored.<br/>
	NB 2: Any future page creation/deletion can ONLY be performed by group admins.</p>
	<div id="1" class="wiki-form-element" style="opacity: 1">
		<input type="textarea" id="wikiPageTitle[]" name="wikiPageTitle[]" class="wiki-page-title-input" value="Wiki Page Title"/>
		<div id="1" class="spacer-wiki-title-input-field"></div>
		<div id="1" class="add-wiki-title-input-field"></div>
	</div>

	<br/>
	<div class="checkbox">
			<label><input type="checkbox" <?php if (groups_get_groupmeta($bp->groups->current_group->id, 'wiki_member_page_create')) echo 'checked="1"'; ?> id="groupwiki-member-page-create" name="groupwiki-member-page-create"/> Click here to allow <strong>all</strong> group members to create their own wiki pages.<br/>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;Use with caution - members can create pages but currently only admins can remove them.</label>	
	</div>	

</div>
<br/>