<?php
/* This is the amr ical wordpress admin section file */

$amr_ical_version = '2.0';

	function allowed_html ($s)
	/* string any unallowed html from the before and after fields 
	strip tags tries to return a string with all HTML and PHP tags stripped	*/
	{return strip_tags($s, '<p><br /><hr /><h2><h3><<h4><h5><h6><strong><em>');
	}


/* -------------------------------------------------------------------------------------------------------------*/
function amr_ical_list_widget_control()
{
	global $amrwidget_options;
	
//	if ( isset ($_POST['reset']))  { echo '<h3>Resetting</h3>'; amr_getset_widgetoptions (true); }
	if (!isset($amrwidget_options)) $amrwidget_options = amr_getset_widgetoptions(false);	/* options will be set to defaults here if not already existing */
   
	$title = wp_specialchars($amrwidget_options['title']);
	$urls =  wp_specialchars($amrwidget_options['urls']);		
	$listtype = wp_specialchars($amrwidget_options['listtype']);
	$limit= wp_specialchars($amrwidget_options['limit']);	
	$moreurl = 	wp_specialchars($amrwidget_options['moreurl']);
		   
    if ( $_POST['amr_ical_submit'] ) 
    {	/*  should we validate these a bit  - or is admin, should know what they are doing */
		$amrwidget_options['title'] = strip_tags(stripslashes($_POST['amr_ical_title']));
		$amrwidget_options['listtype'] = strip_tags(stripslashes($_POST['amr_list_type']));
		$amrwidget_options['limit'] = strip_tags(stripslashes($_POST['amr_limit']));
		$amrwidget_options['moreurl'] = strip_tags(stripslashes($_POST['amr_moreurl']));
		if (isset ($_POST['amr_ical_urls'])) {
			$amrwidget_options['urls'] = strip_tags(stripslashes($_POST['amr_ical_urls']));
			if (!(filter_var($_POST['amr_ical_urls'], FILTER_VALIDATE_URL))) 
				$amrwidget_options['urls'] .= ' Invalid URL! ';		
		}	
		update_option('AmRiCalWidget', $amrwidget_options);
    }

?>
	<input type="hidden" id="amr_ical_submit" name="amr_ical_submit" value="1" />
	<p><label for="amr_ical_title"><?php _e('Title', 'amr-ical-events-list'); ?> 
	<input style="width: 230px;" id="amr_ical_title" name="amr_ical_title" type="text" value="<?php echo $title; ?>" /></label></p>
	<p><label for="amr_list_type"><?php _e('List Type from plugin settings', 'amr-ical-events-list'); ?> 
	<input id="amr_list_type" name="amr_list_type" type="text" style="width: 25px;"  value="<?php echo $listtype; ?>" /></label></p>
	<p><label for="amr_limit"><?php _e('Number of Events', 'amr-ical-events-list'); ?> 
	<input id="amr_limit" name="amr_limit" type="text" style="width: 25px;"  value="<?php echo $limit; ?>" /></label></p>
	<p><label for="amr_moreurl"><?php _e('Link to calendar page in this website', 'amr-ical-events-list'); ?> 
	<input id="amr_moreurl" name="amr_moreurl" type="text" style="width: 240px;" 
	value="<?php echo $moreurl; ?>" /></label></p>
	<p><label for="amr_ical_urls"><?php _e('Urls', 'amr-ical-events-list'); ?> </label>
	<textarea cols="25" rows="4" id="amr_ical_urls" name="amr_ical_urls" ><?php
		echo $urls; ?></textarea></p>
	
<?php
}

/* -------------------------------------------------------------------------------------------------------------*/
	
	function AmRIcal_add_options_panel() {
	global $wp_version;
	/* add the options page at admin level of access */

		$menutitle = __('AmR iCal Events List', 'amr-ical-events-list');
		add_options_page(__('AmR iCal Event List Configuration', 'amr-ical-events-list'), $menutitle , 8, 'manage_amr_ical', 'AmRIcal_option_page');
		
	}
			
	//build admin interface =======================================================
	
	function amr_ical_validate_options()
	{
		global 
		$amr_options,
		$amr_calprop,
		$amr_limits,
		$amr_compprop,
		$amr_groupings,
		$amr_components;

		$nonce = $_REQUEST['_wpnonce'];
		if (! wp_verify_nonce($nonce, 'amr_ical')) die ("Cancelled due to failed security check");
		
		if (isset ($_POST['reset']))  { 
			amr_getset_options (true); 
			amr_getset_widgetoptions (true); 
		}
		else
		{	
			if (isset($_POST['ngiyabonga'])) 	$amr_options['ngiyabonga'] =  true;							
			else 	$amr_options['ngiyabonga'] =  false;
			if (isset($_POST['noeventsmessage'])) 	$amr_options['noeventsmessage'] =  $_POST['noeventsmessage'];

			if (isset($_POST["own_css"])) $amr_options['own_css'] =  true;							
			else $amr_options['own_css'] =  false;
			
			if (isset($_POST["css_file"])) $amr_options['css_file'] =  $_POST["css_file"];		/* from dropdown */					
			else $amr_options['css_file'] =  '';
		
			/* check if no types updated, do not process other stuff if it has been  */
		
			if (isset($_POST["no_types"]) && (!($_POST["no_types"]== $amr_options['no_types'])))
			{		
				if (function_exists( 'filter_var') )
				{
					$int_ok = (filter_var($_POST["no_types"], FILTER_VALIDATE_INT, 
						array("options" => array("min_range"=>1, "max_range"=>10))));
				}
				else $int_ok = 	(is_integer($_POST["no_types"]) ? $_POST["no_types"] : false);
			}
			if ($int_ok) 
			{	for ($i = $amr_options['no_types']+1; $i <= $int_ok; $i++)
				{	
					$amr_options[$i] = $amr_options[1];
				}
				$amr_options['no_types'] =  $int_ok;							
			}
			else
			{
				if (isset($_POST['general']))  
					for ($i = 1; $i <= $amr_options['no_types']; $i++)			
					{	if (is_array($_POST['general'][$i])) 
						{	foreach ($_POST['general'][$i] as $c => $v)
							{ 
								$amr_options[$i]['general'][$c] = 
									(isset($_POST['general'][$i][$c])) ? $_POST['general'][$i][$c] : '';
							}
						}
						else echo 'Error in form - general array not found';
					}
				if (isset($_POST['limit']))  
					for ($i = 1; $i <= $amr_options['no_types']; $i++)			
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
					for ($i = 1; $i <= $amr_options['no_types']; $i++)			
					{	if (is_array($_POST['format'][$i])) 
						{	foreach ($_POST['format'][$i] as $c => $v)
							{   /* amr - how should we validate this ?  accepting any input for now */ 
								$amr_options[$i]['format'][$c] = 
									(isset($_POST['format'][$i][$c])) ? $_POST['format'][$i][$c] :'';
							}
						}
						else echo 'Error in form - format array not found';
					}	
				
				for($i = 1; $i <= $amr_options['no_types']; $i++)	{		 /* switch all off, as default and then check for any that are set */
					foreach ($amr_options[$i]['component'] as $k => $c) {
						if (isset($_POST['component'][$i][$k])) {
							$amr_options[$i]['component'][$k] =  true;						
						}
						else {
							$amr_options[$i]['component'][$k] =  false;	
						}
					}
				}
				
				for($i = 1; $i <= $amr_options['no_types']; $i++)	{		 /* switch all off, as default and then check for any that are set */
					foreach ($amr_options[$i]['grouping'] as $k => $c) {
						if (isset($_POST['grouping'][$i][$k])) {
							$amr_options[$i]['grouping'][$k] =  true;						
						}
						else {
							$amr_options[$i]['grouping'][$k] =  false;	
						}
					}
				}
			
				if (isset($_POST['ColH']))  
					for ($i = 1; $i <= $amr_options['no_types']; $i++)			
					{	if (is_array($_POST['ColH'][$i])) {	
							foreach ($_POST['ColH'][$i] as $c => $v) { 
								$amr_options[$i]['heading'][$c] = $v;
							}
						}
						// else echo 'Error in form - grouping array not found';   /* May not want any groupings ?
					}	
				if (isset($_POST['CalP']))  
				for ($i = 1; $i <= $amr_options['no_types']; $i++)			
				{	if (is_array($_POST['CalP'][$i])) 
					{	
						foreach ($_POST['CalP'][$i] as $c => $v)
						{   if (is_array($v)) 
							foreach ($v as $p => $pv)
							{  								
								/*need to validate these */
								switch ($p):
								case 'Column': 
									if (function_exists( 'filter_var') )
									{	if (filter_var($pv, FILTER_VALIDATE_INT, 
										array("options" => array("min_range"=>1, "max_range"=>20))))
										$amr_options[$i]['calprop'][$c][$p]= $pv;
										else 	$amr_options[$i]['calprop'][$c][$p]= 0;
									}
									else $amr_options[$i]['calprop'][$c][$p]= $pv;
									break;
																	
								case 'Order':
									if (function_exists( 'filter_var') )
									{	if (filter_var($pv, FILTER_VALIDATE_INT, 
										array("options" => array("min_range"=>1, "max_range"=>99))))
										$amr_options[$i]['calprop'][$c][$p] = $pv;break;
									}
									else $amr_options[$i]['calprop'][$c][$p] = $pv;break;
								case 'Before': $amr_options[$i]['calprop'][$c][$p] = allowed_html($pv);
									break;
								case 'After': $amr_options[$i]['calprop'][$c][$p] = allowed_html($pv);
									break;
								endswitch;
							}
						}
					}
					else _e('Error in form - calprop array not found');
					
				}
				if (isset($_POST['ComP']))  
				for ($i = 1; $i <= $amr_options['no_types']; $i++)			
				{	if (is_array($_POST['ComP'][$i])) 
					{	
						foreach ($_POST['ComP'][$i] as $si => $sv)  /* eg si = descriptve */
						{   
							foreach ($sv as $c => $v)  /* eg c= summary */
							{  
								if (is_array($v)) 
								foreach ($v as $p => $pv)
								{  								
									/*need to validate these */
									switch ($p):
									case 'Column': 
										if (function_exists( 'filter_var') )
										{	if (filter_var($pv, FILTER_VALIDATE_INT, 
											array("options" => array("min_range"=>1, "max_range"=>20))))
											$amr_options[$i]['compprop'][$si][$c][$p]= $pv;
											else 	$amr_options[$i]['compprop'][$si][$c][$p]= 0;
											break;
										}
										else $amr_options[$i]['compprop'][$si][$c][$p]= $pv;
										break;
									case 'Order':
										if (function_exists( 'filter_var') )
										{	if (filter_var($pv, FILTER_VALIDATE_INT, 
											array("options" => array("min_range"=>1, "max_range"=>99))))
											$amr_options[$i]['compprop'][$si][$c][$p] = $pv; break;
										}
										else $amr_options[$i]['compprop'][$si][$c][$p] = $pv; 
										break;
									case 'Before': $amr_options[$i]['compprop'][$si][$c][$p] = allowed_html($pv);
										break;
									case 'After': $amr_options[$i]['compprop'][$si][$c][$p] = allowed_html($pv);
										break;
									endswitch;
								}
							}
						}
					}
					else echo 'Error in form - compprop array not found';				
				}	
			}
			if ( update_option(  'AmRiCalEventList', $amr_options))
				{ _e("Options  <strong>Updated</strong>. ", 'amr-ical-events-list');	}
			else {
					add_option('AmRiCalEventList', $amr_options);
			}	
		}			
	return (true);	
	}
/* -------------------------------------------------------------------------------------------------*/	
	function AmRIcal_formats ($i) 
	{
	global $amr_options;	
	global $amr_globaltz;
	
		echo "\n\t".'<fieldset id="formats'.$i.'" class="formats" >';
		echo '<legend>';
		_e(' Define date and time formats:', 'amr-ical-events-list');
		echo '</legend><p>';
		_e(' These are also used for the grouping headings.', 'amr-ical-events-list'); 
		echo '</p><p>'.__('Use the standard PHP format strings: ','amr-ical-events-list')
			. '<a href="#" title="'.__('Php manual - date datetime formats', 'amr-ical-events-list').'" ' 
			.'onclick="window.open(\'http://www.php.net/manual/en/function.date.php\', \'dates\', \'width=600, height=400,scrollbars=yes\')"'
			.'> '
			.__('date' , 'amr-ical-events-list').'</a>'
			.__(' (will localise), ' , 'amr-ical-events-list')
			. '<a href="#" title="'.__('Php manual - Strftime datetime formats', 'amr-ical-events-list').'" '
			.'onclick="window.open(\'http://php.net/manual/en/function.strftime.php\', \'dates\', \'width=600, height=400,scrollbars=yes\')"'
			.'> '			
			.__('strftime' , 'amr-ical-events-list').'</a></p>';
		if (! isset($amr_options[$i]['format'])) echo 'No formats set';
		else
		{	$date = new DateTime();
			echo '<ul>';
			foreach ( $amr_options[$i]['format'] as $c => $v )					
			{		
				$l = str_replace(' ','', $c).$i;
				echo '<li><label for="'.$l.' ">'.__($c,'amr-ical-events-list').'</label>';
				echo '<input type="text" size="12" id="'.$l.'" name="format['.$i.']['.$c.']"';
				echo ' value="'.$v.'" /> ';
				echo amr_format_date( $v, $date); //a* amr ***/
				echo '</li>'; 
			} 
			echo '</ul>';
		} 
		echo "\n\t".'</fieldset>';
	return ;	
	}
	/* ---------------------------------------------------------------------*/
	function AmRIcal_general ($i) 
	{
	global $amr_options;
	
		echo "\n\t".'<fieldset id="general'.$i.'" class="general" >';
		if (! isset($amr_options[$i]['general'])) echo 'No general specifications set';
		else
		{	echo '<ul>';
			foreach ( $amr_options[$i]['general'] as $c => $v )					
			{		
				$l = str_replace(' ','', $c).$i;
				echo '<li><label for="'.$l.'" >'.$c.'</label>';
				echo '<input type="text" class="wide" size="20" id="'.$l.'" name="general['.$i.']['.$c.']"';
				echo ' value="'.$v.'" /></li>'; 
			} 
			echo '</ul>';
		} 
		echo "\n\t".'</fieldset>';
	return ;	
	}
	/* ---------------------------------------------------------------------*/	
	function AmRIcal_limits($i) 
	{
	global $amr_options;	
		
		echo "\n\t".'<fieldset id="limits'.$i.'" class="limits" ><legend>'. __('Define maximums:', 'amr-ical-events-list').'</legend>'; 
		if (! isset($amr_options[$i]['limit'])) echo 'No default limits set';
		else
		{	foreach ( $amr_options[$i]['limit'] as $c => $v )					
			{					
				echo '<label for="L'.$i.$c.'" >'.$c.'</label>';
				echo '<input type="text" size="2" id="L'.$i.$c.'"  name="limit['.$i.']['.$c.']"';
				echo ' value="'.$v.'" />'; 
			} 
		} 
		echo "\n\t".'</fieldset>';
	return ;	
	}
	/* ---------------------------------------------------------------------*/	
	function AmRIcal_componentsoption($i) 
	{
	global $amr_options;	
		
		echo "\n\t".'<fieldset id="components'.$i.'" class="components" ><legend>'. __('Select components to show:', 'amr-ical-events-list').'</legend>'; 
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
		echo "\n\t".'</fieldset>';
	return ;	
	}
	/* ---------------------------------------------------------------------*/	
	function AmRIcal_groupingsoption($i) 
	{
		global $amr_options;
	
		echo  "\n\t".'<fieldset id="groupings'.$i.'" class="icalgroupings">';
		echo '<legend>'. __('Define grouping:', 'amr-ical-events-list').'</legend>'; 
			foreach ( $amr_options[$i]['grouping'] as $c => $v )					
			{	$l = 'G'.$i.str_replace(' ','', $c);
				echo '<label for="'.$l.'"  >';
				echo '<input type="checkbox" id="'.$l.'" name="grouping['.$i.']['.$c.']"'. ($v ? ' checked="checked"' : '').' />';
				echo $c.' </label>';
			}
		echo "\n\t".'</fieldset> <!-- end of grouping -->';
	return;	
	}
	/* ---------------------------------------------------------------------*/	
	function AmRIcal_calpropsoption($i) 
	{
	global $amr_options;	
	global $amr_csize;
		echo "\n\t".'<fieldset id="calprop'.$i.'" class="props">';
		echo '<legend>'.__('Calendar properties' , 'amr-ical-events-list').'</legend>';
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
		echo "\n\t".'</fieldset>';
		return;	
	}
	/* ---------------------------------------------------------------------*/
	function AmRIcal_compropsoption($i) 
	{
	global $amr_options;	
	global $amr_csize;
		echo "\n".'<fieldset id="comprop'.$i.'" class="props" >';
		echo '<legend>'.__('Specify component contents:', 'amr-ical-events-list').'</legend>';

		foreach ( $amr_options[$i]['compprop'] as $si => $section )	/* s= descriptive */
		{   
			echo "\n\t".'<fieldset class="section"><legend>'.$si.'</legend>';
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
			echo "\n\t".'</fieldset> <!-- end of section -->';
		}	
		echo "\n".'</fieldset>  <!-- end of compprop -->';
		return;	
	}	
	
	/* ---------------------------------------------------------------------*/

	function AmRIcal_col_headings($i) {
	/* for component properties only */
	global $amr_options;	
	global $amr_csize;
		echo "\n\t".'<fieldset id="colhead'.$i.'" class="section">';
		echo '<legend>'.__('Column Headings','amr-ical-events-list').'</legend>';
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
		echo "\n\t".'</fieldset>';
		return;	
	}
/* ---------------------------------------------------------------------*/
	
	//styling options page
	function AmRIcal_options_style() 
	{?>
		<style type="text/css" media="screen">
			div#AmRIcal 	{margin: 0 1em;}
			div#AmRIcal ul {list-style: none; padding: 0; margin:0;}
			fieldset.alt {background: #eee;}
			div#AmRIcal fieldset {float: left; width: 40em; margin: 0.5em 0;}
			div#AmRIcal fieldset#amrglobal { width: 35em; }
			div#AmRIcal fieldset#submit {float: left; width: 20em; margin: 0.5em 0;}
			div#AmRIcal fieldset#ListTypes {width: 350em; margin-bottom: 1em; }	
			div#AmRIcal fieldset#ListTypes fieldset { padding: 0 0.5em; }				
			
			div#AmRIcal legend {font-weight: bold; }
			div#AmRIcal fieldset.layout legend {font-weight: normal; }
			
			div#AmRIcal input {margin-left: 1em; padding: 0.2em 0 0.2em 0; }
			div#AmRIcal fieldset.layout input {margin: 0; padding: 0;}
			div#AmRIcal fieldset#submit input {padding: 0.4em;}
			div#AmRIcal fieldset#ListTypes fieldset.formats input { } 
			
			div#AmRIcal fieldset.limits label  {margin-left: 1em; }
			div#AmRIcal fieldset.general label {margin-left: 1em; }

			div#AmRIcal fieldset#amrglobal label {  display: block;}
			div#AmRIcal label.Column {margin-left: 9em; }
			div#AmRIcal fieldset#ListTypes fieldset.formats label 
			{	margin-left: 1em; 
			}
			div#AmRIcal fieldset.layout label 
			{	font-size: xx-small; 
				position:relative; 
				top: -2.5em;
				right: -4em;
			}
			div#AmRIcal input.wide {
            width: 90%;
			}

		</style>
		<?php
	}
	/* ---------------------------------------------------------------------*/
	function amr_request_acknowledgement () {
	?>
	<p style="border-width: 1px;"><?php _e('Significant effort goes into these plugins to ensure that they <strong>work straightaway</strong> with minimal effort, are easy to use but <strong>very configurable</strong>, that they are <strong>well tested</strong>,that they produce <strong>valid html and css</strong> both at the front and admin area. If you wish to remove the credit link or using the plugin commercially, then please donate.','amr-ical-events-list'); ?>
	<span style="font-size: x-large;"><a href="http://webdesign.anmari.com/web-tools/donate/"><?php
	_e('Donate','amr-ical-events-list');?></a></span>&nbsp;&nbsp;&nbsp;&nbsp;
	<a href='http://wordpress.org/tags/amr-ical-events-list'><?php _e('Support at Wordpress');?></a>
	&nbsp;&nbsp;&nbsp;&nbsp;
	<a href="http://icalevents.anmari.com"><?php _e('Plugin website');?></a></p>

	<?php
	}
/* ---------------------------------------------------------------------*/
	function amr_get_files ($dir, $string) {
	$dh  = opendir($dir);
	while ($filename = readdir($dh)) {
		if (stristr ($filename, $string)) 
		$files[] = $filename;
		}
	return ($files);
	}
	/* -------------------------------------------------------------------------------------------------------------*/
	function amr_check_edit_file() {
	/* check if there is an own style file, if not, then copy it over */
	  if (file_exists(ICAL_EDITSTYLEFILE)) return (true);
	  else {
		$c = copy (ICALSTYLEFILE, ICAL_EDITSTYLEFILE);
		echo '</ br><h3>'.__('Custom css file does not exist!').'</h3></ br>';		
		echo '</ br>'.sprintf(__('Copying %s1 to %s2 to allow custom css'),ICALSTYLEFILE,ICAL_EDITSTYLEFILE).'</ br>';
		return ($c);
		}
	}
	/* ---------------------------------------------------------------------*/
	function AmRIcal_option_page()  {
	global $amr_csize;
	global 
		$amr_calprop,
		$amr_formats,
		$amr_limits,
		$amr_compprop,
		$amr_groupings,
		$amr_components,
		$amr_options,
		$amr_globaltz;
	
	$nonce = wp_create_nonce('amr_ical'); /* used for security to verify that any action request comes from this plugin's forms */
	if (isset($_REQUEST['uninstall'])  OR isset($_REQUEST['reallyuninstall']))  { /*  */
		amr_ical_check_uninstall(); 	
		return;
	}
	else {
		amr_check_edit_file();

		$amr_options = amr_getset_options(false);	/* options will be set to defaults here if not already existing */
		if($_POST['action'] == "save") /* Validate the input and save */
			{ if (! amr_ical_validate_options() ) {echo '<h2>Error validating input</h2>';}	}	
		echo '<h2>'.__('AmR iCal Events List ', 'amr-ical-events-list')
			.AMR_ICAL_VERSION.'</h2>'.AMR_NL;
		amr_request_acknowledgement();
				?>
		<div class="wrap" id="AmRIcal"> 					
		<?php
			if (isset($amr_globaltz)) {
				echo '<p>'.__('Timezone for date and time calculations is ','amr-ical-events-list')
				.'<strong><a href="'.WP_SITEURL.'/wp-admin/options-general.php" > '. timezone_name_get($amr_globaltz)
				.' </a></strong></p>';
				}	
//		else /* when wordpress fixes the daylight saving timezone issue, then we can change this */
//			echo '<strong>'.__('No reliable timezone - Timezone of first calendar will be used ','amr-ical-events-list').'</strong>';?>
			<form method="post" action="<?php htmlentities($_SERVER['PHP_SELF']); ?>">
				<?php  wp_nonce_field('amr_ical'); /* outputs hidden field */?>
				
				<fieldset id="amrglobal"><legend><?php _e('AmR ICal Global Options', 'amr-ical-events-list'); ?></legend>
					<label for="no_types"><?php _e('Number of Ical Lists:', 'amr-ical-events-list'); ?>
					<input type="text" size="2" id="no_types" name="no_types" value="<?php echo $amr_options['no_types'];  ?>" />
					</label>
					<label for="ngiyabonga">
					<input type="checkbox" id="ngiyabonga" name="ngiyabonga" value="ngiyabonga" 
					<?php if (isset($amr_options['ngiyabonga']) and ($amr_options['ngiyabonga']))  {echo 'checked="checked"';}
					?>/>
<?php 				_e('Donation made', 'amr-ical-events-list'); ?></label>
					<label for="own_css">
					<input type="checkbox" id="own_css" name="own_css" value="own_css" 
					<?php if (isset($amr_options['own_css']) and ($amr_options['own_css']))  {echo 'checked="checked"';}
					?>/><?php _e(' Do not generate css', 'amr-ical-events-list'); ?>
					</label>
					<label for="css_file"><?php _e('Css file to generate from plugin directory', 'amr-ical-events-list'); ?>
					<select id="css_file" name="css_file" <?php
						$dir = WP_PLUGIN_DIR.'/amr-ical-events-list';
						$files = amr_get_files($dir, 'css');
						if (empty ($files)) echo AMR_NL.' <option value=""> No css files found in plugin directory '.$dir.' '.$files.'</option>';
						else foreach ($files as $ifile => $file) {
							echo AMR_NL.' <option value="'.$file.'"';
							if (isset($amr_options['css_file']) and ($amr_options['css_file'] == $file)) echo ' selected="selected" ';
							echo '>'.$file.'</option>';
						}					
						?>
					</select>
					<a href="plugin-editor.php" title="<?php
					_e('Go to Plugin Editor, select this plugin and scroll to the file','amr-ical-events-list');
					echo '" >';
					_e("Edit",'amr-ical-events-list');?></a>
					</label>

					
					<label for="noeventsmessage">		
					<?php _e('Message if no events found: ', 'amr-ical-events-list');?>
					</label>
					<input class="wide" type="text" id="noeventsmessage" name="noeventsmessage" 
					<?php if (isset($amr_options['noeventsmessage']) and ($amr_options['noeventsmessage']))  
						{echo 'value="'.$amr_options['noeventsmessage'].'"';}?>/> 
				</fieldset>
				<fieldset id="submit">
					<input type="hidden" name="action" value="save" />
					<input type="submit" value="<?php _e('Update', 'amr-ical-events-list') ?>" />
					<input type="submit" name="reset" value="<?php _e('Reset', 'amr-ical-events-list') ?>" />
					<input type="submit" name="uninstall" value="<?php _e('Uninstall', 'amr-ical-events-list') ?>" />		
				</fieldset>
				<fieldset id="ListTypes">
				<?php 
				$alt = true;
				for ($i = 1; $i <= $amr_options['no_types']; $i++) 
				{ 
					echo "\n\t".'<fieldset id="List'.$i.'"' ;
					if ($alt) { $alt=false; echo ' class="alt">';}
					else { $alt=true; echo '>';}
					echo '<legend>'.
						 __('List Type ', 'amr-ical-events-list').$i
						.'</legend>'; 
					if (!(isset($amr_options[$i])) )  echo 'Error in saved options';							
					else 
					{	AmRIcal_general($i);	
						AmRIcal_limits($i);	
						AmRIcal_formats ($i);
						AmRIcal_componentsoption($i);			
						AmRIcal_groupingsoption($i); 
						AmRIcal_calpropsoption($i);
						AmRIcal_col_headings($i);
						AmRIcal_compropsoption($i); 
					}	
					echo "\n\t".'</fieldset>  <!-- end of list type -->';	
				}
				echo "\n".'</fieldset> <!-- end of list types -->';	
?>
			</form>
		</div>
		<?php
		}
	}	//end AmRIcal_option_page
?>