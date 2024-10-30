=== BuddyPress Group Wiki ===
Contributors: David Cartwright, Stuart Anderson, Ryo Seo-Zindy
Tags: buddypress, activities, groups, wiki, groupwiki, collaboration, education
Requires at least: 3
Tested up to: 3
Stable tag: 1.8

This plugin provides simple group wiki functionality within BuddyPress.  REQUIRES WPMU!

== Description ==

NOTE: THIS PLUGIN REQUIRES WPMU!

This plugin provides simple group wiki functionality within BuddyPress.

A group admin can create a group wiki and corresponding group wiki pages.  Each page has settings (which can override the group privacy settings) to control access to the page both in terms of view access and edit access.  The group also has a shared document library for uploading files.  Page revisions are fully supported, as are revision compares and restores.  Activity stream updates for wiki edits are also created, based on an excerpt of the changed text.

The wiki pages are edited with tinymce for lots of wysiwyg loveliness.  We chose not to implement any kind of edit-lock, but users are warned if other people are editing the page at the same time.  They also receive a more noticable alert should someone else save a page whilst they are editing it.  Finally, after 30 minutes of viewing the wiki edit page, the page is automatically saved and the user is returned to the view screen (given a warning 5 minutes beforehand).

This plugin is licensed under the GNU AGPL.  Use it however you like.  Modify it however you like.  Provide any improvements to the code to the wordpress community for free.

http://www.fsf.org/licensing/licenses/agpl-3.0.html

Technical stuff you might want to know:

1. Each group wiki is actually a wordpress blog in the database.  This was done to take advantage of all the prebuilt WP functions for revisions, file uploads/media libraries/etc.

Where to get support:

http://namoo.co.uk

Possible future updates:

1. Global Wiki Directory
2. Site-wide wiki pages (not tied to a particular group)
3. Nested pages to allow for better categorisation/namespace type stuff
4. Improved navigation.  Substitution of top menu nav in groupwiki pages with breadcrumb nav
5. i18n support

Known bugs:

1. Slowness of tinymce to load.  Partially due to use of dev code and bloated plugins and partially due to dodgy implementation
2. On group deletion, wiki (blog) database tables + files are not deleted
3. Page edit save warnings (see above) are sometimes troublesome after multiple warnings

== Installation ==

1. Copy main plugin files to wp-content/plugins/buddypress-group-wikis/
2. Copy wikioverride folder to wp-content/themes/

== Changelog === 1.8 =* Fixed incorrect domain set during blog creation
= 1.7 =* Fixed some missing js files.= 1.6 =* Fixed a bug with frotend page creation.* Fixed a bug with group wikis being shown in the site blog lists.* Fixed a bug with group wiki creation in wordpress 3.0.= 1.5 =* Fixed a bug which was preventing saving of comments and wiki pages.
= 1.4 =
* Massive cleanup of code and comments.

= 1.3 =
* Some cleanup

= 1.2 =
* Fixed issue with blog domain/path on wikis.  

= 1.1 =
* Frontend page creation fixed.
* CSS, JS issues fixed.
* Some other stuff fixed.

= 1.0 =
* Initial release.  
* Not recommended for production sites.
* Please test and provide feedback.  

