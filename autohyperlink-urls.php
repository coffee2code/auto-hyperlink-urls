<?php
/*
Plugin Name: Auto-hyperlink URLs
Version: 3.5
Plugin URI: http://coffee2code.com/wp-plugins/autohyperlink-urls
Author: Scott Reilly
Author URI: http://coffee2code.com
Description: Automatically hyperlink text URLs and email addresses originally written only as plaintext.

This plugin seeks to replace and extend WordPress's default auto-hyperlinking function.  This plugin tweaks the
pattern matching expressions to prevent inappropriate adjacent characters from becoming part of the link (such as a 
trailing period when a link ends a sentence, links that are parenthesized or braced, comma-separated, etc) and it prevents
invalid text from becoming a mailto: link (i.e. smart@ss) or for invalid URIs (i.e. http://blah) from becoming links.  In 
addition, this plugin adds configurability to the auto-hyperlinker such that you can configure:

* If text URLs should only show the hostname
* If text URLs should be truncated after N characters
* If auto-hyperlinked URLs should open in new browser window or not
* If the protocol (i.e. "http://") should to be stripped for displayed links
* The text to come before and after the link text for truncated links
* If rel="nofollow" should be supported
* If there should be support additional domain extensions not already configured into the plugin

This plugin will recognize any protocol-specified URI (http|https|ftp|news)://, etc, as well as e-mail addresses.  
It also adds the new ability to recognize Class B domain references (i.e. "somesite.net", not just domains prepended 
with "www.") as valid links (i.e. "wordpress.org" would now get auto-hyperlinked)

Known issue:
* It will not hyperlink URLs that are immediately single- or double-quoted, i.e. 'http://example.com' or "http://example.com"
	
Compatible with WordPress 2.6+, 2.7+, 2.8+.

=>> Read the accompanying readme.txt file for more information.  Also, visit the plugin's homepage
=>> for more information and the latest updates


Installation:

1. Download the file http://coffee2code.com/wp-plugins/autohyperlink-urls.zip and unzip it into your 
/wp-content/plugins/ directory.
2. Activate the plugin through the 'Plugins' admin menu in WordPress
3. (optional) Go to the Settings -> Autohyperlink admin settings page (which you can also get to via the Settings link next to
the plugin on the Manage Plugins page) and customize the settings.


Example (when running with default configuration):

"wordpress.org"
=> <a href="http://wordpress.org" title="http://wordpress.org" target="_blank" class="autohyperlink">wordpress.org</a>

"http://www.cnn.com"
=> <a href="http://www.cnn.com" title"http://www.cnn.com" target="_blank" class="autohyperlink">www.cnn.com</a>

"person@example.com"
=> <a href="mailto:person@example.com" title="mailto:person@example.com" class="autohyperlink">person@example.com</a>

*/

/*
Copyright (c) 2004-2009 by Scott Reilly (aka coffee2code)

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

if ( !class_exists('AutoHyperlinkURLs') ) :

class AutoHyperlinkURLs {
	var $admin_options_name = 'c2c_autohyperlink_urls';
	var $nonce_field = 'update-autohyperlink_urls';
	var $show_admin = true;	// Change this to false if you don't want the plugin's admin page shown.
	var $config = array();
	var $options = array(); // Don't use this directly
	var $plugin_basename = '';

	function AutoHyperlinkURLs() {
		$this->plugin_basename = plugin_basename(__FILE__);
		add_action('admin_menu', array(&$this, 'admin_menu'));
		add_filter('the_content', array(&$this, 'hyperlink_urls'), 9);
		
		$this->config = array(
			'hyperlink_comments' => array('input' => 'checkbox', 'default' => true,
					'label' => 'Auto-hyperlink comments?'),
			'hyperlink_emails' => array('input' => 'checkbox', 'default' => true, 
					'label' => 'Hyperlink email addresses?'),
			'strip_protocol' => array('input' => 'checkbox', 'default' => true, 
					'label' => 'Strip protocol?',
					'help' => 'Remove the protocol (i.e. \'http://\') from the displayed auto-hyperlinked link?'),
			'open_in_new_window' => array('input' => 'checkbox', 'default' => true,
					'label' => 'Open auto-hyperlinked links in new window?'),
			'nofollow' => array('input' => 'checkbox', 'default' => false,
					'label' => 'Enable <a href="http://en.wikipedia.org/wiki/Nofollow">nofollow</a>?'),
			'truncation_before_text' => array('input' => 'text', 'default' => '',
					'label' => 'Text to show before link truncation'),
			'truncation_after_text' => array('input' => 'text', 'default' => '...',
					'label' => 'Text to show after link truncation'),
			'more_extensions' => array('input' => 'text', 'default' => '',
					'label' => 'Extra domain extensions.',
					'help' => 'Space and/or comma-separated list of extensions/<acronym title="Top-Level Domains">TLDs</acronym>.
								<br />These are already built-in: com, org, net, gov, edu, mil, us, info, biz, ws, name, mobi, cc, tv'),
			'hyperlink_mode' => array('input' => 'shorttext', 'default' => 0,
					'label' => 'Hyperlink Mode/Truncation',
					'help' => 'This determines what text should appear as the link.  Use <code>0</code>
								to show the full URL, use <code>1</code> to show just the hostname, or
								use a value greater than <code>10</code> to indicate how many characters
								of the URL you want shown before it gets truncated.  <em>If</em> text
								gets truncated, the truncation before/after text values above will be used.')
		);

		$options = $this->get_options();
		if ( $options['hyperlink_comments'] ) {
			remove_filter('comment_text', array(&$this, 'make_clickable'));
			add_filter('comment_text', array(&$this, 'hyperlink_urls'), 9);
		}
	}
	
	function install() {
		$this->options = $this->get_options();
		update_option($this->admin_options_name, $this->options);
	}

	function admin_menu() {
		if ( $this->show_admin ) {
			global $wp_version;
			if ( current_user_can('manage_options') ) {
				if ( version_compare( $wp_version, '2.6.999', '>' ) )
					add_filter( 'plugin_action_links_' . $this->plugin_basename, array(&$this, 'plugin_action_links') );
				add_options_page('Auto-Hyperlink URLs', 'Auto-hyperlink', 9, $this->plugin_basename, array(&$this, 'options_page'));
			}
		}
	}

	function plugin_action_links($action_links) {
		$settings_link = '<a href="options-general.php?page='.$this->plugin_basename.'">' . __('Settings') . '</a>';
		array_unshift( $action_links, $settings_link );

		return $action_links;
	}

	function get_options() {
		if ( !empty($this->options) ) return $this->options;
		// Derive options from the config
		$options = array();
		foreach (array_keys($this->config) as $opt) {
			$options[$opt] = $this->config[$opt]['default'];
		}
        $existing_options = get_option($this->admin_options_name);
        if ( !empty($existing_options) ) {
            foreach ($existing_options as $key => $option)
                $options[$key] = $option;
        }            
		$this->options = $options;
        return $options;
	}

	function options_page() {
		$options = $this->get_options();
		// See if user has submitted form
		if ( isset($_POST['submitted']) ) {
			check_admin_referer($this->nonce_field);

			foreach (array_keys($options) AS $opt) {
				$options[$opt] = htmlspecialchars(stripslashes($_POST[$opt]));
				$input = $this->config[$opt]['input'];
				if (($input == 'checkbox') && !$options[$opt])
					$options[$opt] = 0;
				if ($this->config[$opt]['datatype'] == 'array') {
					if ($input == 'text')
						$options[$opt] = explode(',', str_replace(array(', ', ' ', ','), ',', $options[$opt]));
					else
						$options[$opt] = array_map('trim', explode("\n", trim($options[$opt])));
				}
				elseif ($this->config[$opt]['datatype'] == 'hash') {
					if ( !empty($options[$opt]) ) {
						$new_values = array();
						foreach (explode("\n", $options[$opt]) AS $line) {
							list($shortcut, $text) = array_map('trim', explode("=>", $line, 2));
							if (!empty($shortcut)) $new_values[str_replace('\\', '', $shortcut)] = str_replace('\\', '', $text);
						}
						$options[$opt] = $new_values;
					}
				}
			}
			// Remember to put all the other options into the array or they'll get lost!
			update_option($this->admin_options_name, $options);

			echo "<div id='message' class='updated fade'><p><strong>" . __('Settings saved.') . '</strong></p></div>';
		}

		$action_url = $_SERVER[PHP_SELF] . '?page=' . $this->plugin_basename;
		$logo = plugins_url() . '/' . basename($_GET['page'], '.php') . '/c2c_minilogo.png';

		echo <<<END
		<div class='wrap'>
			<div class='icon32' style='width:44px;'><img src='$logo' alt='A plugin by coffee2code' /><br /></div>
			<h2>Auto-Hyperlink URLs Settings</h2>
			<p>This plugin seeks to address certain shortcomings with WordPress's default auto-hyperlinking function.
			This tweaks the pattern matching expressions to prevent inappropriate adjacent characters from becoming 
			part of the link (such as a trailing period when a link ends a sentence, links that are parenthesized or 
			braced, comma-separated, etc) and it prevents invalid text from becoming a mailto: link (i.e. smart@ss) 
			or for invalid URIs (i.e. http://blah) from becoming links.</p>
			
			<p>This plugin will recognize any protocol-specified URI (http|https|ftp|news)://, etc, as well as e-mail addresses.  
			It also adds the new ability to recognize Class B domain references (i.e. "somesite.net", not just domains prepended 
			with "www.") as valid links (i.e. "wordpress.org" would now get auto-hyperlinked)</p>

			<p>See the examples at the bottom of this page.</p>
			
			<form name="autohyperlink_urls" action="$action_url" method="post">	
END;
				wp_nonce_field($this->nonce_field);
		echo '<table width="100%" cellspacing="2" cellpadding="5" class="optiontable editform form-table">';
				foreach (array_keys($options) as $opt) {
					$input = $this->config[$opt]['input'];
					if ($input == 'none') continue;
					$label = $this->config[$opt]['label'];
					$value = $options[$opt];
					if ($input == 'checkbox') {
						$checked = ($value == 1) ? 'checked=checked ' : '';
						$value = 1;
					} else {
						$checked = '';
					};
					if ($this->config[$opt]['datatype'] == 'array') {
						if (!is_array($value))
							$value = '';
						else {
							if ($input == 'textarea' || $input == 'inline_textarea')
								$value = implode("\n", $value);
							else
								$value = implode(', ', $value);
						}
					} elseif ($this->config[$opt]['datatype'] == 'hash') {
						if (!is_array($value))
							$value = '';
						else {
							$new_value = '';
							foreach ($value AS $shortcut => $replacement) {
								$new_value .= "$shortcut => $replacement\n";
							}
							$value = $new_value;
						}
					}
					echo "<tr valign='top'>";
					if ($input == 'textarea') {
						echo "<td colspan='2'>";
						if ($label) echo "<strong>$label</strong><br />";
						echo "<textarea name='$opt' id='$opt' {$this->config[$opt]['input_attributes']}>" . $value . '</textarea>';
					} else {
						echo "<th scope='row'>$label</th><td>";
						if ($input == "inline_textarea")
							echo "<textarea name='$opt' id='$opt' {$this->config[$opt]['input_attributes']}>" . $value . '</textarea>';
						elseif ($input == 'select') {
							echo "<select name='$opt' id='$opt'>";
							foreach ($this->config[$opt]['options'] as $sopt) {
								$selected = $value == $sopt ? " selected='selected'" : '';
								echo "<option value='$sopt'$selected>$sopt</option>";
							}
							echo "</select>";
						} else
							echo "<input name='$opt' type='$input' id='$opt' value='$value' $checked {$this->config[$opt]['input_attributes']} />";
					}
					if ($this->config[$opt]['help']) {
						echo "<br /><span style='color:#777; font-size:x-small;'>";
						echo $this->config[$opt]['help'];
						echo "</span>";
					}
					echo "</td></tr>";
				}
		$txt = __('Save Changes');
		echo <<<END
			</tbody></table>
			<input type="hidden" name="submitted" value="1" />
			<div class="submit"><input type="submit" name="Submit" class="button-primary" value="{$txt}" /></div>
		</form>
			</div>
END;

		echo <<<END
		<style type="text/css">
			#c2c {
				text-align:center;
				color:#888;
				background-color:#ffffef;
				padding:5px 0 0;
				margin-top:12px;
				border-style:solid;
				border-color:#dadada;
				border-width:1px 0;
			}
			#c2c div {
				margin:0 auto;
				padding:5px 40px 0 0;
				width:45%;
				min-height:40px;
				background:url('$logo') no-repeat top right;
			}
			#c2c span {
				display:block;
				font-size:x-small;
			}
		</style>
		<div id='c2c' class='wrap'>
			<div>
			This plugin brought to you by <a href="http://coffee2code.com" title="coffee2code.com">Scott Reilly, aka coffee2code</a>.
			<span><a href="http://coffee2code.com/donate" title="Please consider a donation">Did you find this plugin useful?</a></span>
			</div>
		</div>
END;
		echo <<<END
			<div class='wrap'>
				<h2>Examples</h2>
				
				<p>To better illustrate what results you might get using the various settings above, here are examples.</p>
				
				<p>In all cases, assume the following URL is appearing as plaintext in a post:<br />
				<code>www.somelonghost.com/with/some/long/URL/that/might/mess/up/your/theme/and/is/unsightly.php</code></p>
				
				<p>And unless explicitly stated, the results are using default values (nofollow is false, hyperlink emails is true, Hyperlink Mode is 0)</p>
				
			<dl>
				<dt>By default</dt>
				<dd><a href="http://www.somelonghost.com/with/some/long/URL/that/might/mess/up/your/theme/and/is/unsightly.php"  class="autohyperlink" title="http://www.somelonghost.com/with/some/long/URL/that/might/mess/up/your/theme/and/is/unsightly.php" target="_blank">www.somelonghost.com/with/some/long/URL/that/might/mess/up/your/theme/and/is/unsightly.php</a></dd>
				<dt>With Hyperlink Mode set to 1</dt>
				<dd><a href="http://www.somelonghost.com/with/some/long/URL/that/might/mess/up/your/theme/and/is/unsightly.php" title="http://www.somelonghost.com/with/some/long/URL/that/might/mess/up/your/theme/and/is/unsightly.php" class="autohyperlink" target="_blank">www.somelonghost.com</a></dd>
				<dt>With Hyperlink Mode set to 15</dt>
				<dd><a href="http://www.somelonghost.com/with/some/long/URL/that/might/mess/up/your/theme/and/is/unsightly.php" title="http://www.somelonghost.com/with/some/long/URL/that/might/mess/up/your/theme/and/is/unsightly.php" class="autohyperlink" target="_blank">www.somelonghos...</a></dd>
				<dt>With Hyperlink Mode set to 15, nofollow set to true, open in new window set to false, truncation before of "[", truncation after of "...]"</dt>
				<dd><a href="http://www.somelonghost.com/with/some/long/URL/that/might/mess/up/your/theme/and/is/unsightly.php" title="http://www.somelonghost.com/with/some/long/URL/that/might/mess/up/your/theme/and/is/unsightly.php" class="autohyperlink" rel="nofollow">[www.somelonghos...]</a></dd>
			</dl>

			</div>
END;
	}

	function get_class() {
		return attribute_escape(apply_filters('autohyperlink_urls_class', 'autohyperlink'));
	}

	function get_link_attributes( $title = '' ) {
		$link_attributes = 'class="' . $this->get_class() . '"';
		if ( $title ) $link_attributes .= ' title="' . attribute_escape($title) . '"';
		if ( $options['open_in_new_window'] ) $link_attributes .= ' target="_blank"';
	 	if ( $options['nofollow'] ) $link_attributes .= ' rel="nofollow"';
		return apply_filters('autohyperlink_urls_link_attributes', $link_attributes);
	}

	function get_tlds() {
		static $tlds;
		if ( !$tlds ) {
			$options = $this->get_options();
			$tlds = 'com|org|net|gov|edu|mil|us|info|biz|ws|name|mobi|cc|tv';
			if ( $options['more_extensions'] )
			 	$tlds .= '|' . implode('|', array_map('trim', explode('|', str_replace(array(', ', ' ', ','), '|', $options['more_extensions']))));
		}
		return apply_filters('autohyperlink_urls_tlds', $tlds);
	}

	function truncate_link( $url ) {
		$options = $this->get_options();
		$mode = intval($options['hyperlink_mode']);
		$more_extensions = $options['more_extensions'];
		$trunc_before = $options['truncation_before_text'];
		$trunc_after = $options['truncation_after_text'];
		$original_url = $url;
		if ( 1 === $mode ) {
			$url = preg_replace("/(([a-z]+?):\\/\\/[a-z0-9\-\:@]+).*/i", "$1", $url);
			$extensions = $this->get_tlds();
			$url = $trunc_before . preg_replace("/([a-z0-9\-\:@]+\.($extensions)).*/i", "$1", $url) . $trunc_after;
		} elseif ( ($mode > 10) && (strlen($url) > $mode) ) {
			$url = $trunc_before . substr($url, 0, $mode) . $trunc_after;
		}
		return apply_filters('autohyperlink_urls_truncate_link', $original_url, $url);
	}

	function hyperlink_urls( $text ) {
		$options = $this->get_options();
		$text = ' ' . $text . ' ';
		$extensions = $this->get_tlds();

		$text = preg_replace_callback("#(?!<.*?)([\s{}\(\)\[\]>])([a-z0-9\-\.]+[a-z0-9\-])\.($extensions)((?:[/\#?][^\s<{}\(\)\[\]]*[^\.,\s<{}\(\)\[\]]?)?)(?![^<>]*?>)#is",
							array(&$this, 'do_hyperlink_url_no_proto'), $text);
		$text = preg_replace_callback('#(?!<.*?)(?<=[\s>])(\()?(([\w]+?)://((?:[\w\\x80-\\xff\#$%&~/\-=?@\[\](+]|[.,;:](?![\s<])|(?(1)\)(?![\s<])|\)))+))(?![^<>]*?>)#is',
							array(&$this, 'do_hyperlink_url'), $text);
/*		$text = preg_replace_callback('#(?!<.*?)(?<=[\s>])(\()?(([\w]+?)://((?:[\w\\x80-\\xff\#$%&~/\-=?@\[\](+]|[.,;:](?![\s<])|(?(1)\)(?![\s<])|\)))+))(?![^<>]*?>)#is',
							array(&$this, 'do_hyperlink_url'), $text);
*/
		if ( $options['hyperlink_emails'] )
			$text = preg_replace_callback('#(?!<.*?)([.0-9a-z_+-]+)@(([0-9a-z-]+\.)+[0-9a-z]{2,})(?![^<>]*?>)#i',
							array(&$this, 'do_hyperlink_email'), $text);

		// Remove links within links
/*
 		$text = preg_replace("#(<a [^>]+[\"'][^>\"']+)<a [^>]+>([^>]+?)</a>(.+)</a>#isU", "$1$3$4</a>", $text);
		$text = preg_replace("#(<a( [^>]+?>|>))<a [^>]+?>([^>]+?)</a></a>#i", "$1$3</a>", $text);
*/
$text = preg_replace("#(<a [^>]+>)(.*)<a [^>]+>([^<]*)</a>([^>]*)</a>#iU", "$1$2$3$4</a>" , $text);
//$text = preg_replace("#(<a [^>]+>)(.*)<a [^>]+>((?!</a>)*)</a>((?!</a>)*)</a>#iU", "$1$2$3$4</a>" , $text);
		return trim($text);
	}

	function do_hyperlink_url( $matches ) {
		$options = $this->get_options();
		$link_text = $options['strip_protocol'] ? $matches[4] : $matches[2];
		return $matches[1] . "<a href=\"$matches[2]\" " . $this->get_link_attributes($matches[2]) .'>' . $this->truncate_link($link_text) . '</a>';
	}

	function do_hyperlink_url_no_proto( $matches ) {
		$dest = $matches[2] . '.' . $matches[3] . $matches[4];
		return $matches[1] . "<a href=\"http://$dest\" " . $this->get_link_attributes("http://$dest") .'>' . $this->truncate_link($dest) . '</a>';
	}

	function do_hyperlink_email( $matches ) {
		$email = $matches[1] . '@' . $matches[2];
		return "<a class=\"" . $this->get_class() . "\" href=\"mailto:$email\" title=\"mailto:$email\">" . $this->truncate_link($email) . '</a>';
	}
} // end AutoHyperlinkURLs

endif; // end if !class_exists()

if ( class_exists('AutoHyperlinkURLs') ) :
	$autohyperlink_urls = new AutoHyperlinkURLs();
	if ( isset($autohyperlink_urls) )
		register_activation_hook( __FILE__, array(&$autohyperlink_urls, 'install') );
endif;

/*
 * TEMPLATE TAGS
 */

function autohyperlink_truncate_link( $url ) {
	global $autohyperlink_urls;
	return $autohyperlink_urls->truncate_link($url);
}

function autohyperlink_link_urls( $text ) {
	global $autohyperlink_urls;
	return $autohyperlink_urls->hyperlink_urls($text);
}

?>