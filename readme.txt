=== Auto-hyperlink URLs ===
Contributors: coffee2code
Donate link: http://coffee2code.com
Tags: links, link, URLs, url, auto-link, hyperlink, coffee2code
Requires at least: 2.6
Tested up to: 2.8.4
Stable tag: 3.5
Version: 3.5

Automatically hyperlink text URLs and email addresses originally written only as plaintext.

== Description ==

Automatically hyperlink text URLs and email addresses originally written only as plaintext.

This plugin seeks to replace and extend WordPress's default auto-hyperlinking function.  This plugin tweaks the pattern matching expressions to prevent inappropriate adjacent characters from becoming part of the link (such as a trailing period when a link ends a sentence, links that are parenthesized or braced, comma-separated, etc) and it prevents invalid text from becoming a mailto: link (i.e. smart@ss) or for invalid URIs (i.e. http://blah) from becoming links.  In addition, this plugin adds configurability to the auto-hyperlinker such that you can configure:

* If you want text URLs to only show the hostname
* If you want text URLs truncated after N characters
* If you want auto-hyperlinked URLs to open in new browser window or not
* If you want the protocol (i.e. "http://") to be stripped for displayed links
* The text to come before and after the link text for truncated links
* If you want rel="nofollow" to be supported
* If you wish to support additional domain extensions not already configured into the plugin

This plugin will recognize any protocol-specified URI (http|https|ftp|news)://, etc, as well as e-mail addresses.  It also adds the new ability to recognize Class B domain references (i.e. "somesite.net", not just domains prepended with "www.") as valid links (i.e. "wordpress.org" would get auto-hyperlinked)

The following domain extensions (aka TLDs, Top-Level Domains) are recognized by the plugin: com, org, net, gov, edu, mil, us, info, biz, ws, name, mobi, cc, tv.  Knowing these only comes into play when you have a plaintext URL that does not have an explicit protocol specified.  If you need support for additional TLDs, you can add more via the plugin's admin options page.

== Installation ==

1. Unzip `autohyperlink-urls.zip` inside the `/wp-content/plugins/` directory, or upload `autohyperlink-urls.php` to `/wp-content/plugins/`
1. Activate the plugin through the 'Plugins' admin menu in WordPress
1. (optional) Go to the Settings -> Autohyperlink admin settings page (which you can also get to via the Settings link next to the plugin on
the Manage Plugins page) and customize the settings.

== Examples ==

(when running with default configuration):

* "wordpress.org"
<a href="http://wordpress.org" title="http://wordpress.org" target="_blank" class="autohyperlink">wordpress.org</a>

* "http://www.cnn.com"
<a href="http://www.cnn.com" title"http://www.cnn.com" target="_blank" class="autohyperlink">www.cnn.com</a>

* "person@example.com"
<a href="mailto:person@example.com" title="mailto:person@example.com" class="autohyperlink">person@example.com</a>

To better illustrate what results you might get using the various settings above, here are examples.
	
For the following, assume the following URL is appearing as plaintext in a post: `www.somelonghost.com/with/some/long/URL/that/might/mess/up/your/theme/and/is/unsightly.php`
	
And unless explicitly stated, the results are using default values (nofollow is false, hyperlink emails is true, Hyperlink Mode is 0)
	
* By default:
<a href="http://www.somelonghost.com/with/some/long/URL/that/might/mess/up/your/theme/and/is/unsightly.php" title="http://www.somelonghost.com/with/some/long/URL/that/might/mess/up/your/theme/and/is/unsightly.php"  class="autohyperlink" target="_blank">www.somelonghost.com/with/some/long/URL/that/might/mess/up/your/theme/and/is/unsightly.php</a>

* With Hyperlink Mode set to 1
<a href="http://www.somelonghost.com/with/some/long/URL/that/might/mess/up/your/theme/and/is/unsightly.php" title="http://www.somelonghost.com/with/some/long/URL/that/might/mess/up/your/theme/and/is/unsightly.php" class="autohyperlink" target="_blank">www.somelonghost.com</a>

* With Hyperlink Mode set to 15
<a href="http://www.somelonghost.com/with/some/long/URL/that/might/mess/up/your/theme/and/is/unsightly.php" title="http://www.somelonghost.com/with/some/long/URL/that/might/mess/up/your/theme/and/is/unsightly.php" class="autohyperlink"target="_blank">www.somelonghos...</a>

* With Hyperlink Mode set to 15, nofollow set to true, open in new window set to false, truncation before of "[", truncation after of "...]"
<a href="http://www.somelonghost.com/with/some/long/URL/that/might/mess/up/your/theme/and/is/unsightly.php" title="http://www.somelonghost.com/with/some/long/URL/that/might/mess/up/your/theme/and/is/unsightly.php" class="autohyperlink" rel="nofollow">[www.somelonghos...]</a>

== Known Shortcomings ==

* Currently the plugin hyperlinks URLs that appear embedded within the middle of a longer string used as tag attribute value, i.e.
`<a href="http://example.com" title="I go to http://example.com often">example.com</a>`
comes out as:
`<a href="http://example.com" title="I go to <a href="http://example.com" class="autohyperlink">http://example.com</a> often">example.com</a>`
  
* It will also not auto-hyperlink URLs that are immediately single- or double-quoted, i.e. `'http://example.com'` or `"http://example.com"`

== Screenshots ==

1. A screenshot of the plugin's admin options page.

== Changelog ==

= 3.5 (unreleased) =
* NEW:
* Extracted functionality into clearly defined, single-tasked, and filterable functions
* Added get_class() with filter 'autohyperlink_urls_class' to filter class assigned to auto-hyperlinks (default is 'autohyperlink')
* Added get_link_attributes() with filter 'autohyperlink_urls_link_attributes' to filter all attributes for auto-hyperlink
* Added get_tlds() with filter 'autohyperlink_urls_tlds' to filter TLDs recognized by the plugin (a '|' separated string of tlds)
* Added filter 'autohyperlink_urls_truncate_link' to truncate_link() to facilitate customized link truncation
* Added strip_protocol setting to control if protocol should be stripped from auto-hyperlinks
* Added 'Settings' link to plugin's plugin listing entry
* Added Changelog to readme.txt
* CHANGED:
* Moved all global functions into class (except autohyperlink_truncate_link() and autohyperlink_link_urls(), which are now just single argument proxies to classed versions)
* Rewrote significant portions of all regular expressions
* Added hyphen to settings link text
* truncate_link() and hyperlink_urls() now pass arguments inline instead of setting temporary variables
* Memoized options in class
* Added class variable 'plugin_basename', which gets initialized in constructor, and use it instead of hardcoded path
* Updated to current admin page markup conventions
* Improved options handling
* Added logo to settings page
* Minor reformatting
* Noted compatibility through WP2.8+
* Dropped support for versions of WP older than 2.6
* Changed description
* Updated copyright date
* Updated screenshot
* FIXED:
* Changed pattern matching code for email addresses to allow for emails to be preceded by non-space characters
* Changed pattern matching code for all auto-hyperlinking to better prevent linking a link within tag attributes
* Used plugins_url() instead of hardcoded path

= 3.0 =
* Overhauled and added a bunch of new code
* Encapsulated a majority of functionality in a class
* Added admin options page for the plugin, under Options -> Autohyperlink (or in WP 2.5: Settings -> Autohyperlink)
* Added options so that default auto-hyperlinking can be easily configured
* Added option to allow for user-specified TLDs
* Added TLDs of mil, mobi, and cc
* Added option to conditionally auto-hyperlink comments
* Renamed existing functions
* "~" is a valid URL character
* Added class of "autohyperlink" to all links created by the plugin
* Removed the A-Z from regexp since they are case-insensitive
* Recoded some of the core functionality so as to execute only one preg_replace() call for everything (by passing patterns and replacements as arrays)
* Added a note about the known issue of the plugin linking URLs that appear within a longer string in a tag attribute's value
* trim() text before return instead of doing a substr()
* Added nofollow support
* Moved Class B domain preg to after explicitly protocoled links
* Tweaked description and installation instructions
* Updated copyright date and version to 3.0
* Added readme.txt and screenshot image to distribution zip
* Tested compatibility with WP 2.3+ and 2.5

= 2.01 =
* Fix to once again prevent linking already hyperlinked URL

= 2.0 =
* Plaintext URLs can now begin, end, or be all of the post and it will get auto-hyperlinked
* Incorporated some WP1.3 regular expression changes to make_clickable()
* Added “gov” and “edu” to the list of common domain extensions (for Class B domain support)
* No longer displays the protocol (the “http://” part) in the displayed link text
* Dropped support for auto-linking aim: and icq:
* Prepended function names with “c2c_”to avoid potential future conflict with other plugins or the WordPress core
* Changed license from BSD-new to MIT

= 1.01 =
* Slight tweak to prevent http://blah from becoming a link

= 1.0 =
* Complete rewrite

= 0.9 =
* Initial release