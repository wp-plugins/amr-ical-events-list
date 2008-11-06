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
		$cache_path = (ABSPATH . 'wp-content/ical-events-cache/');
		if (! file_exists($cache_path)) {
			if (is_writable($cache_path)) {
				if (! mkdir($cache_path, 0777)) {
					die("Error creating cache directory ($cache_path)");
				}
			}
			else {
				die("Your cache directory (<code>$cache_path</code>) needs to be writable for this plugin to work. Double-check it.");
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
		$file = get_cache_file($url);
		if (ICAL_EVENTS_DEBUG) {echo '<br>Cache period in hours = '.$cache;}
		if (! file_exists($file) or ((time() - filemtime($file)) >= ($cache*60*60))) 
		{
			if (ICAL_EVENTS_DEBUG) {echo '<br>Refreshing File '.$url;}
			$data = wp_remote_fopen($url);
			if ($data === false) die("No data - Could not fetch [$url]");
			$dest = fopen($file, 'w') or die("Error opening $file");
			if (!(fwrite($dest, $data))) die ("Error writing cache file");
			fclose($dest);
		}
		return $file;
	}
/* ---------------------------------------------------------------------- */	
    /**
     * Parse a Time Period field.
     */
    function amr_parsePeriod($text)
    {
        $periodParts = explode('/', $text);
        $start = amr_parseDateTime($periodParts[0]);
        if ($duration = amr_parseDuration($periodParts[1])) {
            return array('start' => $start, 'duration' => $duration);
        } elseif ($end = amr_parseDateTime($periodParts[1])) {
            return array('start' => $start, 'end' => $end);
        }
    }
	/* ---------------------------------------------------------------------- */	
	   /**
     * Parses a DateTime field and returns a datetime object, with either it's own tz if it has one, or the passed one
     */
    function amr_parseDateTime($text, $tzobj=null)
    { 
		/*  	DTSTART:19970714T133000            ;Local time
			DTSTART:19970714T173000Z           ;UTC time
			DTSTART;TZID=US-Eastern:19980101T090000

		amr *** could be multiple, could be a list - need to handle that too 
		Must contain a complete datetime string 20080809T120000, with optional Z 
		DateTime requires eg:  20080809 14:50:00 GMT*/
		global $amr_globaltz;
	
		$p = explode (':',$text);  /* isolate if it has a TZID */

		if (isset ($p[1])) { /* then must have two part with TZid: Datetime */
			$d = $p[1];
			parse_str ($p[0], $args); /* eg: TZID=US-Eastern */
			if (isset ($args['TZID'])) {	
				$tz = timezone_open($args['TZID']);	
			}
		}	
		else {
			$d = $text;
			if ((substr($d, strlen($d)-1, 1) === 'Z')) {  
				$tz = timezone_open('UTC') ;
				$d = substr($d, 0, strlen($d)-1);
				}
			else { 
				$tz = $amr_globaltz;  /* local time */
			}			
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
		$dt = new DateTime($date.' '.$time,	$tz);
	
	return ($dt);
    }

	/* ---------------------------------------------------------------------- */
    /* Parses a Date field. */

    function amr_parseDate($text, $tzobj)
    {  /* 
		 VALUE=DATE:
		 19970101,19970120,19970217,19970421
		   19970526,19970704,19970901,19971014,19971128,19971129,19971225
	*/
		global $amr_globaltz;
		
// 		if (ICAL_EVENTS_DEBUG) {echo '</br>Parsing '.$text.' expecting date.';}
		
		if (!isset($tzobj)) {$tzobj = $amr_globaltz; } /* set the local timezone */
			
		$p = explode (',',$text); 	/* if only a single will still return one array value */
		foreach ($p as $i => $v) {
			$dates[] =  new DateTime(substr($v,0, 4).'-'.substr($v,4, 2).'-'.substr($v,6, 2), $tzobj);		
		}
			
		return ($dates);
    }
	/* ------------------------------------------------------------------ */
   function amr_parseTZID($text)
    {	/* accepst long and short TZ's, returns false if not valid */
		return ( timezone_open($text));
    }		
/* ------------------------------------------------------------------ */
	function amr_getset_globalTZ($tz)	{
	/* for now we are ignoring any complicated tz definition in theics files and using the new php timezone database definition
	  THis should be okay in 99.999 % of the cases.  In searching even the ical providers are wrong sometimes, so this may be even better. */
	global $amr_globaltz; /* a timezone object */

	if (!(isset($amr_globaltz))) { /* set from config file and/or from looking up timezone plugin option, or from first calendar */
		if (isset($tz)) {
			$amr_globaltz = amr_parseTZID ($tz);
			date_default_timezone_set ( timezone_name_get($amr_globaltz));  /* needs trsing not object */
			if (ICAL_EVENTS_DEBUG) 
				echo '<h3>'.$tz.' Global TZ set to '.timezone_name_get($amr_globaltz);
		}
	}
	}
	/* ---------------------------------------------------------------------- */	

   function amr_parseSingleDate($VALUE='DATETIME', $text, $tz=null)	{
   /* used for those properties that should only have one value - since many other dates can have multiple date specs, the parsing function returns an array 
	Reduce the array to a single value */

		$arr = amr_parseVALUE($VALUE, $text, $tz);
		
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

   function amr_parseVALUE($VALUE, $text, $tz)	{
	/* amr parsing a value like 
	VALUE=PERIOD:19960403T020000Z/19960403T040000Z,	19960404T010000Z/PT3H
	VALUE=DATE:19970101,19970120,19970217,19970421,..	19970526,19970704,19970901,19971014,19971128,19971129,19971225	*/
	/* *** amr needs attention */
		switch ($VALUE) {
			case 'DATETIME': { return (amr_parseDateTime($text, $tz)); }
			case 'DATE': {return (amr_parseDate($text, $tz)); }
			case 'PERIOD': {return (amr_parsePeriod($text, $tz)); }
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
		    if (!empty($durvalue[3])) $dur['weeks'] = $durvalue[3];  
			
            if (count($durvalue) > 4) {                // Days.
				if (!empty($durvalue[4])) $dur['days'] = $durvalue[4];  
            }
            if (count($durvalue) > 5) {                // Hours.
				if (!empty($durvalue[7])) $dur['hours'] = $durvalue[7]; 
          
                if (isset($durvalue[8])) {    // Mins.
					$dur['mins'] = $durvalue[8];  
                }              
                if (isset($durvalue[9])) { // Secs.
					$dur['secs'] = $durvalue[9];  
                }
            }    
			If (ICAL_EVENTS_DEBUG) {echo '<br>Duration = ';print_r($dur);}
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
*/
global $amr_globaltz;

	$tz = $amr_globaltz;
	$p0 = explode (';', $parts[0], 2);  /* Looking for ; VALUE = something...;   or TZID=...*/
	
	if (isset($p0[1])) { /* ie if we have some modifiers like TZID */
		parse_str($p0[1]);/*  (will give us if exists $value = 'xxx', or $tzid= etc) */

		if (!(isset($TZID)) or !($tz = timezone_open($TZID))) {
			$tz = $amr_globaltz;
		};  /* should create datetime object with it's own TZ, datetime maths works correctly with TZ's */
		
	}
		
//	If (ICAL_EVENTS_DEBUG) 	echo '<br>tag 0 ='.$tag[0].' tag 1 = '.(isset($tag[1])?$tag[1]:'')	.' $part 0 = '.$parts[0]. ' $part 1='.(isset($parts[1])?$parts[1]:'') ;

	switch ($p0[0]) {
		case 'CREATED':
		case 'COMPLETED': 
		case 'LAST-MODIFIED':
		case 'DTSTART':   
		case 'DTEND':
		case 'DTSTAMP':		
		case 'DUE':	
			if (isset($VALUE)) { 
				return (amr_parseSingleDate($VALUE, $parts[1], $tz));	}
			else {
				return (amr_parseSingleDate('DATETIME', $parts[1], $tz)); 
			}
		case 'ALARM':
		case 'RECURRENCE-ID':  /* could also have range ?*/
			if (isset($VALUE)) { 
				return (amr_parseValue($VALUE, $parts[1], $tz));	}
			else {
				return (amr_parseDateTime($parts[1], $tz)); 
				}
			
		case 'RRULE': return (amr_parseRRULE($parts[1]));	
		case 'BDAY':	
			return (amr_parseDate ($parts[1])); 
		
		case 'EXDATE':
		case 'RDATE':  /* could be multiple dates after value */
				if (isset($VALUE)) 	return (amr_parseValue ($VALUE, $parts[1], $tz));
				else if (isset ($TZID)) return (amr_parseTZDate ($parts[1], TZID));
				else {	/* must be just a date */
					return (amr_parseDateTime ( $parts[1], $tz)); 
				}
		
		case 'TRIGGER': /* not supported yet, check for datetime and / or duration */
		case 'DURATION':
			return (amr_parseDuration ($parts[1])); 
		case 'FREEBUSY':
			return ( amr_parsePeriod ($parts[1])); 
		case 'TZID': /* ie TZID is a property, not part of a date spec */
			if (ICAL_EVENTS_DEBUG) { echo '<h3>Got a tzid as property not attribute :'.$parts[1].'</h3>'; }
			if (!isset ($amr_globaltz)) {	/* This will have been set if we are using the timezone plugin */	
					amr_getset_globalTZ($parts[1]);
					}
			return ($parts[1]);
		default:
		
			return (str_replace ('\,', ',', $parts[1]));  /* replace any slashes added by ical generator */
	}


}


/* ---------------------------------------------------------------------- */	

// Replace RFC 2445 escape characters
function format_ical_text($value) {
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

function parse_component($type)
	{	/* so we know we have a vcalendar at lines[$n] - check for properties or components */	
	global $amr_lines;
	global $amr_totallines;
	global $amr_n;
	global $amr_validrepeatablecomponents;
	global $amr_validrepeatableproperties;
	global $amr_globaltz;

	if (ICAL_EVENTS_DEBUG) { echo '<br>Parsing component: '.$type;}

	while (($amr_n < $amr_totallines)	)	
		{
			$amr_n++;
			$parts = explode (':', $amr_lines[$amr_n],2 ); /* explode faster than the preg, just split first : */
			if ((!$parts) or ($parts === $amr_lines[$amr_n])) 
				echo '<!-- Error in line skipping '.$amr_n.': with value:'.$amr_lines[$amr_n].' -->';
			else {			
//				If (ICAL_EVENTS_DEBUG) echo '<br>Parsing line'.$amr_n.' with '.$parts[0].' and '.wp_specialchars($parts[1]);
				if ($parts[0] === 'BEGIN') { /* the we are starting a new sub component - end of the properties, so drop down */					
					if (in_array ($parts[1], $amr_validrepeatablecomponents)) {
						$subarray[$parts[1]][] = parse_component($parts[1]);
					}
					else {	
						$subarray[$parts[1]] = parse_component($parts[1]);	
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

			/* Need to isolate TZ early as needed for later date setup.. The first calendar is the one that will be used  */
			
			if (!isset ($amr_globaltz)) {	/* This will have been set if we are using the timezone plugin */	
				if ($parts[0] === 'X-WR-TIMEZONE') { 
					amr_getset_globalTZ($parts[1]);
				}			

			}	

			}
		}
		return ($subarray);	/* return the possibly nested component */	
	}


/* ---------------------------------------------------------------------- */
// Parse the ical file and return an array ('Properties' => array ( name & params, value), 'Items' => array(( name & params, value), )
function parse_ical ( $cal_file ) 
	{
/* we will try to continue as much as possible, ignore lines that are problems */

	global $amr_lines;
	global $amr_totallines;
	global $amr_n;
	global $amr_validrepeatablecomponents;
	
    $line = 0;
    $event = '';
	$ical_data = array();

	if (!$fd=@fopen($cal_file,"r")) {
	    echo "Can't read temporary file: $cal_file\n";
	    exit();
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
	   //echo "<h3>Orig:</h3><pre>$data</pre><br/><br/>\n";
	    $data = preg_replace ( "/[\r\n]+ /", "", $data );
	    $data = preg_replace ( "/[\r\n]+/", "\n", $data );
	    //echo "<h3>Data:</h3><pre>$data</pre><P>";
		
		$amr_n = 0;
	    $amr_lines = explode ( "\n", $data );
		$amr_totallines = count ($amr_lines) - 1; /* because we start from 0 */
		If (ICAL_EVENTS_DEBUG) echo '<br>'.$amr_totallines.' lines after unfolding lines';
		
		$parts = explode (':', $amr_lines[$amr_n],2 ); /* explode faster than the preg, just split first : */
		if ($parts[0] === 'BEGIN') {
			$ical = parse_component('VCALENDAR');
			return($ical);			
			}
		else 
			{
			echo 'VCALENDAR not found in file';
			return false;
			}
	}
}
