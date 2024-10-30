<?php 
/*
 * The main display page for each wiki page.  Wiki pages also allow comments to be viewed and added
 * via a simple form interface.  Display pages also show a short summary of the last few revisions entries.
 */

if($wiki_can_view) { 
?>
	<div id="blog-page" class="bp-widget">
		<div id='wiki-display'>
			<div class="wiki-title">
				<div class="titlebar">
				<h4><?php echo $wiki_page->post_title; ?></h4>
				<?php
				if ($wiki_can_edit) { 
				?>
					<div class="page-edit-link generic-button group-button public">
						<a href="<?php echo $wiki_page_uri; ?>edit"><span>Edit this page</span></a>
					</div>
				<?php  
				} 
				?>
				</div>
			<p class="last-edited">
			<?php echo "Last edited by ".bp_core_get_userlink($wiki_page->post_author).
					   " on ".bpgw_to_wiki_date_format($wiki_page->post_modified_gmt . ' +0000' ); ?>
			</p>
			</div>
			<div class="wiki-post">
				<?php 
				// If the post contains no content, display a message to inform user
				if($wiki_page->post_content == "") {
					echo "<p class='wiki-meta'>No content has been added to this page revision. " .
						 "Select 'Edit this page' to begin adding some.</p>";
				}
				else {
					echo $wiki_page_content;
					?>
					<div class="clean"></div>
					<?php
				}
			?>
			</div>
			<?php
			echo $wiki_page_revisions;
			?>
			<div class="wiki-comments">
				<?php
				echo $wiki_comment_title_bar;
				
				// Do we output the submit comment form?
				if ( is_user_logged_in() && $wiki_can_comment ) { 
				?>
				<p class='wiki-meta'>Leave a comment about this wiki page.</p>
				<form id="commentform" class="standard-form" method="post" action="<?php echo WIKI_S_COMMENT_SAVE ?>">
					<?php
					if (function_exists('wp_nonce_field'))
						wp_nonce_field('wiki_unfiltered_html_comment');
					?>
					<p class="form-textarea">
					<label for="comment">Comment</label>
					<textarea id="comment_text" tabindex="4" rows="10" cols="60" name="comment_text"></textarea>
					</p>
					<p class="form-submit">
					<input id="submit" class="submit-comment button" type="submit" value="Submit" tabindex="5" name="submit"/>
					<input type="hidden" id="group_id" name="group_id" value="<?php echo $wiki_group_id; ?>"/>
					<input type="hidden" id="comment_post_id" name="comment_post_id" value="<?php echo $wiki_page->ID; ?>"/>
					<input id="comment_parent" type="hidden" value="0" name="comment_parent"/>
					</p>
				</form>
				<?php 
				} // End $wiki_can_comment check 
				?>
				 
				<div class="comment-navigation paged-navigation">
				<?php bpgw_paginate_comment_links($wiki_page_uri, $wiki_comments, 5); ?>
				</div>
				<?php echo bpgw_format_comments_as_list($wiki_comments, 5, $wiki_group_id); ?>
				<div class="comment-navigation paged-navigation">
				<?php bpgw_paginate_comment_links($wiki_page_uri, $wiki_comments, 5); ?>
				</div>
			</div>
		</div>
	</div>
<?php 
}
?>