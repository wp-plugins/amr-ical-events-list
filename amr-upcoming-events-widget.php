<?php
/*
Description: Display a sweet, concise list of events from iCal sources, using a list type from the amr iCal plugin <a href="options-general.php?page=manage_amr_ical">Manage Settings Page</a> and  <a href="widgets.php">Manage Widget</a> 

*/
class amr_ical_widget extends WP_widget {
    /** constructor */
    function amr_ical_widget() {
		$widget_ops = array ('description'=>__('Upcoming Events', 'amr-ical-events-list' ),'classname'=>__('events', 'amr-ical-events-list' ));
        $this->WP_Widget(false, __('AmR Upcoming Events', 'amr-ical-events-list' ), $widget_ops);	
    }
	
/* ============================================================================================== */	
	function widget ($args, $instance) { /* this is the piece that actualy does the widget display */
	global $amrW;
	global $amr_options;
	global $amr_limits;
	global $amr_listtype;
	global $widget_icalno; /* used to give each ical widget a unique id on a page */

	amr_getset_options ();
	$amr_listtype = '4';  /* default only */
	extract ($args, EXTR_SKIP); /* this is for the before / after widget etc*/
	extract ($instance, EXTR_SKIP); /* this is for the before / after widget etc*/		
	foreach ($amr_options[$amr_listtype]['limit'] as $i=> $l) $amr_limits[$i] = $l;  /* override any other limits with the widget limits */	
	if (isset ($shortcode_urls)) {
		$atts 		= shortcode_parse_atts($shortcode_urls);
		$criteria 	= amr_get_params ($atts);  /* this may update listtype, limits  etc */
	}
	else 
		echo('no url data for events widget');

	$amrW = 'w';	 /* to maintain consistency with previous version */
	if (isset($doeventsummarylink) and !($doeventsummarylink)) $amrW = 'w_no_url';
	$moreurl = trim($moreurl," ");
	$moreurl = (empty($moreurl)) ? null : $moreurl ;
	if (!empty ($moreurl)) $title = '<a href= "'.$moreurl.'">'.$title.'</a>';	
	if (!(isset($widget_icalno))) $widget_icalno = 0;
	else $widget_icalno= $widget_icalno + 1;
	$criteria['EventPostsToo'] = true;	
	$content = amr_process_icalspec($criteria, $widget_icalno);
	//output...
	echo $before_widget;
	echo $before_title . $title . $after_title ;
	echo $content;
	echo $after_widget; 

	}
/* ============================================================================================== */	
	
	function update($new_instance, $old_instance) {  /* this does the update / save */
		$instance = $old_instance;
		$instance['title'] = strip_tags($new_instance['title']);
		$instance['shortcode_urls'] = strip_tags($new_instance['shortcode_urls']);
		$instance['moreurl'] = 	strip_tags($new_instance['moreurl']);
		$instance['doeventsummarylink'] = 	strip_tags($new_instance['doeventsummarylink']);		
		if (get_option('amr-ical-widget') ) delete_option('amr-ical-widget'); /* if t exists - leave code for a while for conversion */
		return $instance;
	}
	
/* ============================================================================================== */
	function form($instance) { /* this does the display form */
	global $amrW;
	
        $instance = wp_parse_args( (array) $instance, array( 
			'title' => __('Upcoming Events','amr-ical-events-list') ,
			'shortcode_urls' => 'http://www.google.com/calendar/ical/0bajvp6gevochc6mtodvqcg9o0%40group.calendar.google.com/public/basic.ics',
			'moreurl' => '',
			'doeventsummarylink' => true
			) );
			
		$title = $instance['title'];	
		$moreurl = $instance['moreurl'];
		$shortcode_urls = $instance['shortcode_urls'];
		$doeventsummarylink = $instance['doeventsummarylink'];
			
		if ($opt = get_option('amr-ical-widget')) {  /* delete the old option in the save */	
			if (isset ($opt['urls']) ) $shortcode_urls = str_replace(',', ' ',$opt['urls']);  /* in case anyone had multiple urls separate by commas - change to spaces*/
			if (isset ($opt['moreurl']) ) $moreurl = $opt['moreurl'];
			if (isset ($opt['title']) ) $title = $opt['title'];
			if (isset ($opt['listtype'])  and (!($opt['listtype']===4))) $shortcode_urls = 'listtype='.$opt['listtype'].' '.$shortcode_urls;
			if (isset ($opt['limit']) and (!($opt['limit']==='5'))) $shortcode_urls = 'events='.$opt['limit'].' '.$shortcode_urls;
		}

?>
	<input type="hidden" id="submit" name="submit" value="1" />
	<p><label for="<?php echo $this->get_field_id('title'); ?>"><?php _e('Title', 'amr-ical-events-list'); ?> 
	<input class="widefat" id="<?php echo $this->get_field_id('title'); ?>" name="<?php echo $this->get_field_name('title'); ?>" type="text" 
	value="<?php echo attribute_escape($title); ?>" />		</label></p>

	<p><label for="<?php echo $this->get_field_id('moreurl'); ?>"><?php _e('Calendar page url in this website, for event title links', 'amr-ical-events-list'); ?> 
	<input id="<?php echo $this->get_field_id('moreurl'); ?>" name="<?php echo $this->get_field_name('moreurl'); ?>" type="text" style="width: 200px;" 
	value="<?php echo attribute_escape($moreurl); ?>" /></label></p>
	<p><label for="<?php echo $this->get_field_id('doeventsummarylink'); ?>"><?php 
	_e('Do an event summary hyperlink with event description as title text?', 'amr-ical-events-list'); ?> 
	<input id="<?php echo $this->get_field_id('doeventsummarylink'); ?>" name="<?php 
	echo $this->get_field_name('doeventsummarylink'); ?>" type="checkbox" 
	value="true" <?php if ($doeventsummarylink) echo 'checked="checked"';?> /></label></p>
	<p><label for="<?php echo $this->get_field_id('shortcode_urls');?>"><?php _e('Urls (plus optional shortcode parameters)', 'amr-ical-events-list'); ?> </label>
	<a href="http://icalevents.anmari.com" title="<?php _e('See plugin website','amr-ical-events-list'); ?>">?</a>
	<textarea cols="25" rows="10" id="<?php echo $this->get_field_id('shortcode_urls');?>" name="<?php echo $this->get_field_name('shortcode_urls'); ?>" ><?php
		echo attribute_escape($shortcode_urls); ?></textarea></p>
	
<?php }
/* ============================================================================================== */
}

?>