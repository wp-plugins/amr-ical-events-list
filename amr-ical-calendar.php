<?php 
/**
 * Display calendar with days that have posts as links.
 *
 * The calendar is cached, which will be retrieved, if it exists. If there are
 * no posts for the month, then it will not be displayed.
 *
 * @since 1.0.0
 *
 * @param bool $initial Optional, default is true. Use initial calendar names.
 * @param bool $echo Optional, default is true. Set to false for return.
 */
// ----------------------------------------------------------------------------------------
function amr_get_day_link($thisyear, $thismonth, $thisday, $link) { /* the start date object  and the months to show */
	$link = add_query_arg( 'days', '1' ,$link);
	$link = add_query_arg( 'start', $thisyear.str_pad($thismonth,2,'0',STR_PAD_LEFT).str_pad($thisday,2,'0',STR_PAD_LEFT), $link );	
return ($link);
}
// ----------------------------------------------------------------------------------------
function amr_monthyeardrop_down($startpassed) {
global $wp_locale, $amr_globaltz;
//	$m = isset($_GET['m']) ? (int)$_GET['m'] : 0;  // actually yyyymm
	$start = isset($_GET['start']) ? (int)$_GET['start'] : $startpassed;
	$startobj = new datetime($start,$amr_globaltz);
	$ym = (int) substr($start, 0, 6);
	$m  = (int) substr($start, 4, 2);
	$html = '';
	$options=array();
	date_modify($startobj, '-3 months');
	for ($i=1; $i<=24; $i=$i+1) {
		$startstring = $startobj->format('Ymd');
		$m = (int) substr($startstring, 4, 2);
		$y = (int) substr($startstring, 0, 4);
		$options[$startstring] = $wp_locale->get_month($m).' '.$y;
		date_modify($startobj,'+1 month');
	}
	$html .= amr_simpledropdown('start', $options, $start);
	return($html);
}

// ----------------------------------------------------------------------------------------
function amr_clean_link() { /* the start date object  and the months to show */
	$link = remove_query_arg(array(
	'months',
	'hours',
	'start',
	'startoffset',
	'hoursoffset',
	'eventoffset',
	'monthsoffset'));
	return ($link);
}	
// ----------------------------------------------------------------------------------------
function amrical_get_month_link($start, $months, $link) { /* the start date object  and the months to show */
	$link = (add_query_arg( 'start', $start, $link ));
	$link = (add_query_arg( 'months', $months, $link ));
return ($link);
}
// ----------------------------------------------------------------------------------------
function amrical_calendar_views ($link) {
	global $amr_listtype, $amr_limits;
	$link = remove_query_arg(array(
		'calendar',
		'agenda',
		'listtype',
		'eventmap'));
	
	if (isset ($amr_limits['agenda'])) $agenda = $amr_limits['agenda'];
	else $agenda = 1;
	if (isset ($amr_limits['eventmap'])) $eventmap = $amr_limits['eventmap'];
	else $eventmap = false;  // if not explicitly asked for a map, then do not do it
	if (isset ($amr_limits['calendar'])) $calendar = $amr_limits['calendar'];
	else {
		
		$calendar = 9;
	}
				
	if ($agenda) {
		$agendaviewlink = remove_query_arg('months',$link );
		$agendaviewlink = add_query_arg(array('agenda'=>$agenda),$agendaviewlink );		
		$agendaviewlink = '<a class="agendalink button" href="' 
		. htmlentities($agendaviewlink) 
		. '" title="' . __('Go to agenda or list view', 'amr_ical_list_lang'). '">'.__('Agenda', 'amr_ical_list_lang').'</a>';
	}
	else $agendaviewlink = '';
	//
	if ($calendar) {
		$calendarviewlink = '<a class="calendarlink" href="' 
		. htmlentities(add_query_arg(array('calendar'=>$calendar,'months'=>'1'),$link )) 
		. '" title="' . __('Go to calendar view', 'amr_ical_list_lang'). '">'.__('Calendar', 'amr_ical_list_lang').'</a>';
	}
	else $calendarviewlink  = '';
	//
	if ($eventmap) {
		$mapviewlink = '<a class="maplink" href="' 
		. htmlentities(add_query_arg('view','map',$link )) 
		. '" title="' . __('Go to map view', 'amr_ical_list_lang'). '">'.__('Map', 'amr_ical_list_lang').'</a>';
	}
	else $mapviewlink = '';
	$html = '<div class="calendarviews">'.$agendaviewlink.' '.$calendarviewlink.' '.$mapviewlink.'</div>';
	return ($html);
}
// ---------------------------------------------------------------------------------------- 
function amr_month_year_navigation ($start) {
return ('<form method="post" action="'.htmlentities(remove_query_arg('start')).'">'
		.amr_monthyeardrop_down($start->format('Ymd'))
		.'<input title="'.__('Go to date', 'amr_ical_list_lang').'" type="submit" value="&raquo;&raquo;" >'
		.'</form>');
}
// ---------------------------------------------------------------------------------------- 
function amr_month_year_links ($start,$months) { // returns array ($nextlink, $prevlink, $dropdown
	global $amr_calendar_url;
	global $wpdb, $wp_locale;

	// Get the next and previous month and year  
	$previous = new Datetime();
	$previous = clone $start;
	date_modify($previous, '-'.$months.' month');   //may need later on if we are going to show multiple boxes on one page 
	$prevmonth = $previous->format('m');
	$prevyear = $previous->format('Y');
	$next     = new Datetime();
	$next     = clone $start;
	date_modify($next, '+'.$months.' month');
	$nextmonth  = $next->format('m');
	$nextyear 	= $next->format('Y');

	//---------------------------  get navigation links ---------------------------------------
	
	if (!empty($amr_calendar_url))  $link = $amr_calendar_url;
	else $link = amr_clean_link();
	
	if ( $previous ) { $prevlink = 
		'<a href="' 
		. htmlentities(amrical_get_month_link($previous->format('Ymd'), $months, $link)) . '" title="' 
		. sprintf(__('Go to %1$s %2$s', 'amr_ical_list_lang'), $wp_locale->get_month($prevmonth), $prevyear) . '">&laquo;' 
		. $wp_locale->get_month_abbrev($wp_locale->get_month($prevmonth)) . '</a>';
	}
	else $prevlink = '';
	if ( $next ) {
		$nextlink = '<a href="' 
		. htmlentities(amrical_get_month_link($next->format('Ymd'), $months, $link)) 
		. '" title="' . esc_attr( sprintf(__('Go to %1$s %2$s', 'amr_ical_list_lang'), $wp_locale->get_month($nextmonth), $nextyear)) 
		. '">' . $wp_locale->get_month_abbrev($wp_locale->get_month($nextmonth)) . '&raquo;</a>';
	}
	else $nextlink = '';
	return (array('prevlink'=>$prevlink,'nextlink'=>$nextlink));
}
// ---------------------------------------------------------------------------------------- 
function amr_events_as_calendar($liststyle, $events, $id, $class='event-calendar', $initial = true) { /* startingpoint was wp calendar */
	global $amr_options, $amr_listtype, $amr_limits, $amrW;
	global $amr_globaltz;
	global $change_view_allowed;
	global $wpdb, $wp_locale;
	
// ---  Note that if months set, then events will have started from beg of month */
//	if (isset ($amr_limits['months'])) $months = $amr_limits['months'];  //may need later on if we are going to show multiple boxes on one page 
//	else 
	$months = 1;

//	var_dump($amr_options[$amr_listtype]);
	if (!empty($amr_options[$amr_listtype]['format']['Month']))  
			$month_format = $amr_options[$amr_listtype]['format']['Month'];
	else 	$month_format =	'F,Y';


//	if ( isset($_GET['w']) ) $w = ''.intval($_GET['w']); /* what sthis for ?*/
	// week_begins = 0 stands for Sunday
	$week_begins= intval(get_option('start_of_week'));
		
	if (isset($amrW) and ($amrW == 'w')) {/* we are in widget   *** later we may want to change this as will cause html validtaion failure if have post calendar too */
		$id = "wp-calendar";
		}
	else $id = str_replace('compprop','boxical', $id);  // don't wantto change listing styles for compatibility, but lets make it nicer now 	
	if (empty($class)) $class=$liststyle;
	else $class = 'box'.$class.' '.$liststyle;
	
	// Let's figure out when we are
	$start    = new Datetime('now',$amr_globaltz);
	$start    = clone $amr_limits['start'];
	$thismonth= $start->format('m');
	$thisyear = $start->format('Y');
	$m = $thisyear.$thismonth;
	$start->setDate($thisyear, $thismonth, 1);

	$month_nav_html = amr_month_year_links ($start, $months); // returns array ($nextlink, $prevlink, $dropdown
	$prevlink = $month_nav_html['prevlink'];
	$nextlink = $month_nav_html['nextlink'];

	//
	$calendar_caption = amr_date_i18n ($month_format, $start);
	if ((isset($amr_limits['show_views'])) and ($amr_limits['show_views']) and $change_view_allowed) {
			$views = amrical_calendar_views($link);
		}
	else $views = '&nbsp;';	
	//
	if ($liststyle=="smallcalendar") {		
		$navigation = '<tr class="calendar_navigation">';
		$navigation .= "\n\t\t".'<td colspan="3" class="prev">'.$prevlink.'</td>';
		$navigation .= "\n\t\t".'<td class="pad">&nbsp;</td>';
		$navigation .= "\n\t\t".'<td colspan="3" class="next">'.$nextlink.'</td>';
		$navigation .= '</tr>';
	}
	else {
		$navigation = '<tr class="calendar_navigation">';
		$navigation .= "\n\t\t".'<td colspan="4" class="navigation">'
		.$prevlink.'&nbsp;'
		.amr_month_year_navigation ($start)
		.'&nbsp;'
		.$nextlink.'</td>';
//		$navigation .= "\n\t\t".'<td class="pad">&nbsp;</td>';
		$navigation .= "\n\t\t".'<td colspan="3" class="views">'.
		$views.'</td>';
		$navigation .= '</tr>';

	}
	//------------------------end navigation-----------
	
	// now do for each month-------------------------------------------------
	$multi_output = '';
//	for ($i=1; $i <= $months; $i++) {
	
			$calendar_output = '<table id="'.$id.'" class="'.$class.'" >
			<caption>' . $calendar_caption. '</caption>
			<thead>'.$navigation.'<tr>';

			$myweek = array();
			for ( $wdcount=0; $wdcount<=6; $wdcount++ ) {
				$dayofweek = ($wdcount+$week_begins)%7;
				$myweek[] = $wp_locale->get_weekday($dayofweek);
				// make a note of which number is saturday and sunday so we can add css classes for the weekend
				if ($dayofweek == 6) $sunday = $wdcount;
				if ($dayofweek == 5) $satday = $wdcount;	
			}
			foreach ( $myweek as $wd ) {
				$day_name = ($liststyle=="smallcalendar") ? $wp_locale->get_weekday_initial($wd) : $wp_locale->get_weekday_abbrev($wd);
				$wd = esc_attr($wd);
				$calendar_output .= "\n\t\t<th scope=\"col\" title=\"$wd\">$day_name</th>";
			}
			$calendar_output .= '
			</tr></thead>
			<tfoot></tfoot>
			<tbody><tr>';

			// Get days with events
			$titles = array();

			if (!empty ($events)) { 
				foreach ($events as $event) {
//			var_dump($event);
					if (isset ($event['EventDate']) ) {
						$month = $event['EventDate']->format('m');
						if ($month == $thismonth) {  // this allows to have agenda with more months and events cached, and possibly later adjust code to show multiple boxes
							$day = $event['EventDate']->format('j');
							$dayswithevents[] = $day;
							$title = '';
							if (isset ($event['SUMMARY']) ) $title = $event['SUMMARY'];
							if (is_array($title)) $title = implode($title);
							$titles[$day][] = $title;	
							$eventsfortheday[$day][] = $event;
							}
					}
				}
			}
			
			if (isset($dayswithevents)) $dayswithevents = array_unique ($dayswithevents);

			if (strpos($_SERVER['HTTP_USER_AGENT'], 'MSIE') !== false || stripos($_SERVER['HTTP_USER_AGENT'], 'camino') !== false || stripos($_SERVER['HTTP_USER_AGENT'], 'safari') !== false)
				$ak_title_separator = "\n";
			else
				$ak_title_separator = ', ';
// if small calendar 
			if ( $titles ) {
				foreach ( $titles as $day => $daywithtitles_array ) {
//				echo '<br />'.$day; 
						if (is_array($daywithtitles_array) ) $string = implode(', ',$daywithtitles_array);
						else $string = $daywithtitles_array;
//				echo 'string = '.$string;
						$daytitles[$day] = esc_attr($string );
				}
			}
			
			/* ----------- flatten the array of component property options  - the order of fields for the event detail */
			foreach ($amr_options[$amr_listtype]['compprop'] as $k => $v)
				{ 	foreach ($v as $i=>$j) 	{ $order[$i] = $j; 	}; 	}
			$order = prepare_order_and_sequence ($order);		
			if (!($order)) return;
			//-----------------------------------------------------------------------------------	
			if (ICAL_EVENTS_DEBUG) echo ' we have '.count($eventsfortheday);
			if (!empty($eventsfortheday)) { 
					foreach ( $eventsfortheday as $day => $devents ) {
						if (ICAL_EVENTS_DEBUG) echo '<br />Day ='.$day. ' with '.count($devents).' events '; 
						$dayhtml[$day] = amr_list_one_days_events($devents, $order);
						if (ICAL_EVENTS_DEBUG) echo 'string = '.$dayhtml[$day];
				}
			}
			
/* ------See how much we should pad in the beginning */
			$pad = calendar_week_mod($start->format('w')-$week_begins);  
			if ( 0 != $pad )
				$calendar_output .= "\n\t\t".'<td colspan="'. esc_attr($pad) .'" class="pad">&nbsp;</td>';

			$daysinmonth = $start->format('t');
			for ( $day = 1; $day <= $daysinmonth; ++$day ) {
				if ( isset($newrow) && $newrow )
					$calendar_output .= "\n\t</tr>\n\t<tr>\n\t\t";
				$newrow = false;
				$lastinrow = '';
				// check if after this we need a new row //
				if ( 6 == calendar_week_mod(date('w', mktime(0, 0 , 0, $thismonth, $day, $thisyear))-$week_begins) ) {
					$newrow = true;
					$lastinrow = ' endweek';
				}
				/* wp code - can't we do better ? */
				$datestring = $day.'-'.$thismonth.'-'.$thisyear; // must use hyphens for uk english dates, els eit goes US 
				$dow = date('N',strtotime($datestring));
				if ( $day == gmdate('j', current_time('timestamp')) && $thismonth == gmdate('m', current_time('timestamp')) && $thisyear == gmdate('Y', current_time('timestamp')) )
					$calendar_output .= '<td class="day'.$dow.' today '.$lastinrow.'">';
				else
					$calendar_output .= '<td class="day'.$dow.$lastinrow.'">';

				if ((!empty($dayswithevents) ) and ( in_array($day, $dayswithevents) )) {// any posts today?
				
					if ($liststyle == 'largecalendar') 
						$calendar_output .= $day.'<div class="day">'.$dayhtml[$day];
					else 		
						$calendar_output .= '<div class="day"><a href="' 
						. htmlentities(amr_get_day_link($thisyear, $thismonth, $day, $amr_calendar_url)) . "\" title=\"" . esc_attr($daytitles[$day]) . "\">$day</a>";
					}
				else
					$calendar_output .= '<div class="day">'.$day;
				$calendar_output .= '</div></td>';


			}

			$pad = 7 - calendar_week_mod(date('w', mktime(0, 0 , 0, $thismonth, $day, $thisyear))-$week_begins);
			if ( $pad != 0 && $pad != 7 )
				$calendar_output .= "\n\t\t".'<td class="pad" colspan="'. esc_attr($pad) .'">&nbsp;</td>';

			$calendar_output .= "\n\t</tr>\n\t</tbody>\n\t</table>";
			$multi_output .= $calendar_output;
//			date_modify($start, '+'.$i.' month');
//	} // for each month		

	return($multi_output);
}
/* --------------------------------------------------  */
function amr_list_one_days_events($events, $order) { /* for the large calendar */
	global $amr_options,
		$amr_limits,
		$amr_listtype,
		$amrW,
		$amrtotalevents;

		if (empty($events)) return;	
		$html = '';		

//		var_dump($order);	
		$no_cols = 1;
/* --- setup the html tags ------need to have divs if wantto allow html else must strip---------------------------------------- */		
		$ev = '<div class="event">';
		$evc = '</div> ';
		
/* -- body code ------------------------------------------*/			
		$groupedhtml = '';
		foreach ($events as $i => $e) { /* for each event, loop through the properties and see if we should display */
			amr_derive_component_further ($e);	
			if ((isset($e['Classes'])) and (!empty($e['Classes']))) 
				$classes = strtolower($e['Classes']);
			else $classes = '';
			$eventhtml = ''; /*  each event on a new list */
			$colhtml = array();
			foreach ($order as $field => $fieldconfig) { /* ie for one event, check how to order the bits */
								/* Now check if we should print the component or not, we may have an array of empty string - check our event has that value */
				if (isset($e[$field])) $v = amr_check_flatten_array ($e[$field]);
				else $v =null;				
				if ((isset ($v))  && (!empty($v)))	{	
					$col = $fieldconfig['Column'];
					if (empty($colhtml[$col])) $colhtml[$col] = '';
					$colhtml[$col] .= stripslashes($fieldconfig['Before'])
						. amr_format_value($v, $field, $e).stripslashes($fieldconfig['After']);  /* amr any special formating here */
				}
			}
			foreach ($colhtml as $col => $chtml) {
				$eventhtml .= '<div class="details'.$col.'">'.$chtml.'</div>';
			}
			if (!($eventhtml === '')) { /* ------------------------------- if we have some event data to list  */
				/* then finish off the event or row, save till we know whether to do group change heading first */
				$eventhtml = $ev.$eventhtml.$evc;		
				}	
			$html .= $eventhtml;	
		} 
		//if (!empty($html)) $html = $html.$ulc;
return ($html);
}
?>