<?php
/**
 * Activation and upgrade.
 *
 * @package WordPairReplacer
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class WPR_Activator {

	public static function activate(): void {
		self::create_or_update_table();
		self::create_default_settings();
		self::migrate_legacy_rows();
		self::seed_default_presets();
		self::purge_unconnected_security_provider_settings();

		update_option( 'wpr_version', WPR_VERSION, false );
	}

	public static function maybe_upgrade(): void {
		$installed = get_option( 'wpr_version', '' );

		if ( WPR_VERSION === $installed ) {
			return;
		}

		self::create_or_update_table();
		self::create_default_settings();
		self::seed_default_presets();
		self::migrate_legacy_rows();
		self::purge_unconnected_security_provider_settings();
		update_option( 'wpr_version', WPR_VERSION, false );
	}

	/**
	 * The WPVulnerability, Wordfence, WPScan and Patchstack security-scan
	 * providers are "coming soon" placeholders with no outbound request
	 * implemented. Older versions still let admins toggle them on and store a
	 * real API token/key that was never used. Clear those out on upgrade so no
	 * unused secret lingers in the database.
	 */
	private static function purge_unconnected_security_provider_settings(): void {
		if ( ! class_exists( 'WPR_Settings' ) ) {
			return;
		}

		$settings = get_option( WPR_Settings::OPTION_KEY, false );

		if ( ! is_array( $settings ) ) {
			return;
		}

		$stale_keys = array(
			'security_wpvulnerability_enabled',
			'security_wordfence_enabled',
			'security_wpscan_enabled',
			'security_patchstack_enabled',
			'security_wpscan_api_token',
			'security_patchstack_api_key',
		);

		$changed = false;

		foreach ( $stale_keys as $key ) {
			$empty_value = str_ends_with( $key, '_enabled' ) ? 0 : '';

			if ( array_key_exists( $key, $settings ) && $settings[ $key ] !== $empty_value ) {
				$settings[ $key ] = $empty_value;
				$changed          = true;
			}
		}

		if ( $changed ) {
			update_option( WPR_Settings::OPTION_KEY, $settings, false );
		}
	}

	public static function table_name(): string {
		global $wpdb;

		return $wpdb->prefix . 'wordpair_replacer';
	}

	public static function presets_table_name(): string {
		global $wpdb;

		return $wpdb->prefix . 'wordpair_replacer_presets';
	}

	private static function create_or_update_table(): void {
		global $wpdb;

		$table_name      = self::table_name();
		$charset_collate = $wpdb->get_charset_collate();

		require_once ABSPATH . 'wp-admin/includes/upgrade.php';

		$sql = "CREATE TABLE {$table_name} (
			id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
			original_word VARCHAR(255) NOT NULL DEFAULT '',
			replacement_word VARCHAR(255) NOT NULL DEFAULT '',
			is_active TINYINT(1) NOT NULL DEFAULT 1,
			case_sensitive TINYINT(1) NOT NULL DEFAULT 0,
			whole_word TINYINT(1) NOT NULL DEFAULT 1,
			use_custom_style TINYINT(1) NOT NULL DEFAULT 0,
			font_family VARCHAR(255) NOT NULL DEFAULT '',
			font_size VARCHAR(20) NOT NULL DEFAULT '',
			font_size_unit VARCHAR(10) NOT NULL DEFAULT 'px',
			font_weight VARCHAR(20) NOT NULL DEFAULT '',
			line_height VARCHAR(20) NOT NULL DEFAULT '',
			font_style VARCHAR(20) NOT NULL DEFAULT 'normal',
			text_decoration VARCHAR(30) NOT NULL DEFAULT 'none',
			text_transform VARCHAR(30) NOT NULL DEFAULT 'none',
			letter_spacing VARCHAR(20) NOT NULL DEFAULT '',
			letter_spacing_unit VARCHAR(10) NOT NULL DEFAULT 'px',
			word_spacing VARCHAR(20) NOT NULL DEFAULT '',
			word_spacing_unit VARCHAR(10) NOT NULL DEFAULT 'px',
			white_space VARCHAR(20) NOT NULL DEFAULT '',
			color VARCHAR(20) NOT NULL DEFAULT '',
			background_color VARCHAR(20) NOT NULL DEFAULT '',
			bg_gradient_enabled TINYINT(1) NOT NULL DEFAULT 0,
			bg_gradient_color_1 VARCHAR(20) NOT NULL DEFAULT '#24afab',
			bg_gradient_color_2 VARCHAR(20) NOT NULL DEFAULT '#6c5ce7',
			bg_gradient_type VARCHAR(20) NOT NULL DEFAULT 'linear',
			bg_gradient_angle VARCHAR(20) NOT NULL DEFAULT '90',
			bg_gradient_position VARCHAR(20) NOT NULL DEFAULT 'center',
			gradient_enabled TINYINT(1) NOT NULL DEFAULT 0,
			gradient_color_1 VARCHAR(20) NOT NULL DEFAULT '#24afab',
			gradient_color_2 VARCHAR(20) NOT NULL DEFAULT '#6c5ce7',
			gradient_type VARCHAR(20) NOT NULL DEFAULT 'linear',
			gradient_angle VARCHAR(20) NOT NULL DEFAULT '90',
			gradient_position VARCHAR(20) NOT NULL DEFAULT 'center',
			text_shadow_enabled TINYINT(1) NOT NULL DEFAULT 0,
			text_shadow_color VARCHAR(20) NOT NULL DEFAULT '#000000',
			text_shadow_x VARCHAR(20) NOT NULL DEFAULT '0',
			text_shadow_y VARCHAR(20) NOT NULL DEFAULT '2',
			text_shadow_blur VARCHAR(20) NOT NULL DEFAULT '8',
			border_width VARCHAR(20) NOT NULL DEFAULT '',
			border_width_unit VARCHAR(10) NOT NULL DEFAULT 'px',
			border_width_t VARCHAR(20) NOT NULL DEFAULT '',
			border_width_r VARCHAR(20) NOT NULL DEFAULT '',
			border_width_b VARCHAR(20) NOT NULL DEFAULT '',
			border_width_l VARCHAR(20) NOT NULL DEFAULT '',
			border_style VARCHAR(20) NOT NULL DEFAULT 'solid',
			border_color VARCHAR(20) NOT NULL DEFAULT '',
			border_radius_t VARCHAR(20) NOT NULL DEFAULT '',
			border_radius_r VARCHAR(20) NOT NULL DEFAULT '',
			border_radius_b VARCHAR(20) NOT NULL DEFAULT '',
			border_radius_l VARCHAR(20) NOT NULL DEFAULT '',
			border_radius_unit VARCHAR(10) NOT NULL DEFAULT 'px',
			padding_t VARCHAR(20) NOT NULL DEFAULT '',
			padding_r VARCHAR(20) NOT NULL DEFAULT '',
			padding_b VARCHAR(20) NOT NULL DEFAULT '',
			padding_l VARCHAR(20) NOT NULL DEFAULT '',
			padding_unit VARCHAR(10) NOT NULL DEFAULT 'px',
			text_effect VARCHAR(50) NOT NULL DEFAULT 'none',
			animation_duration VARCHAR(20) NOT NULL DEFAULT '600',
			animation_delay VARCHAR(20) NOT NULL DEFAULT '0',
			animation_loop TINYINT(1) NOT NULL DEFAULT 0,
			animation_timing VARCHAR(30) NOT NULL DEFAULT 'ease-in-out',
			effect_color VARCHAR(20) NOT NULL DEFAULT '#24afab',
			effect_color_2 VARCHAR(20) NOT NULL DEFAULT '#6c5ce7',
			effect_strength VARCHAR(20) NOT NULL DEFAULT '12',
			effect_blur VARCHAR(20) NOT NULL DEFAULT '10',
			custom_class VARCHAR(120) NOT NULL DEFAULT '',
			custom_id VARCHAR(120) NOT NULL DEFAULT '',
			pair_custom_css TEXT NULL,
			link_enabled TINYINT(1) NOT NULL DEFAULT 0,
			link_url TEXT NULL,
			link_target VARCHAR(20) NOT NULL DEFAULT '_self',
			link_rel_nofollow TINYINT(1) NOT NULL DEFAULT 0,
			link_rel_sponsored TINYINT(1) NOT NULL DEFAULT 0,
			link_rel_noopener TINYINT(1) NOT NULL DEFAULT 1,
			link_title VARCHAR(255) NOT NULL DEFAULT '',
			link_aria_label VARCHAR(255) NOT NULL DEFAULT '',
			link_class VARCHAR(120) NOT NULL DEFAULT '',
			link_id VARCHAR(120) NOT NULL DEFAULT '',
			link_color VARCHAR(20) NOT NULL DEFAULT '',
			link_hover_color VARCHAR(20) NOT NULL DEFAULT '',
			link_underline_hover TINYINT(1) NOT NULL DEFAULT 1,
			created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
			updated_at DATETIME NULL DEFAULT NULL,
			PRIMARY KEY  (id),
			KEY original_word (original_word),
			KEY is_active (is_active)
		) {$charset_collate};";

		dbDelta( $sql );

		$presets_table = self::presets_table_name();
		$presets_sql   = "CREATE TABLE {$presets_table} (
			id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
			slug VARCHAR(160) NOT NULL DEFAULT '',
			name VARCHAR(190) NOT NULL DEFAULT '',
			description TEXT NULL,
			preview_keyword VARCHAR(120) NOT NULL DEFAULT 'WordPress',
			preset_json LONGTEXT NOT NULL,
			readonly TINYINT(1) NOT NULL DEFAULT 0,
			created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
			updated_at DATETIME NULL DEFAULT NULL,
			PRIMARY KEY (id),
			UNIQUE KEY slug (slug),
			KEY readonly (readonly)
		) {$charset_collate};";

		dbDelta( $presets_sql );
	}

	private static function migrate_legacy_rows(): void {
		global $wpdb;

		$table = self::table_name();

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- Activation-only schema migration for a plugin-owned custom table. Table name is generated internally via $wpdb->prefix and escaped with esc_sql().
		$columns = $wpdb->get_results( "SHOW COLUMNS FROM {$table}", ARRAY_A );

		if ( ! is_array( $columns ) ) {
			return;
		}

		$column_names = wp_list_pluck( $columns, 'Field' );

		$new_columns = array(
			'font_style'           => "ALTER TABLE {$table} ADD font_style VARCHAR(20) NOT NULL DEFAULT 'normal' AFTER line_height",
			'text_decoration'      => "ALTER TABLE {$table} ADD text_decoration VARCHAR(30) NOT NULL DEFAULT 'none' AFTER font_style",
			'text_transform'       => "ALTER TABLE {$table} ADD text_transform VARCHAR(30) NOT NULL DEFAULT 'none' AFTER text_decoration",
			'letter_spacing'       => "ALTER TABLE {$table} ADD letter_spacing VARCHAR(20) NOT NULL DEFAULT '' AFTER text_transform",
			'letter_spacing_unit'  => "ALTER TABLE {$table} ADD letter_spacing_unit VARCHAR(10) NOT NULL DEFAULT 'px' AFTER letter_spacing",
			'word_spacing'         => "ALTER TABLE {$table} ADD word_spacing VARCHAR(20) NOT NULL DEFAULT '' AFTER letter_spacing_unit",
			'word_spacing_unit'    => "ALTER TABLE {$table} ADD word_spacing_unit VARCHAR(10) NOT NULL DEFAULT 'px' AFTER word_spacing",
			'white_space'          => "ALTER TABLE {$table} ADD white_space VARCHAR(20) NOT NULL DEFAULT '' AFTER word_spacing_unit",
			'bg_gradient_enabled'  => "ALTER TABLE {$table} ADD bg_gradient_enabled TINYINT(1) NOT NULL DEFAULT 0 AFTER background_color",
			'bg_gradient_color_1'  => "ALTER TABLE {$table} ADD bg_gradient_color_1 VARCHAR(20) NOT NULL DEFAULT '#24afab' AFTER bg_gradient_enabled",
			'bg_gradient_color_2'  => "ALTER TABLE {$table} ADD bg_gradient_color_2 VARCHAR(20) NOT NULL DEFAULT '#6c5ce7' AFTER bg_gradient_color_1",
			'bg_gradient_type'     => "ALTER TABLE {$table} ADD bg_gradient_type VARCHAR(20) NOT NULL DEFAULT 'linear' AFTER bg_gradient_color_2",
			'bg_gradient_angle'    => "ALTER TABLE {$table} ADD bg_gradient_angle VARCHAR(20) NOT NULL DEFAULT '90' AFTER bg_gradient_type",
			'bg_gradient_position' => "ALTER TABLE {$table} ADD bg_gradient_position VARCHAR(20) NOT NULL DEFAULT 'center' AFTER bg_gradient_angle",
			'border_width_t'       => "ALTER TABLE {$table} ADD border_width_t VARCHAR(20) NOT NULL DEFAULT '' AFTER border_width_unit",
			'border_width_r'       => "ALTER TABLE {$table} ADD border_width_r VARCHAR(20) NOT NULL DEFAULT '' AFTER border_width_t",
			'border_width_b'       => "ALTER TABLE {$table} ADD border_width_b VARCHAR(20) NOT NULL DEFAULT '' AFTER border_width_r",
			'border_width_l'       => "ALTER TABLE {$table} ADD border_width_l VARCHAR(20) NOT NULL DEFAULT '' AFTER border_width_b",
			'animation_loop'        => "ALTER TABLE {$table} ADD animation_loop TINYINT(1) NOT NULL DEFAULT 0 AFTER animation_delay",
			'animation_timing'      => "ALTER TABLE {$table} ADD animation_timing VARCHAR(30) NOT NULL DEFAULT 'ease-in-out' AFTER animation_loop",
			'custom_class'         => "ALTER TABLE {$table} ADD custom_class VARCHAR(120) NOT NULL DEFAULT '' AFTER effect_blur",
			'custom_id'            => "ALTER TABLE {$table} ADD custom_id VARCHAR(120) NOT NULL DEFAULT '' AFTER custom_class",
			'pair_custom_css'      => "ALTER TABLE {$table} ADD pair_custom_css TEXT NULL AFTER custom_id",
			'link_enabled'         => "ALTER TABLE {$table} ADD link_enabled TINYINT(1) NOT NULL DEFAULT 0 AFTER custom_id",
			'link_url'             => "ALTER TABLE {$table} ADD link_url TEXT NULL AFTER link_enabled",
			'link_target'          => "ALTER TABLE {$table} ADD link_target VARCHAR(20) NOT NULL DEFAULT '_self' AFTER link_url",
			'link_rel_nofollow'    => "ALTER TABLE {$table} ADD link_rel_nofollow TINYINT(1) NOT NULL DEFAULT 0 AFTER link_target",
			'link_rel_sponsored'   => "ALTER TABLE {$table} ADD link_rel_sponsored TINYINT(1) NOT NULL DEFAULT 0 AFTER link_rel_nofollow",
			'link_rel_noopener'    => "ALTER TABLE {$table} ADD link_rel_noopener TINYINT(1) NOT NULL DEFAULT 1 AFTER link_rel_sponsored",
			'link_title'           => "ALTER TABLE {$table} ADD link_title VARCHAR(255) NOT NULL DEFAULT '' AFTER link_rel_noopener",
			'link_aria_label'      => "ALTER TABLE {$table} ADD link_aria_label VARCHAR(255) NOT NULL DEFAULT '' AFTER link_title",
			'link_class'           => "ALTER TABLE {$table} ADD link_class VARCHAR(120) NOT NULL DEFAULT '' AFTER link_aria_label",
			'link_id'              => "ALTER TABLE {$table} ADD link_id VARCHAR(120) NOT NULL DEFAULT '' AFTER link_class",
			'link_color'           => "ALTER TABLE {$table} ADD link_color VARCHAR(20) NOT NULL DEFAULT '' AFTER link_id",
			'link_hover_color'     => "ALTER TABLE {$table} ADD link_hover_color VARCHAR(20) NOT NULL DEFAULT '' AFTER link_color",
			'link_underline_hover' => "ALTER TABLE {$table} ADD link_underline_hover TINYINT(1) NOT NULL DEFAULT 1 AFTER link_hover_color",
		);

		foreach ( $new_columns as $column_name => $alter_sql ) {
			if ( ! in_array( $column_name, $column_names, true ) ) {
				// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.NotPrepared
				$wpdb->query( $alter_sql );
				$column_names[] = $column_name;
			}
		}


		if ( in_array( 'original_en', $column_names, true ) && in_array( 'replacement_en', $column_names, true ) ) {
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
			$wpdb->query(
				// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
				"UPDATE {$table}
				 SET original_word = IF(original_word = '' AND original_en != '', original_en, original_word),
					 replacement_word = IF(replacement_word = '' AND replacement_en != '', replacement_en, replacement_word)
				 WHERE original_en != '' OR replacement_en != ''"
			);
		}

		if ( in_array( 'original_de', $column_names, true ) && in_array( 'replacement_de', $column_names, true ) ) {
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
			$wpdb->query(
				// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
				"UPDATE {$table}
				 SET original_word = IF(original_word = '' AND original_de != '', original_de, original_word),
					 replacement_word = IF(replacement_word = '' AND replacement_de != '', replacement_de, replacement_word)
				 WHERE original_de != '' OR replacement_de != ''"
			);
		}

		if ( in_array( 'border_width', $column_names, true ) && in_array( 'border_width_t', $column_names, true ) ) {
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
			$wpdb->query(
				// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
				"UPDATE {$table}
				 SET border_width_t = IF(border_width_t = '' AND border_width != '', border_width, border_width_t),
				     border_width_r = IF(border_width_r = '' AND border_width != '', border_width, border_width_r),
				     border_width_b = IF(border_width_b = '' AND border_width != '', border_width, border_width_b),
				     border_width_l = IF(border_width_l = '' AND border_width != '', border_width, border_width_l)
				 WHERE border_width != ''"
			);
		}

		if ( in_array( 'padding_y', $column_names, true ) && in_array( 'padding_t', $column_names, true ) ) {
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
			$wpdb->query(
				// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
				"UPDATE {$table}
				 SET padding_t = IF(padding_t = '', padding_y, padding_t),
					 padding_b = IF(padding_b = '', padding_y, padding_b),
					 padding_r = IF(padding_r = '', padding_x, padding_r),
					 padding_l = IF(padding_l = '', padding_x, padding_l)
				 WHERE padding_y != '' OR padding_x != ''"
			);
		}

		if ( in_array( 'border_radius', $column_names, true ) && in_array( 'border_radius_t', $column_names, true ) ) {
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
			$wpdb->query(
				// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
				"UPDATE {$table}
				 SET border_radius_t = IF(border_radius_t = '', border_radius, border_radius_t),
				     border_radius_r = IF(border_radius_r = '', border_radius, border_radius_r),
				     border_radius_b = IF(border_radius_b = '', border_radius, border_radius_b),
				     border_radius_l = IF(border_radius_l = '', border_radius, border_radius_l)
				 WHERE border_radius != ''"
			);
		}
	}


	private static function seed_default_presets(): void {
		global $wpdb;

		$table = self::presets_table_name();
		$defaults = class_exists( 'WPR_Settings' ) ? WPR_Settings::style_defaults() : array();
		$presets = self::default_presets( $defaults );

		foreach ( $presets as $preset ) {
			$slug = sanitize_title( $preset['slug'] ?? $preset['name'] );
			if ( '' === $slug ) {
				continue;
			}

			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
			$existing = $wpdb->get_var(
				$wpdb->prepare(
					// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
					"SELECT id FROM {$table} WHERE slug = %s LIMIT 1",
					$slug
				)
			);

			$data = array(
				'slug'            => $slug,
				'name'            => sanitize_text_field( $preset['name'] ),
				'description'     => sanitize_textarea_field( $preset['description'] ?? '' ),
				'preview_keyword' => sanitize_text_field( $preset['preview_keyword'] ?? 'WordPress' ),
				'preset_json'     => wp_json_encode( array( 'style' => $preset['style'] ), JSON_UNESCAPED_SLASHES ),
				'readonly'        => 1,
				'updated_at'      => current_time( 'mysql' ),
			);

			if ( $existing ) {
				// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
				$wpdb->update( $table, $data, array( 'id' => absint( $existing ) ) );
			} else {
				$data['created_at'] = current_time( 'mysql' );
				// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
				$wpdb->insert( $table, $data );
			}
		}
	}

	private static function preset_style( array $defaults, array $overrides ): array {
		return array_replace( $defaults, $overrides );
	}

	private static function default_presets( array $defaults ): array {
		return array(
			array(
				'slug' => 'seo-highlight',
				'name' => 'SEO Highlight',
				'description' => 'Readable marker style for important internal SEO keywords.',
				'preview_keyword' => 'SEO',
				'style' => self::preset_style( $defaults, array( 'font_weight' => '800', 'color' => '#111827', 'background_color' => '#fde68a', 'padding_t' => '2', 'padding_r' => '8', 'padding_b' => '2', 'padding_l' => '8', 'border_radius_t' => '6', 'border_radius_r' => '6', 'border_radius_b' => '6', 'border_radius_l' => '6' ) ),
			),
			array(
				'slug' => 'minimal-link',
				'name' => 'Minimal Link',
				'description' => 'Clean internal-link preset with subtle underline behavior.',
				'preview_keyword' => 'Internal Link',
				'style' => self::preset_style( $defaults, array( 'font_weight' => '600', 'color' => '#2563eb', 'text_decoration' => 'underline', 'link_enabled' => '1', 'link_target' => '_self', 'link_color' => '#2563eb', 'link_hover_color' => '#1d4ed8', 'link_underline_hover' => '1' ) ),
			),
			array(
				'slug' => 'keyword-accent',
				'name' => 'Keyword Accent',
				'description' => 'Modern accent color for high-value terms.',
				'preview_keyword' => 'Keyword',
				'style' => self::preset_style( $defaults, array( 'font_weight' => '800', 'color' => '#24afab', 'letter_spacing' => '.2', 'letter_spacing_unit' => 'px' ) ),
			),
			array(
				'slug' => 'neon-glow',
				'name' => 'Neon Glow',
				'description' => 'Cyan glow with soft continuous light for modern hero sections.',
				'preview_keyword' => 'Neon',
				'style' => self::preset_style( $defaults, array( 'font_weight' => '900', 'color' => '#24f0ff', 'text_effect' => 'neon-glow', 'animation_duration' => '1800', 'animation_loop' => '1', 'effect_color' => '#24f0ff', 'effect_strength' => '24', 'effect_blur' => '18' ) ),
			),
			array(
				'slug' => 'cyberpunk',
				'name' => 'Cyberpunk',
				'description' => 'High-energy magenta/cyan gradient with glitch effect.',
				'preview_keyword' => 'Cyber',
				'style' => self::preset_style( $defaults, array( 'font_weight' => '900', 'text_transform' => 'uppercase', 'gradient_enabled' => '1', 'gradient_color_1' => '#ff2bd6', 'gradient_color_2' => '#00f5ff', 'text_effect' => 'glitch', 'animation_duration' => '1200', 'animation_loop' => '1', 'effect_color' => '#ff2bd6', 'effect_color_2' => '#00f5ff' ) ),
			),
			array(
				'slug' => 'glass-accent',
				'name' => 'Glass Accent',
				'description' => 'Transparent badge with border and soft glass feel.',
				'preview_keyword' => 'Glass',
				'style' => self::preset_style( $defaults, array( 'font_weight' => '700', 'color' => '#0f766e', 'background_color' => '#ecfeff', 'border_width_t' => '1', 'border_width_r' => '1', 'border_width_b' => '1', 'border_width_l' => '1', 'border_color' => '#67e8f9', 'padding_t' => '3', 'padding_r' => '10', 'padding_b' => '3', 'padding_l' => '10', 'border_radius_t' => '12', 'border_radius_r' => '12', 'border_radius_b' => '12', 'border_radius_l' => '12' ) ),
			),
			array(
				'slug' => 'premium-gold',
				'name' => 'Premium Gold',
				'description' => 'Elegant serif typography with a premium gold gradient.',
				'preview_keyword' => 'Premium',
				'style' => self::preset_style( $defaults, array( 'font_family' => 'Georgia, serif', 'font_weight' => '700', 'gradient_enabled' => '1', 'gradient_color_1' => '#f59e0b', 'gradient_color_2' => '#fde68a', 'text_shadow_enabled' => '1', 'text_shadow_color' => '#92400e', 'text_shadow_x' => '0', 'text_shadow_y' => '2', 'text_shadow_blur' => '8' ) ),
			),
			array(
				'slug' => 'dark-luxury',
				'name' => 'Dark Luxury',
				'description' => 'Dark pill badge with refined gold border.',
				'preview_keyword' => 'Luxury',
				'style' => self::preset_style( $defaults, array( 'font_weight' => '800', 'color' => '#f5d36b', 'background_color' => '#111827', 'border_width_t' => '1', 'border_width_r' => '1', 'border_width_b' => '1', 'border_width_l' => '1', 'border_color' => '#b45309', 'padding_t' => '4', 'padding_r' => '12', 'padding_b' => '4', 'padding_l' => '12', 'border_radius_t' => '999', 'border_radius_r' => '999', 'border_radius_b' => '999', 'border_radius_l' => '999' ) ),
			),
			array(
				'slug' => 'soft-gradient',
				'name' => 'Soft Gradient',
				'description' => 'Smooth pastel gradient for friendly highlights.',
				'preview_keyword' => 'Gradient',
				'style' => self::preset_style( $defaults, array( 'font_weight' => '800', 'gradient_enabled' => '1', 'gradient_color_1' => '#8b5cf6', 'gradient_color_2' => '#14b8a6' ) ),
			),
			array(
				'slug' => 'warning-pulse',
				'name' => 'Warning Pulse',
				'description' => 'Attention preset with warm colors and subtle pulse.',
				'preview_keyword' => 'Warning',
				'style' => self::preset_style( $defaults, array( 'font_weight' => '900', 'color' => '#991b1b', 'background_color' => '#fee2e2', 'text_effect' => 'pulse', 'animation_duration' => '1600', 'animation_loop' => '1', 'padding_t' => '3', 'padding_r' => '10', 'padding_b' => '3', 'padding_l' => '10', 'border_radius_t' => '8', 'border_radius_r' => '8', 'border_radius_b' => '8', 'border_radius_l' => '8' ) ),
			),
			array(
				'slug' => 'info-badge',
				'name' => 'Info Badge',
				'description' => 'Readable blue badge for notes, tips and product keywords.',
				'preview_keyword' => 'Info',
				'style' => self::preset_style( $defaults, array( 'font_weight' => '700', 'color' => '#1e3a8a', 'background_color' => '#dbeafe', 'border_width_b' => '2', 'border_color' => '#60a5fa', 'padding_t' => '2', 'padding_r' => '9', 'padding_b' => '2', 'padding_l' => '9', 'border_radius_t' => '7', 'border_radius_r' => '7', 'border_radius_b' => '7', 'border_radius_l' => '7' ) ),
			),
			array(
				'slug' => 'success-glow',
				'name' => 'Success Glow',
				'description' => 'Green success highlight with subtle glow for positive signals.',
				'preview_keyword' => 'Success',
				'style' => self::preset_style( $defaults, array( 'font_weight' => '800', 'color' => '#166534', 'background_color' => '#dcfce7', 'text_shadow_enabled' => '1', 'text_shadow_color' => '#86efac', 'text_shadow_x' => '0', 'text_shadow_y' => '0', 'text_shadow_blur' => '10', 'padding_t' => '2', 'padding_r' => '9', 'padding_b' => '2', 'padding_l' => '9', 'border_radius_t' => '7', 'border_radius_r' => '7', 'border_radius_b' => '7', 'border_radius_l' => '7' ) ),
			),
		);
	}

	private static function create_default_settings(): void {
		if ( false === get_option( WPR_Settings::OPTION_KEY, false ) ) {
			add_option( WPR_Settings::OPTION_KEY, WPR_Settings::defaults(), '', false );
		}
	}
}