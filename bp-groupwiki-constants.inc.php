<?php
/*
 * A script containing useful constants defined to assist the group wiki.  Most importantly,
 * we abstract away file paths and URLs which relate to the structure of the group wiki to
 * this file.
 */


/*
 * Paths 
 */ 
define('WIKI_D_INSTALL_PATH', WP_PLUGIN_DIR.'/buddypress-group-wiki/');
define('WIKI_D_WEB_PATH', WP_PLUGIN_URL.'/buddypress-group-wiki/');
define('WIKI_D_WEB_CONTENT_PAGES', WIKI_D_WEB_PATH.'content-pages/');

/*
 * PHP scripts
 */
define('WIKI_S_COMMENT_SAVE', WIKI_D_WEB_CONTENT_PAGES.'wiki-comment-save.php');
define('WIKI_S_COMMENT_OPTIONS', WIKI_D_WEB_CONTENT_PAGES.'wiki-comment-options.php');
define('WIKI_S_EDIT_SAVE', WIKI_D_WEB_CONTENT_PAGES.'wiki-edit-save.php');
define('WIKI_S_REVISION_RESTORE', WIKI_D_WEB_CONTENT_PAGES.'wiki-revision-restore.php');
define('WIKI_S_REVISION_COMPARE', WIKI_D_WEB_CONTENT_PAGES.'wiki-revision-compare.php');


/*
 * Post metadata keys
 */
define('WIKI_M_PAGE_RESTORED', 'wiki_page_restored');
define('WIKI_M_COMMENT_DELETED_CONTENT', 'wiki_deleted_content');
define('WIKI_M_COMMENT_DELETED_BY', 'wiki_deleted_by');
define('WIKI_M_COMMENT_DELETED_DATE', 'wiki_deleted_date');
?>