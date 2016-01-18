<?php
/**
 * Plugin Name: Auto-hyperlink URLs
 * Version:     5.0
 * Plugin URI:  http://coffee2code.com/wp-plugins/auto-hyperlink-urls/
 * Author:      Scott Reilly
 * Author URI:  http://coffee2code.com/
 * Text Domain: auto-hyperlink-urls
 * Description: Automatically hyperlink text URLs and email addresses originally written only as plaintext.
 *
 * Compatible with WordPress 4.1 through 4.4+.
 *
 * =>> Read the accompanying readme.txt file for instructions and documentation.
 * =>> Also, visit the plugin's homepage for additional information and updates.
 * =>> Or visit: https://wordpress.org/plugins/auto-hyperlink-urls/
 *
 * @package Auto_Hyperlink_URLs
 * @author  Scott Reilly
 * @version 5.0
 */

/*
 * TODO:
 * - Filter/option to disable class B domain autolinking (the non protocol urls)
 * - Way to exclude pages from autolinking? (Q on forums)
 * - Test against oembeds (and Viper's Video Quicktags). Run at 11+ priority?
 * - More tests (incl. testing filters)
 * - Ability to truncate middle of link http://domain.com/som...file.php (config options for
 *   # of chars for first part, # of chars for ending, and truncation string?)
 * - Option to specify hosts to prevent truncation (so stuff like youtube.com autoembeds work)
 *   (or better if it solves this situation: simply filter text later)
 */

/*
	Copyright (c) 2004-2016 by Scott Reilly (aka coffee2code)

	This program is free software; you can redistribute it and/or
	modify it under the terms of the GNU General Public License
	as published by the Free Software Foundation; either version 2
	of the License, or (at your option) any later version.

	This program is distributed in the hope that it will be useful,
	but WITHOUT ANY WARRANTY; without even the implied warranty of
	MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
	GNU General Public License for more details.

	You should have received a copy of the GNU General Public License
	along with this program; if not, write to the Free Software
	Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA  02110-1301, USA.
*/

defined( 'ABSPATH' ) or die();

if ( ! class_exists( 'c2c_AutoHyperlinkURLs' ) ) :

require_once( dirname( __FILE__ ) . DIRECTORY_SEPARATOR . 'c2c-plugin.php' );

final class c2c_AutoHyperlinkURLs extends c2c_AutoHyperlinkURLs_Plugin_040 {

	/**
	 * The one true instance.
	 *
	 * @var c2c_AutoHyperlinkURLs
	 */
	public static $instance;

	/**
	 * Memoized array of TLDs.
	 *
	 * @since 5.0
	 * @var array
	 */
	public static $tlds = array();

	/**
	 * Get singleton instance.
	 *
	 * @since 5.0
	 */
	public static function get_instance() {
		if ( ! isset( self::$instance ) ) {
			self::$instance = new self();
		}

		return self::$instance;
	}

	/**
	 * Constructor.
	 */
	protected function __construct() {
		parent::__construct( '5.0', 'autohyperlink-urls', 'c2c', __FILE__, array() );
		register_activation_hook( __FILE__, array( __CLASS__, 'activation' ) );

		return self::$instance = $this;
	}

	/**
	 * Handles activation tasks, such as registering the uninstall hook.
	 *
	 * @since 4.0
	 */
	public static function activation() {
		register_uninstall_hook( __FILE__, array( __CLASS__, 'uninstall' ) );
	}

	/**
	 * Handles uninstallation tasks, such as deleting plugin options.
	 *
	 * @since 4.0
	 */
	public static function uninstall() {
		delete_option( 'c2c_autohyperlink_urls' );
	}

	/**
	 * Resets plugin options.
	 *
	 * @since 5.0
	 */
	public function reset_options() {
		parent::reset_options();
		self::$tlds = array();
	}

	/**
	 * Initializes the plugin's config data array.
	 */
	public function load_config() {
		$this->name      = __( 'Auto-hyperlink URLs', 'auto-hyperlink-urls' );
		$this->menu_name = __( 'Auto-hyperlink', 'auto-hyperlink-urls' );

		$this->config = array(
			'hyperlink_comments' => array( 'input' => 'checkbox', 'default' => true,
				'label' => __( 'Auto-hyperlink comments?', 'auto-hyperlink-urls' ),
				'help'  => __( 'Note that if disabled WordPress\'s built-in hyperlinking function will still be performed, which links email addresses and text URLs with explicit protocols.', 'auto-hyperlink-urls' ),
			),
			'hyperlink_emails' => array( 'input' => 'checkbox', 'default' => true,
				'label' => __( 'Hyperlink email addresses?', 'auto-hyperlink-urls' )
			),
			'strip_protocol' => array( 'input' => 'checkbox', 'default' => true,
				'label' => __( 'Strip protocol?', 'auto-hyperlink-urls' ),
				'help'  => __( 'Remove the protocol (i.e. \'http://\') from the displayed auto-hyperlinked link?', 'auto-hyperlink-urls' )
			),
			'open_in_new_window' => array( 'input' => 'checkbox', 'default' => true,
				'label' => __( 'Open auto-hyperlinked links in new window?', 'auto-hyperlink-urls' )
			),
			'nofollow' => array( 'input' => 'checkbox', 'default' => false,
				'label' => __( 'Enable <a href="http://en.wikipedia.org/wiki/Nofollow">nofollow</a>?', 'auto-hyperlink-urls' )
			),
			'hyperlink_mode' => array( 'input' => 'shorttext', 'default' => 0,
				'label' => __( 'Hyperlink Mode/Truncation', 'auto-hyperlink-urls' ),
				'help' => __( 'This determines what text should appear as the link.  Use <code>0</code> to show the full URL, use <code>1</code> to show just the hostname, or use a value greater than <code>10</code> to indicate how many characters of the URL you want shown before it gets truncated.  <em>If</em> text gets truncated, the truncation before/after text values below will be used.', 'auto-hyperlink-urls' )
			),
			'truncation_before_text' => array( 'input' => 'text', 'default' => '',
				'label' => __( 'Text to show before link truncation', 'auto-hyperlink-urls' )
			),
			'truncation_after_text' => array( 'input' => 'text', 'default' => '...',
				'label' => __( 'Text to show after link truncation', 'auto-hyperlink-urls' )
			),
			'more_extensions' => array( 'input' => 'text', 'default' => '',
				'label' => __( 'Extra domain extensions', 'auto-hyperlink-urls' ),
				'help'  => __( 'Space and/or comma-separated list of extensions/<acronym title="Top-Level Domains">TLDs</acronym>.<br />These are already built-in: com, org, net, gov, edu, mil, us, info, biz, ws, name, mobi, cc, tv', 'auto-hyperlink-urls' )
			),
			'exclude_domains' => array( 'input' => 'inline_textarea', 'datatype' => 'array',
				'no_wrap' => true, 'input_attributes' => 'rows="6"',
				'label' => __( 'Exclude domains', 'auto-hyperlink-urls' ),
				'help' => __( 'List domains that should NOT get automatically hyperlinked. One domain per line. Do not include protocol (e.g. "http://") or trailing slash.', 'auto-hyperlink-urls' ),
			),
		);
	}

	/**
	 * Override the plugin framework's register_filters() to actually actions
	 * against filters.
	 */
	public function register_filters() {
		$options = $this->get_options();

		$filters = (array) apply_filters( 'c2c_autohyperlink_urls_filters', array( 'the_content', 'the_excerpt', 'widget_text' ) );
		foreach( $filters as $filter ) {
			add_filter( $filter, array( $this, 'hyperlink_urls' ), 9 );
		}

		if ( $options['hyperlink_comments'] ) {
			remove_filter( 'comment_text', 'make_clickable', 9 );
			add_filter( 'comment_text', array( $this, 'hyperlink_urls' ), 9 );
		}
	}

	/**
	 * Outputs the text above the setting form.
	 *
	 * @param string $localized_heading_text (optional) Localized page heading text.
	 */
	public function options_page_description( $localized_heading_text = '' ) {
		parent::options_page_description( __( 'Auto-hyperlink URLs', 'auto-hyperlink-urls' ) );

		echo '<p>' . __( 'Automatically hyperlink text URLs and email addresses originally written only as plaintext.', 'auto-hyperlink-urls' ) . '</p>';
	}

	/**
	 * Returns the class name(s) to be used for links created by Autohyperlinks.
	 *
	 * Default value is 'autohyperlink'. Can be filtered via the
	 * 'autohyperlink_urls_class' filter.
	 *
	 * @return string Class to assign to link.
	 */
	public function get_class() {
		return esc_attr( apply_filters( 'autohyperlink_urls_class', 'autohyperlink' ) );
	}

	/**
	 * Returns the link attributes to be used for links created by Autohyperlinks.
	 *
	 * Utilizes plugin options to determine if attributes such as 'target' and
	 * 'nofollow' should be used. Calls get_class() to determine the
	 * appropriate class name(s).
	 * Can be filtered via 'autohyperlink_urls_link_attributes' filter.
	 *
	 * @param  string $title Optional. The text for the link's title attribute.
	 * @return string The entire HTML attributes string to be used for link.
	 */
	public function get_link_attributes( $title = '' ) {
		$options = $this->get_options();

		$link_attributes = 'class="' . $this->get_class() . '"';

		if ( $title ) {
			$link_attributes .= ' title="' . esc_attr( $title ) . '"';
		}

		if ( $options['open_in_new_window'] ) {
			$link_attributes .= ' target="_blank"';
		}

		if ( $options['nofollow'] ) {
			$link_attributes .= ' rel="nofollow"';
		}

		return apply_filters( 'autohyperlink_urls_link_attributes', $link_attributes );
	}

	/**
	 * Returns the TLDs recognized by the plugin.
	 *
	 * Returns a '|'-separated string of TLDs recognized by the plugin to be
	 * used in searches for non-protocoled text links.
	 *
	 * By default this is:
	 * 'com|org|net|gov|edu|mil|us|info|biz|ws|name|mobi|cc|tv'.  More
	 * extensions can be added via the plugin's settings page.
	 *
	 * @return string The '|'-separated string of TLDs.
	 */
	public function get_tlds() {
		if ( ! self::$tlds ) {
			$options = $this->get_options();

			// The default TLDs.
			self::$tlds = 'com|org|net|gov|edu|mil|us|info|biz|ws|name|mobi|cc|tv';

			// Add TLDs defined via options.
			if ( $options['more_extensions'] ) {
				self::$tlds .= '|' . implode( '|', array_map( 'trim', explode( '|', str_replace( array( ', ', ' ', ',' ), '|', $options['more_extensions'] ) ) ) );
			}
		}

		$tlds = apply_filters( 'autohyperlink_urls_tlds', self::$tlds );

		// Sanitize TLDs for use in regex.
		$safe_tlds = array();
		foreach ( explode( '|', $tlds ) as $tld ) {
			if ( $tld ) {
				$safe_tlds[] = preg_quote( $tld, '#' );
			}
		}

		return implode( '|', $safe_tlds );
	}

	/**
	 * Truncates a URL according to plugin settings.
	 *
	 * Based on various plugin settings, this function will potentially
	 * truncate the supplied URL, optionally adding text before and/or
	 * after the URL if truncated.
	 *
	 * @param string $url The URL to potentially truncate
	 * @return string the potentially truncated version of the URL
	 */
	public function truncate_link( $url ) {
		$options         = $this->get_options();
		$mode            = intval( $options['hyperlink_mode'] );
		$more_extensions = $options['more_extensions'];
		$trunc_before    = $options['truncation_before_text'];
		$trunc_after     = $options['truncation_after_text'];
		$original_url    = $url;

		if ( 1 === $mode ) {
			$url = preg_replace( "#(([a-z]+?):\\/\\/[a-z0-9\-\:@]+).*#i", "$1", $url );
			$extensions = $this->get_tlds();
			$url = $trunc_before . preg_replace( "/([a-z0-9\-\:@]+\.($extensions)).*/i", "$1", $url ) . $trunc_after;
		} elseif ( ( $mode > 10 ) && ( strlen( $url ) > $mode ) ) {
			$url = $trunc_before . substr( $url, 0, $mode ) . $trunc_after;
		}

		return apply_filters( 'autohyperlink_urls_truncate_link', $url, $original_url );
	}

	/**
	 * Hyperlinks plaintext links within text.
	 *
	 * @param  string $text The text to have its plaintext links hyperlinked.
	 * @param  array  $args An array of configuration options, each element of which will override the plugin's corresponding default setting.
	 * @return string The hyperlinked version of the text.
	 */
	public function hyperlink_urls( $text, $args = array() ) {
		$options = $this->get_options();

		if ( $args ) {
			$options = $this->options = wp_parse_args( $args, $options );
		}

		// Temporarily introduce a leading and trailing single space to the text to simplify regex handling.
		$text = ' ' . $text . ' ';

		// Get the regex-style list of domain extensions that are acceptable for non-protocoled links.
		$extensions = $this->get_tlds();

		// Link links that don't have a protocol.
		$text = preg_replace_callback(
			"#(?!<.*?)([\s{}\(\)\[\]>,.\'\";:])([a-z0-9]+[a-z0-9\-\.]*)\.($extensions)((?:[/\#?][^\s<{}\(\)\[\]]*[^\.,\s<{}\(\)\[\]]?)?)(?![^<>]*?>)#is",
			array( $this, 'do_hyperlink_url_no_proto' ),
			$text
		);

		// Link links that have an explicit protocol.
		$text = preg_replace_callback(
			'#(?!<.*?)(?<=[\s>])(\()?(([\w]+?)://((?:[\w\\x80-\\xff\#$%&~/\-=?@\[\](+]|[.,;:](?![\s<])|(?(1)\)(?![\s<])|\)))+))(?![^<>]*?>)#is',
			array( $this, 'do_hyperlink_url' ),
			$text
		);

		// Link email addresses, if enabled to do so.
		if ( $options['hyperlink_emails'] ) {
			$text = preg_replace_callback(
				'#(?!<.*?)([.0-9a-z_+-]+)@(([0-9a-z-]+\.)+[0-9a-z]{2,})(?![^<>]*?>)#i',
				array( $this, 'do_hyperlink_email' ),
				$text
			);
		}

		// Remove links within links
		$text = preg_replace( "#(<a [^>]+>)(.*)<a [^>]+>([^<]*)</a>([^>]*)</a>#iU", "$1$2$3$4</a>" , $text );

		// Remove temporarily added leading and trailing single spaces before returning.
		return substr( $text, 1, -1 );
	}

	/**
	 * Should the hyperlinking be performed?
	 *
	 * At the point before the plugin constructs the actual markup for the link,
	 * should the text link actually get linked?
	 *
	 * @since 5.0
	 *
	 * @param  string $url    The URL to hyperlink.
	 * @param  string $domain Optional. The domain part of the URL, if known.
	 * @return bool   True if the URL can be hyperlinked, false if not.
	 */
	protected function can_do_hyperlink( $url, $domain = '' ) {
		$options = $this->get_options();

		// If domain wasn't provided, figure it out.
		if ( ! $domain ) {
			$parts = parse_url( $url );
			$domain = $parts['host'];
		}

		// Don't link domains explicitly excluded.
		$exclude_domains = (array) apply_filters( 'autohyperlink_urls_exclude_domains', $options['exclude_domains'] );
		foreach ( $exclude_domains as $exclude ) {
			if ( $domain === $exclude ) {
				return false;
			}
		}

		return true;
	}

	/**
	 * preg_replace_callback to create the replacement text for hyperlinks.
	 *
	 * @param  array  $matches Matches as generated by a preg_replace_callback().
	 * @return string Replacement string.
	 */
	public function do_hyperlink_url( $matches ) {
		$options = $this->get_options();

		// Check to see if the link should actually be hyperlinked.
		if ( ! $this->can_do_hyperlink( $matches[0] ) ) {
			return $matches[0];
		}

		$link_text = $options['strip_protocol'] ? $matches[4] : $matches[2];

		return $matches[1] . '<a href="' . esc_attr( $matches[2] ) . '" ' . $this->get_link_attributes( $matches[2] ) . '>' . $this->truncate_link( $link_text ) . '</a>';
	}

	/**
	 * preg_replace_callback to create the replacement text for non-protocol
	 * hyperlinks.
	 *
	 * @param  array  $matches Matches as generated by a preg_replace_callback().
	 * @return string Replacement string
	 */
	public function do_hyperlink_url_no_proto( $matches ) {
		$dest = $matches[2] . '.' . $matches[3] . $matches[4];

		// Check to see if the link should actually be hyperlinked.
		if ( ! $this->can_do_hyperlink( $matches[0], $dest ) ) {
			return $matches[0];
		}

		// If the link ends in a question mark, pull the question mark out of the URL
		// and append to link text.
		if ( '?' === substr( $dest, -1 ) ) {
			$dest  = substr( $dest, 0, -1 );
			$after = '?';
		} else {
			$after = '';
		}
		return $matches[1]
			. '<a href="http://' . esc_attr( $dest ) . '" ' . $this->get_link_attributes( "http://$dest" ) . '>'
			. $this->truncate_link( $dest )
			. '</a>'
			. $after;
	}

	/**
	 * preg_replace_callback to create the replacement text for emails.
	 *
	 * @param  array  $matches Matches as generated by a preg_replace_callback().
	 * @return string Replacement string.
	 */
	public function do_hyperlink_email( $matches ) {
		$email = $matches[1] . '@' . $matches[2];

		return "<a class=\"" . $this->get_class() . "\" href=\"mailto:$email\" title=\"mailto:$email\">" . $this->truncate_link( $email ) . '</a>';
	}
} // end c2c_AutoHyperlinkURLs

c2c_AutoHyperlinkURLs::get_instance();

endif; // end if !class_exists()

/*
 * TEMPLATE TAGS
 */
if ( ! function_exists( 'c2c_autohyperlink_truncate_link' ) ) :
	/**
	 * Truncates a URL according to plugin settings.
	 *
	 * Based on various plugin settings, this function will potentially
	 * truncate the supplied URL, optionally adding text before and/or
	 * after the URL if truncated.
	 *
	 * @param  string $url The URL to potentially truncate.
	 * @return string The potentially truncated version of the URL.
	 */
	function c2c_autohyperlink_truncate_link( $url ) {
		return c2c_AutoHyperlinkURLs::get_instance()->truncate_link( $url );
	}
endif;

if ( ! function_exists( 'c2c_autohyperlink_link_urls' ) ) :
	/**
	 * Hyperlinks plaintext links within text.
	 *
	 * @param  string $text The text to have its plaintext links hyperlinked.
	 * @param  array  $args An array of configuration options, each element of which will override the plugin's corresponding default setting.
	 * @return The hyperlinked version of the text.
	 */
	function c2c_autohyperlink_link_urls( $text, $args = array() ) {
		return c2c_AutoHyperlinkURLs::get_instance()->hyperlink_urls( $text, $args );
	}
endif;


/**
 * DEPRECATED
 */

if ( ! function_exists( 'autohyperlink_truncate_link' ) ) :
	/**
	 * @deprecated since 4.0 Use c2c_autohyperlink_truncate_link()
	 */
	function autohyperlink_truncate_link( $url ) {
		_deprecated_function( __FUNCTION__, '4.0', 'c2c_autohyperlink_truncate_link()' );
		return c2c_autohyperlink_truncate_link( $url );
	}
endif;

if ( ! function_exists( 'autohyperlink_link_urls' ) ) :
	/**
	 * @deprecated since 4.0 Use c2c_autohyperlink_link_urls()
	 */
	function autohyperlink_link_urls( $text ) {
		_deprecated_function( __FUNCTION__, '4.0', 'c2c_autohyperlink_link_urls()' );
		return c2c_autohyperlink_link_urls( $text );
	}
endif;
