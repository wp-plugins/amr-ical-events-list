=== AmR iCal Events List ===
Contributors: Anmari
Donate link: http://webdesign.anmari.com/web-tools/donate/
Tags: calendar, events, ical, ics, upcoming events, google, notes, todo, journal, freebusy, availability, widget
Requires at least: 2.6
Tested up to: 2.7.1
Version: 2.3.3
Stable tag: 2.3.3

Displays events from one or more calendars as a variety of customisable lists, as widget or page.

== Description ==

Displays cached lists of upcoming events, and/or notes, journal, freebusy information from one or more ical calendar (`.ics`) files. Offers a range of defaults and customisation options. Including the possiblity of grouping events by month/week/day or many other for presentation and styling. Offers your viewers the option to add the events or the whole calendar to their calendars (google or other).   Try the demo api with your icsfile before you download: 
[Demo site](http://anmari.com/testing/wp)

== Version History ==
= Version 2.3.3 =
*   Changed the user access level to 8, so only admin can do setting changes, not editor, previous version allowed editor to change settings.
*   Fixed bug where the relocated refresh icon did not actually refresh if you had no "?" in the url.  Also allow 'refresh=true' instead of 'nocache'.
*   Changed form security to use new 2.7 wordpress "nonce" functions.  This prevents cross scripting in a stronger way than before.
*   added an uninstall option which will delete the option entries, either by request from the settings or when the plugin files are deleted (if using wordpress 2.7). Note the reset button will delete and recreate the default Amr iCal options in one go. The uninstall is added for completeness and for your use if you no longer need the plugin. 
*   Made settings menu entry look prettier - tightened up the text and added calendar icon
*   "Bling" classes for the link icons added so that canbe not displayed when printing. A print stylesheet has also been added to achieve this.
*	Added alt text on the settings icon in the admin menu to ensure that the admin page still validates 100% with html - on my code anyway.
*   Added option to specify own css rather than automarically loading ical css.   You should ensure that the necessary css is in your theme stylesheet then.   This allows you to make your pages more efficient by reducing the number of files required to load.
*   An settings "RESET" will now also reset widget settings, not just the main settings.  Remeber to save any special settings if you do this.  A reset may be necessary if you have an old version and want to take advantage of new options and defaults.
*   Removed the line breaks for the widget event summary 'titles' that appear when you hover on the summary. This looks better and does not require any javascript.
*   Clarified the widget calendar page option and attempted to default it to what you might have called your calendar.  You may need to reset to see this happen.

= Version 2.3.2 =
*   Fixed bug if there was a url for the event.  (The url is entered as a hyperlink behind the summary text).  Thanks to Ron Blaisdell for finding this.  Currently in google one cannot setup a URL for a event.  
*   Removed testmode comment when iCal url passed in query string, allow possibble "API" use.
*   Straight after importing events in the timezones specified by the ical file, they will be converted to the timezone of the wordpress installation.  THis ensures that "same day" and "until" functions.
*   Plugin will determine a default php timezone from the wordpress gmt offset if the automatic timezone plugin has not been installed.
*   If the wordpress timezone is different from the calendar timezone, one can click on the timezone icon and refresh the page in the calendar's timezone. 
*   Set the defalt start time to the beginning of the day that the wordpress timezone is in, so that we
can also see events that might have just started.
*   Changed the refresh link to be next to the other calendar property icons and put the last cached time in the alt text and title rather than at bottom of calendar.  Also fixed how it reflected time relative to the server timezone.
*   In the "Add event to google", improved handling of complex details - google only handles simple html.  Note: bad calendar content can still break google (for example the valentines day entry has an errant "/")

= Version 2.3.1 =
*   Changed some error detection and reporting to improve user experience - moved many messages to comments if no data or bad url entered
*   Fixed the way the widget was interacting with the main plugin
*   Corrected an error that was visible when the calendar timezone and the wordpress timezone were different.  This showed up on single events only as google offers a UTC date, not a TZ date and the plugin was not dealing with this correctly.  Plugin will work now if wordpress timezone and calendar timezone are the same.  More work is required though to make it more robust and cater for different situations - coming soon.


= Version 2.3 =
*   Simplified css styling by deciding that a list of events was essentially a table and going back to the table html - this avoids problems with many less robust themes.
*   Css file spec changed to one at global level (Icallist.ccs)  If the file does not exist, it will assume that you have included the necessary styling in your theme stylesheet.
*   Added icons to allow for clean look, while still having functionality of options. 


= Version 2.2 alpha =
*   Removes duplicated events that may be generated by your ical generator.  For example if one instance of a recurring event is edited.  Implementing the recurring rule generates an event instance that matches another event in the file.  They will have the same UID and date, but a different Sequence ID. 
*   Improved the imezone and date handling uses PHP 5 dateTime class and timezone object functionality.  Somewhat tested - again good test situations are required - around daylightsaving time is really interesting.
*   column headings not in use yet (but enterable) - need to convert to table output - coming soon I hope.
*   calendar Subscribe link available if 'icsurl' requested in the settings for a list type.
*   can test by passing iCal=url:listtype=n in the query string of any wordpress page - the page content will be ignored.
*   css changed slightly - more testing required for impact on different themes.
*   removed the </p> added to make wp validate - not required anymore in latest version of wordpress ?
*   allows for other ical components such as todo lists, journals and freebusy (maybe for use as availability!) - this has been slightly tested, not up to my usual standard.  Good test files are required.  If you have a need for this and think there is an error, please send me your files or links to your public files.  It uses the same logic as the event, so differences may just be a question of layout and style.
*   improved conversion of urls to hyperlinks in long text fields like description - will now handle all sorts of links including bookmarks.  I had a bit of fun (not) dealing with <br> after urls!
*   changed some defaults - simplified - commented out some that are unlikley to be used. 
*   allows for repeatable properties - in theory one could have multiple summary fields for one event etc.
*   Todo: implement more complex recurring rules, more thorough testing, some user documentation and ideas, simplify the css. 

= Version 2.1 =
*   datetime formats, name and css file now update and save in admin menu- no need to go to config file; 
*   deleted ridiculous grouping option solar term!! 
*   added code for grouping options that people may actually want to use (Seasons, astronomical etc). [Seasons on wikipedia] (http://en.wikipedia.org/wiki/Season#Reckoning)
*   Zodiac grouping added just for the fun of it [Zodiac] (http://en.wikipedia.org/wiki/Zodiac)
*   Quarter grouping added - change dates in the config file if fiscal or tax groupings required.

= Version 2.01 = 
*   added check for existance of validation function filter_var (introduced in 5.2).  No/Limited validation in admin if it does not exist.  Ask your host to update.
*   changed css to specify width for first col so that all rows look the same
*   switched timezone fields on by default in listtype 1.

= Version 2 =
*   repeating events, no table all nested lists, lots of configuration options.

= Version 1 =
*   Listed events without repeats into a table with nested lists. It allowed for a monthly break, a config file and had a default css file

= Version 0 =

== More to come ==
If time permits, I'd like to:
*  Add in using the new shortcode API, while maintaining previous method for compatibility
*  Add the more remote recurrence rules
*  Add more css examples
*  Allow an option to say whether to use defau lt style file or not - this would prevent upgrades from recreating the style file.
*  Possibly get the plugin to deal with the blank lines that the Remember the milk ical task files deliver - is that correct or are they wrong? and if so, should one allow for their error?

= Content =
*   If the information is available in your calendar, include additional fields and/or add some bling: .. links to google maps if location or geo exists, "add event" icons or "add calendar" (not just the icsfile)
*   Include other calendars for your viewers info.  Many are available on the web and can be "sorted" into your calendar: public holidays, world events, school terms, eccentric dates etc.
*   Will handle any html in the text fields.
*   Allocate fields to columns and order within the columns and use css for example to float end time up next to start time.
*   Offers a refresh link with date and time last cached - may be useful if your calendar has many updates on one day a week, with long gaps inbetween. Prevents unnecessary downloads.
*   Optionally choose timezone.
*   Add your own before/after content or styling (eg: SUMMARY as h3 ) for each field

= Styling =

*   Works out the box with a default css style as well as many other possibilities:
*   Allows grouping of events (eg: daily, weekly, monthly, quarterly, by seasons, by years for readability and styling. 
*   Default basic css provided, plus lots of css tags for innovative styling (eg: by group of dates, or for recurring events, or untimed (all day) events. 
*   A default set of transparent(for diffirent backgrounds) images is provided for the additional "icon" fields

= Date, Times and Timezone =

*   Timezones - there is your server's timezone, the timezone of the calendar files, and your wordpress timezone.  If you have the automatic timezone plugin activated, this is the best option.  If anyone needs more sophisticated functionality such as allowing a selection of timezones, please contact me.
*   Locale and language specific date and time formatting is provided. Both the datetime and strftime formats can be used.  Note that not all are supported on all servers (particularly not windows). 
*   Wordpress default date and time formats will be defaulted to.  If upgrading, you will have to change to these manually if you want them as it will not overwrite your earlier settings until you reset.

= Testing =
*    Can pass URL's, Listtypes and nocache/debug options via the url query string for ease of testing.

= Upgrading =
*    To access some of the new features, you may need to "reset" your options.  First make a note of any special settings, reset, then reapply your special settings.

= General Logic =
1. Check if page has iCal Urls, and then parse URL's (cacheing or refreshing as necessary)
2. Merge events if multiple urls specified
3. Expand recurring events and Limit the total list, so it does not go one for ever
4. Sort by datetime
5. Group (or rather issue grouping code on change of group) if requested 
6. Generate any special display situations such as:
*   If event is all day, replace start time with all day
*   If start time equals end time, set end time to empty string
*   If end date = start date, don't display end date
*   If url in text, convert to a hyperlink
*   If location or geo exists and map requested, add a map link to google maps. Include the calendar location if the location text is short, to help google find it. 
*  Allow html in descriptions, and convert any url's to links if not already converted.  

This version of the plugin has been rewritten significantly, so while ideas have come from a number of sources, in many cases the code is new - developed based on the RFC 2445.   In various other code scripts originally used, problems were being experienced with Recurrence, Duplications (due to exceptions in Recurrences) and Timezones.   Recurrence can be incredibly complex and some plugins opt for simply not implementing many possibilities.   

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
5. Change/salt the css as desired.

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
Remember to add css for the extra calendars.

You can of course have text between the iCal specs.

The admin section (or if wanting to operate standalone - see the Ical_common file.) allows control over many aspects:
*   the components to include (eg: todo's )
*   the component properties, their layout and sequence.  Eg select end times, or durations (or both!)
*   the grouping (we used to just do a monthly grouping, no we allow for many others )

and many more - see the settings page in the wordpress admin section.

= What css tags are there? =

There is enough css to work out the box, however if you want to style it further, then each iCal field has it's own li class.  Plus there are additional derived classes such as
*   .addtogoogle
*   .addevent
*   .icsurl
This is not a complete list, please view page source for more.
*   .alt - alternate rows
*   .map - to style or hide the map link to google maps
*   .group - to style any grouping level lists, 
*   .Month, .day etc - if that grouping was chosen
*   .MonthMMMYY - to style individual groups
*   .amrcol - to style a subset of event properties
*   .untimed
*   .recur - repeating events
...

= Why different css tags for widget and page calendars? = 

Well, if the widget and the calendar happen to occur on the same page then the page will not validate.  And Of course allowing for multiple calendars on a page was also tricky!

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
The PHP timezone definition is used.  Any TimeZone definitions component and subcomponents if specified are not parsed and used.  However the timezone of your calendar and of any item is noted and the time duly calculated with that timezone.

== Screenshots ==

1. Screenshot with monthly grouping and "add to", timezone and subscribe to icons
2. Widget screenshot in Golden Essence Theme - description shows on hover of summary
3. Three Column calendar list
4. Freebusy in widget - shows non availability.  This example has weekly grouping.
5. Part of Admin screen showing options for a list type - multiple list types are provided for.
6. Part of admin screen showing how one can select the ical components and derieved pseudo components
7. Widget Admin screen, showing Title, No of events, List Type from plugin (default = 4 for widget), provision for multiple URL's, and link to calendar page.  the calendar page lin is inserted behind the title.
8.  iCal Specification on the page that you wish the calendar/'s to appear.  
9. With locale set to German, showing german days of week, in Sandbox theme.
10. Just for fun - Multiple Groupings (unstyled here, but with styling tags, so imagine what you could do )




= Trouble shooting =
*  For missing or incorrect data:
Look at your .ics file.  Find the timezone and date specs for the problem event.  Check your server timezone.  Check the configuration.

*  For coders or modifiers:
If unexpected data is encountered, an HTML comment may be written.  The idea is that it flags the problem without making your website look dirty. So view your source to see if there is any info there. The problem may be in the source data, or it may be in the code.  
If a major problem is experienced, this may be written to the screen.
You can also set the debug flag to true - this will result in quite a lot of code written to the screen!
There is also commented out debug code.  You could check the problem area and switch on.
