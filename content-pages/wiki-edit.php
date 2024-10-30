<?php 

if ($wiki_can_edit) {
	wp_print_scripts('utils');
	wp_print_scripts('jquery');
	wp_print_scripts('jquery-ui-core');
	wp_print_scripts('jquery-ui-draggable');
	wp_print_scripts('jquery-ui-dialog');
	?>
	
	<link rel="stylesheet" type="text/css" href="<?php echo WIKI_D_WEB_PATH . 'js/thickbox/thickbox.css'; ?>" />
	<script type="text/javascript" src="<?php echo WIKI_D_WEB_PATH . 'js/thickbox/thickbox.js'; ?>"></script>
	<script type="text/javascript" src="<?php echo WIKI_D_WEB_PATH . 'js/jquery.timer.js'; ?>"></script>
	
	<script language="javascript" type="text/javascript"> 
		show_wiki_editor();
		add_navigate_away_warning();
		
	jQuery(document).ready(function(){
	
		jQuery.post("<?php echo $bp->root_domain; ?>?bpgw_group_id=<?php echo $wiki_group_id;?>&bpgw_post_id=<?php echo $wiki_page->ID;?>&wiki_edit_lock=1", function(data){
				 jQuery("#now-editing-tab").html(data);
			   });
		timerUpdateCount=1;		
		
		jQuery.timer( 10000, function (timer) {
			if ( timerUpdateCount < ((60*30)/10) ) { // This equates to the counter value after 30 mins
				jQuery.post("<?php echo $bp->root_domain; ?>?bpgw_group_id=<?php echo $wiki_group_id;?>&bpgw_post_id=<?php echo $wiki_page->ID;?>&wiki_edit_lock=1", function(data){
					jQuery("#now-editing-tab").html(data);			
					var checkForWarn = jQuery("#editing-now-warnings > div");
					if ( checkForWarn.is("#page-save-warning") ) {
						jQuery("#editing-now-warnings").after('<div id="warning-dialog" title="Editing Warning"><h3>WARNING</h3><p>This page has been saved by another user.<br/>You should reload the page to avoid saving over their work.</p></div>');
						jQuery("#warning-dialog").dialog();//{ buttons: { "OK": function() { jQuery("#warning-dialog").dialog('close'); } } });
					}
				});
				timerUpdateCount++;
				if ( timerUpdateCount == ((60*25)/10) ) { // This equates to the counter value after 25 mins
						jQuery("#editing-now-warnings").after('<div id="warning-dialog" title="Editing Warning"><h3>WARNING<h3><p>You have been editing this page for 25 minutes.  Please save and reload the page.<br/>After a further 5 minutes the page will be automatically saved and you will be returned to the page view screen.</p></div>');
						jQuery("#warning-dialog").dialog();
				} 
			} else {
				timer.stop();
				needToConfirm = false;
				jQuery("#wiki-edit-form").submit();
			}
		});
			
	});
	
		var tb_pathToImage = "<?php echo WIKI_D_WEB_PATH . 'js/thickbox/loadingAnimation.gif' ?>";
		var tb_closeImage =  "<?php echo WIKI_D_WEB_PATH . 'js/thickbox/tb-close.png' ?>";
		
		function send_to_editor(h) {
			var ed;

			if ( typeof tinyMCE != 'undefined' && ( ed = tinyMCE.activeEditor ) && !ed.isHidden() ) {
				ed.focus();
				if ( tinymce.isIE )
					ed.selection.moveToBookmark(tinymce.EditorManager.activeEditor.windowManager.bookmark);

				if ( h.indexOf('[caption') === 0 ) {
					if ( ed.plugins.wpeditimage )
						h = ed.plugins.wpeditimage._do_shcode(h);
				} else if ( h.indexOf('[gallery') === 0 ) {
					if ( ed.plugins.wpgallery )
						h = ed.plugins.wpgallery._do_gallery(h);
				} else if ( h.indexOf('[embed') === 0 ) {
					if ( ed.plugins.wordpress )
						h = ed.plugins.wordpress._setEmbed(h);
				}

				ed.execCommand('mceInsertContent', false, h);

			} else if ( typeof edInsertContent == 'function' ) {
				edInsertContent(edCanvas, h);
			} else {
				jQuery( edCanvas ).val( jQuery( edCanvas ).val() + h );
			}

			tb_remove();
		}
		
	</script>
	

	<div id="blog-page" class="bp-widget">
	
		<form action="<?php echo WIKI_S_EDIT_SAVE; ?>" method="post" id="wiki-edit-form" name="wiki-edit-form" onclick="needToConfirm = false;">
		
		<div class="wiki-title">
			<div class="titlebar">
				<h4> <?php echo $wiki_page->post_title; ?></h4>
				
				<div id="wiki-edit-save">
					<input id="wiki-edit-save-submit" type="submit" value="Save" />
				</div>
				
				<div id="wiki-edit-cancel">
					<div class="generic-button group-button public">
						<a href="<?php echo bpgw_get_permalink($wiki_group_id, $wiki_page->ID); ?>" onclick="return forceAskUserToConfirm();">
						<span>Cancel</span></a>
					</div>
				</div>
				
				<div id="wiki-media-button">
					<div class="generic-button group-button public">
						<a href="<?php echo get_blog_option($wiki_blog_id, 'siteurl'); ?>/wp-admin/media-upload.php?flash=0&post_id=<?php echo $wiki_page->ID; ?>&amp;TB_iframe=true&amp;width=640&amp;height=678"class="thickbox">
						<span>Upload Files</span></a>
					</div>
				</div>	
				
			</div>
			<?php wp_nonce_field('wiki_edit_save_'); ?> 
			<input type="hidden" id="group_id" name="group_id" value="<?php echo $wiki_group_id; ?>"/>
			<input type="hidden" id="post_id" name="post_id" value="<?php echo $wiki_page->ID; ?>"/>	
			
		</div>
		
		<div id="now-editing-tab"></div>
		<div class="clean"></div>
		<textarea id="wiki-post-edit" name="wiki-post-edit" class="post">
			<?php echo $wiki_page->post_content; ?>
		</textarea>
				
		</form>
	</div>
<?php
}
?>