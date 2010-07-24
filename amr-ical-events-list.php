<?php
/*
Plugin Name: AmR iCal Events List
Author URI: http://anmari.com/
Plugin URI: http://icalevents.anmari.com
Version: 2.9.3
Text Domain: amr-ical-events-list 
Domain Path:  /lang

Description: Display highly customisable and styleable list of events from iCal sources.  Handles almost all types of recurring events, notes, journals, freebusy etc. <a href="http://webdesign.anmari.com/web-tools/donate/">Donate</a>,  <a href="http://wordpress.org/extend/plugins/amr-ical-events-list/"> rate it</a>, or link to it. <a href="page-new.php">Write Calendar Page</a>  and put [iCal http://yoururl.ics ] where you want the list of events.  To tweak: <a href="options-general.php?page=manage_amr_ical">Manage Settings Page</a>,  <a href="widgets.php">Manage Widget</a>.
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

define('AMR_ICAL_VERSION', '2.9.3');
define('AMR_PHPVERSION_REQUIRED', '5.2.0');
define( 'AMR_BASENAME', plugin_basename( __FILE__ ) );

/*  these are  globals that we do not want easily changed -others are in the config file */
global $amr_options;
global $amrW;  /* set to W if running as widget, so that css id's will be different */
$amrW = '';
	
if (version_compare(AMR_PHPVERSION_REQUIRED, PHP_VERSION, '>')) {
	echo( '<h2>'.__('Minimum Php version '.AMR_PHPVERSION_REQUIRED.' required for Amr Ical Events.  Your version is '.PHP_VERSION,'amr-ical-events-list').	'</h2>');
	}
	
if (!(class_exists('DateTime'))) {
	echo '<h1>'.
	__ ('The <a href="http://au.php.net/manual/en/class.datetime.php"> DateTime Class </a> must be enabled on your system for this plugin to work. They may need to be enabled at compile time.  The class should exist by default in PHP version 5.2.',"amr-ical-events-list")
	.'</h1>';}	

require_once('amr-ical-config.php');
require_once('amr-ical-list-admin.php');
require_once('amr-import-ical.php');
require_once('amr-rrule.php');
require_once('amr-ical-uninstall.php');
require_once('amr-upcoming-events-widget.php');
require_once('amr_date_i18n.php');
$f = WP_PLUGIN_DIR.'/amr-ical-events-list/amr-ical-events-plus.php'; 
if (file_exists($f))
	include_once('amr-ical-events-plus.php');   /* include the plus functions if they have been purchased  */

/* see http://acko.net/blog/php-clone */
  if (version_compare(phpversion(), '5.0', '<')) {
    eval('function clone($object) {
      return $object;
    }
    ');
  }
/*--------------------------------------------------------------------------------*/
function amr_get_googletime($time)   {  
	$t = clone ($time);  /* if you get a parse error, then you are not on PHP 5! */
    $t->setTimezone(new DateTimeZone("UTC"));
    return ($t->format("Ymd\THis\Z"));
   } 
/*--------------------------------------------------------------------------------*/   
function amr_get_googledate($time)   {  
	$t = clone ($time);
      $t->setTimezone(new DateTimeZone("UTC"));
      return ($t->format("Ymd"));
   } 
/*--------------------------------------------------------------------------------*/   
function amr_get_googleeventdate($e) {  
		if (isset ($e['StartTime'])) $d = (amr_get_googletime ($e['StartTime']));
		else if (isset ($e['EventDate'] )) $d = (amr_get_googledate ($e['EventDate'])); /* no time just a day */
		else return (''); /* we have no start date or start time */
		if (isset ($e['EndTime'])) $e = (amr_get_googletime ($e['EndTime']));
		else if (isset ($e['EndDate'])) $e = (amr_get_googledate ($e['EndDate']));
		else return ($d.'/'.$d);
		return ($d.'/'.$e);
   }   
/*--------------------------------------------------------------------------------*/
function add_cal_to_google($cal) {
global $amr_options;
/* adds a button to add the current calemdar link to the users google calendar */
	$text1 = __('Add to google calendar', "amr-ical-events-list");
	if (isset ($amr_options['no_images'])  and $amr_options['no_images']) $text2 = $text1;
	else	$text2 = '<img src="'.IMAGES_LOCATION.ADDTOGOOGLEIMAGE.'" title="'.$text1.'" alt="'.$text1.'" class="amr-bling" />';
	return (
	'<a href="http://www.google.com/calendar/render?cid='.$cal.'" target="_blank"  title="'.$text1.'">'.$text2.'</a>');
}
/*--------------------------------------------------------------------------------*/
function add_event_to_google($e) {
global $amr_options;
	if (!isset($e['EventDate'])) return('');
	if (isset($e['LOCATION'])) $l = str_replace(' ','%20',htmlentities($e['LOCATION'] ));
	else $l = '';
	if (!isset($e['DESCRIPTION'])) $e['DESCRIPTION'] = '';
	$t = __("Add event to google" , "amr-ical-events-list");
	
	if (isset ($amr_options['no_images']) and $amr_options['no_images']) $t2 = $t; 
	else $t2 = '<img src="'.IMAGES_LOCATION.ADDTOGOOGLEIMAGE.'" alt="'.$t.'" title="'.$t.'" class="amr-bling"/>';
/* adds a button to add the current calemdar link to the users google calendar */
	$html = '<a href="http://www.google.com/calendar/event?action=TEMPLATE'
	.'&amp;text='.str_replace(' ','%20',(htmlentities(amr_just_flatten_array ($e['SUMMARY']))))
	/* dates and times need to be in UTC */
	.'&amp;dates='.amr_get_googleeventdate($e)
	.'&amp;details='.str_replace('\n','&amp;ltbr%20/&amp;gt',htmlentities(amr_just_flatten_array (str_replace(' ','%20',$e['DESCRIPTION']))))  /* Note google only allows simple html*/
	.'&amp;location='.$l
	.'&amp;trp=false'
	.'" target="_blank" title="'.$t.'">'.$t2.'</a>';
	return ($html);
}
/*--------------------------------------------------------------------------------*/
function amr_ical_events_style()  /* check if there is a style spec, and file exists */{
global $amr_options;
global $amr_listtype;

	$icalstyleurl = ICALSTYLEURL;
	if ((isset($amr_options)) or ($amr_options = get_option ('amr-ical-events-list'))) {
		if ((isset ($amr_options['own_css'])) and !($amr_options['own_css'])) { 
				if (empty($amr_options['cssfile'])) $icalstyleurl = ICALSTYLEURL;  
				else ($icalstyleurl = AMRICAL_ABSPATH.$amr_options['cssfile']);
	            wp_register_style('amr-ical-events-list', $icalstyleurl, array( ), 1.0 , 'all' );
				wp_enqueue_style('amr-ical-events-list' ); 
		}	
	}
	else {
	            wp_register_style('amr-ical-events-list', $icalstyleurl, array( ), 1.0 , 'all' );
				wp_enqueue_style('amr-ical-events-list' ); 
	}
	wp_register_style('amr-ical-events-list_print', ICALSTYLEPRINTURL, array(), 1.0 , 'print');					
	wp_enqueue_style('amr-ical-events-list_print'); 
}
/* --------------------------------------------------  sort through the options that define what to display in what column, in what sequence, delete the non display and sort column and sequenc  */
function prepare_order_and_sequence ($orderspec){
//	foreach ($amr_options[$format]['compprop'] as $key => $row) 
	foreach ($orderspec as $key => $row) {	
		if (( isset ($row['Column'])) && (!($row['Column']== "0") ))	
		$order[$key] = $row;	
	}
	if (!isset($order)) return;  /* Nothing is to be displayed */
	// Prepare to sort order for printing
	foreach ($order as $key => $row) {
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

	return (make_clickable($text));  /* now works better than the code  below*/
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

global $amr_globaltz, $amr_lastcache, $amr_options, $amr_last_modified;

	$uri = add_query_arg('nocache', 'true');

	if (!is_object($amr_lastcache)) $text = __('Last Refresh time unexpectedly not available','amr-ical-events-list');
	else {
		date_timezone_set($amr_lastcache, $amr_globaltz);
		$t = $amr_lastcache->format(get_option('time_format').' T');
		$text = __('Refresh calendars','amr-ical-events-list');
		$text2 = sprintf(__('Last refresh was at %s. ','amr-ical-events-list'),$t);
		}
	if (!is_object($amr_last_modified)) $text2 =  __('Remote file had no modifications. ','amr-ical-events-list');
	else {
		date_timezone_set($amr_last_modified, $amr_globaltz);
		$t2 = $amr_last_modified->format(get_option('date_format').' '.get_option('time_format').' T.');
		$text2 = sprintf(__('The remote file was last modified on %s.','amr-ical-events-list'),$t2);
		}
		
	if (isset ($amr_options['no_images']) and $amr_options['no_images']) $t3 = $text; 	
	else $t3 = '<img src="'.IMAGES_LOCATION.REFRESHIMAGE
		.'" class="amr-bling" title="'.__('Click to refresh','amr-ical-events-list').' '.$text2.'" alt="'.$text.'" />';

	return ( '<a class="refresh" href="'.$uri.'" title="'.$text.' '.$text2.'">'.$t3.'</a>');
}

/* --------------------------------------------------  */
function amr_derive_calprop_further (&$p) {
	global $amr_options; 	
	if (isset ($p['totalevents'])) $title = __('Total events: ').$p['totalevents'];	/* in case we have noename? ***/
	if (isset ($p['icsurl']))  {/* must be!! */
		$p['addtogoogle'] = add_cal_to_google ($p['icsurl']);
		if (isset ($p['X-WR-CALNAME'])) {
				$p['subscribe'] = sprintf(__('Subscribe to %s Calendar','amr-ical-events-list'), 
				htmlentities ($p['X-WR-CALNAME']));
				$p['X-WR-CALNAME'] = '<a '
				.' title="'.$p['subscribe'].'"'
				.' href="'.$p['icsurl'].'">'
				.htmlspecialchars($p['X-WR-CALNAME'])
				.'</a>';	
		}
		else {
				$f = basename($p['icsurl'], ".ics");
				$p['subscribe'] = sprintf(__('Subscribe to %s Calendar','amr-ical-events-list'), $f);
				$p['X-WR-CALNAME'] = '<a '
				.' title="'.$p['subscribe'].'"'
				.' href="'.$p['icsurl'].'">'
				.$f
				.'</a>';
		}
		$t = __('Calendar', 'amr-ical-events-list');
		if (isset ($amr_options['no_images']) and $amr_options['no_images']) $t3 = $t; 
		else $t3 = '<img class="subscribe amr-bling" src="'.IMAGES_LOCATION.CALENDARIMAGE.'" title= "'.$t.'" alt="'.$t.'" />';			
		$p['icsurl'] = '<a class="icalsubscribe" title="'.$p['subscribe']
					.'" href="'.$p['icsurl'].'">'.$t3.'</a>';
		}
		if (isset ($p['X-WR-CALDESC'])) {
				$p['X-WR-CALDESC'] = nl2br2 ($p['X-WR-CALDESC']);
		}

		$p['icalrefresh'] = amr_show_refresh_option();
		return ($p);
	}
/* --------------------------------------------------  */
function amr_list_properties($icals) {  /* List the calendar properties if requested in options  */
	global $amr_options; 
	global $amr_listtype;
	
		/* we want to maybe be able to replace the table html for alternate styling - will keep the li items though */
	$r = '<tr> ';  $d='<td '; /* allow for a class specifictaion */
	$rc = '</tr> '; $dc ='</td>';
	
	$html = '';
	
	$order = prepare_order_and_sequence  ($amr_options[$amr_listtype]['calprop']);

	if (!($order)) return; 

	foreach ($icals as $i => $p)	{ /* go through the options list and list the properties */
		amr_derive_calprop_further ($icals[$i]);
		$prevcol = $col = ''; 
		$cprop = '';   
		foreach ($order as $k => $v)  /* for each column, */
		{	
			$col = $v['Column'];
			if (!($col === $prevcol)) /* then starting new col */
			{	if (!($prevcol === '')) { 
					$cprop .= '</ul>'.$dc.' <!-- end of amrcol -->';
					}  /* end prev column */
				$cprop .= $d.'><ul class="amrcol amrcol'.$col.'">';  /* start next column */
				$prevcol = $col;
			}			
			if (isset ($icals[$i][$k])) /*only take the fields that are specified in options  */
			{		
				$cprop .= AMR_NL.AMR_TB.'<li class="'.strtolower($k).'">'.stripslashes($v['Before'])
				.amr_format_value($icals[$i][$k], $k)
				.stripslashes($v['After']).'</li>';
			}
		}
		if (!($cprop === '')) {/* if there were some calendar property details*/
//			if (!($amrW) and ($i == 0 ))  { /* only need to show the refresh once */
//				 $cprop .= AMR_NL.AMR_TB.'<li class="icalrefresh" >'.amr_show_refresh_option().'</li>';
//				}

			$html .= $r.$cprop.'</ul>'.$dc.' <!-- end of amrcol -->'.AMR_NL.$rc.AMR_NL;  		
			}
	}
	return ($html);
}

/* --------------------------------------------------  */
function amr_same_time ($d1, $d2) {
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
function amr_daysDifference( $beginDate, $endDate){ /* *** what if dates are in different timezones, and somehow cross over the days - will that ever happen??  */
   //explode the date by "-" and storing to array
   if (!(is_object($beginDate) and (is_object($endDate)))) return(false);

   $date_parts1=explode("-", $beginDate->format('n-j-Y'));
   $date_parts2=explode("-", $endDate->format('n-j-Y'));
   //gregoriantojd() Converts a Gregorian date to Julian Day Count  - month/day/year
   $start_date=gregoriantojd($date_parts1[0], $date_parts1[1], $date_parts1[2]);
   $end_date=gregoriantojd($date_parts2[0], $date_parts2[1], $date_parts2[2]);
   return ($end_date - $start_date);
}
/* --------------------------------------------------  */
function amr_calc_duration ( $start, $end) { /* assume in same timezone */
	/* In php 5.3 there is a date diff calculation */
	/* calculate weeks, days etc and return in array */
	/* don't want to use unix date stamp */
	   if (!(is_object($start) and (is_object($end)))) return(false);
	
	if (function_exists('date_diff')) { /* for php 5.3 only - untested locally */
		$interval = date_diff($start, $end);
		$d = (int) $interval->format('%d');

		$d = $duarray['days'] = (int) $interval->format('%d');
		$duarray['hours'] = (int) $interval->format('%h');
		$duarray['minutes'] = (int) $interval->format('%m');
		$duarray['seconds'] = (int) $interval->format('%s');
		$duarray['weeks'] = $d / 7;  if ($duarray['weeks'] < 1) $duarray['weeks'] = 0;
		$duarray['days'] = $d % 7; 
		return ($duarray);

	}
	/* else ....  do our own non unix calc */

	$d = amr_daysDifference( $start, $end);

	/* weeks */
	$w = $d / 7;  if ($w < 1) $w = 0;
	$d = $d % 7;   /* the remainder of days after complete weeks taken out */
	/* Note we do not need to add an extra day  or prior period if the hours go over a day, as the previous calculation will already have worked that out ????*/
	
	/* hours */
	$b = $start->format('G');
	$e = $end->format('G');	
	$h = $e - $b;
	if ($h < 0) { 
		$d = $d - 1;
		$h = 24 + $h;
	}

	/* minutes */
	$b = $start->format('i');
	$e = $end->format('i');	
	$m = $e - $b;
	if ($m < 0) { 
		$h = $h - 1;
		$m = 60 + $m;
	}
	
	/* seconds */
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
   if (!(is_object($d1) and (is_object($d2)))) return(false);
		 
	if (($d1->format('His') === '000000') and 
				($d2->format('His') === '000000')) {
				//$d1a = new DateTime();
				$d1a = clone ($d1);
				date_modify ($d1a,'next day');
				if ($d1a = $d2) return (true); 
			}
			return (false);
		}
/* ---------------------------------------------------------------------- */	
/* return true if the event is untimed and the end is one day after the start */
function amr_is_an_ical_single_day($d1, $d2) {

//	If (ICAL_EVENTS_DEBUG) echo '<br>check if ical single day<br>'.$d1->format('c').'<br>'.$d2->format('c');
		 
	$d1a = clone ($d1);
	date_modify ($d1a,'next day');
//	If (ICAL_EVENTS_DEBUG) echo '<br>check if ical single day<br>'.$d1a->format('c').'<br>'.$d2->format('c');
	if ($d1a === $d2) {

		return (true); 
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
		if ($d1 < $d2 ) return (true);
		else return (false);
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
global $amr_globaltz, $amr_options;
	$url = $_SERVER['REQUEST_URI'];
	$tz = timezone_name_get($amr_globaltz);
	if ($tz === $tzstring) $tz2 = date_default_timezone_get();
	else $tz2 = $tzstring;
	if ($tz2===$tz) $tz2 = 'UTC';

	$text1 = __('Change Timezone','amr-ical-events-list');
	$text2 = sprintf( __('Timezone: %s, Click for %s','amr-ical-events-list'),$tz, $tz2);

	if (isset ($amr_options['no_images']) and $amr_options['no_images']) $t3 = $text1; 	
	else $t3 = '<img title = "'.$text2.'" src="'.IMAGES_LOCATION.TIMEZONEIMAGE.'" class="amr-bling" alt="'.$text1.'" />';
	
	return ('<span class="timezone" ><a href="'
		.htmlspecialchars(add_querystring_var($url,'tz',$tz2)).'" title="'
		.$text2.'" >'.$t3.' </a></span>');
}
/* --------------------------------------------------------- */
function amr_format_bookmark ($text) {
	return ('<a id="'.$text.'"></a>');  /* ***/
}
/* --------------------------------------------------------- */
function amr_format_rrule ($rule) {

	$c = '';
	foreach ($rule as $i => $v) {			
		if (!(empty($v))) {$c .= 	amr_format_value ($v, $k) .'<br />';}
	}
	return ($c);

}
/* --------------------------------------------------------- */
function amr_derive_summary (&$e) {
	global $amr_options;
	global $amr_listtype;
	global $amrW;
	global $amrwidget_options;
/* If there is a event url, use that as href, else use icsurl, use description as title */

	if (isset($e['SUMMARY'])) $e['SUMMARY'] = htmlspecialchars(amr_just_flatten_array ($e['SUMMARY'] ));
	else return ('');
	if (isset($e['URL'])) $e_url = amr_just_flatten_array($e['URL']);
	else $e_url = '';	
	/* If not a widget, not listype 4, then if no url, do not need or want a link */
	/* Correction - we want a link to the bookmark anchor on the calendar page***/
	if (empty($e_url))  {
		if (!($amrW == 'w_no_url'))  {
			if (!empty($amrwidget_options['moreurl'])) {
				$e_url = ' href="'.$amrwidget_options['moreurl'].'#'.$e['Bookmark'].'" ';
			}
			else { 
				if (!empty($amr_options[$amr_listtype]['general']['Default Event URL'])) {
					$e_url = ' class="url" href="'
						.$amr_options[$amr_listtype]['general']['Default Event URL'].'" ';
					}
				else $e_url = ''; /*empty anchor as defined by w3.org */			
				/* not a widget */
			}
		}
		else {return ($e['SUMMARY']);	}
	}
	else { 
		$e_url = ' class="url" href="'.$e_url.'" ' ;
	}
	$e_desc = '';
	if (isset ($e['DESCRIPTION'])) {	
		$e_desc = amr_just_flatten_array($e['DESCRIPTION']);
		}
    if (!empty($e_desc)) {
		$e_desc = (str_replace( '\n', '  ', (htmlspecialchars($e_desc))));
	}
	else $e_desc =  '';

	if (!empty ($e_url)) 
		$e_url = '<a '.$e_url.' title="'.$e_desc.'">'. $e['SUMMARY'].'</a>';
	else $e_url = $e['SUMMARY'];
	
	return( $e_url );

}

/* --------------------------------------------------------- */
function amr_format_value ($content, $k) {
/*  Format each Ical value for our presentation purposes 
Note: Google does toss away the html when editing the text, but it is there if you add but don't edit.
what about all day?
*/

	global $amr_formats;  /* amr check that this get set to the chosen list type */
	global $amr_options;
	global $amr_listtype;

	if (is_object($content)) {
		switch ($k){
			case 'EventDate': return ('<abbr class="dtstart" title="'
					.amr_format_date ('l jS F Y, H:i e ', $content).'">'
					.amr_format_date ($amr_formats['Day'], $content)
					.'</abbr>'
					); 
			case 'EndDate': return ('<abbr class="dtend" title="'
					.amr_format_date ('r', $content).'">'
					.amr_format_date ($amr_formats['Day'], $content)
					.'</abbr>'
					); 
			case 'EndTime':
			case 'StartTime': 
				return (amr_format_date ($amr_formats['Time'], $content)); 
			case 'DTSTART': /* probably will never display these */
			case 'DTEND':
			case 'UNTIL':
				return ( amr_format_date ($amr_formats['Day'], $content).' '.amr_format_date ($amr_formats['Time'], $content)); 	
			case 'X-WR-TIMEZONE': { /* amr  need to add code to reformat the timezone as per admin entry.  Also only show if timezone different ? */
				return(timezone_name_get($content));
			}	
			case 'TZID': { /* amr  need to add code to reformat the timezone as per admin entry.  Also only show if timezone different ? */
				return(amr_format_tz (timezone_name_get($content)));
			}	

			default: 	/* should not be any */

				return (amr_format_date ($amr_formats['DateTime'], $content)); 	
		}	
	}
	else if (is_array($content)) {
		if ($k === 'DURATION') { return( amr_format_duration ($content)); }
		else if (($k === 'RRULE') or ($k === 'EXRULE')) { 
			$rule = amr_prepare_pretty_rrule ($content);
			return( amr_prettyprint_rule ($rule)); }
		else if (($k === 'RDATE') or ($k === 'EXDATE')) { 
			$dates = amr_prettyprint_r_ex_date ($content);
			return( $dates); 
			}			
		else {  /* don't think wee need to list the items separately eg: multiple comments or descriptions - just line  */
			$c = '';
			foreach ($content as $i => $v) {			
				if (!(empty($v))) {$c .= 	amr_format_value ($v, $k) .'<br />';}
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
					return( $content);
			case 'addtogoogle': return ($content);
			case 'addevent': return($content);									
			case 'X-WR-TIMEZONE':	/* not parsed as object - since it is cal attribute, not property attribue */	
				return(amr_format_tz ($content));
			case 'refresh': return($content);
			default: 
				return (amr_amp($content));
//		$content = format_date ( $amr_formats['Day'], $content);break;			 
		}
	}
	/* Convert any newlines to html breaks */
	return (str_replace("\n", "<br />", $content));
	
}
/* ------------------------------------------------------------------------------------*/
function amr_add_duration_to_date (&$e, $d) {	
/* adjust the signs  of the duration array as necessary to that date modify can handle it */
/*   dur-value  = (["+"] / "-") "P" (dur-date / dur-time / dur-week)

  dur-date   = dur-day [dur-time]
  dur-time   = "T" (dur-hour / dur-minute / dur-second)
  dur-week   = 1*DIGIT "W"
  dur-hour   = 1*DIGIT "H" [dur-minute]
  dur-minute = 1*DIGIT "M" [dur-second]
  dur-second = 1*DIGIT "S"
  dur-day    = 1*DIGIT "D"
  */
	if ((isset($d['sign'] )) and ($d['sign'] === '-')) $dmod = '-';  /* then apply it to get our current end time */
	else $dmod = '+';
	foreach ($d as $i => $v)  {  /* the duration array must be in the right order */
		if (!($i === 'sign')) { $dmod .= $v.' '.$i ;}
	}
	date_modify ($e, $dmod );
	return ($e);		
  }
 /* ------------------------------------------------------------------------------------*/
function amr_derive_dates (&$e) {	
/* Derive basic date dependent data  - called early on before repeating */
	if (!isset($e['DTSTART']) ) return (false);
	if (is_array($e['DTSTART'])) $e['DTSTART'] = $e['DTSTART'][0];
	if (isset($e['DTEND']) and is_array($e['DTEND'])) $e['DTEND'] = $e['DTEND'][0];

	if ((isset ($e['DURATION'])) and (!isset ($e['DTEND'])))  {  /*** an array of the duration values, calc the end date or time */	
		$e['DTEND'] = new DateTime();	
		$e['DTEND'] = clone (amr_add_duration_to_date ($e['DTSTART'], $e['DURATION']));
		If (ICAL_EVENTS_DEBUG) {echo '<br>DTEND set to = '.$e['DTEND']->format('c');}
	}
	else 
		if ((isset ($e['DTEND'])) and (!isset ($e['DURATION']))) { /* we don't have a duration */
			$e['DURATION'] = $d = amr_calc_duration ( $e['DTSTART'], $e['DTEND']);		/* calc the duration from the original values*/
		}
/* else EndDate will be unset */
	return($e);
} 
/* ------------------------------------------------------------------------------------*/
function amr_derive_eventdates_further (&$e) {	
global $amr_globaltz;
/* Derive any date dependent data - requires EventDate at least to have been set */
	$now = date_create('now',$amr_globaltz );
	
	if (isset($e['EventDate'])) { date_timezone_set($e['EventDate'],$amr_globaltz );}
	if (!isset($e['Classes'])) $e['Classes'] = '';
	if (isset($e['EndDate'])) {
		date_timezone_set($e['EndDate'],$amr_globaltz );
		if (amr_is_before($e['EndDate'], $now)) $e['Classes'] .= ' history'; 
		}
	else /* ie there is no end date, just an event date */ if (amr_is_before($e['EventDate'], $now)) $e['Classes'] .= ' history'; 	
	if (amr_is_before( $now, $e['EventDate'])) $e['Classes'] .= ' future';
	else $e['Classes'] .= ' inprogress';
	if (amr_is_same_day ($e['EventDate'],  $now)) $e['Classes'] .= ' today'; 
	
	if (isset ($e['Untimed'])) { /* if it is untimed, the ical spec says that the end date is the "next day" */
		$e['Classes'] .= ' untimed';
		if (isset ($e['EndDate']) ) {
			if (	(amr_is_an_ical_single_day ($e['EventDate'], $e['EndDate'])) OR 
					(amr_is_same_day($e['EndDate'],  $e['EventDate'])) ) /* an ical generator error, but deal with it */ { 				
				unset ($e['EndDate']); /* so we don't display them unecessarily */
				unset ($e['EndTime']);
				}
			else { /* it must be a multi day, all day - due to spec, we need to chop a day off for presentation purposes */	
				$e['EndDate']->modify("-1 day");
				if ($e['EventDate'] == $e['EndDate']) { /* */
				}
				}
			}
	}
	else $e['StartTime'] = $e['EventDate']; /* will format to time, later keep date  for max flex */	
	
	if (isset ($e['EndDate']) ) {
		if (amr_is_all_day($e['EventDate'], $e['EndDate'])) {	
			unset ($e['StartTime']);
			unset ($e['EndTime']);
			$e['Classes'] .= ' allday'; 
		}
		else {
			if (amr_same_time($e['EventDate'], $e['EndDate'])) unset ($e['EndTime']);	
			else $e['EndTime'] = $e['EndDate'];

		}
		if (amr_is_same_day($e['EndDate'],  $e['EventDate'])) {
			unset($e['EndDate']);  /* will just have end time if we need it */		
		}
		else $e['Classes'] .= ' multiday'; 
	}
	

	return($e);
}
/* ------------------------------------------------------------------------------------*/

function amr_derive_component_further (&$e) {	
global $amrW;

	/* The RFC 2445 values will have been set by the import process.
		Eventdate will be set by the recur check process
		Now we need to derive, set or modify  any other values for our output requirements
	*/
	$e['Classes'] = '';
	if (isset ($e['EventDate'])) {
		amr_derive_eventdates_further($e);
		$bookm = $e['EventDate']->format('U');
	}
	else $bookm = '';
	
	/* Noew get some styling possibilities */
	if (isset ($e['RRULE']) or (isset ($e['RDATE']))) $e['Classes'] .= ' recur'; 
	if (isset ($e['STATUS']) ){
		$e['Classes'] .= ' '.amr_just_flatten_array($e['STATUS']);  /* would have values like 'CONFIRMED'*/ 
	}
	if (isset($e['name']))  $e['Classes'] .= ' '.$e['name'];
	if (isset($e['type']))  $e['Classes'] .= ' '.$e['type'];  /* so we can style events, todo's differently */
	if (isset($e['CATEGORIES'])) 
		$e['Classes'] .= ' '.str_replace(',',' ',amr_just_flatten_array($e['CATEGORIES']));

	if (isset($e['UID'])) $e['Bookmark'] = 'a'.str_replace('@','',$e['UID']).$bookm;  /* must be before summary as it is used there .  Must be a char to start not a number and get rid of odd chars for validation*/
	$e['SUMMARY'] = amr_derive_summary ($e);
	if (isset ($e['GEO'])) {	$e['map'] = amr_ical_showmap($e['GEO']); }
	else if ((isset ($e['LOCATION'])) and (!empty($e['LOCATION']))) { 
			$e['map'] = amr_ical_showmap($e['LOCATION']); 	
	}
	
	if ($g = add_event_to_google($e))  $e['addevent'] = $g;
	
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
			if (empty($arr) or (count($arr)< 1)) return (null);
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

function amr_list_events($events, $g=null) {
	global $amr_options; 
	global $amr_listtype;
	global $amrW;
	global $amr_groupings;
	
	/* we want to maybe be able to replace the table html for alternate styling - will keep the li items though */
	$r = '<tr '; $h='<th '; $d='<td '; /* allow for a class specifictaion */
	$rc = '</tr> '; $hc ='</th>'; $dc ='</td>';
	$hd = AMR_NL.'<thead>'; $ft = AMR_NL.'<tfoot>'; $bd = AMR_NL.'<tbody>'; 
	$hdc = AMR_NL.'</thead>'; $ftc = AMR_NL.'</tfoot>'; $bdc = AMR_NL.'</tbody>'; 

	/* The component properties have an additional structuring level that we need to remove */
	if (!isset($amr_options)) echo '<br />Options not set';
	else if (!isset($amr_options[$amr_listtype])) {
			echo '<br>Please tell Administrator: listtype Not set: '.$listtype.' In Option array ';
			}
	else if (!isset($amr_options[$amr_listtype]['compprop'] )) {	echo '<br>Compprop not set in Option array ';
		}
	else {	
		/* check for groupings and compress these to requested groupings only */
		if (isset($_REQUEST['grouping'])) $gg = ucwords($_REQUEST['grouping']);
		else $gg = '';
		if (array_key_exists($gg,$amr_groupings)) $g[$gg] = true;
		else {
			if (isset ($amr_options[$amr_listtype]['grouping'])) {  	
				foreach (($amr_options[$amr_listtype]['grouping']) as $i => $v)
					{	if ($v) { $g[$i] = $v; }			}
			}
		}
		if ($g) {foreach ($g as $gi=>$v) 
			{$new[$gi] = $old[$gi] = '';}} /* initialise group change trackers */		
		
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
		$html = $hd.$r.'>';
		for ($i = 1; $i <= $no_cols; $i++) { 			/* generate the heading code if requested */
			if (isset($amr_options[$amr_listtype]['heading'][$i])) $head = $amr_options[$amr_listtype]['heading'][$i];
			else $head = ' ';
			$html .= $h.'class="amrcol'.$i.'">'.$head.$hc;	
		}
		$html .= $rc.$hdc;
		$html .= $ft.$r.'>'.$d.'colspan="'.$no_cols.'"  >';
		if (!empty($amr_limits)) {
			if (function_exists('amr_semi_paginate')) $html .= amr_semi_paginate();
			else $html .= amr_ngiyabonga();
		}
//		if (!($amrW)) {$html .= amr_show_refresh_option ();}
		$html .= $dc.$rc.$ftc;
		$html .= $bd;
		$alt= false;
		
		if ((!is_array($events)) and (count($events) > 0 )) return ('');
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
				if (isset($e[$k])) $v = amr_check_flatten_array ($e[$k]);
				else $v =null;
				
				if ((isset ($v))  && (!empty($v)))	{
					$col = $kv['Column']; 
					if ($col > $prevcol) { /* if new column, then new cell , */
						if (!($prevcol === 0))  {	/*if not the first col, then end the prev col */
							$eprop .= '</ul>'.$dc; 
						}
						$colcount = $colcount +1;
						while ($colcount < $col) { /* then we are missing data for this column and need to skip it */
							$colcount = $colcount +1;
							$eprop .= $d.'>&nbsp;'.$dc;
						}
						$eprop .= $d. ' class="amrcol'.$col;
						if ($col == $no_cols) $eprop .= " lastcol"; /* only want the call to be lastcol, not the row */
						$eprop .= '">'
							.(((!$amrW) and ($col==1))?
							amr_format_bookmark($e['Bookmark'])	: "")
						.'<ul class="amrcol'.$col.' amrcol">';/* each column in a cell or list */
						$prevcol = $col;
					}						
					$eprop .= AMR_NL.AMR_TB.'<li class="'.strtolower($k).'">'.stripslashes($kv['Before'])
						. amr_format_value($v, $k).stripslashes($kv['After']).'</li>';  /* amr any special formatiing here */
				}
			}

			if (!($eprop === '')) /* ------------------------------- if we have some event data to list  */
			{	/* then finish off the event or row, save till we know whether to do group change heading first */
				$eprop = $r.($alt ? ' class="alt':' class="').$classes.'"> '
					.$eprop.AMR_NL.'</ul>'.$dc.$rc;
					
				if ($alt) $alt=false; else $alt=true; 	
				/* -------------------------- Check for a grouping change, need to end last group, if there was one and start another */
				$change = '';
				if ($g) 
				{	foreach ($g as $gi=>$v) {	
						if (isset($e['EventDate'])) $grouping = format_grouping($gi, $e['EventDate']) ; 
						else $grouping = '';
						$new[$gi] = amr_string($grouping);  
						if (!($new[$gi] == $old[$gi])) {  /* if there is a grouping change then write the heading for the group */
							$id = amr_string($gi.$new[$gi]);
							$change .= 	$r.'class="group '.$gi.'">'.$h
							.' class="'.$id.' group '.$gi. '"  colspan="'.$no_cols.'" >'.$grouping.$hc.$rc;
							$old[$gi] = $new[$gi];							
						}
					} 					
				}				
				$html .= $change.AMR_NL.$eprop;	
			} 	
		} 
	}
	$html .= $bdc;
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
			$w = amr_format_date( 'W', $datestamp);
			return (sprintf(__('Week  %u', 'amr-ical-events-list'),$w));
		}
	else 
	{ 	/* for "Quarter",	"Astronomical Season",	"Traditional Season",	"Western Zodiac",	"Solar Term" */
		$func = str_replace(' ','_',$grouping);
		if (function_exists($func) ) {
			return call_user_func($func,$datestamp);
			}
		else  return ('No function defined for Date Grouping '.$grouping);
	}
}/* -------------------------------------------------------------------------------------------*/
function amr_wp_format_date( $format, $datestamp, $gmttf) { /* want a  integer timestamp or a date object  */
global $amr_options;
/* Need to get rid the unnecessary dat logic - should only be using date objects for now */
							
	if (is_object($datestamp))	{	
		$offset = $datestamp->getOffset();
		If (isset ($_REQUEST['tzdebug'])) {
			echo '<br />Want to format '.$datestamp->format('Ymd His').' in '.$format.' like this '.$datestamp->format($format).' but localised';
//			echo '<br />Add offset '.$offset/(60*60).' back to Unix timestamp to force correct localised date ';
			}
		$dateInt = $datestamp->format('U') /* + $offset */;  
		}
	else if (is_integer ($datestamp)) $dateInt = $datestamp;
	else return(false); 

	if (stristr($format, '%') ) return (strftime( $format, $dateInt ));  /* keep this for compatibility!  will not localise though */
	else {
		$text = date_i18n($format, $dateInt, $gmttf); /*  should  be false, otherwise we get the utc/gmt time.   */
		If (isset ($_REQUEST['tzdebug'])) 
			{	echo '<br />Localised with gmt=false: '.$text.'<br />';	
				$text2 = date_i18n($format, $dateInt, false); 
				echo 'Localised with gmt=true:  '.$text2.'<br />';	
				$text3 = amr_date_i18n ('D, F j, Y g:i a', $datestamp); 
				echo 'Localised with amr date obj fn: '.$text3.'<br />';
			}
		return ($text); // 

		}

}
/* -------------------------------------------------------------------------------------------*/
function amr_format_date( $format, $datestamp) { /* want a  integer timestamp or a date object  */
global 	$amr_options,
		$amr_globaltz;

	if (isset ($amr_options ['date_localise'])) $method = $amr_options ['date_localise'];
	else $method = 'none';

	date_timezone_set ($datestamp, $amr_globaltz);  /* Converting here, but then some derivations wrong eg: unsetting of end date */

	if ($method === 'wp') return amr_wp_format_date ( $format, $datestamp, false);
	else if ($method === 'wpgmt') return amr_wp_format_date ( $format, $datestamp, true);
	else if ($method === 'amr') return amr_date_i18n ( $format, $datestamp); 
	else {	
		if (stristr($format, '%') ) return (strftime( $format, $datestamp->format('U') ));  /* keep this for compatibility!  will not localise though */
		else return ($datestamp->format($format)); 

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
			$compare = create_function('$a, $b', 'if (!isset($a["' . $key . '"])) return false;'
			.'	if (!isset($b["' . $key . '"])) return (false) ;'
			.'	if  ($a["' . $key . '"] == $b["' . $key . '"]) { return 0; } else { return ($a["' . $key . '"] < $b["' . $key . '"]) ? -1 : 1; }');
			usort($data, $compare);

			return $data;
		}
/* ------------------------------------------------------------------------------------*/

function amr_falls_between($eventdate, $astart, $aend) {
		/*
		 * Return true iff the specified event falls between the given
		 * start and end times.
		 */		
		if (($eventdate <= $aend) and 
			($eventdate >= $astart)) return ( true);
		else return (false);	
		}
/* ------------------------------------------------------------------------------------*/

function amr_event_should_be_shown($event, $astart, $aend) {
		/*
		 Return true if the specified event should be shown.  This could be due to a number of situations:
		 * there is no dtstart (it is a note?)
		 * event starts and ends between the start and end date
		 * event starts after the start date, before the end date - it may finish after the end date/time.
		 * event started before the start date and ends after the finish date
		 * event is an untimed event (eg: all day) and starts on the same day, but before the start datetime.  (Does it still have it's end date - we zap that at some point?)
		 */
		if (!isset ($event['DTSTART'] )) return (true);
		if (ICAL_EVENTS_DEBUG) echo '<hr>dtstart='.$event['DTSTART']->format('c');
		if ($event['DTSTART'] >= $astart) {
			if (ICAL_EVENTS_DEBUG) echo '<br />date >= start' ;
			if ($event['DTSTART'] <= $aend) {
				if (ICAL_EVENTS_DEBUG) echo '<br />date <= end' ;
				return (true);
				}
			else {
				if (ICAL_EVENTS_DEBUG) echo '<br />date > start' ;
				if (isset($event['Untimed']) and amr_is_same_day($event['DTSTART'],$aend )) {
					if (ICAL_EVENTS_DEBUG) echo '<br />date untime and same day' ;
					return (true);
				}
				else {
					if (ICAL_EVENTS_DEBUG) { echo '<br />date not untimed or not same day' ;  echo '<hr>';}
					return (false);
					}
			}
		}
		else { /* ie DTSTART is < $astart, but may still be on same day, or not finished, ie continuing past */	
			if (ICAL_EVENTS_DEBUG) echo '<br />date < start' ;
			if (isset($event['DTEND'])	and ($event['DTEND'] >= $astart)) {
				if (ICAL_EVENTS_DEBUG) echo '<br />date <= end' ;
				return (true);
			}
			else {
				if (ICAL_EVENTS_DEBUG) echo '<br /> No end....' ;
				if (isset($event['Untimed']) and amr_is_same_day($event['DTSTART'],$astart )) {
					if (ICAL_EVENTS_DEBUG) echo '..Is Untimed and same day' ;
					return (true);
				}
				else if (ICAL_EVENTS_DEBUG) echo '.. Is NOT Untimed or not same day' ;
			}
		
		}	

}		

/* ------------------------------------------------------------------------------------*/
		/* Process an array of datetime objects and remove duplicates 
		 */
function amr_arrayobj_unique2 (&$arr) {
		/* 	Duplicates can arise from edit's of ical events - these will have different sequence numbers and possibly differnet details - delete one with lower sequence,
			Also there maybe? the possobility of complex recurring rules generating duplicates - these will have same sequence no's and should have same details - delete one    
			Note: Mozilla does not seem to generate a SEQUENCE ID, but does do a X-MOZ-GENERATION.
		*/	
		$limit = count ($arr);	
		if (isset($_REQUEST['debugexc'])) {echo '<br><br>Check for modifications or exceptions for array of '.$limit.' records<br>'; }	
		krsort ($arr);  /***  sort numerically  We can then walk through and "toss" the lower sequence numbers for a given uid and date instance */

		if (isset($_REQUEST['debugexc'])) foreach ($arr as $i=> $a) {echo '<br>..'.$i;}  /* Check the sorting */		
		$seqprev = '';
		$uiddateprev = '';
		foreach ($arr as $i => $e) {			
				if ($seqstart = strrpos($i, " ")) {
					$seq = (int) substr($i, $seqstart, 100); 
					$uiddate = substr ($i, 0, $seqstart);
				}
				else {if (isset($_REQUEST['debugexc'])) echo '<hr/>Must be a unique event? as key does not match pattern'; break;} /* not an ical event, must be a unique post event */
				if (isset($_REQUEST['debugexc'])) { 
					echo '<br />seq='.$seq.' '.substr($i,0,6).'...'.substr($i,$seqstart-26,200);
					echo '<br />uidate: '.$uiddate;
					echo '<br />uidprv: '.$uiddateprev;
				} 
				if ($uiddate == $uiddateprev) {
					if ($seq < $seqprev ) {
						unset ($arr[$i]);
						if (isset($_REQUEST['debugexc'])) echo '<br /><b>Delete seq: Seq was ='.$seq.' and $seqprev was: '.$seqprev	.'</b>';						
						}
					else if ($seqprev < $seq) {
						if (isset ($iprev)) unset ($arr[$iprev]);
						if (isset($_REQUEST['debugexc'])) 
							echo '<br /><b>Delete seqprev Seq was ='.$seq.' and seqprev was: '.$seqprev.'</b>';
					}
					else echo '<br ><b>Unexpected inability to sort modification from original.  Please inform administrator. ? seqprev = '.$seqprev.' and seq = '.$seq.'</b>';
					/* Note that while sequence numbers can be the same for a recurrencid, the plugn will add 999 for a recurrence id */
				}

				$uiddateprev = $uiddate;
				$seqprev = $seq;
				$iprev = $i;
//				if (!(empty($seq)) and (!($seq===" "))) {
	//				if (isset($_REQUEST['debugexc'])) echo '<br />** Possible exception with seq: '.$seq.' - check for outdated instances for <br />'.substr($i,0,6).'...'.substr($i,$seqstart-26,200);
//				}

			}
	}

	/* ========================================================================= */			
function amr_repeat_anevent($event, $astart, $aend, $limit) {
	/* for a single event, handle the repeats as much as is possible */
	$repeats = array();
	$exclusions = array();
	
	$repeatstart = $event['DTSTART'];
	if (isset($event['RRULE']))	{	
			if (ICAL_EVENTS_DEBUG) { echo '<br>RRULE =  '; var_dump($event['RRULE']);}	
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
			if (ICAL_EVENTS_DEBUG) { echo '<br><h3>Have EXRULE </h3>';var_dump($event['EXRULE']);}			
			foreach ($event['EXRULE'] as $i => $exrule) {			
				$reps = amr_process_RRULE($exrule, $repeatstart, $aend, $limit);
				if (is_array($reps) and count($reps) > 0) {			
					$exclusions  = $reps;
				}
			}
			if (ICAL_EVENTS_DEBUG) { echo '<br><h3>Got '.count($exclusions). ' after EXRULE</h3>';}		
		}
			
		if (isset($event['EXDATE']))	{	
			if (ICAL_EVENTS_DEBUG) { echo '<br><h4>Have EXDATE </h4>';}	
			foreach ($event['EXDATE'] as $i => $exdate) {	
				$reps = amr_process_RDATE($exdate, $repeatstart, $aend, $limit);
				if (is_array($reps) and count($reps) > 0) {
						$exclusions  = array_merge($exclusions , $reps);
				}
				
			}
			if (ICAL_EVENTS_DEBUG) { foreach ($exclusions as $z => $y) echo '<br />'.$y->format('c');}	
			if (ICAL_EVENTS_DEBUG) { echo '<br />Got  '.count($exclusions). ' exclusions after checking EXDATE';}	
		}

		if (ICAL_EVENTS_DEBUG) { echo '<br />Still have '.count($repeats). ' before exclusions';}				
			/* Now remove the exclusions from the repeat instances */
		if (is_array($exclusions) and count ($exclusions) > 0) {
			foreach ($exclusions as $i => $excl) {
			    foreach ($repeats as $j => $rep) {
				    if ($excl->format('c') === $rep->format('c')) {
//					if (!($excl < $rep) and !($excl > $rep)) {
						if (ICAL_EVENTS_DEBUG) {
							echo '<br> Exclusion matches repeat date, so exclude this date '.$j.' '. $rep->format('c');
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
		$tab = '';
		echo '<ul>';
		foreach ($e as $i => $f) {
			if (is_object($f)) echo '<li>'.$tab.$i.' = '.$f->format('c').'</li>';
			else if (is_array($f)) {echo '<li>'.$tab.$i; debug_print_event ($f, ($nest+1)); echo '</li>';}
				else {echo '<li>';
//				.$tab.$i.' = '
				if (!(is_int($i))) echo $i.' = ';
				echo var_dump($f).'</li>';
				}
		}
		echo '</ul>';
	}
	/* ========================================================================= */	
function amr_create_enddate (&$e) {	
	/* if the necessary data exist, then create the end date for a possibly repeated event. */
	if (isset ($e['DURATION'])) {/* if not just an alarm */
		if (isset ($e['EventDate'])) {
			$e['EndDate'] = new DateTime();
			$e['EndDate'] = clone ($e['EventDate']); 
			$e['EndDate'] = amr_add_duration_to_date ($e['EndDate'], $e['DURATION']);  
			if (ICAL_EVENTS_DEBUG) {
				echo ' with end date '.$e['EndDate']->format('c');	
			}
			return (true);
		}
		else return (false);
	}
	else return (false);
	}
	/* ========================================================================= */	
function amr_process_single_icalevents(&$event, $astart, $aend, $limit) {

		$repeats = array(); // and array of dates 
		$newevents = array();  // an array of events 
//		$utz = new DateTimeZone('UTC');
		
		if (isset($event['DTSTART'])) {
			if (is_object($event['DTSTART'])) {
				$dtstart = $event['DTSTART'];	
				$dt = empty($event['DTSTART']) ? '' : $event['DTSTART']->format('c'); /* begin setting up the event key that will help us check for modifocations - semingly duplicates! - overwrite for repeats */
			}
			else {
				if (ICAL_EVENTS_DEBUG) echo('DTSTART is not an object'); return (false); /* it is set, but it is not an aobject ? */
				}
			}
		else { /* possibly an undated, non repeating VTODO or Vjournal- no repeating to be done if no DTSTART, and no RDATE */
			$dt = '-nodtstart';
		
			if (!isset($event['RDATE']))	{
				if (isset ($event['UID'])) 	$newevents[$event['UID']] = $event;
				else echo('This event is invalid.  It has no UID.');	
//					echo '<h2>no dtstart, no rdate </h2>';
//					var_dump($newevents);
				return ($newevents); /* possibly an undated, non repeating VTODO or Vjournal- no repeating to be done if no DTSTART, and no RDATE */
			}
			else {
				echo ('This event is invalid.  It has no DTSTART, but does have RDATE.  Not allowed according to ical spec.');
				return(false);
			/***check for repeating RDATEs if no start date */
			}
		}

		/* To handle modifications, use a key to the events, so can match any later mods with a repeating event we may have generated */
		$seq = empty($event['SEQUENCE']) ? '0' : $event['SEQUENCE']; /* begin setting up the event key that will help us check for modifications - semingly duplicates!*/
		if (!isset($event['UID'])) $event['UID'] = 'NoUID';
		 /* there is no repeating rule, so just copy over */			

		if (isset ($event['RECURRENCE-ID'])) {   /* a modification or exceptions to a repeating instance ? */
//					$recdate = $dt;
			if (is_array($event['RECURRENCE-ID'])) { /* just take first, should only be one */
				$recdateobj = $event['RECURRENCE-ID'][0];	
				$recdate = $recdateobj->format('YmdHis');   // purely identifies specifc instances of a repeating rule are affected by the exception/modification		
				if (isset ($_GET['debugexc'])) {echo '<br /> Flag recurrence modification for '.$recdate; }						
			}
			else if (is_object($event['RECURRENCE-ID'])) {  /* Then it is a date instance which has been modified .  We need to overwrite the appropriate repeating dates.  This is done later? *** */
				$recdateobj = $event['RECURRENCE-ID'];
				$recdate = $recdateobj->format('YmdHis');
				if (isset ($_GET['debugexc'])) {echo '<br /> We have a single object recurrence date to check for modifications of an event.' ;echo($recdate);}
			}
			else { /****  should deal with THISANDFUTURE or THISANDPRIOR  EG:
				 RECURRENCE-ID;RANGE=THISANDPRIOR:19980401T133000Z */
				echo '<br>THISAND.... modification to repeating event encountered.  This cannot be dealt with yet'; var_dump($event['RECURRENCE-ID']);
			}
			if ( amr_event_should_be_shown($event,$astart, $aend) or 			
				amr_falls_between($recdateobj, $astart, $aend) or  /* If the modification relates to an event  instance (ie date) that is in range */
				amr_is_same_day ($recdateobj, $astart) or /* if on same day, may be an all day event - will constrain finally later */
				amr_is_same_day ($recdateobj, $aend) 

				) /* OR the new date is in our display range, */{

				$key = $event['UID'].' '.$recdate.' '.$seq.'999';  /* By virtue of being a recurrence id it should override a non recurrence (ie normal) even if they have the same sequence */	
				$newevents[$key] = $event;  /* so we drop the old events used to generate the repeats */
				$newevents[$key]['EventDate'] = new DateTime(); 
				$newevents[$key]['EventDate'] = clone ($dtstart); 
				if (!(amr_create_enddate($newevents[$key]))) {if (ICAL_EVENTS_DEBUG) echo ' ** No end date - is it an alarm? ';};		
			}
			else {
				if (isset ($_GET['debugexc'])) {
					echo '<br /> '.$recdate.' not in range and '.$dtstart->format('c').' not in range' ;
//					debug_print_event ($event);
					}
				return(false); /* the modification and the instance that it relates to are not in our date range */
			}
			
		}

		else { /* It is not a recurrence id, may be a repating, or solo */

			if (isset($dtstart) ) {
				if ( amr_is_before ($dtstart, $aend) ) {  /* If the start is after our end limit, then skip this event */		
			
				if (isset($event['RRULE']) or (isset($event['RDATE']))) { 
							/* if have, must use dtstart in case we are dependent on it's characteristics,. We can exclude too early dates later on */
					$repeats = amr_repeat_anevent($event,$astart,$aend, $limit );  /**** try for a more efficient start? */					
					if (ICAL_EVENTS_DEBUG) {echo '<br>Num of Repeats to be created: '.count($repeats).'<br>';}	
					/* now need to convert back to a full event by copying the event data for each repeat */						
					if (is_array($repeats) and (count($repeats) > 0)) {

						foreach ($repeats as $i => $r) {
			
							$repkey = $event['UID'].' '.$r->format('YmdHis').' '.$seq;	/* Don't use timezone - some recurrence id's maybe created with universal dates */					

							if (isset ($newevents[$repkey])) error_log('Unexpected Duplication of Repeating Event - error in ical file or error in plugin?');
							$newevents[$repkey] = $event;  // copy the event data over - note objects will point to same object - is this an issue?   Use duration or new /clone Enddate
							$newevents[$repkey]['EventDate'] = new DateTime();
							$newevents[$repkey]['EventDate'] = clone ($r);  
//							if (ICAL_EVENTS_DEBUG) {echo '<br>Created '.$newevents[$repkey]['EventDate']->format('YmdHis');	}
							if (!amr_create_enddate($newevents[$repkey])) {if (ICAL_EVENTS_DEBUG) echo ' ** error creating end date ';};
						}
					}				
				}
				else {
					$key = $event['UID'].' '.$dt.' '.$seq;  /* No Recurrence id and no RRULE or RDATE */		
					$newevents[$key] = $event;  // copy the event data over - note objects will point to same object - is this an issue?   Use duration or new /clone Enddate
					$newevents[$key]['EventDate'] = new DateTime();
					$newevents[$key]['EventDate'] = clone ($dtstart);  
					if (isset ($newevents[$key]['DTEND'] )) $newevents[$key]['EndDate'] = $newevents[$key]['DTEND'] ;
				}
				}
				else if (ICAL_EVENTS_DEBUG) {echo '<br>Skipped this event.  Overall End '.$aend->format('c'), ' is not later than event start '. $event['DTSTART']->format('c');}
			}
			else {
				$key = $event['UID'].' '.$dt.' '.$seq; 
				$newevents[$key] = $event; 
				$newevents[$key]['EventDate'] = '';
				}
		}
		
		return ($newevents);
	}	
/* ------------------------------------------------------------------------------------*/
	/*
	 * Constrain the list of COMPONENTS to those which fall between the
	 * specified start and end time, up to the specified number of
	 * events.
	 * Note; MUST take  RECURRENCE -ID if the recurrence date is in range (even though the DTSTART may not be), as they may be modifying a date that is within the range
	 */
function amr_constrain_components($events, $start, $end, $limit) {
global $amr_limits;
	
		$newevents = amr_process_icalevents($events, $start, $end, $limit);	

		/* should we be moving to destination date  */
		
		if (count($newevents) < 1) 	return (false);
		$newevents = amr_sort_by_key($newevents , 'EventDate');	
		$constrained = array();
		$count = 0;
		foreach ($newevents as $k => $event) {	
		
			if (isset ($event['EventDate']) and (is_object ($event['EventDate']))) {
				if (ICAL_EVENTS_DEBUG) { echo '<br>Check eventdate '.$k.' '. $event['EventDate']->format('c'). ' against '.$start->format('c');}	
//				if (amr_event_should_be_shown($event,  $start, $end)) { /* note usung event date not dt start, so this will not work unless we modify function */
				
				if (amr_falls_between($event['EventDate'], $start, $end) OR
					(isset($event['EndDate']) and 
						(amr_falls_between($event['EndDate'], $start, $end) OR  /* end date will catch those all day ones that are untimed or those that h ave not yet finished !! */
						(amr_falls_between($start, $event['EventDate'], $event['EndDate'])))) ) /* catch those that start before our start and end after our start */
					{					
					$constrained[] = $event;
					if (ICAL_EVENTS_DEBUG) { 
						echo '<br>Choosing no: '.$k.' '. $event['EventDate']->format('c');}		
					++$count;	
				}
			}
			else $constrained[] = $event;							
			if ($count >= $limit) break;
		}
		
		if (isset($amr_limits['eventsoffset']) and (!empty($amr_limits['eventsoffset']))) {
			if (function_exists('amr_do_events_offset')) {
				$constrained = amr_do_events_offset ($constrained);
			}
			else suggest_other_icalplugin(__('Events Offset','amr-ical-events-list'));
		
		}
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

			amr_derive_dates ($event); /* basic clean up only - removing unnecessary arrays etc */
			$more = amr_process_single_icalevents($event, $astart, $aend, $limit);
			if (ICAL_EVENTS_DEBUG) { 
				if ($more) {echo ' <br>No.Dates= '.count($dates).' Plus = '.count($more). ' keys: ' ;  
					foreach ($more as $i=>$a) echo ' '.$i;
				}
			}	
			if (is_array($more)) $dates = $dates + $more ;	
			if (ICAL_EVENTS_DEBUG) { echo '<hr>'; foreach ($dates as $i=>$a) echo ' '.$i;}
		}
		
		if (ICAL_EVENTS_DEBUG) {echo '<hr>No.Dates= '.count($dates);  } // 	
		
		if ((is_array($dates)) and (count($dates) > 1)) { /* must be > 1 for tere to be a duplicate! */
			amr_arrayobj_unique2($dates); /* remove any duplicate in the values , check UID and Seq*/
			if (ICAL_EVENTS_DEBUG) { echo '<br>Now have '.count($dates). ' after  duplicates check.';}	
		}
	
		return ($dates) ; 
	}


/* -------------------------------------------------------------------------*/
function process_icalurl($url) {
global $amr_limits;
/* cache the url if necessary, and then parse it into basic nested structure */

	$file = amr_cache_url(str_ireplace('webcal://', 'http://',$url),$amr_limits['cache']);
	if (!($file)) {	
			echo '<br>'.sprintf(__('Unable to load or cache ical calendar %s','amr-ical-events-list'),$url);	
			return;	
		}
	$ical = amr_parse_ical($file);
	if (! (is_array($ical) )) {
			echo sprintf('Error finding or parsing ical calendar %s',$url);
			return($ical);
		}
	$ical['icsurl'] = $url; 	
	return ($ical);	
}

/* -------------------------------------------------------------------------*/
function amr_echo_parameters() {
global $amr_limits;

	$text = '';
	foreach ($amr_limits as $i=> $v) {
		$text .= __($i,'amr-ical-events-list').' = ';
		if (is_object($v)) $text .= $v->format('j M i:s');
		else $text .= $v;
		$text .= ', ';
	}
	return ($text);
	

//	return ( __('Days to show: ','amr-ical-events-list').$amr_limits['days'].';&nbsp;'
//	.__('Events to show: ','amr-ical-events-list').$amr_limits['events'].';&nbsp;'
//	.__('Start date: ','amr-ical-events-list').$amr_limits['start']->format('j M i:s').';&nbsp;'
//	.__('End date: ','amr-ical-events-list').'&nbsp;&nbsp;'.$amr_limits['end']->format('j M i:s')
//);
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
/* -------------------------------------------------------------------------*/
function suggest_other_icalplugin($featuretext) {?>
	<br/><?php echo $featuretext.' - '.__('This requested feature requires the plugin amr-events','amr-ical-events-list'); ?>
	<a href="http://icalevents.anmari.com"><?php _e('Get it here','amr-ical-events-list' ); ?></a>
	<br/><?php
}
/* -------------------------------------------------------------------------*/
function first_of_month($date) { /* delete */
	$days = (int) $date->format('d');
	if ($days > 1) 	return($date->modify('-'.$days.' days'));
	else return($date);
}

/* -------------------------------------------------------------------------*/
function amr_process_icalspec($criteria, $icalno=0) {
/*  parameters - an array of urls, an array of limits (actually in amr_limits)  */
	global $amr_options;
	global $amr_limits;
	global $amr_listtype;
	global $amrW;
	global $amr_doing_icallist; /* use to prevent the eventinfo shortcode echoing data to the screen when in ical event list mode */
	
	$amr_doing_icallist = true;
	
	if (!empty($amrW)) $w = 'w';
	else $w = '';
	
//	if (empty($criteria)) return ('');
	if (!empty($criteria['urls'])) {
		foreach ($criteria['urls'] as $i => $url) {
			$icals[$i] = process_icalurl($url);
			if (!(is_array($icals[$i]))) unset ($icals[$i]);
		}
//		unset ($criteria['urls']);
	}
	if ((empty($criteria['urls'])) OR (count($criteria) > 1)) { /* if there were no urls, or if there is some other criteria to also select posts by */

//	if (!empty($criteria)) { /* then we have some query posts selection to do - using other plugin */
	
		if (function_exists('amr_ical_from_posts')) {
			if (ICAL_EVENTS_DEBUG) echo '<h3>Get events from posts </h3>';
			$icals2 = amr_ical_from_posts($criteria);
			if (ICAL_EVENTS_DEBUG) { echo '<br>Got x events from posts '.count($icals2); var_dump($icals2);}
			
			if (isset($icals) ) $icals = array_merge($icals, $icals2 );
			else $icals = $icals2;
		}
		else suggest_other_icalplugin (__('Events from Posts','amr-ical-events-list' ));
	}
		
	/* now we have potentially  a bunch of calendars in the ical array, each with properties and items */
	
	/* Merge then constrain  by options */
	$components = array();  /* all components actually, not just events */
	if (isset ($icals) and is_array($icals)) {	/* if we have some parse data */
		foreach ($icals as $j => $ical) { /* for each  Ics file within an ICal spec*/
			if ((!isset($amr_options[$amr_listtype]['component'])) or (count($amr_options[$amr_listtype]['component']) < 1)) 
				_e('No ical components requested for display','amr-ical-events-list');
			else {	
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
		}
/* amr here is the main calling code  *** */	
		$calprophtml =  amr_list_properties ($icals);		
		if (isset($calprophtml) and (!(empty($calprophtml))) and (!($calprophtml === ''))) {
			$calprophtml  = '<table id="'.$w.'calprop'.$icalno.'" class="'.$w.'icalprop">'.$calprophtml.'</table>'.AMR_NL;
		}  			
		$thecal = $calprophtml;
		
		if (!empty($amr_limits)) $components = amr_constrain_components($components, $amr_limits['start'], $amr_limits['end'], $amr_limits ['events']);	

		if (count($components) === 0) {
			if (isset($amr_options['noeventsmessage'])) 
				$thecal .=  '<a style="cursor:help;" href="" title="'.amr_echo_parameters().'"> '.$amr_options['noeventsmessage'].'</a>';
		}
		else {
				$thecal .= 
				AMR_NL.'<table id="'.$w.'compprop'.$icalno.'" class="'.$w.'ical">'				
				.amr_list_events($components )
				.AMR_NL.'</table>'.AMR_NL;
			}
		}
		else $thecal = '';	/* the urls were not valid or some other error ocurred, for this spec, we have nothing to print */

/* amr  end of core calling code --- */		
		return ($thecal);
	} 

/* -------------------------------------------------------------------------*/
function amr_get_params ($attributes=array()) {
/*  We are passed the shortcode attributes, check them, get what we can there, then check for passed parameters (form or query string ) 
   Anything unset we will get from the default settings for that listtype.
   The defaults list type is 1.
    we could even have a default url!   
*/
	global $amr_limits;
	global $amr_listtype;
	global $amr_options;  
	global $amr_formats;  
	global $amr_globaltz;
	
	$amr_options = amr_getset_options();
		
	if (isset($_REQUEST['listtype'])) $amr_listtype = $_REQUEST['listtype'];
	else if (isset($attributes['listtype'])) $amr_listtype = $attributes['listtype'];
	else if (!(isset($amr_listtype))) $amr_listtype = 1;
	
	$amr_limits = $amr_options[$amr_listtype]['limit']; /* get the default limits */
	
	$defaults = array( /* defaults array for shortcode , want them all here so we can get urls out separately  */
	'listtype' => $amr_listtype,
   	'startoffset' => '0',
 	'hoursoffset' => '0',
	'monthsoffset' => '0',
	'eventsoffset'=> '0',
	'start' => '', /* date('Ymd'), */
	'days' => $amr_options[$amr_listtype]['limit']['days'],
	'events' => $amr_options[$amr_listtype]['limit']['events'],
	'tz' => '',
	'months' => '0',      /* if we have months, start at begin of month and ignore days? */
	'hours' => '0'
	);

	$atts = shortcode_atts( $defaults, $attributes ) ;  /*  get the parameters we want out of the attributes */

	/* check if we want to overwrite the wordpress timezone */
	if (isset($_REQUEST['tz'])) $amr_globaltz =  timezone_open($_REQUEST['tz']);
	else if ((isset($atts['tz'])) and (!(empty($atts['tz'])))) $amr_globaltz = timezone_open ($atts['tz']);
	If (isset($_REQUEST['tzdebug'])) {
		echo '<h4>Plugin Timezone:'.timezone_name_get($amr_globaltz);
		echo ', current offset is'.$amr_globaltz->getOffset(date_create('now',timezone_open('UTC')))/(60*60).'</h4>';
		}		

	$pos_int_options = array("options"=> array("min_range"=>1, "max_range"=>1000));		
	$neg_int_options = array("options"=> array("min_range"=>-1000, "max_range"=>1000));
	/* check non url parameters  */
	foreach ($defaults as $i => $a) { 

		if ($i === 'start') {
			if (isset($_REQUEST[$i] )) $start = $_REQUEST[$i];
			else $start = $atts[$i];
			if (!(is_object($start))) {
				if (checkdate(substr($start,4,2), /* month */
						substr($start,6,2), /* day*/
						substr($start,0,4)) /* year */ )
						$amr_limits['start'] = date_create($start);					
				else $amr_limits['start'] = date_create();	
			}
			else $amr_limits['start'] = date_create($start);		
		 /*  else all is okay - we have default date of now */
		}
		else if  (($i === 'days') OR ($i==='events') or ($i === 'months')) {
			if (isset($_REQUEST[$i])) {
				if (function_exists ('filter_var') and (filter_var($_REQUEST[$i], FILTER_VALIDATE_INT, $pos_int_options))) 
					$amr_limits[$i] = $_REQUEST[$i];
				else $amr_limits[$i] = $atts[$i];
			}
			else $amr_limits[$i] = $atts[$i];
		}
		else if (!(($i === 'tz') OR ($i === 'listtype') )) { /* then it's a number  like days, events, startoffset, houroffset*/
		
			
		
			if (isset($_REQUEST[$i])) {		
				if (filter_var($_REQUEST[$i], FILTER_VALIDATE_INT, $neg_int_options)) $amr_limits[$i] = $_REQUEST[$i];
				else $amr_limits[$i] = $atts[$i];
			}
			else $amr_limits[$i] = $atts[$i];
		};
	}
	
	/* check for urls that are either passed by query or form, or are in the shortcode with a number or not  */
	if (is_null($attributes)) $others = $atts;
	else $others = array_diff_assoc ($attributes, $atts);  /*  get the urls and other out of the shortcodes */

	foreach ($others as $i => $v) {
			if (is_numeric ($i)) {
				if (substr($v, 0 ,1) == ':') { /* attempt to maintain old filter compatibity */ 
					$others['urls'][$i] = substr ($v, 1);
				} 
				$v = (str_ireplace('webcal://', 'http://',$v));
				if ((function_exists('filter_var')) and (!filter_var($v, FILTER_VALIDATE_URL))) {
					echo '<h2>'.sprintf(__('Invalid Ical URL %s','amr-ical-events-list'), $v).'</h2>';
				}
				else $others['urls'][$i] = $v;	
				unset ($others[$i]);
				}
			else { /* it's not a url, it's a query selection criteria */			}
		}
	
	if (isset($_REQUEST['ics'])) {
		$spec = (str_ireplace('webcal://', 'http://',$_REQUEST['ics']));
		if ((function_exists('filter_var')) and (!filter_var($spec, FILTER_VALIDATE_URL))) {
				echo '<h2>'.sprintf(__('Invalid Ical URL passed in query string %s','amr-ical-events-list'), $spec).'</h2>';
			}
			else $others['urls'] = array($spec); /* replace the urls with the one that is passed */
		}	
	
	$amr_formats = $amr_options[$amr_listtype]['format'];
	
	$daysoffset = (int)($amr_limits['startoffset']);


	if ($daysoffset === 0) {} /* the unst only unsets locally and for some reason the globals unset is not working */
	else if ($daysoffset > 0) $daysoffset = '+'.(string)$daysoffset.' days';
	else $daysoffset = (string)$daysoffset.' days';
	
	date_modify($amr_limits['start'],$daysoffset) ;	
	
	$hrsoffset = (int)($amr_limits['hoursoffset']);
	if ($hrsoffset === 0) unset ($amr_limits['hoursoffset']);
	else if ($hrsoffset > 0) $hrsoffset = '+'.(string)$hrsoffset.' hours';	
	else $hrsoffset = (string)$hrsoffset.' hours';	
	date_modify($amr_limits['start'],$hrsoffset) ; /*** as per request from jd  */
	
	$mthsoffset = (int)($amr_limits['monthsoffset']);
	if ($mthsoffset >= 0) $mthsoffset = '+'.(string)$mthsoffset.' months';	
	else $mthsoffset = (string)$mthsoffset.' months';	
	date_modify($amr_limits['start'],$mthsoffset) ; 

	if (isset($amr_limits['hours'])) {
		if ($amr_limits['hours']>0) {  /* then set the time to the beginning of the day */
			date_time_set ($amr_limits['start'],$amr_limits['start']->format('G'),0,0);	/* set the time to beginning of the hour */			
			unset ($amr_limits['days']);
			unset ($amr_limits['months']);
		}
	}		
	if (isset($amr_limits['months'])) {
		if ($amr_limits['months']>0) {  /* then set the date to the beginning of the month */
			$days = (int) $amr_limits['start']->format('d');
			if ($days > 1) 	$amr_limits['start']->modify('-'.($days-1).' days');
			date_time_set ($amr_limits['start'],0,0,0);
			unset ($amr_limits['days']);
		}
		else unset ($amr_limits['months']);
	}

	if (isset($amr_limits['days'])) {
		if ($amr_limits['days']>0) {  /* then set the time to the beginning of the day */
			date_time_set ($amr_limits['start'],0,0,0);
		}
	}

	$amr_limits['end'] = clone ($amr_limits['start']);
	if (isset($amr_limits['hours'])) 
		date_modify($amr_limits['end'],'+'.($amr_limits['hours']).' hours') ;			
	if (isset($amr_limits['months'])) 
		date_modify($amr_limits['end'],'+'.($amr_limits['months']).' months') ;	
	if (isset($amr_limits['days'])) 
		date_modify($amr_limits['end'],'+'.($amr_limits['days']).' days') ;	
	date_modify($amr_limits['end'],'-1 second') ; /* so that we do not include events starting in the next time period */

	If (ICAL_EVENTS_DEBUG) {echo '<hr>'.amr_echo_parameters();}

	return ($others);
}

/* -------------------------------------------------------------------------*/
function amr_do_ical_shortcode ($atts, $content = null) {
// This is the main function.  It replaces [iCal:URL]'s with events. Each as a separate list 
/* Allow multiple urls and only one listtype */
/*  merge atts with this array, so we will have a default list */
	global $amr_icalno; /* used to give each ical  table a unique id on a page or post */

	$criteria =	amr_get_params ($atts);  /* strip out and set any other attstributes  - they will set the limits table */
	/* separate out the other possible variables like list type, then just have the urls */

	if (!(isset($amr_icalno))) $amr_icalno = 0;
	else $amr_icalno= $amr_icalno + 1;
	$content = amr_process_icalspec($criteria, $amr_icalno);
		
  return ($content);
}
/* -------------------------------------------------------------------------------------------------------------*/
 function amr_ical_load_text() {
/**
 * Internationalization functionality
 */
/* $textdomain, path from abspath, path from plugins folder */
	load_plugin_textdomain('amr-ical-events-list', false , dirname(plugin_basename(__FILE__)).'/lang' );
//	load_textdomain('amr-ical-events-list',false,'/lang');

}
/* -------------------------------------------------------------------------------------------------------------*/

	/**
	Adds a link directly to the settings page from the plugin page
	*/
function amr_plugin_action($links, $file) {
	/* create link */
	if ( $file == AMR_BASENAME ) {
		array_unshift($links,'<a href="options-general.php?page=manage_amr_ical">'. __('Settings','amr-ical-events-list').'</a>' );
	}
 
	return $links;
	} // end plugin_action()
 

/* -------------------------------------------------------------------------------------------------------------*/
function amr_ical_widget_init() {
//    register_sidebar_widget("AmR iCal Widget", "amr_ical_list_widget");
//    register_widget_control("AmR iCal Widget", "amr_ical_list_widget_control");
	register_widget('amr_ical_widget');
}
/* ------------------------------------------------------------------------------------------------ */
 function AmRical_add_scripts() {
 	wp_enqueue_script('jquery');
	wp_enqueue_script('jquery-ui-core');
}
/* ------------------------------------------------------------------------------------------------ */
 function AmRical_add_adminstyle() {
 
	if (stristr ($_SERVER['QUERY_STRING'],'manage_amr_ical')) {
      $myStyleUrl = WP_PLUGIN_URL . '/amr-ical-events-list/icaladmin.css';
      $myStyleFile = WP_PLUGIN_DIR . '/amr-ical-events-list/icaladmin.css';
      if ( file_exists($myStyleFile) ) {
            wp_register_style('amricaladmin', $myStyleUrl);
            wp_enqueue_style( 'amricaladmin');
        }
	}
}
/* ------------------------------------------------------------------------------------------------------ */
function amr_ical_exception_handler($exception) {
  echo __("Uncaught exception: ") , $exception->getMessage(), "\n";
  _e('<br /><br />An error in the input data may prevent correct display of this page.  Please advise the administrator as soon as possible.');
}

	set_exception_handler('amr_ical_exception_handler');
//	add_action( 'load_textdomain', 'amr-ical-events-list', '/lang/amr-ical-events-list/'.WPLANG );

	if (is_admin() )	{
		add_action('admin_init', 'AmRical_add_adminstyle');
		add_action('admin_menu', 'AmRIcal_add_options_panel');	
		add_action('admin_print_scripts', 'AmRical_add_scripts');
	}
	else //	add_action('wp_head',  'amr_ical_events_style');
		add_action('wp_print_styles', 'amr_ical_events_style');
		
//	add_action('plugins_loaded', 'amr_ical_widget_init');	
	add_action('widgets_init', 'amr_ical_widget_init');	
	add_action('plugins_loaded', 'amr_ical_load_text' );	

//	add_action( 'admin_init', 'amr_ical_load_text' );	
	add_filter('plugin_action_links', 'amr_plugin_action', 8, 2);	
	add_shortcode('iCal', 'amr_do_ical_shortcode');
	

	
?>