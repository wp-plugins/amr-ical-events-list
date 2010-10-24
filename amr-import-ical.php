<?php
/*
 * This file incudes functions for parsing iCal data files duringan import.
 /* It endeavours to parse as incluisive;y as much as possible.
 /* It includes functions to cache the file
 /* It is not a validator!
 /* The function will return a nested array 
	properties
		vevents
			event1
				parameters
				repeatable parameters
					repeat 1
					repeat 2
			event2
		vtodos etc
 *
 * The iCal specification is available online at:
 *	http://www.ietf.org/rfc/rfc2445.txt
 *
 */

/* ---------------------------------------------------------------------- */
	/*
	 * Return the full path to the cache file for the specified URL.
	 */
	function get_cache_file($url) {		
		return get_cache_path() .'/'. amr_get_cache_filename($url);
	}
/* ---------------------------------------------------------------------- */
	/*
	 * Attempt to create the cache directory if it doesn't exist.
	 * Return the path if successful.
	 */
	function get_cache_path() {
	global $amr_options;
		$cache_path = (ICAL_EVENTS_CACHE_LOCATION. '/ical-events-cache');
		if (!file_exists($cache_path)) { /* if there is no folder */
			if (wp_mkdir_p($cache_path, 0777)) {
				printf('<br />'.__('Your cache directory %s has been created','amr_ical_list_lang'),'<code>'.$cache_path.'</code>');
			}
			else {
				die( '<br />'.sprintf(__('Error creating cache directory %s. Please check permissions','amr_ical_list_lang'),$cache_path)); 
			}
		}
		return $cache_path;
	}
/* ---------------------------------------------------------------------- */
	/*
	 * Return the cache filename for the specified URL.
	 */
	function amr_get_cache_filename($url) {
		$extension = ICAL_EVENTS_CACHE_DEFAULT_EXTENSION;
		$matches = array();
		if (preg_match('/\.(\w+)$/', $url, $matches)) {
			$extension = $matches[1];
		}
		return md5($url) . ".$extension";
	}
/* ---------------------------------------------------------------------- */
	/*
	 * Cache the specified URL and return the name of the
	 * destination file.
	 */
	function amr_cache_url($url, $cache=ICAL_EVENTS_CACHE_TTL) {
	global $amr_lastcache;
	global $amr_globaltz;	
		If (ICAL_EVENTS_DEBUG) echo '<br />url: '.$url; 	
		$file = get_cache_file($url);
		if ( file_exists($file) ) {
			$c = filemtime($file);		
			if ($c) $amr_lastcache = date_create(strftime('%c',$c));
			else $amr_lastcache = '';
		}
		else {
			$c = false;
			$amr_lastcache = date_create(strftime('%c',0));	
			}
		if ( isset($_REQUEST['nocache']) or isset($_REQUEST['refresh']) 
			or (!(file_exists($file))) or ((time() - ($c)) >= ($cache*60*60))) 
		{
			If (ICAL_EVENTS_DEBUG) {
				echo '<br>Get ical file remotely, it is time to refresh or it is not cached: <br />'; 
				print_r ($url);
				}	
			if (version_compare( PHP_VERSION,'5.2.13', '>')) $u = filter_var ($url, FILTER_VALIDATE_URL);
			else $u = $url;
			if (!($u) ) { _e('Invalid URL','amr_ical_list_lang'); return(false);}
//			$check = get_headers ( $url  , 1  );
			$check = wp_remote_get ($u);
			
			if (( is_wp_error($check) ) or  (isset ($check['response']['code']) and ($check['response']['code'] == 404))
			or (isset ($check[0]) and preg_match ('#404#', $check[0]))) {  /* is the last bit still meaningful or needed ? */
			
				if (is_wp_error($check)) $text = $check->get_error_message();
				else $text = '';
//				$text .= '&nbsp;'.sprintf(__('Error getting calendar file: %s','amr_ical_list_lang'), $url);		
				if ( file_exists($file) ) { 
					$text .= '&nbsp;'.sprintf(__('Using File last cached at %s','amr_ical_list_lang'), $amr_lastcache->format('D c'));	
					echo '<br /><span style="text-align:center; font-size:small;"><em>'.__('Warning: Events may be out of date. ','amr_ical_list_lang').$text.'</em></span>';	
					return($file);
					}
				else {
					_e('No cached ical file for events','amr_ical_list_lang');	
					echo $text;
					return (false);	
				}
				
			}
			
//			$data = wp_remote_fopen($url);
			echo '<br/>Response code'.$check['response']['code'];
			echo '<br/>Content type'.$check['headers']['content-type'];
//			echo '<br/>Content length'.$check['headers']['content-length'];

			$data = $check['body'];
			
			if (($data) ) { /* now save it as a cached file */
				if ($dest = fopen($file, 'w')) {
					if (!(fwrite($dest, $data))) die ('Error writing cache file'.$dest);
					fclose($dest);
					$amr_lastcache = date_create (date('Y-m-d H:i:s'));
				}
				else  die('Error opening or creating the cached file'.$file);
			}
			else {
				echo '<br>Error opening remote file for refresh '.$url;
				echo '<br><br><strong>Please check you are using shortcode syntax in your page [iCal url], not [iCal:url].  Plugin moved to shortcode usage only several versions back, after maintaining compatibility for a period.  This may cause a remote url problem.</strong><br>';
				return ($file);
				}
			if (!isset($amr_lastcache))	$amr_lastcache = date_create (date('Y-m-d H:i:s'), $amr_globaltz);
		}
		return ($file);
	}
	
	
/* ---------------------------------------------------------------------- */	
    function amr_parseAttendee	($arraybycolon)    { /* receive full string parsed to array  */
	if (ICAL_EVENTS_DEBUG) {echo '<br/>Attendees to parse <br />'; var_dump($arraybycolon);}
	$attendees = amr_parseOrganiser($arraybycolon);
	if (ICAL_EVENTS_DEBUG) {echo '<br/>Using parse organiser gace us  <br />'; var_dump($attendees);}
	return ($attendees);
}	
/* ---------------------------------------------------------------------- */	
    function amr_parseOrganiser($arraybycolon)    { /* receive full string parsed to array 
	[0]=>ORGANIZER;SENT-BY="mailto
	[1]=>dwood@uoguelph.ca":mailto:ovcweb@uoguelph.ca
	
	or 
	[0]=>ORGANIZER;CN=Webmaster - OVC;SENT-BY="mailto 
	[1] => bagunn@uoguelph.ca":mailto:ovcweb@uoguelph.ca
	
	*/
	if (ICAL_EVENTS_DEBUG) {echo '<br/>Organiser to parse <br />'; var_dump($arraybycolon);}
	$org = array();
	$p0 = explode(';',$arraybycolon[0]);
	$m = explode(':',$arraybycolon[1]);
//	if (ICAL_EVENTS_DEBUG) {echo '<br/>m : <br />'; var_dump($m); echo '<br/>p0 : <br />'; var_dump($p0);}
	foreach ($m as $i => $m2) {
		if (strtoupper($m2) == 'MAILTO') $mailto = rtrim($m[$i+1],'"');
	}

	foreach ($p0 as $i => $p) {
		$p1 = explode('=',$p);
		if (isset ($p1[0]))  {	
			$org['type'] = $p1[0]; /* if (!empty($p1[1])) $org['typevalue'] = $p1[1];   *** Parse this properly if we wantto handle complex attendees */
			if ( ($p1[0] == 'SENT-BY') and (!empty($p1[1]))) {
				$sentby = rtrim($m[0],'"');
				$org['SENT-BY'] = $sentby;
			}
			else if (($p1[0] == 'CN') and (!empty($p1[1]))) $org['CN'] = rtrim( $p1[1], '"');
		}
	}
	
	if (!empty($mailto)) $org['MAILTO'] = $mailto;
	return ($org);
    }
/* ---------------------------------------------------------------------- */	
    /**
     * Parse a Time Period field.
     */
    function amr_parsePeriod($text,$tzobj)    {
        $periodParts = explode('/', $text);
        if (!($start = amr_parseDateTime($periodParts[0], $tzobj))) return (false);	
        if ($duration = amr_parseDuration($periodParts[1])) return array('start' => $start, 'duration' => $duration);
		else {
			if (!($end = amr_parseDateTime($periodParts[1], $tzobj))) return (false);
			else {
				return array('start' => $start, 'end' => $end);
			}
		}
    }
	/* ---------------------------------------------------------------------- */	
	   /**
     * Parses a DateTime field and returns a datetime object, with either it's own tz if it has one, or the passed one
     */
    function amr_parseDateTime($d, $tzobj)    {
		global $amr_globaltz;
		global $utczobj;
		/*  	19970714T133000            ;Local time
			19970714T173000Z           ;UTC time 
			tz dealt with already ?*/
			
		if ((substr($d, strlen($d)-1, 1) === 'Z')) {  /*datetime is specifed in UTC */
			$tzobj = $utczobj;
			$d = substr($d, 0, strlen($d)-1);			
		}		
	
		$date = substr($d,0, 4).'-'.substr($d,4, 2).'-'.substr($d,6, 2);
		if (strlen ($d) > 8) {	
			$time = substr($d,9 ,2 ).':'.substr($d,11 ,2 )  ; /* has to at least have hours and mins */
		}		
		else $time = '00:00';
		if (strlen ($d) > 13) {
			$time .= ':'.substr($d,13 ,2 );
		}
		else $time .= ':00';
		/* Now create our date with the timezone in which it was defined , or if local, then in the plugin glovbal timezone */
		try {	$dt = new DateTime($date.' '.$time,	$tzobj); }
		catch(Exception $e) {
			echo '<br />Unable to create DateTime object.<br />'.$e->getMessage();
			return (false);
		}
	return ($dt);
    }
	/* ---------------------------------------------------------------------- */
    /* Parses a Date field. */
    function amr_parseRange($range, $daterange, $tzobj)    {  /* 
  For RECURRENCE-ID;
  Strings like:
 VALUE=DATE:19960401
 RANGE=THISANDFUTURE:19960120T120000Z
 RANGE=THISANDPRIOR:19960120T120000Z
	*/	
		If (isset ($_REQUEST['debugexc'])) {	echo '<br />Got Range '.$range.' with '.$daterange.'<br />';	}
		$r = explode (':', $daterange);
		if (!($thisanddate = amr_parseDateTime($r[1], $tzobj))) return (false);
		If (isset ($_REQUEST['debugexc'])) {	echo '<br />Got range '.$range.' "THISAND" date '.$thisanddate ->format('c').'<br />';	}	
		return (array('RANGE'=>$p[0],'DATE'=> $thisanddate));
    }
	/* ---------------------------------------------------------------------- */
    /* Parses a Date field. */
    function amr_parseDate($text, $tzobj)    {  /* 
		 VALUE=DATE:
		 19970101,19970120,19970217,19970421
		   19970526,19970704,19970901,19971014,19971128,19971129,19971225
		   VALUE=DATE;TZID=/mozilla.org/20070129_1/Europe/Berlin:20061223
	*/	
		$p = explode (',',$text); 	/* if only a single will still return one array value */
		foreach ($p as $i => $v) {
			try {
				$dates[] =  new DateTime(substr($v,0, 4).'-'.substr($v,4, 2).'-'.substr($v,6, 2), $tzobj);		
			}	
			catch(Exception $e) {
				echo '<br />Unable to create DateTime object.<br />'.$e->getMessage();
				return (false);
			}
		}
		return ($dates);
		
    }
	/* ------------------------------------------------------------------ */
	function amr_parseTZDate ($value, $tzid) {	
		$tzobj = amr_parseTZID($text); 
			
		if (!($d = amr_parseDateTime ($value, $tzobj))) return(false);
		else return ($d);
	}
	/* ------------------------------------------------------------------ */	
    function amr_get_timezone_cities () {
	    $timezone_identifiers = DateTimeZone::listIdentifiers();   
/* Africa/Abidjan
Africa/Accra
Africa/Addis_Ababa
Africa/Algiers
Africa/Asmara
*/
	    foreach( $timezone_identifiers as $i=> $value ){
			$c = explode("/",$value);//obtain continent,city   
			$tzcities[$value]['continent'] = $c[0];
	        if (isset($c[1])) $tzcities[$c[1]] = $value;
			else $tzcities[$c[0]] = $value;
              
	     } 
		//print_r($tzcities);	
		return ($tzcities);
	}	
	/* ------------------------------------------------------------------ */	
   function amr_parseTZID($text)    {	
   global $amr_globaltz;
   /* take a string that may have a olson tz object and try to return a tz object */  
   /* accept long and short TZ's, --- assume website tz if ot valid eg Zimbra's: GMT+01.00/+02.00 */
		$icstzid = trim($text,'"=' ); /* check for enclosing quotes like zimbra issues */
		
		$globaltzstring = timezone_name_get($amr_globaltz);
		if (!($globaltzstring == $icstzid	)) {/* if the timezone matches the wordpress or shortcode time zone, then we are cool ! */
			/* else try figure the timezone out */
//			$strip = array ('(',' ');
//			$icstzid = str_replace($strip,'',$icstzid);
			$gmtend = stripos($icstzid,')'); /* do we have a brackedt GMT ? */
			if (isset ($_REQUEST['tzdebug'])) {echo '<br/>gmtend = '.$gmtend.' in string '.$icstzid ; }
			if (!empty ($gmtend) ) {
				$icstzid = str_replace(')','/',$icstzid);
				$icstzcities = explode ('/',$icstzid); /* could be commas, could be slashes */
				if (isset ($_REQUEST['tzdebug'])) {echo '<br/>strip the gmt out '; print_r($icstzcities);}
				$gmt = stripos(  $icstzid, 'GMT'); /* do we have a brackedt GMT ? */
				if (!empty($gmt)) unset ($icstzcities[0]); /* don't want the GMT - potentially misleading */
			}
			else { /* Maybe we have a list of cities and maybe we do not */
				$icstzcities = array();
				$temp = explode (',',$icstzid); /* could be commas, could be slashes */
				foreach ($temp as $temp2) {
					$temp3 = explode ('/',$temp2); 
					$icstzcities = array_merge($icstzcities, $temp3);
					}
				}
			foreach ($icstzcities as $i=>$icscity) $icstzcities[$i] = trim($icscity,' ');
			if (isset ($_REQUEST['tzdebug'])) { echo '<br />Cities? <br />';print_r($icstzcities);}
			$globalcontcity = explode ('/',$globaltzstring);
			if (isset ($globalcontcity[1]) ) $globalcity = $globalcontcity[1];
			else $globalcity = $globalcontcity[0];
			if (isset ($_REQUEST['tzdebug'])) {
				echo '<hr> text = '.$text.'<br/>icstzid = '.$icstzid.'<br /> wordpress tz = '.$globalcity.' <br >'; print_r($icstzcities);
			}
			if (in_array($globalcity, $icstzcities)) { /* if one of the cities in the tzid matches ours, again we can use the matched one */
				$tzname = $globaltzstring;
			}
			else {
				$alltzcities = amr_get_timezone_cities ();
				if (isset($alltzcities[$icstzid])) { /* then it is a normal php timezone we know about, so we can proceed */
					$tzname = $icstzid;
				}	
				else {
					foreach ($icstzcities as $i=>$c) {
						if (isset ($alltzcities[$c] )) { /* try each of the cities if we have mutiple */
							$tzname = $alltzcities[$c];
							break;
						}
					}
				}					
			}
			/* */
		}
		else $tzname = $icstzid;
		if (!isset ($tzname)) { /* see if we do it with GMT after all ? */
			if (isset($icstzcities[0])) {
				$tryoffset = str_replace('GMT','',$icstzcities[0]);  
				$tzname = amr_getTimeZone($tryoffset);
			}
			else {
				$tzname = $globaltzstring;
				$emessage = 'Unable to deal with timezone like this: '.$text;
				echo '<!-- '.$emessage.' -->';
				if (isset ($_REQUEST['tzdebug']) or ICAL_EVENTS_DEBUG) {
					echo  '<b>'.$emessage.'</b>';
					echo '- Making an assumption! Using '.$tzname.'<br />';
				}				
			}
		}
		if (isset ($_REQUEST['tzdebug'])) echo '<br /><b>Timezone must be: </b> '.$tzname.'<br />';
		try {
				$tz =  timezone_open($tzname);	
			}	
			catch(Exception $e) {
				echo '<br />Unable to create Time zone object., Using wp default.<br />'.$e->getMessage();
				return ($amr_globaltz);
			}
		return ( $tz);
    }		
/* ------------------------------------------------------------------ */
   function amr_parseSingleDate($VALUE='DATE-TIME', $text, $tzobj)	{
   /* used for those properties that should only have one value - since many other dates can have multiple date specs, the parsing function returns an array 
	Reduce the array to a single value */
		$arr = amr_parseVALUE($VALUE, $text, $tzobj);		
		if (is_array($arr)) {
			if (count($arr) > 1) {
				error_log ( '<br>Unexpected multiple date values'.$text);
			}
			else return ($arr[0]);
		}
		return ($arr);
	}
	
	/* ---------------------------------------------------------------------- */	
   function amr_deal_with_tzpath_in_date ( $tzstring )	{
   /* Receive something like   /mozilla.org/20070129_1/Europe/Berlin 
	and return a tz object */
		$tz = explode ('/',$tzstring);
		$l = count ($tz);
		if ($l>1) { 
			$tzid= $tz[$l-2].'/'.$tz[$l-1];
		}
		else $tzid = $tz[0] ;
		$tzobj = timezone_open  ( $tzid );
		If (ICAL_EVENTS_DEBUG or isset ($_REQUEST['tzdebug'])) {
			echo '<br />Timezone Reduced to: '.$tzid.' Result of timezone object creation:';
			print_r($tzobj);
		}
		return ($tzobj); 
	}
	/* ---------------------------------------------------------------------- */	
   function amr_parseVALUE($VALUE, $text, $tzobj)	{
	/* amr parsing a value like 
	VALUE=PERIOD:19960403T020000Z/19960403T040000Z,	19960404T010000Z/PT3H
	VALUE=DATE:19970101,19970120,19970217,19970421,..	19970526,19970704,19970901,19971014,19971128,19971129,19971225
	VALUE=DATE;TZID=/mozilla.org/20070129_1/Europe/Berlin:20061223	*/
		switch ($VALUE) {
			case 'DATE-TIME': { 
				if (!($d = amr_parseDateTime($text, $tzobj))) return (false);
				else return ($d); 
				}
			case 'DATE': {if (!($d = amr_parseDate($text, $tzobj))) return (false);
						else return ($d); }
			case 'PERIOD': {if (!($d = amr_parsePeriod($text, $tzobj))) return (false);
						else return ($d); }
			default: { /* something like DATE;TZID=/mozilla.org/20070129_1/Europe/Berlin */
				$p = explode (';',$VALUE);
				if (!($p[0] === 'DATE')) {
					if (ICAL_EVENTS_DEBUG) {echo 'Error: Unexpected data in file '; print_r($p);}
					return (false);
					}
				else { 
					if (substr ($p[1], 0, 4) === 'TZID') {/* then we have a weird TZ */
						$tzobj = amr_deal_with_tzpath_in_date (substr($p[1],5)); /* pass the rest of the string over for tz extraction */
						if (!($d = amr_parseDate($text, $tzobj))) return (false);
						else return ($d); 
					}
					else {
						if (ICAL_EVENTS_DEBUG) {echo 'Error: Unexpected data in file '; print_r($p[1]);} 
						return (false);
					};
				}
			}
			return (false);
		}
	}
/* ---------------------------------------------------------------------- */		
/**
     * Parse a Duration Value field.
 */
    function amr_parseDuration($text)
    {
	/*
	A duration of 15 days, 5 hours and 20 seconds would be:  P15DT5H0M20S
	A duration of 7 weeks would be:  P7W, can be days or weeks, but not both
	we want to convert so can use like this +1 week 2 days 4 hours 2 seconds ether for calc with modify or output.  Could be neg (eg: for trigger)
	*/
        if (preg_match('/([+]?|[-])P(([0-9]+W)|([0-9]+D)|)(T(([0-9]+H)|([0-9]+M)|([0-9]+S))+)?/', 
			trim($text), $durvalue)) {
			
			/* 0 is the full string, 1 is the sign, 2 is the , 3 is the week , 6 is th T*/
			
			if ($durvalue[1] == "-") {  // Sign.
                $dur['sign'] = '-';
            }
            // Weeks
		    if (!empty($durvalue[3])) $dur['weeks'] = rtrim($durvalue[3],'W');  
			
            if (count($durvalue) > 4) {                // Days.
				if (!empty($durvalue[4])) $dur['days'] = rtrim($durvalue[4],"D");  
            }
            if (count($durvalue) > 5) {                // Hours.
				if (!empty($durvalue[7])) $dur['hours'] = rtrim($durvalue[7],"H"); 
          
                if (isset($durvalue[8])) {    // Mins.
					$dur['mins'] = rtrim($durvalue[8],"M");  
                }              
                if (isset($durvalue[9])) { // Secs.
					$dur['secs'] = rtrim($durvalue[9],"S");  
                }
            }    
            return $dur;
			
        } else {
            return false;
        }
    }
/* ---------------------------------------------------------------------- */
function amr_track_last_mod($date) {
global $amr_last_modified;
if (empty ($amr_last_modified)) $amr_last_modified = date_create('0000-00-00 00:00:01');
if ($date->format('c') > $amr_last_modified->format('c')) {
	$amr_last_modified = clone ($date);
	}
}
/* ---------------------------------------------------------------------- */
function amr_parseRDATE ($string, $tzobj ) {
/* could be multiple dates after value */
	$rdate = explode(':',$string);   /* $VALUE=DATE: and a series of dates (no time) */
	if (isset($rdate[0])) {
		if (($rdate[0] = 'VALUE=DATE') and (isset($rdate[1])) ) {
			$rdate =  explode(',',$rdate[1]); /* that' sall we are doing for now */
			foreach ($rdate as $i => $r)  {
					$dates[$i] = array_shift(amr_parseValue ('DATE', $r, $tzobj)); 
					/*returns array, but there should only be 1 value */
			}
			return($dates);
		}
		else if (($rdate[0] = 'VALUE=PERIOD') and (isset($rdate[1])) ) {
		 "HELP cannot yet deal with RDATE with VALUE=PERIOD"; return (false);
			}
			else {
				parse_str($string);
				if (isset ($TZID)) return (amr_parseTZDate ($string, $TZID));
				else {	/* must be just a date */
					if (!($d = amr_parseDateTime ( $string, $tzobj))) return (false);
					else return ($d); 
				}
			}
	}			
	else return (false);
}
/* ---------------------------------------------------------------------- */
function amr_parse_property ($parts) {
/* would receive something like array ('DTSTART; VALUE=DATE', '20060315')) */
/*  NOTE: parts[0]    has the long tag eg: RDATE;TZID=US-EASTERN
		parts[1]  the bit after the :  19960403T020000Z/19960403T040000Z, 19960404T010000Z/PT3H
		IF 'Z' then must be in UTC
		If no Z
*/
global $amr_globaltz;
	$p0 = explode (';', $parts[0], 2);  /* Looking for ; VALUE = something...;   or TZID=... or both???*/
	if (isset($p0[1])) { /* ie if we have some modifiers like TZID, or maybe just VALUE=DATE , note parse_str s dangerous */
//		if (ICAL_EVENTS_DEBUG) {echo '<br/>*** p0[1]'.$p0[1];}
		if (stristr($p0[1], 'TZID')) {
		    /* Normal TZ, not the one with the path eg:  DTSTART;TZID=US-Eastern:19980119T020000 or  zimbras TZID="GMT+01.00/+02.00 */
			$TZID = substr($p0[1], 4 );
			$tzobj = amr_parseTZID($TZID);
		}  /* should create datetime object with it's own TZ, datetime maths works correctly with TZ's */
		else {/* might be just a value=date, in which case we use the global tz?  no may still have TZid */
			$tzobj = $amr_globaltz;
		;}
	}
	else $tzobj = $amr_globaltz;  /* Because if there is no timezone specified in any way for the date time then it must a floating value, and so should be created in the global timezone.*/
	switch ($p0[0]) {
		case 'CREATED':
		case 'COMPLETED': 
		case 'LAST-MODIFIED':
		case 'DTSTART':   
		case 'DTEND':
		case 'DTSTAMP':		
		case 'DUE':	
			if (isset($VALUE)) { 
				$date = amr_parseValue($VALUE, $parts[1], $tzobj);	} 
/*				return (amr_parseSingleDate($VALUE, $parts[1], $tzobj));	} */
			else {
				$date = amr_parseSingleDate('DATE-TIME', $parts[1], $tzobj); 
			}
			if (($p0[0] === 'LAST-MODIFIED') or ($p0[0] === 'CREATED')) amr_track_last_mod($date);
			return ($date);
		case 'ALARM':
		case 'RECURRENCE-ID':  /* could also have range ?*/
			if (isset($VALUE)) { 
				return (amr_parseValue($VALUE, $parts[1], $tzobj));	}
			elseif (isset($RANGE)){
				return (amr_parseRange($RANGE, $parts[1], $tzobj)); 
				}
			else {
				return (amr_parseSingleDate('DATE-TIME', $parts[1], $tzobj)); 
				}
		case 'EXRULE':	
		case 'RRULE': return (amr_parseRRULE($parts[1]));	
		case 'BDAY':	
			return (amr_parseDate ($parts[1])); 
		
		case 'EXDATE':
		case 'RDATE':  
			return (amr_parseRDATE ($parts[1],$tzobj)); 
		case 'TRIGGER': /* not supported yet, check for datetime and / or duration */
		case 'DURATION':
			return (amr_parseDuration ($parts[1])); 
		case 'FREEBUSY':
			return ( amr_parsePeriod ($parts[1])); 	
		case 'TZID': /* ie TZID is a property, not part of a date spec */
			return ($parts[1]);
		case 'ORGANIZER': {
			return(amr_parseOrganiser($parts));
			}
		case 'ATTENDEE': {
			return(amr_parseAttendee($parts));
			}	
		default:	
			if (isset ($parts[1])) return (str_replace ('\,', ',', $parts[1]));  /* replace any slashes added by ical generator */
			else return;
	}
}
/* ---------------------------------------------------------------------- */	
// Replace RFC 2445 escape characters
function amr_format_ical_text($value) {
  $output = str_replace(
    array('\\\\', '\;', '\,', '\N', '\n'),
    array('\\',   ';',  ',',  "\n", "\n"),
    $value
  );
  return $output;
}
/* ---------------------------------------------------------------------- */	
function amr_is_untimed($text) {
/*  checks for VALUE=DATE */
if (stristr ($text, 'VALUE=DATE')) return (true);
else return (false);
}
/* ---------------------------------------------------------------------- */	
function amr_parse_component($type)	{	/* so we know we have a vcalendar at lines[$n] - check for properties or components */	
	global $amr_lines;
	global $amr_totallines;
	global $amr_n;
	global $amr_validrepeatablecomponents;
	global $amr_validrepeatableproperties;
	global $amr_globaltz;
	
	
	while (($amr_n < $amr_totallines)	)	{	
		//if (ICAL_EVENTS_DEBUG) {echo '<br/>*** '.$type.' '.$amr_lines[$amr_n];}
			$amr_n++;
			$parts = explode (':', $amr_lines[$amr_n],2 ); /* explode faster than the preg, just split first : */
			if ((!$parts) or ($parts === $amr_lines[$amr_n])) {
				if (ICAL_EVENTS_DEBUG) echo ( '<br /> Error in line, skipping '.$amr_n.': with value:'.$amr_lines[$amr_n]);
				}
			else {
				if ($parts[0] === 'BEGIN') { /* the we are starting a new sub component - end of the properties, so drop down */		
					if (in_array ($parts[1], $amr_validrepeatablecomponents)) {
						$subarray[$parts[1]][] = amr_parse_component($parts[1]);
					}
					else { $subarray[$parts[1]] = amr_parse_component($parts[1]);	
					}				
				}	
				else {
					if ($parts[0] === 'END') {	
						return ($subarray ); 
					}
					/* now grab the value - just in case there may have been ";" in the value we will take all the rest of the string */
					else {
						if ($parts[0] === 'X-WR-TIMEZONE;VALUE=TEXT') $parts[0] === 'X-WR-TIMEZONE';
						$basepart = explode (';', $parts[0], 2);  /* Looking for RRULE; something...*/					
						if (in_array ($basepart[0], $amr_validrepeatableproperties)) {
								$subarray[$basepart[0]][] = amr_parse_property ($parts);
						}
						else {	
//							if (ICAL_EVENTS_DEBUG) {echo '<br/>*** Parts ';var_dump($parts);			}
							$subarray [$basepart[0]] = amr_parse_property($parts);							
							if (($basepart[0] === 'DTSTART') and (isset($basepart[1]))) {
								if (amr_is_untimed($basepart[1])) { /* ie has VALUE=DATE */		
									$subarray ['Untimed'] = TRUE;
								}						
							}
							if (($basepart[0] === 'X-MOZ-GENERATION') and (!isset( $subarray ['SEQUENCE']))) $subarray ['SEQUENCE'] = $subarray ['X-MOZ-GENERATION'] ;
							/* If we have an mozilla funny thing, convert it to the sequence if there is no sequence */
						}
					}
				}	
			}
		}
		return ($subarray);	/* return the possibly nested component */	
	}
/* ---------------------------------------------------------------------- */
// Parse the ical file and return an array ('Properties' => array ( name & params, value), 'Items' => array(( name & params, value), )
function amr_parse_ical ( $cal_file ) {
/* we will try to continue as much as possible, ignore lines that are problems */
	global $amr_lines;
	global $amr_totallines;
	global $amr_n;
	global $amr_validrepeatablecomponents;
	global $amr_last_modified;
	
    $line = 0;
    $event = '';
	
	if (!$fd=@fopen($cal_file,"r")) {
	    echo '<br>'.sprintf(__('Error reading cached file: %s', 'amr_ical_list_lang'), $cal_file);
	    return ($cal_file);
	} 
	else {
	// Read in contents of entire file first
		$data = '';
		while (!feof($fd) ) {
		  $line++;
		  $data .= fgets($fd, 4096);
		}
		fclose($fd);
		// Now fix folding.  According to RFC, lines can fold by having
		// a CRLF and then a single white space character.
		// We will allow it to be CRLF, CR or LF or any repeated sequence
		// so long as there is a single white space character next.
		
		/**** we may also need to cope with backslahed backslashes, commas, semicolons as per http://www.kanzaki.com/docs/ical/text.html*/
	
	    $data = preg_replace ( "/[\r\n]+ /", "", $data );
	    $data = preg_replace ( "/[\r\n]+/", "\n", $data );
	    $data = str_replace ( "\;", ";", $data );
	    $data = str_replace ( "\,", ",", $data );
		$amr_n = 0;
	    $amr_lines = explode ( "\n", $data );
		$amr_totallines = count ($amr_lines) - 1; /* because we start from 0 */
		If (ICAL_EVENTS_DEBUG) {
			echo '<br>data lines: '.$amr_totallines ;
			echo '<br />first line: ';	var_dump($amr_lines[0]);
			
			echo '<br />';
			}	

	
		
		$parts = explode (':', $amr_lines[$amr_n],2 ); /* explode faster than the preg, just split first : */
	
		
		if ($parts[0] === 'BEGIN') {
			$ical = amr_parse_component('VCALENDAR');
			if (!empty ($amr_last_modified)) $ical['LastModificationTime'] = $amr_last_modified;
			return($ical);			
			}
		else {
			If (ICAL_EVENTS_DEBUG) {
				echo '<br>VCALENDAR not found in file:'.$cal_file;
				echo '<br>Line has: '.$amr_lines[$amr_n] ;
				}
			return false;
			}
	}
}
