<?php

global	$timeperiod_conv; 
global	$amr_day_of_week;
global  $amr_day_of_week_no;
global 	$amr_bys;  /* an array containing all the diificuly by's such as negative bymonthdays, byday 9days of week) etc */
global $amr_wkst;   /* alpha */
global $amr_rulewkst; /* local to rule and numeric */

$amr_timeperiod_conv = array (
/* used to convert from ical FREQ to gnu relative items for date strings useed by php datetime to do maths */
			'DAILY' => 'day',
			'MONTHLY' => 'month',
			'YEARLY' =>  'year',
			'WEEKLY' => 'week',
			'HOURLY' => 'hour',
			'MINUTELY' => 'minute',
			'SECONDLY' => 'second'
			);
$amr_day_of_week	= array (
			'MO' => 'Monday',
			'TU' => 'Tuesday',
			'WE' => 'Wednesday',
			'TH' => 'Thursday',
			'FR' => 'Friday',
			'SA' => 'Saturday',
			'SU' => 'Sunday'
			);
$amr_day_of_week_no	= array (
			'MO' => 1,
			'TU' => 2,
			'WE' => 3,
			'TH' => 4,
			'FR' => 5,
			'SA' => 6,
			'SU' => 7
			);			

/* ---------------------------------------------------------------------------- */

function amr_parse_RRULEparams ( $args)	{

global $amr_bys;
global $amr_day_of_week_no;
global $amr_wkst;
global $amr_rulewkst;
global $amr_globaltz;

		foreach ($args as $i => $a) parse_str ($a);
		/* now we should have if they are there: $freq, $interval, $until, $wkst, $ count, $byweekno etc */
		
			
		if (isset ($BYMONTH)) {	
			$p['month'] = explode (',',$BYMONTH);	
			if (isset($FREQ) and ($FREQ === 'MONTHLY')) {
				echo '</br>Incompatible FREQ=MONTHLY and BYMONTH, corrected: FREQ set to YEARLY</br>';
				$FREQ = 'YEARLY';  /* if Freq was left out, we can still "recover from that */
			} 
			foreach ($p['month'] as $j => $k) { 
				if ($k < 0) { $p['month'][$j] = 13 + $k; }
			}
		}
		
		if (isset ($BYMONTHDAY)) {
			$p['day'] = explode (',',$BYMONTHDAY);
			if (isset($FREQ) and ($FREQ === 'DAILY')) {
				echo '</br>Incompatible FREQ=DAILY and BYMONTHDAY, corrected: FREQ set to MONTHLY</br>';
				$FREQ = 'MONTHLY'; /* if Freq was left out, we can still "recover from that */
			}		
		}
			
		if (isset ($BYHOUR)) {	
				$p['hour'] = explode (',', $BYHOUR);  
				foreach ($p['hour'] as $j => $k) { 
				if ($k < 0) { $p['hour'][$j] = 24 + $k; }
				}
		}
		if (isset ($BYMINUTE)) {	
			$p['minute'] = explode (',', $BYMINUTE); 
			foreach ($p['minute'] as $j => $k) { 
				if ($k < 0) { $p['minute'][$j] = 60 + $k; }
				}
		}
		if (isset ($BYSECOND)) {	
			$p['second'] = explode (',', $BYSECOND); 
			foreach ($p['second'] as $j => $k) { 
				if ($k < 0) { $p['second'][$j] = 60 + $k; }
				}
		}
		if (isset ($BYDAY)) {
			$p['byday'] = explode(',', $BYDAY);
			foreach ($p['byday'] as $j => $k) {
					$l = strlen($k); 
					if ($l > 2) {  /* special treatment required - flag to re handle, keep as we want to isolate a subset anyway */
						$p['specbyday'][] = $k;
						$p['byday'][$j] = substr($k, $l-2, $l);
					}
					else $by2[] = $k;
				}	
			
		}
		if (isset ($BYWEEKNO)) 	{$p['byweekno'] = explode(',', $BYWEEKNO);}
		if (isset ($BYYEARDAY)) {$p['byyearday'] = explode(',', $BYYEARDAY);  }
		if (isset ($UNTIL)) 	{$p['until'] = amr_parseDateTime($UNTIL, $amr_globaltz);}
		if (isset ($COUNT)) 	{$p['count'] = $COUNT;}
		if (isset ($INTERVAL)) 	{$p['interval'] = $INTERVAL;}
		if (isset ($FREQ)) 		{$p['freq'] = $FREQ;}
		if (isset ($WKST)) 		{
			$p['wkst'] = $WKST;
		}
		else {
			$p['wkst'] = $amr_wkst;
		}

		if (isset ($BYSETPOS)) {
			echo '<br>bysetpos not yet supported';	
			$p['bysetpos'] = BYSETPOS;}

/* Now test for negtaives, and fix or remove them for special handling  amr do we need thsi here? handle in the special by's anyway? */
			
		foreach (array('byyearday','byweekno', /* 'day' */) as $i => $b)	{
			if (isset ($p[$b])) {
				foreach ($p[$b] as $j => $k) {
					if ($k < 0) {  /* special treatment required - handle separately */
						$p['neg'.$b][] = $k;
						unset ($p[$b][$j]);
						echo '<br>Negative '.$b.' '.$k.' not yet supported ';
					}
					else $by2[] = $k;
				}
				if (isset ($by2)) $p[$b] = $by2;
				else unset ($p[$b]);
			}	
		}
		return ($p);  /* Need the array values to reindex the array so can acess start positions */	
	}	

/* ---------------------------------------------------------------------------- */
function amr_process_byday ($d, $bd /* an array of bydays */, $wkst)	{
/* very tricky - could be 1MO (first monday) or -1MO (last monday) 
	or TU, WE, TH
	need to generate all th's for params given
	want to return a set of starts, not repeats wha if the iteration was something else?
	Still need to check the bydays later in case the iteration was not weekly or was neg
	
*/
global  $amr_day_of_week_no;
		$amr_rulewkst = $amr_day_of_week_no[$wkst];
	
		if (ICAL_EVENTS_DEBUG) echo '<br>Jump back '.$amr_rulewkst.' days to get start dates for current week from '.$d->format('c');
		
		$dd = new DateTime();
		$dd = clone ($d);
		date_modify ($dd,'-'.$amr_rulewkst.' day');		
		$x = 0;
		while ($x < 7) {
			$ax = strtoupper(substr($dd->format('D'), 0, 2));
			if (ICAL_EVENTS_DEBUG) echo '<br>Trying '.$ax. ' for '.$dd->format('c');
			if (in_array($ax, $bd)) { 
				$d2[$x] = new DateTime();
				$d2[$x] = clone ($dd); 
				if (ICAL_EVENTS_DEBUG) echo ' Use this one! no'.count($d2);
			}
			date_modify ($dd,'+1 day');		
			$x = $x+1;
		}

	if (ICAL_EVENTS_DEBUG) {  echo '<br>Number of day of week records = '.count($d2); foreach ($d2 as $i => $e) {echo '<BR>'.$e->format('c');};}
	return ($d2);
}		

/* ---------------------------------------------------------------------------- */
function amr_process_easybys ($date, $p, $wkst)	{

global  $amr_day_of_week_no;

/* THis should process the 'By's and generate an array of start dates to repeat with the FREQ 
/* now we need to ascertain our starting points with the byday, bymonth etc before we can start repeating with freq , interval */
/* BYMONTH, BYWEEKNO, BYYEARDAY, BYMONTHDAY, BYDAY, BYHOUR,
BYMINUTE, BYSECOND and BYSETPOS */
	
/*		if (isset ($p[  ]) and (isset ($FREQ) and $FREQ='YEARLY'))	{ 
		/* LATER then we need to check for wkst - default is MO and week no uses MO, but what if it is different? *** */
/*			if (isset ($WKST) and 
				!(substr(date_format($date,'D'), 0,2 ) === $WKST)) {
				date_modify ( $date, 'next '.$amr_day_of_week[$WKST]);
			}
		}	
*/
		$tz = date_timezone_get($date); /* save the timezone of the date that we are using,so we can create others using it  */

		$d = date_parse ($date->format('Y-M-j H:i:s'));	/* gives $d['year']  etc */	
		if (ICAL_EVENTS_DEBUG) {echo '<br />The parsed date:'; 	 var_dump($d);	}
		

		$d2[0] = $d;  /* the first in an array of start dates is our default date */
		if (isset ($p['month'])) {
			/* set the first month , and create more records of there are othr months */
			$d2[0]['month'] = $p['month'][0]; 
			$d2i = 1;
			while ($d2i < count($p['month'])) {  /* create a new start record for each additional month type */
				$d2[$d2i] = $d2[0];
				$d2[$d2i]['month'] = $p['month'][$d2i];
				$d2i = $d2i + 1;
			}		
		}	
/* ------------------------------------------- */

		$others = array ('day', 'hour', 'minute', 'second');
		foreach ($others as $oi => $o) {		
			if (isset ($p[$o])) {	
				$d3i = 0;
				foreach ($d2 as $i => $d3) {
					/* set the day of all the previous starts to the first monthday, create new records for others  */
					$d2[$i][$o] = $p[$o][0];
					$pi = 1;
					while ($pi < count($p[$o])) {  /* create a new start record for each additional month day type */
						$mored2[$d3i] = $d2[$i];  /* copy over all the values */
						$mored2[$d3i][$o] = $p[$o][$pi];    /* adjust the one value we are working with here */
										
						$d3i = $d3i + 1;
						$pi = $pi + 1;
					}				
				}
				if (isset($mored2)) {
					$d2 = array_merge ($d2, $mored2);
					unset($mored2);
				}
				/* now add the new d2's to the previous set */
			}		
		}
			
		foreach ($d2 as $i => $try) { 				
			if ($try['day'] < 0) { /* we have a negative bymonthday, so must every iteration, work back from the last day */
				$d = new DateTime (
					$try['year'] .'-'.
					$try['month'] . '-'.
					'01 '.
					$try['hour'] . ':'.
					$try['minute'] . ':'.
					$try['second'], $tz);  /* must use the same timezone, not the php default as else daylight saving etc could cause havoc with recurring entries */
				$d = amr_get_last_xday_of_month ($d, $try['day']); 	
				if (ICAL_EVENTS_DEBUG)  {
					echo '<br /> Got last '.$try['day'].' day of month: '.$d->format('c');
				}
				}
			else {	
				$d = new DateTime (
				$try['year'] .'-'.
				$try['month'] . '-'.
				$try['day'] . ' '.
				$try['hour'] . ':'.
				$try['minute'] . ':'.
				$try['second'], $tz);  /* must use the same timezone, not the php default as else daylight saving etc could cause havoc with recurring entries */
			}	
			$start[] = $d;
		}
		if (isset($p['day']) or isset($p['bymonthday'])) {   /* Note: BYMONTHDAY and BYDAY can occur at same time and must be used together */
			echo '<hr><h3>GOT IT</h3>';
			var_dump($p);
		}
		
		if (isset ($p['byday'])) {
			foreach ($start as $i => $s) {
				$start2[] = amr_process_byday ($s, $p['byday'], $wkst );
			}
		unset($start);
		$start = array();
			foreach ($start2 as $i => $s) {
				$start = array_merge($start, $s);
			}
		}

		return ($start);   /* these are the parsed arrays */			
}		

/* ---------------------------------------------------------------------------------------------------- */
function amr_increment_datetime ($dateobject, $int) {
	/* note we are only incrementing by the freq - can only be one?   */ 	/* Now increment and try next repeat  
	check we have not fallen foul of a by -or is that elsewhere ?? */
	if ((!isset ($int)) or (!is_array($int))) {echo 'unexpected error: no interval';return (false);}
	foreach ($int as $i=>$interval) {  /* There should actually only be one */
		date_modify($dateobject,'+'.$interval.' '.$i);
	}
	return ($dateobject);
}
/* -------------------------------------------------------------------------------- */
function amr_check_bys($do, $bys) { /* Do we really need this ?*/
/* check whther the date passed meets any 'bys' criteria 
negday, byyearday, byweekno, negday (was bymonthday)
 bydays - can be be BYDAY=SU,MO,TU,WE,TH,FR,SA
BYDAY=1FR  1st friday, or BYDAY=1SU,-1SU 1st and last sunday
BYDAY=-2MO 2nd to last mon
return false if not a match with the by's */

if (ICAL_EVENTS_DEBUG)  echo '<br />Checking the bys';
foreach ($bys as $i => $b) {
	switch ($i) {
		case 'day': {
			$d = ($do->format('j')); /* Day of the month without leading zeros */
			foreach ($b as $j => $k) {
				if ($k<1) {} //{	echo 'Negative '.$i.' not yet supported '; }
				else if (!($d === $k)) return (false); 
			}

			break;
		}	
		case 'byweekno': {/* need week start too */
			echo '<br>BYWEEKNO not supported yet';
			break;
		}
		case 'byday': {
			$d = strtoupper(substr(($do->format('D')), 0 , 2));
//			if (ICAL_EVENTS_DEBUG) echo '<br>WDay '.$d.' for '. $do->format('c');
	
			foreach ($bys['byday'] as $j => $day) {
				if (substr ($day, 0, 1) === '-') {$day = substr($day, 1,2);}
				if (!($d === $day)) return(false);
				}
			}
			break;		
		case 'month': {
			$d = ($do->format('n')); /* numeric month */
			if (ICAL_EVENTS_DEBUG) echo '<br>Month is '.$d.' for '. $do->format('c');

			foreach ($b as $j => $k) {
				if ($k<1)	echo 'Negative '.$i.' not yet supported '; 
				else if (!($d === $k)) return(false);
			}
			break;
		}
		case 'wkst': 
		default: echo '<br>Unsupported BY '.$i.' found in data';
	}
}
return(true);
}

/* -------------------------------------------------------------------------------- */
function amr_get_repeats (
	$starts, /* an array of date strings */
	$dstart, 
	$until, /* array of parameters such as $p['until']*/
	$count,
	$int, /* array of intervals */
	$bys = null /* and arry of (bydays, byweekno, byyearday arrays */
	) {
	$i = 0;	
	$repeats = array();
		// v2.3.2 $try = new DateTime();		/* our work object - don't need, as clone will create object */	
		
		
		foreach ($starts as $s => $d) {	
			if (!(is_object($d)))  { echo '<br />Unexpected non date value'.$d.'<br />'; return (false); }		
			$try = new DateTime();
			$try = clone ($d);

			while (($i < $count) and ($try->format('c') <= $until->format('c')) )   {
			/* increment and see if that is valid.	Note that the count here is just to limit the search, we may still end up with too many and will check that later*/		

				if ($try->format('c') >= $dstart->format('c')) {  /* start our counts from here */
				/* we may need to check days of week etc here becuase if there is a combination rule, it may be tricky  */		
					if (!isset($bys) or amr_check_bys($try, $bys)) {
						if (ICAL_EVENTS_DEBUG)  echo '<br />bys ok <br />';
						$repeats[$i] = new DateTime();				
						$repeats[$i] = clone ($try);
						$i = $i+1;
					}
					else if (ICAL_EVENTS_DEBUG)  echo '<br />No bys or bys wrong';
				}
				else if (ICAL_EVENTS_DEBUG) { echo '<br>Date '.$try->format('c').' too early';}
	
				$try = amr_increment_datetime ($try, $int);		
				
				if (ICAL_EVENTS_DEBUG) {echo '<hr>bys:'; var_dump($bys);}
							
				if (isset ($bys['day'])) {
					
					$days = intval($bys['day']);
					echo ' got them from'.$bys['day'].' to '.$days;
					if ($days < 0) { 
						$try=amr_get_last_xday_of_month($try,$days );
						if (ICAL_EVENTS_DEBUG) {echo '<br />Adjusted increment to last '.$bys['day'].'  day of month ';}
					}
				}
			}
		}
	
	return ($repeats);
}
/* --------------------------------------------------------------------------------------------------- */
function amr_parseRRULE($rrule)  {    
	 /* RRULE's can vary so much!  Some examples:
		FREQ=YEARLY;INTERVAL=3;COUNT=10;BYYEARDAY=1,100,200
		FREQ=WEEKLY;UNTIL=19971007T000000Z;WKST=SU;BYDAY=TU,TH
	 */
		$p = amr_parse_RRULEparams (explode (';', $rrule));
		return ($p);
	}

/* --------------------------------------------------------------------------------------------------- */
function amr_process_RRULE($p, $start, $end )  {    
	 /* RRULE a parsed array.  If the specified event repeats between the given start and
	 * end times, return one or more nonrepeating date strings in array 
	 */
	global	$amr_timeperiod_conv; /* converts daily to day etc */

		/* now we should have if they are there: $p[freq], $p[interval, $until, $wkst, $ count, $byweekno etc */	
		/* check  / set limits  NB don't forget the distinction between the two kinds of limits  */
		
		if (!isset($p['count'])) { $count = AMR_MAX_REPEATS; } /* to avoid any chance of infinite loop! */
		else $count = $p['count'];
		if (ICAL_EVENTS_DEBUG) echo '<br />Limiting the number of repeats to '.$count;
		if (!isset($p['until']))  $until = $end;	
		else { 
			$until = $p['until']; 
			if ($until > $end) {	$until = $end;	}		
		}
		if (amr_is_before ($until, $start )) { return(false); }/* if it ends before our start, then skip */
				
		/* now prepare out "intervales array for date incrementing eg: p[monthly] = 2 etc... Actualy there should only be 1 */

		if (isset($p["freq"])) { /* so know yearly, daily or weekly etc  - setup increments eg 2 yearsly or what */
			if (!isset ($p['interval'])) $p['interval'] = 1;	
			switch ($p['freq']) {
				case 'WEEKLY': $int['day'] = $p['interval'] * 7; break;
				default: {
					$inttype = $amr_timeperiod_conv[$p['freq']];
					$int[$inttype] = $p['interval']; 
				}
			}
		}
		else {/** log error **/ error_log( 'No freq - aborting for this recurrence');  return (false);}
			
		unset ($p['until']);  /* unset so we can use other params more cleanly */
		unset ($p['count']); unset ($p['freq']); unset ($p['interval']); 	
		$wkst = $p['wkst']; unset($p['wkst']);
		if (count($p) === 0) {$p=null; }  /* If that was all we had, get rid of it anyway */
			
		/*** we should leap forward until within one FREQ of our current start date ? or will that mess up th e numeric options?  leave for now*/
		
		if (!empty($p)) {
			if (isset ($p['specbyday'])) {
				if (ICAL_EVENTS_DEBUG) {echo '<br /><br /><h3>Special By day:</h3>';var_dump($p['specbyday']);}
				$repeats = amr_process_numericbydays($start, $p, $wkst, $count, $until, $int);
			}
			else { 		
				$poss = amr_process_easybys ($start, $p, $wkst);  /* get the easy date by's and setup initial starting dates */
				if (!empty($poss)) $repeats = amr_get_repeats ($poss, $start, $until, $count, $int, $p );
			}
		}
		else { /* No by's just one start */
			$poss[] = $start;
			if (!empty($poss)) $repeats = amr_get_repeats ($poss, $start, $until, $count, $int, $p );
		}
		if (ICAL_EVENTS_DEBUG and !(empty($repeats))) {
			echo '<hr>Possible Repeats:'; 
			foreach ($repeats as $i =>$r) {echo '<br>'.$r->format('D,c');};
		}			
	return ($repeats);
	
	}
/* ---------------------------------------------------------------------------- */
function amr_get_last_xday_of_month ($date, $x=-1)	{ /* helper function format passed is NB.  Can be used for last day of year and last day of month*/
/* php 'last day of month' not working? */
		$last = date_create ($date->format('Y-m-01 H:i:s'), $date->getTimezone()); /* set to first of month  */
		$last->modify('+1 month');
		$x = (int) $x;
		$last->modify($x.' days');
		return ($last);
}
/* ---------------------------------------------------------------------------- */
function amr_get_last_day_of_year ($date)	{ /* helper function format passed is NB.  Can be used for last day of year and last day of month*/
/* php 'last day of month' not working? */
		$last = date_create ($date->format('Y-12-01 H:i:s'), $date->getTimezone()); /* set to first of month  */
		$last->modify('+1 month');
		$last->modify('-1 day');
		return ($last);
}
/* ---------------------------------------------------------------------------- */
function amr_get_last_day ($date, $format)	{ /* helper function format passed is NB.  Can be used for last day of year and last day of month*/
/* php 'last day of month' not working? */
		$last = date_create ($date->format($format), $date->getTimezone()); /* set to first of month  */
		$last->modify('+1 month');
		$last->modify('-1 day');
		return ($last);
}

	/* ---------------------------------------------------------------------------- */
function amr_goto_byday ($dd, $byday, $sign='+')	{
/* from the given date, check the current day move forward, or backward a day at time till the day of week is the one we want  */
		$x = 0;
		$d2 = new DateTime();
		$d2 = clone ($dd); 		
		while ($x < 7) {
			$ax = strtoupper(substr($d2->format('D'), 0, 2));
//			echo '<br>Looking for '.$byday.'Trying '.$ax. ' for '.$d2->format('c');
			if ($ax === $byday)  {	return ($d2);	}
			else {
				date_modify ($d2,$sign.'1 day');		
				$x = $x+1;
//				echo ' Got '.$ax.'so Add a day ';
			}
		}
		return (false);
}			
/* ---------------------------------------------------------------------------- */
function amr_process_numericbydays ($start, $p, $wkst, $count, $until, $int)	{
/* we may have multiple bydays, however the count applies to the whole set, so we need to relimit here  */

	$repeats = array();
	if (is_array($p['specbyday'])) {
		foreach ($p['specbyday'] as $i=> $spec) {
			$reps = amr_process_numericbyday ($start, $spec, $p['byday'][$i], $wkst, $count, $until, $int);
			if (is_array($reps)) $repeats = array_merge($repeats, $reps);
		}
		/* limit the results to "count".  But they must be in date order */
		asort($repeats);
		$repeats = array_slice($repeats, 0, $count); /* return only the number of iterations requested */	
	} else { echo '<br />Unexpected Error in recurring rule: '; var_dump($p); }
	return ($repeats); 
}					
/* ---------------------------------------------------------------------------- */
function amr_process_numericbyday ($start, $specbyday, $byday, $wkst, $count, $until, $int)	{
/* we may have multiple bydays */

	$i = 0;	
	$try = new DateTime();
	$try = clone ($start);
	$thenumber = (int) (str_replace($byday,'', $specbyday));
	if (ICAL_EVENTS_DEBUG) { echo '<br />specybyday='.$specbyday.' byday='.$byday.' the number is '.$thenumber;}
	$loops = 0;

	while (($i < $count) and (amr_is_before ($try->format('c'),$until->format('c')) ))   {
		$loops = $loops+1;
		if ($loops > 100) {die ('Unexpected long loop?');}

		if (substr($specbyday, 0 , 1) === '-') {
					/* then we are dealing with a negative by day */
					if (ICAL_EVENTS_DEBUG) { echo '<br />Dealing with neg '.$specbyday;}
					if (isset($int['month'])) $last = amr_get_last_day($try,'Y-m-01 H:i:s');
					else if (isset($int['year']))  $last = amr_get_last_day($try,'Y-12-01 H:i:s');
					if (ICAL_EVENTS_DEBUG) {echo '<br /> Last day of month: '.$last->format('D c');}
					$try = amr_goto_byday($last,$byday,'-' );
					if (ICAL_EVENTS_DEBUG) { echo '<br/>Start with '.$try->format('D c').' and go back '.$thenumber;}
					if (is_object($try)) {
						if ($thenumber < -1) $try->modify(($thenumber+1).' weeks');  /* minus one because we are at the first already, so if looking for -2, we just need -1 more */
						if (ICAL_EVENTS_DEBUG) { echo '<br/>It is the '.$specbyday.'....'.$try->format('D c');}
					}
					else  {echo 'Unexpected Error, could  not find day of week: '.$byday; return (false);}	
					
				}
		else { /* We are dealing with positive numeric bydays */
			
				if (isset($int['month'])) $first = date_create ($try->format('Y-m-01 H:i:s'), $try->getTimezone()); /* set to first of month or interval  */
				else if (isset($int['year']))  $first = date_create ($try->format('Y-01-01 H:i:s'), $try->getTimezone()); /* set to first year */
				else echo'<br>Unexpected frequency in recurring byday rule';
				if (ICAL_EVENTS_DEBUG) {echo '<br /> First '.$first->format('D c').' '. $first->getTimezone()->getName();}
				$try = amr_goto_byday($first,$byday,'+' );

				if (is_object($try)) {
					$try->modify(($thenumber-1).' weeks');  /* minus one because we are at the first already, so if looking for 2, we just need 1 more */
					if (ICAL_EVENTS_DEBUG) echo '<br/>It is the '.$specbyday.'....'.$try->format('D c');
					}
				else  {echo 'Unexpected Error, could  not find day of week: '.$byday; return (false);}	
		}
				
		if (amr_falls_between($try, $start, $until)) {
				if (ICAL_EVENTS_DEBUG) echo ' In range<br /> ';
				$reps[$i] = new DateTime();
				$reps[$i]  = clone ($try);
				$i = $i+1;	
				if (ICAL_EVENTS_DEBUG) echo '<br/>We have '.$i.' of these bydays now, and count limit = '.$count;
									
		}
		else { /* not in our valid date range yet? */
			if (ICAL_EVENTS_DEBUG) echo ' but not in range....';
		}
		$try = amr_increment_datetime ($try, $int);
		
	}

//	var_dump($reps);
	return ($reps);
}	
/* --------------------------------------------------------------------------------------------------- */
function amr_process_RDATE($p, $start, $end, $limit)  {    
	 /* RDATE or EXDATE processes  a parsed array.  If the specified event repeats between the given start and
	 * end times, return one or more nonrepeating date strings in array 
	 
		RDATE:19970714T123000Z
		RDATE;TZID=US-EASTERN:19970714T083000
		RDATE;VALUE=PERIOD:19960403T020000Z/19960403T040000Z,19960404T010000Z/PT3H
		RDATE;VALUE=DATE:19970101,19970120,19970217,19970421,19970526,19970704,19970901,19971014,19971128,19971129,19971225
		
		should be passed as object now?  *** amr check!!
	 */

	$repeats = array();
	if (is_object ($p))	{
		if (isset($_REQUEST['debugexc'])) {echo '<br> Object passed '. amr_format_date('F j, Y g:i a',$p);}
		if (amr_falls_between($p, $start, $end));
		$repeats[] = $p;
		}
	else 	
	if (is_array($p)) {

		foreach ($p as $i => $r) {

			if (amr_falls_between($r, $start, $end))  $repeats[] = $r;
		}
	}
	else {
		if (isset($_REQUEST['debugexc'])) {error_log ('Not an Object, Not an array passed ', $p);}
		if (amr_falls_between($r, $start, $end))  $repeats[] = $p;
	}											
	return ($repeats);
	}
					