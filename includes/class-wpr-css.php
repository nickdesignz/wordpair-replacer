<?php
/**
 * Frontend CSS file generator.
 *
 * @package WordPairReplacer
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class WPR_CSS {

	public static function upload_dir(): array {
		$upload = wp_upload_dir( null, false );

		if ( ! empty( $upload['error'] ) ) {
			return array(
				'dir'  => '',
				'url'  => '',
				'file' => '',
				'href' => '',
			);
		}
		$dir    = trailingslashit( $upload['basedir'] ) . 'wordpair-replacer';
		$baseurl = set_url_scheme( $upload['baseurl'], is_ssl() ? 'https' : 'http' );
		$url     = trailingslashit( $baseurl ) . 'wordpair-replacer';

		return array(
			'dir'  => $dir,
			'url'  => $url,
			'file' => trailingslashit( $dir ) . 'wpr-frontend.css',
			'href' => set_url_scheme( trailingslashit( $url ) . 'wpr-frontend.css', is_ssl() ? 'https' : 'http' ),
		);
	}

	public static function get_pairs(): array {
		global $wpdb;

		$table = WPR_Activator::table_name();

		$query = 'SELECT *
			FROM ' . $table . "
			WHERE is_active = 1
			  AND original_word != ''
			  AND replacement_word != ''
			ORDER BY CHAR_LENGTH(original_word) DESC, id ASC";

		// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, PluginCheck.Security.DirectDB.UnescapedDBParameter
		$pairs = $wpdb->get_results( $query );

		return is_array( $pairs ) ? $pairs : array();
	}

	public static function ensure_file(): void {
		$paths = self::upload_dir();

		if ( empty( $paths['file'] ) ) {
			return;
		}

		if ( ! file_exists( $paths['file'] ) ) {
			self::generate();
		}
	}

	public static function generate(): bool {
		$paths = self::upload_dir();

		if ( empty( $paths['dir'] ) || empty( $paths['file'] ) || ! wp_mkdir_p( $paths['dir'] ) ) {
			return false;
		}

		$settings       = WPR_Settings::get();
		$google_enabled = ! empty( $settings['enable_google_fonts'] );
		$pairs          = self::get_pairs();
		$effects        = array();

		$css  = "/* WordPair Replacer frontend CSS. Auto-generated. Do not edit manually. */\n";
		$css .= ".wpr-replaced{display:inline;position:relative;" . WPR_Settings::style_to_css( $settings['global_style'] ?? array(), $google_enabled ) . "}\n";
		$css .= ".wpr-replaced>.wpr-replaced-text{position:relative;z-index:1;display:inline;line-height:inherit;}\n";
		$global_text_layer_css = WPR_Settings::text_layer_css( $settings['global_style'] ?? array() );
		if ( '' !== $global_text_layer_css ) {
			$css .= ".wpr-replaced>.wpr-replaced-text{" . $global_text_layer_css . "}\n";
			$css .= WPR_Settings::text_shadow_pseudo_css( '.wpr-replaced>.wpr-replaced-text', $settings['global_style'] ?? array() );
		}

		$global_effect = WPR_Settings::sanitize_effect( (string) ( $settings['global_style']['text_effect'] ?? 'none' ) );

		if ( 'none' !== $global_effect ) {
			$effects[] = $global_effect;
		}

		foreach ( $pairs as $pair ) {
			$style = array();

			foreach ( array_keys( WPR_Settings::style_defaults() ) as $key ) {
				$style[ $key ] = $pair->{$key} ?? '';
			}

			$has_custom_style = (int) $pair->use_custom_style === 1 || self::pair_has_custom_style_values( $style );

			if ( $has_custom_style ) {
				$style = array_replace( WPR_Settings::style_defaults(), array_filter( $style, static function ( $value ) { return '' !== (string) $value; } ) );

				$pair_css = WPR_Settings::style_to_css( $style, $google_enabled );

				if ( ! empty( $style['pair_custom_css'] ) ) {
					$pair_css .= WPR_Settings::sanitize_custom_css_declarations( (string) $style['pair_custom_css'] );
				}

				$css .= '.wpr-replaced-' . absint( $pair->id ) . '{' . $pair_css . "}\n";

				$text_layer_css = WPR_Settings::text_layer_css( $style );
				if ( '' !== $text_layer_css ) {
					$text_layer_selector = '.wpr-replaced-' . absint( $pair->id ) . '>.wpr-replaced-text';
					$css .= $text_layer_selector . '{' . $text_layer_css . "}\n";
					$css .= WPR_Settings::text_shadow_pseudo_css( $text_layer_selector, $style );
				}
			}

			if ( ! empty( $pair->link_enabled ) ) {
				$link_css = '';
				$link_color = sanitize_hex_color( $pair->link_color ?? '' );
				$link_hover = sanitize_hex_color( $pair->link_hover_color ?? '' );
				if ( $link_color ) {
					$link_css .= 'color:' . $link_color . ';';
				}
				$link_css .= 'text-decoration:none;';
				$css .= '.wpr-replaced-' . absint( $pair->id ) . '-link{' . $link_css . "}\n";
				$hover_css = '';
				if ( $link_hover ) {
					$hover_css .= 'color:' . $link_hover . ';';
				}
				if ( ! empty( $pair->link_underline_hover ) ) {
					$hover_css .= 'text-decoration:underline;';
				}
				if ( '' !== $hover_css ) {
					$css .= '.wpr-replaced-' . absint( $pair->id ) . '-link:hover{' . $hover_css . "}\n";
				}
			}

			$effect = WPR_Settings::sanitize_effect( (string) ( $style['text_effect'] ?? 'none' ) );

			if ( 'none' !== $effect && $has_custom_style ) {
				$effects[] = $effect;
			}
		}

		$effects = array_values( array_unique( $effects ) );

		foreach ( $effects as $effect ) {
			$css .= self::keyframe_css( $effect );
		}

		$css .= self::special_effect_css( $effects );
		$css .= isset( $settings['custom_css'] ) ? "\n" . trim( (string) $settings['custom_css'] ) . "\n" : '';

		if ( ! self::write_css_atomic( $paths['file'], $css ) ) {
			return false;
		}

		update_option( 'wpr_css_version', (string) time(), false );

		return true;
	}

	/**
	 * Writes the CSS file atomically. Writes to a temporary sibling file with
	 * an exclusive lock and renames it into place so concurrent writers cannot
	 * leave a half-written stylesheet behind.
	 */
	private static function write_css_atomic( string $target, string $contents ): bool {
		$dir = dirname( $target );

		if ( ! is_dir( $dir ) ) {
			return false;
		}

		$temp = $target . '.tmp-' . wp_generate_password( 8, false, false );

		$bytes = file_put_contents( $temp, $contents, LOCK_EX );
		if ( false === $bytes ) {
			return false;
		}

		// Try to keep file permissions consistent with WordPress defaults.
		if ( function_exists( 'wp_chmod' ) ) {
			@chmod( $temp, FS_CHMOD_FILE );
		}

		if ( ! @rename( $temp, $target ) ) {
			// Fallback: write directly with LOCK_EX so we never leave the temp file behind.
			@unlink( $temp );
			return false !== file_put_contents( $target, $contents, LOCK_EX );
		}

		return true;
	}

	private static function pair_has_custom_style_values( array $style ): bool {
		$defaults = WPR_Settings::style_defaults();

		foreach ( $defaults as $key => $default ) {
			if ( ! array_key_exists( $key, $style ) ) {
				continue;
			}

			$value = (string) $style[ $key ];

			if ( '' !== $value && $value !== (string) $default ) {
				return true;
			}
		}

		return false;
	}

	private static function keyframe_css( string $effect ): string {
		$keyframes = array(
			'fade-in'            => "@keyframes wpr-fade-in{from{opacity:0}to{opacity:1}}\n",
			'slide-in-left'      => "@keyframes wpr-slide-in-left{from{opacity:0;transform:translateX(-18px)}to{opacity:1;transform:translateX(0)}}\n",
			'slide-in-right'     => "@keyframes wpr-slide-in-right{from{opacity:0;transform:translateX(18px)}to{opacity:1;transform:translateX(0)}}\n",
			'slide-in-up'        => "@keyframes wpr-slide-in-up{from{opacity:0;transform:translateY(18px)}to{opacity:1;transform:translateY(0)}}\n",
			'slide-in-down'      => "@keyframes wpr-slide-in-down{from{opacity:0;transform:translateY(-18px)}to{opacity:1;transform:translateY(0)}}\n",
			'zoom-in'            => "@keyframes wpr-zoom-in{from{opacity:0;transform:scale(.86)}to{opacity:1;transform:scale(1)}}\n",
			'bounce'             => "@keyframes wpr-bounce{0%{transform:scale(.9)}50%{transform:scale(1.08)}100%{transform:scale(1)}}\n",
			'pulse'              => "@keyframes wpr-pulse{0%{transform:scale(1)}50%{transform:scale(1.05)}100%{transform:scale(1)}}\n",
			'shake'              => "@keyframes wpr-shake{0%,100%{transform:translateX(0)}20%,60%{transform:translateX(-3px)}40%,80%{transform:translateX(3px)}}\n",
			'blur-in'            => "@keyframes wpr-blur-in{from{opacity:0;filter:blur(8px)}to{opacity:1;filter:blur(0)}}\n",
			'typing'             => "@keyframes wpr-typing{from{clip-path:inset(0 100% 0 0)}to{clip-path:inset(0 0 0 0)}}\n",
			'glitch'             => "@keyframes wpr-glitch{0%{text-shadow:2px 0 var(--wpr-effect-color,#24afab),-2px 0 var(--wpr-effect-color-2,#ff3b6b)}25%{text-shadow:-2px 0 var(--wpr-effect-color,#24afab),2px 0 var(--wpr-effect-color-2,#ff3b6b)}50%{text-shadow:1px 0 var(--wpr-effect-color,#24afab),-1px 0 var(--wpr-effect-color-2,#ff3b6b)}100%{text-shadow:none}}\n",
			'neon-glow'          => "@keyframes wpr-neon-glow{0%,100%{text-shadow:0 0 3px var(--wpr-effect-color,currentColor)}50%{text-shadow:0 0 var(--wpr-effect-strength,12px) var(--wpr-effect-color,currentColor)}}\n",
			'text-shadow-glow'   => "@keyframes wpr-text-shadow-glow{from{text-shadow:none}to{text-shadow:0 0 var(--wpr-effect-blur,10px) var(--wpr-effect-color,currentColor)}}\n",
			'gradient-animation' => "@keyframes wpr-gradient-animation{0%{background-position:0% 50%}100%{background-position:100% 50%}}\n",
			'letter-spacing'     => "@keyframes wpr-letter-spacing{from{letter-spacing:-.04em;opacity:0}to{letter-spacing:.04em;opacity:1}}\n",
			'reveal'             => "@keyframes wpr-reveal{from{clip-path:inset(0 100% 0 0)}to{clip-path:inset(0 0 0 0)}}\n",
			'underline-slide'    => "@keyframes wpr-underline-slide{from{background-size:0 2px}to{background-size:100% 2px}}\n",
			'wave-text'          => "@keyframes wpr-wave-text{0%,100%{transform:translateY(0)}50%{transform:translateY(-3px)}}\n",
			'mask-reveal'        => "@keyframes wpr-mask-reveal{from{mask-size:0% 100%;-webkit-mask-size:0% 100%}to{mask-size:100% 100%;-webkit-mask-size:100% 100%}}\n",
			'clip-path-reveal'   => "@keyframes wpr-clip-path-reveal{from{clip-path:polygon(0 0,0 0,0 100%,0 100%)}to{clip-path:polygon(0 0,100% 0,100% 100%,0 100%)}}\n",
			'shimmer-text'       => "@keyframes wpr-shimmer-text{0%{background-position:-200% center}100%{background-position:200% center}}\n",
			'outline-fill'       => "@keyframes wpr-outline-fill{from{-webkit-text-fill-color:transparent}to{-webkit-text-fill-color:currentColor}}\n",
			'three-d-text'       => "@keyframes wpr-three-d-text{from{text-shadow:0 0 0 rgba(0,0,0,0)}to{text-shadow:1px 1px 0 rgba(0,0,0,.2),2px 2px 0 rgba(0,0,0,.12)}}\n",
			'skew-reveal'        => "@keyframes wpr-skew-reveal{from{opacity:0;transform:skewX(-12deg) translateY(8px)}to{opacity:1;transform:skewX(0) translateY(0)}}\n",
		);

		return $keyframes[ $effect ] ?? '';
	}

	private static function special_effect_css( array $effects ): string {
		$css = '';

		if ( array_intersect( $effects, array( 'gradient-text', 'gradient-animation', 'shimmer-text' ) ) ) {
			$css .= ".wpr-effect-gradient-text>.wpr-replaced-text,.wpr-effect-gradient-animation>.wpr-replaced-text,.wpr-effect-shimmer-text>.wpr-replaced-text{background:linear-gradient(90deg,var(--wpr-effect-color,#24afab),var(--wpr-effect-color-2,#6c5ce7),var(--wpr-effect-color,#24afab));background-size:200% auto;-webkit-background-clip:text;background-clip:text;color:transparent!important;}\n";
		}

		if ( in_array( 'stroke-text', $effects, true ) ) {
			$css .= ".wpr-effect-stroke-text>.wpr-replaced-text{color:transparent!important;-webkit-text-stroke:var(--wpr-effect-strength,1px) var(--wpr-effect-color,#24afab);}\n";
		}

		if ( in_array( 'hover-highlight', $effects, true ) ) {
			$css .= ".wpr-effect-hover-highlight{transition:background-color .25s ease,color .25s ease}.wpr-effect-hover-highlight:hover{background-color:var(--wpr-effect-color,#24afab)!important;color:#fff!important;}\n";
		}

		return $css;
	}
}
