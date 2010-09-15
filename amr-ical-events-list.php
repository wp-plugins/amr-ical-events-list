<?php
/*
Plugin Name: AmR iCal Events List
Author URI: http://anmari.com/
Plugin URI: http://icalevents.anmari.com
Version: 3.0.4
Text Domain: amr-ical-events-list 
Domain Path:  /lang
Description: Display simple or highly customisable and styleable list of events.  Handles all types of recurring events, notes, journals, freebusy etc. Offers links to add events to viewers calendar or subscribe to whole calendar. Write Calendar Page</a>  and put [iCal http://yoururl.ics ] where you want the list of events.  To tweak: <a href="options-general.php?page=manage_amr_ical">Manage Settings Page</a>,  <a href="widgets.php">Manage Widget</a>.
		
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
//  NB Change version in list main tooo define('AMR_ICAL_LIST_VERSION', '3.0.1');
require_once('amr-ical-events-list-main.php');	
require_once('amr-ical-config.php');
require_once('amr-ical-list-admin.php');
require_once('amr-import-ical.php');
require_once('amr-rrule.php');
require_once('amr-ical-uninstall.php');
require_once('amr-upcoming-events-widget.php');
require_once('amr_date_i18n.php');
define( 'AMR_BASENAME', plugin_basename( __FILE__ ) );	
add_filter('plugin_action_links', 'amr_plugin_action', 8, 2);	
function amr_plugin_action($links, $file) {
	/* create link */
	if ( $file == AMR_BASENAME ) {
		if (function_exists('amr_events_settings_menu')) 
		array_unshift($links,'<a href="admin.php?page=manage_amr_ical">'. __('Settings','amr_ical_list_lang').'</a>' );
		else 
		array_unshift($links,'<a href="options-general.php?page=manage_amr_ical">'. __('Settings','amr_ical_list_lang').'</a>' );
	}
	return $links;
	} // end plugin_action()
?>