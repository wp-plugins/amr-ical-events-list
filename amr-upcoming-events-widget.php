<?php
//ini_set('display_errors', 1);
//error_reporting(E_ALL);
/*
Description: Display a sweet, concise list of events from iCal sources, using a list type from the amr iCal plugin <a href="options-general.php?page=manage_amr_ical">Manage Settings Page</a> and  <a href="widgets.php">Manage Widget</a> 

*/

/* ============================================================================================== */
function amr_ical_list_widget($args)  {	
	global $amrW;
	global $amr_options;
	global $amr_limits;
	global $amrwidget_options;
	global $amr_listtype;
//	global $amr_ical_widgetlimit;  /* we are using same variable for widget and page, but it gets picked up from the options table as needed, so should be ok */
	$amrW = 'w';

	extract( $args );

	$urls =	amr_get_params (); 
	$amrwidget_options = amr_getset_widgetoptions();
	$title = (empty($amrwidget_options["title"])) ? null : $amrwidget_options["title"];
	$urls[]  = (empty($amrwidget_options["urls"])) ? null : $amrwidget_options["urls"];
	$amr_listtype  = (empty($amrwidget_options["listtype"])) ? null : $amrwidget_options["listtype"];
	$amr_limits['events'] = (empty($amrwidget_options["limit"])) ? 5 :$amrwidget_options['limit'];
	$moreurl = (empty($amrwidget_options['moreurl'])) ? null : $amrwidget_options['moreurl'] ;
	if (isset ($moreurl)) $title = '<a href= "'.$moreurl.'">'.$title.'</a>';
	
	$content = process_icalspec($urls, '0');
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
//		if ($reset)	{ 	if (function_exists ('delete_option')) 	delete_option("AmRiCalWidget");	}  /* Don't reset the widget - too confusing */
		if (!(function_exists ('get_option') && ($amrwidget_options = get_option('AmRiCalWidget'))))
			$amrwidget_options = amrwidget_defaults();			
		return ($amrwidget_options);
	}

/* -------------------------------------------------------------------------------------------*/
