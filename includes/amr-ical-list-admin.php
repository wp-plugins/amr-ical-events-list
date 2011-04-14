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
		if (! wp_verify_nonce($nonce, 'amr-ical-events-list')) die ("Cancelled due to failed security check");

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
			if (isset($_POST['images_size']))  
				$amr_ical_image_settings['images_size'] =  (int) ($_POST['images_size']) ;		/* from dropdown */
			else 
				$amr_ical_image_settings['images_size'] =  '16';
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
				else { _e('Invalid Number of Lists', 'amr-ical-events-list'); return(false);
				}
			}
			update_option('amr-ical-events-list', $amr_options);
			update_option('amr_ical_images_to_use', $amr_ical_image_settings);
			amr_ical_events_list_record_version();
			
			return(true);
	}

/* ---------------------------------------------------------------------- */
function amr_ical_validate_list_options($i)	{
global $amr_options;
	if (isset($_POST['general']))  {  
		if (is_array($_POST['general'][$i])){ 
			foreach ($_POST['general'][$i] as $c => $v)	{			
				if (!empty($_POST['general'][$i][$c])) { 
					switch ($c) {
					case 'Default Event URL' : { 
						if	(!filter_var($_POST['general'][$i][$c],FILTER_VALIDATE_URL)) {					
							 amr_invalid_url();
						}
						else {
							$url = filter_var($_POST['general'][$i][$c],FILTER_SANITIZE_URL);
							$sticky_url = amr_make_sticky_url($url);
							if (!$sticky_url) $amr_options[$i]['general'][$c] = $url ; //might be external
							else $amr_options[$i]['general'][$c] = $sticky_url ;
						}
						break;
					}	
					case 'customHTMLstylefile': { 
							$custom_htmlstyle_file = esc_attr($_POST['general'][$i]['customHTMLstylefile']);
							
							if (!($custom_htmlstyle_file[0]  === '/')) 
								$custom_htmlstyle_file = '/'.$custom_htmlstyle_file;
							$uploads = wp_upload_dir();
							if (!file_exists($uploads['basedir'].$custom_htmlstyle_file))  {
								amr_invalid_file();
								$amr_options[$i]['general']['customHTMLstylefile'] = ' ';
								}
							else { 
								$amr_options[$i]['general']['customHTMLstylefile']
								= $custom_htmlstyle_file;
							}	
						}
					break;
					default: { 
						$amr_options[$i]['general'][$c] 
						= filter_var($_POST['general'][$i][$c],FILTER_SANITIZE_STRING) ;	
					}
					}	
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
		else _e('Error in form - calprop array not found', 'amr-ical-events-list');

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
function amrical_general_form ($i) {
	global $amr_options;
	update_option('amr-ical-events-list-version', AMR_ICAL_LIST_VERSION); // for upgrade checks 
 ?><fieldset  id="general<?php echo $i; ?>" class="general" >
	<h4><?php _e('General:', 'amr-ical-events-list'); ?></h4>
	<div><?php
	if (! isset($amr_options[$i]['general'])) echo 'No general specifications set';
	else {
		if (isset($amr_options[$i]['general']['ListHTMLStyle'])) 
			$style = $amr_options[$i]['general']['ListHTMLStyle'];
		else 
			$style = '';
	?><label for="name" ><?php _e('Name','amr-ical-events-list'); ?></label>
		<input type="text" class="wide" size="20" id="name" name="general[<?php echo $i; ?>][name]" value="<?php
		if (isset($amr_options[$i]['general']['name'])) echo $amr_options[$i]['general']['name']; ?>" />
	<label for="description" ><?php _e('Internal Description','amr-ical-events-list'); ?></label><br />
		<textarea cols="60" rows="6" id="description" name="general[<?php echo $i; ?>][Description]"><?php
		if (isset($amr_options[$i]['general']['Description'])) echo $amr_options[$i]['general']['Description']; ?></textarea><br />
	<label for="ListHTMLStyle" ><?php _e('List HTML Style','amr-ical-events-list'); ?></label>
		<select id="ListHTMLStyle" name="general[<?php echo $i; ?>][ListHTMLStyle]">
			<option value="table" <?php if ($style==='table') echo 'selected="selected" '; ?>><?php _e('Table', 'amr-ical-events-list'); ?></option>
			<option value="list" <?php if ($style==='list') echo 'selected="selected" '; ?>><?php _e('Lists for rows', 'amr-ical-events-list'); ?></option>
			<option value="HTML5table" <?php if ($style==='HTML5Table') echo 'selected="selected" '; ?>><?php _e('HTML5 in table', 'amr-ical-events-list'); ?></option>
			<option value="HTML5" <?php if ($style==='HTML5') echo 'selected="selected" '; ?>><?php _e('HTML5 clean and lean', 'amr-ical-events-list'); ?></option>
			<option value="custom" <?php if ($style==='custom') echo 'selected="selected" '; ?>><?php _e('Custom - file required', 'amr-ical-events-list'); ?></option>
			<option value="breaks" <?php if ($style==='breaks') echo 'selected="selected" '; ?>><?php _e('Breaks for rows!', 'amr-ical-events-list'); ?></option>
			<option value="smallcalendar" <?php if ($style==='smallcalendar') echo 'selected="selected" '; ?>><?php _e('Small box calendar', 'amr-ical-events-list'); ?></option>
			<option value="largecalendar" <?php if ($style==='largecalendar') echo 'selected="selected" '; ?>><?php _e('Large box calendar', 'amr-ical-events-list'); ?></option>
			<option value="tableoriginal" <?php if ($style==='tableoriginal') echo 'selected="selected" '; ?>><?php _e('Table with lists in cells (original)', 'amr-ical-events-list'); ?></option>
	
		</select><br />	<br />
<?php //--------------------------------------------  
	$uploads = wp_upload_dir();?>
	<label for="customHTMLstylefile" >
	<?php echo sprintf(__('Custom HTML style file at %s...','amr-ical-events-list'),$uploads['basedir']); 
	?><a title="<?php  _e(' (Html and some php knowledge required)','amr-ical-events-list'); ?>"	
	href="" >?</a>
	</label>
		<input type="text" class="wide" size="60" id="customHTMLstylefile" 
		name="general[<?php echo $i; ?>][customHTMLstylefile]" value="<?php
				if (isset($amr_options[$i]['general']['customHTMLstylefile'])) 
					echo esc_textarea($amr_options[$i]['general']['customHTMLstylefile']); ?>" />
		
		
<?php //--------------------------------------------  ?>		
	<br />
	<label for="defaulturl" ><?php _e('Default Event URL','amr-ical-events-list'); ?><em>
	<?php
	_e(' (For ics files in widget. External, or calendar page.)','amr-ical-events-list'); ?>
	</em>
	<a title="<?php _e('More information','amr-ical-events-list'); ?>"	href="http://icalevents.anmari.com/1901-widgets-calendar-pages-and-event-urls/" >?</a>
	</label>
		<input type="text" class="wide" size="60" id="defaulturl" name="general[<?php echo $i; ?>][Default Event URL]" value="<?php
				if (isset($amr_options[$i]['general']['Default Event URL'])) 
					echo esc_textarea($amr_options[$i]['general']['Default Event URL']); ?>" />
<?php //--------------------------------------------
 }
?>
</div></fieldset>
<?php
	return ;
	}
	/* ---------------------------------------------------------------------*/
	function amrical_limits($i) {
	global $amr_options;
		?><fieldset class="limits" ><h4 class="trigger"><a href="#" ><?php _e('Define maximums:', 'amr-ical-events-list'); ?></a></h4>
		<div class="toggle_container">
		<p><em><?php _e('Note cache times are in hours','amr-ical-events-list'); ?></em></p><?php
		if (! isset($amr_options[$i]['limit'])) echo 'No default limits set';
		else {
			foreach ( $amr_options[$i]['limit'] as $c => $v )
			{
				echo '<label for="L'.$i.$c.'" >'.__($c,'amr-ical-events-list').'</label>';
				echo '<input type="text" size="2" id="L'.$i.$c.'"  name="limit['.$i.']['.$c.']"';
				echo ' value="'.$v.'" />';
			}
		}
		echo "\n\t".'</div></fieldset>';
	return ;
	}
	/* ---------------------------------------------------------------------*/
	function amrical_componentsoption($i) {
	global $amr_options;
	?><fieldset id="components<?php echo $i; ?>" class="components" >
	<h4 class="trigger"><a href="#" ><?php _e('Select components to show:', 'amr-ical-events-list');
	?></a>&nbsp;<a title="<?php _e('Wikipedia entry describing components', 'amr-ical-events-list'); ?>"
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
	function amrical_groupingsoption($i) {
		global $amr_options;

		?><fieldset class="icalgroupings">
		<h4 class="trigger"><a href="#" ><?php _e('Define grouping:', 'amr-ical-events-list');?></a></h4>
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
	function amrical_calpropsoption($i) {
	global $amr_options;
	global $amr_csize;
		?><fieldset id="calprop" class="props">
		<h4 class="trigger"><a href="#"><?php _e('Calendar properties' , 'amr-ical-events-list'); ?></a></h4>
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
	function amrical_compropsoption($i) {
	global $amr_options;
	global $amr_csize;
		?><fieldset id="comprop" class="props" >
		<h4 class="trigger"><a href="#"><?php _e('Specify fields to show:' , 'amr-ical-events-list'); ?></a></h4>

		<div class="toggle_container">
<p>
<em><?php _e('Note: a 0 (zero) in column = do not show that field.', 'amr-ical-events-list'); 
?></em> <a title="<?php _e('Link to more information', 'amr-ical-events-list'); ?>" href="http://icalevents.anmari.com/list-types/"><?php _e('More information', 'amr-ical-events-list'); ?></a>
</p><?php
		?><p><em><?php _e('Uppercase fields are those defined in the iCal specification.', 'amr-ical-events-list');?></em></p>
		<p><em><?php
		_e('Lowercase fields are additional fields added by this plugin and derived from the iCal fields for your convenience.' , 'amr-ical-events-list');
		_e('Fields show if "column" > 0 and if there is data available in your event or ics file.', 'amr-ical-events-list');
		?></em></p><?php
		foreach ( $amr_options[$i]['compprop'] as $si => $section )	{ /* s= descriptive */
		?><fieldset class="section"><h4 class="trigger">&nbsp;&nbsp;<a href="#"><?php _e($si,'amr-ical-events-list'); ?></a></h4>
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

	function amrical_col_headings($i) {
	/* for component properties only */
	global $amr_options;
	global $amr_csize;
		?><fieldset class="section">
		<h4 class="trigger"><a href="#" ><?php _e('Column Headings:','amr-ical-events-list');?></a></h4>
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

function amr_ical_support_links () {
?><div class="postbox" style="padding:1em 2em; width: 600px;">
	<p>
	<a href="http://icalevents.anmari.com" title="Sign up or monitor the feed for regular updates"><?php _e('Documentation', 'amr-ical-events-list');?></a>
	&nbsp;&nbsp;
	<a href="http://forum.anmari.com" title="Support Forum"><?php _e('Support', 'amr-ical-events-list');?></a>
	&nbsp;&nbsp;
	<a href="http://icalevents.anmari.com/videos" title="Events plugin videos"><?php _e('Videos', 'amr-ical-events-list');?></a>
	&nbsp;&nbsp;
	<a href='http://wordpress.org/tags/amr-ical-events-list' title="If you like it rate it..."><?php _e('Rate it at WP', 'amr-ical-events-list');?></a>
	&nbsp;&nbsp;
	<a href="http://icalevents.anmari.com/feed/"><?php _e('Plugin feed', 'amr-ical-events-list');?></a><img src="http://icalevents.anmari.com/images/amrical-rss.png" alt="Rss icon" style="vertical-align:middle;" />
	&nbsp;&nbsp;
	<a href="http://forum.anmari.com/rss.php?id=1"><?php _e('Forum feed', 'amr-ical-events-list');?></a><img src="http://icalevents.anmari.com/images/amrical-rss.png" alt="Rss icon" style="vertical-align:middle;" />		
	</p></div><?php

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
			echo '</ br><h3>'.__('Unable to create Custom css file for you to edit if you wish - not essential.', 'amr-ical-events-list').'</h3></ br>';
			return (false);
			}
		else {
			echo '</ br>'.sprintf(__('Copied %s1 to %s2 to allow custom css', 'amr-ical-events-list'),ICALSTYLEFILE,ICAL_EDITSTYLEFILE).'</ br>';
			return ($c);
			}
		}
	}

	/* -------------------------------------------------------------------------------------------------------------*/
	function amr_check_timezonesettings () {

	global $amr_globaltz;
	?><ul><?php
	if (function_exists('timezone_version_get'))
		printf('<li>'.__('Your timezone db version is: %s','amr-ical-events-list').'</li>',  timezone_version_get());
	else echo '<li>'.'<a href="http://en.wikipedia.org/wiki/Tz_database">'
		.__('Plugin cannot determine timezonedb version in php &lt; 5.3.' ,'amr-ical-events-list')
		.'</a>';?></li>
		<li>
		<?php _e('The timezone database defines the daylight saving changes amongst other things.  If correct daylight saving switchover is important to you, please check for the latest updates. ', 'amr-ical-events-list');  _e('You may need to talk to your webhost.' , 'amr-ical-events-list');
		?></li><li><a href="http://pecl.php.net/package/timezonedb"><?php _e('Php timezonedb versions', 'amr-ical-events-list');?></a></li>
		<li><a href="http://pecl.php.net/package/timezonedb"><?php _e('Info on what changes are in which timezonedb version', 'amr-ical-events-list');?></a></li>
		<?php

		if (!(isset($amr_globaltz))) {
			echo '<b>'.__('No global timezone - is there a problem here? ','amr-ical-events-list').'</b>'; return;
		}
		$tz = get_option('timezone_string');
		if ($tz == '') {
			$gmtoffset = get_option('gmt_offset');
			if (!empty($gmtoffset ) ) {
				printf('<li>'.__('You are using the "old" gmt_offset setting ','amr-ical-events-list').'</li><li>', $gmtoffset );
				_e('Consider changing to the more accurate timezone setting','amr-ical-events-list');
				echo '&nbsp;<a href="'.get_option('siteurl').'/wp-admin/options-general.php">'.__('Go to settings','amr-ical-events-list').'</a></li>';
				}
		}
		$now = date_create('now', $amr_globaltz);
		echo '<li>'.__('The plugin thinks your timezone is: ','amr-ical-events-list')
		. timezone_name_get($amr_globaltz)
		.'</li>'
		.'<li>'.__('The current UTC offset for that timezone is: ','amr-ical-events-list').$now->getoffset()/(60*60).'</li>';

		if (function_exists('timezone_transitions_get') ) foreach (timezone_transitions_get($amr_globaltz) as $tr)
			if ($tr['ts'] > time())
			break;
		$utctz= new DateTimeZone('UTC');
		if (isset ($tr['ts']) ) {
			try {$d = new DateTime( "@{$tr['ts']}",$utctz );}
			catch(Exception $e) { break;}
			date_timezone_set ($d,$amr_globaltz );
			printf('<li>'.__('Switches to %s on %s. GMT offset: %d', 'amr-ical-events-list').'</li>',
				 $tr['isdst'] ? "DST" : "standard time",
				$d->format('d M Y @ H:i'), $tr['offset']/(60*60)
			);
		}
		?>
		<li><?php _e('Current time (unlocalised): ','amr-ical-events-list');
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

		$amr_ical_image_settings = get_option('amr_ical_images_to_use');
		if (empty ($amr_ical_image_settings['images_size'])) 
			$imagesize = '16';		
		else
			$imagesize = (int) ($amr_ical_image_settings['images_size']);
			
		?><div>
		<fieldset id="amrglobal"><h3><?php _e('General Options', 'amr-ical-events-list'); ?></h3>
		<div class="postbox" style="padding:1em 2em; width: 600px;">
					<label for="no_types"><?php _e('Number of Ical Lists:', 'amr-ical-events-list');
					?><input type="text" size="2" id="no_types" name="no_types" value="<?php echo $amr_options['no_types'];  ?>" />
			</label><br />
			<label for="noeventsmessage">
			<?php _e('Message if no events found: ', 'amr-ical-events-list');
			?></label><br />
			<input class="wide regular-text" type="text" id="noeventsmessage" name="noeventsmessage"
			<?php if (isset($amr_options['noeventsmessage']) and ($amr_options['noeventsmessage']))
				{echo 'value="'.$amr_options['noeventsmessage'].'"';}?>/>
			<br />
			<label for="ngiyabonga">
			<input type="checkbox" id="ngiyabonga" name="ngiyabonga" value="ngiyabonga"
			<?php if (isset($amr_options['ngiyabonga']) and ($amr_options['ngiyabonga']))  {echo 'checked="checked"';}
			?>/> <?php _e('Do not give credit to the author', 'amr-ical-events-list'); ?></label>

			</div>
			</fieldset>
			<fieldset id="amrstyle"><h3><?php _e('Stying and Images', 'amr-ical-events-list'); ?></h3>
			<div class="postbox" style="padding:1em 2em; width: 600px;">	
		
			<label for="own_css">
			<input type="checkbox" id="own_css" name="own_css" value="own_css"
			<?php if (isset($amr_options['own_css']) and ($amr_options['own_css']))  {echo 'checked="checked"';}
			?>/> <?php _e('Use my theme css, not plugin css', 'amr-ical-events-list');
			$files = amr_get_css_url_choices();
			?></label>
			<br />
			<label for="no_images">
			<input type="checkbox" id="no_images" name="no_images" value="true"
			<?php if (isset($amr_options['no_images']) and ($amr_options['no_images']))  {echo 'checked="checked"';}
			?>/><?php _e(' No images (tick for text only)', 'amr-ical-events-list');
			?></label>
			<br />			
			<br />
			
			<label for="images_size">
			<?php _e('Image icon size:', 'amr-ical-events-list');	?>
			<input type="radio" id="images_size" name="images_size" value="16"
			<?php if ($imagesize == '16')  {echo 'checked="checked"';}
			?>/><?php _e('16', 'amr-ical-events-list');	?></label>
			<input type="radio" id="images_size" name="images_size" value="32"
			<?php if ($imagesize == '32')  {echo 'checked="checked"';}
			?>/><?php _e('32', 'amr-ical-events-list');
			?></label>
			<p><em><?php
			_e('The css provided works with the default twenty-ten theme and similar themes.  Your theme may be different.', 'amr-ical-events-list');
			echo ' ';
			_e('To edit the file, download the custom one added to your uploads folder: uploads/css.', 'amr-ical-events-list'); echo ' ';
			_e('Edit it and then re-upload to that same folder. Then select it in the box below.', 'amr-ical-events-list');
			echo ' ';
			_e('This file will not be overwritten when the plugin is upgraded or when your theme is upgraded. ', 'amr-ical-events-list'); ?></em>
			<a href="http://icalevents.anmari.com/?s=css"><?php _e('More info','amr-ical-events-list'); ?></a><br />
			<a href="<?php echo ICALLISTPLUGINURL.'css/icallist.css'; ?>"><?php _e('Download the latest provided css file for editing', 'amr-ical-events-list'); ?></a><?php echo ' '; _e('(optional)','amr-ical-events-list'); ?><br />
			<label for="cssfile"><?php _e('Choose plugin default css or choose a custom css and edit it.', 'amr-ical-events-list'); ?></label>
			<select id="cssfile" name="cssfile" ><?php
				if (empty ($files)) echo AMR_NL.' <option value="">'.__('No css files found in css directory ', 'amr-ical-events-list').$dir.' '.$files.'</option>';
				else foreach ($files as $ifile => $file) {
					echo AMR_NL.' <option value="'.$file.'"';
					if (isset($amr_options['cssfile']) and ($amr_options['cssfile'] == $file)) echo ' selected="selected" ';
					echo '>'.$file.'</option>';
				}
			?></select>

</div>
<h3><?php _e('Advanced:','amr-ical-events-list');
?></h3><div class="postbox" style="padding:1em 2em; width: 600px;">
<?php printf(__('Your php version is: %s','amr-ical-events-list'),  phpversion());	?><br /><?php
if (version_compare('5.3', PHP_VERSION, '>')) {
	echo( '<b>'.__('Minimum Php version 5.3 required for events cacheing. ','amr-ical-events-list').	'</b><br /><br />');
	}
		amr_check_timezonesettings();
		$now = date_create('now', $amr_globaltz);
		?><br /><br /><?php
		_e('Choose date localisation method:', 'amr-ical-events-list');
		?><a href="http://icalevents.anmari.com/2044-date-and-time-localisation-in-wordpress/"><b>?</b></a><br />
			<br /><label for="no_localise"><input type="radio" id="no_localise" name="date_localise" value="none" <?php if ($amr_options['date_localise'] === "none") echo ' checked="checked" '; ?> />
			<?php _e('none', 'amr-ical-events-list'); echo ' - '.amr_format_date('r', $now); ?></label>
			<br /><label for="am_localise"><input type="radio" id="am_localise" name="date_localise" value="amr" <?php if ($amr_options['date_localise'] === "amr") echo ' checked="checked" '; ?> />
			<?php _e('amr', 'amr-ical-events-list'); echo ' - '.amr_date_i18n('r', $now); ?></label>
			<br /><label for="wp_localise"><input type="radio" id="wp_localise" name="date_localise" value="wp" <?php if ($amr_options['date_localise'] === "wp") echo ' checked="checked" '; ?> />
			<?php _e('wp', 'amr-ical-events-list'); echo ' - '.amr_wp_format_date('r', $now, false);?></label>
			<br /><label for="wpg_localise"><input type="radio" id="wpg_localise" name="date_localise" value="wpgmt" <?php if ($amr_options['date_localise'] === "wpgmt") echo ' checked="checked" '; ?> />
			<?php _e('wpgmt', 'amr-ical-events-list'); echo ' - '.amr_wp_format_date('r', $now, true);?></label>
		</div>
		</fieldset>
	</div>
<?php
	}

	/* ---------------------------------------------------------------------*/
	function amrical_option_page()  {
	global $amr_options;
	$nonce = wp_create_nonce('amr-ical-events-list'); /* used for security to verify that any action request comes from this plugin's forms */
	if (isset($_REQUEST['uninstall'])  OR isset($_REQUEST['reallyuninstall']))  { /*  */
		amr_ical_check_uninstall();
		return;
	}
	if (isset ($_POST['reset'])) $amr_options = amr_getset_options (true);
	else $amr_options = amr_getset_options(false);	/* options will be set to defaults here if not already existing */

	if (!(isset ($_POST['reset'])) and (isset ($_POST['action']) and ($_POST['action'] == "save"))) {/* Validate the input and save */
		_e('Saving....','amr-ical-events-list');
		if (!isset($_REQUEST['list'])) {
				if (! amr_ical_validate_general_options() )
					{echo '<h2>Error validating general options</h2>';}
				else _e('List saved','amr-ical-events-list');

			}
		}?>
		<div class="wrap" id="amrical">
		<div id="icon-options-general" class="icon32"><br />
		</div>
		<h2><?php _e('iCal Events List ', 'amr-ical-events-list'); echo AMR_ICAL_LIST_VERSION; ?></h2>
		<form method="post" action="<?php esc_url($_SERVER['PHP_SELF']); ?>">
			<?php  wp_nonce_field('amr-ical-events-list'); /* outputs hidden field */
				amr_request_acknowledgement();
				amr_ical_support_links ();

			amr_ical_submit_buttons ();
			amr_ical_general_form();
		?>
		</form>
		</div><?php
	}	//end amrical_option_page

	/* ---------------------------------------------------------------------*/
	function amrical_listing_options_page()  {
	global $amr_options;
	$amr_options = amr_getset_options();

	if (isset($_REQUEST["list"]) ) 	$listtype = (int) $_REQUEST["list"];
	else $listtype = 1;
	if (isset ($_POST['reset'])) $amr_options = amr_getset_options (true);
	else {
		$amr_options = amr_getset_options(false);	/* options will be set to defaults here if not already existing */
		if ((isset ($_POST['action']) and ($_POST['action'] == "save"))) {/* Validate the input and save */
			_e('Saving....','amr-ical-events-list');
			$result = amr_ical_validate_list_options($listtype); /* messages are in the function */
			if ($result) _e('List saved','amr-ical-events-list');
				else _e('No change to options or unexpected error in saving','amr-ical-events-list');
		}
	}	
?>
		<div class="wrap" id="amrical">
		<div id="icon-options-general" class="icon32"><br />
		</div>
		<h2><?php _e('Configure event list type: ', 'amr-ical-events-list'); echo $listtype; ?></h2>

		<form method="post" action="<?php esc_url($_SERVER['PHP_SELF']); ?>">
				<?php  wp_nonce_field('amr-ical-events-list'); /* outputs hidden field */

			?><div id="listnav"  style="clear:both;"><?php
				$url = remove_query_arg('list');
				_e('Go to list type:','amr-ical-events-list' );echo '<br />';
				for ($i = 1; $i <= $amr_options['no_types']; $i++) {
					if ($i > 1) echo '&nbsp;|&nbsp;';
					$text = ' <a href="'.$url.'&amp;list='.$i.'">'.$i.'&nbsp;'.$amr_options[$i]['general']['name'].'</a>&nbsp;&nbsp;';
					if ($listtype==$i) 	echo '<b>'.$text.'</b>';
					else echo $text;
				}?>
				</div>
				<?php 
			amr_ical_submit_buttons (); 
			amr_configure_list($listtype);
	
		?>
		</form>
		</div><?php
	}	//end amrical_option_page

/* -------------------------------------------------------------------------------------------------*/
	function amrical_formats ($i) {
	global $amr_options;
	global $amr_globaltz;

	?><fieldset id="formats<?php echo $i; ?>" class="formats" >
	<h4 class="trigger"><a href="#" >
	<?php _e(' Define date and time formats:', 'amr-ical-events-list'); ?></a></h4>
	<div class="toggle_container"><p><em><?php
		_e('Define the formats for the day (eg: Event date, End Date) and time (eg: Start time, End Time) fields. You can actually use any of these to display a full Date time string too. Use the Event date for event instances - the DTSTART field is the first startdate of a recurring event sequence.', 'amr-ical-events-list'); ?></em></p><p><em><?php
		_e('These are also used for the date related grouping headings (ie: will show the date in that format as a heading for that group of dates if relevant.)', 'amr-ical-events-list');
		?> <?php echo __('Use the standard PHP format strings: ','amr-ical-events-list')
			. '<a href="#" title="'.__('Php manual - date datetime formats', 'amr-ical-events-list').'" '
			.'onclick="window.open(\'http://www.php.net/manual/en/function.date.php\', \'dates\', \'width=600, height=400,scrollbars=yes\')"'
			.'> '
			.__('See php date function format strings' , 'amr-ical-events-list').'</a>'
			.__(' (will localise) ' , 'amr-ical-events-list')
//			. '<a href="#" title="'.__('Php manual - Strftime datetime formats', 'amr-ical-events-list').'" '
//			.'onclick="window.open(\'http://php.net/manual/en/function.strftime.php\', \'dates\', \'width=600, height=400,scrollbars=yes\')"'
//			.'> '
//			.__('strftime' , 'amr-ical-events-list').'</a>'
	;?></em></p><?php
		if (! isset($amr_options[$i]['format'])) echo 'No formats set';
		else
		{	$date = new DateTime();
			echo '<ul>';
			foreach ( $amr_options[$i]['format'] as $c => $v ) {
				$l = str_replace(' ','', $c).$i;
				echo '<li><label for="'.$l.' ">'.__($c,'amr-ical-events-list').'</label>';
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
function amr_ical_submit_buttons () {
?>
<div style="clear: both;">&nbsp;
				</div>
				<fieldset id="submit" style="clear:both; float: right; margin: 0 2em;">
				<input type="hidden" name="action" value="save" />
				<input type="submit" class="button-primary" title="<?php
					_e('Save the settings','amr-ical-events-list') ;
					?>" value="<?php _e('Update', 'amr-ical-events-list') ?>" />
				<input type="submit" class="button" name="uninstall" title="<?php
					_e('Uninstall the plugin and delete the options from the database.','amr-ical-events-list') ;
					?>" value="<?php _e('Uninstall', 'amr-ical-events-list') ?>" />
				<input type="submit" class="button" name="reset" title="<?php
					_e('Warning: This will reset ALL the listing options immediately.','amr-ical-events-list') ;
					?>" value="<?php _e('Reset all listing options', 'amr-ical-events-list') ?>" />
				</fieldset>
				<?php
}
/* -------------------------------------------------------------------------------------------------------------*/
function amr_configure_list($i) {
global $amr_options;

		echo '<fieldset id="List'.$i.'" >' ;
//		echo '<legend>'. __('List Type ', 'amr-ical-events-list').$i.'</legend>';
		echo '<a class="expandall" style="float:right;" href="" >'.__('Expand/Contract all', 'amr-ical-events-list').'</a>';
//		echo '<a style="float:right; margin-top:-1em;" name="list'.$i.'" href="#">'.__('go back','amr-ical-events-list').'</a>';
		if (!(isset($amr_options[$i])) )  echo 'Error in saved options';
		else{

			amrical_general_form($i);
			amrical_limits($i);
			amrical_formats ($i);
			if (!(in_array($amr_options[$i]['general']['ListHTMLStyle'],array('smallcalendar','largecalendar'))))
				amrical_col_headings($i);
			//
			amrical_compropsoption($i);
			amrical_componentsoption($i);
			if (!(in_array($amr_options[$i]['general']['ListHTMLStyle'],array('smallcalendar','largecalendar')))) {
				amrical_groupingsoption($i);
				amrical_calpropsoption($i);
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