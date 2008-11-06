<?php
//ini_set('display_errors', 1);
//error_reporting(E_ALL);
/*
Plugin Name: AmR iCal Events List
Version: 2.2 Alpha
Plugin URI: http://webdesign.anmari.com/web-tools/plugins-and-widgets/ical-events-list/
Description: Display list of events from iCal sources.  <a href="options-general.php?page=manage_amr_ical">Manage Settings Page</a> and  <a href="widgets.php">Manage Widget</a> or <a href="page-new.php">Write Calendar Page</a>

Features:
- Control over contents and styling from the admin menu's.
- Lots of css tags for innovative styling
- minimalist default css or use your own
- a separate widget list of events available

The following were reviewed and snippets of codes or ideas reused. The majority of the code is now either original or significantly reworked.
A major change was to move to using the PHP 5.2 datetime and timezone class.  This meant that imezone functionality was much simpler and a lot of the code could be simplified.

[import_ical.php](http://cvs.sourceforge.net/viewcvs.py/webcalendar/webcalendar/import_ical.php?rev=HEAD) from the [WebCalendar](http://sourceforge.net/projects/webcalendar/) project. 
[dwc's plugin] (http://dev.webadmin.ufl.edu/~dwc/2005/03/10/ical-events-plugin/)
[PhpIcalendar] (http://phpicalendar.net/)
[Horde] (http://www.horde.org/kronolith/) 

*/

/*  these are  globals that we do not want easily changed -others are in the config file */
global $amr_options;
global $amrW;  /* set to W if running as widget, so that css id's will be different */
$amrW = '';

require_once('amr-ical-config.php');
require_once('ical-list-admin.php');
require_once('import_ical.php');
require_once('amr_rrule.php');
/*--------------------------------------------------------------------------------*/
function amr_echo_style_contents ($ical_style_file) {
/* This function is included as an option of you really want the style code to be echoed directly - lookos messier though */
		$contents = file_get_contents($ical_style_file); 
		if ($contents) {
			echo "\n".'<style type="text/css"> <!--For amrical -using css file '.$ical_style_file.' -->';
			echo $contents             // printing the file content of the file
			.'</style>'."\n";
			}
		else echo '<!-- Error getting contents of Ical Events List style:'.$ical_style_file.' -->'; 
		
}
/*--------------------------------------------------------------------------------*/
function amr_ical_events_style()  /* check if the default table style is there */
{
	global $amr_options;
	global $amr_listtype;
		
	if (!isset($amr_options)) $amr_options = amr_getset_options (false);	
	
	/* amr we need to parse the page content to get the list type requested, else will always use 1 .  */
	if (!(isset($amr_listtype))) 
	{$amr_listtype = 1; echo '<!--AmR iCal list type not set, using 1 -->'.AMR_NL;}
	/* should this be a global rather than refetching here?   Also should the style be specified in the page rather using  No?*/

	$ical_style_file = (empty($amr_options[$amr_listtype]['general']['Css URL'])) 
		? null : $amr_options[$amr_listtype]['general']['Css URL'];
	/* then not using their own style */
	if (isset($ical_style_file))  { 
		echo '<link rel="stylesheet" href="'.$ical_style_file.'" type="text/css" media="screen, print" />';
	// amr_echo_style_contents ($ical_style_file);
	}
	else echo '<!-- Ical Events List style not set :'.$amr_listtype.' -->'; 
}
/* --------------------------------------------------  sort through the options that define what to display in what column, in what sequence, delete the non display and sort column and sequenc  */
function prepare_order_and_sequence ($orderspec)
{
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
function check_hyperlink($text) 
{  /* checks text for links and converts them to html code hyperlinks */



    // match protocol://address/path/
    $text = ereg_replace("[a-zA-Z]+://([.]?[a-zA-Z0-9_/-?#&%])*", "<a href=\"\\0\">\\0</a>", $text);
    
    // match www.something
    $text = ereg_replace("(^| )(www([.]?[a-zA-Z0-9_/-?&#%])*)", "\\1<a href=\"http://\\2\">\\2</a>", $text);
	
	// could not figure out how to prevent it picking up the br's too, so fix afterwards
	$text = str_replace ('<br"', '"', $text);
	$text = str_replace ('</a> />', '</a><br />', $text);	

    return $text;
}
/* --------------------------------------------------  */
	function amr_derive_calprop_further (&$p) {

		if (isset ($p['icsurl']))  {/* must be!! */

			if (isset ($p['X-WR-CALNAME'])) {
				if (ICAL_EVENTS_DEBUG)	{echo '<br>cal prop name= '; print_r ($p['X-WR-CALNAME']); }	
				$p['X-WR-CALNAME'] = '<a href="'.$p['icsurl'].'">'.$p['X-WR-CALNAME'].'</a>';		
			}
		}
		return ($p);
	}
/* --------------------------------------------------  */
function amr_list_properties($icals)
{  /* List the calendar properties if requested in options  */
	global $amr_options; 
	global $amr_listtype;

	$order = prepare_order_and_sequence  ($amr_options[$amr_listtype]['calprop']);

	if (!($order)) { 
		if (ICAL_EVENTS_DEBUG)	{ echo '<br>No calendar properties requested';}
		return; 
		}
	if (ICAL_EVENTS_DEBUG) {echo '<h2>Listing properties using listtype:'.$amr_listtype.' </h2>';}
	foreach ($icals as $i => &$p)
	{ /* go through the options list and list the properties */
	
		amr_derive_calprop_further ($p);

		$prevcol = $col = ''; 
		$cprop = '';   
		foreach ($order as $k => $v)  /* for each column, */
		{	
			if (ICAL_EVENTS_DEBUG) echo '<br>Looking for '.$k;
			$col = $v['Column'];
			if (!($col === $prevcol)) /* then starting new col */
			{	if (!($prevcol === '')) { $cprop .= AMR_NL.$tb.'</ul> <!-- end of amrcol -->';}  /* end prev column */
				$cprop .= AMR_NL.$tb.'<ul class="amrcol amrcol'.$col.'">';  /* start next column */
				$prevcol = $col;
			}			
			if (isset ($icals[$i][$k])) /*only take the fields that are specified in options  */
			{	
				if (ICAL_EVENTS_DEBUG) echo ' -we got '.$icals[$i][$k];			
				$cprop .= AMR_NL.$tb.'<li class="'.strtolower($k).'">'.$v['Before']
				.format_value($icals[$i][$k], $k)
				.$v['After'].'</li>';
			}
		}
		if (!($cprop === '')) /* if there were some calendar property details*/
			{$html .= '<li class="cal">'.AMR_NL.'<ul class="amrrow"><li class="amrrow">'
					.$cprop.AMR_NL.$tb.'</ul> <!-- end of amrcol -->'.AMR_NL
					.'</li></ul><!-- end of amrrow -->'.AMR_NL.'</li>';  		}
	}
	if (!($html === '')) {
		$html  = 	AMR_NL.'<ul class="'.$amrW.'calprop">'.$html.'</ul>';
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
	
	if (ICAL_EVENTS_DEBUG) { echo '<br>Duration calc w='.$w.' d='.$d.' h='.$h. 'm='. $m; print_r ($duarray); }
	
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
				$d1a = new DateTime();
				$d1a = clone $d1;
				date_modify ($d1a,'next day');
				if ($d1a = $d2) return (true); 
			}
			return (false);
		}

		/*
		 * Return true iff the two specified times fall on the same day.
		 */
		function amr_is_same_day($d1, $d2) {
		return ($d1->format('Ymd') === $d2->format('Ymd'));
		}
		/*
			
/* --------------------------------------------------------------------------------------*/
	 /* Return true if the first date is earlier than the second date
	 */
	function amr_is_before($d1, $d2) {			
		if ($d1 < $d2 ) {
//			if (ICAL_EVENTS_DEBUG) echo '<br>'.$d1->format('c').' less than '.$d2->format('c');
			return (true);
		}
		else {
//			if (ICAL_EVENTS_DEBUG) echo '<br>'.$d1->format('c').' greater than or equal '.$d2->format('c');
			return (false);
		}	
	}
/* --------------------------------------------------------- */	
	function amr_format_duration ($arr) {
	/* receive an array of hours, min, sec */
	$d = '';
	$i = count($arr);
	if ($i > 1) {
		$sep = ', ';
	}
	else $sep = '';
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
	if (isset ($arr['days'] )) {
		$d .= sprintf (__ngettext ("%u day", "%u days", $arr['days']), $arr['days']);
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
	
	return($d);
	}
/* --------------------------------------------------------- */
function format_value ($content, $k)
/*  Format each Ical value for our presentation purposes 

Note: Google does toss away the html when editing the text, but it is there if you add but don't edit.

what about all day?
*/
{
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
				
				case 'X-WR-TIMEZONE':
				case 'TZID':
					{ /* amr  need to add code to reformat the timezone as per admin entry.  Also only show if timezone different ? */
							return (timezone_name_get($content));
					}	
				default: 	/* should not be any */
					return (amr_format_date ($amr_formats['DateTime'], $content)); 	
			}	
	}
	else if (is_array($content)) {
		if ($k === 'DURATION') { return( amr_format_duration ($content)); }
		else {  /* need to list the items separately eg: multiple comments or descriptions  */
			$c = '<ul class="multiprop">';
			foreach ($content as $i => $v) {
//			if (ICAL_EVENTS_DEBUG) {echo '<br>Multiprop to format: '; var_dump($v);}
				$c .= '<li>'. format_value ($v, $k) . '</li>';
			}
			$c .= '</ul>';
			return ($c);	
		}
	}
	else if ($content === '') return ($content);
	else {
		switch ($k){
			case 'COMMENT':	
			case 'DESCRIPTION': return ( check_hyperlink(nl2br2($content)));
			case 'SUMMARY': return( nl2br2($content)); /* avoid hyperlink as we may have added url already */
			case 'LOCATION': 
				return( check_hyperlink(nl2br2($content)));
			case 'map':	
				return (amr_ical_showmap ($content, __('map','amr-ical-events-list'))); 
			case 'URL': /* assume valid URL, should not need to validate here, then format it as such */
					return( '<a href="'.$content.'">'.__('Event Link', 'amr-ical-events-list').'</a>');
			case 'icsurl': /* assume valid URL, should not need to validate here, then format it as such */
					return( '<a class="icalsubscribe" href="'.$content.'">'.__('Subscribe', 'amr-ical-events-list').'</a>');
				break;	

			default: 
				return ($content);
//		$content = format_date ( $amr_formats['Day'], $content);break;			 
		}
	}
	/* Convert any newlines to html breaks */
	return (str_replace("\n", "<br />", $content));
	
}
/* ------------------------------------------------------------------------------------*/

function amr_derive_event_further (&$e)
{
	/* The RFC 2445 values will have been set by the import process.
		Eventdate will be set by the recur check process
		Now we need to derive, set or modify  any other values for our output requirements
	*/
	
	if (isset ($e['Untimed'])) {  
		if (ICAL_EVENTS_DEBUG) {echo '<br> Untimed!'; }
		unset ($e['DURATION']);
		unset ($e['StartTime']);
		unset ($e['EndTime']);
		$e['Classes'] .= ' untimed'; 
		
	}
	else {
		$e['StartTime'] = $e['EventDate']; /* will format to time, later keep date  for max flex */	
		
		if (!isset ($e['DURATION'])) {  /* an array of the duration values */
			if (isset ($e['DTEND'])) {
				if (ICAL_EVENTS_DEBUG) {echo '<br> DTEND = '.$e['DTEND']->format('c').' DTstart = '.$e['DTSTART']->format('c'); }		
				$e['DURATION'] = $d = amr_calc_duration ( $e['DTSTART'], $e['DTEND']);		
				$e['EndDate'] = new DateTime();
				$e['EndDate'] = clone $e['EventDate'];
				if ($d['sign'] === '-') $dmod = '-';
				else $dmod = '+';
				foreach ($d as $i => $v)  {  /* the duration array must be in the right order */
					if (!($i === 'sign')) { $dmod .= $v.' '.$i ;}
				}
				date_modify ($e['EndDate'], $dmod );
			}
		}
	if (ICAL_EVENTS_DEBUG) {echo '<br> Duration = '; print_r($e['DURATION']); }
	}
	
	if (isset ($e['EndDate']) ) {
		if (amr_is_all_day($e['EventDate'], $e['EndDate'])) {	
			unset ($e['StartTime']);
//			= __('All day', 'amr-ical-events-list');
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
	}
		
	if (isset ($e['URL'])) {  /* THis assumes that there is a one-to-one relationship between summary and url's which may not always be true */
		foreach ($e['SUMMARY'] as $i => $s) {
			$e['SUMMARY'][$i] = '<a href="'.$e['URL'][$i].'">'.$s.'</a>';
		}
	}
	
	/* Noew get some styling possibilities */
	if (isset ($e['RRULE']) or (isset ($e['RRULE']))) {
		$e['Classes'] .= ' recurring'; 
	}
	if (isset ($e['Status']) ){
		$e['Classes'] .= ' '.__($e['Status']);  /* would have values like 'CONFIRMED'*/ 
	}
}

/* --------------------------------------------------  */

function list_events($events, $g=null)
{
	global $amr_options; 
	global $amr_listtype;
	global $amrW;

	/* The component properties have an additional structuring level that we need to remove */
	if (!isset($amr_options)) echo '<br />Options not set';
	else if (!isset($amr_options[$amr_listtype])) echo '<br>listtype Not set: '.$listtype.' In Option array '.var_dump($amr_options);
	else if (!isset($amr_options[$amr_listtype]['compprop'] )) echo '<br>Compprop not set in Option array '.var_dump($amr_options[$listyype]);
	else 
	{	
			/* flatten the array of component property options  */
		foreach ($amr_options[$amr_listtype]['compprop'] as $k => $v)
			{ 	foreach ($v as $i=>$j) 	{ $order[$i] = $j; 	}; 	}
		$order = prepare_order_and_sequence ($order);
		
		/* check for groupings and compress these to requested groupings only */
		if (isset ($amr_options[$amr_listtype]['grouping'])) 
		{  	foreach (($amr_options[$amr_listtype]['grouping']) as $i => $v)
				{	if ($v) { $g[$i] = $v; }			}
				if ($g) {foreach ($g as $gi=>$v) 
						{$new[$gi] = $old[$gi] = '';}} /* initialise group change trackers */		
		}
		$html = AMR_NL.'<ul class="'.$amrW.'compprop"><li>';
	
		$alt= false;
		foreach ($events as $i => $e)  /* for each event, loop through the properties and see if we should display */
		{	 	
			if (ICAL_EVENTS_DEBUG) {echo '<br> DTstart = '.$e['DTSTART']->format('c'); }		
			amr_derive_event_further ($e);

			$eprop = ''; /*  each event on a new list */
			$prevcol = $col = ''; /* reset where we are with columns */		
			foreach ($order as $k => $v)  /* ie for one event, check how to order the bits */
			{	/* Now check if we should print the component or not */
				if ((isset ($e[$k]))  && (!empty($e[$k])))
				{
					$col = $v['Column']; 
					if (!($col === $prevcol))  /* if new column, then new cell , if not the first col, then end the prev col */
					{	if (!($prevcol === ''))  {	$eprop .= '</ul> <!-- end of amrcol-->'; }
						$eprop .= AMR_NL.'<ul class="amrcol'.$col.' amrcol">';/* each column in a cell or list */
						$prevcol = $col;
					}
					
					$eprop .= '<li class="'.$k.'">'.$v['Before']
						. format_value($e[$k], $k).$v['After'].'</li>';  /* amr any special formatiing here */
				}
			}
			if (!($eprop === '')) /* ------------------------------- if we have some event data to list  */
			{	/* then finish off the event or row, save till we know whether to do group change heading  */
				
				$eprop = AMR_NL.'<ul class="amrrow '.($alt ? 'alt' : '').'">'
					.'<li class="amrrow">'
					.$eprop.'</ul><!-- end of amrcol -->'.AMR_NL.'</li></ul><!-- end of amrrow -->';
					
				if ($alt) $alt=false; else $alt=true; 	
				/* -------------------------- Check for a grouping change, need to end last group, if there was one and start another */
				$change = '';
				if ($g) 
				{	foreach ($g as $gi=>$v) 
					{	
						$grouping = format_grouping($gi, $e['EventDate']) ;

						$new[$gi] = str_replace(array(' ',','),array('',''), $grouping);  /* amr **** need to fix this */
						if (!($new[$gi] == $old[$gi]))  /* if there is a grouping change */
						{	/* end the prev group and start the new group */
		
							if (!($old[$gi] == '')) 
							{ /* not first such group, so end previous,   */
								$change = AMR_NL.'</li></ul><!-- end of '.$gi.'-->';
						
							}
							/* Then write the heading for the group */
							$id = str_replace(' ','','"'.$gi.$new[$gi].'"');
							$change .= 	'<ul class="group '.$gi.'"><li id='.$id
							.' class="group '.$gi. '"><h3 class="group '.$gi. '">'.$grouping.'</h3>';
							$old[$gi] = $new[$gi];							
						}
					} 					
				}				
				$html .= $change.AMR_NL.$eprop;	
			}	
					
		} /* Close out each of the groupings */
		if ($g) {foreach ($g as $gi=>$v) $html .= AMR_NL.'</li></ul><!-- one for '.$gi.'-->';}
		$html .= AMR_NL.'</li></ul><!-- end of compprop -->'.AMR_NL;
	}
return ($html);
}

/* -------------------------------------------------------------------------*/
function amr_getset_listtype($txt) {
/* allow for the format name to be entered as well instead of the number */
global $amr_listtype;
global $amr_options;
global $amr_formats;  /* specify the formats to be used */
global $amr_limits;

		if (isset($txt)) 
			{	/* allow upper or lower iCal parameters */
				parse_str(strtolower($txt), $args);
				if (isset ($args['listtype'])) $amr_listtype = $args['listtype'];
				else $amr_listtype = 1;
			}
		else $amr_listtype = 1;
		
		if (isset ($amr_options[$amr_listtype]['format'])) $amr_formats = $amr_options[$amr_listtype]['format'];
		else foreach ($amr_options as $k => $i) {
				if ($amr_options[$i]['general']['Name'] === $amr_listtype) {
					$amr_listtype = $i;
					$amr_formats = $amr_options[$amr_listtype]['format'];
				}
			}
		if (ICAL_EVENTS_DEBUG) {
			echo '<br>List type: '. $amr_listtype.'  Name: ' 
			. $amr_options[$amr_listtype]['general']['Name'];}
			
		foreach ($amr_options[$amr_listtype]['limit'] as $i => $l){
			$amr_limits[$i] = $l;
		}
		
		/* amr - could later add query params maybe  */
		$amr_limits['start'] = date_create();
		
		if (isset ($amr_options[$amr_listtype]['limit']['Days'])){
			$amr_limits['end'] = new DateTime();
			$amr_limits['end'] = clone $amr_limits['start'];
			date_modify($amr_limits['end'],'+ '.($amr_options[$amr_listtype]['limit']['Days']).' days') ;}
			
		if (ICAL_EVENTS_DEBUG) 
		echo '<br>Limits for list: startdatetime: '.$amr_limits['start']->format('r')
		     .'  enddatetime:'.$amr_limits['end']->format('r')
			 .'  number:'.$amr_limits['Events'] . '<br>';
}

/* ------------------------------------------------------------------------------------------------------ */

	function amr_getset_options ($reset=false)
	/* get the options from wordpress if in wordpress
	if no options, then set defaults */
	{
		global $amr_formats;
		global $amr_options;
		$update = false;  /* used to indicate if we need to do an update eg: for upgrade, in case user does not save */
		$amr_ical_in_DB = false;
			

		if (function_exists ('get_option') ) {
			if ($amr_options = get_option('AmRiCalEventList')) {
				$amr_ical_in_DB = true;
			}
			else $amr_ical_in_DB = false;
		}
		else 
			{/* so no wordpress functions  - do we need anything here - I don't think so */	}
	
			
		/* Now check if we need to setup any more defaults or is first time  */	
		if (!(isset($amr_options['no_types'])))  
			{ 	$amr_options['no_types'] = 6;
				$amr_options[1] = new_listtype();
				$update = true;   
			}
		for ($i = 1; $i <= $amr_options['no_types']; $i++)   /* setup some list type defaults if we have empty list type arrays */
		{	/* if we are reseting or the options are not set */
			if ($reset )   
			{	
				$amr_options[$i] = new_listtype();
				$amr_options[$i] = customise_listtype( $i);
				$update = true;   
			}	
			else 
			{	
				if (!(isset($amr_options[$i])))
				{	$update = true;   
					if ($i == '1') {	
						$amr_options[$i] = new_listtype();
					}
					else {	
						$amr_options[$i] = $amr_options['1'];
					}
					$amr_options[$i] = customise_listtype( $i);
				}
				else {  /* yes the options are set, but for upgrade purposes, we will check if anything is missing like col headings */
					if (!(isset($amr_options[$i]['heading']))) {  /* added in version 2, so may not exist */
							$amr_options[$i]['heading'] = $amr_colheading;
							$update = true;
					}
				}
			}

			$amr_colnum['calprop'][$i] = 1;
			$amr_colnum['compprop'][$i] = 1;
			foreach ($amr_options[$i]['calprop'] as $j => $l) {
//				if (ICAL_EVENTS_DEBUG) 	echo '<br>'.$i.'Next col='.$l['Column'].'No of calprop cols = '. $amr_colnum['calprop'][$i]; 
				if ($l['Column'] > $amr_options[$i]['colnum']['calprop']) {
					$amr_options[$i]['colnum']['calprop'] = $l['Column'];
				}	
			}
			foreach ($amr_options[$i]['compprop'] as $j => $l) {
				foreach ($l as $j2 => $l2) {
//					if (ICAL_EVENTS_DEBUG) 	echo '<br>'.$i.'Next col='.$l2['Column'].'No of coprop cols = '. $amr_colnum['compprop'][$i]; 

					if ($l2['Column'] > $amr_options[$i]['colnum']['compprop']) {
						$amr_options[$i]['colnum']['compprop'] = $l2['Column'];
					}	
				}
			}
		}
		

		if ($amr_ical_in_DB) {  
			if ($reset) {	/* The we are requested to reset the options, so delete and update */
				delete_option('AmRiCalEventList'); 
				add_option(  'AmRiCalEventList', $amr_options);
			}
			else {			
				if ($update) {
					update_option('AmRiCalEventList', $amr_options);
					}
			}		
		}
		else { /* It is not in the DB so just add the option */
			add_option(  'AmRiCalEventList', $amr_options) ;
		}

		return ($amr_options);
	}

/* -------------------------------------------------------------------------------------------*/
function format_grouping ($grouping, $datestamp)
/* check what the format for the grouping should be, call functions as necessary*/
{
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
		if (function_exists($func) )
			return call_user_func($func,$datestamp);
		else 
		   return ('No function defined for Date Grouping '.$grouping);
	}
}
/* -------------------------------------------------------------------------------------------*/
function amr_format_date( $format, $datestamp)
{ /* want a  integer timestamp and a date object  */
	// echo ' format = '.$format. var_dump($datestamp);

	if (is_object($datestamp))
		{	
			$dateInt = $datestamp->format('U');
			$dateO = $datestamp;
		}
	else if (is_integer ($datestamp))
			{ 
			$dateInt = $datestamp;
			$dateO = new DateTime(strftime('%Y-%m-%d %T',$datestamp));
			}
	else /* must be an ical date */
		{	
			$dateInt = icaldate_to_timestamp ($datestamp);
			$dateO = new DateTime (strftime('%Y-%m-%d %T',$dateInt));
		}
	if (stristr($format, '%') ) 
	{
		return (strftime($format, $dateInt ));
	}
	else 
		{
			return($dateO->format($format));
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
		
//		if (ICAL_EVENTS_DEBUG) {echo '<br>Comparing '.$eventdate->format('c').' '
//					.$astart->format('c'). ' '. $aend->format('c');}
		
		if (($eventdate <= $aend) and 
			($eventdate >= $astart)) return ( true);
		else return (false);	
		}

/* ------------------------------------------------------------------------------------*/
		/* Process an array of datetime objects and remove duplicates 
		 */
		function amr_arrayobj_unique (&$arr) {
		/* 	Duplicates can arise from edit's of ical events - these will have different sequence numbers and possibly differnet details - delete one with lower sequence,
			Also there is the possobility of complex recurring rules generating duplicates - these will have same sequence no's and should have same details - delete one    
		*/
			if (ICAL_EVENTS_DEBUG) { 
				echo '<h3>Have '.count($arr).' Check for Duplicates due to single instance edits of a recurring rule, or complex rules</h3>' ;}
		
			$l = count ($arr);
			
			foreach ($arr as $i => $e) {
				$j = $i+1;
				while ($j < $l) {
					
//					if (ICAL_EVENTS_DEBUG) {
//						echo '<br>Compare '.$i.' and '.$j;		
//						debug_print_event($e);
//						echo '<br> ------------------------ '; 
//					}
									
					
					if ($arr[$j]['EventDate'] === $e['EventDate']){
						if (ICAL_EVENTS_DEBUG) {
						
							echo '<br><br>*** Same datetime '; debug_print_event($arr[$j]); //$e['EventDate']->format('c');
							}
						if ($e['UID'] === $arr[$j]['UID'])  {  /* keep the one with the hisghest SEQUENCE */
							if (ICAL_EVENTS_DEBUG) {echo '<br>Same uid '.$e['UID'];}
							if ($e['SEQUENCE'] > $arr[$j]['SEQUENCE']) {					
								if (ICAL_EVENTS_DEBUG) {echo ' Unset one instance with sequence =  '.$arr[$j]['SEQUENCE'];}
								unset ($arr [$j]);		
							}
							else {
								if (ICAL_EVENTS_DEBUG) {echo ' Unset one instance with sequence =  '.$e['SEQUENCE'];}
								unset ($arr [$i]);
							}
						}	

					}
					$j = $j + 1;
				}
			}
		
		}
	/* ========================================================================= */			
	function amr_repeat_anevent($event, $repeatstart, $aend, $limit) {
	/* for a single event, handle the repeats as much as is possible */
	$repeats = array();
	
	if (ICAL_EVENTS_DEBUG) {
		echo '<h3>Repeat an event with this '.$event['DTSTART']->format('c').'</h3>';
		//if (is_object ($event['DTEND'])) {	echo ' DTEND= '.$event['DTEND']->format('c').'</h3>';}
		//else {	echo ' DTEND= '.$event['DTEND'].'</h3>';}
		}
	
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
				foreach ($event['EXRULE'] as $i => $exrule) {			
					$reps = amr_process_RRULE($rdate, $repeatstart, $aend, $limit);
					if (is_array($reps) and count($reps) > 0) {
/* amr this need s to remove */					
						$exclusions  = array_merge($exclusions , $reps);

					}
				}
			}
			
		if (isset($event['EXDATE']))	{		
				foreach ($event['EXDATE'] as $i => $exdate) {			
					$reps = amr_process_RDATE($rdate, $repeatstart, $aend, $limit);
					if (is_array($reps) and count($reps) > 0) {
						$exclusions  = array_merge($exclusions , $reps);
					}
				}
			}

		if (ICAL_EVENTS_DEBUG) { echo '<br>Still have '.count($repeats). ' before exclusions';}				
			/* Now remove the exclusions from the repeat instances */
		if (is_array($exclusions) and count ($exclusions) > 0) {
				foreach ($exclusions as $i=> $excl)
					if (in_array($excl, $repeats)) {
						unset ($i);  /* will create a gap in the index, but that's okay, not reliant on it  */ 
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

//			if (is_array($event['DTSTART'])) { /* there should only  be one DTSTART,  it returns array because of parsing for all dates like a RDATE */
//					$dtstart = $event['DTSTART'][0]; 
//				}
//			else 
				$dtstart = $event['DTSTART'];
			if (ICAL_EVENTS_DEBUG) {echo '<br><h3>Before repeat DTSTART = '.$dtstart->format('c').'</h3> '; debug_print_event ($event);	}

			if (amr_is_before ($dtstart, $aend)) {  /* If the start is after our end limit, then skip this event */			
					if (isset($event['RRULE']) or (isset($event['RDATE']))) {
						
						/* must use dtstart in case we are dependent on it's characteristics,. We can exclude too early dates later on */
						$repeats = amr_repeat_anevent($event,$dtstart,$aend, $limit );
						
						if (ICAL_EVENTS_DEBUG) {echo '<br>Num of Repeats '.count($repeats).'<br>';}	
						/* now need to convert back to a full event by copying the event data for each repeat */
						
						if (is_array($repeats) and (count($repeats) > 0)) {
							foreach ($repeats as $i => $r) {
								$newevents[$i] = $event;  // copy the event data over - not objects will point to same object - is this an issue?   Use duration or new /clone Enddate
								$newevents[$i]['EventDate'] = new DateTime();
								$newevents[$i]['EventDate'] = clone $r;  
								if (ICAL_EVENTS_DEBUG) {echo '<br><br>Created new event - DTSTART '.$event['DTSTART']->format('c');									
								}
							}
						}				
					}
					else { /* there is no repeating rule, so just copy over */
					
						if (ICAL_EVENTS_DEBUG) {echo '<br>Non-repeating event ';}
							
						$newevents[0] = $event;  /* so we drop the old events used to generate the repeats */
						
						if (isset ($event['RECURRENCE-ID'])) {   /*repeats already done by ical generator */
							if (is_array($event['RECURRENCE-ID'])) { /* just take first, should only be one */
								$newevents[0]['EventDate'] = $event['RECURRENCE-ID'][0];   // this should be okay without cloning, as wil not modify ?
							}
							else if (is_object($event['RECURRENCE-ID'])) {
								$newevents[0]['EventDate'] = $event['RECURRENCE-ID'];
							}
							else if (ICAL_EVENTS_DEBUG) {echo '<br>Error in Recurrence id '; var_dump($event['RECURRENCE-ID']);}
						}
						else {
							$newevents[0]['EventDate'] = new DateTime();
							$newevents[0]['EventDate'] = clone $dtstart; 
						}
					}
				}
				else {
					if (ICAL_EVENTS_DEBUG) echo '<br>Skipped this event.  Overall End '.$aend->format('c'), ' is before event start'. $event['DTSTART']->format('c');
				}
			return ($newevents);
		}
/* ------------------------------------------------------------------------------------*/
	/*
	 * Constrain the list of COMPONENTS to those which fall between the
	 * specified start and end time, up to the specified number of
	 * events.
	 */
	function amr_process_all_components_within_ranges($events, $start, $end, $limit) {
			
		if (ICAL_EVENTS_DEBUG) { 
			echo '<h3>Starting with '.count($events). ' want all Starting from:'.$start->format('c').' and before '.$end->format('c').' and no more than '.$limit. '</h3>';
		}
		
		$newevents = amr_process_icalevents($events, $start, $end, $limit);

		if (ICAL_EVENTS_DEBUG) { echo '<br>Records generated = '.count($newevents);}
		
		$newevents = amr_sort_by_key($newevents , 'EventDate');
		
		if (ICAL_EVENTS_DEBUG) { echo '<br>After sorting = '.count($newevents); }
		
		$constrained = array();
		$count = 0;
		foreach ($newevents as $k => $event) 
		{	
			//if (ICAL_EVENTS_DEBUG) { echo '<br>Choosing '.$count.' '. $event['EventDate']->format('c');}	
		
			if (amr_falls_between($event['EventDate'], $start, $end)) {
				$constrained[] = $event;
				if (ICAL_EVENTS_DEBUG) { echo '<br>Choosing '.$k.' '. $event['EventDate']->format('c').' with DTSTART '.$event['DTSTART']->format('c');}		
				++$count;	
			}
			else if (ICAL_EVENTS_DEBUG) { echo '<br>Not Choosing '.$k.' '. $event['EventDate']->format('c').' Limit = '.$limit;}		
			
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
		 
		 convert to utc first? to avoid date maths problems
		 */
	function amr_process_icalevents($events, $astart, $aend, $limit) {
		$dates = array();

		foreach ($events as $i=> $event) {
		
			$more = amr_process_single_icalevents($event, $astart, $aend, $limit);
			if (ICAL_EVENTS_DEBUG) { echo ' <br>No.Date= '.count($dates).' Plus more = '.count($more) ;}	
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
/* validate the url, cache it if necessary, and then parse it into basic nested structure */

		if (!filter_var($url, FILTER_VALIDATE_URL))  {	 echo "<h2>URL is not valid: ".$url.'/<h2>'; return; }

		$file = cache_url($url,$amr_limits['cache']);
		if (! $file) {	echo "iCal Events: Error loading [$url]";	return;	}

		$ical = parse_ical($file);
		
		if (! is_array($ical) ) {
			echo "iCal Events: Error parsing calendar [$url]";
			return;
			}
		$ical['icsurl'] = $url; 	

		return ($ical);	

}
/* -------------------------------------------------------------------------*/
function process_icalspec($spec) {
	global $amr_options;
	global $amr_limits;
	global $amr_listtype;
	
/* amr  should split out format spec here  if it exists, else the first one in the options will be used or a default  */
		$temp = explode (';',$spec);
		$urls = $temp[0];
		amr_getset_listtype ($temp[1]);

	  /* amr  should split out url's here  if there are multiple */	
		$urls = explode (',',$urls);
		foreach ($urls as $i => $url) {
			$icals[$i] = process_icalurl($url);
		}
		
		if (ICAL_EVENTS_DEBUG)	echo '<h2>Finished Parsing.... now generate repeated events</h2>';	
		
		/* now we have potentially  a bunch of calendars in the ical array, each with properties and items */
		/* only doing vevent here, must allow for others */
		/* Merge then constrain  by options */
		$events = array();  /* all components actually, not just events */
		if (isset ($icals) ) {	/* if we have some parse data */
			foreach ($icals as $j => $ical) { /* for each  Ics file within an ICal spec*/
				foreach ($amr_options[$amr_listtype]['component'] as $i => $c) {  /* for each component type requested */		
					if ($c) {		/* If this component was requested, merge the items from Ical items into events */	
						if (isset($ical[$i])) {
								if (!empty($events) ) {
									$events = array_merge ($events, $ical[$i]);	
								}
								else $events = $ical[$i];
							}
					}
				}
			}
		

/* amr here is the main calling code */	
			$calprophtml = amr_list_properties ($icals);	
 			
			if (ICAL_EVENTS_DEBUG) {echo '<h3>Got '.count($events).'</h3>';}
			
			$events = amr_process_all_components_within_ranges($events, $amr_limits['start'], 
				$amr_limits['end'],$amr_limits ['Events']);	
				
			if (ICAL_EVENTS_DEBUG) {echo '<h3>Then '.count($events).'</h3>';}
				
			$thecal = '<li id="'.$amrW.'amrical'.$icalno.'">'
				.$calprophtml.list_events ($events ) . amr_give_credit() .'</li>';
			
/* amr  end of core calling code --- */
		}
		else {	/* the urls were not valid or some other error ocurred, for this spec, we have nothing to print */
			$thecal = '';
		}
		return ($thecal);
	} 



/* -------------------------------------------------------------------------*/
function amr_query_passed_in_url () {
	if (isset($_GET['iCal'])) {
		$spec = $_GET['iCal'];
		if (!filter_var($spec, FILTER_VALIDATE_URL)) {
			echo '<h2>Invalid Ical URL passed in query string</h2>';
			return false;
		}
		else { 	
			if (isset($_GET['listtype'])) {
				if (filter_var($_GET['listtype'], FILTER_VALIDATE_INT)) {
					return ($spec .';'. 'listtype='.$_GET['listtype']);
				}	
				else echo '<h2>Invalid listtype passed in query string</h2>';
			}
			else return ($spec);
		}
	}
	else return (false);
}
/* -------------------------------------------------------------------------*/
// This is the main function.  It replaces [iCal:URL]'s with events. Each as a separate list 
function amr_replaceURLs($content) 
{
	global $amrW;
	
	if ($icalspecs[] = amr_query_passed_in_url()) {  /* mainly used for testing */
		$replacestrings[] = $content; /* overwrite all */		
		echo '<p><strong>Note: Using Testmode - Ical Specification passed in query string - ignoring page content and using '.$icalspecs[0].'</strong></p>';
	}
	else {

		/* amr note the iCal line must be one string on one line */
		preg_match_all('/\[iCal:(\S+)\]/', $content, $matches, PREG_PATTERN_ORDER);

		$replacestrings = $matches[0];
		$icalspecs = $matches[1];
	}

	foreach ($icalspecs as $icalno => $spec) {/* for each Ical spec */
		$thecal[$icalno] = process_icalspec($spec);
	}   
	foreach ($icalspecs as $icalno => $spec) /* for each Ical spec */
	{

		if (!$amrW) $thecal [$icalno] = AMR_NL.'<ul class="amrical">'.$thecal[$icalno].'</ul>'.AMR_NL;  

		$content = str_replace($replacestrings[$icalno], $thecal[$icalno], $content);
	}

  return ($content);
}


/* -------------------------------------------------------------------------------------------------------------*/
/**
 * Internationalization functionality
 */

$amr_text_loaded = false;
/* -------------------------------------------------------------------------------------------------------------*/
function amr_load_textdomain()
{
   global $amr_text_loaded;
   if($amr_text_loaded) return;

   load_plugin_textdomain('amr-ical-events-list', PLUGINDIR.'/'.dirname(plugin_basename(__FILE__)), dirname(plugin_basename(__FILE__)));
   $amr_text_loaded = true;
}

/* -------------------------------------------------------------------------------------------------------------*/


	if ( !defined('WP_CONTENT_DIR') )	define( 'WP_CONTENT_DIR', ABSPATH . 'wp-content' );
	define('AMRICAL_ABSPATH', WP_CONTENT_DIR.'/plugins/' . dirname(plugin_basename(__FILE__)) . '/');
	
	if (!version_compare(AMR_PHPVERSION_REQUIRED, PHP_VERSION)) {
		echo '<h1>'.'Minimum Php version '.AMR_PHPVERSION_REQUIRED.' required.  Your version is '.PHP_VERSION.'</h1>';}

	amr_load_textdomain();
			
	if (is_admin() )
	{
		add_action('admin_head', 'AmRIcal_options_style');
		add_action('admin_menu', 'AmRIcal_add_options_panel');	
	}

	add_action('wp_head',  'amr_ical_events_style');
	add_filter('the_content','amr_replaceURLs'); 
