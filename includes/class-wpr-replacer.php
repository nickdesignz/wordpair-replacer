<?php
/**
 * Frontend replacement.
 *
 * @package WordPairReplacer
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class WPR_Replacer {

	private ?array $pairs = null;
	private array $settings = array();

	/**
	 * Per-pair precomputed pieces used during replacement, indexed by pair id.
	 * Avoids rebuilding the wrapper markup and effect class on every match.
	 *
	 * @var array<int, array{pattern:string,flags:string,wrap_open:string,wrap_close:string,link_open:string,link_close:string,replacement:string}>
	 */
	private array $compiled_pairs = array();

	public function __construct() {
		if ( is_admin() && ! wp_doing_ajax() ) {
			return;
		}

		if ( $this->is_non_html_request() ) {
			return;
		}

		$this->settings = WPR_Settings::get();

		// add_filter( 'the_content', array( $this, 'replace_output' ), 9999 );
		// add_filter( 'widget_text', array( $this, 'replace_output' ), 9999 );
		// add_filter( 'widget_text_content', array( $this, 'replace_output' ), 9999 );
		// add_filter( 'render_block', array( $this, 'replace_output' ), 9999 );
		// add_filter( 'wp_nav_menu_items', array( $this, 'replace_output' ), 9999 );

		add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_frontend_assets' ) );
		add_action( 'wp', array( $this, 'start_buffer' ), 999 );
	}

	/**
	 * Returns true for requests that should never be processed by the replacer
	 * (REST, AJAX outside of plugin actions, cron, XML/JSON output, feeds).
	 */
	private function is_non_html_request(): bool {
		if ( defined( 'REST_REQUEST' ) && REST_REQUEST ) {
			return true;
		}

		if ( defined( 'DOING_CRON' ) && DOING_CRON ) {
			return true;
		}

		if ( defined( 'XMLRPC_REQUEST' ) && XMLRPC_REQUEST ) {
			return true;
		}

		if ( function_exists( 'wp_is_json_request' ) && wp_is_json_request() ) {
			return true;
		}

		if ( function_exists( 'wp_is_jsonp_request' ) && wp_is_jsonp_request() ) {
			return true;
		}

		return false;
	}

	public function enqueue_frontend_assets(): void {
		if ( is_admin() ) {
			return;
		}

		WPR_CSS::ensure_file();
		$paths       = WPR_CSS::upload_dir();
		$css_version = get_option( 'wpr_css_version', WPR_VERSION );

		if ( file_exists( $paths['file'] ) ) {
			wp_enqueue_style(
				'wpr-frontend',
				esc_url_raw( $paths['href'] ),
				array(),
				$css_version
			);
		}

		if ( empty( $this->settings['enable_google_fonts'] ) ) {
			return;
		}

		$fonts = array();

		if ( ! empty( $this->settings['global_style']['font_family'] ) && WPR_Settings::is_google_font( (string) $this->settings['global_style']['font_family'] ) ) {
			$fonts[] = (string) $this->settings['global_style']['font_family'];
		}

		foreach ( $this->get_pairs() as $pair ) {
			if ( (int) $pair->use_custom_style === 1 && ! empty( $pair->font_family ) && WPR_Settings::is_google_font( (string) $pair->font_family ) ) {
				$fonts[] = (string) $pair->font_family;
			}
		}

		$url = WPR_Settings::google_font_url( $fonts );

		if ( '' !== $url ) {
			wp_enqueue_style( 'wpr-google-fonts', esc_url_raw( $url ), array(), md5( $url ) );
		}
	}

	public function print_frontend_css(): void {
		return;
	}

	public function start_buffer(): void {
		if ( is_admin() || is_feed() || is_robots() || is_trackback() ) {
			return;
		}

		if ( $this->is_non_html_request() ) {
			return;
		}

		if ( function_exists( 'wp_using_themes' ) && ! wp_using_themes() ) {
			return;
		}

		ob_start( array( $this, 'replace_output' ) );
	}

	public function replace_output( string $html ): string {
		$pairs = $this->get_pairs();

		if ( empty( $pairs ) || '' === trim( $html ) ) {
			return $html;
		}

		// Pre-compile per-pair replacement data once per request.
		if ( empty( $this->compiled_pairs ) ) {
			$this->compile_pairs( $pairs );
		}

		if ( empty( $this->compiled_pairs ) ) {
			return $html;
		}

		// Fast path: no tags at all, treat whole string as plain text.
		if ( false === stripos( $html, '<' ) ) {
			return $this->replace_plain_text( $html );
		}

		$protected_blocks = array();

		$html = preg_replace_callback(
			'#<(script|style|textarea|pre|code|noscript|svg)\b[^>]*>.*?</\1>#is',
			static function ( array $matches ) use ( &$protected_blocks ): string {
				$key                      = '%%WPR_PROTECTED_' . count( $protected_blocks ) . '%%';
				$protected_blocks[ $key ] = $matches[0];

				return $key;
			},
			$html
		);

		$parts = preg_split( '/(<[^>]+>)/', $html, -1, PREG_SPLIT_DELIM_CAPTURE );

		if ( ! is_array( $parts ) ) {
			return $html;
		}

		$ignore_depth = 0;

		$void_tags_pattern = '#^<(?:br|hr|img|input|meta|link|area|base|col|embed|param|source|track|wbr)\b#i';

		foreach ( $parts as &$part ) {
			if ( '' === $part ) {
				continue;
			}

			$is_tag = ( '<' === $part[0] );

			if ( $is_tag ) {
				$is_closing_tag          = ( '/' === ( $part[1] ?? '' ) );
				$is_self_closing         = ( '/>' === substr( $part, -2 ) ) || ( 1 === preg_match( $void_tags_pattern, $part ) );
				$starts_ignore_container = false;

				if ( ! $is_closing_tag &&
					( false !== stripos( $part, 'data-wpr-ignore' ) || false !== stripos( $part, 'wpr-ignore' ) )
				) {
					$starts_ignore_container = true;
				}

				if ( $ignore_depth > 0 ) {
					if ( $is_closing_tag ) {
						$ignore_depth--;
					} elseif ( ! $is_self_closing ) {
						$ignore_depth++;
					}
				} elseif ( $starts_ignore_container && ! $is_self_closing ) {
					$ignore_depth = 1;
				}

				continue;
			}

			if ( $ignore_depth > 0 ) {
				continue;
			}

			$part = $this->replace_plain_text( $part );
		}

		unset( $part );

		$html = implode( '', $parts );

		if ( ! empty( $protected_blocks ) ) {
			$html = strtr( $html, $protected_blocks );
		}

		return $html;
	}

	/**
	 * Precompiles per-pair pattern, flags and HTML wrappers so the hot path
	 * inside replace_plain_text only does the regex + a single sprintf.
	 *
	 * @param array<int, object> $pairs Active pair rows from the database.
	 */
	private function compile_pairs( array $pairs ): void {
		$special_effects = array(
			'gradient-text',
			'gradient-animation',
			'shimmer-text',
			'stroke-text',
			'hover-highlight',
		);

		$global_effect = isset( $this->settings['global_style']['text_effect'] )
			? WPR_Settings::sanitize_effect( (string) $this->settings['global_style']['text_effect'] )
			: 'none';

		foreach ( $pairs as $pair ) {
			$original    = (string) ( $pair->original_word ?? '' );
			$replacement = (string) ( $pair->replacement_word ?? '' );

			if ( '' === $original || '' === $replacement ) {
				continue;
			}

			$pattern = preg_quote( $original, '#' );

			if ( (int) ( $pair->whole_word ?? 0 ) === 1 ) {
				$pattern = '(?<![\p{L}\p{N}_])' . $pattern . '(?![\p{L}\p{N}_])';
			}

			$flags = (int) ( $pair->case_sensitive ?? 0 ) === 1 ? 'u' : 'iu';

			$effect = 'none';
			if ( (int) ( $pair->use_custom_style ?? 0 ) === 1 && ! empty( $pair->text_effect ) ) {
				$effect = (string) $pair->text_effect;
			} elseif ( 'none' !== $global_effect ) {
				$effect = $global_effect;
			}

			$effect       = WPR_Settings::sanitize_effect( $effect );
			$effect_class = in_array( $effect, $special_effects, true )
				? 'wpr-effect-' . sanitize_html_class( $effect )
				: '';

			$extra_classes = $this->sanitize_class_list( (string) ( $pair->custom_class ?? '' ) );

			$class_attr = trim(
				sprintf(
					'wpr-replaced wpr-replaced-%1$d %2$s %3$s',
					(int) $pair->id,
					$effect_class,
					$extra_classes
				)
			);
			$class_attr = preg_replace( '/\s+/', ' ', $class_attr );

			$span_attrs = 'class="' . esc_attr( $class_attr ) . '"';

			if ( ! empty( $pair->custom_id ) ) {
				$span_attrs .= ' id="' . esc_attr( sanitize_html_class( (string) $pair->custom_id ) ) . '"';
			}

			$wrap_open = '<span ' . $span_attrs . '><span class="wpr-replaced-text" data-wpr-text="'
				. esc_attr( $replacement ) . '">';
			$wrap_close = '</span></span>';

			$link_open  = '';
			$link_close = '';

			if ( ! empty( $pair->link_enabled ) && ! empty( $pair->link_url ) ) {
				$rel = array();
				if ( ! empty( $pair->link_rel_nofollow ) )  { $rel[] = 'nofollow'; }
				if ( ! empty( $pair->link_rel_sponsored ) ) { $rel[] = 'sponsored'; }
				if ( ! empty( $pair->link_rel_noopener ) || '_blank' === (string) ( $pair->link_target ?? '' ) ) {
					$rel[] = 'noopener';
				}

				$link_class_attr = trim(
					'wpr-replaced-link wpr-replaced-' . absint( $pair->id ) . '-link '
					. $this->sanitize_class_list( (string) ( $pair->link_class ?? '' ) )
				);
				$link_class_attr = preg_replace( '/\s+/', ' ', $link_class_attr );

				$link_attrs = array(
					'href="' . esc_url( (string) $pair->link_url ) . '"',
					'class="' . esc_attr( $link_class_attr ) . '"',
				);

				if ( '_blank' === (string) ( $pair->link_target ?? '_self' ) ) {
					$link_attrs[] = 'target="_blank"';
				}
				if ( ! empty( $rel ) ) {
					$link_attrs[] = 'rel="' . esc_attr( implode( ' ', array_unique( $rel ) ) ) . '"';
				}
				if ( ! empty( $pair->link_title ) ) {
					$link_attrs[] = 'title="' . esc_attr( (string) $pair->link_title ) . '"';
				}
				if ( ! empty( $pair->link_aria_label ) ) {
					$link_attrs[] = 'aria-label="' . esc_attr( (string) $pair->link_aria_label ) . '"';
				}
				if ( ! empty( $pair->link_id ) ) {
					$link_attrs[] = 'id="' . esc_attr( sanitize_html_class( (string) $pair->link_id ) ) . '"';
				}

				$link_open  = '<a ' . implode( ' ', $link_attrs ) . '>';
				$link_close = '</a>';
			}

			$this->compiled_pairs[ (int) $pair->id ] = array(
				'pattern'     => '#' . $pattern . '#' . $flags,
				'needle'      => $original,
				'replacement' => $link_open . $wrap_open . esc_html( $replacement ) . $wrap_close . $link_close,
			);
		}
	}

	/**
	 * Splits a class string on whitespace, sanitizes each token and joins it
	 * back together. Prevents multi-class entries from being collapsed by a
	 * single sanitize_html_class() call.
	 */
	private function sanitize_class_list( string $classes ): string {
		$classes = trim( $classes );

		if ( '' === $classes ) {
			return '';
		}

		$tokens = preg_split( '/\s+/', $classes );
		if ( ! is_array( $tokens ) ) {
			return '';
		}

		$clean = array();
		foreach ( $tokens as $token ) {
			$token = sanitize_html_class( $token );
			if ( '' !== $token ) {
				$clean[] = $token;
			}
		}

		return implode( ' ', $clean );
	}
	private function replace_plain_text( string $text ): string {
		if ( '' === $text || empty( $this->compiled_pairs ) ) {
			return $text;
		}

		foreach ( $this->compiled_pairs as $compiled ) {
			// Cheap pre-filter: skip pair if its needle isn't even in the chunk.
			// stripos() is much faster than preg_match() when most chunks won't match.
			if ( false === stripos( $text, $compiled['needle'] ) ) {
				continue;
			}

			$result = preg_replace( $compiled['pattern'], $compiled['replacement'], $text );

			if ( null !== $result ) {
				$text = $result;
			}
		}

		return $text;
	}

	private function get_pairs(): array {
		if ( null !== $this->pairs ) {
			return $this->pairs;
		}

		global $wpdb;

		$table_name = WPR_Activator::table_name();

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		$results = $wpdb->get_results(
			$wpdb->prepare(
				'SELECT *
				FROM ' . $table_name . '
				WHERE is_active = %d
				  AND original_word != %s
				  AND replacement_word != %s
				ORDER BY CHAR_LENGTH(original_word) DESC, id ASC',
				1,
				'',
				''
			)
		);

		$this->pairs = is_array( $results ) ? $results : array();

		return $this->pairs;
	}

	private function effect_class_for_pair( object $pair ): string {
		$effect = 'none';

		if ( (int) $pair->use_custom_style === 1 && ! empty( $pair->text_effect ) ) {
			$effect = (string) $pair->text_effect;
		} elseif ( ! empty( $this->settings['global_style']['text_effect'] ) ) {
			$effect = (string) $this->settings['global_style']['text_effect'];
		}

		$effect = WPR_Settings::sanitize_effect( $effect );

		return 'none' === $effect ? '' : 'wpr-effect-' . sanitize_html_class( $effect );
	}

	private function inline_style_for_pair( object $pair ): string {
		if ( (int) $pair->use_custom_style !== 1 ) {
			return '';
		}

		$style = array();

		foreach ( array_keys( WPR_Settings::style_defaults() ) as $key ) {
			$style[ $key ] = $pair->{$key} ?? WPR_Settings::style_defaults()[ $key ];
		}

		return WPR_Settings::style_to_css( $style, ! empty( $this->settings['enable_google_fonts'] ) );
	}
}
