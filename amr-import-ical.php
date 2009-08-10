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
		return get_cache_path() . get_cache_filename($url);
	}
/* ---------------------------------------------------------------------- */
	/*
	 * Attempt to create the cache directory if it doesn't exist.
	 * Return the path if successful.
	 */
	function get_cache_path() {
	global $amr_options;
		$cache_path = (ICAL_EVENTS_CACHE_LOCATION. '/ical-events-cache/');
		if (!file_exists($cache_path)) { /* if there is no folder */
			if (! wp_mkdir_p($cache_path, 0777)) {
					die("Error creating cache directory ($cache_path)");
			}
			else {
				die( "Your cache directory (<code>$cache_path</code>) needs to be writable for this plugin to work. Double-check it.");
			}
		}
		return $cache_path;
	}
/* ---------------------------------------------------------------------- */
	/*
	 * Return the cache filename for the specified URL.
	 */
	function get_cache_filename($url) {
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
	function cache_url($url, $cache=ICAL_EVENTS_CACHE_TTL) {
	global $amr_lastcache;
	global $amr_globaltz;
	
		
		$file = get_cache_file($url);	
		if ( file_exists($file) ) {
			$c = filemtime($file);
			$amr_lastcache = date_create(strftime('%c',$c));
			} 

		if (( $_REQUEST['nocache'] or $_REQUEST['refresh']  )
			or (! file_exists($file) or ((time() - 	($c)) >= ($cache*60*60))) )
		{
			$data = wp_remote_fopen($url);
			if ($data === false) {
				return ('No data');
				}
			else {
				$dest = fopen($file, 'w') or die("Error opening $file");
				if (!(fwrite($dest, $data))) die ("Error writing cache file");
				fclose($dest);
				$amr_lastcache = date_create (date('Y-m-d H:i:s'));
			}

		}
		if (!isset($amr_lastcache)) {
			$amr_lastcache = date_create (date('Y-m-d H:i:s'), $amr_globaltz);
			}

		return $file;
	}
/* ---------------------------------------------------------------------- */	
    /**
     * Parse a Time Period field.
     */
    function amr_parsePeriod($text,$tzobj)    {
        $periodParts = explode('/', $text);
        $start = amr_parseDateTime($periodParts[0], $tzobj);
        if ($duration = amr_parseDuration($periodParts[1])) {
			If (ICAL_EVENTS_DEBUG) {echo '<br>Duration = '; var_dump($duration);}
            return array('start' => $start, 'duration' => $duration);
        } 
		else {
			If (ICAL_EVENTS_DEBUG) {echo '<br>Duration not '; var_dump($duration);}
			if ($end = amr_parseDateTime($periodParts[1], $tzobj)) {
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

		/*  	19970714T133000            ;Local time
			19970714T173000Z           ;UTC time */

		if ((substr($d, strlen($d)-1, 1) === 'Z')) {  /*datetime is specifed in UTC */
			//echo '<br>we got a Z'.$d;
			$tzobj = timezone_open('UTC');
			$d = substr($d, 0, strlen($d)-1);
				
		}		
		//else echo '<br>we no have a Z '.$d;
		//if (is_object($tzobj)) {echo ' '.timezone_name_get($tzobj);}
	
		$date = substr($d,0, 4).'-'.substr($d,4, 2).'-'.substr($d,6, 2);
		if (strlen ($d) > 8) {	
			$time = substr($d,9 ,2 ).':'.substr($d,11 ,2 )  ; /* has to at least have hours and mins */
		}		
		else $time = '00:00';
		if (strlen ($d) > 13) {
			$time .= ':'.substr($d,13 ,2 );
		}
		else $time .= ':00';
		/* Now create our date with the timezone in wich it was defined */
		$dt = new DateTime($date.' '.$time,	$tzobj);
		$dt->setTimezone($amr_globaltz);  /* V2.3.1   shift date time to our desired timezone */
		
	return ($dt);
    }

	/* ---------------------------------------------------------------------- */
    /* Parses a Date field. */

    function amr_parseDate($text, $tzobj)    {  /* 
		 VALUE=DATE:
		 19970101,19970120,19970217,19970421
		   19970526,19970704,19970901,19971014,19971128,19971129,19971225
	*/			
		$p = explode (',',$text); 	/* if only a single will still return one array value */
		foreach ($p as $i => $v) {
			$dates[] =  new DateTime(substr($v,0, 4).'-'.substr($v,4, 2).'-'.substr($v,6, 2), $tzobj);		
		}		
		return ($dates);
    }
	/* ------------------------------------------------------------------ */
	function amr_parseTZDate ($value, $tzid) {	
		$tzobj = timezone_open($tzid);			
		return (amr_parseDateTime ($value, $tzobj));
	}
	/* ------------------------------------------------------------------ */	
   function amr_parseTZID($text)    {	/* accepst long and short TZ's, returns false if not valid */
		return ( timezone_open($text));
    }		
/* ------------------------------------------------------------------ */

   function amr_parseSingleDate($VALUE='DATETIME', $text, $tzobj)	{
   /* used for those properties that should only have one value - since many other dates can have multiple date specs, the parsing function returns an array 
	Reduce the array to a single value */

		$arr = amr_parseVALUE($VALUE, $text, $tzobj);
		
		if (is_array($arr)) {
			if (count($arr) > 1) {
				echo '<br>Unexpected multiple date values'.var_dump($arr);
			}
			else {
				return ($arr[0]);
			}
		}
		return ($arr);
	}
	
	/* ---------------------------------------------------------------------- */	

   function amr_parseVALUE($VALUE, $text, $tzobj)	{
	/* amr parsing a value like 
	VALUE=PERIOD:19960403T020000Z/19960403T040000Z,	19960404T010000Z/PT3H
	VALUE=DATE:19970101,19970120,19970217,19970421,..	19970526,19970704,19970901,19971014,19971128,19971129,19971225	*/

		switch ($VALUE) {
			case 'DATETIME': { return (amr_parseDateTime($text, $tzobj)); }
			case 'DATE': {return (amr_parseDate($text, $tzobj)); }
			case 'PERIOD': {return (amr_parsePeriod($text, $tzobj)); }
			default: return (false);
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

function amr_parse_property ($parts) {
/* would receive something like array ('DTSTART; VALUE=DATE', '20060315')) */
/*  NOTE: parts[0]    has the long tag eg: RDATE;TZID=US-EASTERN
		parts[1]  the bit after the :  19960403T020000Z/19960403T040000Z, 19960404T010000Z/PT3H
		IF 'Z' then must be in UTC
		If no Z
*/
global $amr_globaltz;

	$p0 = explode (';', $parts[0], 2);  /* Looking for ; VALUE = something...;   or TZID=...*/
	
	if (isset($p0[1])) { /* ie if we have some modifiers like TZID, or maybe just VALUE=DATE */
	
		parse_str($p0[1]);/*  (will give us if exists $value = 'xxx', or $tzid= etc) */
		if (isset($TZID)) {
			$tzobj = timezone_open($TZID);
		}  /* should create datetime object with it's own TZ, datetime maths works correctly with TZ's */
		else {/* might be just a value=date, in which case we use the global tz? */
			$tzobj = $amr_globaltz;
		;}
	}
	else $tzobj = timezone_open('UTC');

	switch ($p0[0]) {
		case 'CREATED':
		case 'COMPLETED': 
		case 'LAST-MODIFIED':
		case 'DTSTART':   
		case 'DTEND':
		case 'DTSTAMP':		
		case 'DUE':	
			if (isset($VALUE)) { 
				return (amr_parseSingleDate($VALUE, $parts[1], $tzobj));	}
			else {
				return (amr_parseSingleDate('DATETIME', $parts[1], $tzobj)); 
			}
		case 'ALARM':
		case 'RECURRENCE-ID':  /* could also have range ?*/
			if (isset($VALUE)) { 
				return (amr_parseValue($VALUE, $parts[1], $tzobj));	}
			else {
				return (amr_parseDateTime($parts[1], $tzobj)); 
				}
		case 'EXRULE':	
		case 'RRULE': return (amr_parseRRULE($parts[1]));	
		case 'BDAY':	
			return (amr_parseDate ($parts[1])); 
		
		case 'EXDATE':
		case 'RDATE':  /* could be multiple dates after value */
				if (isset($VALUE)) 	return (amr_parseValue ($VALUE, $parts[1], $tzobj));
				/* This could be simplified */
				else if (isset ($TZID)) return (amr_parseTZDate ($parts[1], $TZID));
				else {	/* must be just a date */
					return (amr_parseDateTime ( $parts[1], $tzobj)); 
				}
		
		case 'TRIGGER': /* not supported yet, check for datetime and / or duration */
		case 'DURATION':
			return (amr_parseDuration ($parts[1])); 
		case 'FREEBUSY':
			return ( amr_parsePeriod ($parts[1])); 	
		case 'TZID': /* ie TZID is a property, not part of a date spec */
			return ($parts[1]);
		default:	
			return (str_replace ('\,', ',', $parts[1]));  /* replace any slashes added by ical generator */
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
function is_untimed($text) {
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

	while (($amr_n < $amr_totallines)	)	
		{
			$amr_n++;
			$parts = explode (':', $amr_lines[$amr_n],2 ); /* explode faster than the preg, just split first : */
			if ((!$parts) or ($parts === $amr_lines[$amr_n])) 
				echo '<!-- Error in line skipping '.$amr_n.': with value:'.$amr_lines[$amr_n].' -->';
			else {			
				if ($parts[0] === 'BEGIN') { /* the we are starting a new sub component - end of the properties, so drop down */					
					if (in_array ($parts[1], $amr_validrepeatablecomponents)) {
						$subarray[$parts[1]][] = amr_parse_component($parts[1]);
					}
					else {	
						$subarray[$parts[1]] = amr_parse_component($parts[1]);	
					}
				}	
				else {
					if ($parts[0] === 'END') {	
						return ($subarray ); 
					}
					/* now grab the value - just in case there may have been ";" in the value we will take all the rest of the string */
					else { 
						$basepart = explode (';', $parts[0], 2);  /* Looking for RRULE; something...*/
						
						if (in_array ($basepart[0], $amr_validrepeatableproperties)) {
								$subarray[$basepart[0]][] = amr_parse_property ($parts);
						}
						else {	
							$subarray [$basepart[0]] = amr_parse_property($parts);	
							if (($basepart[0] === 'DTSTART') and (is_untimed($basepart[1]))) {
								$subarray ['Untimed'] = TRUE;
							}
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
	
    $line = 0;
    $event = '';
//	$ical_data = array();

	if (!$fd=@fopen($cal_file,"r")) {
	    echo "<!-- Can't read temporary file: $cal_file\n -->";
	    return ($cal_file);
	} else {

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
		If (ICAL_EVENTS_DEBUG) echo '<br>'.$line.' lines in Source file';
	    $data = preg_replace ( "/[\r\n]+ /", "", $data );
	    $data = preg_replace ( "/[\r\n]+/", "\n", $data );
		
		$amr_n = 0;
	    $amr_lines = explode ( "\n", $data );
		$amr_totallines = count ($amr_lines) - 1; /* because we start from 0 */
		If (ICAL_EVENTS_DEBUG) echo '<br>'.$amr_totallines.' lines after unfolding lines </br>';
		
		$parts = explode (':', $amr_lines[$amr_n],2 ); /* explode faster than the preg, just split first : */
		if ($parts[0] === 'BEGIN') {
			$ical = amr_parse_component('VCALENDAR');
			return($ical);			
			}
		else 
			{
			echo '<!--- Check the feed - VCALENDAR not found in file --> ';
			return false;
			}
	}
}
