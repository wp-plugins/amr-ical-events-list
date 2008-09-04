<?php
/* This is the amr-ical config section file */

/* setlocale(LC_ALL, 'German');  */

global $amr_components;
global $amr_calprop;
global $amr_compprop;
global $amr_groupings;
global $amr_limits;
global $amr_formats;
global $amr_csize;

/* set to empty string for concise code */
define('AMR_NL',"\n" );
define('AMR_TB',"\t" );
define('ICAL_EVENTS_DEBUG', false);
	
/* used for admin field sizes */	
$amr_csize = array('Column' => '2', 'Order' => '2', 'Before' => '10', 'After' => '10');	
/* the default setup shows what the default display option is */
$amr_formats = array (
		'Time' => '%I:%M %p',
		'Day' => '%a, %d %b %Y',  /* could check for % and do strtime?*/
		'Month' => '%b, %Y',		/* %B is the full month name */
		'Year' => '%Y',			
		'Week' => '%U',
		'Timezone' => 'T',	/* */
		'DateTime' => '%d-%b-%Y %I:%M %p'   /* use if displaying date and time together eg the original fields, */
		);

$amr_general = array (
		"Name" => 'Default',
		"Css URL" => get_bloginfo('wpurl').'/wp-content/plugins/amr-ical-events-list/icallist1.css');   /* If empty, then assume the blog stylesheet will cope, else could contain special one */
		
$amr_limits = array (
		"Events" => 10,
		"Days" => 10,
		"cache" => 4);
		
$amr_components = array (
		"VEVENT" => true,
		"VTODO" => true,
		"VJOURNAL" => true,
		"VFREEBUSY" => false,
		"VTIMEZONE" => false );
		
$amr_groupings = array (
		"Year" => false,
		"Month" => true,
		"Week" => false,
		"Day" => false,
		"Quarter" => false,
		"Astronomical Season" => false,
		"Traditional Season" => false,
		"Western Zodiac" => false,
		"Solar Term" => false
		);

$dfalse = array('Column' => 0, 'Order' => 1, 'Before' => '', 'After' => '');
$dtrue = array('Column' => 1, 'Order' => 1, 'Before' => '', 'After' => '');
$dtrue2 = array('Column' => 2, 'Order' => 1, 'Before' => '', 'After' => '');


$amr_calprop = array (
		'X-WR-CALNAME'=> $dfalse,
		'X-WR-CALDESC'=> $dfalse,
		'X-WR-TIMEZONE'=> $dfalse,
		'CALSCALE'=> $dfalse,
		'METHOD'=> $dfalse,
		'PRODID'=> $dfalse,
		'VERSION'=> $dfalse,
		'X-WR-RELCALID'=> $dfalse
		);  

/* NB need to switch some field s on for initial plugin view */
$amr_compprop = array 
	(
	'Descriptive' => array (
		'SUMMARY'=> array('Column' => 2, 'Order' => 1, 'Before' => '', 'After' => ''),
		'DESCRIPTION'=> array('Column' => 2, 'Order' => 10, 'Before' => '', 'After' => ''),
		'LOCATION'=> array('Column' => 2, 'Order' => 20, 'Before' => '', 'After' => ''),
		'GEO'=> array('Column' => 2, 'Order' => 21, 'Before' => '', 'After' => ''),
		'ATTACH'=> $dfalse,
		'CATEGORIES'=> $dfalse,
		'CLASS'=> $dfalse,
		'COMMENT'=> $dfalse,
		'PERCENT-COMPLETE'=> $dfalse,
		'PRIORITY'=> $dfalse,
		'RESOURCES'=> $dfalse,
		'STATUS'=> $dfalse
		),
	'Date and Time' => array (
		'EventDate' => array ('Column' => 1, 'Order' => 1, 'Before' => '', 'After' => ''),
		'StartTime' => array('Column' => 1, 'Order' => 2, 'Before' => '', 'After' => ''),
		'EndDate' => array('Column' => 1, 'Order' => 3, 'Before' => 'Until ', 'After' => ''),
		'EndTime' => array('Column' => 1, 'Order' => 4, 'Before' => '', 'After' => ''),
		'DTSTART'=> $dfalse,
		'DTEND'=> $dfalse,
		'DUE'=> $dfalse,
		'DURATION'=> $dtrue,
		'COMPLETED'=> $dfalse,
		'FREEBUSY'=> $dfalse,
		'TRANSP'=> $dfalse),
	'Time Zone' => array (
		'TZID'=> $dtrue,  /* only show if different from calendar TZ */
		'TZNAME'=> $dfalse,
		'TZOFFSETFROM'=> $dfalse,
		'TZOFFSETTO'=> $dfalse,
		'TZURL'=> $dfalse),
	'Relationship' => array ( 
		'ATTENDEE'=> $dfalse,
		'CONTACT'=> $dtrue,
		'ORGANIZER'=> $dfalse,
		'RECURRENCE-ID'=> $dfalse,
		'RELATED-TO'=> $dfalse,
		'URL'=> array('Column' => 2, 'Order' => 10, 'Before' => '', 'After' => ''),
		'UID'=> $dfalse),
	'Recurrence' => array (
		'EXDATE'=> $dfalse,
		'EXRULE'=> $dfalse,
		'RDATE'=> $dfalse,
		'RRULE'=> $dfalse),
	'Alarm' => array (
		'ACTION'=> $dfalse,
		'REPEAT'=> $dfalse,
		'TRIGGER'=> $dfalse),
	'Change Management'	=> array (
		'CREATED'=> $dfalse,
		'DTSTAMP'=> $dfalse,
		'LAST-MODIFIED'=> $dfalse,
		'SEQUENCE'=> $dfalse)
	);
?>