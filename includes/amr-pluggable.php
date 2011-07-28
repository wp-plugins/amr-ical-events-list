<?php /* Pluggable functions that need to be loaded after the theme so that a theme functions.php can override 
*/
// ----------------------------------------------------------------------------------------
if (!function_exists( 'amr_handle_no_events')) {
	function amr_handle_no_events () { 
		global $amr_options,
		$amr_limits;
		
		$thecal = '';
		if (!empty($amr_options['noeventsmessage'])) {
			$thecal .=  '<a class="noeventsmessage" style="cursor:help;" href="" title="'
			.amr_echo_parameters().'"> '
			.$amr_options['noeventsmessage'].'</a>';
				
			if ((isset($amr_limits['show_look_more'])) and ($amr_limits['show_look_more'])) {
					$thecal .= amr_show_look_more();
			}					
			if ((isset($amr_limits['pagination'])) and ($amr_limits['pagination'])) {			
					$thecal .= amr_semi_paginate();
			}
		}
		return ($thecal);
	} // end function	
}
// ----------------------------------------------------------------------------------------
if (!function_exists( 'amr_human_time')) {
	function amr_human_time ($time) { 
		if ($time == '000000') return (__('midnight', 'amr-ical-events-list'));  // to avoid am/pm confusion, note midnight is start of day
		else if ($time == '120000') return (__('midday', 'amr-ical-events-list'));  // to avoid am/pm confusion
		else return ($time);
	}
}
add_filter ('amr_human_time','amr_human_time');
// ----------------------------------------------------------------------------------------

if (!function_exists('amrical_calendar_views')) {
function amrical_calendar_views () {
	global $amr_limits;
	
	if (ICAL_EVENTS_DEBUG) echo '<br />Preparing views<br />';

	$link = amr_clean_link();
	$link = remove_query_arg(array(
		'calendar',
		'agenda',
		'listtype',
		'eventmap'), $link);

	if (!empty ($amr_limits['agenda'])) $agenda = $amr_limits['agenda'];
	else $agenda = 1;
	if (!empty ($amr_limits['eventmap'])) $eventmap = $amr_limits['eventmap'];
	else $eventmap = false;  // if not explicitly asked for a map, then do not do it
	if (!empty($amr_limits['calendar'])) $calendar = $amr_limits['calendar'];
	else {		$calendar = 9;
	}

	if ($agenda) {
		$agendaviewlink = remove_query_arg('months',$link );
		$agendaviewlink = add_query_arg(array('agenda'=>$agenda),$agendaviewlink );
		$agendaviewlink = '<a class="agendalink button" href="'
		. htmlentities($agendaviewlink)

		. '" title="' . __('Go to agenda or list view', 'amr-ical-events-list'). '">'.__('Agenda', 'amr-ical-events-list').'</a>';
	}
	else $agendaviewlink = '';
	//
	if ($calendar) {
		$calendarviewlink = ' <a class="calendarlink" href="'
		. htmlentities(add_query_arg(array('calendar'=>$calendar),$link ))

		. '" title="' . __('Go to calendar view', 'amr-ical-events-list'). '">'.__('Calendar', 'amr-ical-events-list').'</a>';
	}
	else $calendarviewlink  = '';
	//
	if ($eventmap) {
		$mapviewlink = ' <a class="maplink" href="'
		. htmlentities(add_query_arg('view','map',$link ))

		. '" title="' . __('Go to map view', 'amr-ical-events-list'). '">'.__('Map', 'amr-ical-events-list').'</a>';
	}
	else $mapviewlink = '';
	$htmlviews = $agendaviewlink.$calendarviewlink.$mapviewlink;
	if (!empty ($htmlviews ) ) // this is so we do not return an empty div
		return ('<div id="calendar_views">'.$htmlviews.'</div>');
	else 
		return ('');
}
}
// ----------------------------------------------------------------------------------------
if (!function_exists('amr_month_year_navigation')) {
function amr_month_year_navigation ($start) { //note get is faster than post
global $amr_listtype;	
	$link = remove_query_arg('start',get_permalink());
	$link = add_query_arg('listtype',$amr_listtype, $link);
	return ('<form method="POST" action="'.htmlspecialchars($link).'">'
			.amr_monthyeardrop_down($start->format('Ymd'))
			.'<input title="'.__('Go to date', 'amr-ical-events-list').'" type="submit" value="'
			._x('&raquo;&raquo;','Submit button for month and year navigation.  Use translation to replace with words if you want.','amr-ical-events-list').'" >'
			.'</form>');
}
}
// ----------------------------------------------------------------------------------------
if (!function_exists('amr_week_links')) {
function amr_week_links ($start,$weeks) { // returns array ($nextlink, $prevlink, 

	global $wpdb, $wp_locale;

	// Get the next and previous month and year
	$prev = new Datetime();
	$prev = clone $start;
	date_modify($prev, '-'.($weeks*7).' days');   //may need later on if we are going to show multiple boxes on one page
	$prevstring = $prev->format('Ymd');
	$prevstring2 = amr_date_i18n('jS F',$prev);
	$next     = new Datetime();
	$next     = clone $start;
	date_modify($next, '+'.($weeks*7).' days');
	$nextstring = $next->format('Ymd');
	$nextstring2 =amr_date_i18n('jS F',$next);

	//---------------------------  get navigation links ---------------------------------------

	$link = amr_clean_link();

	$prevlink =
		'<a class="prevweek" href="'
		. htmlentities(add_query_arg('start',$prevstring,$link)) . '" title="'
		. sprintf(__('Week starting %1$s', 'amr-ical-events-list'), $prevstring2) 
		. '">'._x('&larr;','for prev navigation, translate allows you to use words', 'amr-ical-events-list').'</a>';
	
	$nextlink = '<a class="nextweek" href="'
		. htmlentities(add_query_arg('start',$nextstring,$link)) 
		. '" title="' 
		. sprintf(__('Week starting %1$s', 'amr-ical-events-list'), $nextstring2)
		. '">'._x('&rarr;','for next navigation, translate allows you to use words', 'amr-ical-events-list').'</a>';

	return (array('prevlink'=>$prevlink,'nextlink'=>$nextlink));
}
}
// ----------------------------------------------------------------------------------------
if (!function_exists('amr_month_year_links')) {
function amr_month_year_links ($start,$months) { // returns array ($nextlink, $prevlink, $dropdown

	global $wpdb, $wp_locale;

	// Get the next and previous month and year
	$previous = new Datetime();
	$previous = clone $start;
	date_modify($previous, '-1 month');   //may need later on if we are going to show multiple boxes on one page
	$prevmonth = $previous->format('m');
	$prevyear = $previous->format('Y');
	$next     = new Datetime();
	$next     = clone $start;
	date_modify($next, '+'.$months.' month');
	$nextmonth  = $next->format('m');
	$nextyear 	= $next->format('Y');

	//---------------------------  get navigation links ---------------------------------------

	$link = amr_clean_link();

	if ( $previous ) { $prevlink =
		'<a class="prevmonth" href="'
		. htmlentities(amrical_get_month_link($previous->format('Ymd'), $months, $link)) . '" title="'
		. sprintf(__('Go to %1$s %2$s', 'amr-ical-events-list'), $wp_locale->get_month($prevmonth), $prevyear) . '">&laquo;'
		. $wp_locale->get_month_abbrev($wp_locale->get_month($prevmonth)) . '</a>';
	}
	else $prevlink = '';
	if ( $next ) {
		$nextlink = '<a class="nextmonth" href="'
		. htmlentities(amrical_get_month_link($next->format('Ymd'), $months, $link))
		. '" title="' . esc_attr( sprintf(__('Go to %1$s %2$s', 'amr-ical-events-list'), $wp_locale->get_month($nextmonth), $nextyear))
		. '">' . $wp_locale->get_month_abbrev($wp_locale->get_month($nextmonth)) . '&raquo;</a>';
	}
	else $nextlink = '';
	return (array('prevlink'=>$prevlink,'nextlink'=>$nextlink));
}
}
// ----------------------------------------------------------------------------------------
if (!function_exists('amr_monthyeardrop_down')) {
function amr_monthyeardrop_down($current_start) {
global $wp_locale, $amr_globaltz;
	
//	$m = isset($_GET['m']) ? (int)$_GET['m'] : 0;  // actually yyyymm
	$startobj = new datetime();
	$ym = $startobj->format('Ym');
	$y = $startobj->format('Y');
	$m = $startobj->format('m');
	$startobj->setDate($y,$m,'01');
//	$ym = (int) substr($start, 0, 6);
//	$m  = (int) substr($start, 4, 2);
	$html = '';
	$options=array();
	date_modify($startobj, '-3 months');
	for ($i=1; $i<=18; $i=$i+1) {
		$startstring = $startobj->format('Ymd');
		$m = (int) substr($startstring, 4, 2);
		$y = (int) substr($startstring, 0, 4);
		$options[$startstring] = $wp_locale->get_month($m).' '.$y;
		date_modify($startobj,'+1 month');
	}
	$html .= amr_simpledropdown('start', $options, $current_start);
	return($html);
}
}
// ----------------------------------------------------------------------------------------
if (!function_exists('amr_calendar_navigation')) {
function amr_calendar_navigation($start, $months, $weeks, $liststyle, $views='') {

	if ($liststyle === 'weekscalendar') 
		$month_nav_html = amr_week_links ($start, $weeks); // returns array ($nextlink, $prevlink, $dropdown
	else	
		$month_nav_html = amr_month_year_links ($start, $months); // returns array ($nextlink, $prevlink, $dropdown
	$prevlink = $month_nav_html['prevlink'];
	$nextlink = $month_nav_html['nextlink'];		
	//
	if (($liststyle === 'weekscalendar') OR 
	(($months < 2) and ($liststyle == "smallcalendar"))) {
		$navigation = $prevlink.'&nbsp;&nbsp;'.$nextlink;
	}
	else {
		$navigation = 
		amr_month_year_navigation ($start)
		.$prevlink.'&nbsp;'
		.$nextlink
		;
	}
	return ($navigation);	
		//------------------------end navigation-----------
}
}
/* --------------------------------------------------  */
if (!function_exists('amr_weeks_caption')) {
	function amr_weeks_caption($start) {  
	// do not just want to use day format here, as may be too concise and week format cannot handle the start date, and there is no universal consistency on the week number logic 
	// later may offer an option we can use, but for now people can write a function
	// should we use this for weeks grouping too ?   maybe
		$caption_format = 'l jS F';
		$calendar_caption = sprintf(
			__('Week starting %s','amr-ical-events-list'),
			amr_date_i18n ($caption_format, $start));
		return($calendar_caption);	
	}
}


// ----------------------------------------------------------------------------------------
if (!function_exists('amr_calendar_navigation')) {
function amr_calendar_navigation($start, $months, $weeks, $liststyle, $views='') {

	if ($liststyle === 'weekscalendar') 
		$month_nav_html = amr_week_links ($start, $weeks); // returns array ($nextlink, $prevlink, $dropdown
	else	
		$month_nav_html = amr_month_year_links ($start, $months); // returns array ($nextlink, $prevlink, $dropdown
	$prevlink = $month_nav_html['prevlink'];
	$nextlink = $month_nav_html['nextlink'];		
	//
	if (($liststyle === 'weekscalendar') OR 
	(($months < 2) and ($liststyle == "smallcalendar"))) {
		$navigation = $prevlink.'&nbsp;&nbsp;'.$nextlink;
	}
	else {
		$navigation = 
		amr_month_year_navigation ($start)
		.$prevlink.'&nbsp;'
		.$nextlink
		;
	}
	return ($navigation);	
		//------------------------end navigation-----------
}
}
/* --------------------------------------------------  */
if (!function_exists('amr_weeks_caption')) {
	function amr_weeks_caption($start) {  
	// do not just want to use day format here, as may be too concise and week format cannot handle the start date, and there is no universal consistency on the week number logic 
	// later may offer an option we can use, but for now people can write a function
	// should we use this for weeks grouping too ?   maybe
		$caption_format = 'l jS F';
		$calendar_caption = sprintf(
			__('Week starting %s','amr-ical-events-list'),
			amr_date_i18n ($caption_format, $start));
		return($calendar_caption);	
	}
}

/* --------------------------------------------------  */
if (!function_exists('amr_semi_paginate')) {
function amr_semi_paginate() {
 	global $amr_limits;
	global $amrW;
	if ($amrW) return ('');
	$next = new datetime();
	$next = clone $amr_limits['end'];
	$next->modify('+1 second');
	$nextd = $next->format("Ymd");
	$gobackd = $amr_limits['start']->format("Ymd");
	$next = htmlentities (add_query_arg (array ('start'=>$nextd, 'startoffset'=>0 )));
	$explaint = '';
	if ((!empty ($amr_limits['hours'])) and (!($amr_limits['hours'] == '0'))) {
		$goback = htmlentities (add_query_arg (array ('start'=>$gobackd, 'hoursoffset'=> -$amr_limits['hours'])));
		$goback = remove_query_arg('startoffset',$goback);
		$goback = remove_query_arg('monthsoffset',$goback);
		$showmore = htmlentities (add_query_arg (array(
				
				'hours' => $amr_limits['hours']*2
				)));
		$showless = htmlentities (add_query_arg (array(
				
				'hours' => round($amr_limits['hours']/2)
				)));
		$showmuchmore = htmlentities (add_query_arg (array(
				
				'hours' => $amr_limits['hours']*20
				)));
		$showmuchless = htmlentities (add_query_arg (array(
				'hours' => max (1, round($amr_limits['hours']/20))
				)));	
		$explaint =  ' '.__('hours','amr-events');				
		}
	else
	if (isset ($amr_limits['months'])) {
		$goback = htmlentities (add_query_arg (array ('start'=>$gobackd, 'monthsoffset'=> -$amr_limits['months'])));
		$showmore = htmlentities (add_query_arg (array(
				
				'months' => $amr_limits['months']*2
				)));
		$showless = htmlentities (add_query_arg (array(
				
				'months' => round($amr_limits['months']/2)
				)));
		$showmuchmore = htmlentities (add_query_arg (array(
			
				'months' => $amr_limits['months']*2
				)));
		$showmuchless = htmlentities (add_query_arg (array(
				'months' => round($amr_limits['months']/2)
				)));	
		$explaint =  ' '.__('months','amr-events');				
		}
	else if (isset ($amr_limits['days'])) {
		$goback = htmlentities (add_query_arg (array ('start'=>$gobackd, 'startoffset'=> -$amr_limits['days'])));
		$showmore = htmlentities (add_query_arg (array(
				'days' => $amr_limits['days']*2
				)));
		$showless = htmlentities (add_query_arg (array(
				'days' => round($amr_limits['days']/2)
				)));
		$showmuchmore = htmlentities (add_query_arg (array(
				'days' => $amr_limits['days']*10
				)));
		$showmuchless = htmlentities (add_query_arg (array(
				'days' => max (1,round($amr_limits['days']/10))
				)));
		$explaint =  ' '.__('days','amr-events');			
	}

	$show10   = htmlentities (add_query_arg (array('events'=> 10)));
	$show50   = htmlentities (add_query_arg (array('events'=> 50)));
	$show100  = htmlentities (add_query_arg (array('events'=> 100)));
//	$explaint = ' - '.amr_explain_limits();

	$prevt    = __('show past events'  ,'amr-events');
	$lesst    = __('show less' ,'amr-events').$explaint;
	$moret    = __('show more' ,'amr-events').$explaint;
	$muchlesst    = __('show much less' ,'amr-events').$explaint;
	$muchmoret    = __('show much more' ,'amr-events').$explaint;
//	$lesstt   = __('show less' ,'amr-events').$explaint;
//	$morett   = __('show more' ,'amr-events').$explaint;
	$nextt    = __('show future events'   ,'amr-events');
	$eventnum10t= __('show maximum 10 events if available' ,'amr-events').$explaint;
	$eventnum50t= __('show maximum 50 events if available' ,'amr-events').$explaint;
	$eventnum100t= __('show maximum 100 events if available' ,'amr-events').$explaint;
	return (
		'<div id="icalnavs" class="icalnav" >'
		.'<a id="icalback" class="icalnav symbol" title="'.$prevt
		.'" href="'.$goback.'">&larr;</a>&nbsp;'
		.'<a id="icalmuchless" class="icalnav symbol" title="'.$muchlesst
		.'" href="'.$showmuchless.'">&minus;&minus;</a>&nbsp;'
		.'<a id="icalless" class="icalnav symbol" title="'.$lesst
		.'" href="'.$showless.'"> 	&minus;</a>&nbsp;&nbsp;'
		.'<a class="icalnav" title="'.$eventnum10t.'" href="'.$show10.'">10</a> '
		.'<a class="icalnav" title="'.$eventnum50t.'" href="'.$show50.'">50</a> '
		.'<a class="icalnav" title="'.$eventnum100t.'" href="'.$show100.'">100</a> '
		.'<a id="icalmore"  class="icalnav symbol" title="'.$moret
		.'" href="'.$showmore.'">+</a>&nbsp;'
		.'<a id="icalmuchmore"  class="icalnav symbol" title="'.$muchmoret
		.'" href="'.$showmuchmore.'">++</a>&nbsp;'
		.'<a id="icalnext"  class="icalnav symbol" title="'.$nextt
		.'" href="'.$next.'">&rarr;</a>'
//		.'<br /><span id="explain" style="font-style: italic; font-size: small; ">'.amr_explain_limits().'</span>'
		.'</div>'
		);
	}
}

/* -------------------------------------------------------------------------------------------*/
if (!function_exists('amr_format_CID'))  {
	function amr_format_CID ($cid, $event) {
		return ($cid);
	}
}
/* -------------------------------------------------------------------------*/
if (!function_exists('amr_mimic_taxonomies')) { // only called if we have an ics file
	function amr_mimic_taxonomies ($ical) {  // check if there is anything in the query url and only accept matches
	
		if (isset($ical['VEVENT']) and (isset($_REQUEST['category_name']))) 	{
			$catname = $_REQUEST['category_name'];
			
			foreach ($ical['VEVENT'] as $i => $e) {
				$found= false;
				if (!empty($e['CATEGORIES'])) {

					foreach ($e['CATEGORIES'] as $j => $c) {
						if (is_array($c)) {
							
							foreach ($c as $k => $c2) {
								if (($c2 == $catname )) $found= true;
							}
						}
						else if ($c == $catname ) $found= true;
					}
				}
				if (!$found) unset($ical['VEVENT'][$i]);
			}
			
		}	
		return($ical);	
	}
	
	//	var_dump($ical);
		//foreach ($ical )
	
}
/* --------------------------------------------------------- */
if (!function_exists('amr_format_attendees') ) {
	function amr_format_attendees ($attendees) {/* receive array of hopefully attendess[] CN and MAILTO, and possibly other */

	If (ICAL_EVENTS_DEBUG) {echo '<br />Attendee array:    '; var_dump($attendees);}
	$text = '';

	if (is_array($attendees))
		foreach ($attendees as $i => $attendee) {
			$list[] = amr_format_attendee ($attendee);
		}

	if (!empty($list)) $text = implode (', ',$list);
	return($text);
	}
}
/* --------------------------------------------------------- */
if (!function_exists('amr_format_attendee') ) {
	function amr_format_attendee ($attendee) {  // do not show emails for privacy reasons

	if (!empty ($attendee['CN'])) {
		if (!empty  ($attendee['LINK']))
		$text = '<a href="'.$attendee['LINK'].'" >'.$attendee['CN'].'</a>';
		else $text = $attendee['CN'];
	}
	else {
		if (is_array($attendee)) $text= implode($attendee);
		else $text = $attendee;
	}

	return ($text);
	}
}
/* -------------------------------------------------------------------------------------------*/
if (!function_exists ('amr_ical_showmap')) {
	function amr_ical_showmap ($text) { /* the address text */
	global $amr_options;
		$t1 = __('Show in Google map','amr-ical-events-list');
		if (isset ($amr_options['no_images']) and $amr_options['no_images']) $t3 = $t1;
		else $t3 = '<img src="'.IMAGES_LOCATION.MAPIMAGE.'" alt="'.	$t1	.'" class="amr-bling" />';
	/* this is used to determine what should be done if a map is desired - a link to google behind the text ? or some thing else  */
	
	return('<a class="hrefmap" href="http://maps.google.com/maps?q='
		.str_replace(' ','%20',($text)).'" target="_BLANK"'   //google wants unencoded
		.' title="'.__('Show location in Google Maps','amr-ical-events-list').'" >'.$t3.'</a>');
	}
}
/* --------------------------------------------------------- */
if (!function_exists('amr_format_allday')) {
	function amr_format_allday ($content) {
			if ($content == 'allday') return (__('all day', 'amr-ical-events-list'));
		else return ('');
	}
}
/* -------------------------------------------------------------------------------------------*/
if (!function_exists('amr_format_attach'))  {
	function amr_format_attach ($item, $event) {  // receive 1 attachment each being an array of type, url, binary (opt)

		if (empty ($item)) return ;
		$hrefhtml = '';
//	---- handle binary
			if (!empty($item['binary'])) {
				$name = str_replace($event['UID'], ' ','').'-image';
				$src = amr_format_binary ($name,$item['binary']);
				if (!empty($src))
					$hrefhtml .= '<img class="ics_attachment" src="'.$src.'" />';
			}
//	---- handle CID
			else if (!empty($item['CID']) ) {
				$hrefhtml .= amr_format_CID ($item['CID'], $event);
			}
//	---- handle url, prepare title
			if (empty($item['title'])) {  // make a title somehow
				if ( !empty($item['url']))   // this will only be  there if it is an internal event
					$item_title = esc_url($item['url']);
				else
					$item_title = '&nbsp;';
			}
			else $item_title = $item['title'];
//	---- handle mime types or url
			if (!empty($item['type']) ) {  // the fmttype or mime type

				$typeparts = explode ('/',$item['type'] );
// check for  title, type = audio/... video/.. etc
				if ($typeparts[0] === 'image') {  // only include if NOT already in the content

					if (!empty($event['DESCRIPTION'])) {
						if (stristr($event['DESCRIPTION'], $item['url']))
						return;  // if=gnore it if already in content
						if ((!empty($item['thumb'])) and
							(stristr($event['DESCRIPTION'], $item['thumb']))) return;
					}
					else if (!empty($item['thumb'])) {
						$item_title = '<img alt="'.$item_title
						.'" src="'.esc_url($item['thumb']).'" />';
					}
				}
				else if ($typeparts[0] === 'text') { }
// do not do others for now - may not make sense to do , other than text

			}

			if (!empty($item['url'])) {

				$tmp = apply_filters('amr_attachment_title', array('title'=>$item_title , 'event'=>$event));
				$item_title =$tmp['title'];

				$hrefhtml .= '<a class="ics_attachment" href="'
				.$item['url']
				.'" title="'
				.__('Event attachment','amr-ical-events-list').'" >'
				.$item_title.'</a>';
			}
			else $hrefhtml .= $item_title;

		if (!empty($hrefhtml)) return ($hrefhtml);
	}
}
/* -------------------------------------------------------------------------------------------*/
if (!function_exists('amr_format_binary'))  {
	function amr_format_binary ($name,$binary) {
// getting error - data not in recognised format in the binary, so skip for now till someone wants it
		return null;
				$im = imagecreatefromstring($binary);
				$filename = ICAL_EVENTS_CACHE_LOCATION.'/ical-events-cache/'.$name.'.jpg';
				// Save the image as 'simpletext.jpg'
				imagejpeg($im, $filename);
				$uploads = wp_upload_dir();
				$url = $uploads[base_url].'/ical-events-cache/'.$name;
				return($url);
	}
}
/* --------------------------------------------------------- */
if (!function_exists('amr_format_allday') ) {
	function amr_format_allday ($content) {
		if ($content == 'allday')
			return (_x('all&nbsp;day', 'when an event runs for full days, note the &nbsp; prevents the text wrappping in a table.','amr-ical-events-list'));
		else return ('');
	}
}
/* ------------------------------------------------------------------------------------*/
if (!function_exists('amr_format_taxonomy_link') ) {  //problem ics file categories are string not array ? so skip?
	function amr_format_taxonomy_link ($tax_name,$tax_term, $link='') {
	global $amr_calendar_url;
	
	if (empty($amr_calendar_url)) 
		$amr_calendar_url = amr_clean_link();
	if (empty($link) and (!empty($amr_calendar_url)) )
		$link = $amr_calendar_url;
	$link2 = add_query_arg ($tax_name, $tax_term, $link);
	$html = '<a href="'.htmlspecialchars($link2).'" title="'
			.sprintf(__('View events in %s %s','amr-ical-events-list'),
			__($tax_name,'amr-events'), $tax_term).'">'
			.$tax_term.'</a>';
	return ($html);
	}
}

/* ------------------------------------------------------------------------------------*/

if (!function_exists('amr_format_taxonomies') ) {  //problem ics file categories are string not array ? so skip?
	function amr_format_taxonomies ($tax_name, $tax_array, $link='' ) {

	if (!is_array($tax_array) and (stristr($tax_array,',')))  // if it is a string like in the icsfile, convert to an array 
		$tax_array = explode(',',$tax_array);
	

	foreach ($tax_array as $i => $t) {
		if (is_array($t)) {
			foreach ($t as $i2 => $t2) {
				$links[] = amr_format_taxonomy_link ($tax_name, $t2);
			}
		}
		else
			$links[] = amr_format_taxonomy_link ($tax_name, $t);
		}
	$html = implode(',',$links);


	return( $html);

	}
	
}


/* --------------------------------------------------  */
if (!function_exists('amr_derive_calprop_further')) {
function amr_derive_calprop_further (&$p) {
	global $amr_options;
	if (isset ($p['totalevents'])) $title = __('Total events: ', 'amr-ical-events-list').$p['totalevents'];	/* in case we have noename? ***/
	if (isset ($p['X-WR-CALDESC'])) {
		$p['X-WR-CALDESC'] = nl2br2 ($p['X-WR-CALDESC']);
		$desc = __($p['X-WR-CALDESC'],'amr-ical-events-list');  //allow translation of whatever value is in the ics file

	}
	else $desc = __('No description available','amr-ical-events-list');

	if (isset ($p['X-WR-CALNAME'])) {
		$p['X-WR-CALNAME'] = __($p['X-WR-CALNAME'],'amr-ical-events-list');
	}
	if (isset ($p['icsurl']))  {/* must be!! */
		$p['addtogoogle'] = add_cal_to_google ($p['icsurl']);
		if (isset ($p['X-WR-CALNAME'])) {
				$p['subscribe'] = sprintf(__('Subscribe to %s Calendar','amr-ical-events-list'),
				htmlentities ($p['X-WR-CALNAME']));
				$p['X-WR-CALNAME'] = '<a class="x-wr-calname" '
				.' title="'.$p['subscribe'].'"'
				.' href="'.htmlentities($p['icsurl']).'">'
				.htmlspecialchars($p['X-WR-CALNAME'])
				.'</a><!-- '.$desc.' -->';
		}
		else { // if we do not have a name, use the basename of the url
				$f = basename($p['icsurl'], ".ics");
				$p['subscribe'] = sprintf(__('Subscribe to %s Calendar','amr-ical-events-list'), $f);
				$p['X-WR-CALNAME'] = '<a '
				.' title="'.$p['subscribe'].'"'
				.' href="'.htmlentities($p['icsurl']).'">'
				.$f
				.'</a>';
		}
		$t = __('Subscribe to calendar in your calendar application.', 'amr-ical-events-list');
		if (isset ($amr_options['no_images']) and $amr_options['no_images'])
			$t3 = $t = __('Subscribe to calendar', 'amr-ical-events-list');
		else
			$t3 = '<img class="subscribe amr-bling" src="'.IMAGES_LOCATION.CALENDARIMAGE.'" title= "'.$t.'" alt="'.$t.'" />';

		$p['icsurl'] =
			'<a class="amr-bling icalsubscribe" title="'.$p['subscribe']
			.'" href="'.htmlentities($p['icsurl']).'">'
			.$t3.'</a>';
		}
		$p['icalrefresh'] = amr_show_refresh_option();
		return ($p);
	}
}

/* --------------------------------------------------------- */
if (!function_exists('amr_derive_summary')) {
	function amr_derive_summary (&$e ) {
		global $amr_options;
		global $amr_listtype;
		global $amrW;
		global $amrwidget_options;
		global $amr_liststyle;
	/* If there is a event url, use that as href, else use icsurl, use description as title */
		if (in_array($amr_liststyle, array('smallcalendar', 'largecalendar','weekscalendar'))) $hoverdesc = false;
		else {

			if (empty($amrW)) $hoverdesc = false;
			else if ($amrW == 'w_no_url') $hoverdesc = false;
			else $hoverdesc ='maybe';
		}

		if (!empty($e['excerpt'])) {
			$e['excerpt'] = (amr_just_flatten_array ($e['excerpt'] ));
		}
		if (isset($e['SUMMARY'])) $e['SUMMARY'] = (amr_just_flatten_array ($e['SUMMARY'] ));
	//	if (isset($e['SUMMARY'])) $e['SUMMARY'] = htmlspecialchars(amr_just_flatten_array ($e['SUMMARY'] ));
		else return ('');
		if (isset($e['URL'])) $e_url = amr_just_flatten_array($e['URL']);
		else $e_url = '';
		/* If not a widget, not listype 4, then if no url, do not need or want a link */
		/* Correction - we want a link to the bookmark anchor on the calendar page***/
		if (empty($e_url))  {
			if (!($amrW == 'w_no_url'))  {
				if (!empty($amrwidget_options['moreurl'])) {
					$e_url = ' href="'.clean_url($amrwidget_options['moreurl'])
	//				.'#'.$e['Bookmark']
					.'" ';
				}
				else {
					if (!empty($amr_options['listtypes'][$amr_listtype]['general']['Default Event URL'])) {
						$e_url = ' class="url" href="'
							.clean_url($amr_options['listtypes'][$amr_listtype]['general']['Default Event URL']).'" ';
						}
					else $e_url = ''; /*empty anchor as defined by w3.org */
					/* not a widget */
				}
			}
			else {return ($e['SUMMARY']);	}
		}
		else {
			$e_url = ' class="url" href="'.esc_url($e_url).'" ' ;
		}
		$e_desc =  '';
		if ($hoverdesc) {
			if (isset ($e['DESCRIPTION'])) {
				$e_desc = amr_just_flatten_array($e['DESCRIPTION']);
				}
		    if (!empty($e_desc)) {
				$e_desc = 'title="'.htmlspecialchars(str_replace( '\n', '  ', (strip_tags($e_desc)))).'"';
			}
		}
		else {
			if (!empty ($e['excerpt'])) {
				$e_desc = strip_tags($e['excerpt']);
				$e_desc = ' title="'.$e_desc.'" ';
				}
			else
				$e_desc = ' title="'.$e['SUMMARY'].' - '.__('More information', 'amr-ical-events-list').'" ';
			}
		if (!empty ($e_url))
			$e_summ = '<a '.$e_url.$e_desc.'>'. $e['SUMMARY'].'</a>';
		else $e_summ = $e['SUMMARY'];
		return( $e_summ );
	}
}
/*--------------------------------------------------------------------------------*/
if (!function_exists('add_cal_to_google')) {
	function add_cal_to_google($cal) {
	global $amr_options;
	/* adds a button to add the current calemdar link to the users google calendar */
		$text1 = __('Add to google calendar', 'amr-ical-events-list');
		if (isset ($amr_options['no_images'])  and $amr_options['no_images'])
			$text2 = __('Add to google', 'amr-ical-events-list');
		else
			$text2 = '<img src="'.IMAGES_LOCATION.ADDTOGOOGLEIMAGE.'" title="'.$text1.'" alt="'.$text1.'" class="amr-bling" />';
		return (
		'<a class= "amr-bling addtogoogle" href="http://www.google.com/calendar/render?cid='.htmlentities($cal).'" target="_blank"  title="'.$text1.'">'.$text2.'</a>');
	}
}
/*--------------------------------------------------------------------------------*/
if (!function_exists('add_event_to_google')) {
	function add_event_to_google($e) {
	global $amr_options;

		if (!isset($e['EventDate'])) return('');
		if (isset($e['LOCATION'])) $l = 	'&amp;location='.esc_html(strip_tags(str_replace(' ','%20',($e['LOCATION'] ))));
		else $l = '';
		if (!isset($e['DESCRIPTION'])) $e['DESCRIPTION'] = '';
		$t = __("Add event to google" , 'amr-ical-events-list');

		if (isset ($amr_options['no_images']) and $amr_options['no_images']) $t2 = $t;
		else $t2 = '<img src="'.IMAGES_LOCATION.ADDTOGOOGLEIMAGE.'" alt="'.$t.'" class="amr-bling"/>';
		$details = amr_just_flatten_array ($e['DESCRIPTION']); //var_dump($details);
		if (!empty($details)) $details ='&amp;details='.rawurlencode(strip_tags($details));

	/* adds a button to add the current calemdar link to the users google calendar */
		$html = '<a class= "amr-bling hrefaddtogoogle" href="http://www.google.com/calendar/event?action=TEMPLATE'
		.'&amp;text='.str_replace(' ','%20',esc_html(strip_tags(amr_just_flatten_array ($e['SUMMARY']))))
		/* dates and times need to be in UTC */
		.'&amp;dates='.amr_get_googleeventdate($e)
		.$l
		.'&amp;trp=false'
		.$details
		.'" target="_blank" title="'.$t.'" >'.$t2.'</a>';
		return ($html);/* Note google only allows simple html*/
	}
}
/* --------------------------------------------------  */
if (!function_exists('amr_show_refresh_option')) {
	function amr_show_refresh_option() {
	global $amr_globaltz, $amr_lastcache, $amr_options, $amr_last_modified;
		$uri = add_query_arg(array('nocache'=>'true'), $_SERVER['REQUEST_URI']);
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
		return ( '<a class="refresh amr-bling" href="'.htmlentities($uri).'" title="'.$text.' '.$text2.'">'.$t3.'</a>');
	}
}
/* --------------------------------------------------  */
if (!function_exists('amr_list_properties')) {
	function amr_list_properties($icals, $tid, $class) {  /* List the calendar properties if requested in options  */
	global $amr_options,
		$amr_liststyle,
		$amr_listtype;
/* --- setup the html tags ---------------------------------------------- */

	if ($amr_liststyle === 'custom') {  // get the stored file uirl, if it does not exist, set to table
		$custom_htmlstyle_file = amr_get_htmlstylefile();
		if (empty ($custom_htmlstyle_file ) )
			$amr_liststyle = 'table';
	}

	switch ($amr_liststyle) {
	case 'list' :
		$d ='<span ';
		$dc='</span>';
		$r   = '<div>';
		$rc  = '</div>';
		$box = '<div';
		$boxc= '</div>';
		break;
	case 'breaks' :
		$d ='<span ';
		$dc ='</span>';
		$r   = '<span>';
		$rc  = '</span>';
		$box = '<div';
		$boxc= '</div>';
		break;
	case 'table':
	case 'HTML5table':  // still using a table, so columns will work  but with html5 elements too
		$d 	='<td';
		$dc	='</td>';
		$r   = '<tr> ';
		$rc  = '</tr> ';
		$box = '<table';
		$boxc= '</table>';
		break;
	case 'HTML5':
		$box 	= '<section';
		$boxc	= '</section>';
		$r   	= '<header><h2>';
		$rc  	= '</h2></header> ';
		$d  	= '';
		$dc		= '';
		break;
	case 'custom':
		$where_am_i = 'in_calendar_properties';
		include ($custom_htmlstyle_file);
		break;
	default:  /* the old way or tableoriginal*/
		$r   = '<tr> ';  $d ='<td';
		$rc  = '</tr> '; $dc='</td>';
		$box = '<table';
		$boxc= '</table>';
	}
	$html = '';

	$columns = prepare_order_and_sequence  ($amr_options['listtypes'][$amr_listtype]['calprop']);
	if (!($columns)) return;

//	if (!($order)) return;
	foreach ($icals as $i => $p)	{ /* go through the options list and list the properties */
		amr_derive_calprop_further ($icals[$i]);

		foreach ($columns as $col => $data) {

//			echo '<br />'.$col.' '; var_dump($data);

			$cprop = '';
			foreach ($data as $k => $v) {
				if (!empty ($icals[$i][$k])) {/*only take the fields that are specified in options  */
					$cprop .= amr_format_value($icals[$i][$k], $k,
						$icals[$i], $v['Before'], $v['After'] );
				}
			}
			if (empty($cprop)) $cprop = '&nbsp;';
			if (!empty($d))  // if we have a td type html to bracket the column with
				$cprop = $d.' class="col'.$col.'">'.$cprop.$dc;
			$cprop .= AMR_NL;


		} // end of columns for one calendar
		$html .= $r.$cprop.$rc.AMR_NL;
	} // end of calendars

	if (!(empty($html)) ) {
			$html  =
			((!empty($box)) ? $box.' id="'.$tid.'" class="'.$class.'">' : '')
			.$html
			.((!empty($boxc)) ? $boxc :  '');
		}

	return ($html);
}
}
/* -------------------------------------------------------------------------------------------*/

if (!function_exists('amr_format_grouping') ) {
	function amr_format_grouping ($grouping, $datestamp) {
	/* check what the format for the grouping should be, call functions as necessary*/
	global $amr_options;
	global $amr_listtype;
	global $amr_formats;
		if (in_array ($grouping ,array ('Year', 'Month', 'Day')))
			return (amr_format_date( $amr_options['listtypes'][$amr_listtype]['format'][$grouping], $datestamp));
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
	}
}
/* --------------------------------------------------  */
if (!function_exists('amr_list_events') ) {
function amr_list_events($events,  $tid, $class, $show_views=true) {
	global $wp_locale,
		$locale,
		$amr_options,
		$amr_limits,
		$amr_listtype,
		$amr_liststyle,
		$amr_current_event,
		$amrW,
		$amrtotalevents,
		$amr_globaltz,
		$amr_groupings,
		$change_view_allowed;

	if (ICAL_EVENTS_DEBUG) {
		echo '<br />Peak Memory So far :'.amr_memory_convert(memory_get_usage(true));
		echo '<h2>Now Listing, and locale = '.$locale.' and list type = '.$amr_listtype.'</h2>';
	}

	if (!defined('AMR_NL')) define('AMR_NL','PHP_EOL');
		/* we want to maybe be able to replace the table html for alternate styling - may need to  keep the li items though */
	$amrconstrainedevents = count($events);
	$html = '';

	if (in_array ($amr_liststyle, array('smallcalendar','largecalendar','weekscalendar'))) {
		/* is it a calendar box we want - handle separately */
			if (ICAL_EVENTS_DEBUG) echo '<br />Do calendar<br>';
			$html = amr_events_as_calendar($amr_liststyle, $events, $tid, $class);
			return($html);
		}
/* ----------- check for groupings and compress these to requested groupings only */
		$g = amr_get_groupings_requested ();
		if (!empty($g)) {
			foreach ($g as $gi=>$v) {
				$new[$gi] = $old[$gi] = '';
			}
		} /* initialise group change trackers */

		$columns = prepare_order_and_sequence ($amr_options['listtypes'][$amr_listtype]['compprop']);

		if (!$columns) {
			if (ICAL_EVENTS_DEBUG) {echo '<h2>Columns to show</h2>';	var_dump($columns);}
			return;
		}
		else $no_cols = count($columns);

/* --- setup the html tags ---------------------------------------------- */

	if ($amr_liststyle === 'custom') {  // get the stored file uirl, if it does not exist, set to table
		$custom_htmlstyle_file = amr_get_htmlstylefile();
		if (empty ($custom_htmlstyle_file ) )
			$liststyle = 'table';
	}
	switch ($amr_liststyle) {
	case 'list' :
		$ul 	= ''; $li = '';
		$ulc	= ''; $lic = '';
//		$ul 	= '<span '; 	$li = '<span ';
//		$ulc	= '</span>'; 	$lic = '</span> ';
		$row 	= '<li ';
		$rowc 	= '</li>'.AMR_NL;
		$hcell	= '<span  ';
		$cell 	= '<span '; /* allow for a class specifictaion */
		$hcellc = '</span>';
		$cellc 	= '</span>';
		$grow	= '<li ';
		$growc  = '</li>'.AMR_NL;
		$ghcell = '<span ';
		$ghcellc= '</span>'.AMR_NL;
		$head 	= '<div> '; 		//closed
		$headc 	= '</div>'.AMR_NL;
		$foot 	= '<div> ';  //closed
		$footc 	= '</div>'.AMR_NL;
		$body 	= '<ul '; // open
		$bodyc 	= '</ul>'.AMR_NL;
		$box 	= AMR_NL.'<div ';
		$boxc 	= '</div>'.AMR_NL;
		break;
	case 'table':
		$ul 	= '<div '; 	$li = '<div '; // need these if we want details to hover
		$ulc	= '</div>'; $lic = '</div>';
		$row 	= '<tr '; 	$hcell	='<th '; 	$cell 	='<td '; /* allow for a class specifictaion */
		$rowc 	= '</tr> '; $hcellc ='</th>'; 	$cellc 	='</td>';
		$grow	= '<tr ';	$ghcell  = '<th colspan="'.$no_cols.'"'; $ghcellc = $hcellc;
		$growc  ='</tr>'.AMR_NL;
		$head 	= '<thead>';
		$headc 	= '</thead>';
		$foot 	= '<tfoot>';
		$footc 	= '</tfoot>';
		$body 	= AMR_NL.'<tbody '; //open
		$bodyc 	= AMR_NL.'</tbody>'.AMR_NL;
		$box 	= '<table';
		$boxc 	= '</table>'.AMR_NL;
		break;
	case 'HTML5table' :
		/* historical - will fall away one day */
		$ul 	= ''; $li = '';
		$ulc	= ''; $lic = '';
		/* allow for a class specifictaion */
		$row 	= '<tr ';
		$rowc 	= '</tr> ';
		$hcell	='<th ';
		$hcellc ='</th>';
		$cell 	='<td ';
		$cellc 	='</td>';
		$grow	= '<tr ';
		$ghcell = '<th colspan="'.$no_cols.'"';
		$ghcellc = $hcellc;
		$growc  ='</tr>'.AMR_NL;
		$head 	= AMR_NL.'<thead>';
		$headc 	= '</thead>'.AMR_NL;
		$foot 	= AMR_NL.'<tfoot>';
		$footc 	= '</tfoot>'.AMR_NL;
		$body 	= AMR_NL.'<tbody '; //open
		$bodyc 	= AMR_NL.'</tbody>'.AMR_NL;
		$box 	= AMR_NL.'<table';
		$boxc 	= '</table>'.AMR_NL;
		break;
	case 'HTML5' :
		/* historical - will fall away one day */
		$ul 	= ''; $li = '';
		$ulc	= ''; $lic = '';
		/* allow for a class specifictaion */
		$row 	= '<article '; 	 // each event
		$rowc 	= '</article>'.AMR_NL;
		$hcell	='<h2 '; 	// the 'column' header cell
		$hcellc ='</h2>';
		$cell 	='';
		$cellc 	='';
//
		$grow	 = '<header><h3 ';	// the grouping html text for a group of events - not the surrounding selector
		$growc   = '</h3></header>'.AMR_NL;
		$ghcell  = '';
		$ghcellc = '';
//
		$head 	= '<h2 ';
		$headc 	= '</h2>';
		$foot 	= '<div ';
		$footc 	= '</div>';
//
		$body 	= '<section ';	// the grouping html text for a group of events - not the surrounding selector
		$bodyc 	= '</section>'.AMR_NL;
//
		$box 	= '<section';  // the whole calendar
		$boxc 	= '</section>'.AMR_NL;
		break;
	case 'breaks' :
		$ul 	= ''; 	$li = '';
		$ulc	= ''; 	$lic = '';
		$row 	= '';
		$rowc 	= '';
		$hcell	='<div ';
		$hcellc ='</div>&nbsp;';
		$cell 	='<div '; /* allow for a class specifictaion */
		$cellc 	='</div>';
		$grow	= '<div ';
		$growc  ='</div>'.AMR_NL;
		$ghcell = $hcell;
		$ghcellc= $hcellc;
		$head 	= '<div> ';
		$headc 	= '</div>'.AMR_NL;
		$foot 	= '<div> ';
		$footc 	= '</div>'.AMR_NL;
		$body 	= AMR_NL.'<div '; //open
		$bodyc 	= '</div>'.AMR_NL;
		$box 	= AMR_NL.'<div';
		$boxc 	= '</div>'.AMR_NL;
		break;
	case 'custom':
		$where_am_i = 'in_events';
		$ul 	= ''; 	$li = ''; // we will phase these out eventually
		$ulc	= ''; 	$lic = '';
		include ($custom_htmlstyle_file);
	break;

	default:  /* the old way or tableoriginal*/
		$ul 	= '<ul';	$li = '<li';
		$ulc	= '</ul>';	$lic = '</li>';
		$row 	= '<tr '; 				$hcell	='<th '; 	$cell 	='<td '; /* allow for a class specifictaion */
		$rowc 	= '</tr> '; 			$hcellc ='</th>'; 	$cellc 	='</td>';
		$ghcell = '<th colspan="'.$no_cols.'"';
		$grow	= '<tr ';	        $growc  ='</tr>';
        $ghcellc= $hcellc;
		$head 	= AMR_NL.'<thead>'; 	$foot 	= AMR_NL.'<tfoot>'; 	$body 	= AMR_NL.'<tbody ';
		$headc 	= AMR_NL.'</thead>'; 	$footc 	= AMR_NL.'</tfoot>'; 	$bodyc 	= AMR_NL.'</tbody>';
		$box 	= AMR_NL.'<table';
		$boxc 	= '</table>'.AMR_NL;
	}

	/* -- show view options or not  ------------------------------------------*/
	if ((isset($amr_limits['show_views']))
	and ($amr_limits['show_views']) and $change_view_allowed) {
		$views = amrical_calendar_views();
	}
	else $views = '';
	/* -- show month year nav options or not  ----------------NOT IN USE - need to lift code out for reuse --------------------------*/

	$start    = new Datetime('now',$amr_globaltz);
	$start    = clone $amr_limits['start'];	
	$navigation = '';
//	if (ICAL_EVENTS_DEBUG) {
//	echo '<br />Limit parameters '; var_dump($amr_limits);
//	}
	if ((isset($amr_limits['show_month_nav']))
	and ($amr_limits['show_month_nav']) ) {
		if (isset ($amr_limits['months']))	$months = $amr_limits['months'];
		else $months = 1;
//		$start    = new Datetime('now',$amr_globaltz);
//		$start    = clone $amr_limits['start'];
		$navigation = amr_calendar_navigation($start, $months, 0, $amr_liststyle); // include month year dropdown	with links
		$navigation = '<div class="calendar_navigation">'.$navigation.'</div>';
	}
	else {
		if ((isset($amr_limits['month_prev_next'])) and $amr_limits['month_prev_next'] 
		and function_exists('amr_do_month_prev_next_shortcode')) {
			$navigation .= amr_do_month_prev_next_shortcode();
		}
		if ((isset($amr_limits['month_year_dropdown'])) and $amr_limits['month_year_dropdown'] 
		and function_exists('amr_month_year_navigation')) {
			$navigation .= amr_month_year_navigation($start);		
		}

	}

/* -- heading and footers code ------------------------------------------*/

	if (ICAL_EVENTS_DEBUG) {echo '<br />Limit parameters '; var_dump($amr_limits);}
	if (isset($amr_limits['headings'])) $doheadings = $amr_limits['headings'];
	else $doheadings = true;
	if (isset($amr_limits['pagination'])) $dopagination = $amr_limits['pagination'];
	else $dopagination = true;

	$headhtml = '';
	if ($doheadings) {
		$docolheading=false;

		foreach ($amr_options['listtypes'][$amr_listtype]['heading'] as $i => $h) {
			if (!empty($h)) $docolheading=true;
		}
		if ($docolheading) { 	if (ICAL_EVENTS_DEBUG) {echo '<br />Do col headings '; }

			foreach ($columns as $i => $col) {
				if (isset($amr_options['listtypes'][$amr_listtype]['heading'][$i]))
					$colhead = __($amr_options['listtypes'][$amr_listtype]['heading'][$i],'amr-ical-events-list');
				else
					$colhead = '&nbsp;';
				$headhtml .=
					((!empty($hcell)) ? $hcell.' class="amrcol'.$i.'">': '')
					.$colhead
					.((!empty($hcellc)) ? $hcellc : '');
			}
			$html .= $head
			.(!empty($row) ? ($row.'>'): '')
			.$headhtml
			.(!empty($rowc) ? $rowc : '')
			.$headc;
		}
	}


/* ***** with thechange in list types, we have to rethink how we do the footers .... for tables we say the footers up front, but for others not. */
		$fhtml = '';
		if ((isset($amr_options['ngiyabonga']) and ($amr_options['ngiyabonga'])))
			$fhtml .= amr_ngiyabonga();
		else
			$fhtml .='<!-- event calendar by anmari.com.  See it at icalevents.com -->';
		
		if ((isset($amr_limits['show_look_more'])) and ($amr_limits['show_look_more'])) {
				$fhtml .= amr_show_look_more();
		}	
		if ((!empty($amr_limits)) and ($amrtotalevents > $amrconstrainedevents) ) {
			if ($dopagination and function_exists('amr_semi_paginate'))
				$fhtml .= amr_semi_paginate();
			if (function_exists('amr_ical_edit'))
				$fhtml .= amr_add_new_event_link();
		}

		$alt = false;
/* -- body code ------------------------------------------*/
		if ((!is_array($events)) or (count($events) < 0 )) return ('');
		$groupedhtml = '';
		$changehtml = '';
		$startallgroups = true;
		foreach ($events as $i => $e) { /* for each event, loop through the properties and see if we should display */
			amr_derive_component_further ($e);
			if (isset($_GET['debugevent'])) var_dump($e);
			if (!empty($e['Classes']))
				$classes = strtolower($e['Classes']);
			else $classes = '';
			$eprop = ''; /*  each event on a new list */
			$prevcol = 0;
			$colcount = 0;
			$col = 1; /* reset where we are with columns */

			$rowhtml = '';
			foreach ($columns as $col => $order) {  // prepare the row
				$eprop = '';
				foreach ($order as $k => $kv) { /* ie for one column in event, check how to order the bits  */
					/* Now check if we should print the component or not, we may have an array of empty string */
					if (isset($e[$k]))
						$v = amr_check_flatten_array ($e[$k]); // *** huh? shoudl we do this here?
					else
						$v =null;

					$selector = $li;
					$selectorend = $lic;
					if (!empty($selector)) 	$selector .=' class="'.strtolower($k).'">';
					if (!empty($v)) {
						$eprop .= $selector
							.amr_format_value($v, $k, $e,$kv['Before'],$kv['After'] )
							.$selectorend;
					}
				} 

				if (empty($eprop)) $eprop = '&nbsp;';  // a value for a dummytable cell if tere were no values in the column
					
				// annoying but only way to pass variables by reference is through an array, must return array to then.	
				// so to allow filter of column and pass which column it is, thsi is how we do it
				$tmp = apply_filters('amr_events_column_html', 
					array('colhtml'=>$eprop, 'col'=>$col));
				$eprop = $tmp['colhtml'];
				
				if (!empty($ul))  // will phase the ul's  out eventually
					$eprop = $ul.' class="amrcol'.$col.' amrcol">'
					.$eprop
					.$ulc;
					
				/* each column in a cell or list */
				$cellclasses = '';
				if (!empty($cell) ) { // if we have a selector that surounds each property , then add classes.
					$cellclasses .= ' amrcol'.$col;
					if ($col == $no_cols) $cellclasses .= ' lastcol'; /* only want the cell to be lastcol, not the row */
					$thiscolumn = $cell.' class="'.$cellclasses.'">' .$eprop. (empty($cellc) ? '' : $cellc);
				}
				else $thiscolumn = $eprop;
	
				$rowhtml .= $thiscolumn; // build up the row with each column 
			} // end row

//			if (!($eprop === '')) { /* ------------------------------- if we have some event data to list  */

				/* -------------------------- Check for a grouping change, need to end last group, if there was one and start another */
				$changehtml = '';
//				$changehtml = $rowhtml;
				$groupclass = '';
				if (!empty($g) and ($g)) {  // if there is a already
					foreach ($g as $gi=>$v) {
						if (isset($e['EventDate']))
							$grouping = amr_format_grouping($gi, $e['EventDate']) ;
						else
							$grouping = '';
						$new[$gi] = amr_string($grouping);
						if (!($new[$gi] == $old[$gi]))	{   // if there is a change of group
						    /* we have a new group  */
							$id = amr_string($gi.$new[$gi]);
							$changehtml =
								((!empty($grow)) ? $grow.'class="group '.$gi.'">' : '')
								.((!empty($ghcell)) ? $ghcell.' class="group '.$gi. '" >' : '')
								.$grouping
								.$ghcellc
								.$growc;
							// allow the row to be filterd to maybe add a column								
							$tmp = apply_filters ('amr_events_event_html', array('rowhtml'=>$rowhtml, 'event'=>$e));	
							$rowhtml = $tmp['rowhtml'];
							// end filter
							$changehtml .=  // start a new body with the row we just processed that flagged that we had a change of group
								((!empty($body)) ? $body.' class="'.$gi.'"> ' : '')
								.(!empty($row) ? ($row.($alt ? ' class="odd alt':' class="').$classes.'"> ') : '')
								.$rowhtml
								.$rowc;
							$rowhtml = '';
							if ($alt) $alt=false;
							else $alt=true;
//
							$old[$gi] = $new[$gi];

							if (!$startallgroups) {// 	ie if we already had some going
								$html .= $bodyc.'<!-- end group that just changed -->'; // finish off the group that just changed
							}
							//else  we are just starting, so no need to finish off
						}
					}
					$startallgroups = false;
					// so now we have finished off any changed groups, so now we can add the new bit in
					$html .= $changehtml;

				}
				
				if ($startallgroups) { // there were no groups, so we have no opening body
					$html .= $body.'>';
					$startallgroups = false;
				}

				// so now we havefinsihed that group, start next
				// save the event or row,  for next group
				if (!empty($rowhtml)) {
					$tmp = apply_filters('amr_events_event_html', array('rowhtml'=>$rowhtml, 'event'=>$e));
					$rowhtml = $tmp['rowhtml'];
					$rowhtml = (!empty($row) ? ($row.($alt ? ' class="odd alt':' class="').$classes.'"> ') : '')
					.$rowhtml
					.$rowc;

				if ($alt) $alt=false;
				else $alt=true;

				$html .= $rowhtml;		/* build  the group of events , adding on eprop */
				$rowhtml = '';
				}
			
			}
			//end of row or event
		// finish off each group as there will not have been a change ?
		if (!empty($g) and ($g)) {
			foreach ($g as $gi=>$v) {
				$html .= $bodyc.'<!-- end grouping '.$gi.'-->';
			}
		}
		else {	// there may have been  no  grouping but still events
			$html .= $bodyc.'<!-- end if no grouping -->';
		}

	if (!empty ($tid)) {
		$tid = ' id="'.$tid.'" ';
		}
	$html = ((!empty($box)) ? ($box.$tid.' class="'.$class.'">') : '')
		.$html
		.$boxc
		.$fhtml;

	$html =
		$views.AMR_NL
		.$navigation.AMR_NL
		.$html.AMR_NL;

	return ($html);
	}

}

/* --------------------------------------------------  */
if (!function_exists('amr_show_look_more')) {  // does a google style next
function amr_show_look_more() {
 	global $amr_limits,
	$amr_options,
	$amr_formats,
	$amr_last_date_time;
	
	$next = new datetime();
	if (!empty($amr_last_date_time)) $next = clone $amr_last_date_time; // get  last used event date
	else {
		$amr_last_date_time = $amr_limits['end'] ;
		$next = $amr_limits['end'] ;
	}
	date_time_set($next,0,0,0); // set to the beginning of the day
	$nextd = $next->format("Ymd");
	$nexturl = (add_query_arg (array ('start'=>$nextd )));
	
	// if no events, then this makes no sense  $explaint = sprintf (__('Displaying %s events.'),$amr_limits['events']) ;
	$explaint = '';
	// due to events limit, it may not show all events in a given day, so do not say displaying until date,
	// rather just start the next display from that last date - may be a few events that overlap.
		
	foreach ($amr_limits as $i=>$value) {
		if (in_array ($i, array('days','hours','months','weeks'))) {
			$nexturl = add_query_arg ($i, $value, $nexturl);
		}
	}
	// NB MUST increase the number of events otherwise one can get caught in a situation where if num of evemnts less than events in a day, one can never get past that day.
	$nexturl = add_query_arg ('events', $amr_limits['events']*2, $nexturl);
	
	if (empty($amr_options['lookmoremessage'])) $moret    =  __('Look for more', 'amr-events');
	else $moret    = $amr_options['lookmoremessage'];
	$morett    = sprintf( __('Look for more from %s' ,'amr-events'),$amr_last_date_time->format($amr_formats['Day']));
	
	if (!empty($_GET['start'])) {
		$reset = __('Reset','amr-events' );
		$reseturl = remove_query_arg(array('start','startoffset','events','days','months','hours','weeks'));
		$reset ='&nbsp;<a id="icalareset"  title="'
		.__('Go back to initial view' ,'amr-events')
		.'" href="'.esc_attr($reseturl).'">'.$reset.'</a>';
	}
	else $reset = '';
	return (
		'<div id="icallookmore" class="icalnext" >'
		
		.'<a id="icalalookmore"  title="'.$explaint.' '.$morett.'" href="'.esc_attr($nexturl).'">'.$moret.'</a>'
		.$reset
		.'</div>'
		);
	}
}

/* --------------------------------------------------------- */
if (!function_exists('amr_format_organiser')) {
	function amr_format_organiser ($org) {/* receive array of hopefully CN and MAILTO, and possibly SENTBY */
	//	If (ICAL_EVENTS_DEBUG) {echo '<br />Organiser array:    '; var_dump($org);}
		$text = '';
	//	if (!(is_array($org))) $org = amr_parseOrganiser('ORGANIZER;'.$org);  // may not have been parsed yet (eg in wp events)
	//	var_dump($org);
		if (!empty ($org['CN'])) {
			if (!empty  ($org['MAILTO']))
			$text = '<a href="mailto:'.$org['MAILTO'].'" >'.$org['CN'].'</a>';
			else $text = $org['CN'];
		}
		else {
			if (!empty  ($org['MAILTO'])) $text = '<a href="mailto:'.$org['MAILTO'].'" >'.$org['MAILTO'].'</a>';
		}
		if (!empty ($text)) $text .= '&nbsp;';
		if (!empty ($org['SENT-BY'])) {
			$text .= __('Sent by ','amr-ical-events-list').'<a href="mailto:'.$org['SENT-BY'].'" >'.$org['SENT-BY'].'</a>';
		}
		return($text);
	}
}

/* --------------------------------------------------------- */
if (!function_exists('adebug')) {  // we are loading late, so hope fully this should be fine - don'twant top long a name
	function adebug( $text, $whattodebug=true) {
		if ((isset ($_REQUEST['debug']) ) and ($_REQUEST['debug'] == $whattodebug))
			echo $text;
	}
}