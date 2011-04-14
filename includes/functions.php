<?php //comonly useful functions
if (!function_exists('esc_textarea') ) {
	function esc_textarea( $text ) {
	$safe_text = htmlspecialchars( $text, ENT_QUOTES );
	}
}	
// ----------------------------------------------------------------------------------------
 function amr_check_for_wpml_lang_parameter ($link) {
 	if (isset($_REQUEST['lang'])) {
		$lang = $_REQUEST['lang'];
		$link = remove_query_arg( 'lang', $link );  //is there a wpml bug ? or wp bug, we are getting lang twice 
		$link = add_query_arg( 'lang', $lang, $link );
		}
	return ($link);
}
// ----------------------------------------------------------------------------------------

function array_merge_recursive_distinct ( array &$array1, array &$array2 ) { /* array 2 will replace array 1*/
  $merged = $array1;

  foreach ( $array2 as $key => &$value )  {
 if ( is_array ( $value ) && isset ( $merged [$key] ) && is_array ( $merged [$key] ) ) {
   $merged [$key] = array_merge_recursive_distinct ( $merged [$key], $value );
 }
 else {
   $merged [$key] = $value;
 }
  }
  return $merged;
}
/* ---------------------------------------------------------------------*/
function amr_clean_link() { /* get cleaned up version of current url  remove other parameters */

	$link = remove_query_arg(array(
	'months',
	'hours',
	'start',
	'startoffset',
	'hoursoffset',
	'eventoffset',
	'monthsoffset'));

	return ($link);
}
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
	/* ---------------------------------------------------------------------- */
function  amr_make_sticky_url($url) { 
	$page_id = url_to_postid($url);
	
	if (!$page_id) return false ;
	else {
		$sticky_url  = add_query_arg('page_id',$page_id,get_bloginfo('url'));
		return( $sticky_url) ;
	}	
}	
/* ---------------------------------------------------------------------- */
function  amr_invalid_url() { 
?><div class="error fade"><?php	_e('Invalid Url','amr-ical-events-list');?></div><?php
}
/* ---------------------------------------------------------------------- */
function  amr_invalid_file() { 
?><div class="error fade"><?php	_e('Invalid Url','amr-ical-events-list');?></div><?php
}
/* --------------------------------------------------  */
function amr_click_and_trim($text) { /* Copy code from make_clickable so we can trimthe text */

	$text = make_clickable($text);
	amr_trim_url($text);
	return $text;
}
/* --------------------------------------------------  */
function amr_trim_url(&$ret) { /* trim urls longer than 30 chars, but not if the link text does not have http */
	$links = explode('<a', $ret);
    $countlinks = count($links);

	for ($i = 0; $i < $countlinks; $i++) {
		$link    = $links[$i]; 
		$link    = (preg_match('#(.*)(href=")#is', $link)) ? '<a' . $link : $link;
		$begin   = strpos($link, '>');
		
		if ($begin) {
		
			$begin   = $begin + 1;

			$end     = strpos($link, '<', $begin);

			$length  = $end - $begin;

			$urlname = substr($link, $begin, $length); 
			
			$trimmed = (strlen($urlname) > 50 && preg_match('#^(http://|ftp://|www\.)#is', $urlname)) ? substr_replace($urlname, '.....', 30, -5) : $urlname;
			$trimmed = str_replace('http://','',$trimmed);

			$ret     = str_replace('>' . $urlname . '<', '>' . $trimmed . '<', $ret);
		}
	}
   	return ($ret);
}

/* ---------------------------------------------------------------------*/

function amr_request_acknowledgement () {
?><div class="postbox" style="padding:1em 2em; width: 600px;">
	<p style="border-width: 1px;"><?php _e('I try to make these plugins work <strong>"out of the box"</strong> with minimal effort; that they be easy to use but <strong>very configurable</strong>; <strong>well tested</strong>; with <strong>valid html and css</strong> both at the front and admin area.','amr-ical-events-list');?> <?php
_e('If you have a feature request, please do let me know. ','amr-ical-events-list');
?></p><p><b><?php _e('To edit events in wordpress:','amr-ical-events-list'); ?> <a href="http://icalevents.anmari.com" >icalevents.anmari.com</a></b>
</div>
<?php
}
?>