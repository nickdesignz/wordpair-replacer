<?php
/**
 * Settings helper.
 *
 * @package WordPairReplacer
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class WPR_Settings {

	public const OPTION_KEY = 'wpr_settings';

	public static function defaults(): array {
		return array(
			'plugin_language'     => 'en_US',
			'enable_google_fonts' => 0,
			'global_style'        => self::style_defaults(),
			'custom_css'          => '',
			'security_monitor_enabled' => 0,
			'security_wpvulnerability_enabled' => 0,
			'security_wordfence_enabled' => 0,
			'security_wpscan_enabled' => 0,
			'security_wpscan_api_token' => '',
			'security_patchstack_enabled' => 0,
			'security_patchstack_api_key' => '',
		);
	}

	public static function style_defaults(): array {
		return array(
			'font_family'        => 'inherit',
			'font_size'          => '',
			'font_size_unit'     => 'px',
			'font_weight'        => '',
			'line_height'        => '',
			'font_style'         => 'normal',
			'text_decoration'    => 'none',
			'text_transform'     => 'none',
			'letter_spacing'     => '',
			'letter_spacing_unit' => 'px',
			'word_spacing'       => '',
			'word_spacing_unit'  => 'px',
			'white_space'        => '',
			'color'              => '',
			'background_color'   => '',
			'bg_gradient_enabled' => '0',
			'bg_gradient_color_1' => '#24afab',
			'bg_gradient_color_2' => '#6c5ce7',
			'bg_gradient_type'    => 'linear',
			'bg_gradient_angle'   => '90',
			'bg_gradient_position' => 'center',
			'gradient_enabled'  => '',
			'gradient_color_1'  => '#24afab',
			'gradient_color_2'  => '#6c5ce7',
			'gradient_type'     => 'linear',
			'gradient_angle'    => '90',
			'gradient_position' => 'center',
			'text_shadow_enabled' => '0',
			'text_shadow_color' => '#000000',
			'text_shadow_x'     => '',
			'text_shadow_y'     => '',
			'text_shadow_blur'  => '',
			'border_width'       => '',
			'border_width_unit'  => 'px',
			'border_width_t'     => '',
			'border_width_r'     => '',
			'border_width_b'     => '',
			'border_width_l'     => '',
			'border_style'       => 'solid',
			'border_color'       => '#24afab',
			'border_radius_t'    => '',
			'border_radius_r'    => '',
			'border_radius_b'    => '',
			'border_radius_l'    => '',
			'border_radius_unit' => 'px',
			'padding_t'          => '',
			'padding_r'          => '',
			'padding_b'          => '',
			'padding_l'          => '',
			'padding_unit'       => 'px',
			'text_effect'        => 'none',
			'animation_duration' => '600',
			'animation_delay'    => '0',
			'animation_loop'     => '0',
			'animation_timing'   => 'ease-in-out',
			'effect_color'        => '#24afab',
			'effect_color_2'      => '#6c5ce7',
			'effect_strength'     => '12',
			'effect_blur'         => '10',
			'custom_class'       => '',
			'custom_id'          => '',
			'pair_custom_css'    => '',
			'link_enabled'       => '0',
			'link_url'           => '',
			'link_target'        => '_self',
			'link_rel_nofollow'  => '0',
			'link_rel_sponsored' => '0',
			'link_rel_noopener'  => '1',
			'link_title'         => '',
			'link_aria_label'    => '',
			'link_class'         => '',
			'link_id'            => '',
			'link_color'         => '',
			'link_hover_color'   => '',
			'link_underline_hover' => '1',
		);
	}

	public static function get(): array {
		$settings = get_option( self::OPTION_KEY, array() );

		if ( ! is_array( $settings ) ) {
			$settings = array();
		}

		return array_replace_recursive( self::defaults(), $settings );
	}

	public static function update( array $input ): void {
		$settings = self::get();

		$language = isset( $input['plugin_language'] ) ? sanitize_text_field( wp_unslash( $input['plugin_language'] ) ) : 'en_US';
		$settings['plugin_language'] = in_array( $language, array( 'en_US', 'de_DE' ), true ) ? $language : 'en_US';

		$settings['enable_google_fonts'] = ! empty( $input['enable_google_fonts'] ) ? 1 : 0;

		$settings['security_monitor_enabled'] = ! empty( $input['security_monitor_enabled'] ) ? 1 : 0;

		/*
		 * External vulnerability providers (WPVulnerability, Wordfence, WPScan,
		 * Patchstack) are not wired up to any outbound request yet. Force these
		 * off/empty on every save so no real API key is ever persisted for a
		 * feature that does not use it, regardless of what the client submits.
		 */
		$settings['security_wpvulnerability_enabled'] = 0;
		$settings['security_wordfence_enabled']       = 0;
		$settings['security_wpscan_enabled']          = 0;
		$settings['security_patchstack_enabled']      = 0;
		$settings['security_wpscan_api_token']        = '';
		$settings['security_patchstack_api_key']      = '';

		foreach ( array_keys( self::style_defaults() ) as $key ) {
			$settings['global_style'][ $key ] = isset( $input['global_style'][ $key ] )
				? sanitize_text_field( wp_unslash( $input['global_style'][ $key ] ) )
				: self::style_defaults()[ $key ];
		}

		$settings['custom_css'] = isset( $input['custom_css'] )
			? wp_strip_all_tags( wp_unslash( $input['custom_css'] ) )
			: '';

		update_option( self::OPTION_KEY, $settings, false );
	}

	public static function update_language( string $language ): void {
		$settings = self::get();
		$settings['plugin_language'] = in_array( $language, array( 'en_US', 'de_DE' ), true ) ? $language : 'en_US';
		update_option( self::OPTION_KEY, $settings, false );
	}

	public static function allowed_plugin_languages(): array {
		return array(
			'en_US' => '🇬🇧 English',
			'de_DE' => '🇩🇪 Deutsch',
		);
	}

	public static function allowed_units(): array {
		return array( 'px', 'em', 'rem', '%' );
	}

	public static function allowed_border_styles(): array {
		return array(
			'solid'  => wpr_t( 'Durchgezogen' ),
			'dashed' => wpr_t( 'Gestrichelt' ),
			'dotted' => wpr_t( 'Gepunktet' ),
			'double' => wpr_t( 'Doppelt' ),
			'none'   => wpr_t( 'Keine' ),
		);
	}

	public static function allowed_effects(): array {
		return array(
			'none'               => wpr_t( 'Keine Animation' ),
			'fade-in'            => 'Fade In',
			'slide-in-left'      => 'Slide In Left',
			'slide-in-right'     => 'Slide In Right',
			'slide-in-up'        => 'Slide In Up',
			'slide-in-down'      => 'Slide In Down',
			'zoom-in'            => 'Zoom In',
			'bounce'             => 'Bounce',
			'pulse'              => 'Pulse',
			'shake'              => 'Shake',
			'blur-in'            => 'Blur In',
			'typing'             => 'Typing Effect',
			'glitch'             => 'Glitch',
			'neon-glow'          => 'Neon Glow',
			'text-shadow-glow'   => 'Text Shadow Glow',
			'gradient-text'      => 'Gradient Text',
			'gradient-animation' => 'Gradient Animation',
			'letter-spacing'     => 'Letter Spacing',
			'reveal'             => 'Reveal',
			'underline-slide'    => 'Underline Slide',
			'wave-text'          => 'Wave Text',
			'mask-reveal'        => 'Mask Reveal',
			'clip-path-reveal'   => 'Clip Path Reveal',
			'shimmer-text'       => 'Shimmer Text',
			'stroke-text'        => 'Stroke Text',
			'outline-fill'       => 'Outline Fill',
			'three-d-text'       => '3D Text',
			'hover-highlight'    => 'Hover Highlight',
			'skew-reveal'        => 'Skew Reveal',
			'fade-up-words'      => 'Fade Up Words',
			'fade-in-letters'    => 'Fade In Letters',
		);
	}

	public static function allowed_system_fonts(): array {
		return array(
			'inherit'                      => wpr_t( 'Theme-Schrift übernehmen' ),
			'Arial, sans-serif'            => 'Arial',
			'Helvetica, Arial, sans-serif' => 'Helvetica',
			'Georgia, serif'               => 'Georgia',
			'Times New Roman, serif'       => 'Times New Roman',
			'Verdana, sans-serif'          => 'Verdana',
			'Tahoma, sans-serif'           => 'Tahoma',
			'Trebuchet MS, sans-serif'     => 'Trebuchet MS',
			'Courier New, monospace'       => 'Courier New',
			'system-ui, sans-serif'        => 'System UI',
		);
	}

	public static function allowed_google_fonts(): array {
		return array(
			'Roboto',
			'Open Sans',
			'Lato',
			'Montserrat',
			'Poppins',
			'Source Sans 3',
			'Merriweather',
			'Playfair Display',
			'Inter',
			'Nunito',
		);
	}

	public static function is_google_font( string $font ): bool {
		return in_array( $font, self::allowed_google_fonts(), true );
	}

	public static function sanitize_pair_style( array $input ): array {
		$output = array( 'use_custom_style' => ! empty( $input['use_custom_style'] ) ? 1 : 0 );

	foreach ( array_keys( self::style_defaults() ) as $key ) {
		$output[ $key ] = isset( $input[ $key ] )
			? sanitize_text_field( wp_unslash( $input[ $key ] ) )
			: self::style_defaults()[ $key ];
	}

	if ( isset( $input['pair_custom_css'] ) ) {
		$output['pair_custom_css'] = self::sanitize_custom_css_declarations( (string) wp_unslash( $input['pair_custom_css'] ) );
	}

	$output['animation_timing'] = self::sanitize_animation_timing(
		(string) ( $output['animation_timing'] ?? 'ease-in-out' )
	);
	$output['font_style']      = self::sanitize_font_style( (string) ( $output['font_style'] ?? 'normal' ) );
	$output['text_decoration'] = self::sanitize_text_decoration( (string) ( $output['text_decoration'] ?? 'none' ) );
	$output['text_transform']  = self::sanitize_text_transform( (string) ( $output['text_transform'] ?? 'none' ) );
	$output['white_space']     = self::sanitize_white_space( (string) ( $output['white_space'] ?? '' ) );
	$output['gradient_type']   = self::sanitize_gradient_type( (string) ( $output['gradient_type'] ?? 'linear' ) );
	$output['gradient_position'] = self::sanitize_gradient_position( (string) ( $output['gradient_position'] ?? 'center' ) );
	$output['bg_gradient_type'] = self::sanitize_gradient_type( (string) ( $output['bg_gradient_type'] ?? 'linear' ) );
	$output['bg_gradient_position'] = self::sanitize_gradient_position( (string) ( $output['bg_gradient_position'] ?? 'center' ) );

		return $output;
	}

	public static function normalize_font_family( string $font_family, bool $google_fonts_enabled ): string {
		$font_family = trim( $font_family );

		if ( '' === $font_family || 'inherit' === $font_family ) {
			return 'inherit';
		}

		if ( self::is_google_font( $font_family ) ) {
			return $google_fonts_enabled ? "'" . $font_family . "', sans-serif" : 'inherit';
		}

		$system_fonts = self::allowed_system_fonts();

		return array_key_exists( $font_family, $system_fonts ) ? $font_family : 'inherit';
	}

	public static function sanitize_unit( string $unit ): string {
		return in_array( $unit, self::allowed_units(), true ) ? $unit : 'px';
	}

	public static function sanitize_border_style( string $style ): string {
		return array_key_exists( $style, self::allowed_border_styles() ) ? $style : 'solid';
	}

	public static function sanitize_effect( string $effect ): string {
		return array_key_exists( $effect, self::allowed_effects() ) ? $effect : 'none';
	}

	public static function sanitize_font_style( string $style ): string {
		return in_array( $style, array( 'normal', 'italic', 'oblique' ), true ) ? $style : 'normal';
	}

	public static function sanitize_text_decoration( string $decoration ): string {
		return in_array( $decoration, array( 'none', 'underline', 'overline', 'line-through' ), true ) ? $decoration : 'none';
	}

	public static function sanitize_text_transform( string $transform ): string {
		return in_array( $transform, array( 'none', 'uppercase', 'lowercase', 'capitalize' ), true ) ? $transform : 'none';
	}

	public static function sanitize_white_space( string $white_space ): string {
		return in_array( $white_space, array( '', 'normal', 'nowrap' ), true ) ? $white_space : '';
	}
	
	public static function sanitize_animation_timing( string $timing ): string {
		$allowed = array(
			'ease',
			'linear',
			'ease-in',
			'ease-out',
			'ease-in-out',
		);

		return in_array( $timing, $allowed, true ) ? $timing : 'ease-in-out';
	}



	public static function text_layer_css( array $style ): string {
		$css = '';

		if ( ! empty( $style['gradient_enabled'] ) ) {
			$css .= 'background-image:' . self::gradient_css_value( $style ) . ';';
			$css .= 'background-size:100% 100%;';
			$css .= '-webkit-background-clip:text;background-clip:text;color:transparent;';
		}

		if ( ! empty( $style['text_shadow_enabled'] ) ) {
			$shadow_color = sanitize_hex_color( $style['text_shadow_color'] ?? '#000000' ) ?: '#000000';
			$shadow_x     = self::css_value( $style['text_shadow_x'] ?? '0', 'px', 0 );
			$shadow_y     = self::css_value( $style['text_shadow_y'] ?? '2', 'px', 2 );
			$shadow_blur  = self::css_value( $style['text_shadow_blur'] ?? '8', 'px', 8 );

			if ( ! empty( $style['gradient_enabled'] ) ) {
				$css .= 'position:relative;display:inline-block;isolation:isolate;';
				$css .= '--wpr-text-shadow:' . $shadow_x . ' ' . $shadow_y . ' ' . $shadow_blur . ' ' . $shadow_color . ';';
			} else {
				$css .= 'text-shadow:' . $shadow_x . ' ' . $shadow_y . ' ' . $shadow_blur . ' ' . $shadow_color . ';';
			}
		}

		return $css;
	}

	public static function text_shadow_pseudo_css( string $selector, array $style ): string {
		if ( empty( $style['gradient_enabled'] ) || empty( $style['text_shadow_enabled'] ) ) {
			return '';
		}

		return $selector . '::before{content:attr(data-wpr-text);position:absolute;left:0;top:0;z-index:-1;color:transparent;text-shadow:var(--wpr-text-shadow);pointer-events:none;white-space:inherit;}' . "\n";
	}

	public static function sanitize_custom_css_declarations( string $css ): string {
		$css = wp_strip_all_tags( $css );
		$css = str_replace( array( '{', '}', '<', '>' ), '', $css );
		$css = preg_replace( '#/\*.*?\*/#s', '', $css );
		return trim( (string) $css );
	}

	private static function css_value( mixed $value, string $unit, float $fallback = 0 ): string {
		$value  = trim( (string) $value );
		$number = '' === $value ? $fallback : (float) str_replace( ',', '.', $value );

		if ( $number < 0 ) {
			$number = 0;
		}

		return rtrim( rtrim( number_format( $number, 2, '.', '' ), '0' ), '.' ) . self::sanitize_unit( $unit );
	}


	public static function sanitize_gradient_type( string $type ): string {
		return in_array( $type, array( 'linear', 'radial' ), true ) ? $type : 'linear';
	}

	public static function sanitize_gradient_position( string $position ): string {
		return in_array( $position, array( 'center', 'top', 'right', 'bottom', 'left' ), true ) ? $position : 'center';
	}

	public static function gradient_css_value( array $style ): string {
		$color_1  = sanitize_hex_color( $style['gradient_color_1'] ?? '#24afab' ) ?: '#24afab';
		$color_2  = sanitize_hex_color( $style['gradient_color_2'] ?? '#6c5ce7' ) ?: '#6c5ce7';
		$type     = self::sanitize_gradient_type( (string) ( $style['gradient_type'] ?? 'linear' ) );
		$angle    = max( 0, min( 360, absint( $style['gradient_angle'] ?? 90 ) ) );
		$position = self::sanitize_gradient_position( (string) ( $style['gradient_position'] ?? 'center' ) );

		if ( 'radial' === $type ) {
			return 'radial-gradient(circle at ' . $position . ',' . $color_1 . ',' . $color_2 . ')';
		}

		return 'linear-gradient(' . $angle . 'deg,' . $color_1 . ',' . $color_2 . ')';
	}

	public static function background_gradient_css_value( array $style ): string {
		$color_1  = sanitize_hex_color( $style['bg_gradient_color_1'] ?? '#24afab' ) ?: '#24afab';
		$color_2  = sanitize_hex_color( $style['bg_gradient_color_2'] ?? '#6c5ce7' ) ?: '#6c5ce7';
		$type     = self::sanitize_gradient_type( (string) ( $style['bg_gradient_type'] ?? 'linear' ) );
		$angle    = max( 0, min( 360, absint( $style['bg_gradient_angle'] ?? 90 ) ) );
		$position = self::sanitize_gradient_position( (string) ( $style['bg_gradient_position'] ?? 'center' ) );

		if ( 'radial' === $type ) {
			return 'radial-gradient(circle at ' . $position . ',' . $color_1 . ',' . $color_2 . ')';
		}

		return 'linear-gradient(' . $angle . 'deg,' . $color_1 . ',' . $color_2 . ')';
	}


	public static function style_to_css( array $style, bool $google_fonts_enabled = false ): string {
		$style = array_replace( self::style_defaults(), $style );
		$css   = '';

		$font_family = self::normalize_font_family( (string) $style['font_family'], $google_fonts_enabled );

		if ( '' !== $font_family && 'inherit' !== $font_family ) {
			$css .= 'font-family:' . $font_family . ';';
		}

		if ( '' !== trim( (string) $style['font_size'] ) ) {
			$css .= 'font-size:' . self::css_value( $style['font_size'], $style['font_size_unit'], 0 ) . ';';
		}

		if ( '' !== trim( (string) $style['font_weight'] ) ) {
			$font_weight = absint( $style['font_weight'] );

			if ( $font_weight > 0 ) {
				$css .= 'font-weight:' . $font_weight . ';';
			}
		}

		if ( '' !== trim( (string) $style['line_height'] ) ) {
			$line_height = (float) str_replace( ',', '.', (string) $style['line_height'] );

			if ( $line_height > 0 ) {
				$css .= 'line-height:' . $line_height . ';';
			}
		}

		if ( 'normal' !== self::sanitize_font_style( (string) $style['font_style'] ) ) {
			$css .= 'font-style:' . self::sanitize_font_style( (string) $style['font_style'] ) . ';';
		}

		if ( 'none' !== self::sanitize_text_decoration( (string) $style['text_decoration'] ) ) {
			$css .= 'text-decoration:' . self::sanitize_text_decoration( (string) $style['text_decoration'] ) . ';';
		}

		if ( 'none' !== self::sanitize_text_transform( (string) $style['text_transform'] ) ) {
			$css .= 'text-transform:' . self::sanitize_text_transform( (string) $style['text_transform'] ) . ';';
		}

		if ( '' !== trim( (string) $style['letter_spacing'] ) ) {
			$css .= 'letter-spacing:' . self::css_value( $style['letter_spacing'], $style['letter_spacing_unit'], 0 ) . ';';
		}

		if ( '' !== trim( (string) $style['word_spacing'] ) ) {
			$css .= 'word-spacing:' . self::css_value( $style['word_spacing'], $style['word_spacing_unit'], 0 ) . ';';
		}

		$white_space = self::sanitize_white_space( (string) $style['white_space'] );
		if ( '' !== $white_space ) {
			$css .= 'white-space:' . $white_space . ';';
		}

		$color = sanitize_hex_color( $style['color'] );
		if ( $color ) {
			$css .= 'color:' . $color . ';';
		}

		$background_color = sanitize_hex_color( $style['background_color'] );
		if ( $background_color ) {
			$css .= 'background-color:' . $background_color . ';';
		}

		if ( ! empty( $style['bg_gradient_enabled'] ) ) {
			$css .= 'background-image:' . self::background_gradient_css_value( $style ) . ';';
		}

		/*
		 * Text gradients and text shadows are rendered on the inner
		 * .wpr-replaced-text layer by WPR_Settings::text_layer_css().
		 * Keeping them off the outer wrapper prevents text gradients from
		 * overriding box/background gradients.
		 */

		$border_style = self::sanitize_border_style( (string) $style['border_style'] );
		if ( 'none' !== $border_style ) {
			$border_color = sanitize_hex_color( $style['border_color'] ) ?: '#24afab';
			$border_unit  = self::sanitize_unit( (string) ( $style['border_width_unit'] ?? 'px' ) );

			$border_sides = array(
				'top'    => (string) ( $style['border_width_t'] ?? '' ),
				'right'  => (string) ( $style['border_width_r'] ?? '' ),
				'bottom' => (string) ( $style['border_width_b'] ?? '' ),
				'left'   => (string) ( $style['border_width_l'] ?? '' ),
			);

			$has_side_border = array_filter(
				$border_sides,
				static fn( $value ) => '' !== trim( $value ) && 0.0 !== (float) str_replace( ',', '.', $value )
			);

			if ( ! empty( $has_side_border ) ) {
				foreach ( $border_sides as $side => $side_width ) {
					if ( '' !== trim( $side_width ) && 0.0 !== (float) str_replace( ',', '.', $side_width ) ) {
						$css .= 'border-' . $side . ':' . self::css_value( $side_width, $border_unit, 0 ) . ' ' . $border_style . ' ' . $border_color . ';';
					}
				}
			} else {
				$border_width = (float) str_replace( ',', '.', (string) $style['border_width'] );

				if ( $border_width > 0 ) {
					$css .= 'border:' . self::css_value( $border_width, $border_unit, 0 ) . ' ' . $border_style . ' ' . $border_color . ';';
				}
			}
		}

		$radius_values = array(
			(string) ( $style['border_radius_t'] ?? '' ),
			(string) ( $style['border_radius_r'] ?? '' ),
			(string) ( $style['border_radius_b'] ?? '' ),
			(string) ( $style['border_radius_l'] ?? '' ),
		);

		if ( array_filter( $radius_values, static fn( $value ) => '' !== trim( $value ) && 0.0 !== (float) str_replace( ',', '.', $value ) ) ) {
			$css .= 'border-radius:'
				. self::css_value( $style['border_radius_t'], $style['border_radius_unit'], 0 ) . ' '
				. self::css_value( $style['border_radius_r'], $style['border_radius_unit'], 0 ) . ' '
				. self::css_value( $style['border_radius_b'], $style['border_radius_unit'], 0 ) . ' '
				. self::css_value( $style['border_radius_l'], $style['border_radius_unit'], 0 ) . ';';
		}

		$padding_values = array(
			(string) ( $style['padding_t'] ?? '' ),
			(string) ( $style['padding_r'] ?? '' ),
			(string) ( $style['padding_b'] ?? '' ),
			(string) ( $style['padding_l'] ?? '' ),
		);

		if ( array_filter( $padding_values, static fn( $value ) => '' !== trim( $value ) && 0.0 !== (float) str_replace( ',', '.', $value ) ) ) {
			$css .= 'padding:'
				. self::css_value( $style['padding_t'], $style['padding_unit'], 0 ) . ' '
				. self::css_value( $style['padding_r'], $style['padding_unit'], 0 ) . ' '
				. self::css_value( $style['padding_b'], $style['padding_unit'], 0 ) . ' '
				. self::css_value( $style['padding_l'], $style['padding_unit'], 0 ) . ';';
		}

		$effect       = self::sanitize_effect( (string) $style['text_effect'] );
		$effect_color = sanitize_hex_color( $style['effect_color'] ?? '#24afab' ) ?: '#24afab';
		$effect_color_2 = sanitize_hex_color( $style['effect_color_2'] ?? '#6c5ce7' ) ?: '#6c5ce7';
		$effect_strength = max( 0, (float) str_replace( ',', '.', (string) ( $style['effect_strength'] ?? '12' ) ) );
		$effect_blur = max( 0, (float) str_replace( ',', '.', (string) ( $style['effect_blur'] ?? '10' ) ) );

		if ( 'none' !== $effect ) {
			$css .= '--wpr-effect-color:' . $effect_color . ';';
			$css .= '--wpr-effect-color-2:' . $effect_color_2 . ';';
			$css .= '--wpr-effect-strength:' . rtrim( rtrim( number_format( $effect_strength, 2, '.', '' ), '0' ), '.' ) . 'px;';
			$css .= '--wpr-effect-blur:' . rtrim( rtrim( number_format( $effect_blur, 2, '.', '' ), '0' ), '.' ) . 'px;';

			$duration  = max( 0, absint( $style['animation_duration'] ) );
			$delay     = max( 0, absint( $style['animation_delay'] ) );
			$loopable_effects = array(
				'pulse',
				'neon-glow',
				'text-shadow-glow',
				'gradient-animation',
				'shimmer-text',
				'wave-text',
				'glitch',
			);

			$loop_enabled = ! empty( $style['animation_loop'] ) && in_array( $effect, $loopable_effects, true );
			$iteration    = $loop_enabled ? 'infinite' : '1';
			$timing       = self::sanitize_animation_timing( (string) ( $style['animation_timing'] ?? 'ease-in-out' ) );
			
			$transform_effects = array(
				'pulse',
				'bounce',
				'shake',
				'wave-text',
				'glitch',
				'rotate',
				'zoom-in',
				'zoom-out',
			);

			if ( in_array( $effect, $transform_effects, true ) ) {
				$css .= 'display:inline-block;';
				$css .= 'transform-origin:center center;';
			}
			$css .= 'animation-name:wpr-' . $effect . ';';
			$css .= 'animation-duration:' . $duration . 'ms;';
			$css .= 'animation-timing-function:' . $timing . ';';
			$css .= 'animation-fill-mode:both;';
			$css .= 'animation-iteration-count:' . $iteration . ';';
			$css .= 'animation-delay:' . $delay . 'ms;';
		}

		return $css;
	}

	public static function google_font_url( array $fonts ): string {
		$allowed  = self::allowed_google_fonts();
		$fonts    = array_values( array_unique( array_filter( $fonts ) ) );
		$families = array();

		foreach ( $fonts as $font ) {
			if ( in_array( $font, $allowed, true ) ) {
				$families[] = 'family=' . rawurlencode( str_replace( ' ', '+', $font ) ) . ':wght@300;400;500;600;700;800';
			}
		}

		return empty( $families ) ? '' : 'https://fonts.googleapis.com/css2?' . implode( '&', $families ) . '&display=swap';
	}
}
