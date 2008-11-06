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
						echo '<br>Byday '.$k.' not yet supported, will try with '.$p['byday'][$j];
					}
					else $by2[] = $k;
				}	
			
		}
		if (isset ($BYWEEKNO)) 	{$p['byweekno'] = explode(',', $BYWEEKNO);}
		if (isset ($BYYEARDAY)) {$p['byyearday'] = explode(',', $BYYEARDAY);  }
		if (isset ($UNTIL)) 	{$p['until'] = amr_parseDateTime($UNTIL);}
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
			
		foreach (array('byyearday','byweekno', 'day') as $i => $b)	{
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
//		if (ICAL_EVENTS_DEBUG) {echo '<br>Parsed RRULE = '; print_r($p);	}
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
		$dd = clone $d;
		date_modify ($dd,'-'.$amr_rulewkst.' day');		
		$x = 0;
		while ($x < 7) {
			$ax = strtoupper(substr($dd->format('D'), 0, 2));
			if (ICAL_EVENTS_DEBUG) echo '<br>Trying day of week ='.$ax. ' for '.$d->format('c');
			if (in_array($ax, $bd)) { 
				$d2[] = $dd; 
				if (ICAL_EVENTS_DEBUG) echo ' Use this one!';
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
	if (ICAL_EVENTS_DEBUG) {echo '<br>Easybys starting '.$date->format('c');}
		$d = date_parse ($date->format('Y-M-j H:i:s'));	/* gives $d['year']  etc */	
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
		if (ICAL_EVENTS_DEBUG) {echo '<br>d2= '; var_dump($d2);}

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
			$d = new DateTime (
				$try['year'] .'-'.
				$try['month'] . '-'.
				$try['day'] . ' '.
				$try['hour'] . ':'.
				$try['minute'] . ':'.
				$try['second']);
			$start[] = $d;
		}
		
		if (ICAL_EVENTS_DEBUG) { echo '<br>Number of Start records so far for this rule '.count($start).' <br>' ;
			foreach ($start as $i=>$s) echo ' '.$s->format('c'); 
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
		if (ICAL_EVENTS_DEBUG) echo '<br>Number of Start Records after byday='.count($start).' <br>' ;
		return ($start);   /* these are the parsed arrays */			
}		

/* ---------------------------------------------------------------------------------------------------- */
function amr_increment_datetime ($dateobject, $int) {
	/* note we are only incrementing by the freq - can only be one?   */ 	/* Now increment and try next repeat  
	check we have not fallen foul of a by -or is that elsewhere ?? */

	if (!isset ($int)) return (false);

	foreach ($int as $i=>$interval) {  /* There should actually only be one */
		date_modify($dateobject,'+'.$interval.' '.$i);
	}
	return ($dateobject);
}
/* -------------------------------------------------------------------------------- */
function amr_check_bys($do, $bys) {
/* check whther the date passed meets any 'bys' criteria 
negday, byyearday, byweekno, negday (was bymonthday)
 bydays - can be be BYDAY=SU,MO,TU,WE,TH,FR,SA
BYDAY=1FR  1st friday, or BYDAY=1SU,-1SU 1st and last sunday
BYDAY=-2MO 2nd to last mon
return false if not a match with the by's */

foreach ($bys as $i => $b) {
	switch ($i) {
		case 'day': {
			$d = ($do->format('j'));
			if (ICAL_EVENTS_DEBUG) {echo '<br>Day of Month is '.$d.' for '. $do->format('c');	}
			foreach ($b as $j => $k) {
				if ($k<1)  {
					echo 'Negative '.$i.' not yet supported '; }
				else {
					if ($d === $k) return (true);
				}
			}
			if (ICAL_EVENTS_DEBUG) echo ' -- Reject ';
			return (false);  /*if a spec exists and it doesn't mathc any - must be reject */
			break;
		}	
		case 'byweekno': {/* need week start too */
			echo '<br>BYWEEKNO not supported yet';
			break;
		}
		case 'byday': {
			$d = strtoupper(substr(($do->format('D')), 0 , 2));
			if (ICAL_EVENTS_DEBUG) echo '<br>Day of week is '.$d.' for '. $do->format('c');
	
			foreach ($bys['byday'] as $j => $day) {
				if (substr ($day, 0, 1) === '-') {
					echo 'Negative day of week not yet supported '; }
				else {
					if ($d === $day) return (true);
				}
			}
			if (ICAL_EVENTS_DEBUG) echo ' reject ';
			return (false);  /*if a spec exists and it doesn't match any - must be reject */
			break;
		}
		
		case 'month': {
			$d = ($do->format('n'));
			if (ICAL_EVENTS_DEBUG) echo '<br>Month is '.$d.' for '. $do->format('c');

			foreach ($b as $j => $k) {
				if ($k<1)  {
					echo 'Negative '.$i.' not yet supported '; }
				else {
					if ($d === $k) return (true);
				}
			}
			if (ICAL_EVENTS_DEBUG) echo ' reject ';
			return (false);  /*if a spec exists and it doesn't mathc any - must be reject */
			break;
		}
		case 'wkst': 
		default: echo '<br>Unsupported BY '.$i.' found in data';
	}
}
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
	
		$try = new DateTime();		/* our work object */	
		foreach ($starts as $s => $d) {	
			if (ICAL_EVENTS_DEBUG) {echo '<br>Generating a set of repeats from this start: '.$try->format('c'). 'Until '. $until->format('c');	}
		
			$i = 0;

			$try = clone $d;
			while ($try and ($try <= $until) and ($i <= $count))   {
			/* increment and see if that is valid.	Note that the count here is just to limit the search, we may still end up with too many and will check that later*/		

				if ($try >= $dstart) {  /* start our counts from here */
				/*** amr add BYDAY etc checks in here>? */		

					if (!isset($bys) or amr_check_bys($try, $bys)) {
						$repeats[$i] = new DateTime();				
						$repeats[$i] = clone $try;
						$i = $i+1;
						if (ICAL_EVENTS_DEBUG) echo ' Saving '.$try->format('c');
					}
				}
				else if (ICAL_EVENTS_DEBUG) { echo '<br>Date '.$try->format('c').' too early';}
	
				$try = amr_increment_datetime ($try, $int);		
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
function amr_process_RRULE($p, $start, $end, $limit)  {    
	 /* RRULE a parsed array.  If the specified event repeats between the given start and
	 * end times, return one or more nonrepeating date strings in array 
	 */
	global	$amr_timeperiod_conv; /* converts daily to day etc */
					
		/* now we should have if they are there: $p[freq], $p[interval, $until, $wkst, $ count, $byweekno etc */	
		/* check  / set limits  NB don't forget the distinction between the two kinds of limits  */
		if (!isset($p['count'])) { $count = $limit; }
		if (!isset($p['until'])) { $until = $end;	}
		else { 
			$until = $p['until']; 
			if ($until > $end) {	$until = $end;	}		
		}
		if (amr_is_before ($until, $start )) { /* if it ends begfore our start, then skip */
				if (ICAL_EVENTS_DEBUG) {
					echo '<br>End '.var_dump($until).'before our requested start '. var_dump($start);
				}
		}
		
		if (ICAL_EVENTS_DEBUG) {
			echo '<br>RRULE Parameters: start = '.$start->format('c')
				. ' until = '.$until->format('c').' count = '.$count .'wkst = '. $p['wkst'].'<br>' ; var_dump($p);
		}
				
		/* now prepare out "intervales array for date incrementing eg: p[monthly] = 2 etc... Actualy there should only be 1 */

		if (isset($p["freq"])) { /* so know yearly, daily or weekly etc  - setup increments eg 2 yearsly or what */
			if (!isset ($p['interval'])) {
				$p['interval'] = 1;	
			}		
			switch ($p['freq']) {
				case 'WEEKLY': $int['day'] = $p['interval'] * 7; break;
				default: {
					$inttype = $amr_timeperiod_conv[$p['freq']];
					$int[$inttype] = $p['interval']; 
				}
			}
		}
		else {echo '<br>No freq - aborting for this recurrence'; var_dump($p); return (false);}
			
		unset ($p['until']);  /* unset so we can use other params more cleanly */
		unset ($p['count']); unset ($p['freq']); unset ($p['interval']); 	
		$wkst = $p['wkst']; unset($p['wkst']);
		if (count($p) === 0) {$p=null; }  /* If that was all we had, get rid of it anyway */
			
		if (!empty($p)) $poss = amr_process_easybys ($start, $p, $wkst);  /* get the easy date by's and setup initial starting dates */
		else {
			if (ICAL_EVENTS_DEBUG) {echo '<br>No bys, Just one start'; $start->format('c');}
			$poss[] = $start;
			}

		$repeats = amr_get_repeats ($poss, $start, $until, $count, $int, $p );					

	return ($repeats);
	
	}
	
/* --------------------------------------------------------------------------------------------------- */
function amr_process_RDATE($p, $start, $end, $limit)  {    
	 /* RDATE or EXDATE processes  a parsed array.  If the specified event repeats between the given start and
	 * end times, return one or more nonrepeating date strings in array 
	 
		RDATE:19970714T123000Z
		RDATE;TZID=US-EASTERN:19970714T083000
		RDATE;VALUE=PERIOD:19960403T020000Z/19960403T040000Z,19960404T010000Z/PT3H
		RDATE;VALUE=DATE:19970101,19970120,19970217,19970421,19970526,19970704,19970901,19971014,19971128,19971129,19971225
	 */
	if (ICAL_EVENTS_DEBUG) {echo '<br>$p = '; var_dump($p);}
	$repeats = array();
	if (is_array($p)) {
		foreach ($p as $i => $r) {
			if (ICAL_EVENTS_DEBUG) {echo ' '.$i.' = ';var_dump($r);}
			if (amr_falls_between($r, $start, $end))  $repeats[] = $r;
		}
	}
	else 			if (amr_falls_between($r, $start, $end))  $repeats[] = $p;
												
	return ($repeats);
	}
					