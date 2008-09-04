<?php
/* This is the amr ical wordpress admin section file */

$Version = '2.0';

	function allowed_html ($s)
	/* string any unallowed html from the before and after fields 
	strip tags tries to return a string with all HTML and PHP tags stripped	*/
	{return strip_tags($s, '<p><br /><hr /><h2><h3><<h4><h5><h6><strong><em>');
	}


/* -------------------------------------------------------------------------------------------------------------*/
function amr_ical_list_widget_control()
{
	global $amr_options;
	
    if (!isset($amr_options)) $amr_options = amr_getset_options (false);
	
	if (!(isset($amr_options['title']))) 
		$title = __('Upcoming Events', 'AmRIcalList');
	else     
		$title = wp_specialchars($amr_options['title']);
	if (!(isset($amr_options['urls'])))	
		$urls = get_bloginfo('wpurl').'/wp-content/plugins/amr-ical-events-list/basic.ics'; 
	else
		$urls =  wp_specialchars($amr_options['urls']);		
	if (!(isset($amr_options['listtype'])))	
		$listtype = 4;
	else
		$listtype = wp_specialchars($amr_options['listtype']);
	if (!(isset($amr_options['limit'])))	
		$limit = 5;
	else
		$limit= wp_specialchars($amr_options['limit']);	
	
	$moreurl = 	wp_specialchars($amr_options['moreurl']);
		   
    if ( $_POST['amr_ical_submit'] ) 
    {	/*  should we validate these a bit  - or is admin, should know what they are doing */
		$amr_options['title'] = strip_tags(stripslashes($_POST['amr_ical_title']));
		$amr_options['listtype'] = strip_tags(stripslashes($_POST['amr_list_type']));
		$amr_options['limit'] = strip_tags(stripslashes($_POST['amr_limit']));
		$amr_options['moreurl'] = strip_tags(stripslashes($_POST['amr_moreurl']));
		$amr_options['urls'] = strip_tags(stripslashes($_POST['amr_ical_urls']));
		update_option('AmRiCalEventList', $amr_options);
    }

?>
	<input type="hidden" id="amr_ical_submit" name="amr_ical_submit" value="1" />
	<p><label for="amr_ical_title"><?php _e('Title'); ?> 
	<input style="width: 230px;" id="amr_ical_title" name="amr_ical_title" type="text" value="<?php echo $title; ?>" /></label></p>
	<p><label for="amr_list_type"><?php _e('List Type from plugin settings'); ?> 
	<input id="amr_list_type" name="amr_list_type" type="text" style="width: 25px;"  value="<?php echo $listtype; ?>" /></label></p>
	<p><label for="amr_limit"><?php _e('Number of Events'); ?> 
	<input id="amr_limit" name="amr_limit" type="text" style="width: 25px;"  value="<?php echo $limit; ?>" /></label></p>
	<p><label for="amr_moreurl"><?php _e('Link to Calendar'); ?> 
	<input id="amr_moreurl" name="amr_moreurl" type="text" style="width: 240px;" 
	value="<?php echo $moreurl; ?>" /></label></p>
	<p><label for="amr_ical_urls"><?php _e('Urls'); ?> </label>
	<textarea cols="25" rows="4" id="amr_ical_urls" name="amr_ical_urls" ><?php
		echo $urls; ?></textarea></p>
	
<?php
}

/* -------------------------------------------------------------------------------------------------------------*/
	
	function AmRIcal_add_options_panel() {
	/* add the options page at admin level of access */
		add_options_page('AmR ICal list', 'AmR ICal list', 7, 'manage_amr_ical', 'AmRIcal_option_page');
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
		
		if (isset ($_POST['reset']))  { echo '<h2>Resetting</h2>'; amr_getset_options (true); }
		else
		{	/* check if no types updated, do not process other stuff if it has been  */

			if (isset($_POST["no_types"]) && (!($_POST["no_types"]== $amr_options['no_types'])))
			{		
				$int_ok = (filter_var($_POST["no_types"], FILTER_VALIDATE_INT, 
					array("options" => array("min_range"=>1, "max_range"=>10))));
				if ($int_ok) 
				{	for ($i = $amr_options['no_types']+1; $i <= $int_ok; $i++)
					{	
						$amr_options[$i] = $amr_options[1];
					}
					$amr_options['no_types'] =  $int_ok;	
						
				}
			}
			else
			{
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
				if (isset($_POST['component']))  
					for ($i = 1; $i <= $amr_options['no_types']; $i++)			
					{	if (is_array($_POST['component'][$i])) 
						{	foreach ($_POST['component'][$i] as $c => $v)
							{ $amr_options[$i]['component'][$c] = $_POST['component'][$i][$c] ? isset($_POST['component'][$i][$c]): false;
							}
						}
					}
				if (isset($_POST['grouping']))  
					for ($i = 1; $i <= $amr_options['no_types']; $i++)			
					{	if (is_array($_POST['grouping'][$i])) 
						{	foreach ($_POST['grouping'][$i] as $c => $v)
							{ $amr_options[$i]['grouping'][$c] = $_POST['grouping'][$i][$c] ? isset($_POST['grouping'][$i][$c]): false;
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
									if (filter_var($pv, FILTER_VALIDATE_INT, 
										array("options" => array("min_range"=>1, "max_range"=>20))))
										$amr_options[$i]['calprop'][$c][$p]= $pv;
									else 	$amr_options[$i]['calprop'][$c][$p]= 0;
									break;
								case 'Order':
									if (filter_var($pv, FILTER_VALIDATE_INT, 
										array("options" => array("min_range"=>1, "max_range"=>99))))
										$amr_options[$i]['calprop'][$c][$p] = $pv;break;
								case 'Before': $amr_options[$i]['calprop'][$c][$p] = allowed_html($pv);
									break;
								case 'After': $amr_options[$i]['calprop'][$c][$p] = allowed_html($pv);
									break;
								endswitch;
							}
						}
					}
					else echo 'Error in form - calprop array not found';
					
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
										if (filter_var($pv, FILTER_VALIDATE_INT, 
											array("options" => array("min_range"=>1, "max_range"=>20))))
											$amr_options[$i]['compprop'][$si][$c][$p]= $pv;
										else 	$amr_options[$i]['compprop'][$si][$c][$p]= 0;
										break;
									case 'Order':
										if (filter_var($pv, FILTER_VALIDATE_INT, 
											array("options" => array("min_range"=>1, "max_range"=>99))))
											$amr_options[$i]['compprop'][$si][$c][$p] = $pv; break;
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
		}
		
		if ($opt = get_option("AmRiCalEventList"))
			{ 	if (update_option(  "AmRiCalEventList", $amr_options))
				{ _e("Options  <strong>Updated</strong>. ");	}
				else echo "<br />Error updating";
			}
		else {add_option("AmRiCalEventList", $amr_options);}
		
		return (true);	
		
	}
	
	function AmRIcal_formats ($i) 
	{
	global $amr_options;	
		echo "\n\t".'<fieldset id="formats'.$i.'" class="formats" >';
		echo '<legend>'. __('Define date and time formats:', 'AmRIcalList').'</legend>'; 
		if (! isset($amr_options[$i]['format'])) echo 'No formats set';
		else
		{	$date = new DateTime();
			echo '<ul>';
			foreach ( $amr_options[$i]['format'] as $c => $v )					
			{		
				$l = str_replace(' ','', $c).$i;
				echo '<li><label for="'.$l.' ">'.$c.'</label>';
				echo '<input type="text" size="12" id="'.$l.'" name="format['.$i.']['.$c.']"';
				echo ' value="'.$v.'" /> ';
				echo format_date( $v, $date); //a* amr ***/
				echo '</li>'; 
			} 
			echo '</ul>';
		} 
		echo "\n\t".'</fieldset>';
	return ;	
	}

	function AmRIcal_general ($i) 
	{
	global $amr_options;	
		echo "\n\t".'<fieldset id="general'.$i.'" class="general" >';
//		echo '<legend>'. __('Define general options:', 'AmRIcalList').'</legend>'; 
		if (! isset($amr_options[$i]['general'])) echo 'No general specifications set';
		else
		{	echo '<ul>';
			foreach ( $amr_options[$i]['general'] as $c => $v )					
			{		
				$l = str_replace(' ','', $c).$i;
				echo '<li><label for="'.$l.'" >'.$c.'</label>';
				echo '<input type="text" size="70" id="'.$l.'" name="general['.$i.']['.$c.']"';
				echo ' value="'.$v.'" /></li>'; 
			} 
			echo '</ul>';
		} 
		echo "\n\t".'</fieldset>';
	return ;	
	}
	
	function AmRIcal_limits($i) 
	{
	global $amr_options;	
		
		echo "\n\t".'<fieldset id="limits'.$i.'" class="limits" ><legend>'. __('Define maximums:', 'AmRIcalList').'</legend>'; 
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
	
	function AmRIcal_componentsoption($i) 
	{
	global $amr_options;	
		
		echo "\n\t".'<fieldset id="components'.$i.'" class="components" ><legend>'. __('Select components to show:', 'AmRIcalList').'</legend>'; 
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
	
	function AmRIcal_groupingsoption($i) 
	{
		global $amr_options;
	
		echo  "\n\t".'<fieldset id="groupings'.$i.'" class="icalgroupings">';
		echo '<legend>'. __('Define grouping:', 'AmRIcalList').'</legend>'; 
			foreach ( $amr_options[$i]['grouping'] as $c => $v )					
			{	$l = 'G'.$i.str_replace(' ','', $c);
				echo '<label for="'.$l.'"  >';
				echo '<input type="checkbox" id="'.$l.'" name="grouping['.$i.']['.$c.']"'. ($v ? ' checked="checked"' : '').' />';
				echo $c.' </label>';
			}
		echo "\n\t".'</fieldset> <!-- end of grouping -->';
	return;	
	}
	
	function AmRIcal_calpropsoption($i) 
	{
	global $amr_options;	
	global $amr_csize;
		echo "\n\t".'<fieldset id="calprop'.$i.'" class="props">';
		echo '<legend>'.__('Calendar properties', 'AmRIcalList').'</legend>';
		//echo col_headings(); 
		foreach ( $amr_options[$i]['calprop'] as $c => $v )					
		{ 	
			echo "\n\t\t".'<fieldset class="layout"><legend>'.$c.'</legend>';
			foreach ( $v as $si => $sv )  /* for each specification */
			{	echo '<label class="'.$si.'" for="'.$c.$si.$i.'" >'.$si.'</label>'
					.'<input type="text" size="'.$amr_csize[$si].'"  class="'.$si.'"  id="'.$c.$si.$i
					.'"  name="'.'CalP['.$i.']['.$c.']['.$si.']"  value= "'.htmlspecialchars($sv).'"  />'; 
			}
			echo "\n\t\t".'</fieldset>';
		}	
		echo "\n\t".'</fieldset>';
		return;	
	}

	function AmRIcal_compropsoption($i) 
	{
	global $amr_options;	
	global $amr_csize;
		echo "\n".'<fieldset id="comprop'.$i.'" class="props" >';
		echo '<legend>'.__('Specify component contents:', 'AmRIcalList').'</legend>';
		//echo col_headings(); 
		//var_dump($amr_options[$i]['compprop']);
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
	
	function AmRIcal_option_page() 
	{
	global $amr_csize;
	global 
		$amr_calprop,
		$amr_formats,
		$amr_limits,
		$amr_compprop,
		$amr_groupings,
		$amr_components,
		$amr_options;
	
	if (!isset($amr_options)) $amr_options = amr_getset_options(false);	/* options will be set to defaults here if not already existing */
	if($_POST['action'] == "save") /* Validate the input and save */
		{ if (! amr_ical_validate_options() ) {echo '<h2>Error validating input</h2>';}	}

	?>
	<div id="AmRIcal">
		<form method="post" action="<?php htmlentities($_SERVER['PHP_SELF']); ?>">
			<fieldset id="amrglobal"><legend><?php _e('AmR ICal Global Options', 'AmRIcalList'); ?></legend>
				<label for="no_types"><?php _e('Number of Ical Lists:', 'AmRIcalList'); ?></label>
				<input type="text" size="2" id="no_types" name="no_types" value="<?php echo $amr_options['no_types'];  ?>" />
			</fieldset>
			<fieldset id="submit">
				<input type="hidden" name="action" value="save" />
				<input type="submit" value="<?php _e('Update Options', 'AmRIcalList') ?>" />
				<input type="submit" name="reset" value="<?php _e('Reset to Default', 'AmRIcalList') ?>" />
			</fieldset>
			<fieldset id="ListTypes">
			<?php 
			$alt = true;
			for ($i = 1; $i <= $amr_options['no_types']; $i++) 
			{ 
				echo "\n\t".'<fieldset id="List'.$i.'"' ;
				if ($alt) { $alt=false; echo ' class="alt">';}
				else { $alt=true; echo '>';}
				echo '<legend>'
					.sprintf( __('List Type %d','AmRIcalList'),$i).' '.$amr_options[$i]['Name']
					.'</legend>'; 
				if (!(isset($amr_options[$i])) )  echo 'Error in saved options';							
				else 
				{	AmRIcal_general($i);	
					AmRIcal_limits($i);	
					AmRIcal_formats ($i);
					AmRIcal_componentsoption($i);			
					AmRIcal_groupingsoption($i); 
					AmRIcal_calpropsoption($i);
					AmRIcal_compropsoption($i); 
				}	
				echo "\n\t".'</fieldset>  <!-- end of list type -->';	
			}
			echo "\n".'</fieldset> <!-- end of list types -->';	
?>
		</form>
	</div>
	<?php
	}	//end AmRIcal_option_page
	
	//styling options page
	function AmRIcal_options_style() 
	{?>
		<style type="text/css" media="screen">
			div#AmRIcal 	{margin: 0 1em;}
			div#AmRIcal ul {list-style: none; padding: 0; margin:0;}
			fieldset.alt {background: #eee;}
			div#AmRIcal fieldset {float: left; width: 40em; margin: 0.5em 0;}
			div#AmRIcal fieldset#amrglobal {width: 20em; }
			div#AmRIcal fieldset#submit {float: left; width: 30em; margin: 0.5em 0;}
			div#AmRIcal fieldset#ListTypes {width: 400em; margin-bottom: 1em; }	
			div#AmRIcal fieldset#ListTypes fieldset { padding: 0 0.5em; }				
			
			div#AmRIcal legend {font-weight: bold; }
			div#AmRIcal fieldset.layout legend {font-weight: normal; }
			
			div#AmRIcal input {margin-left: 1em; padding: 0;}
			div#AmRIcal fieldset.layout input {margin: 0; padding: 0;}
			div#AmRIcal fieldset#submit input {padding: 0.4em;}
			div#AmRIcal fieldset#ListTypes fieldset.formats input { } 
			
			div#AmRIcal fieldset.limits label  {margin-left: 1em; }
			div#AmRIcal fieldset.general label {margin-left: 1em; }


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

		</style>
		<?php
	}
	
?>