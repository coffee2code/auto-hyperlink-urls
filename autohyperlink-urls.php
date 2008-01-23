<?php
/*
Plugin Name: Auto-hyperlink URLs
Version: 2.1
Plugin URI: http://www.coffee2code.com/wp-plugins/
Author: Scott Reilly
Author URI: http://www.coffee2code.com
Description: Auto-hyperlink text URLs in post content and comment text to the URL they reference.

=>> Visit the plugin's homepage for more information and latest updates  <<=

Installation:

1. Download the file http://www.coffee2code.com/wp-plugins/autohyperlink-urls.zip and unzip it into your /wp-content/plugins/ directory.
-OR-
Copy and paste the the code ( http://www.coffee2code.com/wp-plugins/autohyperlink-urls.phps ) into a file called autohyperlink-urls.php, and put 
that file into your /wp-content/plugins/ directory.
2. Optional: Modify any configuration options (presented as defaults for the arguments to the function c2c_hyperlink_urls())
3. Activate the plugin from your WordPress admin 'Plugins' page.


Notes:

This plugin seeks to address certain shortcomings with WordPress's default auto-hyperlinking function (make_clickable()) (which itself was borrowed 
from phpBB).  This tweaks the pattern matching expressions to prevent inappropriate adjacent characters from becoming part of the link (such as 
a trailing period when a link ends a sentence, links that are parenthesized or braced, comma-separated, etc) and it prevents invalid text from becoming 
a mailto: link (i.e. smart@ss) or for invalid URIs (i.e. http://blah) from becoming links.  In addition, this plugin adds configurability to the 
auto-hyperlinker such that you can configure:

- If you want text URLs to only show the hostname
- If you want text URLs truncated after N characters
- If you want auto-hyperlinked URLs to open in new browser window or not
- The text to come before and after the link text for truncated links

This plugin will recognize any protocol-specified URI (http|https|ftp|news)://, etc, as well as e-mail addresses.  It also adds the new ability to 
recognize Class B domain references (i.e. "somesite.net", not just domains prepended with "www.") as valid links (i.e. "wordpress.org" would now get auto-hyperlinked)

Example (when running with default configuration):

"wordpress.org"
=> <a href="http://wordpress.org" title="http://wordpress.org" target="_blank" rel="nofollow">wordpress.org</a>

"http://www.cnn.com"
=> <a href="http://www.cnn.com" title"http://www.cnn.com" target="_blank" rel="nofollow">http://www.cnn.com</a>

*/

/*
Copyright (c) 2004-2006 by Scott Reilly (aka coffee2code)

Permission is hereby granted, free of charge, to any person obtaining a copy of this software and associated documentation 
files (the "Software"), to deal in the Software without restriction, including without limitation the rights to use, copy, 
modify, merge, publish, distribute, sublicense, and/or sell copies of the Software, and to permit persons to whom the 
Software is furnished to do so, subject to the following conditions:

The above copyright notice and this permission notice shall be included in all copies or substantial portions of the Software.

THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES
OF MERCHANTABILITY, FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE AUTHORS OR COPYRIGHT HOLDERS BE
LIABLE FOR ANY CLAIM, DAMAGES OR OTHER LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM, OUT OF OR
IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE SOFTWARE.
*/

// Don't set the default values for the function arguments here; do so for the hyperlink_urls function
function c2c_truncate_link ($url, $mode='0', $trunc_before='', $trunc_after='...') {
	if (1 == $mode) {
                $url = preg_replace("/(([a-z]+?):\\/\\/[A-Za-z0-9\-\.]+).*/i", "$1", $url);
                $url = $trunc_before . preg_replace("/([A-Za-z0-9\-\.]+\.(com|org|net|gov|edu|us|info|biz|ws|name|tv)).*/i", "$1", $url) . $trunc_after;
        } elseif (($mode > 10) && (strlen($url) > $mode)) {
                $url = $trunc_before . substr($url, 0, $mode) . $trunc_after;
        }
        return $url;
} //end c2c_truncate_link()

// mode: 0=full url; 1=host-only ;11+=number of characters to truncate after
function c2c_hyperlink_urls ($text, $mode='0', $trunc_before='', $trunc_after='...', $open_in_new_window=true, $nofollow=true) {
	$text = ' ' . $text . ' ';
	$new_win_txt = ($open_in_new_window) ? ' target="_blank"' : '';
	$nofollow_txt = ($nofollow) ? ' rel="nofollow"' : '';
        
	// Hyperlink anything with an explicit protocol
	$text = preg_replace("#([\s{}\(\)\[\]])(([a-z]+?)://([A-Za-z_0-9\-]+\.([^\s{}\(\)\[\]]+[^\s,\.\;{}\(\)\[\]])))#ie",
		"'$1<a href=\"$2\" title=\"$2\"$new_win_txt$nofollow_txt>' . c2c_truncate_link(\"$4\", \"$mode\", \"$trunc_before\", \"$trunc_after\") . '</a>'",
                $text);

	// Hyperlink Class B domains *.(com|org|net|gov|edu|us|info|biz|ws|name|tv)(/*)
	$text = preg_replace("#([\s{}\(\)\[\]])([A-Za-z0-9\-\.]+[A-Za-z0-9\-])\.(com|org|net|gov|edu|us|info|biz|ws|name|tv)((?:/[^\s{}\(\)\[\]]*[^\.,\s{}\(\)\[\]]?)?)#ie",
		"'$1<a href=\"http://$2.$3$4\" title=\"http://$2.$3$4\"$new_win_txt$nofollow_txt>' . c2c_truncate_link(\"$2.$3$4\", \"$mode\", \"$trunc_before\", \"$trunc_after\") . '</a>'",
		$text);

	// Hyperlink e-mail addresses
	$text = preg_replace("#([\s{}\(\)\[\]])([A-Za-z0-9\-_\.]+?)@([^\s,{}\(\)\[\]]+\.[^\s.,{}\(\)\[\]]+)#ie",
		"'$1<a href=\"mailto:$2@$3\" title=\"mailto:$2@$3\">' . c2c_truncate_link(\"$2@$3\", \"$mode\", \"$trunc_before\", \"$trunc_after\") . '</a>'",
		$text);

	return substr($text,1,strlen($text)-2);
} //end c2c_hyperlink_urls()

add_filter('the_content', 'c2c_hyperlink_urls', 9);
remove_filter('comment_text', 'make_clickable');
add_filter('comment_text', 'c2c_hyperlink_urls', 9);

?>