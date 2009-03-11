<?php
/* This is the amr-ical config section file */

global $amr_components;
global $amr_calprop;
global $amr_colheading;
global $amr_compprop;
global $amr_groupings;
global $amr_limits;
global $amr_formats;
global $amr_csize;
global $amr_validrepeatablecomponents;
global $amr_validrepeatableproperties;
global $amr_wkst;
global $amr_globaltz;
global $amr_utctz;
global $amrdf;
global $amrtf;

define('WP_DEBUG', true);  /* when testing only */
if (isset($_REQUEST["debug"]) ) {
	define('ICAL_EVENTS_DEBUG', true);
	}
else {define('ICAL_EVENTS_DEBUG', false);}



$amr_wkst = 'MO';   /* Generally the ical file should specify the WKST, so this should be unneccssary */
$amr_utctz = timezone_open('UTC');

/* set to empty string for concise code */
define('AMR_NL',"\n" );
define('AMR_TB',"\t" );
define('AMRICAL_ABSPATH', WP_PLUGIN_URL . '/amr-ical-events-list/');
define('AMR_PHPVERSION_REQUIRED', '5.2.0');
define('ICAL_EVENTS_CACHE_TTL', 24 * 60 * 60);  // 1 day
define('IMAGES_LOCATION', AMRICAL_ABSPATH.'images/');
define('CLOSE_P','</p>'); /* required to fix p's ineserted in body content, so they will validate*/
define('OPEN_P','<p>');
define('TIMEZONEIMAGE','timezone.png');
define('MAPIMAGE','map.png');
define('CALENDARIMAGE','calendar.png');
define('ADDTOGOOGLEIMAGE','addtogoogle.png');
define('REFRESHIMAGE','refresh.png');
define('ICALSTYLEFILE', WP_PLUGIN_URL. '/amr-ical-events-list/'.'icallist.css');
define('ICALSTYLEPRINTFILE', WP_PLUGIN_URL. '/amr-ical-events-list/'.'icalprint.css');
define('ICAL_EVENTS_CACHE_DEFAULT_EXTENSION','ics');


$amr_validrepeatablecomponents = array ('VEVENT', 'VTODO', 'VJOURNAL', 'VFREEBUSY', 'VTIMEZONE');
$amr_validrepeatableproperties = array (
		'ATTACH', 'ATTENDEE',
		'CATEGORIES','COMMENT','CONTACT','CLASS' ,
		'DESCRIPTION', 'DAYLIGHT',
		'EXDATE','EXRULE',
		'FREEBUSY',
		'RDATE', 'RSTATUS','RELATED','RESOURCES','RRULE','RECURID', 
		'SEQ',  'SUMMARY', 'STATUS', 'STANDARD', 
		'TZOFFSETTO','TZOFFSETFROM',
		'URL', 
		'XPARAM', 'X-PROP');
	
/* used for admin field sizes */	
$amr_csize = array('Column' => '2', 'Order' => '2', 'Before' => '10', 'After' => '10', 'ColHeading' => '10');	
/* the default setup shows what the default display option is */
/* --------------------------------------------------  */

$amr_formats = array (

		'Time' => get_option('time_format'),
		'Day' => get_option('date_format'),
//		'Time' => '%I:%M %p',
//		'Day' => '%a, %d %b %Y',  
		'Month' => '%b, %Y',		/* %B is the full month name */
		'Year' => '%Y',			
		'Week' => '%U',
//		'Timezone' => 'T',	/* Not accurate enough, leave at default */
		'DateTime' => get_option('date_format').' '.get_option('time_format')
//		'DateTime' => '%d-%b-%Y %I:%M %p'   /* use if displaying date and time together eg the original fields, */
		);
		
function amr_getTimeZone($offset) {
    $timezones = array(
        '-12'=>'Pacific/Kwajalein',
        '-11'=>'Pacific/Samoa',
        '-10'=>'Pacific/Honolulu',
		'-9.5'=>'Pacific/Marquesas', 	
        '-9'=>'America/Juneau',
        '-8'=>'America/Los_Angeles',
        '-7'=>'America/Denver',
        '-6'=>'America/Mexico_City',
        '-5'=>'America/New_York',
		'-4.5'=>'America/Caracas',
        '-4'=>'America/Manaus',
        '-3.5'=>'America/St_Johns',
        '-3'=>'America/Argentina/Buenos_Aires',
        '-2'=>'Brazil/DeNoronha',
        '-1'=>'Atlantic/Azores',
        '0'=>'Europe/London',
        '1'=>'Europe/Paris',
        '2'=>'Europe/Helsinki',
        '3'=>'Europe/Moscow',
        '3.5'=>'Asia/Tehran',
        '4'=>'Asia/Baku',
        '4.5'=>'Asia/Kabul',
        '5'=>'Asia/Karachi',
        '5.5'=>'Asia/Calcutta',
		'5.75'=>'Asia/Katmandu',
        '6'=>'Asia/Colombo',
		'6.5'=>'Asia/Rangoon',
        '7'=>'Asia/Bangkok',
        '8'=>'Asia/Singapore',
        '9'=>'Asia/Tokyo',
        '9.5'=>'Australia/Darwin',
        '10'=>'Pacific/Guam',
        '11'=>'Australia/Sydney',
		'11.5'=>'Pacific/Norfolk',
        '12'=>'Asia/Kamchatka',
		'13'=>'Pacific/Enderbury',
		'14'=>'Pacific/Kiritimati'
    );
		if (isset($timezones[strval($offset)])) return ($timezones[strval($offset)]);
		else return false; 	
	}
	
	/* ---------------------------------------------------------------------------*/

				
if (function_exists ('get_option') and ($d = get_option ('date_format'))) $amr_formats['Day'] = $d;		
if (function_exists ('get_option') and ($d = get_option ('time_format'))) $amr_formats['Time'] = $d;	
if (function_exists ('get_option') and ($d = get_option ('timezone_string'))) {
/* If the wordpress timezone plug in is being used, then use that timezone as our default.  Else use first calendar ics file ?  */
	 $amr_globaltz = timezone_open($d);
	// date_default_timezone_set ($d);
} else {  /* *** the timezoneplugin not here, let us try with the normal offset */
	if (function_exists ('get_option') and ($gmt_offset = get_option ('gmt_offset'))) 
		$amr_globaltz = timezone_open(amr_getTimeZone($gmt_offset));
	}
if (isset($_REQUEST["tz"])) { /* If a tz is passed in the query string, then use that as our global timezone, rather than the wordpress one */
	$d = ($_REQUEST['tz']);
	if (!($amr_globaltz = timezone_open($d))) {
		echo "<h1>Ivalid Timezone passed in query string</h1>";  /* *** does not trap the eror this way, need to validate before */
	 };
	//date_default_timezone_set ($_REQUEST['tz']);
}

$amr_general = array (
		"Name" => 'Default'
		);   
		
$amr_limits = array (
		"Events" => 30,
		"Days" => 30,
		"cache" => 24);  /* hours */
		
$amr_components = array (
		"VEVENT" => true,
		"VTODO" => true,
		"VJOURNAL" => false,
		"VFREEBUSY" => true
//		"VTIMEZONE" => false    /* special handling required if we want to process this - for now we are going to use the php definitions rather */
		);
		
$amr_groupings = array (
		"Year" => false,
		"Quarter" => false,
		"Astronomical Season" => false,
		"Traditional Season" => false,
		"Western Zodiac" => false,
		"Month" => true,
		"Week" => false,
		"Day" => false
		);

$amr_colheading = array (
	'1' => __('When', 'amr-ical-events-list'),
	'2' => __('What', 'amr-ical-events-list'),
	'3' => __('Where', 'amr-ical-events-list')
	);	
		
$dfalse = array('Column' => 0, 'Order' => 1, 'Before' => '', 'After' => '');
$dtrue = array('Column' => 1, 'Order' => 1, 'Before' => '', 'After' => '');
$dtrue2 = array('Column' => 2, 'Order' => 1, 'Before' => '', 'After' => '');


$amr_calprop = array (
		'X-WR-CALNAME'=> array('Column' => 1, 'Order' => 1, 'Before' => '', 'After' => ''),
		'X-WR-CALDESC'=> $dfalse,
		'X-WR-TIMEZONE'=> array('Column' => 1, 'Order' => 2, 'Before' => '', 'After' => ''),
		'icsurl'=> array('Column' => 1, 'Order' => 2, 'Before' => '', 'After' => ''),
		'addtogoogle' => array('Column' => 1, 'Order' => 5, 'Before' => '', 'After' => ''),
		/* for linking to the ics file, not intended as a display field really unless you want a separate link to it, intended to sit behind name, with desc as title */
		'LAST-MODIFIED' => $dtrue
//		'CALSCALE'=> $dfalse,
//		'METHOD'=> $dfalse,
//		'PRODID'=> $dfalse,
//		'VERSION'=> $dfalse,
//		'X-WR-RELCALID'=> $dfalse
		);  

/* NB need to switch some field s on for initial plugin view.  This will be common default for all, then some are customised separately */
$amr_compprop = array 
	(
	'Descriptive' => array (
		'SUMMARY'=> array('Column' => 2, 'Order' => 10, 'Before' => '', 'After' => ''),
		'DESCRIPTION'=> array('Column' => 2, 'Order' => 20, 'Before' => '', 'After' => ''),
		'LOCATION'=> array('Column' => 2, 'Order' => 32, 'Before' => '', 'After' => ''),
		'map'=> array('Column' => 2, 'Order' => 30, 'Before' => '', 'After' => ''),
		'addevent' => array('Column' => 2, 'Order' => 1, 'Before' => '', 'After' => ''),
		'GEO'=> $dfalse,
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
		'EventDate' => array ('Column' => 1, 'Order' => 1, 'Before' => '', 'After' => ''), /* the instnace of a repeating date */
		'StartTime' => array('Column' => 1, 'Order' => 2, 'Before' => '', 'After' => ''),
		'EndDate' => array('Column' => 1, 'Order' => 3, 'Before' => 'Until ', 'After' => ''),
		'EndTime' => array('Column' => 1, 'Order' => 4, 'Before' => '', 'After' => ''),
//		'DTSTART'=> $dfalse,
//		'DTEND'=> $dfalse,
		'DUE'=> $dfalse,
		'DURATION'=> $dfalse,
		'COMPLETED'=> $dfalse,
		'FREEBUSY'=> $dfalse,
		'TRANSP'=> $dfalse),
//	'Time Zone' => array (
//		'TZID'=> $dtrue,  /* but only show if different from calendar TZ */
//		'TZNAME'=> $dfalse,
//		'TZOFFSETFROM'=> $dfalse,
//		'TZOFFSETTO'=> $dfalse,
//		'TZURL'=> $dfalse),
	'Relationship' => array ( 
		'ATTENDEE'=> $dfalse,
		'CONTACT'=> $dtrue,
		'ORGANIZER'=> $dtrue,
//		'RECURRENCE-ID'=> $dfalse,
		'RELATED-TO'=> $dfalse,
		'URL'=> array('Column' => 0, 'Order' => 10, 'Before' => '', 'After' => '')
//		,'UID'=> $dfalse
		),
//	'Recurrence' => array (  /* in case one wants for someone reason to show the "repeating" data, need to create a format rule for it then*/
//		'EXDATE'=> $dfalse,
//		'EXRULE'=> $dfalse,
//		'RDATE'=> $dfalse,
//		'RRULE'=> $dfalse),
	'Alarm' => array (
		'ACTION'=> $dfalse,
		'REPEAT'=> $dfalse,
		'TRIGGER'=> $dfalse),
	'Change Management'	=> array ( /* optional and/or for debug purposes */
//		'CREATED'=> $dfalse,
//		'DTSTAMP'=> $dfalse,
//		'SEQUENCE'=> $dfalse,
		'LAST-MODIFIED' => $dfalse
		)
	);

	/* -------------------------------------------------------------------------------------------------------------*/
	
	function amr_ical_showmap ($text) {
	/* this is used to determine what should be done if a map is desired - a link to google behind the text ? or some thing else  */
		return('<a href="http://maps.google.com/maps?q='
		.$text.'" target="_BLANK"'
		.' title="'.__('Show location in Google Maps','amr-ical-events-list').'" >'
		.'<img src="'.IMAGES_LOCATION.MAPIMAGE.'" alt="' 
		.__('Show in Google map','amr-ical-events-list')     
		.'" class="amr-bling" /> </a>');
	}	
	/* -------------------------------------------------------------------------------------------------------------*/
	/* This is used to tailor the multiple default listing options offered.  A new listtype first gets the common default */

	
	function customise_listtype($i)
	{ /* sets up some variations of the default list type*/
	global $amr_options;

	switch ($i)
		{	
		case 2: 
			$amr_options[$i]['general']['Name']='On Tour';
			$amr_options[$i]['compprop']['Descriptive']['LOCATION']['Column'] = 2;
			$amr_options[$i]['compprop']['Descriptive']['DESCRIPTION']['Column'] = 3;
			$amr_options[$i]['compprop']['Descriptive']['SUMMARY']['Column'] = 3;
			$amr_options[$i]['heading']['2'] = __('Venue','amr-ical-events-list');
			$amr_options[$i]['heading']['3'] = __('Description','amr-ical-events-list');
			break;
		case 3: 
			$amr_options[$i]['general']['Name']='Timetable';
			foreach ($amr_options[$i]['grouping'] as $g=>$v) {$amr_options[$i]['grouping'][$g] = false;}
			$amr_options[$i]['grouping']['Day'] = true;		
			$amr_options[$i]['compprop']['Date and Time']['EventDate']['Column'] = 0;
			$amr_options[$i]['compprop']['Date and Time']['EndDate']['Column'] = 0;
			$amr_options[$i]['compprop']['Descriptive']['LOCATION']['Column'] = 3;
			$amr_options[$i]['compprop']['Descriptive']['map']['Column'] = 0;
			$amr_options[$i]['compprop']['Descriptive']['addevent']['Column'] = 3;
			$amr_options[$i]['heading']['2'] = __('Date','amr-ical-events-list');
			$amr_options[$i]['heading']['2'] = __('Class','amr-ical-events-list');
			$amr_options[$i]['heading']['3'] = __('Room','amr-ical-events-list');
			$amr_options[$i]['format']['Day']='l, j M';
			break;
		case 4: 
			$amr_options[$i]['general']['Name']='Widget'; /* No groupings, minimal */
			$amr_options[$i]['format']['Day']='M j';
			foreach ($amr_options[$i]['grouping'] as $g=>$v) {$amr_options[$i]['grouping'][$g] = false;}
			/* No calendar properties for widget - keep it minimal */
			foreach ($amr_options[$i]['calprop'] as $g => $v) 
				{$amr_options[$i]['calprop'][$g]['Column'] = 0;}
			foreach ($amr_options[$i]['compprop'] as $g => $v) 
				foreach ($v as $g2 => $v2) {$amr_options[$i]['compprop'][$g][$g2]['Column'] = 0;}
			$amr_options[$i]['compprop']['Date and Time']['EventDate']['Column'] = 1;
			$amr_options[$i]['compprop']['Date and Time']['StartTime']['Column'] = 1;
			$amr_options[$i]['compprop']['Date and Time']['EndDate']['Column'] = 1;
			$amr_options[$i]['compprop']['Date and Time']['EndTime']['Column'] = 1;
			$amr_options[$i]['compprop']['Descriptive']['SUMMARY']['Column'] = 1;
			$amr_options[$i]['compprop']['Descriptive']['SUMMARY']['Order'] = 10;
			$amr_options[$i]['heading']['1'] = $amr_options[$i]['heading']['2'] = $amr_options[$i]['heading']['3'] = '';
			break;
		case 5: 
			$amr_options[$i]['general']['Name']='Alternative';
			$amr_options[$i]['grouping']['Year']  = true;		
			$amr_options[$i]['grouping']['Western Zodiac'] = true;
			$amr_options[$i]['compprop']['Date and Time']['EndDate']['Column'] = 2;
			$amr_options[$i]['compprop']['Date and Time']['EndTime']['Column'] = 2;
			$amr_options[$i]['compprop']['Descriptive']['SUMMARY']['Column'] = 3;
			$amr_options[$i]['compprop']['Descriptive']['DESCRIPTION']['Column'] = 3;
			$amr_options[$i]['compprop']['Descriptive']['LOCATION']['Column'] = 3;
			$amr_options[$i]['compprop']['Descriptive']['map']['Column'] = 3;
			break;	
		case 6: 
			$amr_options[$i]['general']['Name']='All for testing';
			foreach ($amr_options[$i]['grouping'] as $g => $v) {$amr_options[$i]['grouping'][$g] = true;}
			foreach ($amr_options[$i]['compprop'] as $g => $v) 
				foreach ($v as $g2 => $v2) 
				{ if ($v2['Column'] === 0) {
					$amr_options[$i]['compprop'][$g][$g2] 
					= array('Column' => 1, 'Order' => 1, 'Before' => "<em>", 'After' => "</em>");}
				}
			foreach ($amr_options[$i]['calprop'] as $g => $v) 
				{$amr_options[$i]['calprop'][$g] = array('Column' => 2, 'Order' => 1, 'Before' => '', 'After' => '');}
			$amr_options[$i]['calprop']['X-WR-CALNAME']['Column'] = 1;
			$amr_options[$i]['calprop']['X-WR-TIMEZONE']['Column'] = 1;
			foreach ($amr_options[$i]['component'] as $g=>$v) {$amr_options[$i]['component'][$g] = true;}
			$amr_options[$i]['general']["Css URL"] =
			'http://localhost/wptest/wp-content/plugins/amr-ical-events-list/icallist6.css';   /* If empty, then assume the blog stylesheet will cope, else could contain special one */
		
			break;		
		}
		return ( $amr_options[$i]);
	}
/* ---------------------------------------------------------------------*/	
	function new_listtype()
	{
	global $amr_calprop;
	global $amr_colheading;
	global $amr_compprop;
	global $amr_groupings;
	global $amr_components;
	global $amr_limits;
	global $amr_formats;
	global $amr_general;
	
	$amr_newlisttype = (array 
		(
		'general' => $amr_general,
		'format' => $amr_formats,
		'heading' => $amr_colheading,
		'calprop' => $amr_calprop, 
		'component' => $amr_components,
		'grouping' => $amr_groupings,
		'compprop' => $amr_compprop,
		'limit' => $amr_limits
		)
		);
	return $amr_newlisttype;
	}
function Quarter ($D)
{ 	/* Quarters can be complicated.  There are Tax and fiscal quarters, and many times the tax and fiscal year is different from the calendar year */
	/* We could have used the function commented out for calendar quarters. However to allow for easier variation of the quarter definition. we used the limits concept instead */
	/* $D->format('Y').__(' Q ').(ceil($D->format('n')/3)); */
return date_season('Quarter', $D); 
}
function Meteorological ($D)
{return date_season('Meteorological', $D);  }
function Astronomical_Season ($D)
{return date_season('Astronomical', $D);  }
function Traditional_Season ($D)
{return date_season('Traditional', $D);  }
function Western_Zodiac ($D){  
return date_season('Zodiac', $D);  }

function date_season ($type='Meteorological',$D)
{ 	/* Receives ($Dateobject and returns a string with the Meterological season by default*/
	/* Note that the limits must be defined on backwards order with a seemingly repeated entry at the end to catch all */

	if (!(isset($D))) $D =  date_create();
	$Y = amr_format_date('Y',$D);
    $limits ['Quarter']=array(
	
	/* for different quarters ( fiscal, tax, etc,) change the date ranges and the output here  */
		'/12/31'=> $Y.' Q1',	
		'/09/31'=> $Y.' Q4',
		'/06/30'=> $Y.' Q3',
		'/03/31'=> $Y.' Q2',
		'/01/00'=> $Y.' Q1',				
		);   
   
   $limits ['Meteorological']=array(
		'/12/01'=>'N. Winter, S. Summer',
		'/09/01'=>'N. Fall, S. Spring',
		'/06/01'=>'N. Summer, S. Winter',
		'/03/01'=>'N. Spring, S. Autumn',
		'/01/00'=>'N. Winter, S. Summer'
		);  
		
	$limits ['Astronomical']=array( 
		'/12/21'=>'N. Winter, S. Summer',
		'/09/23'=>'N. Fall, S. Spring',
		'/06/21'=>'N. Summer, S. Winter',
		'/03/21'=>'N. Spring, S. Autumn',
		'/01/00'=>'N. Winter, S. Summer'
		);  
		
	$limits ['Traditional']=array(
	/*  actual dates vary , so this is an approximation */
		'/11/08'=>'N. Winter, S. Summer',
		'/08/06'=>'N. Fall, S. Spring',
		'/06/05'=>'N. Summer, S. Winter',  
		'/02/05'=>'N. Spring, S. Autumn',
		'/01/00'=>'N. Winter, S. Summer'
		);  		
		
	$limits ['Zodiac']=array(
	/*  actual dates vary , so this is an approximation */
		'/12/22'=>'Capricorn',
		'/11/22'=>'Sagittarius',
		'/10/23'=>'Scorpio',
		'/09/23'=>'Libra',
		'/08/23'=>'Virgo',
		'/07/23'=>'Leo',
		'/06/21'=>'Cancer',
		'/05/21'=>'Gemini',
		'/04/20'=>'Taurus',
		'/03/21'=>'Aries',
		'/02/19'=>'Pisces',
		'/01/20'=>'Aquarius',
		'/01/00'=>'Capricon',		
		); 	

	/* get the current year */
   foreach ($limits[$type] AS $key => $value) 
   {			  
	/* add the current year to the limit */
       $limit = $key; 
	   $input = amr_format_date ('/m/d', $D);
		/* if date is later than limit, then return the current value, else continue to check the next limit */	

       if ($input > $limit) {  	 
			return $value;   
	   }
   }
}	
/* -----------------------------------------------------------------------------------------------------*/

function amrwidget_defaults()
{
return (array (
	'title' => _e('Upcoming Events', 'amr-ical-events-list'),
	'urls' => 'http://www.google.com/calendar/ical/0bajvp6gevochc6mtodvqcg9o0%40group.calendar.google.com/public/basic.ics',
	'listtype' => 4,
	'limit' => 5,
	'moreurl' => WP_HOME.'/calendar'
));
}

global	$gnu_freq_conv;
$gnu_freq_conv = array (
/* used to convert from ical FREQ to gnu relative items for date strings useed by php datetime to do maths */
			'DAILY' => 'day',
			'MONTHLY' => 'month',
			'YEARLY' =>  'year',
			'WEEKLY' => 'week',
			'HOURLY' => 'hour',
			'MINUTELY' => 'minute',
			'SECONDLY' => 'second'
			);
			
function amr_give_credit() {
		/* The credit text styling is designed to be as subtle as possible (small font size with leight weight text, and right aligned, and at the bottom) and fit in within your theme as much as possible by not styling colours etc */
		/* Do not remove credits or change the link text if you have not paid for the software.  You may however style it more gently, and/or subtly to fit in within your theme */
		/* If you wish to remove the credits, then payments are accepted at http://webdesign.anmari.com/web-tools/donate/ - do not be trivial please, rather leave the credit in */
		return (
		'<span class="amrical_credit" style="float: right;" >'
		.'<a title="Ical Upcoming Events List '.AMR_ICAL_VERSION.'" '
		.'href="http://webdesign.anmari.com/web-tools/plugins-and-widgets/ical-events-list/">Upcoming events'
		.' by anmari</a></span>'			
		);
}

