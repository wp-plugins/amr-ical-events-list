<?php
/*
Plugin Name: AmR iCal Events List
Version: 2.4
Text Domain: amr-ical-events-list 
Author URI: http://anmari.com/
Plugin URI: http://icalevents.anmari.com
Description: Display highly customisable and styleable list of events from iCal sources.   <a href="http://webdesign.anmari.com/web-tools/donate/">Donate</a>,  <a href="http://wordpress.org/extend/plugins/amr-ical-events-list/"> rate it</a>, or link to it. <a href="page-new.php">Write Calendar Page</a>  and put [iCal http://yoururl.ics ] where you want the list of events.  To tweak: <a href="options-general.php?page=manage_amr_ical">Manage Settings Page</a>,  <a href="widgets.php">Manage Widget</a>.
More advanced:  [iCal webcal://somecal.ics http://aonthercal.ics listype=2] .  If your implementation looks good, different configuration, unique css etc - register at the plugin website, and write a "showcase" post, linkingto the website you have developed.

Features:
- Handles events, todos, notes, journal items and freebusy info
- Control over contents and styling from the admin menu's.
- Lots of css tags for innovative styling
- minimalist default css or use your own
- a separate widget list of events available
*/
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

define('AMR_ICAL_VERSION', '2.4');
define('AMR_PHPVERSION_REQUIRED', '5.3.0');
define( 'AMR_BASENAME', plugin_basename( __FILE__ ) );

/*  these are  globals that we do not want easily changed -others are in the config file */
global $amr_options;
global $amrW;  /* set to W if running as widget, so that css id's will be different */
$amrW = '';

define('AMR_ICAL_VERSION', '2.3.8');
define('AMR_PHPVERSION_REQUIRED', '5.2.0');
	
if (!version_compare(AMR_PHPVERSION_REQUIRED, PHP_VERSION)) {
	echo( '<h1>'.__('Minimum Php version '.AMR_PHPVERSION_REQUIRED.' required for Amr Ical Events.  Your version is '.PHP_VERSION,'amr-ical-events-list').	'</h1>');
	}
	
if (!(class_exists('DateTime'))) {
	_e ('<h1>The <a href="http://au.php.net/manual/en/class.datetime.php"> DateTime Class </a> must be enabled on your system for this plugin to work. They may need to be enabled at compile time.  The class should exist by default in PHP version 5.2.</h1>',"amr-ical-events-list");}	


require_once('amr-ical-config.php');
require_once('amr-ical-list-admin.php');
require_once('amr-import-ical.php');
require_once('amr-rrule.php');
require_once('amr-ical-uninstall.php');
require_once('amr-upcoming-events-widget.php');

function amr_get_googletime($time)   {  
	$t = clone $time;
    $t->setTimezone(new DateTimeZone("UTC"));
    return ($t->format("Ymd\THis\Z"));
   } 
function amr_get_googledate($time)   {  
	$t = clone $time;
      $t->setTimezone(new DateTimeZone("UTC"));
      return ($t->format("Ymd"));
   }    
function amr_get_googleeventdate($e) {  
		if (isset ($e['StartTime'])) $d = (amr_get_googletime ($e['StartTime']));
		else if (isset ($e['EventDate'] )) $d = (amr_get_googledate ($e['EventDate'])); /* no time just a day */
		else return (''); /* we have no start date or start time */
		if (isset ($e['EndTime'])) $e = (amr_get_googletime ($e['EndTime']));
		else if (isset ($e['EndDate'])) $e = (amr_get_googledate ($e['EndDate']));
		else return ($d.'/'.$d);
		return ($d.'/'.$e);
   }   

function add_cal_to_google($cal) {
/* adds a button to add the current calemdar link to the users google calendar */
	return ('<a href="http://www.google.com/calendar/render?cid='.$cal.'" target="_blank"  title="'
	.__('Add this calendar to your google calendar', "amr-ical-events-list")
	.'"><img src="'
	.IMAGES_LOCATION.ADDTOGOOGLEIMAGE.'" border="0" alt="'
	.__("Add to your Google Calendar", "amr-ical-events-list")
	.'" class="amr-bling" /></a>');
}
function add_event_to_google($e) {
	$l = htmlspecialchars($e['LOCATION'] );

/* adds a button to add the current calemdar link to the users google calendar */
	$html = '<a href="http://www.google.com/calendar/event?action=TEMPLATE'
	.'&amp;text='.(htmlspecialchars(amr_just_flatten_array ($e['SUMMARY'])))
	/* dates and times need to be in UTC */
	.'&amp;dates='.amr_get_googleeventdate($e)
	.'&amp;details='.str_replace('\n','&amp;ltbr /&amp;gt',htmlspecialchars(amr_just_flatten_array ($e['DESCRIPTION'])))  /* Note google only allows simple html*/
	.'&amp;location='.$l
	.'&amp;trp=false'
	.'" target="_blank" title="'.__("Add event to your Google Calendar", "amr-ical-events-list").'" >'
	.'<img src="'.IMAGES_LOCATION.ADDTOGOOGLEIMAGE.'" alt="'
	.__("Add event to google" , "amr-ical-events-list"). '" border="0" class="amr-bling" /></a>';
	return ($html);
}

/*--------------------------------------------------------------------------------*/
function amr_echo_style_contents ($ical_style_file) {
/* This function is included as an option of you really want the style code to be echoed directly  */
		$contents = file_get_contents($ical_style_file); 
		if ($contents) {
			echo "\n".'<style type="text/css"> <!--For amrical -using css file '.$ical_style_file.' -->';
			echo $contents             // printing the file content of the file
			.'</style>'.AMR_NL;
			}
		else echo '<!-- Error getting contents of Ical Events List style:'.$ical_style_file.' -->'; 
		
}
/*--------------------------------------------------------------------------------*/
function amr_ical_events_style()  /* check if there is a style spec, and file exists */{
global $amr_options;
global $amr_listtype;

	$amr_options = get_option ('amr-ical-events-list');

	if (isset ($amr_options['own_css'])) 
		if ($amr_options['own_css']) {/* do nothing  the website will have the css in it's own style sheets */	 }
		else {
			if (($icalstylefile = $amr_options[$amr_listtype]['cssfile']) and file_exists($icalstylefile)) {
				echo AMR_NL.'<link rel="stylesheet" href="'
					.$icalstylefile.'" type="text/css" media="screen, print" />'.AMR_NL;
				echo AMR_NL.'<link rel="stylesheet" href="'
					.ICALSTYLEPRINTURL.'" type="text/css" media="print" />'.AMR_NL;
				}	
		}	
	else { /* no option - should not happen */
		echo '<!-- using default css for Amr Ical Events. -->';
		echo AMR_NL.'<link rel="stylesheet" href="'
			.ICALSTYLEURL.'" type="text/css" media="screen, print" />'.AMR_NL;
		echo AMR_NL.'<link rel="stylesheet" href="'
			.ICALSTYLEPRINTURL.'" type="text/css" media="print" />'.AMR_NL;	
	}

}
/* --------------------------------------------------  sort through the options that define what to display in what column, in what sequence, delete the non display and sort column and sequenc  */
function prepare_order_and_sequence ($orderspec){
//	foreach ($amr_options[$format]['compprop'] as $key => $row) 
	foreach ($orderspec as $key => $row) 
	{	if (( isset ($row['Column'])) && (!($row['Column']== "0") ))	
			$order[$key] = $row;	
	}
	if (!isset($order)) return;  /* Nothing is to be displayed */
	// Prepare to sort order for printing
	foreach ($order as $key => $row) 
	{
		if ( isset ($row['Column']))
		{	$col[$key]  = $row['Column'];
			$seq[$key] = $row['Order'];
		}
	}
	array_multisort($col, SORT_ASC, $seq, SORT_ASC, $order);
	return ($order);
}
/* --------------------------------------------------  */
function check_hyperlink($text) {  /* checks text for links and converts them to html code hyperlinks */
/*or use wordpress function make_clickable */

    // match protocol://address/path/
    $text = ereg_replace(
	'[a-zA-Z]+://([.]?[a-zA-Z0-9-])*([/]?[a-zA-Z0-9_-])*([/a-zA-Z0-9?&#\._=-]*)',
	"<a href=\"\\0\">\\0</a>", $text);
    
    // match www.something
    //$text = ereg_replace("(^| )(www([.]?[a-zA-Z0-9\-_/?&#%])*)", "\\1<a href=\"http://\\2\">\\2</a>", $text);
	$text = ereg_replace(
	'(^| |\n)(www([.]?[a-zA-Z0-9-])*)([/]?[a-zA-Z0-9_-])*([/a-zA-Z0-9?&#\._=-]*)',
	"<a href=\"\\0\">\\0</a>", $text);
	
	// could not figure out how to prevent it picking up the br's too, so fix afterwards
	$text = str_replace ('<br"', '"', $text);
	$text = str_replace ('</a> />', '</a><br />', $text);	
	// also fix any images , but  how


    return $text;
}
/* --------------------------------------------------  */
function amr_show_refresh_option() {

global $amr_globaltz;
global $amr_lastcache;
	$uri = htmlspecialchars($_SERVER[REQUEST_URI]);
	if (!stristr($uri,'nocache=true')) {
		if (stristr($uri,'?')) {	
		 $uri .= '&amp;nocache=true';	
		 }
	}
	date_timezone_set($amr_lastcache, $amr_globaltz);
	$t = $amr_lastcache->format(get_option('time_format').' T');

	//$amr_lastcache->setTimezone($la_time);
	return ( '<a class="refresh" href="'.$uri
		.'" title="'.__('Refresh Calendars ','amr_ical_events_list').$t
		.'"><img src="'.IMAGES_LOCATION.REFRESHIMAGE
		.'" border="0" class="amr-bling" alt="'.__('Refresh calendars','amr_ical_events_list').$t
		.'" />'
//		.'<span id="icalcachetime" >'
//					. '</span>
		.'</a>'
			);
}

/* --------------------------------------------------  */
	function amr_derive_calprop_further (&$p) {
	
		if (isset ($p['totalevents'])) $title = __('Total events: ').$p['totalevents'];	/* in case we have noename? ***/

		if (isset ($p['icsurl']))  {/* must be!! */

			if (isset ($p['X-WR-CALNAME'])) {
				$p['subscribe'] = sprintf(__('Subscribe to %s Calendar','amr_ical_events_list'), $p['X-WR-CALNAME']);
				$p['X-WR-CALNAME'] = '<a '
				.' title="'.$p['subscribe'].'"'
				.' href="'.$p['icsurl'].'">'
				.htmlspecialchars($p['X-WR-CALNAME'])
				.'</a>';	
			}
			else {
				$f = basename($p['icsurl'], ".ics");
				$p['subscribe'] = sprintf(__('Subscribe to %s Calendar','amr_ical_events_list'), $f);
				$p['X-WR-CALNAME'] = '<a '
				.' title="'.$p['subscribe'].'"'
				.' href="'.$p['icsurl'].'">'
				.$f
				.'</a>';
			}
		}
		if (isset ($p['X-WR-CALDESC'])) {
				$p['X-WR-CALDESC'] = nl2br2 ($p['X-WR-CALDESC']);
		}
		
 
		$p['addtogoogle'] = add_cal_to_google ($p['icsurl']);
		return ($p);
	}
/* --------------------------------------------------  */
function amr_list_properties($icals) {  /* List the calendar properties if requested in options  */
	global $amr_options; 
	global $amr_listtype;
	
	$order = prepare_order_and_sequence  ($amr_options[$amr_listtype]['calprop']);

	if (!($order)) return; 

	foreach ($icals as $i => &$p)	{ /* go through the options list and list the properties */
		amr_derive_calprop_further ($p);
		$prevcol = $col = ''; 
		$cprop = '';   
		foreach ($order as $k => $v)  /* for each column, */
		{	
			$col = $v['Column'];
			if (!($col === $prevcol)) /* then starting new col */
			{	if (!($prevcol === '')) { 
					$cprop .= AMR_NL.AMR_TB.'</ul></td> <!-- end of amrcol -->';
					}  /* end prev column */
				$cprop .= AMR_NL.AMR_TB.'<td><ul class="amrcol amrcol'.$col.'">';  /* start next column */
				$prevcol = $col;
			}			
			if (isset ($icals[$i][$k])) /*only take the fields that are specified in options  */
			{		
				$cprop .= AMR_NL.AMR_TB.'<li class="'.strtolower($k).'">'.$v['Before']
				.format_value($icals[$i][$k], $k)
				.$v['After'].'</li>';
			}
		}
		if (!($cprop === '')) {/* if there were some calendar property details*/
			if (!($amrW) and ($i == 0 ))  { /* only need to show the refresh once */
				 $cprop .= AMR_NL.AMR_TB.'<li class="icalrefresh" >'.amr_show_refresh_option().'</li>';
				}

			$html .= AMR_NL.'<tr>'
				.$cprop.AMR_NL.AMR_TB.'</ul></td> <!-- end of amrcol -->'.AMR_NL
				.'</tr>'.AMR_NL;  		
			}
	}
	return ($html);
}

/* --------------------------------------------------  */
function amr_same_time ($d1, $d2)
{
	if ($d1->format('His') === $d2->format('His')) return (true);
	else return (false);
}
/* --------------------------------------------------  */
function nl2br2($string) {
$s2 = str_replace(array('\n\n','\r\n'), '<br /><br />', $string);
$s2 = str_replace(array( '\r', '\n'), '<br />', $string);
return($s2);
} 
/* --------------------------------------------------  */
function amr_amp ($content) {
	return (str_replace('&','&amp;',str_replace('&amp;','&',$content) ));
}
/* --------------------------------------------------  */
function amr_calc_duration ( $start, $end) {

global $amr_globaltz;

	/* calculate weeks, days etc and return in array */
	
	$e = $end->format('z');  /* get the day of the year */
	$s = $start->format('z');
	$d = $e - $s;
	if ($d < 0) {
		$eoy = date_create( $end->format('Y').'-12-31 00:00:00',$amr_globaltz);	
		$d = ($eoy->format('z')) - $s +$e;
	}
	$w = $d / 7;  if ($w < 1) $w = 0;
	$d = $d % 7;   /* the remainder of days after complete weeks taken out */
	/* Note we do not need to add an extra day  or prior period if the hours go over a day, as the previous calculation will already have worked that out ????*/
	
	$b = $start->format('G');
	$e = $end->format('G');	
	$h = $e - $b;
	if ($h < 0) { 
		$d = $d - 1;
		$h = 24 + $h;
	}

	$b = $start->format('i');
	$e = $end->format('i');	
	$m = $e - $b;
	if ($m < 0) { 
		$h = $h - 1;
		$m = 60 + $m;
	}
	
	$b = $start->format('s');
	$e = $end->format('s');	
	$s = $e - $b;
	if ($s < 0) { 
		$m = $m - 1;
		$s = 60 + $s;
	}
	
	$duarray = array ();
	if ($w > 0) {$duarray['weeks'] = (int)$w;}
	if ($d > 0) {$duarray['days'] = (int)$d;}
	if ($h > 0) {$duarray['hours'] = (int)$h;}
	if ($m > 0) {$duarray['minutes'] = (int)$m;}
	if ($s > 0) {$duarray['seconds'] = (int)$s;}
	
	return ($duarray);
}
		/* ---------------------------------------------------------------------- */	

		/*
		 * Return true iff the two times span exactly 24 hours, from
		 * midnight one day to midnight the next.
		 */
function amr_is_all_day($d1, $d2) {
		 
			if (($d1->format('His') === '000000') and 
				($d2->format('His') === '000000')) {
				//$d1a = new DateTime();
				$d1a = clone $d1;
				date_modify ($d1a,'next day');
				if ($d1a = $d2) return (true); 
			}
			return (false);
		}
/* --------------------------------------------------------------------------------------*/
		/*
		 * Return true iff the two specified times fall on the same day.
		 */
		function amr_is_same_day($d1, $d2) {
		return (	$d1->format('Ymd') === 	$d2->format('Ymd'));
		}
		/*
			
/* --------------------------------------------------------------------------------------*/
	 /* Return true if the first date is earlier than the second date
	 */
	function amr_is_before($d1, $d2) {			
		if ($d1 < $d2 ) {
			return (true);
		}
		else {
			return (false);
		}	
	}
/* --------------------------------------------------------- */	
	function amr_format_duration ($arr) {
	/* receive an array of hours, min, sec */

	foreach ($arr as $i => $d) if ($d === 0) unset ($arr[$i]);
	
	If (ICAL_EVENTS_DEBUG) {echo '<br><br>After 0 check = '; var_dump($arr);}
	$i = count($arr);
	
	if ($i > 1) $sep = ', ';
	else $sep = '';

	
	$d = '';
	if (isset ($arr['years'] )) {
		$d .= sprintf (__ngettext ("%u year", "%u years", $arr['years']), $arr['years']);
		$d .= $sep;
		$i = $i-1;
		}
	if (isset ($arr['months'] )) {
		$d .= sprintf (__ngettext ("%u month ", "%u months ", $arr['months']), $arr['months']);
		if ($i> 1) {$d .= $sep;}
		$i = $i-1;
		}
	if (isset ($arr['weeks'] )) {
		$d .= sprintf (__ngettext ("%u week ", "%u weeks", $arr['weeks']), $arr['weeks']);
		if ($i> 1) {$d .= $sep;}
		$i = $i-1;
		}
	if ((isset ($arr['days'] )) ) {

			$d .= sprintf (__ngettext ("%u day", "%u days", $arr['days']), $arr['days']);
			If (ICAL_EVENTS_DEBUG) {echo ' and d = '.$d;}
			if ($i> 1) {$d .= $sep;}
			$i = $i-1;
		}
	if (isset ($arr['hours'] )) {
		$d .= sprintf (__ngettext ("%u hour", "%u hours", $arr['hours']), $arr['hours']);
		if ($i> 1) {$d .= $sep;}
		$i = $i-1;
		}		
	if (isset ($arr['minutes'] )) {
		$d .= sprintf (__ngettext ("%u minute", "%u minutes", $arr['minutes']), $arr['minutes']);
		if ($i> 1) {$d .= $sep;}
		$i = $i-1;
		}		
	if (isset ($arr['seconds'] )) {
		$d .= sprintf (__ngettext ("%u second", "%u seconds", $arr['seconds']), $arr['seconds']);
		
		}				
	If (ICAL_EVENTS_DEBUG) {echo '<br>D = '.$d;}	
	return($d);
	}
/* --------------------------------------------------------- */
function amr_format_tz ($tzstring) {
//	$url = $_SERVER['PHP_SELF'].'?'.$_SERVER['QUERY_STRING'];
	$url = $_SERVER[REQUEST_URI];
	return ('<span class="timezone" ><a href="'
		.htmlspecialchars(add_querystring_var($url,'tz',$tzstring)).'" title="'
		.sprintf( __('Show in timezone %s','amr-ical-events-list'),$tzstring).'" ><img src="'
		.IMAGES_LOCATION.TIMEZONEIMAGE.'" border="0" class="amr-bling" alt="'.$tzstring.'" />'
		.' </a></span>');
}
/* --------------------------------------------------------- */
function amr_derive_summary (&$e) {
	global $amr_options;
	global $amr_listtype;
/* If there is a event url, use that as href, else use icsurl, use description as title */

	$e['SUMMARY'] = htmlspecialchars(amr_just_flatten_array ($e['SUMMARY'] ));
	$e_url = amr_just_flatten_array($e['URL']);
	
	/* If not a widget, not listype 4, then if no url, do not need or want a link */
	if (empty($e_url))  {
		if (!empty($amr_options[$amr_listtype]['general']['Default Event URL'])) {
			$e_url = ' href="'.$amr_options[$amr_listtype]['general']['Default Event URL'].'" ';
			}
		else {
			if ($amr_listtype === "4") {
				$e_url = ' href="#no-url-available" '; /*empty anchor as defined by w3.org */	
			}
			else return ($e['SUMMARY']);
		}
	}
	else { 
	$e_url = ' href="'.$e_url.'" ' ;
	}

	
	$e_desc = '';
	if (isset ($e['DESCRIPTION'])) {	
		$e_desc = amr_just_flatten_array($e['DESCRIPTION']);
		}
    if (!empty($e_desc)) {
		$e_desc = (str_replace( '\n', '  ', (htmlspecialchars($e_desc))));
	}
	else $e_desc =  __('No event description available', 'amr-ical-events-list');

	$e_url = '<a '.$e_url.' title="'.$e_desc.'">'
	.$e['SUMMARY']
	.'</a>';
	
	return( $e_url );

}

/* --------------------------------------------------------- */
function format_value ($content, $k) {
/*  Format each Ical value for our presentation purposes 
Note: Google does toss away the html when editing the text, but it is there if you add but don't edit.
what about all day?
*/

	global $amr_formats;  /* amr check that this get set to the chosen list type */
	global $amr_options;
	global $amr_listtype;

	if (is_object($content)) {
		switch ($k){
			case 'EventDate':
			case 'EndDate': 
				return (amr_format_date ($amr_formats['Day'], $content)); 
			case 'EndTime':
			case 'StartTime': 
				return (amr_format_date ($amr_formats['Time'], $content)); 
			case 'DTSTART': /* probably will never display these */
			case 'DTEND':
			case 'until':
				return (amr_format_date ($amr_formats['Day'], $content)); 	

			case 'TZID': { /* amr  need to add code to reformat the timezone as per admin entry.  Also only show if timezone different ? */
				return(amr_format_tz (timezone_name_get($content)));
			}	
			default: 	/* should not be any */

				return (amr_format_date ($amr_formats['DateTime'], $content)); 	
		}	
	}
	else if (is_array($content)) {
		if ($k === 'DURATION') { return( amr_format_duration ($content)); }
		else {  /* don't think wee need to list the items separately eg: multiple comments or descriptions - just line  */
			$c = '';
			foreach ($content as $i => $v) {			
				if (!(empty($v))) {$c .= 	format_value ($v, $k) .'<br />';}
			}
			return ($c);	
		}
	}
	else if (is_null($content) OR ($content === '')) return ('');
	else {
		switch ($k){
			case 'COMMENT':	
			case 'DESCRIPTION': return(check_hyperlink(nl2br2(amr_amp($content))));
			case 'SUMMARY': return($content); /* avoid hyperlink as we may have added url already */
			case 'LOCATION': 
				return (check_hyperlink(nl2br2(amr_amp($content))));
			case 'map':	
				return ( ($content)); 
			case 'URL': /* assume valid URL, should not need to validate here, then format it as such */
					return( '<a href="'.$content.'">'.__('Event Link', 'amr-ical-events-list').'</a>');
			case 'icsurl': /* assume valid URL, should not need to validate here, then format it as such */
					return( '<a class="icalsubscribe" title="'
					.__('Subscribe to this calendar', 'amr-ical-events-list')
					.'" href="'.amr_amp($content).'">'
					.'<img class="subscribe amr-bling" border="0" src="'.IMAGES_LOCATION.CALENDARIMAGE.'" alt="'.
					__('calendar', 'amr-ical-events-list').'" /></a>'
					);		
			case 'addtogoogle': return ($content);
			case 'addevent': return($content);									
			case 'X-WR-TIMEZONE':	/* not parsed as object - since it is cal attribute, not property attribue */	
				return(amr_format_tz ($content));
			default: 
				return (amr_amp($content));
//		$content = format_date ( $amr_formats['Day'], $content);break;			 
		}
	}
	/* Convert any newlines to html breaks */
	return (str_replace("\n", "<br />", $content));
	
}
/* ------------------------------------------------------------------------------------*/

function amr_derive_eventdates_further (&$e) {	
/* Derive any date dependent data - requires EventDate */
	$now = date_create();

	$e['StartTime'] = $e['EventDate']; /* will format to time, later keep date  for max flex */	
	if (amr_is_same_day ($e['EventDate'],  $now)) $e['Classes'] .= ' today'; 
	if (amr_is_before($e['EventDate'], $now)) $e['Classes'] .= ' history'; 
	else $e['Classes'] .= ' future';
	
	
	if (isset ($e['DURATION'])) {  /* an array of the duration values */	
	
	}
	else 
	if (!isset ($e['DURATION'])) {  /* an array of the duration values */
		if (isset ($e['DTEND'])) {
			$e['DURATION'] = $d = amr_calc_duration ( $e['DTSTART'], $e['DTEND']);		/* calc the duration from the original values*/
			If (ICAL_EVENTS_DEBUG) {echo '<br>Duration = '; var_dump($e['DURATION']);}
			//$e['EndDate'] = new DateTime();
			$e['EndDate'] = clone $e['EventDate'];
			if ($d['sign'] === '-') $dmod = '-';  /* then apply it to get our current end time */
			else $dmod = '+';
			foreach ($d as $i => $v)  {  /* the duration array must be in the right order */
				if (!($i === 'sign')) { $dmod .= $v.' '.$i ;}
			}
			date_modify ($e['EndDate'], $dmod );
		}
	}

	if (isset ($e['EndDate']) ) {
		if (amr_is_all_day($e['EventDate'], $e['EndDate'])) {	
			unset ($e['StartTime']);
			unset ($e['EndTime']);

			$e['Classes'] .= ' allday'; 
		}
		else {
			if (amr_same_time($e['EventDate'], $e['EndDate'])) {	
				unset ($e['EndTime']);
				}		
			else $e['EndTime'] = $e['EndDate'];
		}
		if (amr_is_same_day($e['EndDate'],  $e['EventDate'])) {
			unset($e['EndDate']);  /* will just have end time if we need it */
			
		}
		else $e['Classes'] .= ' multiday'; 
	}
	$e['addevent'] = add_event_to_google($e);

	return($e);
}
/* ------------------------------------------------------------------------------------*/

function amr_derive_component_further (&$e) {	

	/* The RFC 2445 values will have been set by the import process.
		Eventdate will be set by the recur check process
		Now we need to derive, set or modify  any other values for our output requirements
	*/

	if (isset ($e['EventDate']))  	amr_derive_eventdates_further($e);
	if (isset ($e['Untimed'])) {  
		unset ($e['DURATION']);
		unset ($e['StartTime']);
		unset ($e['EndTime']);
		$e['Classes'] .= ' untimed'; 	
	}
	
	/* Noew get some styling possibilities */
	if (isset ($e['RRULE']) or (isset ($e['RDATE']))) $e['Classes'] .= ' recur'; 
	if (isset ($e['STATUS']) ){
		$e['Classes'] .= ' '.amr_just_flatten_array($e['STATUS']);  /* would have values like 'CONFIRMED'*/ 
	}
	$e['Classes'] .= ' '.$e['name'].' '.$e['type'];  /* so we can style events, todo's differently */

	$e['SUMMARY'] = amr_derive_summary ($e);
	if (isset ($e['GEO'])) {	$e['map'] = amr_ical_showmap($e['GEO']); }
	else if ((isset ($e['LOCATION'])) and (!empty($e['LOCATION']))) { 
			$e['map'] = amr_ical_showmap($e['LOCATION']); 	
		}
}
/* --------------------------------------------------  */
function amr_just_flatten_array ($arr) {
/* expecting array of text strings - convert to one txt string */
	$txt = '';
	if (is_array($arr)) {
		if (empty($arr)) return (null);
		else {
			foreach ($arr as $i => $v) {	$txt .= $v;	}
			return ($txt);
		}
	}
}
/* --------------------------------------------------  */
function amr_check_flatten_array ($arr) {
	if (is_array($arr)) {
		if (empty($arr)) return (null);
		else {
			foreach ($arr as $i => $v) {if (empty($v)) unset ($arr[$i]);}
			if (empty($arr)) return (null);
			else return ($arr);
		}
	}
	else return ($arr);
}
/* --------------------------------------------------  */
   function add_querystring_var($url, $key, $value) {
   /* replaces the first instance with the key and value passed */
	   $url = preg_replace('/(.*)(\?|&)' . $key . '=[^&]+?(&)(.*)/i', '$1$2$4', $url . '&');
	   $url = substr($url, 0, -1);
	   if (strpos($url, '?') === false) {
			return ($url . '?' . $key . '=' . $value);
	   } else {
			return ($url . '&' . $key . '=' . $value);
	   }
	 } 
/* --------------------------------------------------  */

function amr_semi_paginate() {
 	global $amr_limits; 
	global $amrW;
	if ($amrW) return ('');
	$nextd = $amr_limits['end']->format("Ymd");
	$gobackd = $amr_limits['start']->format("Ymd");
	$next = add_query_arg (array ('start'=>$nextd, 'startoffset'=>0 ));
	$goback = add_query_arg (array ('start'=>$gobackd, 'startoffset'=> -$amr_limits['days']));
	$showmore = add_query_arg (array(
				'events' => $amr_limits['events']*2,
				'days' => $amr_limits['days']*2
				));
	$showless = add_query_arg (array(
				'events' => (int) $amr_limits['events']/2,
				'days' => (int) $amr_limits['days']/2
				));			
	
	return (
		'<ul><li id="icalnext" class="icalnav"><a title="'.__('Start from day:' ,'amr-ical-events-list').$nextd
		.'" href="'.$next.'">'.__('Jump ahead','amr-ical-events-list').'</a></li>'
		.'<li id="icalback" class="icalnav"><a title="'.__('Jump Back in days:' ,'amr-ical-events-list').-$amr_limits['days']
		.'" href="'.$goback.'">'.__('Jump Back','amr-ical-events-list').'</a></li>'
		.'<li id="icalmore" class="icalnav"><a title="'.__('Show more days and events' ,'amr-ical-events-list')
		.'" href="'.$showmore.'">'.__('Show More','amr-ical-events-list').'</a></li>'
		.'<li id="icalless" class="icalnav"><a title="'.__('Show less days and events' ,'amr-ical-events-list')
		.'" href="'.$showless.'">'.__('Show Less','amr-ical-events-list').'</a></li></ul>'
		);	
			
}		
/* --------------------------------------------------  */

function amr_list_events($events, $g=null) {
	global $amr_options; 
	global $amr_listtype;
	global $amrW;

	/* The component properties have an additional structuring level that we need to remove */
	if (!isset($amr_options)) echo '<br />Options not set';
	else if (!isset($amr_options[$amr_listtype])) {
		echo '<br>listtype Not set: '.$listtype.' In Option array '.var_dump($amr_options);}
	else if (!isset($amr_options[$amr_listtype]['compprop'] )) 
		{echo '<br>Compprop not set in Option array '.var_dump($amr_options[$listyype]);}
	else {	
	
		/* check for groupings and compress these to requested groupings only */
		if (isset ($amr_options[$amr_listtype]['grouping'])) {  	
		foreach (($amr_options[$amr_listtype]['grouping']) as $i => $v)
				{	if ($v) { $g[$i] = $v; }			}
				if ($g) {foreach ($g as $gi=>$v) 
						{$new[$gi] = $old[$gi] = '';}} /* initialise group change trackers */		
		}
		
		/* flatten the array of component property options  */
		foreach ($amr_options[$amr_listtype]['compprop'] as $k => $v)
			{ 	foreach ($v as $i=>$j) 	{ $order[$i] = $j; 	}; 	}
		$order = prepare_order_and_sequence ($order);
		
		if (!($order)) return;
		else {
			$no_cols = 1;  /* check how many columns there are for this calendar */
			foreach ($order as $k => $v) { 
				if ($v['Column'] > $no_cols) {
					$no_cols = $v['Column'];
					};
			}
		}
			
		$html = AMR_NL.'<thead><tr>';
		for ($i = 1; $i <= $no_cols; $i++) { 			/* generate the heading code if requested */
			$html .= AMR_NL.'<th class="amrcol'.$i.'">'.$amr_options[$amr_listtype]['heading'][$i];
			$html .= '</th>';	
		}
		$html .= AMR_NL.'</tr></thead>';
		$html .= AMR_NL.'<tfoot><tr>'.AMR_NL
			.'<td colspan="'.$no_cols.'" style="font-size:x-small; font-weight:lighter;" >'
//			.amr_semi_paginate()
			.amr_ngiyabonga();
//		if (!($amrW)) {$html .= amr_show_refresh_option ();}
		$html .= '</td>'.AMR_NL.'</tr></tfoot>';

		$html .= AMR_NL.'<tbody valign="top">'.AMR_NL;
		$alt= false;
		foreach ($events as $i => $e) { /* for each event, loop through the properties and see if we should display */

			amr_derive_component_further ($e);
	
			if ((isset($e['Classes'])) and (!empty($e['Classes']))) 
				$classes = strtolower($e['Classes']);
			else $classes = '';

			$eprop = ''; /*  each event on a new list */
			$prevcol = 0;
			$colcount = 0;
			$col = 1; /* reset where we are with columns */	

			foreach ($order as $k => $kv) { /* ie for one event, check how to order the bits */
				/* Now check if we should print the component or not, we may have an array of empty string */
				$v = amr_check_flatten_array ($e[$k]);
				if ((isset ($v))  && (!empty($v)))
				{
					$col = $kv['Column']; 
					if ($col > $prevcol) { /* if new column, then new cell , */
						if (!($prevcol === 0))  {	/*if not the first col, then end the prev col */
							$eprop .= AMR_NL.'</ul></td>'; 
						}
						$colcount = $colcount +1;
						while ($colcount < $col) { /* then we are missing data for this column and need to skip it */
							$colcount = $colcount +1;
							$eprop .= AMR_NL.'<td>&nbsp;</td>';
						}

						$eprop .= AMR_NL.'<td class="amrcol'.$col.' '.$classes;
						if ($col == $no_cols) $eprop .= ' lastcol'; /* only want the call to be lastcol, not the row */
						$eprop .= '"><ul class="amrcol'.$col.' amrcol">';/* each column in a cell or list */
						$prevcol = $col;
					}	
					
					$eprop .= AMR_NL.AMR_TB.'<li class="'.$k.'">'.$kv['Before']
						. format_value($v, $k).$kv['After'].'</li>';  /* amr any special formatiing here */
				}
			}

			
			
			if (!($eprop === '')) /* ------------------------------- if we have some event data to list  */
			{	/* then finish off the event or row, save till we know whether to do group change heading first */
				$eprop = AMR_NL.'<tr'.($alt ? ' class="alt':' class="').$classes.'"> '
					.$eprop.AMR_NL.'</ul></td>'.AMR_NL.'</tr>';
					
				if ($alt) $alt=false; else $alt=true; 	
				/* -------------------------- Check for a grouping change, need to end last group, if there was one and start another */
				$change = '';
				if ($g) 
				{	foreach ($g as $gi=>$v) {	
						$grouping = format_grouping($gi, $e['EventDate']) ; 
						$new[$gi] = amr_string($grouping);  
						if (!($new[$gi] == $old[$gi])) {  /* if there is a grouping change then write the heading for the group */
							$id = '"'.amr_string($gi.$new[$gi]).'"';
							$change .= 	'<tr class="group '.$gi.'"><th id='.$id
							.' class="group '.$gi. '"  colspan="'.$no_cols.'" >'.$grouping.'</th></tr>';
							$old[$gi] = $new[$gi];							
						}
					} 					
				}				
				$html .= $change.AMR_NL.$eprop;	
			} 	
		} 
	}
	$html .= AMR_NL.'</tbody>'.AMR_NL;
return ($html);
}

/* -------------------------------------------------------------------------------------------*/
function format_grouping ($grouping, $datestamp) {
/* check what the format for the grouping should be, call functions as necessary*/

global $amr_options;
global $amr_listtype;
global $amr_formats;

	if (in_array ($grouping ,array ('Year', 'Month','Day')))
		return (amr_format_date( $amr_options[$amr_listtype]['format'][$grouping], $datestamp));
	else if ($grouping === 'Week') {
			$f = $amr_formats['Week'];
			$w = amr_format_date( $f, $datestamp);
			return (sprintf(__('Week  %u', 'amr-ical-events-list'),$w));
		}
	else 
	{ 	/* for "Quarter",	"Astronomical Season",	"Traditional Season",	"Western Zodiac",	"Solar Term" */

		$func = str_replace(' ','_',$grouping);
		if (function_exists($func) ) {
			return call_user_func($func,$datestamp);
			}
		else 
		   return ('No function defined for Date Grouping '.$grouping);
	}
}
/* -------------------------------------------------------------------------------------------*/
function amr_format_date( $format, $datestamp) { /* want a  integer timestamp and a date object  */
							
	if (is_object($datestamp))	{	
//			$d = clone $datestamp;	
			$dateInt = $datestamp->format('U') ;  
//			$dateInt = $datestamp->getTimestamp; /* getTimestamp requires php 5.3.0  */
			$dateO = $datestamp;
		}
	else if (is_integer ($datestamp)){ 
			$dateInt = $datestamp;
			$dateO = new DateTime(strftime('%Y-%m-%d %T',$datestamp));
			}
	else /* must be an ical date?! */{	
//			if (ICAL_EVENTS_DEBUG) 
			return($datestamp); 
//			$dateInt = icaldate_to_timestamp ($datestamp);
//			$dateO = new DateTime (strftime('%Y-%m-%d %T',$dateInt));	
		}
	if (stristr($format, '%') ) {
	 return (strftime( $format, $dateInt ));
	}
	else {
		return($dateO->format($format));
//		return (date_i18n($format, $dateInt));  - supposed to localise, but is returning incorrect times - may be dateInt's problem?
		}

}
/* ------------------------------------------------------------------------------------*/
		/*
		 * Sort the specified associative array by the specified key.
		 * Originally from
		 * http://us2.php.net/manual/en/function.usort.php.
		 */
	function amr_sort_by_key($data, $key) {
			// Reverse sort
			$compare = create_function('$a, $b', 'if ($a["' . $key . '"] == $b["' . $key . '"]) { return 0; } else { return ($a["' . $key . '"] < $b["' . $key . '"]) ? -1 : 1; }');
			usort($data, $compare);

			return $data;
		}
/* ------------------------------------------------------------------------------------*/
		/*
		 * Return true iff the specified event falls between the given
		 * start and end times.
		 */
	function amr_falls_between($eventdate, $astart, $aend) {
		
		if (($eventdate <= $aend) and 
			($eventdate >= $astart)) return ( true);
		else return (false);	
		}

/* ------------------------------------------------------------------------------------*/
		/* Process an array of datetime objects and remove duplicates 
		 */
	function amr_arrayobj_unique (&$arr) {
		/* 	Duplicates can arise from edit's of ical events - these will have different sequence numbers and possibly differnet details - delete one with lower sequence,
			Also there maybe? the possobility of complex recurring rules generating duplicates - these will have same sequence no's and should have same details - delete one    
			Note: Mozilla does not seem to generate a SEQUENCE ID, but does do a X-MOZ-GENERATION.
		*/	
		$limit = count ($arr);	
		if (ICAL_EVENTS_DEBUG) {echo '<h2>Check for mods in '.$limit.' records</h2>';}	
		foreach ($arr as $i => $e) {
			if (isset ($e['RECURRENCE-ID'])) { /* then find corresponding UID and date and delete */
				if (ICAL_EVENTS_DEBUG) {echo '<h2> Got Recurrence '.$i.'</h2>';}			
				foreach ($arr as $j => $f) { 
					if (ICAL_EVENTS_DEBUG) {echo '<br>Check '.$j;}	
					if (!($i === $j)) {
						if (($e['RECURRENCE-ID'] == $f['EventDate']) and
						($e['UID'] === $f['UID'])) {
							if (ICAL_EVENTS_DEBUG) {echo '<br>Recurrence Id - Got a match <br>'
							.$e['RECURRENCE-ID']->format('c').' '.$e['UID']
							.'<br>' .$f['EventDate']->format('c').' '.$f['UID'];
							}
							if (isset($e['SEQUENCE'])) {
								if (ICAL_EVENTS_DEBUG) {echo ' Seq '.$i.'= '.$e['SEQUENCE'];}
								if (isset ($f['SEQUENCE'])) {
									if ($e['SEQUENCE'] >= $f['SEQUENCE']) {					
										if (ICAL_EVENTS_DEBUG) {echo ' Unset '.$j;}
										unset($arr [$j]);	
									}
									else unset($arr [$i]);	
								}	
								else unset($arr [$i]);				
							}
						else unset ($arr [$i]);
					   }
					}	
				}
			}
		}
	}
	/* ========================================================================= */			
	function amr_repeat_anevent($event, $repeatstart, $aend, $limit) {
	/* for a single event, handle the repeats as much as is possible */
	$repeats = array();
	$exclusions = array();
	
		if (isset($event['RRULE']))	{	
			foreach ($event['RRULE'] as $i => $rrule) {			
				$reps = amr_process_RRULE($rrule, $repeatstart, $aend, $limit);	
				if (is_array($reps) and count($reps) > 0) {
					$repeats = array_merge ($repeats, $reps);
				}
			}
			if (ICAL_EVENTS_DEBUG) { echo '<br>Got '.count($repeats). ' after RRULE';}	
		}
			
		if (isset($event['RDATE']))	{		
				foreach ($event['RDATE'] as $i => $rdate) {			
					$reps = amr_process_RDATE  ($rdate, $repeatstart, $aend, $limit);
					if (is_array($reps) and count($reps) > 0) {
						$repeats  = array_merge($repeats , $reps);
					}
				}
				if (ICAL_EVENTS_DEBUG) { echo '<br>Got '.count($repeats). ' after RDATE';}
			}
			
		if (isset($event['EXRULE']))	{	
			if (ICAL_EVENTS_DEBUG) { echo '<br><h3>Have EXRULE </h3>';}			
			foreach ($event['EXRULE'] as $i => $exrule) {			
				$reps = amr_process_RRULE($exrule, $repeatstart, $aend, $limit);
				if (is_array($reps) and count($reps) > 0) {			
					$exclusions  = $reps;
				}
			}
			if (ICAL_EVENTS_DEBUG) { echo '<br><h3>Got '.count($exclusions). ' after EXRULE</h3>';}		
		}
			
		if (isset($event['EXDATE']))	{	
			if (ICAL_EVENTS_DEBUG) { echo '<br><h3>Have EXDATE </h3>';}	
			foreach ($event['EXDATE'] as $i => $exdate) {	

				$reps = amr_process_RDATE($exdate, $repeatstart, $aend, $limit);
				if (is_array($reps) and count($reps) > 0) {
						$exclusions  = array_merge($exclusions , $reps);
				}
			}
			if (ICAL_EVENTS_DEBUG) { echo '<br><h3>Got  '.count($exclusions). ' exclusions after EXDATE</h3>';}	
		}

		if (ICAL_EVENTS_DEBUG) { echo '<br>Still have '.count($repeats). ' before exclusions';}				
			/* Now remove the exclusions from the repeat instances */
		if (is_array($exclusions) and count ($exclusions) > 0) {
			foreach ($exclusions as $i => $excl) {
			    foreach ($repeats as $j => $rep) {
					if (!($excl < $rep) and !($excl > $rep)) {
						if (ICAL_EVENTS_DEBUG) {
							echo '<br> Must be same, so exclude '.$j.' '. amr_format_date('c', $rep);
						}
						unset($repeats[$j]); /* will create a gap in the index, but that's okay, not reliant on it  */ 
					}	
				}
			}	
		}
		if (ICAL_EVENTS_DEBUG) { echo '<br>Now have '.count($repeats). ' after  exclusions '; }		
		
		return ($repeats);
	
	}
	/* ========================================================================= */	
	function debug_print_event ($e, $nest=0) {

		$tab = str_repeat('==', $nest);
		foreach ($e as $i => $f) {
			if (is_object($f)) echo '<br>'.$tab.$i.' = '.$f->format('c');
			else if (is_array($f)) {echo '<br>'.$tab.$i; debug_print_event ($f, ($nest+1)); }
				else echo '<br>'.$tab.$i.' = '.$f;
		}
	}
	/* ========================================================================= */	
	function amr_process_single_icalevents($event, $astart, $aend, $limit) {

		$repeats = array(); // and array of dates 
		$newevents = array();  // an array of events 

		if (isset($event['DTSTART'])) {
			$dtstart = $event['DTSTART'];	}
		else if (!isset($event['RDATE']))	{
			$newevents[] = $event;
			return ($newevents); /* possibly an undated, non repeating VTODO or Vjournal- no repeating to be done if no DTSTART, and no RDATE */
		}
//if (ICAL_EVENTS_DEBUG) {echo '<br><h3>Before repeat DTSTART = </h3>'; debug_print_event ($event);	echo '-end debug print event</br>';}

		if (amr_is_before ($dtstart, $aend)) {  /* If the start is after our end limit, then skip this event */			
			if (isset($event['RRULE']) or (isset($event['RDATE']))) {
						
						/* if have, must use dtstart in case we are dependent on it's characteristics,. We can exclude too early dates later on */
				$repeats = amr_repeat_anevent($event,$dtstart,$aend, $limit );
						
				if (ICAL_EVENTS_DEBUG) {echo '<br>Num of Repeats to be created'.count($repeats).'<br>';}	
						/* now need to convert back to a full event by copying the event data for each repeat */						
						if (is_array($repeats) and (count($repeats) > 0)) {
							foreach ($repeats as $i => $r) {
								$newevents[$i] = $event;  // copy the event data over - not objects will point to same object - is this an issue?   Use duration or new /clone Enddate
								//$newevents[$i]['EventDate'] = new DateTime();
								$newevents[$i]['EventDate'] = clone $r;  
								if (ICAL_EVENTS_DEBUG) {
									echo '<br>Created repeated event '.$r->format('c');	
								}
							}
						}				
					}
					else { /* there is no repeating rule, so just copy over */
						$newevents[0] = $event;  /* so we drop the old events used to generate the repeats */
						if (isset ($event['RECURRENCE-ID'])) {   /*repeats already done by ical generator *** or a modification? */
							if (is_array($event['RECURRENCE-ID'])) { /* just take first, should only be one */
								$newevents[0]['EventDate'] = $event['RECURRENCE-ID'][0];   // this should be okay without cloning, as will not modify ?
							}
							else if (is_object($event['RECURRENCE-ID'])) {
								$newevents[0]['EventDate'] = $event['RECURRENCE-ID'];
							}
							else { /****  should deal with THISANDFUTURE or THISANDPRIOR  EG:
							     RECURRENCE-ID;RANGE=THISANDPRIOR:19980401T133000Z
*/
							if (ICAL_EVENTS_DEBUG) {echo '<br>Cannot yet handle modification to recurrence with'; var_dump($event['RECURRENCE-ID']);}
							}
						}
						else {
							$newevents[0]['EventDate'] = new DateTime();
							$newevents[0]['EventDate'] = clone $dtstart; 
						}
					}
				}
				else {
					if (ICAL_EVENTS_DEBUG) {
						echo '<br>Skipped this event.  Overall End '
						.$aend->format('c'), ' is before event start '
						. $event['DTSTART']->format('c');
					}
				}
			return ($newevents);
	}	
/* ------------------------------------------------------------------------------------*/
	/*
	 * Constrain the list of COMPONENTS to those which fall between the
	 * specified start and end time, up to the specified number of
	 * events.
	 */
	function amr_do_components($events, $start, $end, $limit) {
			
		$newevents = amr_process_icalevents($events, $start, $end, $limit);

		if (ICAL_EVENTS_DEBUG) { echo '<br>Records generated = '.count($newevents)
			.' Check between '.$start->format('c');
			' and '.$end->format('c').'*';			}
		
		$newevents = amr_sort_by_key($newevents , 'EventDate');
		
		$constrained = array();
		$count = 0;
		foreach ($newevents as $k => $event) {	
			if (isset ($event['EventDate'])) {
				if (amr_falls_between($event['EventDate'], $start, $end)) {
					$constrained[] = $event;
					if (ICAL_EVENTS_DEBUG) { 
						echo '<br>Choosing '.$k.' '. $event['EventDate']->format('c');}		
					++$count;	
				}
				else if (ICAL_EVENTS_DEBUG) { echo '<br>Not Choosing '.$k.' '. $event['EventDate']->format('c');}	
			}
			else $constrained[] = $event;							
			if ($count >= $limit) break;
		}
		if (ICAL_EVENTS_DEBUG) { echo '<br>Constrained records chosen = '.count($constrained);}
		return $constrained;
	}
			
	/* ========================================================================= */	
		/*
		 * generate repeating events down to nonrepeating events at the
		 * corresponding repeat time.  
		 For ease of processing the repeat arrays will initially be ISO 8601 date (added in PHP 5)  eg:	2004-02-12T15:19:21+00:00
		 we will then convert them to date time objects
		 */
	function amr_process_icalevents($events, $astart, $aend, $limit) {
		$dates = array();

		foreach ($events as $i=> $event) {	
			$more = amr_process_single_icalevents($event, $astart, $aend, $limit);
//			if (ICAL_EVENTS_DEBUG) { echo ' <br>No.Date= '.count($dates).' Plus more = '.count($more) ;}	
			$dates = array_merge ($dates, $more) ;	
		}			
		if (is_array($dates)) {
			amr_arrayobj_unique($dates); /* remove any duplicate in the values , check UID and Seq*/
			if (ICAL_EVENTS_DEBUG) { echo '<br>Now have '.count($dates). ' after  duplicates removal';}	
			return ($dates); 
		}
		else return (null) ; 
	}


/* -------------------------------------------------------------------------*/
function process_icalurl($url) {
global $amr_limits;
/* cache the url if necessary, and then parse it into basic nested structure */

	$file = cache_url(str_ireplace('webcal://', 'http://',$url),$amr_limits['cache']);
	if (! $file) {	echo "<!-- iCal Events: Error loading [$url] -->";	return;	}
	$ical = amr_parse_ical($file);
	if (! is_array($ical) ) {
			echo "<!-- iCal Events: Error parsing calendar [$url] -->";
			return($ical);
		}
	$ical['icsurl'] = $url; 	
	return ($ical);	
}
/* -------------------------------------------------------------------------*/
function amr_echo_parameters() {
global $amr_limits;
	return ( '<ul><li>'.__('Start date: ','amr-ical-events-list').$amr_limits['start']->format('c').'</li>'
	.'<li>'.__('End date: ','amr-ical-events-list').'&nbsp;&nbsp;'.$amr_limits['end']->format('c').'</li>'
	.'<li>'.__('Maximum days: ','amr-ical-events-list').$amr_limits['days'].'</li>'
	.'<li>'.__('Maximum events: ','amr-ical-events-list').$amr_limits['events'].'</li></ul>');
}	
/* -------------------------------------------------------------------------*/
function amr_get_ical_name($ical) {
/* Maybe check for a calendar name and if it exists, then use it for styling? - NOt NOW  */
	if (isset($ical['X-WR-CALNAME'])) return($ical['X-WR-CALNAME']);
	else return (basename($path, ".ics"));  /* use number as the name for now, so that we can use it later for styling? ***/		
}
/* -------------------------------------------------------------------------*/
function amr_string($s) {
/* Maybe check for a calendar name and if it exists, then use it for styling? - NOt NOW  */
return(str_replace(array (' ','.','-',',','"',"'"), '', $s));
	
}
/* -
/* -------------------------------------------------------------------------*/
function process_icalspec($urls, $icalno=0) {
/*  parameters - an array of urls, an array of limits (actually in amr_limits)  */
	global $amr_options;
	global $amr_limits;
	global $amr_listtype;
	global $amrW;

	foreach ($urls as $i => $url) {
		$icals[$i] = process_icalurl($url);
		if (!is_array($icals[$i])) unset ($icals[$i]);
	}			
	/* now we have potentially  a bunch of calendars in the ical array, each with properties and items */
	/* only doing vevent here, must allow for others */
	/* Merge then constrain  by options */
	$components = array();  /* all components actually, not just events */
	if (isset ($icals) ) {	/* if we have some parse data */
		foreach ($icals as $j => $ical) { /* for each  Ics file within an ICal spec*/
			foreach ($amr_options[$amr_listtype]['component'] as $i => $c) {  /* for each component type requested */		
				if ($c) {		/* If this component was requested, merge the items from Ical items into events */	
					if (isset($ical[$i])) {  /* Eg: if we have an array $ical['VEVENT'] etc*/				
						foreach ($ical[$i] as $k => $a) { /*  save the compenent type so we can style accordingly */
							$ical[$i][$k]['type'] = $i;
							$ical[$i][$k]['name'] = 'cal'.$j; /* save the name for styling */
						}
						if (!empty($components) ) {$components = array_merge ($components, $ical[$i]);	}
						else $components = $ical[$i];				
					}
				}
			}
		}
/* amr here is the main calling code  *** */	
		$calprophtml =  amr_list_properties ($icals);		
		if (isset($calprophtml) and (!(empty($calprophtml))) and (!($calprophtml === ''))) {
			$calprophtml  = '<table id="'.$amrW.'calprop'.$icalno.'">'.$calprophtml.'</table>'.AMR_NL;
		}  	
		
		$components = amr_do_components($components, $amr_limits['start'], $amr_limits['end'], $amr_limits ['events']);	

		if (count($components) === 0) {
			if (isset($amr_options['noeventsmessage'])) $thecal =  '<p>'.$amr_options['noeventsmessage'].'</p>';
		}
		else {

				$thecal = $calprophtml
				.AMR_NL.'<table id="'.$amrW.'compprop'.$icalno.'">'				
				.amr_list_events($components )
				.AMR_NL.'</table>'.AMR_NL;
			}
		}
		else $thecal = '';	/* the urls were not valid or some other error ocurred, for this spec, we have nothing to print */
		if (ICAL_EVENTS_DEBUG)  { echo amr_echo_parameters();}
/* amr  end of core calling code --- */		
		return ($thecal);
	} 

/* -------------------------------------------------------------------------*/
function amr_get_params ($attributes=array()) {
/*  We are passed the shortcode attributes, check them, get what we can there, then check for passed paraemeters (form or query string ) 
   Anything unset we will get from the defaults set for that listtype.
   The defaults list type is 1.
    we could even have a default url!   
*/
	global $amr_limits;
	global $amr_listtype;
	global $amr_options;  
	global $amr_formats;  
	
	
	$amr_options = amr_getset_options();
	$defaults = array( /* defaults array */
   	'listtype' => '1',
   	'startoffset' => '0',
	'start' => date_create(),
	'months' => '0',
	'days' => $amr_limits['days'],
	'events' => $amr_limits['events']
      );
	
	$atts = shortcode_atts( $defaults, $attributes ) ;
	$urls = array_diff_assoc ($attributes, $atts);

	$amr_listtype = $atts['listtype'];
	foreach ($amr_options[$amr_listtype]['limit'] as $j => $l) $amr_limits[$j] = $l;  
	/* get the defaults of that listtype */
	foreach ($defaults as $i => $a) { 
		if ($i === 'start') {
			if (isset($_REQUEST[$i] )) $start = $_REQUEST[$i];
			else $start = $atts[$i];
			if (!(is_object($start))) {
				if (checkdate(substr($start,4,2), /* month */
						substr($start,6,2), /* day*/
						substr($start,0,4)) /* year */ )
						$amr_limits['start'] = date_create($start);					
				else {
						echo '<h2>'.__('Invalid Start date','amr-ical-events-list').'</h2>';
						$amr_limits['start'] = date_create();	
				}
			}
			else $amr_limits['start'] = $start;
		 /*  else all is okay - we have default date of now */
		}
		else { /* it's a number */
			$int_options = array("options"=> array("min_range"=>1, "max_range"=>1000));
			if (isset($_REQUEST[$i])) {
				if (filter_var($_REQUEST[$i], FILTER_VALIDATE_INT, $int_options)) $amr_limits[$i] = $_REQUEST[$i];
				else {
					echo '<br>'.$i,' not within valid range. ';
					$amr_limits[$i] = $atts[$i];
				}	
			}
			else $amr_limits[$i] = $atts[$i];
		};
	}
	/* check for urls that are either passed by query or form, or are in the shortcode with a number or not  */
		
		if (isset($_REQUEST['ics'])) {
			$spec = array(str_ireplace('webcal://', 'http://',$_REQUEST[$i]));
			if (!filter_var($spec, FILTER_VALIDATE_URL)) {
					echo '<h2>'.__('Invalid Ical URL passed in query string','amr-ical-events-list').'</h2>';
				}
				else $urls[] = $spec; /* save it as we unset it later , and also if not ics used then will just be left in atts anyway */
			}	

	If (ICAL_EVENTS_DEBUG) echo '<br>'.count($urls).' urls';
	$amr_listtype = $amr_limits['listtype']; /*  need to redo this in case was sent in the query string */
	$amr_formats = $amr_options[$amr_listtype]['format'];

	/*  do some post processing */
	date_time_set($amr_limits['start'],0,0,0); /* set to the beginning of the day */	
	date_modify($amr_limits['start'],'+ '.(int)($amr_limits['startoffset']).' days') ;
	$amr_limits['end'] = clone $amr_limits['start'];
	date_modify($amr_limits['end'],'+ '.($amr_limits['days']).' days') ;		

	return ($urls);
}

/* -------------------------------------------------------------------------*/
function amr_do_ical_shortcode ($atts, $content = null) {
// This is the main function.  It replaces [iCal:URL]'s with events. Each as a separate list 
/* Allow multiple urls and only one listtype */
/*  merge atts with this array, so we will have a default list */
    global $amr_listtype;
	global $amr_limits;
	
	amr_ical_load_text();		 

	$urls =	amr_get_params ($atts);  /* striup out and set any other attstributes  - they will set the limits table */
	/* separate out the other possible variables like list type, then just have the urls */
	/*  check if we have anything passed by form or command line */
	$content = process_icalspec($urls);
  return ($content);
}
/* -------------------------------------------------------------------------------------------------------------*/
 function amr_ical_load_text() {
/**
 * Internationalization functionality
 */
/* $textdomain, path from abspath, path from plugins folder */
	load_plugin_textdomain('amr-ical-events-list', false , basename(dirname(__FILE__)) );
}
/* -------------------------------------------------------------------------------------------------------------*/

	/**
	Adds a link directly to the settings page from the plugin page
	*/
	function amr_plugin_action($links, $file) {
	/* create link */
	if ( $file == AMR_BASENAME ) {
		array_unshift($links,'<a href="options-general.php?page=manage_amr_ical">'. __('Settings').'</a>' );
	}
 
	return $links;
	} // end plugin_action()
 

/* -------------------------------------------------------------------------------------------------------------*/
	add_action( 'init', 'amr_ical_load_text' );	
	add_action('wp_head',  'amr_ical_events_style');
	add_action('plugins_loaded', 'amr_ical_widget_init');	
	add_filter('plugin_action_links', 'amr_plugin_action', 8, 2);
	
	if (is_admin() )	{
		add_action('admin_head', 'AmRIcal_options_style');
		add_action('admin_menu', 'AmRIcal_add_options_panel');	
	}
	add_shortcode('iCal', 'amr_do_ical_shortcode');

?>