<?php
/*
Description: Display a sweet, concise list of events from iCal sources, using a list type from the amr iCal plugin <a href="options-general.php?page=manage_amr_ical">Manage Settings Page</a> and  <a href="widgets.php">Manage Widget</a>

*/

class amr_ical_widget extends WP_widget {
    /** constructor */

    function amr_ical_widget() {
		$widget_ops = array ('description'=>__('Upcoming Events', 'amr_ical_list_lang' ),'classname'=>__('events', 'amr_ical_list_lang' ));

        $this->WP_Widget(false, __('Upcoming Events List', 'amr_ical_list_lang' ), $widget_ops);

    }

/* ============================================================================================== */
	function widget ($args /* the title etc */, $instance /* the params */) { /* this is the piece that actualy does the widget display */
	global $amrW;
	global $amr_options;
	global $amr_limits;
	global $amr_listtype;
	global $amr_calendar_url;
	global $change_view_allowed;
	global $widget_icalno; /* used to give each ical widget a unique id on a page */
//
	amr_ical_load_text(); // do we have to reload all over theplace ?  wp does not always seem to have the translations
	$change_view_allowed = false;
	extract ($args, EXTR_SKIP); /* this is for the before / after widget etc*/
	unset($args);  //nb do not delete this else mucks up the args later
	extract ($instance, EXTR_SKIP); /* this is for the params etc*/
//	foreach ($amr_options[$amr_listtype]['limit'] as $i=> $l) $amr_limits[$i] = $l;  /* override any other limits with the widget limits */
	if (!empty ($shortcode_urls)) $args		= shortcode_parse_atts($shortcode_urls);
	else 	{
		if (ICAL_EVENTS_DEBUG) {echo '<br />No parameters passed <br/>'; }
		if (!isset($args['listtype'])) $args['listtype'] = $amr_listtype = '4';
		}
//
	if (!empty ($externalicalonly) and $externalicalonly) $args['eventpoststoo'] = false;
	else $args['eventpoststoo'] = true;

	$amrW = 'w';	 /* to maintain consistency with previous version and prevent certain actions */
	$criteria 	= amr_get_params ($args);  /* this may update listtype, limits  etc */
		
	if (isset ($criteria['event'])) unset ( $criteria['event']);  //later may need to check for other custo posttypes 
	if (ICAL_EVENTS_DEBUG) echo '<hr>ical list widget:'.$amr_listtype.' <br />'.amr_echo_parameters();

	if (isset($doeventsummarylink) and !($doeventsummarylink)) $amrW = 'w_no_url';
	$moreurl = trim($moreurl," ");

	$moreurl = (empty($moreurl)) ? null : $moreurl ;
	$amr_calendar_url = $moreurl;
	if (ICAL_EVENTS_DEBUG) echo 'Calendar url = '.$amr_calendar_url;
	if (isset($_REQUEST['lang'])) $moreurl = add_query_arg('lang',$_REQUEST['lang'],$moreurl);
	if (!empty ($moreurl)) $title = '<a href= "'.$moreurl.'">'.__($title,'amr_ical_list_lang') .'</a>';
	if (!(isset($widget_icalno))) $widget_icalno = 0;
	else $widget_icalno= $widget_icalno + 1;

	$content = amr_process_icalspec($criteria, $amr_limits['start'], $amr_limits['end'], $amr_limits['events'], $widget_icalno);
	//output...
	echo $before_widget;
	echo $before_title . $title . $after_title ;
	echo $content;
	echo $after_widget;

	}
/* ============================================================================================== */

	function update($new_instance, $old_instance) {  /* this does the update / save */
		$instance                      = $old_instance;

		$instance['title']             = strip_tags($new_instance['title']);
		$instance['moreurl']           = strip_tags($new_instance['moreurl']);
		$instance['moreurl'] 		   = amr_make_sticky_url($instance['moreurl'] );
		$instance['doeventsummarylink']= strip_tags($new_instance['doeventsummarylink']);
		$instance['externalicalonly']  = strip_tags($new_instance['externalicalonly']);
		$instance['shortcode_urls']    = strip_tags($new_instance['shortcode_urls']);
		if (get_option('amr-ical-widget') ) delete_option('amr-ical-widget'); /* if it exists - leave code for a while for conversion */

		return $instance;

	}

/* ============================================================================================== */

	function form($instance) { /* this does the display form */
	global $amrW;

        $instance = wp_parse_args( (array) $instance, array(
			'title' => __('Upcoming Events','amr_ical_list_lang') ,
			'moreurl' => '',
			'doeventsummarylink' => true,
			'externalicalonly'  => false,
			'shortcode_urls' => ''
			) );

		$title             = $instance['title'];
		$moreurl           = $instance['moreurl'];
		$doeventsummarylink= $instance['doeventsummarylink'];
		$externalicalonly  = $instance['externalicalonly'];
		$shortcode_urls    = $instance['shortcode_urls'];

		if ($opt = get_option('amr-ical-widget')) {  /* delete the old option in the save */
			if (isset ($opt['urls']) ) $shortcode_urls = str_replace(',', ' ',$opt['urls']);  /* in case anyone had multiple urls separate by commas - change to spaces*/
			if (isset ($opt['moreurl']) ) $moreurl = $opt['moreurl'];
			if (isset ($opt['title']) ) $title = $opt['title'];
			if (isset ($opt['listtype'])  and (!($opt['listtype']===4))) $shortcode_urls = 'listtype='.$opt['listtype'].' '.$shortcode_urls;
			if (isset ($opt['limit']) and (!($opt['limit']==='5'))) $shortcode_urls = 'events='.$opt['limit'].' '.$shortcode_urls;
		}

	$seemore = __('See plugin website for more details','amr_ical_list_lang');
 // <input type="hidden" name="submit" value="1" />?>
	<p>
	<label for="<?php echo $this->get_field_id('title'); ?>"><?php _e('Title', 'amr_ical_list_lang');
	?><input class="widefat" id="<?php echo $this->get_field_id('title'); ?>" name="<?php echo $this->get_field_name('title'); ?>" type="text"
	value="<?php echo attribute_escape($title); ?>" />		</label></p>

	<p>
	<label for="<?php echo $this->get_field_id('moreurl'); ?>"><b><?php
	_e('Calendar page url', 'amr_ical_list_lang'); ?></b><br /><em>
	<?php _e('Calendar page url in this website, for event title links', 'amr_ical_list_lang');
	?></em> <a href="http://icalevents.anmari.com/1901-widgets-calendar-pages-and-event-urls/" title="<?php echo $seemore; ?>"><b>?</b></a>
	<input id="<?php echo $this->get_field_id('moreurl'); ?>" name="<?php echo $this->get_field_name('moreurl'); ?>" type="text" style="width: 200px;"
	value="<?php echo attribute_escape($moreurl); ?>" /></label></p>
	<p>
	<label for="<?php echo $this->get_field_id('doeventsummarylink'); ?>"><b><?php
	_e('Hover description on Title', 'amr_ical_list_lang'); ?></b><br /><em><?php
	_e('Do an event summary hyperlink with event description as title text ', 'amr_ical_list_lang');
	?></em> <a href="http://icalevents.anmari.com/1908-hovers-lightboxes-or-clever-css/" title="<?php echo $seemore; ?>"><b>?</b></a>
	<input id="<?php echo $this->get_field_id('doeventsummarylink'); ?>" name="<?php
	echo $this->get_field_name('doeventsummarylink'); ?>" type="checkbox"
	value="true" <?php if ($doeventsummarylink) echo 'checked="checked"';?> /></label></p>
	<p>
	<label for="<?php echo $this->get_field_id('externalicalonly'); ?>"><b><?php
	_e('External events only', 'amr_ical_list_lang'); ?></b><br /><em><?php
	_e('Show events from external ics only, do NOT pickup any internal events.', 'amr_ical_list_lang');
	?></em><a href="http://icalevents.anmari.com" title="<?php _e('Else include events created internally too','amr_ical_list_lang'); ?>"><b>?</b></a>
	<input id="<?php echo $this->get_field_id('externalicalonly'); ?>" name="<?php
	echo $this->get_field_name('externalicalonly'); ?>" type="checkbox"
	value="true" <?php if ($externalicalonly) echo 'checked="checked"';?> /></label></p>
	<p>
	<label for="<?php echo $this->get_field_id('shortcode_urls');?>"><b><?php
	_e('External ics urls and advanced options', 'amr_ical_list_lang'); ?></b><br /><em><?php
	_e('External ics urls and/or optional shortcode parameters separated by spaces.)', 'amr_ical_list_lang'); echo '<br />';
	_e(' Examples: listtype=4 events=10 days=60 start=yyyymmdd startoffset=-2... )', 'amr_ical_list_lang');
	?></em> </label>
	<a href="http://icalevents.anmari.com/amr-ical-events-list/#shortcode" title="<?php __('See more parameters','amr_ical_list_lang'); ?>"><b>?</b></a>
	<textarea cols="25" rows="10" id="<?php echo $this->get_field_id('shortcode_urls');?>" name="<?php echo $this->get_field_name('shortcode_urls'); ?>" ><?php

		echo attribute_escape($shortcode_urls); ?></textarea></p>

<?php }
/* ============================================================================================== */

}
class amr_icalendar_widget extends WP_widget {
    /** constructor */

    function amr_icalendar_widget() {
		$widget_ops = array ('description'=>__('Upcoming Events', 'amr_ical_list_lang' ),
		'classname'=>__('icalendar', 'amr_ical_list_lang' ));

        $this->WP_Widget(false, __('Upcoming Events Calendar', 'amr_ical_list_lang' ), $widget_ops);

    }

/* ============================================================================================== */
	function widget ($args, $instance) { /* this is the piece that actualy does the widget display */
	global $amrW;
	global $amr_options;
	global $amr_limits;
	global $amr_listtype;
	global $change_view_allowed;
	global $widget_icalno; /* used to give each ical widget a unique id on a page */
	global $amr_calendar_url;

	amr_ical_load_text(); // do we have to reload all over theplace ?  wp does not always seem to have the translations
	
	$change_view_allowed = false;
//	$amr_listtype = '8';  /* default only, can be overwitten in shortcode or query string  */
	extract ($args, EXTR_SKIP); /* this is for the before / after widget etc*/
	extract ($instance, EXTR_SKIP); /* this is for the before / after widget etc*/
	if (isset ($moreurl) ) $moreurl = trim($moreurl," ");

	$amr_calendar_url = (empty($moreurl)) ? null : $moreurl ;
	if (ICAL_EVENTS_DEBUG) echo 'Calendar url = '.$amr_calendar_url;
	if (!empty ($shortcode_urls)) $atts 		= shortcode_parse_atts($shortcode_urls);
	if (!empty ($externalicalonly) and $externalicalonly) $atts['eventpoststoo'] = false;
	else $atts['eventpoststoo'] = true;
//
	if (!(isset($widget_icalno))) $widget_icalno = 0;
	else $widget_icalno= $widget_icalno + 1;
	$amrW = 'w';	 /* to maintain consistency with previous version */
//
	if (!isset($atts['listtype'])) $atts['listtype'] = $amr_listtype = '8';
	if (!isset($atts['months'])) $atts['months'] = 1;
	//
	$criteria 				= amr_get_params ($atts);  /* this may update listtype, limits  etc */
	if (isset ($criteria['event'])) unset ( $criteria['event']);  //later may need to check for other custo posttypes 
	//
	if (isset($_GET['debug'])) echo '<hr>calen widget listtype :'.$amr_listtype.' '.amr_echo_parameters();
//      overrwite since we always want a month
	if (isset($_GET['debug'])) echo '<br/> Overwriting with limits! ';
	$amr_limits['end'] = clone $amr_limits['start'];
	date_modify ($amr_limits['end'], '+1 month');
	if (!isset($amr_limits['months'])) $amr_limits['months'] = 1;
	unset ($amr_limits['days']);

	if (isset($_GET['debug'])) echo '<hr>calen widget:'.$amr_listtype.' '.amr_echo_parameters();

	$content 	= amr_process_icalspec($criteria,$amr_limits['start'], $amr_limits['end'], $amr_limits['events'], $widget_icalno);

	if (isset($_GET['debuglang'])) echo '<br />Widget title = '.$title.' ='.__($title,'amr_ical_list_lang'). ' '.__('Upcoming Events','amr_ical_list_lang');
	//output...
	echo $before_widget;
	echo $before_title . __($title,'amr_ical_list_lang') . $after_title;
	echo $content;
	echo $after_widget;
	if (isset ($savedays)) $amr_limits['days'] = $savedays;
	}
/* ============================================================================================== */
	function update($new_instance, $old_instance) {  /* this does the update / save */
		$instance                      = $old_instance;

		$instance['title']             = strip_tags($new_instance['title']);
		if (!empty($instance['externalicalonly'])) 
			$instance['externalicalonly']  = strip_tags($new_instance['externalicalonly']);
		
		$instance['shortcode_urls']    = strip_tags($new_instance['shortcode_urls']);
		$instance['moreurl']		   = strip_tags($new_instance['moreurl']);
		$instance['moreurl'] 		   = amr_make_sticky_url($instance['moreurl'] );

		return $instance;

	}
/* ============================================================================================== */

	function form($instance) { /* this does the display form */

        $instance = wp_parse_args( (array) $instance, array(
			'title' => __('Upcoming Events','amr_ical_list_lang'),
			'externalicalonly'  => false,
			'moreurl' 			=> '',
			'shortcode_urls' => 'http://www.google.com/calendar/ical/0bajvp6gevochc6mtodvqcg9o0%40group.calendar.google.com/public/basic.ics'
			) );
		$title             = $instance['title'];
		$moreurl           = $instance['moreurl'];
		$externalicalonly  = $instance['externalicalonly'];
		$shortcode_urls    = $instance['shortcode_urls'];
		$seemore =  __('See more','amr_ical_list_lang'); 
	?><p>
	<label for="<?php echo $this->get_field_id('title'); ?>"><?php _e('Title', 'amr_ical_list_lang');
	?><input class="widefat" id="<?php
	echo $this->get_field_id('title'); ?>" name="<?php echo $this->get_field_name('title'); ?>" type="text"
	value="<?php echo attribute_escape($title); ?>" /></label></p>
		<p>
	<label for="<?php echo $this->get_field_id('moreurl'); ?>"><b><?php
	_e('Calendar page url', 'amr_ical_list_lang'); ?></b><br /><em>
	<?php _e('Calendar page url in this website, for links from widget', 'amr_ical_list_lang');
	?></em> <a href="http://icalevents.anmari.com/1901-widgets-calendar-pages-and-event-urls/" title="<?php echo $seemore; ?>"><b>?</b></a>
	<input id="<?php echo $this->get_field_id('moreurl'); ?>" name="<?php echo $this->get_field_name('moreurl'); ?>" type="text" style="width: 200px;"
	value="<?php echo attribute_escape($moreurl); ?>" /></label></p>
		<p>
	<label for="<?php echo $this->get_field_id('externalicalonly'); ?>"><b><?php
	_e('External events only', 'amr_ical_list_lang'); ?></b><br /><em><?php
	_e('Show events from external ics only, do NOT pickup any internal events.', 'amr_ical_list_lang');
	?></em><a href="http://icalevents.anmari.com" title="<?php _e('Else include events created internally too','amr_ical_list_lang'); ?>"><b>?</b></a>
	<input id="<?php echo $this->get_field_id('externalicalonly'); ?>" name="<?php
	echo $this->get_field_name('externalicalonly'); ?>" type="checkbox"
	value="true" <?php if ($externalicalonly) echo 'checked="checked"';?> /></label></p>
	<p>
	<label for="<?php echo $this->get_field_id('shortcode_urls');?>"><b><?php
	_e('External ics urls and advanced options', 'amr_ical_list_lang'); ?></b><br /><em><?php
	_e('External ics urls and/or optional shortcode parameters separated by spaces.)', 'amr_ical_list_lang'); echo '<br />';
	_e(' Examples: listtype=8 events=10 days=60 start=yymmdd startoffset=-2... )', 'amr_ical_list_lang');
	?></em> </label>
	<a href="http://icalevents.anmari.com/amr-ical-events-list/#shortcode" title="<?php echo $seemore; ?>"><b>?</b></a>
	<textarea cols="25" rows="10" id="<?php echo $this->get_field_id('shortcode_urls');?>" name="<?php echo $this->get_field_name('shortcode_urls'); ?>" ><?php

		echo attribute_escape($shortcode_urls); ?></textarea></p>


<?php }
/* ============================================================================================== */

}


?>