<?php
/* This is the amr ical wordpress admin section file */
	/**
	 * Create a Dropdown input field
	 */
if (!function_exists('amr_simpledropdown')) { 
	function amr_simpledropdown($name, $options, $selected) {
//			
		$html = '<select name=\''.$name.'\'>';
		foreach ($options as $i => $option) {
//				
			$sel = selected($i, $selected, false); //wordpress function returns with single quotes, not double 
			$html .= '<OPTION '.$sel.' label=\''.$option.'\' value=\''.$i.'\'>'.$option.'</OPTION>';
		}
		$html .= '</select>';
		return ($html);
	}
}	
//		
	function amr_allowed_html () {
//	return ('<p><br /><hr /><h2><h3><<h4><h5><h6><strong><em>');
	return (array(
		'br' => array(),
		'em' => array(),
		'span' => array(),
		'h1' => array(),
		'h2' => array(),
		'h3' => array(),
		'h4' => array(),
		'h5' => array(),
		'h6' => array(),
		'strong' => array(),
		'p' => array(),
		'abbr' => array(
		'title' => array ()),
		'acronym' => array(
			'title' => array ()),
		'b' => array(),
		'blockquote' => array(
			'cite' => array ()),
		'cite' => array (),
		'code' => array(),
		'del' => array(
			'datetime' => array ()),
		'em' => array (), 'i' => array (),
		'q' => array(
			'cite' => array ()),
		'strike' => array(),
		'div' => array()

		)); 
	}	
	//build admin interface =======================================================
	function amr_ical_validate_general_options(){	
		global 
		$amr_options,
		$amr_calprop,
		$amr_limits,
		$amr_compprop,
		$amr_groupings,
		$amr_components;

		$nonce = $_REQUEST['_wpnonce'];
		if (! wp_verify_nonce($nonce, 'amr_ical_list_lang')) die ("Cancelled due to failed security check");

			if (isset($_POST['ngiyabonga'])) 	$amr_options['ngiyabonga'] =  true;							
			else 	$amr_options['ngiyabonga'] =  false;
			if (isset($_POST['noeventsmessage'])) 	$amr_options['noeventsmessage'] =  $_POST['noeventsmessage'];
			if (isset($_POST["own_css"])) $amr_options['own_css'] =  true;							
			else $amr_options['own_css'] =  false;			
			if ((isset($_POST["date_localise"])) and (in_array($_POST["date_localise"], array('none', 'wp', 'wpgmt', 'amr')) )) $amr_options['date_localise'] =  $_POST["date_localise"];		/* from dropdown */					
			else $amr_options['date_localise'] =  'none';			
			if (isset($_POST["cssfile"])) $amr_options['cssfile'] =  $_POST["cssfile"];		/* from dropdown */					
			else $amr_options['cssfile'] =  '';	
			if (isset($_POST["no_images"]))  $amr_options['no_images'] =  true;		/* from dropdown */					
			else $amr_options['no_images'] =  false;				
			/* check if no types updated, do not process other stuff if it has been  */		
			if (isset($_POST["no_types"]) && (!($_POST["no_types"]== $amr_options['no_types']))){		
				if (function_exists( 'filter_var') ){
					$int_ok = (filter_var($_POST["no_types"], FILTER_VALIDATE_INT, 
						array("options" => array("min_range"=>1, "max_range"=>10))));
				}
				else $int_ok = 	(is_numeric($_POST["no_types"]) ? (int) $_POST["no_types"] : false);
				if ($int_ok) {
					for ($i = $amr_options['no_types']+1; $i <= $int_ok; $i++)  {	
						$amr_options[$i] = new_listtype();
						$amr_options[$i] = customise_listtype($i);
					}
					$amr_options['no_types'] =  $int_ok;							
				}
				else { _e('Invalid Number of Lists', 'amr_ical_list_lang'); return(false);
				}
			}
			update_option( 'amr-ical-events-list', $amr_options);
			return(true);	
	}
/* ---------------------------------------------------------------------- */	
	function amr_ical_validate_list_options($i)	{
	global $amr_options;
	if (isset($_POST['general']))  {
		
				if (is_array($_POST['general'][$i])) 
				{	foreach ($_POST['general'][$i] as $c => $v)
					{   
						if (isset($_POST['general'][$i][$c])) {  
								$amr_options[$i]['general'][$c] = $_POST['general'][$i][$c] ;
								}
						else	$amr_options[$i]['general'][$c] = '';
					}
				}
				else echo 'Error in form - general array not found';
			}
			
	if (isset($_POST['limit']))  
			{	if (is_array($_POST['limit'][$i])) 
				{	foreach ($_POST['limit'][$i] as $c => $v)
					{ 
						$amr_options[$i]['limit'][$c] = 
							(isset($_POST['limit'][$i][$c])) ? $_POST['limit'][$i][$c] :11;
					}
				}
				else echo 'Error in form - limit array not found';
			}
	if (isset($_POST['format']))  
			{	if (is_array($_POST['format'][$i])) 
				{	foreach ($_POST['format'][$i] as $c => $v)
					{   /* amr - how should we validate this ?  accepting any input for now */ 
						$amr_options[$i]['format'][$c] = 
							(isset($_POST['format'][$i][$c])) ? stripslashes_deep($_POST['format'][$i][$c]) :'';
					}
				}
				else echo 'Error in form - format array not found';
			}	
			
	foreach ($amr_options[$i]['component'] as $k => $c) {
					if (isset($_POST['component'][$i][$k])) {
						$amr_options[$i]['component'][$k] =  true;						
					}
					else {
						$amr_options[$i]['component'][$k] =  false;	
					}
				}				
	foreach ($amr_options[$i]['grouping'] as $k => $c) {
					if (isset($_POST['grouping'][$i][$k])) {
						$amr_options[$i]['grouping'][$k] =  true;						
					}
					else {
						$amr_options[$i]['grouping'][$k] =  false;	
					}
				}		
	if (isset($_POST['ColH']))  
				{	if (is_array($_POST['ColH'][$i])) {	
						foreach ($_POST['ColH'][$i] as $c => $v) { 
							$amr_options[$i]['heading'][$c] = $v;
						}
					}
					// else echo 'Error in form - grouping array not found';   /* May not want any groupings ?
				}	
	if (isset($_POST['CalP'])) { 	
		if (is_array($_POST['CalP'][$i])) {	
			foreach ($_POST['CalP'][$i] as $c => $v) {
			   if (is_array($v)) 
				foreach ($v as $p => $pv){  								
					/*need to validate these */
					switch ($p):
					case 'Column': 
						if (function_exists( 'filter_var') )
						{	if (filter_var($pv, FILTER_VALIDATE_INT, 
							array("options" => array("min_range"=>0, "max_range"=>20))))
							$amr_options[$i]['calprop'][$c][$p]= $pv;
							else 	$amr_options[$i]['calprop'][$c][$p]= 0;
						}
						else $amr_options[$i]['calprop'][$c][$p]= $pv;
						break;
														
					case 'Order':
						if (function_exists( 'filter_var') )
						{	if (filter_var($pv, FILTER_VALIDATE_INT, 
							array("options" => array("min_range"=>0, "max_range"=>99))))
							$amr_options[$i]['calprop'][$c][$p] = $pv;break;
						}
						else $amr_options[$i]['calprop'][$c][$p] = $pv;break;
					case 'Before': $amr_options[$i]['calprop'][$c][$p] = wp_kses($pv, amr_allowed_html());
						break;
					case 'After': $amr_options[$i]['calprop'][$c][$p] = wp_kses($pv, amr_allowed_html());
						break;
					endswitch;
				}
			}
		}
		else _e('Error in form - calprop array not found', 'amr_ical_list_lang');
			
	}

	if (isset($_POST['ComP']))  {	
		if (is_array($_POST['ComP'][$i])) {	
			foreach ($_POST['ComP'][$i] as $si => $sv) { /* eg si = descriptve */
				foreach ($sv as $c => $v)  {/* eg c= summary */
					if (is_array($v)) 
					foreach ($v as $p => $pv)	{  								
						/*need to validate these */
						switch ($p):
						case 'Column': 
							if (function_exists( 'filter_var') )
							{	if (filter_var($pv, FILTER_VALIDATE_INT, 
								array("options" => array("min_range"=>0, "max_range"=>20))))
								$amr_options[$i]['compprop'][$si][$c][$p]= $pv;
								else 	$amr_options[$i]['compprop'][$si][$c][$p]= 0;
								break;
							}
							else $amr_options[$i]['compprop'][$si][$c][$p]= $pv;
							break;
						case 'Order':
							if (function_exists( 'filter_var') )
							{	if (filter_var($pv, FILTER_VALIDATE_INT, 
								array("options" => array("min_range"=>0, "max_range"=>99))))
								$amr_options[$i]['compprop'][$si][$c][$p] = $pv; 
								else 	$amr_options[$i]['compprop'][$si][$c][$p]= 0;
								break;
							}
							else $amr_options[$i]['compprop'][$si][$c][$p] = $pv; 
							break;
						case 'Before': $amr_options[$i]['compprop'][$si][$c][$p] = wp_kses($pv, amr_allowed_html());
							break;
						case 'After': $amr_options[$i]['compprop'][$si][$c][$p] = wp_kses($pv, amr_allowed_html());
							break;
						endswitch;
					}
				}
			}
		}
		else echo 'Error in form - compprop array not found';				
	}	
	$result = update_option( 'amr-ical-events-list', $amr_options);		
	return($result);
}
	/* ---------------------------------------------------------------------*/
	function AmRIcal_general ($i) {
	global $amr_options;
 ?><fieldset  id="general<?php echo $i; ?>" class="general" >
	<h4><?php _e('General:', 'amr_ical_list_lang'); ?></h4> 
	<div><?php
	if (! isset($amr_options[$i]['general'])) echo 'No general specifications set';
	else {	
		if (isset($amr_options[$i]['general']['ListHTMLStyle'])) $style = $amr_options[$i]['general']['ListHTMLStyle'];
		else $style = '';
	?><label for="name" ><?php _e('Name','amr_ical_list_lang'); ?></label>
		<input type="text" class="wide" size="20" id="name" name="general[<?php echo $i; ?>][name]" value="<?php
		if (isset($amr_options[$i]['general']['name'])) echo $amr_options[$i]['general']['name']; ?>" />
	<label for="description" ><?php _e('Internal Description','amr_ical_list_lang'); ?></label><br />
		<textarea cols="60" rows="6" id="description" name="general[<?php echo $i; ?>][Description]"><?php
		if (isset($amr_options[$i]['general']['Description'])) echo $amr_options[$i]['general']['Description']; ?></textarea><br />
	<label for="ListHTMLStyle" ><?php _e('List HTML Style','amr_ical_list_lang'); ?></label>
		<select id="ListHTMLStyle" name="general[<?php echo $i; ?>][ListHTMLStyle]">
			<option value="table" <?php if ($style==='table') echo 'selected="selected" '; ?>><?php _e('Table', 'amr_ical_list_lang'); ?></option>
			<option value="list" <?php if ($style==='list') echo 'selected="selected" '; ?>><?php _e('Lists for rows', 'amr_ical_list_lang'); ?></option>
			<option value="breaks" <?php if ($style==='breaks') echo 'selected="selected" '; ?>><?php _e('Breaks for rows!', 'amr_ical_list_lang'); ?></option>
			<option value="smallcalendar" <?php if ($style==='smallcalendar') echo 'selected="selected" '; ?>><?php _e('Small box calendar', 'amr_ical_list_lang'); ?></option>
			<option value="largecalendar" <?php if ($style==='largecalendar') echo 'selected="selected" '; ?>><?php _e('Large box calendar', 'amr_ical_list_lang'); ?></option>
			<option value="tableoriginal" <?php if ($style==='tableoriginal') echo 'selected="selected" '; ?>><?php _e('Table with lists in cells (original)', 'amr_ical_list_lang'); ?></option>
		</select><br />	<br />
	<label for="defaulturl" ><?php _e('Default Event URL','amr_ical_list_lang'); ?></label>
		<input type="text" class="wide" size="20" id="defaulturl" name="general[<?php echo $i; ?>][Default Event URL]" value="<?php
				if (isset($amr_options[$i]['general']['Default Event URL'])) echo $amr_options[$i]['general']['Default Event URL']; ?>" />				
<?php }
		echo "\n\t".'</div></fieldset>';
	return ;	
	}
	/* ---------------------------------------------------------------------*/	
	function AmRIcal_limits($i) {
	global $amr_options;			
		?><fieldset class="limits" ><h4 class="trigger"><a href="#" ><?php _e('Define maximums:', 'amr_ical_list_lang'); ?></a></h4> 		
		<div class="toggle_container">
		<p><em><?php _e('Note cache times are in hours','amr_ical_list_lang'); ?></em></p><?php
		if (! isset($amr_options[$i]['limit'])) echo 'No default limits set';
		else {
			foreach ( $amr_options[$i]['limit'] as $c => $v )					
			{					
				echo '<label for="L'.$i.$c.'" >'.__($c,'amr_ical_list_lang').'</label>';
				echo '<input type="text" size="2" id="L'.$i.$c.'"  name="limit['.$i.']['.$c.']"';
				echo ' value="'.$v.'" />'; 
			} 
		} 
		echo "\n\t".'</div></fieldset>';
	return ;	
	}
	/* ---------------------------------------------------------------------*/	
	function AmRIcal_componentsoption($i) {
	global $amr_options;	
	?><fieldset id="components<?php echo $i; ?>" class="components" >		
	<h4 class="trigger"><a href="#" ><?php _e('Select components to show:', 'amr_ical_list_lang'); 
	?></a>&nbsp;<a title="<?php _e('Wikipedia entry describing components', 'amr_ical_list_lang'); ?>" 
	href="http://en.wikipedia.org/wiki/ICalendar#Events_.28VEVENT.29">?</a></h4> 
	<div class="toggle_container"><?php
		if (! isset($amr_options[$i]['component'])) echo 'No default components set';
		else
		{	foreach ( $amr_options[$i]['component'] as $c => $v )					
			{					
				echo '<label for="C'.$i.$c.'" >';
				echo '<input type="checkbox" id="C'.$i.$c.'" name="component['.$i.']['.$c.']"';
				echo ($v ? ' checked="checked" />' : '/>');
				echo $c.'</label>';
			} 
		} 
		echo "\n\t".'</div></fieldset>';
	return ;	
	}
	/* ---------------------------------------------------------------------*/	
	function AmRIcal_groupingsoption($i) {
		global $amr_options;
	
		?><fieldset class="icalgroupings">
		<h4 class="trigger"><a href="#" ><?php _e('Define grouping:', 'amr_ical_list_lang');?></a></h4>
		<div class="toggle_container"><?php 
			foreach ( $amr_options[$i]['grouping'] as $c => $v )					
			{	$l = 'G'.$i.str_replace(' ','', $c);
				echo '<label for="'.$l.'"  >';
				echo '<input type="checkbox" id="'.$l.'" name="grouping['.$i.']['.$c.']"'. ($v ? ' checked="checked"' : '').' />';
				echo $c.' </label><br />';
			}
		echo "\n\t".'</div></fieldset> <!-- end of grouping -->';
	return;	
	}
	/* ---------------------------------------------------------------------*/	
	function AmRIcal_calpropsoption($i) {
	global $amr_options;	
	global $amr_csize;
		?><fieldset id="calprop" class="props">
		<h4 class="trigger"><a href="#"><?php _e('Calendar properties' , 'amr_ical_list_lang'); ?></a></h4>
		<div class="toggle_container">
		<?php
		//echo col_headings(); 
		foreach ( $amr_options[$i]['calprop'] as $c => $v )					
		{ 	
			echo "\n\t\t".'<fieldset class="layout"><legend>'.$c.'</legend>';
			foreach ( $v as $si => $sv )  /* for each specification */
			{	echo '<label class="'.$si.'" for="CalP'.$si.$i.$c.'" >'.$si.'</label>'
					.'<input type="text" size="'.$amr_csize[$si].'"  class="'.$si.'"  id="CalP'.$si.$i.$c
					.'"  name="'.'CalP['.$i.']['.$c.']['.$si.']"  value= "'.htmlspecialchars($sv).'"  />'; 
			}
			echo "\n\t\t".'</fieldset>';
		}	
		echo "\n\t".'</div></fieldset>';
		return;	
	}
	/* ---------------------------------------------------------------------*/
	function AmRIcal_compropsoption($i) {
	global $amr_options;	
	global $amr_csize;
		?><fieldset id="comprop" class="props" >
		<h4 class="trigger"><a href="#"><?php _e('Specify fields to show:' , 'amr_ical_list_lang'); ?></a></h4>
		
		<div class="toggle_container">
<p><em><?php _e('Note: a 0 (zero) in column = do not show that field.', 'amr_ical_list_lang'); ?></em> <a title="<?php _e('Link to more information', 'amr_ical', 'amr_ical_list_lang'); ?>" href="http://icalevents.anmari.com/list-types/"><?php _e('More information', 'amr_ical', 'amr_ical_list_lang'); ?></a></p><?php
		?><p><em><?php _e('Uppercase fields are those defined in the iCal specification.', 'amr_ical_list_lang');?></em></p><p><em><?php
		_e('Lowercase fields are additional fields added by this plugin and derived from the iCal fields for your convenience.' , 'amr_ical_list_lang'); 
		_e('Fields show if "column" > 0 and if there is data available in your event or ics file.', 'amr_ical_list_lang');
		?></em></p><?php
		foreach ( $amr_options[$i]['compprop'] as $si => $section )	{ /* s= descriptive */
		?><fieldset class="section"><h4 class="trigger">&nbsp;&nbsp;<a href="#"><?php _e($si,'amr_ical_list_lang'); ?></a></h4>
		<div class="toggle_container"><?php
			foreach ( $section as $p => $pv )  /* for each specification, eg: p= SUMMARY  */
			{
				echo "\n\t\t".'<fieldset class="layout"><legend>'.$p.'</legend>';
				foreach ( $pv as $s => $sv )  /* for each specification eg  $s = column*/ 
				{	echo '<label class="'.$s.'" for="'.$p.$s.$i.'"  >'.$s.'</label>'
						.'<input type="text" size="'.$amr_csize[$s].'"  class="'.$s.'"  id="'.$p.$s.$i
						.'"  name="'.'ComP['.$i.']['.$si.']['.$p.']['.$s.']"  value= "'.htmlspecialchars($sv).'"  />'; 
				}
				echo "\n\t\t".'</fieldset> <!-- end of layout -->';
			}
			echo "\n\t".'</div></fieldset> <!-- end of section -->';
		}	
		echo "\n".'</div></fieldset>  <!-- end of compprop -->';
		return;	
	}	
	
	/* ---------------------------------------------------------------------*/

	function AmRIcal_col_headings($i) {
	/* for component properties only */
	global $amr_options;	
	global $amr_csize;
		?><fieldset class="section">
		<h4 class="trigger"><a href="#" ><?php _e('Column Headings:','amr_ical_list_lang');?></a></h4>
		<div class="toggle_container"><?php
		$j = 0;
		while ($j < 8) {
			$j = $j + 1;
			if (isset ( $amr_options[$i]['heading'][$j] )) {
				$h = $amr_options[$i]['heading'][$j];
			}
			else $h = '';

			echo '<label class="colhead" for="h'.$i.'-'.$j.'" >'
				.'<input type="text" size="'.$amr_csize['ColHeading'].'"  class="colhead"  id="h'.$i.'-'.$j
				.'"  name="ColH['.$i.']['.$j.']"  value= "'.htmlspecialchars($h).'"  />'
				.$j.'</label>'; 
		}	
		echo "\n\t".'</div></fieldset>';
		return;	
	}
/* ---------------------------------------------------------------------*/

function amr_request_acknowledgement () {
?><div class="postbox" style="padding:1em 2em; width: 600px;">
	<p style="border-width: 1px;"><?php _e('I try to make these plugins work <strong>"out of the box"</strong> with minimal effort; that they be easy to use but <strong>very configurable</strong>; <strong>well tested</strong>; with <strong>valid html and css</strong> both at the front and admin area.','amr_ical_list_lang'); 
_e('If you have a feature request, please do let me know. ','amr_ical_list_lang'); 	
?></p><p><b><?php _e('To edit events in wordpress:','amr_ical_list_lang'); ?> <a href="http://icalevents.anmari.com" >icalevents.anmari.com</a><?php
	?></b></p><p>
	<a href="http://icalevents.anmari.com" title="Sign up or monitor the feed for regular updates"><?php _e('Documentation', 'amr_ical_list_lang');?></a>
	&nbsp;&nbsp;
	<a href="http://forum.anmari.com" title="Support Forum"><?php _e('Support', 'amr_ical_list_lang');?></a>
	&nbsp;&nbsp;
	<a href='http://wordpress.org/tags/amr-ical-events-list' title="If you like it rate it..."><?php _e('Rate it at WP', 'amr_ical_list_lang');?></a>
	&nbsp;&nbsp;
	<a href="http://icalevents.anmari.com/feed/"><?php _e('Plugin feed', 'amr_ical_list_lang');?></a><img src="http://icalevents.anmari.com/images/amrical-rss.png" alt="Rss icon" style="vertical-align:middle;" />
	&nbsp;&nbsp;
	<a href="http://icalevents.anmari.com/comments/feed/"><?php _e('Comments feed', 'amr_ical_list_lang');?></a><img src="http://icalevents.anmari.com/images/amrical-rss.png" alt="Rss icon" style="vertical-align:middle;" />
	</p><?php
if (!function_exists('amr_events_settings_menu')) { /* then the paid plugin is already on the system */
	echo '<div class="updated"><p>';
	printf(__('Now you can <b>create events</b> and <b>ics feeds</b> directly in wordpress - See screenshots and demo at %s','amr_ical_list_lang'),
	'<a class="approved" href="http://icalevents.anmari.com/amr-events/">amr-events</a>');  
	echo '</p></div>';
}
	?></div><?php

	}
/* ---------------------------------------------------------------------*/	
	function amr_get_files ($dir, $string) {
	$dh  = opendir($dir);
	while ($filename = readdir($dh)) {
		if (stristr ($filename, $string)) 
		$files[] = $filename;
		}
	if (isset ($files)) return ($files);
	else return (false);
	}
	/* -------------------------------------------------------------------------------------------------------------*/
	function amr_check_edit_file() {
	/* check if there is an own style file, if not, then copy it over */
	/***  getting permisssions errors - probably not the best place to put this  - comment out for now */
	
	  if (file_exists(ICAL_EDITSTYLEFILE)) return (true);
	  else {
		if (!(copy (ICALSTYLEFILE, ICAL_EDITSTYLEFILE))) {
			echo '</ br><h3>'.__('Unable to create Custom css file for you to edit if you wish - not essential.', 'amr_ical_list_lang').'</h3></ br>';
			return (false);
			}
		else {		
			echo '</ br>'.sprintf(__('Copied %s1 to %s2 to allow custom css', 'amr_ical_list_lang'),ICALSTYLEFILE,ICAL_EDITSTYLEFILE).'</ br>';
			return ($c);
			}
		}
	}
	
	/* -------------------------------------------------------------------------------------------------------------*/	
	function amr_check_timezonesettings () {
	
	global $amr_globaltz;
	?><ul><?php
	if (function_exists('timezone_version_get')) 
		printf('<li>'.__('Your timezone db version is: %s','amr_ical_list_lang').'</li>',  timezone_version_get());	
	else echo '<li>'.'<a href="http://en.wikipedia.org/wiki/Tz_database">'
		.__('Plugin cannot determine timezonedb version in php &lt; 5.3.' ,'amr_ical_list_lang')
		.'</a>';?></li>
		<li>
		<?php _e('The timezone database defines the daylight saving changes amongst other things.  If correct daylight saving switchover is important to you, please check for the latest updates. ', 'amr_ical_list_lang');  _e('You may need to talk to your webhost.' , 'amr_ical_list_lang'); 
		?></li><li><a href="http://pecl.php.net/package/timezonedb"><?php _e('Php timezonedb versions', 'amr_ical_list_lang');?></a></li>
		<li><a href="http://pecl.php.net/package/timezonedb"><?php _e('Info on what changes are in which timezonedb version', 'amr_ical_list_lang');?></a></li>
		<?php
		
		if (!(isset($amr_globaltz))) {
			echo '<b>'.__('No global timezone - is there a problem here? ','amr_ical_list_lang').'</b>'; return;
		}	
		$tz = get_option('timezone_string');  	
		if ($tz == '') {		
			$gmtoffset = get_option('gmt_offset');
			if (!empty($gmtoffset ) ) {
				printf('<li>'.__('You are using the "old" gmt_offset setting ','amr_ical_list_lang').'</li><li>', $gmtoffset );
				_e('Consider changing to the more accurate timezone setting','amr_ical_list_lang'); 
				echo '&nbsp;<a href="'.WP_SITEURL.'/wp-admin/options-general.php">'.__('Go to settings','amr_ical_list_lang').'</a></li>';
				}
		}
		$now = date_create('now', $amr_globaltz);	
		echo '<li>'.__('The plugin thinks your timezone is: ','amr_ical_list_lang')
		. timezone_name_get($amr_globaltz)
		.'</li>'
		.'<li>'.__('The current UTC offset for that timezone is: ','amr_ical_list_lang').$now->getoffset()/(60*60).'</li>';

		if (function_exists('timezone_transitions_get') ) foreach (timezone_transitions_get($amr_globaltz) as $tr) 
			if ($tr['ts'] > time())
			break;
		$utctz= new DateTimeZone('UTC');
		if (isset ($tr['ts']) ) {
			try {$d = new DateTime( "@{$tr['ts']}",$utctz );}
			catch(Exception $e) { break;}
			date_timezone_set ($d,$amr_globaltz );
			printf('<li>'.__('Switches to %s on %s. GMT offset: %d', 'amr_ical_list_lang').'</li>',
				 $tr['isdst'] ? "DST" : "standard time",
				$d->format('d M Y @ H:i'), $tr['offset']/(60*60)
			);
		}
		?>
		<li><?php _e('Current time (unlocalised): ','amr_ical_list_lang');
		echo $now->format('r');?>
		</li></ul><?php
	}		

/* ---------------------------------------------------------------------*/
	function amr_ical_general_form() {
	global $amr_csize,
		$amr_calprop,
		$amr_formats,
		$amr_limits,
		$amr_compprop,
		$amr_groupings,
		$amr_components,
		$amr_options,
		$amr_globaltz;
		
		?><div> 
		<fieldset id="amrglobal"><h3><?php _e('General Options', 'amr_ical_list_lang'); ?></h3>
		<div class="postbox" style="padding:1em 2em; width: 600px;">
					<label for="no_types"><?php _e('Number of Ical Lists:', 'amr_ical_list_lang'); 
					?><input type="text" size="2" id="no_types" name="no_types" value="<?php echo $amr_options['no_types'];  ?>" />
			</label><br />		
			<label for="noeventsmessage">		
			<?php _e('Message if no events found: ', 'amr_ical_list_lang');
			?></label><br />
			<input class="wide" type="text" id="noeventsmessage" name="noeventsmessage" 
			<?php if (isset($amr_options['noeventsmessage']) and ($amr_options['noeventsmessage']))  
				{echo 'value="'.$amr_options['noeventsmessage'].'"';}?>/> 	
			<br />
			<label for="ngiyabonga">
			<input type="checkbox" id="ngiyabonga" name="ngiyabonga" value="ngiyabonga" 
			<?php if (isset($amr_options['ngiyabonga']) and ($amr_options['ngiyabonga']))  {echo 'checked="checked"';}
			?>/> <?php _e('Do not give credit to the author', 'amr_ical_list_lang'); ?></label>
			<br />
			<label for="own_css">
			<input type="checkbox" id="own_css" name="own_css" value="own_css" 
			<?php if (isset($amr_options['own_css']) and ($amr_options['own_css']))  {echo 'checked="checked"';}
			?>/> <?php _e('Use my theme css, not plugin css', 'amr_ical_list_lang'); 
			$files = amr_get_css_url_choices();
			?></label>
			<br />
			<label for="no_images">
			<input type="checkbox" id="no_images" name="no_images" value="true" 
			<?php if (isset($amr_options['no_images']) and ($amr_options['no_images']))  {echo 'checked="checked"';}
			?>/><?php _e(' No images (tick for text only)', 'amr_ical_list_lang'); 
			?></label>
			<br />
			<p><em><?php
			_e('The css provided works with the default twenty-ten theme and similar themes.  Your theme may be different.', 'amr_ical_list_lang');
			echo ' ';
			_e('To edit the file, download the custom one added to your uploads folder: uploads/css.', 'amr_ical_list_lang'); echo ' ';
			_e('Edit it and then re-upload to that same folder. Then select it in the box below.', 'amr_ical_list_lang');
			echo ' ';
			_e('This file will not be overwritten when the plugin is upgraded or when your theme is upgraded. ', 'amr_ical_list_lang'); ?></em>
			<a href="http://icalevents.anmari.com/?s=css"><?php _e('More info'); ?></a><p>
			<a href="<?php echo ICALLISTPLUGINURL.'css/icallist.css'; ?>"><?php _e('Download the latest provided css file for editing', 'amr_ical_list_lang'); ?></a><?php echo ' '; _e('(optional)','amr_ical_list_lang'); ?><br />
			<label for="cssfile"><?php _e('Choose plugin default css or choose a custom css and edit it.', 'amr_ical_list_lang'); ?></label>
			<select id="cssfile" name="cssfile" ><?php
				if (empty ($files)) echo AMR_NL.' <option value="">'.__('No css files found in css directory ', 'amr_ical_list_lang').$dir.' '.$files.'</option>';
				else foreach ($files as $ifile => $file) {
					echo AMR_NL.' <option value="'.$file.'"';
					if (isset($amr_options['cssfile']) and ($amr_options['cssfile'] == $file)) echo ' selected="selected" ';
					echo '>'.$file.'</option>';
				}					
			?></select>
			
</div>
<h3><?php _e('Advanced:','amr_ical_list_lang'); 
?></h3><div class="postbox" style="padding:1em 2em; width: 600px;">
<?php printf(__('Your php version is: %s','amr_ical_list_lang'),  phpversion());	?><br /><?php
if (version_compare('5.3', PHP_VERSION, '>')) {
	echo( '<b>'.__('Minimum Php version 5.3 required for events cacheing. ','amr_ical_list_lang').	'</b><br /><br />');
	}
		amr_check_timezonesettings();
		$now = date_create('now', $amr_globaltz);
		?><br /><br /><?php
		_e('Choose date localisation method:', 'amr_ical_list_lang'); 
		?><a href="http://icalevents.anmari.com/2044-date-and-time-localisation-in-wordpress/"><b>?</b></a><br />				
			<br /><label for="no_localise"><input type="radio" id="no_localise" name="date_localise" value="none" <?php if ($amr_options['date_localise'] === "none") echo ' checked="checked" '; ?> />
			<?php _e('none', 'amr_ical_list_lang'); echo ' - '.amr_format_date('r', $now); ?></label>
			<br /><label for="am_localise"><input type="radio" id="am_localise" name="date_localise" value="amr" <?php if ($amr_options['date_localise'] === "amr") echo ' checked="checked" '; ?> />
			<?php _e('amr', 'amr_ical_list_lang'); echo ' - '.amr_date_i18n('r', $now); ?></label>
			<br /><label for="wp_localise"><input type="radio" id="wp_localise" name="date_localise" value="wp" <?php if ($amr_options['date_localise'] === "wp") echo ' checked="checked" '; ?> /> 
			<?php _e('wp', 'amr_ical_list_lang'); echo ' - '.amr_wp_format_date('r', $now, false);?></label>
			<br /><label for="wpg_localise"><input type="radio" id="wpg_localise" name="date_localise" value="wpgmt" <?php if ($amr_options['date_localise'] === "wpgmt") echo ' checked="checked" '; ?> /> 
			<?php _e('wpgmt', 'amr_ical_list_lang'); echo ' - '.amr_wp_format_date('r', $now, true);?></label>
		</div>
		</fieldset>
	</div>
<?php			
	}

	/* ---------------------------------------------------------------------*/
	function AmRIcal_option_page()  {
	global $amr_options;
	$nonce = wp_create_nonce('amr_ical_list_lang'); /* used for security to verify that any action request comes from this plugin's forms */
	if (isset($_REQUEST['uninstall'])  OR isset($_REQUEST['reallyuninstall']))  { /*  */
		amr_ical_check_uninstall(); 	
		return;
	}
	if (isset ($_POST['reset'])) {	
		$amr_options = amr_getset_options (true); 
		}
	else $amr_options = amr_getset_options(false);	/* options will be set to defaults here if not already existing */
	
	if (!(isset ($_POST['reset'])) and (isset ($_POST['action']) and ($_POST['action'] == "save"))) {/* Validate the input and save */	
		_e('Saving....','amr_ical_list_lang');
		if (!isset($_REQUEST['list'])) {
				if (! amr_ical_validate_general_options() ) 
					{echo '<h2>Error validating general options</h2>';}	
				else _e('List saved','amr_ical_list_lang');	
				
			}	
			else {
				if (isset($_REQUEST["list"]) and is_numeric($_REQUEST["list"])) {/* then configure just that list */
					$result = amr_ical_validate_list_options($_REQUEST['list']); /* messages are in the function */
					if ($result) _e('List saved','amr_ical_list_lang');
					else _e('Error in saving','amr_ical_list_lang');
				}
				else {echo '<h2>'.__('Invalid List Type','amr_ical_list_lang').'</h2>';}
			}
			
		}?>	

		<div class="wrap" id="AmRIcal"> 
		<div id="icon-options-general" class="icon32"><br />
		</div>
		<h2><?php _e('iCal Events List ', 'amr_ical_list_lang'); echo AMR_ICAL_LIST_VERSION; ?></h2>		
		
		<form method="post" action="<?php esc_url($_SERVER['PHP_SELF']); ?>">
				<?php  wp_nonce_field('amr_ical_list_lang'); /* outputs hidden field */		
				if (!isset($_GET['list'])) amr_request_acknowledgement();	
			?><div id="listnav"  style="clear:both;"><?php
				$url = remove_query_arg('list');
				echo '<a class="button-primary" href="'.$url.'">'.__('General Options','amr_ical_list_lang').'</a> ';
				_e('Go to list type:','amr_ical_list_lang' );
				for ($i = 1; $i <= $amr_options['no_types']; $i++) {
					if ($i > 1) echo '&nbsp;|&nbsp;';
					echo '&nbsp;<a href="'.$url.'&amp;list='.$i.'">'.$i.' '.$amr_options[$i]['general']['name'].'</a>&nbsp;&nbsp;';
				}?>
				</div>
				<div style="clear: both;">&nbsp;
				</div>
				<fieldset id="submit" style="clear:both; float: right; margin: 0 2em;">
				<input type="hidden" name="action" value="save" />
				<input type="submit" class="button-primary" title="<?php
					_e('Save the settings','amr_ical_list_lang') ; 
					?>" value="<?php _e('Update', 'amr_ical_list_lang') ?>" />
				<input type="submit" class="button" name="uninstall" title="<?php
					_e('Uninstall the plugin and delete the options from the database.','amr_ical_list_lang') ; 
					?>" value="<?php _e('Uninstall', 'amr_ical_list_lang') ?>" />	
				<input type="submit" class="button" name="reset" title="<?php
					_e('Warning: This will reset ALL the options immediately.','amr_ical_list_lang') ; 
					?>" value="<?php _e('Reset', 'amr_ical_list_lang') ?>" />	
				</fieldset>
			<?php		
			if (!isset($_REQUEST['list'])) 	amr_ical_general_form();
			else amr_configure_list($_REQUEST['list']);		
		?>
		</form>
		</div><?php		
	}	//end AmRIcal_option_page



/* -------------------------------------------------------------------------------------------------*/	
	function AmRIcal_formats ($i) {
	global $amr_options;	
	global $amr_globaltz;
	
	?><fieldset id="formats<?php echo $i; ?>" class="formats" >
	<h4 class="trigger"><a href="#" >
	<?php _e(' Define date and time formats:', 'amr_ical_list_lang'); ?></a></h4>
	<div class="toggle_container"><p><em><?php
		_e('Define the formats for the day (eg: Event date, End Date) and time (eg: Start time, End Time) fields. You can actually use any of these to display a full Date time string too. Use the Event date for event instances - the DTSTART field is the first startdate of a recurring event sequence.', 'amr_ical_list_lang'); ?></em></p><p><em><?php
		_e('These are also used for the date related grouping headings (ie: will show the date in that format as a heading for that group of dates if relevant.)', 'amr_ical_list_lang'); 
		?> <?php echo __('Use the standard PHP format strings: ','amr_ical_list_lang')
			. '<a href="#" title="'.__('Php manual - date datetime formats', 'amr_ical_list_lang').'" ' 
			.'onclick="window.open(\'http://www.php.net/manual/en/function.date.php\', \'dates\', \'width=600, height=400,scrollbars=yes\')"'
			.'> '
			.__('See php date function format strings' , 'amr_ical_list_lang').'</a>'
			.__(' (will localise) ' , 'amr_ical_list_lang')
//			. '<a href="#" title="'.__('Php manual - Strftime datetime formats', 'amr_ical_list_lang').'" '
//			.'onclick="window.open(\'http://php.net/manual/en/function.strftime.php\', \'dates\', \'width=600, height=400,scrollbars=yes\')"'
//			.'> '			
//			.__('strftime' , 'amr_ical_list_lang').'</a>'
	;?></em></p><?php
		if (! isset($amr_options[$i]['format'])) echo 'No formats set';
		else
		{	$date = new DateTime();
			echo '<ul>';
			foreach ( $amr_options[$i]['format'] as $c => $v ) {		
				$l = str_replace(' ','', $c).$i;
				echo '<li><label for="'.$l.' ">'.__($c,'amr_ical_list_lang').'</label>';
				echo '<input type="text" size="12" id="'.$l.'" name="format['.$i.']['.$c.']"';
				echo ' value="'.$v.'" /> ';
				echo amr_format_date( $v, $date); //a* amr ***/
				echo '</li>'; 
			} 
			echo '</ul>';
		} ?></div>
		</fieldset><?php 
	return ;	
	}
/* -------------------------------------------------------------------------------------------------------------*/

function amr_configure_list($i) {

global $amr_options;

		
		echo '<fieldset id="List'.$i.'" >' ;		
		echo '<legend>'. __('List Type ', 'amr_ical_list_lang').$i.'</legend>'; 
		echo '<a class="expandall" href="" >'.__('Expand/Contract all', 'amr_ical_list_lang').'</a>';
//		echo '<a style="float:right; margin-top:-1em;" name="list'.$i.'" href="#">'.__('go back','amr_ical_list_lang').'</a>';	
		if (!(isset($amr_options[$i])) )  echo 'Error in saved options';							
		else{ 	

			AmRIcal_general($i);	
			AmRIcal_limits($i);	
			AmRIcal_formats ($i);
			if (!(in_array($amr_options[$i]['general']['ListHTMLStyle'],array('smallcalendar','largecalendar')))) 
				AmRIcal_col_headings($i);
			//	
			AmRIcal_compropsoption($i); 
			AmRIcal_componentsoption($i);
			if (!(in_array($amr_options[$i]['general']['ListHTMLStyle'],array('smallcalendar','largecalendar')))) {
				AmRIcal_groupingsoption($i); 
				AmRIcal_calpropsoption($i);
			}



		}	
		echo "\n\t".'</fieldset>  <!-- end of list type -->';	
		?><script type="text/javascript">
jQuery(document).ready(function(){//Hide (Collapse) the toggle containers on load
	jQuery("div.toggle_container").hide();

	//Switch the "Open" and "Close" state per click
	jQuery(".trigger").toggle(function(){
		jQuery(this).addClass("active");
		}, function () {
		jQuery(this).removeClass("active");
	});
	//Slide up and down on click
	jQuery(".trigger").click(function(){
		jQuery(this).next("div.toggle_container").slideToggle("slow");
	});	
		//Switch the "Open" and "Close" state per click
	jQuery(".expandall").toggle(function(){
		jQuery(this).addClass("active");
		}, function () {
		jQuery(this).removeClass("active");
	});	
		//Slide up and down on click
	jQuery(".expandall").click(function(){
		jQuery("div.toggle_container").slideToggle("slow");
	});

	
	});
</script><?php
					
	}
?>