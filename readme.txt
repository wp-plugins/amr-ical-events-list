=== AmR Users ===
Contributors: anmari
Donate link: https://www.paypal.com/cgi-bin/webscr?cmd=_donations&business=anmari%40anmari%2ecom&item_name=AmRUsersPlugin
Tags: user, users, reports, lists, stats, statistics, members, membership, authors, subscribers, post counts, comment counts, csv, export
Version: 2.3.5
Requires at least: 2.7 
Tested up to: 3.0.1
Stable tag: trunk

== Description ==
Configurable user listings by meta keys and values, comment count and post count. Includes display, inclusion, exclusion, sorting configuration and an option to export to CSV.  Now also includes an option to add a list on the public side as a shortcode.  You must ensure that you suitably define the fields, lists and protection for the shortcode to manage your users privacy.

For more information, please see [amr-users at anmari.com](http://webdesign.anmari.com/plugins/users/) and [news at anmari.com](http://webdesign.anmari.com/category/plugins/user-lists/)

The admin settings area is aimed at an administrator, who will design and setup the reports for less skilled editors or website managers.  Some lists are provided pre-configured to get you going - you can add more or change these.

The fields you see listed will vary depending on the plugins that you have in use, and on the meta data that the plugins may have created.  In the Screenshots you may see data from subscribe 2, register plus and your members plugins.

Version 2 now has the start of a cacheing system to improve the response for large user sites.  Cache's will be updated on update of user records, or on manual request.  Regular cacheing (eg daily?) wil be added soon.  Note: The "whats in cron" plugin may be useful too.

If anyone would like to offer some translations for other languages, please do.  The Code Styling Localisation Plugin is very useful for this.

You may also be interested in [amr-user-templates](http://webdesign.anmari.com/plugins/amr-user-templates/) which will be launching soon.  This will allow you to design and tailor the initial screens (dashboard boxes, screen options etc) of any new users (or reset existing) by role. Monitor the rss feed.

Please check your system meets the following requirements:
*	PHP > 5.2 
*	The filter extension is enabled by default as of PHP 5.2.0 http://au.php.net/manual/en/filter.installation.php
* 	The DateTime Class enabled (should be in php 5.2) http://php.net/manual/en/function.date-create.php


If you do not know how to check your php install, you can use the WordPress PHP Info by Christopher Ross. See http://wordpress.org/extend/plugins/wordpress-php-info/
After activating, find "php info" under settings, 
*   the php version is noted at the top,
*   scroll down till you see the "date" section - check that the datetime class is enabled
*   scroll further till you see the "filter" section - if Input Validation and Filtering is enabled, then you are all set!.

Suggestion: Do not use Register Plus "select" custom fields if you wish to be able to isolated those values in this plugin.   Register Plus stores multiple values of these as  string, not as multiple meta records, or an array or object.  Since it is entitely conceivable that a field may validly have a comma, this plugin cannot simply break down strings with commas.  Rather use multiple checkbox fields with no "extra options".


= More to come =
Please add a comment to the site about any features you would like to see - a contribution towards that would also help! Planned features:
*   possibly allow dropdown selection for those fields that do not have too many values - maybe configure threshold
*   add statistic reports with groupings and totals


== Changelog ==
= Version 2.3.6 coming....=
*   Now you can build reports on custom post types too!  Amazingly useful for one of my other projects that I'll be telling you about soon.
*   The admin settings screens will now not show a field if it is not switched n - hopefully will be faster.  


= Version 2.3.5 =
*	Can now deal with content that has quotes etc - add slashes and strip slashes.  And of course foreign characters work too - just make sure you have all your encodings sorted in your wp site and open office or excel. See the plugin site formore info 
*   CSV Filtered option renamed as .txt export option with some other tweaks too - see the hover text.  Aimed at those poor ms excel users... maybe it will help a bit. 
*   Added ability to request regular rebuild of cache for those who have plugins that do not trigger the update of the user profile.   

= Version 2.3.4 =
*	Changed display order to allow decimals and interpret decimals as follows: 3.1 and 3.2 mean the first and second values in the third column.  This should give a lot more flexibility in formatting, although I think still need flexibility in applying links, and in css without having to mdofiy theme etc - it's coming....
*   Added 'before' and 'after' fields so you can add html around the values, especially if combining fields. Eg: a "&nbsp;" or a "<br />".  
*   Bug Fix on sorting, now sort values before applying links etc, and before paginating!

= Version 2.3.3 =
*	Bug Fix - Plays better with S2member now - I'm embarrassed, I'm not even going to tell you what it was.   
*   Fixed a few other minor details that were annoying me - highlight some text on the log screen etc.
*   Add option to rebuild cache for ALL reports in one go.

= Version 2.3.2 =
*	Added option to not have sorting links on the lists.  Specify this next to list name in main settings.
*   If you run lists without configuring nice names, plugin will attempt to make column headings look nice anyway.
*   Bug & Feature Request Tracking Proper Bug notes with adequate detail and Feature Requests may be logged or voted for at [bugs.anmari.com](http://bugs.anmari.com).  the $vote is an indicative amount to indicate how much you want a feature.  

= Version 2.3.1 =
*    fixed bug for versions less than 3.0 that do not have the list-users capability.  User List access also allowed if user has 'edit users' access.
*    switched defaults headings request for shortcode, so that by default headings will be shown.  If you do not want headings user headings=false in the shortcode.
*    Added option to have "carriage returns" filtered out of your csv export lines as requested by [wjm](http://webdesign.anmari.com/exporting-a-wordpress-user-list-to-a-csv-file/#comment-4311)
    
= Version 2.3 =
*    Widget is now available in a rudimentary fashion.  Please ensure that you design reports carefully, not too many columns, and not too many entries.  It is using the same code as the shortcode, without the headings.  Some themes do not take kindly to tables in the sidebar, so please test thoroughly with the size of data and theme you expect to use.
*    Changed capabilities to use new in 3.0 'list_users'.  So now if user can 'manage options' they can configure the reports.  If they can 'list users' they can access the user lists and export to csv too.
*    Fixed 'privacy' bug - an editor or person able to publish posts would have been able to access the user lists via shortcode even if they did not have capability to 'list users'.  Each list now has a public checkbox.  Only 'public' lists may be accessed via the shortcode by people who do not have the 'list users' capability.  If the shortcode requests a non public list, rather than display a visible error,  a comment will be written to the page for your information when testing.
*    Removed forced ID in first column on display - still appears in csv. Is required in cache for additional field functions on display.
*    The user url column will now contain clickable urls if you request that column to be displayed.
*    CSV export link had the wrong hover text, although it did the right action - fixed.
*    Removed the superfluous links at top of view user lists - use the links in the side menu.  These were causing a problem for some people in some browsers.


= Version 2.2.3 =
*    fixed situation where many lists, or long names caused the nav menu to be off the page in some browsers.  Added whitespace: normal to override wordpress admin default styling.  Thanks to wjm for bringing it to my attention and his suggested code. See http://webdesign.anmari.com/exporting-a-wordpress-user-list-to-a-csv-file/comment-page-1/#comment-4311
*    other minor html generation and/or css changes.
*    tested in wp 3.0.  Added some additional "excluded" fields added or changed in wp 3.0 to avoid cluttering up the list of possible fields.  See ameta-includes.php for the list.

= Version 2.2.2 =
*    CSV bug fix - last line was being missed on csv export!


= Version 2.2.1 =
*    Apologies - a little bug got introduced when users do not values in some fields - use version 2.2.1 or 2.1.

= Version 2.2 =
*    Applied a bit more rigour to the code, no major functionality change.
*    Added the limited comment total functionality back with a warning about it's usage - see href="http://webdesign.anmari.com/comment-totals-by-authors/
*    Fixed bug where htmlentities was used instead of htmlspecialchars.  This messed up foreign characters.
*    Added security check that only users who can edit-users may rebuild cache etc. NOTE: there is no seurity check on who can see lists via the shortcode.  If you create a list and make it availble via shortcode, you are responsible for controlling access and/or determining the data displayed.


= Version 2.1 =
*    Fixed bug for people using php < 5.3 (me! too) and who may have had a comma in their user meta data.  The php function str_getcsv does not exist until php 5.3, and  my quick pseudo function did not anticipate commas within the user meta data (bad).  It now does although still a simple function tailored to this specific use.  So it has been renamed and if another plugin has defined a str_getcsv function, (or if using php 5.3 up), then that function will be used.
*    Also ran quick test using a wp 3.0 beta instance and all seems fine. 

= Version 2 =
*   Major change for sites with many users - all reports are prepared in background and cached.  New cache requested after every user update (at this point std user events only).  You can also request your own updates.  Currently no regular cache update set, but most likely this iwll be done in a future version.
*   Background Events are logged for visibility of what caused a cache request.  Log is cleaned up regularly.
*   Cache Status page 
*   'Role' added - this is not actually stored in the user meta tabel, but is 'prepared' or calculated by wordpress.  Many roles are allowed for.  The current version of wordpress just pops the first role up and serves it up as the role.  I have therefore called this 'first role' in case anyone has configured others.  You can of course change the name via the nice names settings.

= Version 1.4.5 =
*   Allowed for less than wordpress 2.8 for non essential plugin news in admin home
*   Allowed for situation where user has not configured 'nicenames' at all

= Version 1.4.4 =
*   Added exclusion of deprecated duplicate fields (eg: wordpress currently returns both user_description and description, regardless of what is in the database. Only the latter is required now).
*   0 post counts and comments will not be listed
*   if plugin wp-stats is enabled and a stats page has been specified in it's settings, then any non zero comment counts will link to a list of comments by the author (Note this only applies to registered users)
*   Fixed problem where updated nice names where not being correctly accessed.

= Version 1.4.3 =
*   Fixed addition of extra lists - now uses prev list as default entries.  NB Must configure and update before viewing.
*   Added RSS feed links to highlight any plugin related news

= Version 1.4.2 =
*   Hmm now using get_bloginfo('wpurl') not get_option!! - otherwise if no WP_SITEURL defined in config, admin loses colours!

= Version 1.4.1 =
*   Defined WP_SITEURL if not defined, using get_bloginfo('wp-url') (not 'siteurl') so both wordpress relocated, and standard wordpress will work, and it will be faster than calling bloginfo.

= Version 1.4 =
*   Changed get_bloginfo('url') to get_bloginfo('siteurl') for those that have wordpress relocated
*   Put the CSV export back - got temporarily lost due to adding possibility of not having it on the front end 
*   Thanks to kiwicam for quick pickup and detailed specific response!

= Version 1.3 =
*   Changed WP_SITEURL to get_bloginfo('url') for those that do not have WP_SITEUEL defined (was in edit link)
*   Added column titles to the csv file
*   Made some updates as suggested by http://planetozh.com/blog/2009/09/wordpress-plugin-competition-2009-43-reviews/.  Note that as we are not running any DB update queries, some of the comments are not strictly relevant.
*   added ability to access a list via shortcode. Your Themes table styling will apply.
*   improved ability to select data - can include only blanks, or exclude if blank.
*   the following fields will automaically be hyperlinked as follows:
*       email address - mailto link
*       user login - edit user link 
*       post count - author archive link

= Version 1.2 =
*   Fixed bug that had crept in where some aspects were not updating in the admin pages
*   Fixed problem with multiple exclusions and inclusions
*   Changed empty to check to null check as 0 is considered empty, but may be a valid inclusion or exclusion value.
*   Changed admin area to separate pages in attempt to simplify the setup.

= Version 1.1 =
*   Allowed for situation where there may be no user meta data for a user record.
*   Tested on 2.8.4

= Version 1.1 =
*   Fixed an inappropriate nonce security check which caused a plugin conflict.

= Version 1 =
*   Initial Listing

== Installation ==

From wordpress admin folder, click add new, search for "amr user", select and install.

OR 

1.  Download and Unzip the folder into your wordpress plugins folder.
2.  Activate the plugin through the 'Plugins' menu in WordPress
3.  You must configure this plugin for your environment:  
4.  Configure or add listings in the settings panel, Configure the nicenames, Configure the lists.
5.  For shortcode, create page or post, enter in text [userlist list=n].  Note some minor css is added - primarily your themes table css will be used.




== FAQ: == 

See author site: http://webdesign.anmari.com/category/plugins/user-lists/

== Screenshots ==

1. Default list 1
2. Default list 2
3. Default list 3
4. Main Settings Page
5. Configure Nice Names
6. Configure a list
7. CSV Export
8. CSV Imported again!
9. Shortcode simple
10. Shortcode with extras


