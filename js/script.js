/**
 * Provides the hide/show functionality on the table of contents on displayed wiki pages.
 * 
 * @param {String} A reference to the element to which a jQuery trigger will be placed on
 * @param {String} The class name to be appended to the trigger.
 */ 
function toggle_hide_show(el, showHideClassName) {
    if (jQuery(el).hasClass("show")) {
		jQuery(el).removeClass("show").addClass("hide").html('Show').parent().next(''.showHideClassName).hide();
    } else {
		jQuery(el).removeClass("hide").addClass("show").html('Hide').parent().next(''.showHideClassName).show();
    }
}


function cleanBlogLists() {
	jQuery.post(ajaxurl, {
		action:'bpgw_clean_blog_lists',
		'cookie':encodeURIComponent(document.cookie)
	}, function(response) {  
		alert('Blog lists have been cleaned.  Please refresh the page to confirm.');
	});
}


/**
 * Shows the tiny_mce editor for a wiki page edit
 */ 
function show_wiki_editor() {
	// Load the rich editor
	tinyMCE.init({
		mode : "exact", 
		elements: "wiki-post-edit",
		theme : "advanced",
		width:"100%", 
		height:"450px",
		plugins: "safari,inlinepopups,spellchecker,paste,wikimedia,fullscreen,wpeditimage,wpgallery,tabfocus,table,print,preview,wordpress",
		theme_advanced_buttons1 : "bold,italic,underline,strikethrough ,separator,justifyleft,justifycenter,justifyright,justifyfull,separator,bullist,numlist,separator,outdent,indent,separator,fontsizeselect,formatselect,separator,forecolor,separator,pastetext,pasteword,separator,wikimedia",
		theme_advanced_buttons2 : "undo,redo,separator,link,unlink,separator,tablecontrols,separator,spellchecker,separator,code,separator,preview,print",
		theme_advanced_buttons3 : "",
		theme_advanced_blockformats : "p,h1,h2,h3,h4,h5,h6,pre",
		table_styles : "Header 1=header1;Header 2=header2;Header 3=header3",
		table_cell_styles : "Header 1=header1;Header 2=header2;Header 3=header3;Table Cell=tableCel1",
		table_row_styles : "Header 1=header1;Header 2=header2;Header 3=header3;Table Row=tableRow1",
		table_cell_limit : 100,
		table_row_limit : 30,
		table_col_limit : 10,
		spellchecker_languages : "+English=en",
		theme_advanced_toolbar_location:"top", 
		theme_advanced_toolbar_align:"left", 
		theme_advanced_statusbar_location:"bottom", 
		theme_advanced_resizing:"1", 
		theme_advanced_resize_horizontal:"", 
		extended_valid_elements : "a[name|href|target|title|onclick],img[class|src|border=0|alt|title|hspace|vspace|width|height|align|onmouseover|onmouseout|name],hr[class|width|size|noshade],font[face|size|color|style],span[class|align|style]"
	});
}

/**
 * Show a warning to the user when they try to navigate away from a page.
 */ 
function add_navigate_away_warning() {
	function askUserToConfirm(e) {
			if (needToConfirm) {
				if(!e) {
					e = window.event;
				}
				// For IE
				e.cancelBubble = true;
				e.returnValue = 'You have not saved your work.'; //This is displayed on the dialog
				// For firefox
				if (e.stopPropagation) {
					e.stopPropagation();
					e.preventDefault();
				}
			}
	}
	window.onbeforeunload=askUserToConfirm;
	needToConfirm = true;
}


/**
 * Asks the user to confirm a page edit cancel - should reduce accidental data loss
 */ 
function forceAskUserToConfirm() {
	var answer = confirm("Are you sure you wish to discard this change?  \n\nClick OK to return to the previous page without saving.  \nClick Cancel to continue editing.")
	if (answer) {
		return true;
	} else {
		return false;
	}
}

	
/**
 * Adds ajaxy buttons to create additional wiki page creation input rows in the admin screen.  New page input rows can also be deleted 
 * as required by clicking on the remove-wiki-title-input-field element
 */ 
function add_wiki_create_page_buttons() {
	jQuery(document).ready(function() {
		// When click on this element, create a new row beneath it
		// jQuery .live function means that this behaviour 
		// ...is applied to newly created elements too
		jQuery(".add-wiki-title-input-field").live("click", function() {
			var currentTime = new Date() //Used to create unique div IDs
			var currentID = jQuery(this).attr("id"); //Current div ID
			var currentIDnumber = parseInt( currentID );  //Convert to Int
			var nextItemID = currentIDnumber + currentTime.getTime(); //Make ID unique
			jQuery(".wiki-form-element").filter("#" + currentID).after("<div id='" + nextItemID + "' class='wiki-form-element'><input type='textarea' id='wikiPageTitle[]' name='wikiPageTitle[]' class='wiki-page-title-input' /><div id='" + nextItemID + "' class='remove-wiki-title-input-field'></div><div id='" + nextItemID + "' class='add-wiki-title-input-field'></div></div>"); //Insert the new row
			jQuery(".wiki-form-element").filter("#" + nextItemID).fadeTo(300, 1);
		});
		// When click on this element, delete the current row
		// First row cannot be deleted (due to use of css class rather than
		// ...any js shenanigans)
		jQuery(".remove-wiki-title-input-field").live("click" , function() {
			var currentID = jQuery(this).attr("id");  //Current div ID
			var currentIDnumber = parseInt(currentID); //Convert to Int
			jQuery(".wiki-form-element").filter("#" + currentIDnumber).fadeTo(300, 0, function() { 
				jQuery(".wiki-form-element").filter("#" + currentIDnumber).remove()
			});
		});
	});
}

/**
 * Allows only numeric input into a textbox.  By no means bullet-proof but should help users realise that they shouldn't be inputting text
 */ 
function numericInputOnly() {
	jQuery(document).ready(function() {
		jQuery(".js-numeric-only").keyup(function(e) {
			if(e.which==13) {
				return false;
			}
			c = jQuery(this).val().replace(/[A-Za-z\s]/g, '');
			jQuery(this).val(c);
		});
	});
}


/**
 * Makes the "group wiki enabled" tickbox show or hide the rest of the wiki creation/admin controls.  Ticked = visible
 */ 
function toggleWikiCreateFields() {
	jQuery('#wiki-create-unticked').toggleClass('wiki-create-creen-hidden');
	jQuery('#wiki-create-fields').toggleClass('wiki-create-creen-hidden');
}