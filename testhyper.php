<?php function hyperlink($text) 
{
    // match protocol://address/path/
    $text = ereg_replace("[a-zA-Z]+://([.]?[a-zA-Z0-9_/-?&%])*", "<a href=\"\\0\">\\0</a>", $text);
    
    // match www.something
    $text = ereg_replace("(^| )(www([.]?[a-zA-Z0-9_/-?&%])*)", "\\1<a href=\"http://\\2\">\\2</a>", $text);
	
    // return $text
    return $text;
}



$text = '   http://www.harpers.org/ThatsTheMatterWithKansas.html <a href="http://www.anmari.com">test</a>
      http://www2.ljworld.com/news/2005/dec/24/mayor_says_its_time_nonsense/?city_local
      http://en.wikipedia.org/wiki/International_Dadaism_Month www.anmari.com';

  echo '<br 1>'. ereg_replace("[a-zA-Z]+://([.]?[a-zA-Z0-9_/-?&%])*", "<a href=\"\\0\">\\0</a>", $text);
  echo '<br 2>'. ereg_replace("(^| )(www([.]?[a-zA-Z0-9_/-?&%])*)", "\\1<a href=\"http://\\2\">\\2</a>", $text);

?>