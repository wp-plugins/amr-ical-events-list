<?php
/*
 * $Id: import_ical.php,v 1.14 2004/11/22 16:39:48 cknudsen Exp $
 *
 * File Description:
 *	This file incudes functions for parsing iCal data files during
 *	an import.
 *
 *	It will be included by import_handler.php.
 *
 * The iCal specification is available online at:
 *	http://www.ietf.org/rfc/rfc2445.txt
 *
 */

// Parse the ical file and return the data hash.
function parse_ical ( $cal_file ) {
  global $tz, $errormsg;
  global $amr_calprop;

  $ical_data = array();

  if (!$fd=@fopen($cal_file,"r")) {
    $errormsg .= "Can't read temporary file: $cal_file\n";
    exit();
  } else {

    // Read in contents of entire file first
    $data = '';
    while (!feof($fd) && !$error) {
      $line++;
      $data .= fgets($fd, 4096);
    }
    fclose($fd);
    // Now fix folding.  According to RFC, lines can fold by having
    // a CRLF and then a single white space character.
    // We will allow it to be CRLF, CR or LF or any repeated sequence
    // so long as there is a single white space character next.
    //echo "Orig:<br><pre>$data</pre><br/><br/>\n";

    $data = preg_replace ( "/[\r\n]+ /", "", $data );
    $data = preg_replace ( "/[\r\n]+/", "\n", $data );
    //echo "Data:<br><pre>$data</pre><P>";

    // reflect the section where we are in the file:
    // VEVENT, VTODO, VJORNAL, VFREEBUSY, VTIMEZONE
    $state = "NONE";
    $substate = "none"; // reflect the sub section
    $subsubstate = ""; // reflect the sub-sub section
    $error = false;
    $line = 0;
    $event = '';

    $lines = explode ( "\n", $data );

    for ( $n = 0; $n < count ( $lines ) && ! $error; $n++ ) 
	{
      $line++;
      $buff = $lines[$n];

      // parser debugging code...
      //echo "line = $line <br />";
      //echo "state = $state <br />";
      //echo "substate = $substate <br />";
      //echo "subsubstate = $subsubstate <br />";
      //echo "buff = " . htmlspecialchars ( $buff ) . "<br /><br />\n";
	  

      if ($state == "VEVENT") {
          if ( ! empty ( $subsubstate ) ) {
            if (preg_match("/^END:(.+)$/i", $buff, $match)) {
              if ( $match[1] == $subsubstate ) {
                $subsubstate = '';
              }
            } else if ( $subsubstate == "VALARM" && 
              preg_match ( "/TRIGGER:(.+)$/i", $buff, $match ) ) {
              // Example: TRIGGER;VALUE=DATE-TIME:19970317T133000Z
              //echo "Set reminder to $match[1]<br />";
              // reminder time is $match[1]
            }
          }
          else if (preg_match("/^BEGIN:(.+)$/i", $buff, $match)) {
            $subsubstate = $match[1];
          }
           // we suppose ":" is on the same line as property name, this can perhaps cause problems
	  else if (preg_match("/^SUMMARY[^:]*:(.+)$/i", $buff, $match)) {
              $substate = "SUMMARY";
              $event[$substate] = $match[1];
          } elseif (preg_match("/^DESCRIPTION[^:]*:(.+)$/i", $buff, $match)) {
              $substate = "DESCRIPTION";
              $event[$substate] = $match[1];
          } elseif (preg_match("/^LOCATION[^:]*:(.+)$/i", $buff, $match)) {
              $substate = "LOCATION";
              $event[$substate] = $match[1];
          } elseif (preg_match("/^URL(?:;VALUE=[^:]+)?:(.+)$/i", $buff, $match)) {
              $substate = "URL";
              $event[$substate] = $match[1];
          } elseif (preg_match("/^CLASS[^:]*:(.*)$/i", $buff, $match)) {
              $substate = "CLASS";
              $event[$substate] = $match[1];
          } elseif (preg_match("/^PRIORITY[^:]*:(.*)$/i", $buff, $match)) {
              $substate = "PRIORITY";
              $event[$substate] = $match[1];
	  } elseif (preg_match("/^DTSTART[^:]*:\s*(\d+T\d+Z?)\s*$/i", $buff, $match)) {
              $substate = "DTSTART";
              $event[$substate] = $match[1];
	  } elseif (preg_match("/^DTSTART[^:]*:\s*(\d+)\s*$/i", $buff, $match)) {
              $substate = "DTSTART";
              $event[$substate] = $match[1];
          } elseif (preg_match("/^DTEND[^:]*:\s*(.*)\s*$/i", $buff, $match)) {
              $substate = "DTEND";
              $event[$substate] = $match[1];
          } elseif (preg_match("/^DURATION[^:]*:(.+)\s*$/i", $buff, $match)) {
              $substate = "DURATION";
              $durH = $durM = $durS = 0;
              if ( preg_match ( "/PT(?:([0-9]+)H)?(?:([0-9]+)M)?(?:([0-9]+)S)?/", $match[1], $submatch ) ) {
                  $durH = $submatch[1];
                  $durM = $submatch[2];
                  $durS = $submatch[3];
	      }
              $event[$substate] = $durH * 60 + $durM + $durS / 60;
          } elseif (preg_match("/^RRULE[^:]*:(.+)$/i", $buff, $match)) {
              $substate = "RRULE";
              $event[$substate] = $match[1];
          } elseif (preg_match("/^EXDATE[^:]*:(.+)$/i", $buff, $match)) {
              $substate = "EXDATE";
              $event[$substate] = $match[1];
          } elseif (preg_match("/^CATEGORIES[^:]*:(.+)$/i", $buff, $match)) {
              $substate = "CATEGORIES";
              $event[$substate] = $match[1];
          } elseif (preg_match("/^STATUS[^:]*:(.+)$/i", $buff, $match)) {
              $substate = "STATUS";
              $event[$substate] = $match[1];
          } elseif (preg_match("/^RECURRENCE-ID[^:]*:\s*(.*)\s*$/i", $buff, $match)) {
              $substate = "RECURRENCE-ID";
              $event[$substate] = $match[1];
          } elseif (preg_match("/^UID[^:]*:(.+)$/i", $buff, $match)) {
              $substate = "UID";
              $event[$substate] = $match[1];
          } elseif (preg_match("/^END:VEVENT$/i", $buff, $match)) {
		  /* amr - what about todo's */
	            $state = "VCALENDAR";
	            $substate = "none";
	            $subsubstate = '';
				$ical_data[] = format_ical($event);  /*  add to ical array */
              // clear out data for new event
              $event = '';

          } elseif (preg_match("/^\s(\S.*)$/", $buff, $match)) {
              if ($substate != "none") {
                  $event[$substate] .= $match[1];
              } else {
                  $errormsg .= "iCal parse error on line $line:<br />$buff\n";
                  $error = true;
              }
          // For unsupported properties
	  } 
		else {
            $substate = "none";
          }
      } elseif ($state == "VCALENDAR") {
	  
          if (preg_match("/^BEGIN:VEVENT/i", $buff)) {
            $state = "VEVENT";
          } elseif (preg_match("/^END:VCALENDAR/i", $buff)) {
            $state = "NONE";
          } else if (preg_match("/^BEGIN:VTIMEZONE/i", $buff)) {
            $state = "VTIMEZONE";
          } else if (preg_match("/^BEGIN:VALARM/i", $buff)) {
            $state = "VALARM";
			}
		 	/*amr  Must start with calendar Check for and save calprop  stuff , note may be many, allow to list these*/ 
			else  
			  foreach ($amr_calprop as $p => $v)
			  {	if (preg_match("/^".$p.":(.+)$/i", $buff, $match))
				{	$calprops[$p] = format_ical_text($match[1]);
					/*amr - could add a check later to see if any special processing is required.  Check if function for that field exists & do it*/
				}
			  }				
			/* end amr */
      } elseif ($state == "VTIMEZONE") {
        // We don't do much with timezone info yet...
		/* amr - we could look at using php DATETIME to reset by the definition in the ics file */
		/* amr  Note that Standard is a subcomponent of TimeZone , as is  Daylight */
		/* amr PHP now has own definitions of these, so for now could ignore the ical file ones? */
        if (preg_match("/^END:VTIMEZONE$/i", $buff)) {
          $state = "VCALENDAR";
        }
      } elseif ($state == "NONE") {
         if (preg_match("/^BEGIN:VCALENDAR$/i", $buff))
			{
				$state = "VCALENDAR";		 
			}
      }
    } // End while
  }
  $ical['Properties'] = $calprops;
  $ical['Items'] = $ical_data;
  return $ical;
}
/* ------------------------------------------------------------------------------*/
/* amr  rather than going to the old timestamp, use the new datetimeclass*/
function icaldate_to_datetimeobject ($vdate, $plus_d = '0', $plus_m = '0',
  $plus_y = '0') 
  /* vdate could look like
	19980118T073000Z
	TZID=Australia/Sydney:20080906T170000
	19701004T020000
  */
  {
/* amr just copeid needs fixing if we are going to use it */

  $y = substr($vdate, 0, 4) + $plus_y;
  $m = substr($vdate, 4, 2) + $plus_m;
  $d = substr($vdate, 6, 2) + $plus_d;
  $H = substr($vdate, 9, 2);
  $M = substr($vdate, 11, 2);
  $S = substr($vdate, 13, 2);
  $Z = substr($vdate, 15, 1);

  if ($Z == 'Z') /* then we have a UTC date */
  {
    $TS = gmmktime($H,$M,$S,$m,$d,$y);
  } else {
    // Problem here if server in different timezone
    $TS = mktime($H,$M,$S,$m,$d,$y);
  }

  return $TS;
}

// Convert ical format (yyyymmddThhmmssZ) to epoch time
function icaldate_to_timestamp ($vdate, $plus_d = '0', $plus_m = '0',
  $plus_y = '0') {

  $y = substr($vdate, 0, 4) + $plus_y;
  $m = substr($vdate, 4, 2) + $plus_m;
  $d = substr($vdate, 6, 2) + $plus_d;
  $H = substr($vdate, 9, 2);
  $M = substr($vdate, 11, 2);
  $S = substr($vdate, 13, 2);
  $Z = substr($vdate, 15, 1);

  if ($Z == 'Z') {
    $TS = gmmktime($H,$M,$S,$m,$d,$y);
  } else {
    // Problem here if server in different timezone
    $TS = mktime($H,$M,$S,$m,$d,$y);
  }

  return $TS;
}


// Put all ical data into import hash structure
function format_ical($event) {

  // Start and end time
  $fevent['StartTime'] = icaldate_to_timestamp($event['DTSTART']);

  
  if ( isset ( $event['DTEND'] ) ) 
  {
    $fevent['EndTime'] = icaldate_to_timestamp($event['DTEND']);
  } 
  else 
  {
    if ( isset ( $event['DURATION'] ) ) {
      $fevent['EndTime'] = $fevent['StartTime'] + $event['DURATION'] * 60;
    } else {
      $fevent['EndTime'] = $fevent['StartTime'];
    }
  }

  
  // Calculate duration in minutes
  if ( isset ( $event['DURATION'] ) ) {
    $fevent['DURATION'] = $event['DURATION'];
  } else if ( empty ( $fevent['DURATION'] ) ) {
    $fevent['DURATION'] = ($fevent['EndTime'] - $fevent['StartTime']) / 60;
  }

  if ( $fevent['DURATION'] == '1440' ) {
    // All day event... nothing to do here :-)
  } else if ( preg_match ( "/\d{8}$/",
    $event['DTSTART'], $pmatch ) ) {
    // Untimed event
    $fevent['DURATION'] = 0;
    $fevent['Untimed'] = 1;
  }

  $fevent['SUMMARY'] = format_ical_text($event['SUMMARY']);
  $fevent['DESCRIPTION'] = format_ical_text($event['DESCRIPTION']);
  $fevent['LOCATION'] = format_ical_text($event['LOCATION']);
  $fevent['URL'] = ($event['URL']);
  $fevent['PRIVATE'] = preg_match("/private|confidential/i", $event['class']) ? '1' : '0';
  $fevent['UID'] = $event['uid'];

  $fevent['STATUS'] = format_ical_text($event['STATUS']);
  if ( isset( $event['RECURRENCE-ID'] ) ) {
    $fevent['RECURRENCEID'] = icaldate_to_timestamp($event['RECURRENCE-ID']);
  }

  // Repeats
  //
  // Handle RRULE
  if ($event['RRULE']) {
    // first remove and EndTime that may have been calculated above
    unset ( $fevent['Repeat']['EndTime'] );
    //split into pieces
    //echo "RRULE line: $event[rrule] <br />\n";
    $RR = explode ( ";", $event['RRULE'] );

    // create an associative array of key-value paris in $RR2[]
    for ( $i = 0; $i < count ( $RR ); $i++ ) {
      $ar = explode ( "=", $RR[$i] );
      $RR2[$ar[0]] = $ar[1];
    }

    for ( $i = 0; $i < count ( $RR ); $i++ ) {
      //echo "RR $i = $RR[$i] <br />";
      if ( preg_match ( "/^FREQ=(.+)$/i", $RR[$i], $match ) ) {
        if ( preg_match ( "/YEARLY/i", $match[1], $submatch ) ) {
          $fevent['Repeat']['Interval'] = 5;
        } else if ( preg_match ( "/MONTHLY/i", $match[1], $submatch ) ) {
          $fevent['Repeat']['Interval'] = 2;
        } else if ( preg_match ( "/WEEKLY/i", $match[1], $submatch ) ) {
          $fevent['Repeat']['Interval'] = 2;
        } else if ( preg_match ( "/DAILY/i", $match[1], $submatch ) ) {
          $fevent['Repeat']['Interval'] = 1;
        } else {
          // not supported :-(
          if (ICAL_EVENTS_DEBUG) echo "Unsupported iCal FREQ value \"$match[1]\"<br />\n";
        }
      } else if ( preg_match ( "/^INTERVAL=(.+)$/i", $RR[$i], $match ) ) {
        $fevent['Repeat']['Frequency'] = $match[1];
      } else if ( preg_match ( "/^UNTIL=(.+)$/i", $RR[$i], $match ) ) {
        // specifies an end date
        $fevent['Repeat']['EndTime'] = icaldate_to_timestamp ( $match[1] );
      } else if ( preg_match ( "/^COUNT=(.+)$/i", $RR[$i], $match ) ) {
        // NOT YET SUPPORTED -- TODO
        if (ICAL_EVENTS_DEBUG) echo "Unsupported iCal COUNT value \"$RR[$i]\"<br />\n";
      } else if ( preg_match ( "/^BYSECOND=(.+)$/i", $RR[$i], $match ) ) {
        // NOT YET SUPPORTED -- TODO
        if (ICAL_EVENTS_DEBUG) echo "Unsupported iCal BYSECOND value \"$RR[$i]\"<br />\n";
      } else if ( preg_match ( "/^BYMINUTE=(.+)$/i", $RR[$i], $match ) ) {
        // NOT YET SUPPORTED -- TODO
        if (ICAL_EVENTS_DEBUG) echo "Unsupported iCal BYMINUTE value \"$RR[$i]\"<br />\n";
      } else if ( preg_match ( "/^BYHOUR=(.+)$/i", $RR[$i], $match ) ) {
        // NOT YET SUPPORTED -- TODO
        if (ICAL_EVENTS_DEBUG) echo "Unsupported iCal BYHOUR value \"$RR[$i]\"<br />\n";
      } else if ( preg_match ( "/^BYMONTH=(.+)$/i", $RR[$i], $match ) ) {
        // this event repeats during the specified months
        $months = explode ( ",", $match[1] );
        if ( count ( $months ) == 1 ) {
          // Change this to a monthly event so we can support repeat by
          // day of month (if needed)
          // Frequency = 3 (by day), 4 (by date), 6 (by day reverse)
          if ( ! empty ( $RR2['BYDAY'] ) ) {
            if ( preg_match ( "/^-/", $RR2['BYDAY'], $junk ) )
              $fevent['Repeat']['Interval'] = 6; // monthly by day reverse
            else
              $fevent['Repeat']['Interval'] = 3; // monthly by day
            $fevent['Repeat']['Frequency'] = 12; // once every 12 months
          } else {
            // could convert this to monthly by date, but we will just
            // leave it as yearly.
            //$fevent['Repeat']['Interval'] = 4; // monthly by date
          }
        } else {
          // WebCalendar does not support this
          if (ICAL_EVENTS_DEBUG) echo "Unsupported iCal BYMONTH value \"$match[1]\"<br />\n";
        }
      } else if ( preg_match ( "/^BYDAY=(.+)$/i", $RR[$i], $match ) ) {
        $fevent['Repeat']['RepeatDays'] = rrule_repeat_days( explode(',', $match[1]) );
      } else if ( preg_match ( "/^BYMONTHDAY=(.+)$/i", $RR[$i], $match ) ) {
        // NOT YET SUPPORTED -- TODO
        if (ICAL_EVENTS_DEBUG) echo "Unsupported iCal BYMONTHDAY value \"$RR[$i]\"<br />\n";
      } else if ( preg_match ( "/^BYSETPOS=(.+)$/i", $RR[$i], $match ) ) {
        // NOT YET SUPPORTED -- TODO
        if (ICAL_EVENTS_DEBUG) echo "Unsupported iCal BYSETPOS value \"$RR[$i]\"<br />\n";
      }
    }

    // Repeating exceptions?
    if ($event['EXDATE']) {
      $fevent['Repeat']['Exceptions'] = array();
      $EX = explode(",", $event['EXDATE']);
      foreach ( $EX as $exdate ){
        $fevent['Repeat']['Exceptions'][] = icaldate_to_timestamp($exdate);
      }
    }
  } // end if rrule

  
  /* amr - check if we have lost any data in this process and save it anyway */
  foreach ($event as $i => $e)
  {
	if (!isset($fevent[$i])) {$fevent[$i] = $e;}
  }
  /* amr we want this to hold the event date, once we have established a repeat */
  $fevent['EventDate'] = $fevent['StartTime'];  /* amr  *** hot helpding */
  $fevent['EndDate'] = $fevent['EndTime'];
  return $fevent;
}

// Figure out days of week for weekly repeats
function rrule_repeat_days($RA) {
  $T = count($RA);
  $sun = $mon = $tue = $wed = $thu = $fri = $sat = 'n';
  for ($i = 0; $i < $T; $i++) {
    if ($RA[$i] == 'SU') {
      $sun = 'y';
    } elseif ($RA[$i] == 'MO') {
      $mon = 'y';
    } elseif ($RA[$i] == 'TU') {
      $tue = 'y';
    } elseif ($RA[$i] == 'WE') {
      $wed = 'y';
    } elseif ($RA[$i] == 'TH') {
      $thu = 'y';
    } elseif ($RA[$i] == 'FR') {
      $fri = 'y';
    } elseif ($RA[$i] == 'SA') {
      $sat = 'y';
    }
  }
  return $sun.$mon.$tue.$wed.$thu.$fri.$sat;
}


// Calculate repeating ending time
function rrule_endtime($int,$freq,$start,$end) {

  // if # then we have to add the difference to the start time
  if (preg_match("/^#(.+)$/i", $end, $M)) {
    $T = $M[1] * $freq;
    $plus_d = $plus_m = $plus_y = '0';
    if ($int == '1') {
      $plus_d = $T;
    } elseif ($int == '2') {
      $plus_d = $T * 7;
    } elseif ($int == '3') {
      $plus_m = $T;
    } elseif ($int == '4') {
      $plus_m = $T;
    } elseif ($int == '5') {
      $plus_y = $T;
    } elseif ($int == '6') {
      $plus_m = $T;
    }
    $endtime = icaldate_to_timestamp($start,$plus_d,$plus_m,$plus_y);

  // if we have the enddate
  } else {
    $endtime = icaldate_to_timestamp($end);
  }
  return $endtime;
}

// Replace RFC 2445 escape characters
function format_ical_text($value) {
  $output = str_replace(
    array('\\\\', '\;', '\,', '\N', '\n'),
    array('\\',   ';',  ',',  "\n", "\n"),
    $value
  );

  return $output;
}

?>
