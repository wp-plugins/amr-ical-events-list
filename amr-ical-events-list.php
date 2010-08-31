<?php
/*
Plugin Name: AmR iCal Events List
Author URI: http://anmari.com/
Plugin URI: http://icalevents.anmari.com
Version: 2.9.4
Text Domain: amr-ical-events-list 
Domain Path:  /lang

Description: Display simple or highly customisable and styleable list of events.  Handles all types of recurring events, notes, journals, freebusy etc. <a href="http://webdesign.anmari.com/web-tools/donate/">Donate</a>,  <a href="http://wordpress.org/extend/plugins/amr-ical-events-list/"> rate it</a>, or link to it. <a href="page-new.php">Write Calendar Page</a>  and put [iCal http://yoururl.ics ] where you want the list of events.  To tweak: <a href="options-general.php?page=manage_amr_ical">Manage Settings Page</a>,  <a href="widgets.php">Manage Widget</a>.
More advanced:  [iCal webcal://somecal.ics http://aonthercal.ics listype=2] .  If your implementation looks good, different configuration, unique css etc - register at the plugin website, and write a "showcase" post, linkingto the website you have developed.  NOTE: another update will be through soon so if you have no timezone problem, you could wait for the next update.  <strong>NB: If upgrading, then you must change your calendar page to shortcode usage if you have not already done so.  Do not use [iCal:url] - that ':' will cause problems.</strong>

Features:
- Handles events, todos, notes, journal items and freebusy info
- Control over contents and styling from the admin menu's.
- Lots of css tags for innovative styling
- minimalist default css or use your own
- a separate widget list of events available
Data Structure:
[DTSTART]
[DTEND]
[RRULE]
	[0]
		[byday]
		[specbyday]
		[until]
		[freq]
		[wkst]
[DTSTAMP]
 ["UID"]
 ["CREATED"]
 ["DESCRIPTION"] array?
	[0]
["LAST-MODIFIED"]
  ["LOCATION"]
  ["STATUS"] ? array?
  [SUMMARY]
  ["TRANSP"]
  ["type"] VEVENT
  ["name"]  cal0
		
/*  Copyright 2009  AmR iCal Events List  (email : anmari@anmari.com)
    This program is free software; you can redistribute it and/or modify
    it under the terms of the GNU General Public License as published by
    the Free Software Foundation; either version 2 of the License, or
    (at your option) any later version.

    This program is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License see <http://www.gnu.org/licenses/>.
    for more details.
*/
require_once('amr-ical-events-list-main.php');	
require_once('amr-ical-config.php');
require_once('amr-ical-list-admin.php');
require_once('amr-import-ical.php');
require_once('amr-rrule.php');
require_once('amr-ical-uninstall.php');
require_once('amr-upcoming-events-widget.php');
require_once('amr_date_i18n.php');
//$f = WP_PLUGIN_DIR.'/amr-events/amr-ical-events-plus.php'; 
//if (file_exists($f))
//	include_once('amr-ical-events-plus.php');   /* include the plus functions if they have been purchased  */

	/**
	Adds a link directly to the settings page from the plugin page
	*/
define( 'AMR_BASENAME', plugin_basename( __FILE__ ) );	
add_filter('plugin_action_links', 'amr_plugin_action', 8, 2);	
function amr_plugin_action($links, $file) {
	/* create link */
	if ( $file == AMR_BASENAME ) {
		if (function_exists('amr_events_settings_menu')) 
		array_unshift($links,'<a href="admin.php?page=manage_amr_ical">'. __('Settings','amr-ical-events-list').'</a>' );
		else 
		array_unshift($links,'<a href="options-general.php?page=manage_amr_ical">'. __('Settings','amr-ical-events-list').'</a>' );
	}
 
	return $links;
	} // end plugin_action()
?>