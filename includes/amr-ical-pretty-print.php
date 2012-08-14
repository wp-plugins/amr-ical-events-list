<?php
function amr_prettyprint_weekst ($wkst) {
global $amr_day_of_week_no, $wp_locale;
if (empty($wkst)) return '';
return ('<em>'.sprintf(__('Weeks start on %s','amr-ical-events-list'), amr_get_weekday_from1($amr_day_of_week_no[$wkst])).'</em>');
}
/* --------------------------------------------------  */
function amr_prettyprint_r_ex_date ($rdate) { /* rrule or exrule */
global $amr_formats;  /* amr check that this get set to the chosen list type */

//	$df = pref_date_entry_format_string();
	if (is_array($rdate)) {
		foreach ($rdate as $i => $d) {
			if (is_object($d))
				$html[] = amr_format_date ($amr_formats['Day'], $d);
//			 = $d->format($df);  /* *** is it already in the right timezone or not ? If just doing 'date' for now, is okay? */
			else $html[] = $d;
		}
	}
	return (implode(', ', $html));
}
/* --------------------------------------------------  */
function amr_prettyprint_byday ($b) {
	$fulldayofweek = amr_fulldaytext(); /* MO, TU= etc*/
	$h = array();
	$html = '';
	if (is_array($b)) {
		foreach ($b as $d => $n) {
			if (is_array($n)) { /* must be n bydays */
				foreach ($n as $i => $n2) {
					$temp[] = ical_ordinalize_words($n2);
				}
				if (count($temp) == 2) $h[] = implode (__(' and ','amr-ical-events-list'),$temp).' '.$fulldayofweek[$d];
				else $h[] =  implode (', ',$temp).' '.$fulldayofweek[$d];
				$temp = array();
			}
			else { /* normal bydays */
				$h[] = $fulldayofweek[$d];

			}
			if (count($h) == 2) $html = implode(__(' and ','amr-ical-events-list'),$h);
			else $html =  implode (', ',$h);
			if (!is_array($n)) $html =	__('every ','amr-ical-events-list').$html;
		}
		return($html);
	}
	else return ($b);	/* who knows what is in if it is not an array? */

}
/* --------------------------------------------------  */
function amr_prettyprint_byordinal ($b) {
	$h = array();
	if (is_array($b)) {
		foreach ($b as $i => $d) {
			$h[] = ical_ordinalize_words($d);
		};
		if (count($h) == 2) $html = implode(__(' and ','amr-ical-events-list'),$h);
		else $html =  implode (',&nbsp;',$h);
		return($html);
	}
else return ($b);

}
/* --------------------------------------------------  */
function amr_prettyprint_bymonth ($b) {
global $wp_locale;
	$h = array();
	if (is_array($b)) {
		foreach ($b as $i => $d) {
			$h[] = $wp_locale->get_month($d);
		};
		if (count($h) == 2) $html = implode(__(' or ','amr-ical-events-list'),$h);
		else $html =  implode (',&nbsp;',$h);
		return($html);
	}
else return ($b);

}
/* --------------------------------------------------  */
function amr_prettyprint_duration ($duration) {
	if (empty($duration)) return;
	if (!is_array($duration)) echo $duration;
	else { $h = array();
		foreach ($duration as $i => $v) {
			$h[] = sprintf( _n(	'%u '.rtrim($i,'s') /* singular */
			                  , '%u '.$i /* plural */
							  ,$v  // number
							  ,'amr-ical-events-list'),// domain
							  $v);
		}
		$html = implode (',&nbsp;',$h);
	}
	echo $html;
}
/* --------------------------------------------------  */
function amr_prettyprint_rule ($rule) { /* rrule or exrule */
/* Receive an array of prepared fields and combine it into a suitable descriptive string */
global 	$amr_freq,
		$amr_freq_unit,
		$amr_day_of_week_no,
		$wp_locale;
	$sep = '&nbsp;';
	$c = '';

	if (isset($rule['FREQ'])) {
		$nicefrequnit = $amr_freq_unit[$rule['FREQ']]; /* already translated value */
		if (isset($rule['INTERVAL'])) {/*   the freq as it is repetitive */
			$interval  = ' '
			.sprintf(__('Every %s %s','amr-ical-events-list'),
    			ical_ordinalize($rule['INTERVAL']),
				$nicefrequnit).$sep;
		}
//		else $interval = ' '.sprintf( __('every %s','amr-ical-events-list'), $nicefrequnit).$sep; // sounds funny to have daily every day, only have if every 2nd etc
//		$nicefreq = $amr_freq[$rule['FREQ']].$interval; /* already translated value */

		else $interval = $amr_freq[$rule['FREQ']]; /* already translated value */

		if (isset($rule['BYSETPOS'])) $c .= ' '.
			sprintf(__('On %s instance within %s', 'amr-ical-events-list')
			,amr_prettyprint_byordinal($rule['BYSETPOS'])
			,$interval);
//		else $c .= 	$nicefreq;
		else $c .= 	$interval;
		if (isset($rule['COUNT'])) $c .= ' '.sprintf(__('%s times','amr-ical-events-list'), $rule['COUNT']).$sep;
		if (isset($rule['UNTIL'])) {
			if ($rule['UNTIL-TIME'] === '00:00') $rule['UNTIL-TIME'] = '';
			else if (strtolower($rule['UNTIL-TIME']) === '12:00 am') $rule['UNTIL-TIME'] = '';

			$c .= '&nbsp;'.sprintf(__('until %s %s','amr-ical-events-list'), $rule['UNTIL-DATE'], $rule['UNTIL-TIME']).$sep;
			}
		if (isset($rule['MONTH'])) $c .= sprintf(__(' if month is %s','amr-ical-events-list'),amr_prettyprint_bymonth($rule['MONTH']));
//		if (isset($rule['BYWEEKNO'])) $c .= ' '.sprintf(__(' in weeks %s','amr-ical-events-list'),implode(',',$rule['BYWEEKNO']));
		if (isset($rule['BYWEEKNO'])) $c .= ' ' .sprintf(__(' in %s weeks of the year','amr-ical-events-list'),amr_prettyprint_byordinal($rule['BYWEEKNO']));
//		if (isset($rule['BYYEARDAY'])) $c .= ' '.sprintf(__('on the %s day of year','amr-ical-events-list'),implode(',',$rule['BYYEARDAY']));
		if (isset($rule['BYYEARDAY'])) $c .= ' '.sprintf(__('on %s day of the year','amr-ical-events-list'),amr_prettyprint_byordinal($rule['BYYEARDAY']));
		if (isset($rule['DAY'])) $c .= ' '.sprintf(__('on %s day of each month', 'amr-ical-events-list'),amr_prettyprint_byordinal($rule['DAY']));
		if (isset($rule['NBYDAY'])) $nbyday = ' '.sprintf(__('on %s ', 'amr-ical-events-list'),amr_prettyprint_byday($rule['NBYDAY']));
		if (isset($rule['BYDAY'])) $byday = ' '.sprintf(__('on %s ', 'amr-ical-events-list'),amr_prettyprint_byday($rule['BYDAY']));
		$ofthefreq = '';
		// change to accomodate dutch having different artcles for month and year de or het
		if ($rule['FREQ'] == 'MONTHLY')
			$ofthefreq = __(' of the month','eg: last day of the month', 'amr-ical-events-list');
		else if ($rule['FREQ'] == 'YEARLY')
			$ofthefreq = __(' of the year','eg: last day of the year','amr-ical-events-list');	
		if (isset ($nbyday) and isset ($byday)) $c .= $nbyday.__(' and ','amr-ical-events-list').$byday.$ofthefreq;
		else { if (isset ($byday)) $c .= $byday.$ofthefreq;
			if (isset ($nbyday)) $c .= $nbyday.$ofthefreq;
		}
		if (isset($rule['BYHOUR'])) $c .= ' '.sprintf(__('at the %s hour', 'amr-ical-events-list'),implode(',',$rule['BYHOUR']));
		if (isset($rule['BYMINUTE'])) $c .= ' '.sprintf(__('at the %s minute', 'amr-ical-events-list'),implode(',',$rule['BYMINUTE']));
		if (isset($rule['BYSECOND'])) $c .= ' '.sprintf(__('at the %s second', 'amr-ical-events-list'),implode(',',$rule['BYSECOND']));
		if (isset($rule['WKST'])) $c .= '; '.amr_prettyprint_weekst($rule['WKST']);
		}
	return (rtrim($c,','));
}
/* --------------------------------------------------  */
function amr_prepare_pretty_rrule ($rule) {

global $ical_timezone, $amr_formats;

/* take the event and it's parsed rrule or exrule and convert some aspects for people use.  Used by both edit event and event info */

	$df = $amr_formats['Day'];

	$tf = $amr_formats['Time'];

	$rule['UNTIL-DATE'] = '';

	$rule['UNTIL-TIME'] = '';

	if (isset($_GET['wpmldebug'])) {echo '<hr> inprep pretty';var_dump($rule);}

	foreach ($rule as $i=>$r) { $rule[strtoupper($i)] = $r;}

	if (isset($rule['UNTIL']) and is_object($rule['UNTIL'])) {  /* until is possibly in Z time, move to our time first */

			date_timezone_set($rule['UNTIL'], $ical_timezone);

//			$rule['UNTIL-DATE'] = $rule['UNTIL']->format($df);
			$rule['UNTIL-DATE'] = amr_format_date($df, $rule['UNTIL']);
			$rule['UNTIL-TIME'] = amr_format_date($tf, $rule['UNTIL']);

	}

	else if (!(isset($rule['COUNT']))) 	$rule['forever'] = 'forever';

	if (isset ($rule['NOMOWEBYDAY'])) { /* what the F?? */
			foreach ($rule['BYDAY'] as $j => $k) {
					$l = strlen($k);
					if ($l > 2) {  /* special treatment required - we have a numeric byday  */
						$d = substr($k, $l-2, $l);

						$rule['NBYDAY'][$d][substr ($k, 0, $l-2)] = true;
						$rule['BYDAY'][$d] = true;
					}
					else {

						$rule['BYDAY'][$k] = true; /* ie recurs every one of those days of week */
						$rule['NBYDAY'][$k]['0'] = true;

					}
					unset($rule['BYDAY'][$j]);
			}
	}
	return ($rule);

	}
/*--------------------------------------------------------------------------------*/
function amr_format_duration ($arr) {
	/* receive an array of hours, min, sec */

	foreach ($arr as $i => $d) if ($d === 0) unset ($arr[$i]);
	$i = count($arr);

	if ($i > 1) $sep = ', ';
	else $sep = '';

	$d = '';
	if (isset ($arr['years'] )) {
		$d .= sprintf (_n ("%u year", "%u years", $arr['years'], 'amr-ical-events-list'), $arr['years']);
		$d .= $sep;
		$i = $i-1;
		}
	if (isset ($arr['months'] )) {
		$d .= sprintf (_n ("%u month ", "%u months ", $arr['months'], 'amr-ical-events-list'), $arr['months']);
		if ($i> 1) {$d .= $sep;}
		$i = $i-1;
		}
	if (isset ($arr['weeks'] )) {
		$d .= sprintf (_n ("%u week ", "%u weeks", $arr['weeks'], 'amr-ical-events-list'), $arr['weeks']);
		if ($i> 1) {$d .= $sep;}
		$i = $i-1;
		}
	if ((isset ($arr['days'] )) ) {
			$d .= sprintf (_n ("%u day", "%u days", $arr['days'], 'amr-ical-events-list'), $arr['days']);
//			If (ICAL_EVENTS_DEBUG) {echo ' and d = '.$d;}
			if ($i> 1) {$d .= $sep;}
			$i = $i-1;
		}
	if (isset ($arr['hours'] )) {
		$d .= sprintf (_n ("%u hour", "%u hours", $arr['hours'], 'amr-ical-events-list'), $arr['hours']);
		if ($i> 1) {$d .= $sep;}
		$i = $i-1;
		}
	if (isset ($arr['minutes'] )) {
		$d .= sprintf (_n ("%u minute", "%u minutes", $arr['minutes'], 'amr-ical-events-list'), $arr['minutes']);
		if ($i> 1) {$d .= $sep;}
		$i = $i-1;
		}
	if (isset ($arr['seconds'] )) {
		$d .= sprintf (_n ("%u second", "%u seconds", $arr['seconds'], 'amr-ical-events-list'), $arr['seconds']);

		}
	return($d);
	}
/* --------------------------------------------------------- */
function amr_format_tz ($tzstring) {
global $amr_globaltz, $amr_options;

	$url = esc_url_raw($_SERVER['REQUEST_URI']);
	$tz = timezone_name_get($amr_globaltz);
	if ($tz === $tzstring) 
		$tz2 = date_default_timezone_get();
	else 
		$tz2 = $tzstring;
	if ($tz2===$tz) 
		$tz2 = 'UTC';
	$text1 = __('Change Timezone','amr-ical-events-list');
	$text2 = sprintf( __('Timezone: %s, Click for %s','amr-ical-events-list'),$tz, $tz2);
	if (isset ($amr_options['no_images']) and $amr_options['no_images']) 
		$t3 = $text1;
	else 
		$t3 = '<img title = "'.$text2.'" src="'.IMAGES_LOCATION.TIMEZONEIMAGE.'" class="amr-bling" alt="'.$text1.'" />';

	return ('<a class="timezone amr-bling" href="'
		.htmlentities(add_querystring_var($url,'tz',$tz2)).'" title="'
		.$text2.'" >'.$t3.' </a>');
}
/* --------------------------------------------------------- */
function amr_format_bookmark ($text) {
	return ('<a id="'.$text.'"></a>');  /* ***/
}
/* --------------------------------------------------------- */
function amr_format_rrule ($rule) {
	if (isset($rule[0]))  /* we have an array of rules, is this still valid or is it just one now *** if multiple valid, then code change required  */
		$rule = $rule[0];
	if (is_string($rule)) $rule = amr_parseRRULE($rule);
	$rule2 = amr_prepare_pretty_rrule ($rule);
	$rule3 = amr_prettyprint_rule ($rule2);
	return ($rule3);
}
/* --------------------------------------------------------- */
function amr_format_repeatable_property ($content, $k, $event, $before='', $after='') {
// for properties that can have multiple values and for which we have received an array of those values, we need to do the format routine for each value

	$c = '';
	foreach ($content as $i => $v) {
		if (!(empty($v))) {
			$c .= amr_format_value ($v, $k, $event,	$before,$after) .' ';
			}
	}
	return ($c);
}
/* --------------------------------------------------------- */
function amr_format_value ($content, $k, $event, $before='', $after='') { /* include the event so we can check for things like all day */
/*  Format each Ical value for our presentation purposes
Note: Google does toss away the html when editing the text, but it is there if you add but don't edit.
what about all day?
*/
	global $amr_formats;  /* amr check that this get set to the chosen list type */
	global $amr_options;
	global $amr_listtype;
	global $eventtaxonomies;

//	echo '<br >'.$k;
	if (empty($content)) return('');

	if ($k == 'ORGANIZER') 	{ // it is an array but a parsed one, not repeatable
		$htmlcontent = amr_format_organiser ($content);
		}
	elseif ($k == 'ATTENDEE') 	{
		$htmlcontent = amr_format_attendees ($content);
		}
	else if (is_object($content)) {
		switch ($k){
			case 'EventDate': {
				$htmlcontent = ('<abbr class="dtstart" title="'
//					.amr_format_date ('l jS F Y, H:i e ', $content).'">'
					.amr_format_date ('c', $content).'">' //must be ISO 8601 date for microformats to work
					.amr_format_date ($amr_formats['Day'], $content)
					.'</abbr>'
					);
				break;
			}
			case 'EndDate': {
				$days = amr_event_is_multiday($event);
				if ( $days > 1)
					$htmlcontent = ('<abbr class="dtend" title="'
					.amr_format_date ('c', $content).'">'  //must be ISO 8601 date
					.amr_format_date ($amr_formats['Day'], $content)
					.'</abbr>'
					);
				else $htmlcontent = '';
				break;
			}
			case 'EndTime':
			case 'StartTime':{
				if (isset($event['allday']) and ($event['allday'] === 'allday'))
					$htmlcontent = '';
				else
					$htmlcontent = amr_format_time ($amr_formats['Time'], $content);
				break;
			}
			case 'DTSTART':
			case 'DTEND':
			case 'UNTIL': {
				$htmlcontent = amr_format_date ($amr_formats['Day'], $content);
				if (empty($event['allday']) or !($event['allday'] == 'allday'))
					$htmlcontent  .= ' '.amr_format_time ($amr_formats['Time'], $content);
				break;
				}
			case 'X-WR-TIMEZONE': { /* amr  need to add code to reformat the timezone as per admin entry.  Also only show if timezone different ? */
				$htmlcontent = amr_format_tz(timezone_name_get($content));
				break;
			}
			case 'TZID': { /* amr  need to add code to reformat the timezone as per admin entry.  Also only show if timezone different ? */
				$htmlcontent = amr_format_tz (timezone_name_get($content));
				break;
			}
			default: 	/* should not be any */
				$htmlcontent = amr_format_date ($amr_formats['DateTime'], $content);
		}
	}
	elseif (is_array($content)) {

		if ($k === 'DURATION') {
			$htmlcontent = amr_format_duration ($content);
		}
		elseif (($k === 'RRULE') or ($k === 'EXRULE')) {
			$htmlcontent = amr_format_rrule($content);
			}
		elseif (($k === 'RDATE') or ($k === 'EXDATE')) {
			$htmlcontent = amr_prettyprint_r_ex_date ($content);
			}
		elseif ($k=== 'CATEGORIES') {  // umm - what if ics category
				$htmlcontent = amr_format_taxonomies ('category', $content);

			}
		elseif ($k=== 'post_tag' ) {
				$htmlcontent = amr_format_taxonomies ('post_tag', $content);

			}
		elseif ($k == 'ATTACH') {
			if (isset($content[0]['type'] ))	{
				// then we are at the top level of the array, so can ask to handled repetaed values
				return ( amr_format_repeatable_property ($content, $k, $event, $before, $after));
			}
			else
				$htmlcontent = amr_format_attach ($content, $event);
		}
		else {  /* simple array don't think we need to list the items separately eg: multiple comments or descriptions - just line  */
			if (!empty( $eventtaxonomies) and in_array( $k, $eventtaxonomies)) {
					$htmlcontent = amr_format_taxonomies ($k, $content);
				}
			else
				return( amr_format_repeatable_property ($content, $k, $event, $before, $after));
			}
		}

	elseif (is_null($content) OR ($content === ''))
		$htmlcontent = '';
	else {
		if (function_exists ('amr_format_'.$k)) {
			$htmlcontent =(call_user_func('amr_format_'.$k, $content));
		}
		else 
		switch ($k){
			case 'COMMENT':
			case 'DESCRIPTION': {
				$htmlcontent = html_entity_decode(amr_click_and_trim(nl2br2(amr_amp($content))));
				break;
			}
			case 'SUMMARY':
			case 'icsurl':
			case 'addtogoogle':
			case 'addevent':
			case 'subscribeevent':
			case 'subscribeseries':
			case 'map':
			case 'refresh':
			case 'attending_event': {
				$htmlcontent = $content; /* avoid hyperlink as we may have added url already */
				break;
			}
			case 'URL': /* assume valid URL, should not need to validate here, then format it as such */
				$htmlcontent = amr_format_url($content);
				break;
			case 'LOCATION': {
				$htmlcontent = (amr_click_and_trim(nl2br2(amr_amp($content))));
				break;
			}

			case 'X-WR-TIMEZONE':{	/* not parsed as object - since it is cal attribute, not property attribue */
				$htmlcontent = (amr_format_tz ($content));
				break;
			}
			case 'allday': {
				$htmlcontent =(amr_format_allday($content));
				break;
			}


			default: 	/* Convert any newlines to html breaks */

				if (!empty( $eventtaxonomies) and in_array( $k, $eventtaxonomies)) {
					$htmlcontent = amr_format_taxonomies ($k, $content);
				}
				else
					$htmlcontent = str_replace("\n", "<br />", $content);

		}
		
	}

	if (empty ($htmlcontent) ) 
		return;
	return ($before.$htmlcontent.$after);

}
/* ------------------------------------------------------------------------------------*/
function amr_wp_format_date( $format, $datestamp, $gmttf) { /* want a  integer timestamp or a date object  */
global $amr_options, $wp_locale;
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
/*		If (isset ($_REQUEST['tzdebug']))
			{
				echo '<br />Localised with gmt=false: '.$text.'<br />';
				$text2 = date_i18n($format, $dateInt, false);
				echo 'Localised with gmt=true:  '.$text2.'<br />';
				$text3 = amr_date_i18n ('D, F j, Y g:i a', $datestamp);
				echo 'Localised with amr date obj fn: '.$text3.'<br />';
			} */
		return ($text); //
		}
}
/* -------------------------------------------------------------------------------------------*/
function amr_format_time( $format, $datestamp) { /* want a  integer timestamp or a date object  */
global 	$amr_options,
		$amr_globaltz;

	date_timezone_set ($datestamp, $amr_globaltz);  /* Converting here, but then some derivations wrong eg: unsetting of end date */
	// check for midnight, midday, noon etc
	$time = $datestamp->format('His');
	if (isset($_GET['tzdebug'])) echo  '<br />Time='.$time;

	$humanspeak = apply_filters('amr_human_time',$time);
	if (!($time === $humanspeak )) return($humanspeak);
	else
		return (amr_format_date( $format, $datestamp))	;
}
/* -------------------------------------------------------------------------------------------*/
function amr_format_date( $format, $datestamp) { /* want a  integer timestamp or a date object  */
global 	$amr_options,
		$amr_globaltz;

	//if (is_string($datestamp)) $datestamp = date_create($datestamp, $amr_globaltz);

	if (isset ($amr_options ['date_localise']))
		$method = $amr_options ['date_localise'];
	else
		$method = 'wp';  // v4.0.9 was none

	if (isset($_GET['tzdebug'])) echo  '<br />set tz for: '.$datestamp->format('c');

	date_timezone_set ($datestamp, $amr_globaltz);  /* Converting here, but then some derivations wrong eg: unsetting of end date */

	if (isset($_GET['tzdebug'])) echo  '<br />'.$datestamp->format('c');

	if ($method === 'wp') return amr_wp_format_date ( $format, $datestamp, false);
	else if ($method === 'wpgmt') return amr_wp_format_date ( $format, $datestamp, true);
	else if ($method === 'amr') return amr_date_i18n ( $format, $datestamp);
	else {
		if (stristr($format, '%') ) return (strftime( $format, $datestamp->format('U') ));  /* keep this for compatibility!  will not localise though */
		else return ($datestamp->format($format));
		}
}
/* ------------------------------------------------------------------------------------*/