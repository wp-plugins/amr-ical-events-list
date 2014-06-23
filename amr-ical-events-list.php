<?php
/*
Plugin Name: amr events calendar or lists with ical files
Author: anmari
Author URI: http://anmari.com/
Plugin URI: http://icalevents.com
Version: 4.7
Text Domain: amr-ical-events-list
Domain Path:  /lang

Description: Display simple or highly customisable and styleable list of events.  Handles all types of recurring events, notes, journals, freebusy etc. Offers links to add events to viewers calendar or subscribe to whole calendar.  Write Calendar Page</a>  and put [iCal http://yoururl.ics ] where you want the list of events of an ics file and [events] to get internal events.      To tweak: <a href="admin.php?page=manage_amr_ical">Manage Settings Page</a>,  <a href="widgets.php">Manage Widget</a>.

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
//  NB Change version in list main tooo eg define('AMR_ICAL_LIST_VERSION', '3.0.1');

define( 'AMR_BASENAME', plugin_basename( __FILE__ ) );

	require_once('includes/amr-ical-groupings.php'); // must be before for shortcode function
	require_once('includes/amr-ical-config.php');
	require_once('includes/amr-ical-events-list-main.php');
	require_once('includes/amr-import-ical.php');
	require_once('includes/amr-rrule.php');
	require_once('includes/amr-upcoming-events-widget.php');
	require_once('includes/amr_date_i18n.php');
	require_once('includes/amr-ical-calendar.php');
	require_once('includes/amr-ical-pretty-print.php');
	require_once('includes/functions.php');
	require_once('includes/amr-ical-plugin-form-html.php');

if (is_admin()	) {  // are we in admin territory
	require_once('includes/amr-ical-list-admin.php');
}
?>