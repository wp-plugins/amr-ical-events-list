<?php
/*
Plugin Name: iCal Events
Version: 1.12
Plugin URI: http://dev.webadmin.ufl.edu/~dwc/2005/03/10/ical-events-plugin/
Description: Display events from an iCal source. Uses <a href="http://cvs.sourceforge.net/viewcvs.py/webcalendar/webcalendar/import_ical.php?rev=HEAD">import_ical.php</a> from the <a href="http://sourceforge.net/projects/webcalendar/">WebCalendar</a> project.
Author: Daniel Westermann-Clark
Author URI: http://dev.webadmin.ufl.edu/~dwc/
*/

require_once('import_ical.php');
/* amr  get cache time from admin */
if (isset ($amr_listtype))
	if (isset($amr_options[$amr_listtype]['limit']['cache']))
			define('ICAL_EVENTS_CACHE_TTL', $amr_options[$amr_listtype]['limit']['cache'] *60*60);  
	else 	define('ICAL_EVENTS_CACHE_TTL', 24 * 60 * 60);  // 1 day
	
define('ICAL_EVENTS_CACHE_DEFAULT_EXTENSION', 'ics');
define('ICAL_EVENTS_MAX_REPEATS', '100');
if (! defined('ICAL_EVENTS_DEBUG')) define('ICAL_EVENTS_DEBUG', false);

// As defined by import_ical.php
$ICAL_EVENTS_REPEAT_INTERVALS = array(
	1 => 24 * 60 * 60,        // Daily
	2 => 7 * 24 * 60 * 60,    // Weekly
	5 => 365 * 25 * 60 * 60,  // Yearly
);

if (! class_exists('ICalEvents')) 
{
	class ICalEvents {
		/*
		 * Helper method for displaying events. Note that the API of
		 * this method may change, so you should use display_events.
		 */
		function do_display_events($url, $gmt_start, $gmt_end, $limit, $date_format, $time_format, $before, $after, $before_date, $after_date, $use_summary, $before_summary, $after_summary, $use_description, $before_description, $after_description, $replace_newlines_with, $use_location, $before_location, $after_location, $use_url, $echo) 
		{
			$events = ICalEvents::get_events($url, $gmt_start, $gmt_end, $limit);
			if (! $events) return;

			$output = '';
			foreach ($events as $event) {
				$output .= $before;

				$output .= $before_date;
				if (ICalEvents::is_all_day($event['StartTime'], $event['EndTime'])) {
					$output .= htmlentities(strftime($date_format, $event['StartTime']));
				}
				else {
					$output .= htmlentities(ICalEvents::format_date_range($event['StartTime'], $event['EndTime'], $event['Untimed'], $date_format, $time_format));
					
				}
				$output .= $after_date;

				
				if ($use_summary and $event['Summary']) {
					$output .= $before_summary;
					if ($use_url and $event['URL']) {
						$output .= '<a href="' . $event['URL'] . '">';
					}
					$output .= htmlentities($event['Summary']);
					if ($use_url and $event['URL']) {
						$output .= '</a>';
					}
					$output .= $after_summary;
				}

				if ($use_description and $event['Description']) {
					$output .= $before_description;
					if ($replace_newlines_with) {
						$output .= str_replace("\n", $replace_newlines_with, htmlentities($event['Description']));
					}
					$output .= $after_description;
				}

				if ($use_location and $event['Location']) {
					$output .= $before_location . htmlentities($event['Location']) . $after_location;
				}

				if ($event['UID']) {
					$output .= '<!-- ' . htmlentities($event['UID']) . ' -->';
				}
				$output .= $after . "\n";
			}

			if ($echo) {
				echo $output;
			}

			return $output;
		}

		/*
		 * Return a list of events from the specified calendar.  For
		 * more on what's available, read import_ical.php or use
		 * print_r.
		 */
		function get_events($url, $gmt_start = null, $gmt_end = null, $limit = null) 
		{
			if (ICAL_EVENTS_DEBUG)	echo 'Attempt to open get events from '.$url.'<br>';
			$file = ICalEvents::cache_url($url);
			if (! $file) {
				echo "iCal Events: Error loading [$url]";
				return;
			}
			$ical = parse_ical($file);
			$events = $ical ['Items'];
//			$events = parse_ical($file);
			if (! is_array($events) or count($events) <= 0) {
				echo "iCal Events: Error parsing calendar [$url]";
				return;
			}

			$events = ICalEvents::constrain($events, $gmt_start, $gmt_end, $limit);

			return $events;
		}

		/*
		 * Cache the specified URL and return the name of the
		 * destination file.
		 */
		function cache_url($url) {
			$file = ICalEvents::get_cache_file($url);
			if (ICAL_EVENTS_DEBUG)	echo 'Attempt to open File '.$file.'<br>';
			if (! file_exists($file) or time() - filemtime($file) >= ICAL_EVENTS_CACHE_TTL) 
			{
				$data = wp_remote_fopen($url);
				if (ICAL_EVENTS_DEBUG)	echo 'Attempt to open url '.$url.'<br>';
				if ($data === false) die("iCal Events: Could not fetch [$url]");

				$dest = fopen($file, 'w') or die("Error opening $file");
				fwrite($dest, $data);
				fclose($dest);
			}

			return $file;
		}

		/*
		 * Return the full path to the cache file for the specified URL.
		 */
		function get_cache_file($url) {
			return ICalEvents::get_cache_path() . ICalEvents::get_cache_filename($url);
		}

		/*
		 * Attempt to create the cache directory if it doesn't exist.
		 * Return the path if successful.
		 */
		function get_cache_path() {
			$cache_path = trailingslashit(ABSPATH . 'wp-content/ical-events-cache');

			if (! file_exists($cache_path)) {
				if (is_writable(dirname($cache_path))) {
					if (! mkdir($cache_path, 0777)) {
						die("Error creating cache directory ($cache_path)");
					}
				}
				else {
					die("Your cache directory (<code>$cache_path</code>) needs to be writable for this plugin to work. Double-check it. <a href='" . get_settings('siteurl') . "/wp-admin/plugins.php?action=deactivate&amp;plugin=ical-events.php'>Deactivate the iCal Events plugin</a>.");
				}
			}

			return $cache_path;
		}

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

		/*
		 * Constrain the list of events to those which fall between the
		 * specified start and end time, up to the specified number of
		 * events.
		 */
		function constrain($events, $gmt_start = null, $gmt_end = null, $limit = null) {
			$repeats = ICalEvents::collapse_repeats($events, $gmt_start, $gmt_end, $limit);
			if (is_array($repeats) and count($repeats) > 0) {
				$events = array_merge($events, $repeats);
	 		}

			$events = ICalEvents::sort_by_key($events, 'StartTime');
			if (! $limit) $limit = count($events);

			$constrained = array();
			$count = 0;
			foreach ($events as $event) 
			{	
/* amr This is not great, but for formatting purposes, I want the Event date and start time to be separate.  Start time holds the event date after repeats added.*/			
				$event ['EventDate'] = $event['StartTime'];
				
				if (ICalEvents::falls_between($event, $gmt_start, $gmt_end)) {
					$constrained[] = $event;
					++$count;
				}



				if ($count >= $limit) break;
			}

			return $constrained;
		}

		/*
		 * Sort the specified associative array by the specified key.
		 * Originally from
		 * http://us2.php.net/manual/en/function.usort.php.
		 */
		function sort_by_key($data, $key) {
			// Reverse sort
			$compare = create_function('$a, $b', 'if ($a["' . $key . '"] == $b["' . $key . '"]) { return 0; } else { return ($a["' . $key . '"] < $b["' . $key . '"]) ? -1 : 1; }');
			usort($data, $compare);

			return $data;
		}

		/*
		 * Return true iff the specified event falls between the given
		 * start and end times.
		 */
		function falls_between($event, $gmt_start, $gmt_end) {
			$falls_between = false;

			if (ICAL_EVENTS_DEBUG) {
				echo "<br>UID = [{$event['UID']}], StartTime = [{$event['StartTime']}], EndTime = [{$event['EndTime']}], Untimed = [{$event['Untimed']}], Duration = [{$event['Duration']}], gmt_start = [$gmt_start], gmt_end = [$gmt_end]\n";
			}

			if ($event['Untimed'] or $event['Duration'] == 1440) {
				// Keep all-day events for the whole day
				$falls_between = ((! $gmt_start or $event['StartTime'] + 86400 > $gmt_start)
					and (! $gmt_end or $event['EndTime'] < $gmt_end));
			}
			else {
				$falls_between = ((! $gmt_start or $event['StartTime'] > $gmt_start)
					and (! $gmt_end or $event['EndTime'] < $gmt_end));
			}

			return $falls_between;
		}

		/*
		 * Collapse repeating events down to nonrepeating events at the
		 * corresponding repeat time.
		 */
		function collapse_repeats($events, $gmt_start, $gmt_end, $limit) {
			$repeats = array();

			foreach ($events as $event) {
				if (isset($event['Repeat'])) {
					$r = ICalEvents::get_repeats_between($event, $gmt_start, $gmt_end, $limit);
					if (is_array($r) and count($r) > 0) {
						$repeats = array_merge($repeats, $r);
					}
				}
			}

			return $repeats;
		}

		/*
		 * If the specified event repeats between the given start and
		 * end times, return one or more nonrepeating events at the
		 * corresponding times.
		 * TODO: OAMR_NLy handles some types of repeating events
		 * TODO: Check for exceptions to the RRULE
		 */
		function get_repeats_between($event, $gmt_start, $gmt_end, $limit) {
			global $ICAL_EVENTS_REPEAT_INTERVALS;

			$rrule = $event['Repeat'];

			$repeats = array();
			if (isset($ICAL_EVENTS_REPEAT_INTERVALS[$rrule['Interval']])) {
				$interval    = $ICAL_EVENTS_REPEAT_INTERVALS[$rrule['Interval']] * ($rrule['Frequency'] ? $rrule['Frequency'] : 1);
				$repeat_days = ICalEvents::get_repeat_days($rrule['RepeatDays']);

				$repeat = null;
				$count = 0;
				while ($count <= ICAL_EVENTS_MAX_REPEATS) {
					if ($repeat_days) {
						foreach ($repeat_days as $repeat_day) {
							$repeat = ICalEvents::get_repeat($event, $interval, $count, $repeat_day);
							if (! ICalEvents::is_duplicate($repeat, $event)
							    and ICalEvents::falls_between($repeat, $gmt_start, $gmt_end)) {
								$repeats[] = $repeat;
							}

							if (ICalEvents::after_rrule_end_time($repeat, $rrule)) break;
						}
					}
					else {
						$repeat = ICalEvents::get_simple_repeat($event, $interval, $count);
						if (! ICalEvents::is_duplicate($repeat, $event)
						    and ICalEvents::falls_between($repeat, $gmt_start, $gmt_end)) {
							$repeats[] = $repeat;
						}
					}

					if (ICalEvents::after_rrule_end_time($repeat, $rrule)) break;

					// Don't repeat past the user-defined limit, if one exists
					if ($limit and $count >= $limit) break;

					++$count;
				}
			}
			else {
				echo "Unknown repeat interval: ${rr['Interval']}";
			}

			return $repeats;
		}

		/*
		 * Given a string like 'nynynyn' from import_ical.php, return
		 * an array containing the weekday numbers (0 = Sun, 6 = Sat).
		 */
		function get_repeat_days($yes_no) {
			$repeat_days = array();
			for ($i = 0; $i < strlen($yes_no); $i++) {
				if ($yes_no[$i] == 'y') $repeat_days[] = $i;
			}

			return $repeat_days;
		}

		/*
		 * Using the specified event as a base, return the repeating
		 * event the given number of intervals (in seconds) in the
		 * future on the repeat day (0 = Sun, 6 = Sat).
		 */
		function get_repeat($event, $interval, $count, $repeat_day) {
			$repeat = ICalEvents::get_simple_repeat($event, $interval, $count);

			$date = getdate($event['StartTime']);
			$wday = $date['wday'];
			$offset = ($repeat_day - $wday) * 86400;

			$repeat['StartTime'] += $offset;
			if (isset($repeat['EndTime'])) {
				$repeat['EndTime'] += $offset;
			}

			return $repeat;
		}

		/*
		 * Using the specified event as a base, return the repeating
		 * event the given number of intervals (in seconds) in the
		 * future.
		 */
		function get_simple_repeat($event, $interval, $count) {
			$duration = 0;

			if ($event['Duration']) {
				$duration = $event['Duration'] * 60;
			}
			else if ($event['EndTime']) {
				$duration = $event['EndTime'] - $event['StartTime'];
			}

			$repeat = $event;
			unset($repeat['Repeat']);

			$repeat['StartTime'] += $interval * $count;

			// Default to no duration
			$repeat['EndTime'] = $repeat['StartTime'];
			if ($duration > 0) {
				$repeat['EndTime'] = $repeat['StartTime'] + $duration;
			}

			// Handle timezone changes since the initial event date
			$offset = date('Z', $event['StartTime']) - date('Z', $repeat['StartTime']);
			$repeat['StartTime'] += $offset;
			$repeat['EndTime'] += $offset;

			return $repeat;
		}

		/*
		 * Return true if the specified event is passed the
		 * RRULE's end time.  If an end time isn't specified,
		 * return false.
		 */
		function after_rrule_end_time($repeat, $rrule) {
			return ($repeat and $rrule
				and $repeat['StartTime'] and $rrule['EndTime']
				and $repeat['StartTime'] >= $rrule['EndTime']);
		}

		/*
		 * Return true if the start and end times are the same.
		 */
		function is_duplicate($event1, $event2) {
			return ($event1['StartTime'] == $event2['StartTime']
				and $event1['EndTime'] == $event2['EndTime']);
		}

		/*
		 * Return a string representing the specified date range.
		 */
		function format_date_range($gmt_start, $gmt_end, $untimed, $date_format, $time_format, $separator = ' - ') {
			$output = '';

			$output .= ICalEvents::format_date_range_part($gmt_start, $untimed, ICalEvents::is_today($gmt_start), $date_format, $time_format);

			if ($gmt_start != $gmt_end) {
				$output .= $separator;
				$output .= ICalEvents::format_date_range_part($gmt_end, $untimed, ICalEvents::is_same_day($gmt_start, $gmt_end), $date_format, $time_format);
			}

			$output = trim(preg_replace('/\s{2,}/', ' ', $output));

			return $output;
		}

		/*
		 * Return a string representing the specified date.
		 */
		function format_date_range_part($gmt, $untimed, $only_use_time, $date_format, $time_format) {
			$default_format = "$date_format $time_format";

			$format = $default_format;
			if ($untimed) {
				$format = $date_format;
			}
			else if ($only_use_time) {
				$format = $time_format;
			}

			return strftime($format, $gmt);
		}

		/*
		 * Given a time value (as seconds since the epoch), return true
		 * iff the time falls on the current day.
		 */
		function is_today($gmt) {
			return ICalEvents::is_same_day(time(), $gmt);
		}

		/*
		 * Return true iff the two times span exactly 24 hours, from
		 * midnight one day to midnight the next.
		 */
		function is_all_day($gmt1, $gmt2) {
			$local1 = localtime(($gmt1 <= $gmt2 ? $gmt1 : $gmt2), 1);
			$local2 = localtime(($gmt1 <= $gmt2 ? $gmt2 : $gmt1), 1);

			return (abs($gmt2 - $gmt1) == 86400
				and $local1['tm_hour'] == 0
				and $local1['tm_year'] == $local2['tm_year']);
		}

		/*
		 * Return true iff the two specified times fall on the same day.
		 */
		function is_same_day($gmt1, $gmt2) {
			$local1 = localtime($gmt1, 1);
			$local2 = localtime($gmt2, 1);

			return ($local1['tm_mday'] == $local2['tm_mday']
				and $local1['tm_mon'] == $local2['tm_mon']
				and $local1['tm_year'] == $local2['tm_year']);
		}
	}
}
?>
