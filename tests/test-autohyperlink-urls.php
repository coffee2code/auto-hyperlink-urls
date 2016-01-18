<?php

defined( 'ABSPATH' ) or die();

class Autohyperlink_URLs_Test extends WP_UnitTestCase {

	public function setUp() {
		parent::setUp();
		$this->set_option();
	}

	public function tearDown() {
		parent::tearDown();

		// Reset options.
		c2c_AutoHyperlinkURLs::get_instance()->reset_options();

		// Remove hooks.
		remove_filter( 'autohyperlink_urls_class',             array( $this, 'autohyperlink_urls_class' ) );
		remove_filter( 'autohyperlink_urls_link_attributes',   array( $this, 'autohyperlink_urls_link_attributes' ) );
		remove_filter( 'autohyperlink_urls_tlds',              array( $this, 'autohyperlink_urls_tlds' ) );
		remove_filter( 'autohyperlink_urls_exclude_domains',   array( $this, 'autohyperlink_urls_exclude_domains' ) );
	}


	//
	//
	// DATA PROVIDERS
	//
	//


	public static function get_default_filters() {
		return array(
			array( 'the_content' ),
			array( 'the_excerpt' ),
			array( 'widget_text' ),
		);
	}

	public static function get_comment_filters() {
		return array(
			array( 'get_comment_text' ),
			array( 'get_comment_excerpt' ),
		);
	}

	public static function get_protocols() {
		return array(
			array( 'http' ),
			array( 'https' ),
			array( 'ftp' ),
		);
	}

	public static function get_tlds() {
		return array_map( function($v) { return array( $v ); }, explode( '|', c2c_AutoHyperlinkURLs::get_instance()->get_tlds() ) );
	}

	public static function get_ending_punctuation() {
		return array(
			array( '.' ),
			array( ',' ),
			array( '!' ),
			array( '?' ),
			array( ';' ),
			array( ':' ),
		);
	}

	public static function get_punctuation_bookends() {
		return array(
			array( '(', ')' ),
			array( '[', ']' ),
			array( '{', '}' ),
			array( '>', '<' ),
			array( '.', '.' ),
			array( ',', ',' ),
			array( "'", "'" ),
			array( '"', '"' ),
			array( ':', ':' ),
			array( ';', ';' ),
			array( ';', '&' ),
		);
	}


	//
	//
	// HELPER FUNCTIONS
	//
	//


	public function set_option( $settings = array() ) {
		c2c_AutoHyperlinkURLs::get_instance()->load_config();
		$settings = wp_parse_args( $settings, c2c_AutoHyperlinkURLs::get_instance()->get_options() );
		c2c_AutoHyperlinkURLs::get_instance()->update_option( $settings, true );
	}

	public function autolink_text( $text, $args = array() ) {
		return c2c_AutoHyperlinkURLs::get_instance()->hyperlink_urls( $text, $args );
	}

	public function autohyperlink_urls_class( $class ) {
		return 'customclass';
	}

	public function autohyperlink_urls_link_attributes( $attributes ) {
		return $attributes . ' id="id1"';
	}

	public function autohyperlink_urls_tlds( $tlds ) {
		if ( $tlds ) {
			$tlds .= '|';
		}
		return $tlds . 'dev|co|io';
	}

	public function autohyperlink_urls_exclude_domains( $exclusions ) {
		$exclusions[] = 'example.com';
		return $exclusions;
	}

	//
	//
	// TESTS
	//
	//


	public function test_class_exists() {
		$this->assertTrue( class_exists( 'c2c_AutoHyperlinkURLs' ) );
	}

	public function test_plugin_framework_class_name() {
		$this->assertTrue( class_exists( 'c2c_AutoHyperlinkURLs_Plugin_040' ) );
	}

	public function test_plugin_framework_version() {
		$this->assertEquals( '040', c2c_AutoHyperlinkURLs::get_instance()->c2c_plugin_version() );
	}

	public function test_get_version() {
		$this->assertEquals( '5.0', c2c_AutoHyperlinkURLs::get_instance()->version() );
	}

	public function test_instance_object_is_returned() {
		$this->assertTrue( is_a( c2c_AutoHyperlinkURLs::get_instance(), 'c2c_AutoHyperlinkURLs' ) );
	}

	/*
	 * Setting defaults.
	 */

	public function test_default_value_of_hyperlink_comments() {
		$options = c2c_AutoHyperlinkURLs::get_instance()->get_options();
		$this->assertTrue( $options['hyperlink_comments'] );
	}

	public function test_default_value_of_hyperlink_emails() {
		$options = c2c_AutoHyperlinkURLs::get_instance()->get_options();
		$this->assertTrue( $options['hyperlink_emails'] );
	}

	public function test_default_value_of_strip_protocol() {
		$options = c2c_AutoHyperlinkURLs::get_instance()->get_options();
		$this->assertTrue( $options['strip_protocol'] );
	}

	public function test_default_value_of_open_in_new_window() {
		$options = c2c_AutoHyperlinkURLs::get_instance()->get_options();
		$this->assertTrue( $options['open_in_new_window'] );
	}

	public function test_default_value_of_nofollow() {
		$options = c2c_AutoHyperlinkURLs::get_instance()->get_options();
		$this->assertFalse( $options['nofollow'] );
	}

	public function test_default_value_of_hyperlink_mode() {
		$options = c2c_AutoHyperlinkURLs::get_instance()->get_options();
		$this->assertEquals( 0, $options['hyperlink_mode'] );
	}

	public function test_default_value_of_truncation_before_text() {
		$options = c2c_AutoHyperlinkURLs::get_instance()->get_options();
		$this->assertEmpty( $options['truncation_before_text'] );
	}

	public function test_default_value_of_truncation_after_text() {
		$options = c2c_AutoHyperlinkURLs::get_instance()->get_options();
		$this->assertEquals( '...', $options['truncation_after_text'] );
	}

	public function test_default_value_of_more_extensions() {
		$options = c2c_AutoHyperlinkURLs::get_instance()->get_options();
		$this->assertEmpty( $options['more_extensions'] );
	}

	public function test_default_value_of_exclude_domains() {
		$options = c2c_AutoHyperlinkURLs::get_instance()->get_options();
		$this->assertEmpty( $options['exclude_domains'] );
		var_dump($options['exclude_domains']);
		$this->assertTrue( is_array( $options['exclude_domains'] ) );
	}

	/*
	 * Linking.
	 */

	public function test_basic_autolinking( $url = 'http://coffee2code.com', $text = '', $before = '', $after = '', $strip = true ) {
		$out_text = $strip ? preg_replace( '~^.+://(.+)$~', '$1', $url ) : $url;

		if ( empty( $text ) ) {
			$text = $out_text; //preg_replace( '~^.+://(.+)$~', '$1', $url );
		}


		$expected =  $before . '<a href="' . esc_attr( $url ) . '" class="autohyperlink" title="' . esc_attr( $url ) . '" target="_blank">' . $out_text . '</a>' . $after;

		$this->assertEquals(
			$expected,
			c2c_autohyperlink_link_urls( $text )
		);
	}

	public function test_linking_domain_with_trailing_slash() {
		$this->test_basic_autolinking( 'http://coffee2code.com/', 'coffee2code.com/' );
	}

	public function test_linking_URL_with_trailing_slash() {
		$this->test_basic_autolinking( 'http://coffee2code.com/' );
	}

	public function test_linking_domain_with_directories() {
		$this->test_basic_autolinking( 'http://coffee2code.com/wp-plugins/autohyperlink-urls/', 'coffee2code.com/wp-plugins/autohyperlink-urls/' );
	}

	public function test_linking_URL_with_directories() {
		$this->test_basic_autolinking( 'http://coffee2code.com/wp-plugins/autohyperlink-urls/' );
	}

	public function test_linking_domain_with_hashbang_in_path() {
		$this->test_basic_autolinking( "http://twitter.com/#!/coffee2code/" );
	}

	public function test_linking_domain_with_query_args() {
		$this->test_basic_autolinking( 'http://coffee2code.com?emu=1&dog=rocky', 'coffee2code.com?emu=1&dog=rocky' );
	}

	public function test_URI_with_query_args() {
		$this->test_basic_autolinking( 'http://coffee2code.com?emu=1&dog=rocky' );
	}

	public function test_domain_with_encoded_query_args() {
		$this->test_basic_autolinking( 'http://coffee2code.com?emu=1&amp;dog=rocky', 'coffee2code.com?emu=1&amp;dog=rocky' );
	}

	public function test_URI_with_encoded_query_args() {
		$this->test_basic_autolinking( 'http://coffee2code.com?emu=1&amp;dog=rocky' );
	}

	public function test_linking_single_letter_domain() {
		$this->test_basic_autolinking( 'http://w.org', 'w.org' );
	}

	public function test_linking_single_letter_domain_in_URL() {
		$this->test_basic_autolinking( 'https://w.org', 'https://w.org' );
	}

	public function test_linking_two_letter_domain() {
		$this->test_basic_autolinking( 'http://wp.com', 'wp.com' );
	}

	public function test_linking_domain_with_hyphens() {
		$this->test_basic_autolinking( 'http://example-w.org', 'example-w.org' );
	}

	public function test_linking_domain_with_repeating_components() {
		$this->test_basic_autolinking( 'http://org.org.org', 'org.org.org' );
	}

	/**
	 * @dataProvider get_tlds
	 */
	public function test_linking_tlds_with_protocol( $tld ) {
		$this->test_basic_autolinking( "http://coffee2code.{$tld}", "http://coffee2code.{$tld}" );
	}

	/**
	 * @dataProvider get_tlds
	 */
	public function test_linking_tlds_with_no_protocol( $tld ) {
		$this->test_basic_autolinking( "http://coffee2code.{$tld}", "coffee2code.{$tld}" );
	}

	/**
	 * @dataProvider get_protocols
	 */
	public function test_linking_protocols( $protocol ) {
		$this->test_basic_autolinking( "{$protocol}://coffee2code.com", "{$protocol}://coffee2code.com" );
	}

	/**
	 * @dataProvider get_ending_punctuation
	 */
	public function test_linking_when_appended_with_punctuation( $punctuation ) {
		$this->test_basic_autolinking( "http://coffee2code.com", "coffee2code.com{$punctuation}", '', $punctuation );
	}

	/**
	 * @dataProvider get_punctuation_bookends
	 */
	public function test_linking_when_bookended_with_punctuation( $before, $after ) {
		$this->test_basic_autolinking( "http://coffee2code.com", "{$before}coffee2code.com{$after}", $before , $after );
	}

	public function test_does_not_autolink_already_linked_URL() {
		$this->assertEquals(
			'<a href="http://example.com">example.com</a>',
			c2c_autohyperlink_link_urls( '<a href="http://example.com">http://example.com</a>' )
		);
	}

	public function test_does_not_autolink_a_URL_within_linked_sentence() {
		$text = '<a href="http://example.com">Go to the link at example.com if you can</a>';

		$this->assertEquals(
			$text,
			c2c_autohyperlink_link_urls( $text )
		);
	}

	public function test_does_not_autolink_domain_in_tag_attribute() {
		$text = '<a href="http://example.com" title="Or at example.net ok">visit me</a>';

		$this->assertEquals(
			$text,
			c2c_autohyperlink_link_urls( $text )
		);
	}

	public function test_does_not_autolink_domain_immediately_bookended_with_tag_brackets() {
		$text = 'Visit me at <coffee2code.com> if you wish.';

		$this->assertEquals(
			$text,
			c2c_autohyperlink_link_urls( $text )
		);
	}

	public function test_does_not_autolink_URL_in_tag_attribute() {
		$text = '<a href="http://example.com" title="Or at http://example.net ok">visit me</a>';

		$this->assertEquals(
			$text,
			c2c_autohyperlink_link_urls( $text )
		);
	}

	public function test_does_not_autolink_URL_immediately_bookended_with_tag_brackets() {
		$text = 'Visit me at <http://coffee2code.com> if you wish.';

		$this->assertEquals(
			$text,
			c2c_autohyperlink_link_urls( $text )
		);
	}

	/*
	 * Email.
	 */

	public function test_basic_email_autolinking( $email = 'user@example.com', $text = '', $before = '', $after = '' ) {
		if ( empty( $text ) ) {
			$text = $email;
		}


		$expected =  $before . '<a class="autohyperlink" href="mailto:' . esc_attr( $email ) . '" title="mailto:' . esc_attr( $email ) . '">' . $text . '</a>' . $after;

		$this->assertEquals(
			$expected,
			c2c_autohyperlink_link_urls( $text )
		);
	}

	/**
	 * @dataProvider get_tlds
	 */
	public function test_autolink_email_with_tlds( $tld ) {
		$this->test_basic_email_autolinking( "user@example.{$tld}" );
	}

	public function test_does_not_autolink_already_linked_email() {
		$text = '<a href="mailto:test@example.com">test@example.com</a>';

		$this->assertEquals(
			$text,
			c2c_autohyperlink_link_urls( $text )
		);
	}

	public function test_does_not_autolink_email_within_linked_sentence() {
		$text = '<a href="mailto:test@example.com">Email me at test@example.com if you can</a>';

		$this->assertEquals(
			$text,
			c2c_autohyperlink_link_urls( $text )
		);
	}

	public function test_does_not_autolink_email_immediately_bookended_with_tag_brackets() {
		$text = 'Write me at <test@example.com> if you wish.';

		$this->assertEquals(
			$text,
			c2c_autohyperlink_link_urls( $text )
		);
	}
	public function test_does_not_autolink_email_in_tag_attribute() {
		$text = '<a href="http://example.com" title="Or email me at test@example.com ok">visit me</a>';

		$this->assertEquals(
			$text,
			c2c_autohyperlink_link_urls( $text )
		);
	}

	/*
	 * Setting: strip_protocol
	 */

	public function test_strip_protocol_false() {
		$this->set_option( array( 'strip_protocol' => false ) );

		$expected = '<a href="http://coffee2code.com/" class="autohyperlink" title="http://coffee2code.com/" target="_blank">http://coffee2code.com/</a>';

		$this->assertEquals(
			$expected,
			c2c_autohyperlink_link_urls( 'http://coffee2code.com/' )
		);
	}

	public function test_strip_protocol_false_via_args() {
		$expected = '<a href="http://coffee2code.com/" class="autohyperlink" title="http://coffee2code.com/" target="_blank">http://coffee2code.com/</a>';

		$this->assertEquals(
			$expected,
			c2c_autohyperlink_link_urls( 'http://coffee2code.com/', array( 'strip_protocol' => false ) )
		);
	}

	/*
	 * Setting: open_in_new_window
	 */

	public function test_open_in_new_window_false() {
		$this->set_option( array( 'open_in_new_window' => false ) );

		$expected = '<a href="http://coffee2code.com/" class="autohyperlink" title="http://coffee2code.com/">coffee2code.com/</a>';

		$this->assertEquals(
			$expected,
			c2c_autohyperlink_link_urls( 'http://coffee2code.com/' )
		);
	}

	public function test_open_in_new_window_false_via_args() {
		$expected = '<a href="http://coffee2code.com/" class="autohyperlink" title="http://coffee2code.com/">coffee2code.com/</a>';

		$this->assertEquals(
			$expected,
			c2c_autohyperlink_link_urls( 'http://coffee2code.com/', array( 'open_in_new_window' => false ) )
		);
	}

	/*
	 * Setting: nofollow
	 */

	public function test_nofollow_true() {
		$this->set_option( array( 'nofollow' => true ) );

		$expected = '<a href="http://coffee2code.com/" class="autohyperlink" title="http://coffee2code.com/" target="_blank" rel="nofollow">coffee2code.com/</a>';

		$this->assertEquals(
			$expected,
			c2c_autohyperlink_link_urls( 'http://coffee2code.com/' )
		);
	}

	public function test_nofollow_true_via_args() {
		$expected = '<a href="http://coffee2code.com/" class="autohyperlink" title="http://coffee2code.com/" target="_blank" rel="nofollow">coffee2code.com/</a>';

		$this->assertEquals(
			$expected,
			c2c_autohyperlink_link_urls( 'http://coffee2code.com/', array( 'nofollow' => true ) )
		);
	}

	/*
	 * Setting: more_extensions
	 */

	public function test_space_separated_more_extensions() {
		$link = 'coffee2code.io';

		$this->assertEquals(
			$link,
			c2c_autohyperlink_link_urls( $link )
		);

		$this->set_option( array( 'more_extensions' => 'co io' ) );

		$this->assertEquals(
			'<a href="http://coffee2code.io" class="autohyperlink" title="http://coffee2code.io" target="_blank">coffee2code.io</a>',
			c2c_autohyperlink_link_urls( $link )
		);

	}

	public function test_comma_separated_more_extensions() {
		$link = 'coffee2code.io';

		$this->assertEquals(
			$link,
			c2c_autohyperlink_link_urls( $link )
		);

		$this->set_option( array( 'more_extensions' => 'co,io' ) );

		$this->assertEquals(
			'<a href="http://coffee2code.io" class="autohyperlink" title="http://coffee2code.io" target="_blank">coffee2code.io</a>',
			c2c_autohyperlink_link_urls( $link )
		);

	}

	public function test_comma_and_space_separated_more_extensions() {
		$link = 'coffee2code.io';

		$this->assertEquals(
			$link,
			c2c_autohyperlink_link_urls( $link )
		);

		$this->set_option( array( 'more_extensions' => 'co, io' ) );

		$this->assertEquals(
			'<a href="http://coffee2code.io" class="autohyperlink" title="http://coffee2code.io" target="_blank">coffee2code.io</a>',
			c2c_autohyperlink_link_urls( $link )
		);

	}

	/*
	 * Setting: exclude_domains
	 */

	public function test_exclude_domains() {
		$this->set_option( array( 'exclude_domains' => array( 'example.com') ) );

		$text = 'example.com';

		$this->assertEquals(
			$text,
			c2c_autohyperlink_link_urls( $text )
		);
	}

	/*
	 * Filters
	 */

	public function test_filter_autohyperlink_urls_tlds() {
		$link = 'coffee2code.io';

		$this->assertEquals(
			$link,
			c2c_autohyperlink_link_urls( $link )
		);
		add_filter( 'autohyperlink_urls_tlds', array( $this, 'autohyperlink_urls_tlds' ) );

		$this->assertEquals(
			'<a href="http://coffee2code.io" class="autohyperlink" title="http://coffee2code.io" target="_blank">coffee2code.io</a>',
			c2c_autohyperlink_link_urls( $link )
		);
	}

	public function test_filter_autohyperlink_urls_class() {
		add_filter( 'autohyperlink_urls_class', array( $this, 'autohyperlink_urls_class' ) );

		$expected = '<a href="http://coffee2code.com/" class="customclass" title="http://coffee2code.com/" target="_blank">coffee2code.com/</a>';

		$this->assertEquals(
			$expected,
			c2c_autohyperlink_link_urls( 'http://coffee2code.com/' )
		);
	}

	public function test_filter_autohyperlink_urls_link_attributes() {
		add_filter( 'autohyperlink_urls_link_attributes', array( $this, 'autohyperlink_urls_link_attributes' ) );

		$expected = '<a href="http://coffee2code.com/" class="autohyperlink" title="http://coffee2code.com/" target="_blank" id="id1">coffee2code.com/</a>';

		$this->assertEquals(
			$expected,
			c2c_autohyperlink_link_urls( 'http://coffee2code.com/' )
		);
	}

	public function test_filter_autohyperlink_urls_exclude_domains() {
		add_filter( 'autohyperlink_urls_exclude_domains', array( $this, 'autohyperlink_urls_exclude_domains' ) );

		$text = 'Visit example.com soon.';

		$this->assertEquals(
			$text,
			c2c_autohyperlink_link_urls( $text )
		);
	}

	public function test_filter_autohyperlink_urls_custom_exclusions_recognizes_false() {
		add_filter( 'autohyperlink_urls_custom_exclusions', array( $this, 'autohyperlink_urls_custom_exclusions' ), 10, 3 );

		$texts = array(
			'Visit example.com soon.',
			'Visit http://example.com soon.',
		);

		foreach ( $texts as $text ) {
			$this->assertEquals(
				$text,
				c2c_autohyperlink_link_urls( $text )
			);
		}
	}

	/*
	 * Misc
	 */

	public function test_uninstall_deletes_option() {
		$option = 'c2c_autohyperlink_urls';
		c2c_AutoHyperlinkURLs::get_instance()->get_options();

		$this->assertNotFalse( get_option( $option ) );

		c2c_AutoHyperlinkURLs::uninstall();

		$this->assertFalse( get_option( $option ) );
	}

}
