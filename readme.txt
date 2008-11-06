=== AmR iCal Events List ===
Contributors: Anmari, Dwc, PhpIcalendar
Donate link: http://webdesign.anmari.com/web-tools/donate/
Tags: calendar, events, ical, ics
Requires at least: 2.6, may work on earlier
Tested on: 2.6.1
Version: 2.2 alpha
Stable tag: trunk

Displays events from one or more calendars as a variety of customisable lists, as widget or page.

== Description ==

Fetch and display events from an iCalendar (`.ics`) URL in your blog, in  post or page or as a sidebar widget.
*   Widget list of upcoming events available
*   Control over contents and styling from the plugin and widget menu's.
*   Lots of css tags for innovative styling
* 	Grouping of events for readability and styling

It accepts a number of Icals urls (.ics files).  
It allows one to define "groupings of events (eg: monthly, weekly etc) which will then generate the necessary HTML and CSS to allow desired styling.
These could be presented as a single list or a sequence of lists.
There is a  standard default or a variety of configuration options to allow you to format (as in order and sequence) and style the resulting code almost any way you may like.
A number of possibilities are presented to get you started.  
HTML code in the descriptions is handled.
URL's in text fields will generate the necessary links, as will the URL field.
Locale and language specific date and time formatting is provided. Both the datetime and strftime formats can be used.  Note that not all are supported on all servers (particularly not windows). 
Wordpress default date and time formats will be defaulted to.  If upgrading, you will have to change these manually as it will not overwrite your earlier settings until you reset.

= General Logic =
1. Check if page has iCal Urls, and Parse URL's (cacheing or refreshing as necessary)
2. Merge events if multiple urls
3. Expand recurring events and Limit the total list, so it does not go one for ever
4. Sort by datetime
5. Group (or rather issue grouping code on chang eof group) if requested 
6. Generate any special display situations:
*   If event is all day, replace start time with all day
*   If start time equals end time, set end time to empty string
*   If end date = start date, don't display end date
*   If timezone requested, only display if different from first calendar timezone, or different from server timezone.(intended, not completely implemented yet)
*   If url, convert to a hyperlink
*   If location, add a map link to google maps.  This can be hidden with css if not required.
*  Allow html in descriptions, and convert any url's to links if not already converted.  

This version of the plugin has been majorly rewritten, so while ideas have come from a number of sources, in many cases the code has been rewritten based on the RFC 2445.   In various other code used, prioblems were being experienced with Recurrence, Duplications (due to exceptions in Recurrences)  and Timezones.   Recurrence can be incredibly complex and some plugins opt for simply not implementing many possibilities.   This alpha version has NOT been fully tested yet and is being offered as an aid to those experiencing problems.

Some inputs/ideas from:
*  [import_ical.php](http://cvs.sourceforge.net/viewcvs.py/webcalendar/webcalendar/import_ical.php?rev=HEAD) from the [WebCalendar](http://sourceforge.net/projects/webcalendar/) project. 
*  [dwc's plugin] (http://dev.webadmin.ufl.edu/~dwc/2005/03/10/ical-events-plugin/)
*  [PhpIcalendar] (http://phpicalendar.net/)
*  [Horde] (http://www.horde.org/kronolith/) 

== Installation ==

1. Unzip the folder into your wordpress plugins folder.
2. Activate the plugin through the 'Plugins' menu in WordPress
3. Add one or more [iCal:http://yoururl.ics] to a page or post (Note post usage may result in non-validating code, due to multiple occurences of "id" tags on same web page
4. Manage the plugin through the settings screen.

== Frequently Asked Questions ==

= How can I control the output of this plugin? =

Simplest: Put [iCal:http://yoururl.ics] in your page or post.  A Default List Type will be used.

To combine calendars ala Google style, for example including a public holiday calendar, separate the URL's with commas.
[iCal:http://yoururl.ics,http://anotherurl.ics]

To specify another listtype defined in the admin section, add a ";listtype=N" where N is the number of the list type that you want.
[iCal:http://yoururl.ics;listtype=2]

To list a series of calendars -eg: a different calendar for different groups or classes in sequence:
[iCal:http://yoururl.ics]
[iCal:http://anotherurl.ics]

You can of course have text between these specs.

The admin section (or if wanting to operate standalone - see the Ical_common file.) allows control over many aspects:
* the components to include (eg: todo's )
* the component properties, their layout and sequence.  Eg select end times, or durations (or both!)
* the grouping (we used to just do a monthly grouping, no we allow for many others )


and many more - see the settings page in the wordpress admin section.

= What css tags are there? =

This is ot a complete list, please view source for more.
* span.map - to style or hide the map link to google maps
* ul.group - to style any grouping level lists, plus id's for month etc
* ul.amrrow - to style an event list - or row
* ul.amrcol - to style a subset of event properties
* h3.group - ty style grouping levels, but not affect any h3's in the descriptions
...

= Why different css tags for widget and page calendars? = 

Well, if they happend to occur on the same page then the page will not validate.  And Of course allowingfor multiple calendars on a page was also tricky!

= Can it handle html in the descriptions? =

Yes it can - note that google seems to allow you to enter and save html, however if one goes back to edit it, it appears to through the html tags away.  

Please check your google file before assuming it is a plugin problem.

= How often is the calendar checked for new events? =

Specify in the cache parameter a value in hours. Loading calendars too frequently can get your server banned, so use your best judgment when setting this value.

= Why aren't my events showing up correctly? =

This plugin supports many event definitions that follow the iCalendar specification (RFC 2445). However, not all recurrence rules are implemented in the parser. There may also be bugs in how the plugin interprets the parsed data.

If an event is showing up correctly in your calendar application (iCal, Google Calendar) but not on your blog, try turning on debugging:

`define('ICAL_EVENTS_DEBUG', true);`

Now reload your blog.  You may see various lines about unsupported iCal values; if this is the case, and you're interested in getting it fixed, take a look at the `import_ical.php` file.

= Where can I find iCalendar files? =

There are many iCalendar sources, such as:

* [Apple's iCal library](http://www.apple.com/ical/library/)
* [iCalShare](http://www.icalshare.com/)
* [Google Calendar](http://calendar.google.com/)

= My server does not support `fopen` on URLs. Can I still use this plugin? =

As of version 1.9, this plugin supports usage of cURL via WordPress' `wp_remote_fopen` function. Previous versions required the `url-cache` plugin for cURL support, but this is no longer the case.

= Support for more of Ical? =

There is partial support for all the ICAL components and properties.  
Allowance has been made to potentially support all features.  You will notice this particularly in the Admin section.
However since this is a single volunteer effort at this stage, the key areas that may be used have been targeted.
If you find that a particular implementation is needed for your website, contact me and we can discuss the possibility of including it.

For example:
Change Management Fields are not parsed
TimeZone definitions component and subcomponents if specified are not parsed and used.

== Screenshots ==

1. Screenshot of an annual repeating test in the classic theme, with enddates switched on
2. screenshot showing that any html in the description will be retained and that urls will be converted to hyperlinks.
3. A Timetable view - daily grouping with day still shown for each event and date and times listed underneath one another.  the vent link is shown in the second column.
4. Similar timetable view.  Repeating evenst in the Classic theme.  CSS styling allows one to float the times up next to another. 
5. Part of Admin screen showing options for a list type - multiple list types are provided for.
6. Part of admin screen showing how one can select the ical components and derieved pseudo components
7. Widget Admin screen, showing Title, No of events, List Type from plugin (default = 4 for widget), provision for multiple URL's, and link to calendar page.  the calendar page lin is inserted behind the title.
8. Widget in classic theme.  the Classic theme caused a few problems with the CSS !
9. iCal Specification on the page that you wish the calendar/'s to appear.  
10. Widget in the Default theme - need to override colours and still those bullets - normal css not working!
11. With locale set to German, showing german days of week, in Sandbox theme.
12. Multiple Groupings (unstyled here, but with styling tags, so imagine what you could do )

== Version History ==

Version 2.2 alpha
* Removes duplicated events (checks UID and Sequence ID to determine if the event is a later version of another event).
* Improved the whole way that that dates are managed - using PHP dateTime class and timezone object functionality.  Somewhat tested - again good test situations are required - around daylightsaving time is really interesting.
* column headings not in use yet (but enterable) - need to convert to table output
* calendar Subscribe link available if 'icsurl' requested
* can test by passing iCal=url:listtype=b in the query string of any wordpress page - content will be ignored.
* css changed slightly - more testing required for impact on different themes.
* removed the </p> added to make wp validate - not required anymore?
* allows for todo lists, journals and freebusy (for use as availability!) - this has been slightly tested, not up to my usual standard.  Good test files are required.  If you have a need for this, please send me your files or links to your public files.
* improved conversion of urls to hyperlinks in descriptions - will now handle all sorts of links including bookmarks.  Ihad a bit of fun (not) dealing with <br> after urls!
* changed some defaults - simplified - commented out some that would never be used.
* allows for repeatable properties - in theory one could have multiple summary fields etc.


Version 2.1
* datetime formats, name and css file now update and save in admin menu- no need to go to config file; 
* deleted ridiculous grouping option solar term!! 
* added code for grouping options that people may actually want to use (Seasons, astronomical etc). [Seasons on wikipedia] (http://en.wikipedia.org/wiki/Season#Reckoning)
* Zodiac grouping added just for the fun of it [Zodiac] (http://en.wikipedia.org/wiki/Zodiac)
* Quarter grouping added - change dates in the config file if fiscal or tax groupings required.

Version 2.01
* added check for existance of validation function filter_var (introduced in 5.2).  No/Limited validation in admin if it does not exist.  Ask your host to update.
* changed css to specify width for first col so that all rows look the same
* switched timezone fields on by default in listtype 1.

Verson 2
This one - repeating events, no table all nested lists, lots of configuration options.

Version 1
Listed events without repeats into a table with nested lists. It allowed for a monthly break, a config file and had a default css file

Version 0

== More to come ==
If time permits, I'd like to:

* Convert to table use at high level to simlpify css 
* Improve the recurrence rules
* Improvethe css

= To do list, journals =
Not tested or fully implemented

== Bugs ==

= Alternate css in listtypes =
Css file cannot be determined for page icals until too late, so the css file in list type 1 is always used.
The widget can use the css file specified in it's list type, as we do not have to read the page content first.
This is not a major problem, potentially confusing.  One should move the css option out of the list type perhaps?

= Links in description in widgets =

Wordpress appears to also try to convert url's in text to hyperlinks.  So if one had a URL in a text field, and one specified that this should be included in the widget, then one may get a doubled up link. 
Things work as expected for the same code in a page not in the sidebar.
Of course one normally does not want the long text n the widget, so figuring out why this is happening is low on the list!

= Timezones =
If the wordpress timezoneplugin is being used, we will pickl that up and use that as a default.  This will be echoed in the setting screen.
Else it looks for X-WR-TIMEZONE (google) in the first calendar file and uses that as the default. As yet it does not extract TZID from within the Timezone component. (RTM files for eg, but should!).


= Trouble shooting =
For missing or incorrect data:
Look at your .ics file.  Find the timezone and date specs for the problem event.  Check your server timezone.  Check the configuration.

For coders or modifiers:
If unexpected data is encountered, an HTML comment may be written.  The idea is that it flags the problem without making your website look dirty. So view your source to see if there is any info there. The problem may be in the source data, or it may be in the code.  
If a major problem is experienced, it may be written to the screen.
You can also set the debug flag to true - this will result in quite a lot of code written to the screen!
There is also commented out debug code.  You could check the problem area and switch on.
