<?php
//ini_set('display_errors', 1);
//error_reporting(E_ALL);
/*
Description: Display a sweet, concise list of events from iCal sources, using a list type from the amr iCal plugin <a href="options-general.php?page=manage_amr_ical">Manage Settings Page</a> and  <a href="widgets.php">Manage Widget</a> 

*/

/* ============================================================================================== */
function amr_ical_list_widget($args)

{	
	global $amrW;
	global $amr_options;
	global $amrwidget_options;
	global $amr_listtype;
	global $amr_ical_widgetlimit;  /* we are using same variable for widget and page, but it gets picked up from the options table as needed, so should be ok */
	$amrW = 'w';
	
	extract($args);

	if (!isset($amr_options)) $amr_options = amr_getset_options(false); /* in case we have not fetched already */
	$amrwidget_options = amr_getset_widgetoptions();

	if (ICAL_EVENTS_DEBUG) {echo '<br> Widget options from DB:'; var_dump($amrwidget_options);}

	$title = (empty($amrwidget_options["title"])) ? null : $amrwidget_options["title"];
	$urls  = (empty($amrwidget_options["urls"])) ? null : $amrwidget_options["urls"];
	$amr_listtype  = (empty($amrwidget_options["listtype"])) ? null : $amrwidget_options["listtype"];
	$amr_ical_widgetlimit = (empty($amrwidget_options["limit"])) ? 5 :$amrwidget_options['limit'];
	$moreurl = (empty($amrwidget_options['moreurl'])) ? null : $amrwidget_options['moreurl'] ;
	if (!isset ($title)) $title = __('Calendar link');
	if (isset ($moreurl)) $title = '<a href= "'.$moreurl.'">'.$title.'</a>';
	
	$content = '[iCal:'.$urls.';listtype='.$amr_listtype.']';
	$content = amr_replaceURLs($content) ;
	//output...
	echo $before_widget;
	echo $before_title . $title . $after_title ;
	echo $content;
	echo $after_widget; 
}
/* -------------------------------------------------------------------------------------------------------------*/
function amr_ical_widget_init()
{
    register_sidebar_widget("AmR iCal Widget", "amr_ical_list_widget");
    register_widget_control("AmR iCal Widget", "amr_ical_list_widget_control");
}
/* ------------------------------------------------------------------------------------------------------ */
	function amr_getset_widgetoptions ($reset=false)
	/* get the options from wordpress if in wordpress
	if no options, then set defaults */
	{		
		if ($reset)	/* The we are requested to reset the options */
		{ 	if (function_exists ('delete_option'))  	
				{	delete_option("AmRiCalWidget");	}; 
		};

		if (function_exists ('get_option') && ($amrwidget_options = get_option('AmRiCalWidget')))
		{	}
		else 
			{	$amrwidget_options = amrwidget_defaults();	}		
		return ($amrwidget_options);
	}

/* -------------------------------------------------------------------------------------------*/

//	if ( !defined('WP_CONTENT_DIR') )	define( 'WP_CONTENT_DIR', ABSPATH . 'wp-content' );
//	define('AMRICAL_ABSPATH', WP_CONTENT_DIR.'/plugins/' . dirname(plugin_basename(__FILE__)) . '/');
//	add_action('plugins_loaded', 'amr_ical_widget_init');	
