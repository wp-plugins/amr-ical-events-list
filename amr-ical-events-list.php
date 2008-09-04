<?php
//ini_set('display_errors', 1);
//error_reporting(E_ALL);
/*
Plugin Name: AmR iCal Events List
Version: 2
Plugin URI: http://anmari.com
Description: Display events from an iCal source in a list fashion for css styling (not a box calender). 
Uses code and ideas from the following and others:
[import_ical.php](http://cvs.sourceforge.net/viewcvs.py/webcalendar/webcalendar/import_ical.php?rev=HEAD) from the [WebCalendar](http://sourceforge.net/projects/webcalendar/) project. 
[dwc's plugin] (http://dev.webadmin.ufl.edu/~dwc/2005/03/10/ical-events-plugin/)
[PhpIcalendar] (http://phpicalendar.net/)
[Horde] (http://www.horde.org/kronolith/) 
Provides the following additional functionality:
- Widget list of events available
- Control over contents and styling from the plugin and widget menu's.
- Lots of css tags for innovative styling

*/

/*  these are  globals that we do not want easily changed -others are in the config file */
global $amr_options;
global $amrW;  /* set to W if running as widget, so that css id's will be different */
$amrW = '';

require_once('amr-ical-config.php');
require_once('ical-events.php');
require_once('ical-list-admin.php');

function __a ($text)
{  return __($text,'AmRIcalList' );
}

function amr_ical_events_style()  /* check if the default table style is there */
{
	global $amr_options;
	global $amr_listtype;
		
	if (!isset($amr_options)) $amr_options = amr_getset_options (false);	
	
	/* amr we need to parse the page content to get the list type requested, else will always use 1 .  */
	if (!(isset($amr_listtype))) 
	{$amr_listtype = 1; echo '<!--AmR iCal list type not set, using 1 -->'.AMR_NL;}
	/* should this be a global rather than refetching here?   Also should the style be specified in the page rather using  No?*/

	$ical_style_file = (empty($amr_options[$amr_listtype]['general']['Css URL'])) ? null : $amr_options[$amr_listtype]['general']['Css URL'];
	/* then not using their own style */
	if (isset($ical_style_file))  /* amr could we just include the file? would allow dynamic ? */
		{ 
		$contents = file_get_contents($ical_style_file); 
		if ($contents) 
			{
			echo "\n".'<style type="text/css"> <!--For amrical -using css file '.$ical_style_file.' -->';
			echo $contents             // printing the file content of the file
			.'</style>'."\n";
			}
		else echo '<!-- Error getting contents of Ical Events List style:'.$ical_style_file.' -->'; 
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
function hyperlink($text) 
{  /* checks text for links and converts them to html code hyperlinks */

    // match protocol://address/path/
    $text = ereg_replace("[a-zA-Z]+://([.]?[a-zA-Z0-9_/-?&%])*", "<a href=\"\\0\">\\0</a>", $text);
    
    // match www.something
    $text = ereg_replace("(^| )(www([.]?[a-zA-Z0-9_/-?&%])*)", "\\1<a href=\"http://\\2\">\\2</a>", $text);
	
    // return $text
    return $text;
}/* --------------------------------------------------  */
function format_value ($content, $k)
/*  Format each Ical value for our presentation puurposes 

 amr we need to add to this later, but for now, :
get the line breaks working again!! nl2br is broken inside wordpress for some reason?
strip slahes added to comma's etc (see the eccentric dates calendar on google)

Note: Google does toss away the html when editing the text.

what about all day?
*/
{
	global $amr_formats;
	global $amr_options;
	global $amr_listtype;

	if ($content === '') return ($content);

	if (!isset($amr_options[$listype]['format'])) $format = $amr_formats;
	else $format = $amr_options[$listype]['format'];

	switch ($k){
		case 'EventDate':
		case 'EndDate':
 /* These are in timestamp format */
			if (is_int ($content))
				{
				$content = strftime($format['Day'], $content); 
				}
			break;
		case 'EndTime':
		case 'StartTime': /* These are in timestamp format */
			if (is_int ($content))
				$content = strftime($format['Time'], $content); 
			break;
		case 'LOCATION': 
			$content = $content.'<span class="map"><a href="http://maps.google.com/maps?q='.$content.'" target="_BLANK">'
			.__('map','AmRIcalList').'</a></span>'; break;
		case 'URL': /* if valid URL, then format it as such */
			if(filter_var($content, FILTER_VALIDATE_URL))
				$content = '<a href="'.$content.'">'.__('Event Link', 'AmRIcalList').'</a>';
			else $content .=__(' - invalid', 'AmRIcalList');
			break;	
		case 'DTSTART':
		case 'DTEND':
		{	$content = strftime( $format['DateTime'], $content);
			break;
		}
		default: 
			$content =  hyperlink($content);
//		$content = format_date ( $amr_formats['Day'], $content);break;			 
	}
	/* Convert any newlines to html breaks */
	return (str_replace("\n", "<br />", $content));
	
}
/* --------------------------------------------------  */
function list_properties($icals, $amr_listtype)
{  /* List the calendar properties if requested in options  */
	global $amr_options; 
	
	$order = prepare_order_and_sequence  ($amr_options[$amr_listtype]['calprop']);
	if (!($order)) {return; };
	
	foreach ($icals as $i => $p)
	{ /* go through the options list and list the properties */
		if (!isset ($icals[$i]['Properties'])) break;			
		$prevcol = $col = ''; 
		$cprop = '';   
		foreach ($order as $k => $v)  /* for each column, */
		{	
			$col = $v['Column'];
			if (!($col === $prevcol)) /* then starting new col */
			{	if (!($prevcol === '')) { $cprop .= AMR_NL.$tb.'</ul> <!-- end of amrcol -->';}  /* end prev column */
				$cprop .= AMR_NL.$tb.'<ul class="amrcol amrcol'.$col.'">';  /* start next column */
				$prevcol = $col;
			}			
			if (isset ($icals[$i]['Properties'][$k])) /*only take the fields that are specified in options  */
			{		
				$cprop .= AMR_NL.$tb.'<li>'.$v['Before']
				.format_value($icals[$i]['Properties'][$k], $k)
				.$v['After'].'</li>';
			}
		}
		if (!($cprop === '')) /* if there were some calendar property details*/
			{$html .= '<li class="cal">'.AMR_NL.'<ul class="amrrow"><li class="amrrow">'
					.$cprop.AMR_NL.$tb.'</ul> <!-- end of amrcol -->'.AMR_NL
					.'</li></ul><!-- end of amrrow -->'.AMR_NL.'</li>';  		}
	}
	if (!($html === '')) 
	{	$html  = 	AMR_NL.'<ul id="'.$amrW.'calprop">'.$html.'</ul>';
	}
	return ($html);
}

/* --------------------------------------------------  */
function amr_same_time ($starttime, $endtime)
{
		if (strftime('%X',$starttime) === strftime('%X',$endtime)) return (true);
		else return (false);
}

/* --------------------------------------------------  */

function list_events($events, $amr_listtype, $g=null)
{
	global $amr_options; 
	global $amrW;

	/* The component properties have an additional structuring level that we need to remove */
	if (!isset($amr_options)) echo '<br />Options not set';
	else if (!isset($amr_options[$amr_listtype])) echo '<br>listtype Not set: '.$liststype.' In Option array '.var_dump($amr_options);
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
		
			if (ICalEvents::is_all_day($e['StartTime'], $e['EndTime']))
				{	$e['StartTime'] = __('All day','AmRIcalList' );
					$e['EndTime'] = '';
				}
			else 
			if (amr_same_time($e['StartTime'],$e['EndTime']))
				{	$e['EndTime'] = '';
				}	
				
			if 	($e['DURATION'] = 1440) 
				{	$e['EndDate'] = '';  /* If duration is 60*24 minutes, then we do not need the end date */
					$e['DURATION'] = '';					
				}
			
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
// This is the main function.  It replaces [iCal:URL]'s with events. Each as a separate list 
function replaceURLs($content) 
{
	global $amr_options;
	global $amr_listtype;
	global $amrW;
	global $amr_ical_limit;
	/* amr not the iCal line must be one string on one line */
	preg_match_all('/\[iCal:(\S+)\]/', $content, $matches, PREG_PATTERN_ORDER);

	$replacestrings = $matches[0];
	$icalspecs = $matches[1];
  
	if (ICAL_EVENTS_DEBUG)   
	{	echo '<br>Replacestrings = '; var_dump ($replacestrings);
		echo '<br>iCalspecs = '; var_dump ($icalspecs); echo '<br>End spec <br>';
	}
	foreach ($icalspecs as $icalno => $spec) /* for each Ical spec */
	{
	   /* amr  should split out format spec here  if it exists, else the first one in the options will be used or a default  */
		$temp = explode (';',$spec);
		$urls = $temp[0];
		if (isset($temp[1])) 
			{	/* allow upper or lower iCal parameters */
				parse_str(strtolower($temp[1]), $args);
				if (isset ($args['listtype'])) $amr_listtype = $args['listtype'];
				else $amr_listtype = 1;
			}
		else $amr_listtype = 1;
		
		if (ICAL_EVENTS_DEBUG) echo '<br>For Spec '.$i.' listtype = '.$amr_listtype;
	  /* amr  should split out url's here  if there are multiple */	
		$urls = explode (',',$urls);
		if (ICAL_EVENTS_DEBUG) {echo '<br>Urls are: '; var_dump($urls); echo '<br>';}
		foreach ($urls as $i => $url)
		{
			if (!filter_var($url, FILTER_VALIDATE_URL))  {	 echo "URL is not valid: ".$url;  }
			else
			{		
				if (ICAL_EVENTS_DEBUG)	echo '<br>Processing '.$url.'<br>';
				$file = ICalEvents::cache_url($url);
				if (! $file) {	echo "iCal Events: Error loading [$url]";	return;	}

				$icals[] = parse_ical($file);
				
				if (! is_array($icals) or count($icals) <= 0) {
					echo "iCal Events: Error parsing calendar [$url]";
					return;
				}
			}	
		}
		/* now we have potentially  a bunch of calendars in the ical array, each with properties and items */
		if (ICAL_EVENTS_DEBUG)	{echo '<br>Icals '; var_dump($icals); echo'<br>';}
		/* Merge then constrain  by options */
		if (isset ($icals) && (isset ($icals[0]) ))
		{	$events = $icals[0]['Items'];
			for ($j = 1; $j < count($icals); $j++) /* for each additional Ics file within an ICal spec*/
			{
				if (ICAL_EVENTS_DEBUG)	echo 'Merging ical '.$j.'<br>';	
				/* Merge from Ical items into events */	
				$events = array_merge($events, $icals[$j]['Items']);	
			}
					
			if (!isset($amr_options)) $amr_options = amr_getset_options(false);  /* will check for stored options and else get a default set */	

			if (! isset ($amr_options[$amr_listtype]))  $amr_options[$amr_listtype] = $amr_options[1];	
			/* check for constraints */
			$gmt_start = time();
			if (isset($amr_options[$amr_listtype]['limit']['Events']))
				{$amr_ical_limit = $amr_options[$amr_listtype]['limit']['Events'];}
				
			if (isset ($amr_options[$amr_listtype]['limit']['Days']))
				{$gmtend = $gmt_start + ($amr_options[$amr_listtype]['limit']['Days'] *60*60*24);}
				
			if (ICAL_EVENTS_DEBUG) echo '<br><h2>Limits</h2>'.$gmt_start.' '.$gmt_end.' '.$limit.'<br>';
			
/* amr here is the main calling code */	
				
			$calprophtml = list_properties ($icals, $amr_listtype );	
				
			$events = ICalEvents::constrain($events, $gmt_start, $gmt_end, $amr_ical_limit);	
				
			$thecal[$icalno] = '<li id="'.$amrW.'amrical'.$icalno.'">'
				.$calprophtml.list_events ($events, $amr_listtype ).'</li>';
			
/* amr  end of core calling code --- */
		}
		else 
		{	$thecal[$icalno] = '';
			if (ICAL_EVENTS_DEBUG)	echo '<br><h2> No ical for '.$icalno.'</h2><br>';		
		}
	} 
	foreach ($icalspecs as $icalno => $spec) /* for each Ical spec */
	{
		/* Fix worpress html validation in a page or post.  Note it does not do the samething in a widget  - there it has a surrounding ul */
		/* In a page or post,wordpress seems to force a <p> before anfter which so the page then does not validate. */
		/* similarly if inserts a closing </p> after the content */;
		if (!$amrW) $thecal [$icalno] = AMR_NL.'</p><ul class="amrical">'.$thecal[$icalno].'</ul><p>'.AMR_NL;  

		if (ICAL_EVENTS_DEBUG)	echo '<br>Replace '.$icalno.' '.$replacestrings[$icalno].' with the calendar<br>';	
		$content = str_replace($replacestrings[$icalno], $thecal[$icalno], $content);
	}

  return ($content);
}

/* ============================================================================================== */
function amr_ical_list_widget($args)

{	
	global $amrW;
	global $amr_options;
	global $amr_ical_limit;  /* we are using same variable for widget and page, but it gets picked up from the options table as meeded, so should be ok */
	$amrW = 'w';
	
    extract($args);

	if (!isset($amr_options)) $amr_options = amr_getset_options(false);

	$title = (empty($amr_options["title"])) ? null : $amr_options["title"];
	$urls  = (empty($amr_options["urls"])) ? null : $amr_options["urls"];
	$amr_listtype  = (empty($amr_options["listtype"])) ? null : $amr_options["listtype"];
	$amr_ical_limit = $amr_options['limit'];
	$moreurl = (empty($amr_options['moreurl'])) ? null : $amr_options['moreurl'] ;
	if (isset ($moreurl)) $title = '<a href= "'.$moreurl.'">'.$title.'</a>';
	
	$content = '[iCal:'.$urls.';listtype='.$amr_listtype.']';
	$content = replaceURLs($content) ;
	//output...
	echo $before_widget;
	echo $before_title . $title . $after_title . AMR_NL.'<ul id="'.$amrW.'amrical">';
	echo $content;
	echo AMR_NL."</ul>".$after_widget;
}
/* -------------------------------------------------------------------------------------------------------------*/
function amr_ical_widget_init()
{
    register_sidebar_widget("AmR iCal Event List", "amr_ical_list_widget");
    register_widget_control("AmR iCal Event List", "amr_ical_list_widget_control");
}
/* -------------------------------------------------------------------------------------------------------------*/
/* This is used to tailor the multiple default listingoptions offered */
	function new_listtype()
	{
	global $amr_calprop;
	global $amr_compprop;
	global $amr_groupings;
	global $amr_components;
	global $amr_limits;
	global $amr_formats;
	global $amr_general;
	
	$amr_listtype = (array 
		(
		'general' => $amr_general,
		'format' => $amr_formats,
		'calprop' => $amr_calprop, 
		'component' => $amr_components,
		'grouping' => $amr_groupings,
		'compprop' => $amr_compprop,
		'limit' => $amr_limits
		)
		);
	$amr_options[$i]['compprop']['Descriptive']['EndTime']['Order'] = 2;
	return $amr_listtype;
	}
	
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
			break;
		case 3: 
			$amr_options[$i]['general']['Name']='Timetable';
			foreach ($amr_options[$i]['grouping'] as $g=>$v) {$amr_options[$i]['grouping'][$g] = false;}
			$amr_options[$i]['grouping']['Day'] = true;		
			$amr_options[$i]['compprop']['Date and Time']['EventDate']['Column'] = 0;
			$amr_options[$i]['compprop']['Date and Time']['EndDate']['Column'] = 0;
			
			break;
		case 4: 
			$amr_options[$i]['general']['Name']='Widget'; /* No groupings, minimal */
			foreach ($amr_options[$i]['grouping'] as $g=>$v) {$amr_options[$i]['grouping'][$g] = false;}
			foreach ($amr_options[$i]['compprop'] as $g => $v) 
				foreach ($v as $g2 => $v2) {$amr_options[$i]['compprop'][$g][$g2]['Column'] = 0;}
			$amr_options[$i]['compprop']['Date and Time']['EventDate']['Column'] = 1;
			$amr_options[$i]['compprop']['Date and Time']['StartTime']['Column'] = 1;
			$amr_options[$i]['compprop']['Date and Time']['EndDate']['Column'] = 1;
			$amr_options[$i]['compprop']['Date and Time']['EndTime']['Column'] = 1;
			$amr_options[$i]['compprop']['Descriptive']['SUMMARY']['Column'] = 1;
			$amr_options[$i]['compprop']['Descriptive']['SUMMARY']['Order'] = 10;
			break;
		case 5: 
			$amr_options[$i]['general']['Name']='Alternative';
			foreach ($amr_options[$i]['grouping'] as $g=>$v) {$amr_options[$i]['grouping'][$g] = false;}
			$amr_options[$i]['grouping']['Western Zodiac'] = true;
			$amr_options[$i]['compprop']['Date and Time']['DTSTART']['Column'] = 1;
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
			'http://localhost/wptest/wp-content/plugins/amr-ical-events-list/icallist2.css';   /* If empty, then assume the blog stylesheet will cope, else could contain special one */
		
			break;		
		}
		return ( $amr_options[$i]);
	}
/* ------------------------------------------------------------------------------------------------------ */

	function amr_getset_options ($reset=false)
	/* get the options from wordpress if in wordpress
	if no options, then set defaults */
	{
		global $amr_formats;
		global $amr_options;
			
		if ($reset)	/* The we are requested to reset the options */
		{ 	if (function_exists ('delete_option'))  	
				{	delete_option("AmRiCalEventList");	}; 
		};

		if (function_exists ('get_option') && ($amr_options = get_option('AmRiCalEventList')))
		{	}
		else 
			{/* no wordpress options  - do we need anythinghere */	}
			
		/* Now check if we need to setup any more defaults */	
		if (!(isset($amr_options['no_types'])))  
			{ 	$amr_options['no_types'] = 6;
				$amr_options[1] = new_listtype();
			}
		for ($i = 1; $i <= $amr_options['no_types']; $i++)   /* setup some list type defaults if we have empty list type arrays */
		{	/* if we are reseting or the options are not set */
			if ($reset )   
			{	
				$amr_options[$i] = new_listtype();
				$amr_options[$i] = customise_listtype( $i);
			}	
			else 
			{	
				if (!(isset($amr_options[$i])))
				{	if ($i == '1') 
					{	
						$amr_options[$i] = new_listtype();
					}
					else 
					{	
						$amr_options[$i] = $amr_options['1'];
					}
					$amr_options[$i] = customise_listtype( $i);
				}
			}	
		}	
		return ($amr_options);
	}

/* -------------------------------------------------------------------------------------------*/
function format_grouping ($grouping, $datestamp)
/* check what the format for the grouping hshould be, call functiosn as necessary*/
{
global $amr_options;
global $amr_listtype;

	if (in_array ($grouping ,array ('Year', 'Month','Day')))
		return (format_date( $amr_options[$amr_listtype]['format'][$grouping], $datestamp));
	else if ($grouping == 'ISO Week')
		{	return (sprintf(__('Week  %u', 'AmRIcalList'),format_date( 'W', $datestamp)));
		}
	else 
	{ 	/* for "Quarter",	"Astronomical Season",	"Traditional Season",	"Western Zodiac",	"Solar Term" */
		$func = str_replace(' ','_',$grouping);
		if (function_exists($func) )
			return call_user_func($func,$datestamp);
		else 
		   return ('No function for Date Grouping');
	}
}
/* -------------------------------------------------------------------------------------------*/
function format_date( $format, $datestamp)
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

/* -------------------------------------------------------------------------------------------------------------*/

	if ( !defined('WP_CONTENT_DIR') )	define( 'WP_CONTENT_DIR', ABSPATH . 'wp-content' );
	define('AMRICAL_ABSPATH', WP_CONTENT_DIR.'/plugins/' . dirname(plugin_basename(__FILE__)) . '/');

	if (is_admin() )
	{
		add_action('admin_head', 'AmRIcal_options_style');
		add_action('admin_menu', 'AmRIcal_add_options_panel');	
	}

	add_action('wp_head',  'amr_ical_events_style');
	add_action('plugins_loaded', 'amr_ical_widget_init');	
	add_filter('the_content','replaceURLs'); 

	?>