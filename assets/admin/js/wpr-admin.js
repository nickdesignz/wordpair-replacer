/* global jQuery, wprAdmin */
(function ($) {
	'use strict';

	const styleFields = [
		'font_family','font_size','font_size_unit','font_weight','line_height','font_style','text_decoration','text_transform','letter_spacing','letter_spacing_unit','word_spacing','word_spacing_unit','white_space','color','background_color','bg_gradient_enabled','bg_gradient_color_1','bg_gradient_color_2','bg_gradient_type','bg_gradient_angle','bg_gradient_position','gradient_enabled','gradient_color_1','gradient_color_2','gradient_type','gradient_angle','gradient_position','text_shadow_enabled','text_shadow_color','text_shadow_x','text_shadow_y','text_shadow_blur',
		'border_width','border_width_unit','border_width_t','border_width_r','border_width_b','border_width_l','border_style','border_color',
		'border_radius_t','border_radius_r','border_radius_b','border_radius_l','border_radius_unit',
		'padding_t','padding_r','padding_b','padding_l','padding_unit',
		'text_effect','animation_duration','animation_delay','animation_loop','animation_timing','effect_color','effect_color_2','effect_strength','effect_blur','custom_class','custom_id','pair_custom_css','link_enabled','link_url','link_target','link_rel_nofollow','link_rel_sponsored','link_rel_noopener','link_title','link_aria_label','link_class','link_id','link_color','link_hover_color','link_underline_hover'
	];

	// Defaults must mirror WPR_Settings::style_defaults() on the PHP side so that
	// pair_has_custom_style_values() does not flag pairs as "custom" just because
	// the JS emits a literal zero where PHP keeps the field empty.
	const defaults = {
		font_family:'inherit', font_size:'', font_size_unit:'px', font_weight:'', line_height:'', font_style:'normal', text_decoration:'none', text_transform:'none', letter_spacing:'', letter_spacing_unit:'px', word_spacing:'', word_spacing_unit:'px', white_space:'',
		color:'', background_color:'', bg_gradient_enabled:'0', bg_gradient_color_1:'#24afab', bg_gradient_color_2:'#6c5ce7', bg_gradient_type:'linear', bg_gradient_angle:'90', bg_gradient_position:'center', gradient_enabled:'0', gradient_color_1:'#24afab', gradient_color_2:'#6c5ce7', gradient_type:'linear', gradient_angle:'90', gradient_position:'center', text_shadow_enabled:'0', text_shadow_color:'#000000', text_shadow_x:'', text_shadow_y:'', text_shadow_blur:'', border_width:'', border_width_unit:'px', border_width_t:'', border_width_r:'', border_width_b:'', border_width_l:'', border_style:'solid',
		border_color:'#24afab', border_radius_t:'', border_radius_r:'', border_radius_b:'', border_radius_l:'',
		border_radius_unit:'px', padding_t:'', padding_r:'', padding_b:'', padding_l:'', padding_unit:'px',
		text_effect:'none', animation_duration:'600', animation_delay:'0', animation_loop:'0', animation_timing:'ease-in-out', effect_color:'#24afab', effect_color_2:'#6c5ce7', effect_strength:'12', effect_blur:'10', custom_class:'', custom_id:'', pair_custom_css:'', link_enabled:'0', link_url:'', link_target:'_self', link_rel_nofollow:'0', link_rel_sponsored:'0', link_rel_noopener:'1', link_title:'', link_aria_label:'', link_class:'', link_id:'', link_color:'', link_hover_color:'', link_underline_hover:'1'
	};

	const $form = $('#wpr-form');
	const $settingsForm = $('#wpr-settings-form');
	const $list = $('#wpr-pairs-list');
	const $message = $('#wpr-message');

	let itemsById = {};
	let allPresets = [];
	let activePreview = { type: 'global', id: null, scope: 'global', original: 'Keyword', replacement: 'Replacement' };

	const i18n = Object.assign({
		reset: 'Zurücksetzen',
		saveStyle: 'Style speichern',
		lastChange: 'Letzte Änderung:',
		neverSaved: 'Noch nicht gespeichert',
		selectedPair: 'Ausgewähltes Wortpaar',
		livePreview: 'Live Vorschau',
		frontendPreviewHelp: 'So wird das Wortpaar im Frontend dargestellt.',
		automaticClass: 'Automatische Klasse',
		customClass: 'Eigene Klasse',
		customId: 'Eigene ID',
		applyPreset: 'Apply preset',
		loadPreset: 'Load preset',
		saveAsPreset: 'Save as preset',
		presetName: 'Preset name',
		presetSaved: 'Preset saved.',
		presetApplied: 'Preset applied.',
		noPreset: 'No preset selected.'
	}, wprAdmin.i18n || {});


	const memoryStore = {};

	function safeStorageGet(key, fallback = '') {
		try {
			return window.localStorage.getItem(key) || fallback;
		} catch (e) {
			return memoryStore[key] || fallback;
		}
	}

	function safeStorageSet(key, value) {
		try {
			window.localStorage.setItem(key, value);
		} catch (e) {
			memoryStore[key] = value;
		}
	}

	function getSavedTheme() {
		return safeStorageGet('wprAdminTheme', 'light');
	}

	function applyAdminTheme(theme) {
		const finalTheme = theme === 'dark' ? 'dark' : 'light';
		$('html').removeClass('wpr-admin-light wpr-admin-dark').addClass('wpr-admin-' + finalTheme);
		$('body').removeClass('wpr-admin-light wpr-admin-dark').addClass('wpr-admin-' + finalTheme);
		$('#wpr-admin-theme-toggle').prop('checked', finalTheme === 'dark');
		safeStorageSet('wprAdminTheme', finalTheme);
	}

	function getFavorites() {
		try {
			return JSON.parse(safeStorageGet('wprFavorites', '[]')).map(String);
		} catch (e) {
			return [];
		}
	}

	function saveFavorites(ids) {
		safeStorageSet('wprFavorites', JSON.stringify(ids.map(String)));
	}

	function updateFavoriteButtons() {
		const favorites = getFavorites();
		$('.wpr-favorite').each(function () {
			const id = String($(this).data('id'));
			const isFavorite = favorites.includes(id);
			$(this).toggleClass('is-favorite', isFavorite).text(isFavorite ? '★' : '☆');
		});
	}

	function esc(value) {
		return $('<div>').text(value === null || value === undefined ? '' : String(value)).html();
	}

	function scopeId(scope, field) {
		return '#wpr-' + scope + '-' + field.replaceAll('_', '-');
	}

	function showMessage(text, type = 'success') {
		$message.removeClass('is-success is-error')
			.addClass(type === 'error' ? 'is-error' : 'is-success')
			.text(text)
			.show();

		window.setTimeout(function () {
			$message.fadeOut(150);
		}, 2600);
	}


	function showInlineStatus($target, text, type = 'success') {
		if (!$target || !$target.length) {
			showMessage(text, type);
			return;
		}

		$target.removeClass('is-success is-error is-saving')
			.addClass(type === 'error' ? 'is-error' : (type === 'saving' ? 'is-saving' : 'is-success'))
			.text(text)
			.show();

		window.setTimeout(function () {
			$target.fadeOut(150);
		}, 3200);
	}

	function request(action, data = {}) {
		return $.ajax({
			url: wprAdmin.ajaxUrl,
			type: 'POST',
			dataType: 'json',
			data: Object.assign({ action, nonce: wprAdmin.nonce }, data)
		});
	}

	function updateRangeOutput(input) {
		const $input = $(input);
		const unit = $input.data('unit') || '';
		$('[data-output-for="' + $input.attr('id') + '"]').text($input.val() + unit);
	}

	function normalizeHexColor(value) {
		const raw = String(value || '').trim();

		if (raw === '') {
			return '';
		}

		const hex = raw.charAt(0) === '#' ? raw : '#' + raw;

		if (/^#[0-9a-fA-F]{3}$/.test(hex)) {
			return '#' + hex.charAt(1) + hex.charAt(1) + hex.charAt(2) + hex.charAt(2) + hex.charAt(3) + hex.charAt(3);
		}

		return /^#[0-9a-fA-F]{6}$/.test(hex) ? hex.toLowerCase() : '';
	}

	function readableColor(value) {
		return normalizeHexColor(value) || '#ffffff';
	}

	function updateModernColorUI($field) {
		if (!$field.length) {
			return;
		}

		const value = normalizeHexColor($field.val());
		const fallback = normalizeHexColor($field.data('default-color')) || '#ffffff';
		const displayColor = value || fallback;
		const $row = $field.closest('.wpr-color-row');

		$row.find('.wpr-color-swatch').css('backgroundColor', displayColor);
		$row.find('.wpr-color-hex').val(value);
		$row.find('.wpr-color-native').val(readableColor(displayColor));
		$row.toggleClass('is-empty', value === '');
	}

	function initColorPicker($field) {
		if (!$field.length || !$field.hasClass('wpr-color-field')) {
			return;
		}

		updateModernColorUI($field);
	}

	function initColorPickers($context) {
		$context.find('.wpr-color-field').each(function () {
			initColorPicker($(this));
		});
	}

	function updateEffectOptions(scope) {
		const effect = $(scopeId(scope, 'text_effect')).val() || 'none';
		const $box = $('[data-effect-options-for="' + scope + '"]');

		if (!$box.length) {
			return;
		}

		const needsColor = [
			'glitch',
			'neon-glow',
			'text-shadow-glow',
			'gradient-text',
			'gradient-animation',
			'shimmer-text',
			'stroke-text',
			'outline-fill',
			'hover-highlight'
		];

		const needsColor2 = [
			'glitch',
			'gradient-text',
			'gradient-animation',
			'shimmer-text'
		];

		const needsStrength = [
			'neon-glow',
			'text-shadow-glow',
			'stroke-text',
			'outline-fill',
			'three-d-text'
		];

		const needsBlur = [
			'blur-in',
			'neon-glow',
			'text-shadow-glow',
			'shimmer-text'
		];

		$box.toggleClass('is-active', effect !== 'none');
		$box.find('.wpr-effect-option-color').toggle(needsColor.includes(effect));
		$box.find('.wpr-effect-option-color-2').toggle(needsColor2.includes(effect));
		$box.find('.wpr-effect-option-strength').toggle(needsStrength.includes(effect));
		$box.find('.wpr-effect-option-blur').toggle(needsBlur.includes(effect));
		$box.find('.wpr-effect-hint').toggle(effect === 'none');
	}

	function updateColorOptions(scope) {
		const gradientEnabled = $(scopeId(scope, 'gradient_enabled')).is(':checked');
		const bgGradientEnabled = $(scopeId(scope, 'bg_gradient_enabled')).is(':checked');
		const shadowEnabled = $(scopeId(scope, 'text_shadow_enabled')).is(':checked');
		const gradientType = $(scopeId(scope, 'gradient_type')).val() || 'linear';
		const bgGradientType = $(scopeId(scope, 'bg_gradient_type')).val() || 'linear';

		const $body = $(scopeId(scope, 'gradient_enabled')).closest('.wpr-editor-panel-body');
		const $gradientFields = $body.find('.wpr-gradient-fields');
		const $bgGradientFields = $body.find('.wpr-bg-gradient-fields');
		const $shadowFields = $(scopeId(scope, 'text_shadow_enabled')).closest('.wpr-editor-panel-body').find('.wpr-shadow-fields');

		$gradientFields.toggle(gradientEnabled);
		$gradientFields.find('#wpr-' + scope + '-gradient-angle').closest('.wpr-control').toggle(gradientType === 'linear');
		$gradientFields.find('#wpr-' + scope + '-gradient-position').closest('.wpr-control').toggle(gradientType === 'radial');

		$bgGradientFields.toggle(bgGradientEnabled);
		$bgGradientFields.find('#wpr-' + scope + '-bg-gradient-angle').closest('.wpr-control').toggle(bgGradientType === 'linear');
		$bgGradientFields.find('#wpr-' + scope + '-bg-gradient-position').closest('.wpr-control').toggle(bgGradientType === 'radial');

		$shadowFields.toggle(shadowEnabled);
	}

	function updateAllConditionalControls(scope) {
		updateEffectOptions(scope);
		updateColorOptions(scope);
	}

	function filterFontOptions() {
		const enabled = $('#wpr-enable-google-fonts').is(':checked');

		$('select[data-style-field="font_family"]').each(function () {
			const $select = $(this);

			$select.find('option[data-type="google"]').prop('disabled', !enabled).toggle(enabled);

			if (!enabled && $select.find(':selected').data('type') === 'google') {
				$select.val('inherit');
			}
		});
	}

	function setStyleValue(scope, field, value) {
		const $field = $(scopeId(scope, field));

		if (!$field.length) {
			return;
		}

		const finalValue = value !== undefined && value !== null && value !== '' ? value : defaults[field];

		if ($field.hasClass('wpr-color-field')) {
			$field.val(normalizeHexColor(finalValue || ''));
			initColorPicker($field);
			return;
		}

		if ($field.is(':checkbox')) {
			$field.prop('checked', String(finalValue) === '1' || finalValue === true);
			return;
		}

		$field.val(finalValue);
	}

	function collectStyleData(scope) {
		const data = {};

		styleFields.forEach(function (field) {
			const $field = $(scopeId(scope, field));
			data[field] = $field.is(':checkbox') ? ($field.is(':checked') ? 1 : 0) : $field.val();
		});

		return data;
	}

	function hydrateStyleControls(scope, item, $context) {
		initColorPickers($context);
		initInfoTips($context);

		styleFields.forEach(function (field) {
			setStyleValue(scope, field, item[field] || defaults[field]);
		});

		$context.find('input[type="range"]').each(function () {
			updateRangeOutput(this);
		});

		filterFontOptions();
		syncSecurityProviderSwitches();
		updateAllConditionalControls(scope);
		if (activePreview.scope === scope) {
			updatePreview();
		}
	}

	function styleTemplate(scope) {
		const template = $('#tmpl-wpr-style-controls').html() || '';
		return template.replaceAll('__SCOPE__', scope);
	}

	function isEmptyValue(value) {
		return value === undefined || value === null || String(value).trim() === '';
	}

	function unitValue(value, unit) {
		if (isEmptyValue(value)) {
			return '';
		}

		return String(value).replace(',', '.') + (unit || 'px');
	}

	function applyPreviewStyle(style) {
		const $word = $('#wpr-preview-word, #wpr-dock-preview-word');

		if (!$word.length) {
			return;
		}

		const effect = style.text_effect || 'none';
		const duration = parseInt(style.animation_duration || defaults.animation_duration, 10) || 600;
		const delay = parseInt(style.animation_delay || defaults.animation_delay, 10) || 0;

		const timing = style.animation_timing || defaults.animation_timing;

		const loopableEffects = [
			'pulse',
			'neon-glow',
			'text-shadow-glow',
			'gradient-animation',
			'shimmer-text',
			'wave-text',
			'glitch'
		];

		const iteration =
			String(style.animation_loop) === '1' &&
			loopableEffects.includes(effect)
				? 'infinite'
				: '1';

		const effectColor = style.effect_color || defaults.effect_color;
		const effectColor2 = style.effect_color_2 || defaults.effect_color_2;
		const strength = style.effect_strength || defaults.effect_strength;
		const blur = style.effect_blur || defaults.effect_blur;

		$word.removeClass(function (index, className) {
			return (className.match(/(^|\s)wpr-effect-\S+/g) || []).join(' ');
		});

		const css = {
			fontFamily: '',
			fontSize: '',
			fontWeight: '',
			lineHeight: '',
			fontStyle: '',
			textDecoration: '',
			textTransform: '',
			letterSpacing: '',
			wordSpacing: '',
			whiteSpace: '',
			color: '',
			backgroundColor: '',
			border: '',
			borderRadius: '',
			padding: '',
			animation: '',
			animationDelay: '',
			'--wpr-preview-effect-color': effectColor,
			'--wpr-preview-effect-color-2': effectColor2,
			'--wpr-preview-effect-strength': strength + 'px',
			'--wpr-preview-effect-blur': blur + 'px'
		};

		if (style.font_family && style.font_family !== 'inherit') {
			css.fontFamily = style.font_family;
		}

		const fontSize = unitValue(style.font_size, style.font_size_unit);

		if (fontSize) {
			css.fontSize = fontSize;
		}

		if (!isEmptyValue(style.font_weight)) {
			css.fontWeight = style.font_weight;
		}

		if (!isEmptyValue(style.line_height)) {
			css.lineHeight = String(style.line_height).replace(',', '.');
		}

		if (!isEmptyValue(style.font_style) && style.font_style !== 'normal') {
			css.fontStyle = style.font_style;
		}

		if (!isEmptyValue(style.text_decoration) && style.text_decoration !== 'none') {
			css.textDecoration = style.text_decoration;
		}

		if (!isEmptyValue(style.text_transform) && style.text_transform !== 'none') {
			css.textTransform = style.text_transform;
		}

		const letterSpacing = unitValue(style.letter_spacing, style.letter_spacing_unit);
		if (letterSpacing) {
			css.letterSpacing = letterSpacing;
		}

		const wordSpacing = unitValue(style.word_spacing, style.word_spacing_unit);
		if (wordSpacing) {
			css.wordSpacing = wordSpacing;
		}

		if (!isEmptyValue(style.white_space)) {
			css.whiteSpace = style.white_space;
		}

		if (!isEmptyValue(style.color)) {
			css.color = style.color;
		}

		if (!isEmptyValue(style.background_color)) {
			css.backgroundColor = style.background_color;
		}

		if (String(style.bg_gradient_enabled) === '1') {
			const bgGradientType = style.bg_gradient_type || defaults.bg_gradient_type;
			const bgGradientAngle = style.bg_gradient_angle || defaults.bg_gradient_angle;
			const bgGradientPosition = style.bg_gradient_position || defaults.bg_gradient_position;
			css.backgroundImage = bgGradientType === 'radial'
				? 'radial-gradient(circle at ' + bgGradientPosition + ',' + (style.bg_gradient_color_1 || defaults.bg_gradient_color_1) + ',' + (style.bg_gradient_color_2 || defaults.bg_gradient_color_2) + ')'
				: 'linear-gradient(' + bgGradientAngle + 'deg,' + (style.bg_gradient_color_1 || defaults.bg_gradient_color_1) + ',' + (style.bg_gradient_color_2 || defaults.bg_gradient_color_2) + ')';
		}

		if (String(style.gradient_enabled) === '1') {

			const gradientType = style.gradient_type || defaults.gradient_type;
			const gradientAngle = style.gradient_angle || defaults.gradient_angle;
			const gradientPosition = style.gradient_position || defaults.gradient_position;

			const gradientValue =
				gradientType === 'radial'
					? 'radial-gradient(circle at ' + gradientPosition + ',' + (style.gradient_color_1 || defaults.gradient_color_1) + ',' + (style.gradient_color_2 || defaults.gradient_color_2) + ')'
					: 'linear-gradient(' + gradientAngle + 'deg,' + (style.gradient_color_1 || defaults.gradient_color_1) + ',' + (style.gradient_color_2 || defaults.gradient_color_2) + ')';

			css.backgroundImage = gradientValue;
			css.WebkitBackgroundClip = 'text';
			css.backgroundClip = 'text';
			css.color = 'transparent';

		} else {

			if (String(style.bg_gradient_enabled) !== '1') {
				css.backgroundImage = '';
			}
			css.WebkitBackgroundClip = '';
			css.backgroundClip = '';
		}

		if (String(style.text_shadow_enabled) === '1') {

			css.textShadow =
				(style.text_shadow_x || '0') +
				'px ' +
				(style.text_shadow_y || '2') +
				'px ' +
				(style.text_shadow_blur || '8') +
				'px ' +
				(style.text_shadow_color || defaults.text_shadow_color);

		} else {

			css.textShadow = '';
		}

		const borderStyle = style.border_style || 'solid';
		const borderColor = style.border_color || defaults.border_color;
		const borderUnit = style.border_width_unit || 'px';
		const sideBorders = {
			borderTop: style.border_width_t,
			borderRight: style.border_width_r,
			borderBottom: style.border_width_b,
			borderLeft: style.border_width_l
		};
		const hasSideBorder = Object.values(sideBorders).some(function (value) {
			return parseFloat(String(value || '0').replace(',', '.')) > 0;
		});

		if (borderStyle !== 'none') {
			if (hasSideBorder) {
				Object.keys(sideBorders).forEach(function (property) {
					const value = sideBorders[property];
					if (parseFloat(String(value || '0').replace(',', '.')) > 0) {
						css[property] = unitValue(value, borderUnit) + ' ' + borderStyle + ' ' + borderColor;
					} else {
						css[property] = '';
					}
				});
			} else {
				const borderWidth = parseFloat(String(style.border_width || '0').replace(',', '.'));
				if (borderWidth > 0) {
					css.border = unitValue(style.border_width, borderUnit) + ' ' + borderStyle + ' ' + borderColor;
				}
			}
		}

		const radiusUnit = style.border_radius_unit || 'px';

		css.borderRadius = [
			unitValue(style.border_radius_t || '0', radiusUnit),
			unitValue(style.border_radius_r || '0', radiusUnit),
			unitValue(style.border_radius_b || '0', radiusUnit),
			unitValue(style.border_radius_l || '0', radiusUnit)
		].join(' ');

		const paddingUnit = style.padding_unit || 'px';

		const paddingValues = [
			unitValue(style.padding_t || '0', paddingUnit),
			unitValue(style.padding_r || '0', paddingUnit),
			unitValue(style.padding_b || '0', paddingUnit),
			unitValue(style.padding_l || '0', paddingUnit)
		];

		const transformEffects = [
			'pulse',
			'bounce',
			'shake',
			'wave-text',
			'glitch',
			'zoom-in'
		];

		if (paddingValues.some(function (value) {
			return parseFloat(value) > 0;
		})) {

			css.padding = paddingValues.join(' ');
			$word.css('display', 'inline-block');

		} else if (transformEffects.includes(effect)) {

			$word.css('display', 'inline-block');

		} else {

			$word.css('display', 'inline');
		}

		if (effect !== 'none') {

			$word.addClass('wpr-effect-' + effect);

			css.animation =
				'wpr-preview-' +
				effect +
				' ' +
				duration +
				'ms ' +
				timing +
				' ' +
				iteration +
				' both';

			css.animationDelay = delay + 'ms';
		}

		$word.css(css);
	}

	function getActivePreviewStyle() {
		if (activePreview.type === 'pair' && activePreview.scope) {
			return Object.assign({}, defaults, collectStyleData(activePreview.scope));
		}

		return Object.assign({}, defaults, collectStyleData('global'));
	}

	function updatePreview() {
		const style = getActivePreviewStyle();
		const original = activePreview.original || 'Keyword';
		const replacement = activePreview.replacement || 'Replacement';

		$('#wpr-preview-word, #wpr-dock-preview-word').text(replacement);
		$('#wpr-preview-pair, #wpr-dock-preview-pair').text(replacement);
		$('#wpr-preview-mode').text(activePreview.type === 'pair' ? 'Individual Pair Styling' : 'Global Styling');
		$('#wpr-dock-preview-class').text(activePreview.id ? 'wpr-replaced-' + activePreview.id : 'wpr-replaced');
		$('#wpr-dock-preview-custom').text(style.custom_class || '—');
		$('#wpr-dock-preview-id').text(style.custom_id || '—');

		applyPreviewStyle(style);
	}

	function replayPreviewAnimation() {
		const $word = $('#wpr-preview-word');

		if (!$word.length) {
			return;
		}

		$word.removeClass('is-replaying');
		void $word[0].offsetWidth;
		$word.addClass('is-replaying');
		updatePreview();
	}


	function resetForm() {
		$('#wpr-id').val('0');
		$('#wpr-original-word').val('');
		$('#wpr-replacement-word').val('');
		$('#wpr-is-active').prop('checked', true);
		$('#wpr-case-sensitive').prop('checked', false);
		$('#wpr-whole-word').prop('checked', true);
		$('#wpr-original-word').trigger('focus');
	}

	function collectPairData() {
		return {
			id: $('#wpr-id').val(),
			original_word: $('#wpr-original-word').val(),
			replacement_word: $('#wpr-replacement-word').val(),
			is_active: $('#wpr-is-active').is(':checked') ? 1 : 0,
			case_sensitive: $('#wpr-case-sensitive').is(':checked') ? 1 : 0,
			whole_word: $('#wpr-whole-word').is(':checked') ? 1 : 0,
			use_custom_style: 0
		};
	}


	function syncSecurityProviderSwitches() {
		const $monitor = $('#wpr-security-monitor-enabled');
		const enabled = $monitor.is(':checked');
		const selectors = [
			'#wpr-security-wpvulnerability-enabled',
			'#wpr-security-wordfence-enabled',
			'#wpr-security-wpscan-enabled',
			'#wpr-security-patchstack-enabled'
		];

		selectors.forEach(function (selector) {
			const $field = $(selector);
			if (!$field.length) {
				return;
			}
			if (typeof $field.data('wprRememberState') === 'undefined') {
				$field.data('wprRememberState', $field.is(':checked') ? 1 : 0);
			}

			const $switch = $field.closest('.wpr-binary-switch');
			if (!enabled) {
				$field.prop('checked', false).prop('disabled', true);
				$switch.addClass('is-monitor-muted');
				return;
			}

			$field.prop('disabled', false).prop('checked', parseInt($field.data('wprRememberState'), 10) === 1);
			$switch.removeClass('is-monitor-muted');
		});
	}

	function collectSettingsData() {
		const data = {
			enable_google_fonts: $('#wpr-enable-google-fonts').is(':checked') ? 1 : 0,
			security_monitor_enabled: $('#wpr-security-monitor-enabled').is(':checked') ? 1 : 0,
			security_wpvulnerability_enabled: ($('#wpr-security-monitor-enabled').is(':checked') ? $('#wpr-security-wpvulnerability-enabled').is(':checked') : parseInt($('#wpr-security-wpvulnerability-enabled').data('wprRememberState') || 0, 10)) ? 1 : 0,
			security_wordfence_enabled: ($('#wpr-security-monitor-enabled').is(':checked') ? $('#wpr-security-wordfence-enabled').is(':checked') : parseInt($('#wpr-security-wordfence-enabled').data('wprRememberState') || 0, 10)) ? 1 : 0,
			security_wpscan_enabled: ($('#wpr-security-monitor-enabled').is(':checked') ? $('#wpr-security-wpscan-enabled').is(':checked') : parseInt($('#wpr-security-wpscan-enabled').data('wprRememberState') || 0, 10)) ? 1 : 0,
			security_wpscan_api_token: $('#wpr-security-wpscan-api-token').val(),
			security_patchstack_enabled: ($('#wpr-security-monitor-enabled').is(':checked') ? $('#wpr-security-patchstack-enabled').is(':checked') : parseInt($('#wpr-security-patchstack-enabled').data('wprRememberState') || 0, 10)) ? 1 : 0,
			security_patchstack_api_key: $('#wpr-security-patchstack-api-key').val(),
			custom_css: $('#wpr-custom-css').val()
		};

		const globalStyle = collectStyleData('global');

		Object.keys(globalStyle).forEach(function (field) {
			data['global_' + field] = globalStyle[field];
		});

		return data;
	}

	function renderRows(items) {
		$list.empty();
		itemsById = {};

		if (!items || !items.length) {
			$list.html('<div class="wpr-empty">' + esc(wprAdmin.i18n.empty) + '</div>');
			return;
		}

		let rendered = 0;

		items.forEach(function (item) {
			if ((!item.original_word || item.original_word === '') && (!item.replacement_word || item.replacement_word === '')) {
				return;
			}

			itemsById[String(item.id)] = item;
			itemsById[item.id] = item;

			const active = parseInt(item.is_active, 10) === 1;
			const effect = item.text_effect && item.text_effect !== 'none' ? item.text_effect : '';
			const title = item.original_word || item.replacement_word || '—';

			const html = [
				'<article class="wpr-pair-card wpr-pair-list-item" data-id="' + esc(item.id) + '" data-status="' + (active ? 'active' : 'inactive') + '" data-search="' + esc((item.original_word || '') + ' ' + (item.replacement_word || '')) + '">',
					'<div class="wpr-pair-main">',
						'<button type="button" class="wpr-pair-select wpr-open-style" data-id="' + esc(item.id) + '" title="Styling bearbeiten">',
							'<span class="wpr-pair-state ' + (active ? 'is-active' : '') + '"></span>',
							'<span class="wpr-pair-words"><strong>' + esc(title) + '</strong></span>',
							(effect ? '<span class="wpr-pair-effect-chip">' + esc(effect) + '</span>' : ''),
						'</button>',
						'<div class="wpr-pair-meta">',
							'<label class="wpr-status-toggle" title="' + (active ? 'Aktiv' : 'Inaktiv') + '">',
								'<input type="checkbox" class="wpr-toggle-active" ' + (active ? 'checked' : '') + '>',
								'<span></span>',
							'</label>',
							'<button type="button" class="button wpr-favorite" data-id="' + esc(item.id) + '" title="Favorit">☆</button>',
							'<button type="button" class="button button-link-delete wpr-delete" data-id="' + esc(item.id) + '" title="Löschen">×</button>',
						'</div>',
					'</div>',
				'</article>'
			].join('');

			$list.append(html);
			rendered++;
		});

		if (rendered === 0) {
			$list.html('<div class="wpr-empty">' + esc(wprAdmin.i18n.empty) + '</div>');
		}

		updateFavoriteButtons();
		updateBrowserCounts();
		applyPairFilters();
	}


	function prepareInspectorTabs($context) {
		const $editor = $context.find('.wpr-dock-body .wpr-style-editor').first();
		const $panels = $editor.find('> .wpr-editor-panel');

		if (!$editor.length || !$panels.length) {
			return;
		}

		$editor.find('> .wpr-vertical-tabs').remove();
		const $nav = $('<nav class="wpr-vertical-tabs" aria-label="Style sections"></nav>');

		$panels.each(function (index) {
			const $panel = $(this);
			const title = $.trim($panel.find('summary strong').first().text()) || 'Style';
			const icon = $.trim($panel.find('.wpr-panel-icon').first().text()) || '•';
			const id = 'wpr-section-' + index;
			const group = String($panel.data('reset-group') || 'style');
			const $body = $panel.find('> .wpr-editor-panel-body');

			$panel.attr('data-tab-id', id).attr('data-main-tab', group).prop('open', index === 0);
			$panel.find('> summary').attr('aria-hidden', 'true');

			if (!$body.find('> .wpr-section-toolbar').length) {
				$body.prepend(
					'<div class="wpr-section-toolbar">' +
						'<span><em>' + esc(icon) + '</em><strong>' + esc(title) + '</strong></span>' +
						'<button type="button" class="button wpr-reset-section" data-reset-group="' + esc(group) + '">' + esc(i18n.reset) + '</button>' +
					'</div>'
				);
			}

			$nav.append('<button type="button" class="wpr-vertical-tab ' + (index === 0 ? 'is-active' : '') + '" data-target="' + id + '" data-group="' + esc(group) + '"><span>' + esc(icon) + '</span><strong>' + esc(title) + '</strong><i></i></button>');
		});

		$editor.prepend($nav);
	}


	function activateDockMainTab($dock, tab) {
		const current = tab || 'style';
		const groups = {
			style: ['typography', 'colors', 'spacing', 'border', 'customcss'],
			effects: ['effects'],
			seo: ['linkseo'],
			responsive: ['responsive']
		};
		const allowed = groups[current] || groups.style;
		const $editor = $dock.find('.wpr-dock-body .wpr-style-editor').first();

		$dock.find('.wpr-dock-tab').removeClass('is-active');
		$dock.find('.wpr-dock-tab[data-tab="' + current + '"]').addClass('is-active');

		$editor.find('> .wpr-editor-panel').each(function () {
			const $panel = $(this);
			const group = String($panel.data('main-tab') || $panel.data('reset-group') || '');
			$panel.toggle(allowed.includes(group));
			$panel.prop('open', false);
		});

		$editor.find('> .wpr-vertical-tabs .wpr-vertical-tab').each(function () {
			const $button = $(this);
			const group = String($button.data('group') || '');
			$button.toggle(allowed.includes(group)).removeClass('is-active');
		});

		const $firstTab = $editor.find('> .wpr-vertical-tabs .wpr-vertical-tab:visible').first();
		if ($firstTab.length) {
			const target = $firstTab.data('target');
			$firstTab.addClass('is-active');
			$editor.find('> .wpr-editor-panel[data-tab-id="' + target + '"]').prop('open', true).show();
		}
	}


	function currentPairFilter() {
		return $('.wpr-browser-filter.is-active').data('filter') || 'all';
	}

	function applyPairFilters() {
		const query = String($('#wpr-pair-search').val() || '').toLowerCase();
		const filter = currentPairFilter();
		const favorites = getFavorites();

		$('.wpr-pair-list-item').each(function () {
			const $item = $(this);
			const haystack = String($item.data('search') || '').toLowerCase();
			const status = String($item.data('status') || 'inactive');
			const id = String($item.data('id'));
			let visible = haystack.indexOf(query) !== -1;

			if (filter === 'active') {
				visible = visible && status === 'active';
			} else if (filter === 'inactive') {
				visible = visible && status === 'inactive';
			} else if (filter === 'favorites') {
				visible = visible && favorites.includes(id);
			}

			$item.toggle(visible);
		});
	}


	function updateBrowserCounts() {
		const favorites = getFavorites();
		const $items = $('.wpr-pair-list-item');
		const total = $items.length;
		const active = $items.filter('[data-status="active"]').length;
		const inactive = $items.filter('[data-status="inactive"]').length;
		let fav = 0;
		$items.each(function () { if (favorites.includes(String($(this).data('id')))) { fav++; } });
		$('.wpr-browser-filter[data-filter="all"]').text('Alle (' + total + ')');
		$('.wpr-browser-filter[data-filter="active"]').text('Aktiv (' + active + ')');
		$('.wpr-browser-filter[data-filter="inactive"]').text('Inaktiv (' + inactive + ')');
		$('.wpr-browser-filter[data-filter="favorites"]').text('Favoriten (' + fav + ')');
		$('#wpr-pair-total-count').text(total + ' Einträge');
	}


	function styleBadges(style) {
		const groups = {
			'Typography': ['font_family','font_size','font_weight','line_height','font_style','text_decoration','text_transform','letter_spacing','word_spacing','white_space'],
			'Colors': ['color','background_color','gradient_enabled','bg_gradient_enabled','text_shadow_enabled'],
			'Spacing': ['padding_t','padding_r','padding_b','padding_l'],
			'Border': ['border_width','border_width_t','border_width_r','border_width_b','border_width_l','border_color','border_radius_t','border_radius_r','border_radius_b','border_radius_l'],
			'Effects': ['text_effect'],
			'CSS': ['custom_class','custom_id','pair_custom_css'],
			'Link & SEO': ['link_enabled','link_url','link_title','link_aria_label','link_rel_nofollow','link_rel_sponsored','link_rel_noopener']
		};
		const badges = [];
		Object.keys(groups).forEach(function (label) {
			groups[label].some(function (field) {
				const value = style && style[field] !== undefined ? String(style[field]) : '';
				const def = defaults[field] !== undefined ? String(defaults[field]) : '';
				if ($.trim(value) !== '' && value !== def && value !== '0' && value !== 'none') {
					badges.push(label);
					return true;
				}
				return false;
			});
		});
		return badges.length ? badges : ['Clean'];
	}

	function renderPresetSelect($select) {
		if (!$select || !$select.length) {
			return;
		}
		$select.empty().append('<option value="">—</option>');
		allPresets.forEach(function (preset) {
			$select.append('<option value="' + esc(preset.id) + '">' + esc(preset.name) + (preset.readonly ? ' ★' : '') + '</option>');
		});
	}

	function presetById(id) {
		id = String(id || '');
		return allPresets.find(function (preset) { return String(preset.id) === id; });
	}

	function renderPresetPreviewStyle(style) {
		let css = '';
		if (style.color) { css += 'color:' + style.color + ';'; }
		if (style.background_color) { css += 'background-color:' + style.background_color + ';'; }
		if (String(style.gradient_enabled) === '1') {
			const a = style.gradient_angle || '90';
			css += 'background-image:linear-gradient(' + a + 'deg,' + (style.gradient_color_1 || '#24afab') + ',' + (style.gradient_color_2 || '#6c5ce7') + ');-webkit-background-clip:text;background-clip:text;color:transparent;';
		}
		if (String(style.bg_gradient_enabled) === '1') {
			const a = style.bg_gradient_angle || '90';
			css += 'background-image:linear-gradient(' + a + 'deg,' + (style.bg_gradient_color_1 || '#24afab') + ',' + (style.bg_gradient_color_2 || '#6c5ce7') + ');';
		}
		if (style.font_family && style.font_family !== 'inherit') { css += 'font-family:' + style.font_family + ';'; }
		if (style.font_weight) { css += 'font-weight:' + style.font_weight + ';'; }
		if (style.font_size) { css += 'font-size:' + style.font_size + (style.font_size_unit || 'px') + ';'; }
		if (style.border_color) { css += 'border:1px solid ' + style.border_color + ';'; }
		if (style.text_shadow_enabled === '1') { css += 'text-shadow:0 0 ' + (style.text_shadow_blur || '8') + 'px ' + (style.text_shadow_color || '#000') + ';'; }
		css += 'padding:6px 12px;border-radius:12px;display:inline-block;';
		return css;
	}

	function renderPresetLibrary() {
		const $wrap = $('#wpr-preset-library-dynamic');
		if (!$wrap.length) {
			return;
		}
		if (!allPresets.length) {
			$wrap.html('<div class="wpr-empty">No presets found.</div>');
			return;
		}
		const html = allPresets.map(function (preset) {
			const style = Object.assign({}, defaults, preset.style || {});
			const badges = (preset.badges || styleBadges(style)).map(function (b) { return '<span>' + esc(b) + '</span>'; }).join('');
			return [
				'<article class="wpr-preset-card" data-preset-id="' + esc(preset.id) + '">',
					'<div class="wpr-preset-preview"><strong style="' + esc(renderPresetPreviewStyle(style)) + '">' + esc(preset.preview_keyword || 'WordPress') + '</strong></div>',
					'<h3>' + esc(preset.name) + (preset.readonly ? ' <small>Default</small>' : '') + '</h3>',
					'<p>' + esc(preset.description || '') + '</p>',
					'<div class="wpr-preset-badges">' + badges + '</div>',
					'<div class="wpr-preset-card-actions">',
						'<button type="button" class="button wpr-export-preset" data-id="' + esc(preset.id) + '">Export</button>',
						(preset.readonly ? '' : '<button type="button" class="button button-link-delete wpr-delete-preset" data-id="' + esc(preset.id) + '">Delete</button>'),
					'</div>',
				'</article>'
			].join('');
		}).join('');
		$wrap.html(html);
	}

	function loadPresets() {
		request('wpr_get_presets')
			.done(function (response) {
				if (!response.success) {
					return;
				}
				allPresets = response.data && response.data.presets ? response.data.presets : [];
				renderPresetLibrary();
				renderPresetSelect($('.wpr-preset-select'));
			})
			.fail(function () {
				renderPresetLibrary();
			});
	}

	function applyPresetToScope(preset, scope) {
		if (!preset || !preset.style || !scope) {
			showMessage(i18n.noPreset || 'No preset selected.', 'error');
			return;
		}
		styleFields.forEach(function (field) {
			if (preset.style[field] !== undefined) {
				setStyleValue(scope, field, preset.style[field]);
			}
		});
		updateAllConditionalControls(scope);
		updatePreview();
		showMessage(i18n.presetApplied || 'Preset applied.');
	}

	function loadPairs() {
		$list.html('<div class="wpr-empty">' + esc(wprAdmin.i18n.loading) + '</div>');

		request('wpr_get_pairs')
			.done(function (response) {
				if (!response.success) {
					showMessage(wprAdmin.i18n.error, 'error');
					return;
				}

				renderRows(response.data.items);
			})
			.fail(function (xhr) {
				const msg = xhr.responseJSON && xhr.responseJSON.data && xhr.responseJSON.data.message
					? xhr.responseJSON.data.message
					: wprAdmin.i18n.error;

				$list.html('<div class="wpr-empty is-error">' + esc(msg) + '</div>');
				showMessage(msg, 'error');
			});
	}

	$form.on('submit', function (event) {
		event.preventDefault();

		request('wpr_save_pair', collectPairData())
			.done(function (response) {
				if (!response.success) {
					showMessage(response.data && response.data.message ? response.data.message : wprAdmin.i18n.error, 'error');
					return;
				}

				showMessage(wprAdmin.i18n.saved);
				resetForm();
				loadPairs();
			})
			.fail(function (xhr) {
				const msg = xhr.responseJSON && xhr.responseJSON.data && xhr.responseJSON.data.message
					? xhr.responseJSON.data.message
					: wprAdmin.i18n.error;

				showMessage(msg, 'error');
			});
	});


	$(document).on('change', '#wpr-security-wpvulnerability-enabled, #wpr-security-wordfence-enabled, #wpr-security-wpscan-enabled, #wpr-security-patchstack-enabled', function () {
		if ($('#wpr-security-monitor-enabled').is(':checked')) {
			$(this).data('wprRememberState', $(this).is(':checked') ? 1 : 0);
		}
	});

	$(document).on('change', '#wpr-security-monitor-enabled', function () {
		syncSecurityProviderSwitches();
	});

	$settingsForm.on('submit', function (event) {
		event.preventDefault();

		const $result = $('#wpr-settings-save-result');
		showInlineStatus($result, wprAdmin.i18n.saving || 'Saving...', 'saving');

		request('wpr_save_settings', collectSettingsData())
			.done(function (response) {
				if (!response.success) {
					showInlineStatus($result, response.data && response.data.message ? response.data.message : wprAdmin.i18n.error, 'error');
					return;
				}

				showInlineStatus($result, response.data.message || wprAdmin.i18n.saved);
				if (response.data && response.data.details_html) {
					$('#wpr-security-scan-details').html(response.data.details_html);
				}
				showMessage(response.data.message || wprAdmin.i18n.saved);
				filterFontOptions();
				syncSecurityProviderSwitches();
				if (activePreview.type === 'global') {
					updatePreview();
				}
			})
			.fail(function (xhr) {
				const msg = xhr.responseJSON && xhr.responseJSON.data && xhr.responseJSON.data.message
					? xhr.responseJSON.data.message
					: wprAdmin.i18n.error;

				showInlineStatus($result, msg, 'error');
			});
	});

	$(document).on('click', '#wpr-run-security-scan', function () {
		const $button = $(this);
		const $result = $('#wpr-security-scan-result');
		const originalText = $button.text();
		$button.prop('disabled', true).text(wprAdmin.i18n.scanning || 'Scanning...');
		showInlineStatus($result, wprAdmin.i18n.scanning || 'Scanning...', 'saving');

		request('wpr_run_security_scan', {})
			.done(function (response) {
				if (!response.success) {
					showInlineStatus($result, response.data && response.data.message ? response.data.message : wprAdmin.i18n.error, 'error');
					return;
				}

				showInlineStatus($result, response.data.message || wprAdmin.i18n.saved);
				if (response.data && response.data.details_html) {
					$('#wpr-security-scan-details').html(response.data.details_html);
				}
				showMessage(response.data.message || wprAdmin.i18n.saved);
			})
			.fail(function (xhr) {
				const msg = xhr.responseJSON && xhr.responseJSON.data && xhr.responseJSON.data.message
					? xhr.responseJSON.data.message
					: wprAdmin.i18n.error;
				showInlineStatus($result, msg, 'error');
			})
			.always(function () {
				$button.prop('disabled', false).text(originalText);
			});
	});

	$(document).on('change', 'input[name="plugin_language"], select[name="plugin_language"]', function () {
		request('wpr_save_language', { plugin_language: $(this).val() })
			.done(function () {
				window.location.reload();
			})
			.fail(function () {
				showMessage(wprAdmin.i18n.error, 'error');
			});
	});

	$(document).on('change', '.wpr-language-toggle', function () {
		const locale = $(this).is(':checked') ? 'de_DE' : 'en_US';

		request('wpr_save_language', { plugin_language: locale })
			.done(function () {
				window.location.reload();
			})
			.fail(function () {
				showMessage(wprAdmin.i18n.error, 'error');
			});
	});

	$(document).on('click', '.wpr-edit', function (event) {
		event.preventDefault();

		const item = itemsById[String($(this).data('id'))];

		if (!item) {
			showMessage(wprAdmin.i18n.error, 'error');
			return;
		}

		$('#wpr-id').val(item.id);
		$('#wpr-original-word').val(item.original_word || '');
		$('#wpr-replacement-word').val(item.replacement_word || '');
		$('#wpr-is-active').prop('checked', parseInt(item.is_active, 10) === 1);
		$('#wpr-case-sensitive').prop('checked', parseInt(item.case_sensitive, 10) === 1);
		$('#wpr-whole-word').prop('checked', parseInt(item.whole_word, 10) === 1);

		const top = $('#wpr-form').closest('.wpr-card').offset().top - 40;
		$('html, body').animate({ scrollTop: top }, 250);
		$('#wpr-original-word').trigger('focus');
	});

	$(document).on('click', '.wpr-open-style', function (event) {
		event.preventDefault();

		const itemId = String($(this).data('id'));
		const item = itemsById[itemId];

		if (!item) {
			showMessage(wprAdmin.i18n.error, 'error');
			return;
		}

		const $dock = $('#wpr-editor-dock');
		const scope = 'pair-' + itemId;

		$('.wpr-pair-card').removeClass('is-selected');
		$('.wpr-pair-card[data-id="' + itemId + '"]').addClass('is-selected');

		const html = [
			'<div class="wpr-dock-head">',
				'<div>',
					'<span class="wpr-dock-kicker">' + esc(i18n.selectedPair) + '</span>',
					'<h3>' + esc(item.original_word || 'Keyword') + ' <span>→</span> <em>' + esc(item.replacement_word || 'Replacement') + '</em></h3><small class="wpr-last-updated">' + esc(item.updated_at ? (i18n.lastChange + ' ' + item.updated_at) : i18n.neverSaved) + '</small>',
				'</div>',
				'<div class="wpr-dock-actions">',
					'<button type="button" class="button wpr-duplicate-pair" data-id="' + esc(itemId) + '">Duplizieren</button>',
					'<button type="button" class="button wpr-export-pair" data-id="' + esc(itemId) + '">Exportieren</button>',
					'<button type="button" class="button wpr-edit" data-id="' + esc(itemId) + '">Wort bearbeiten</button>',
					'<button type="button" class="button button-primary wpr-primary wpr-save-style" data-id="' + esc(itemId) + '">' + esc(i18n.saveStyle) + '</button>',
				'</div>',
			'</div>',
			'<div class="wpr-pair-style-body wpr-dock-body">',
				'<div class="wpr-dock-editor-grid">',
					'<div class="wpr-dock-controls">' + styleTemplate(scope) + '</div>',
					'<aside class="wpr-dock-preview-panel">',
						'<div class="wpr-preset-toolbar" data-scope="' + esc(scope) + '">',
							'<label><span>' + esc(i18n.loadPreset) + '</span><select class="wpr-preset-select"><option value="">—</option></select></label>',
							'<div class="wpr-preset-toolbar-actions">',
								'<button type="button" class="button wpr-apply-preset">' + esc(i18n.applyPreset) + '</button>',
								'<button type="button" class="button wpr-save-current-preset">' + esc(i18n.saveAsPreset) + '</button>',
							'</div>',
						'</div>',
						'<div class="wpr-dock-preview-title"><span></span> ' + esc(i18n.livePreview) + '</div>',
						'<div class="wpr-dock-preview-canvas is-dark" id="wpr-dock-preview">',
							'<div class="wpr-dock-preview-word" id="wpr-dock-preview-word">' + esc(item.replacement_word || 'Replacement') + '</div>',
						'</div>',
						'<p class="wpr-dock-preview-help">' + esc(i18n.frontendPreviewHelp) + '</p>',
						'<div class="wpr-css-info-grid">',
							'<label>' + esc(i18n.automaticClass) + '<code id="wpr-dock-preview-class">wpr-replaced-' + esc(itemId) + '</code></label>',
							'<label>' + esc(i18n.customClass) + '<code id="wpr-dock-preview-custom">' + esc(item.custom_class || '—') + '</code></label>',
							'<label>' + esc(i18n.customId) + '<code id="wpr-dock-preview-id">' + esc(item.custom_id || '—') + '</code></label>',
						'</div>',
					'</aside>',
				'</div>',
				'<div class="wpr-actions wpr-dock-footer">',
					'<button type="button" class="button button-primary wpr-primary wpr-save-style" data-id="' + esc(itemId) + '">' + esc(i18n.saveStyle) + '</button>',
				'</div>',
			'</div>'
		].join('');

		$dock.html(html);
		hydrateStyleControls(scope, item, $dock);
		prepareInspectorTabs($dock);
		renderPresetSelect($dock.find('.wpr-preset-select'));

		activePreview = {
			type: 'pair',
			id: itemId,
			scope: scope,
			original: item.original_word || 'Keyword',
			replacement: item.replacement_word || 'Replacement'
		};
		updatePreview();
	});

	$(document).on('click', '.wpr-save-style', function (event) {
		event.preventDefault();

		const itemId = String($(this).data('id'));
		const scope = 'pair-' + itemId;
		const data = Object.assign({ id: itemId, use_custom_style: 1 }, collectStyleData(scope));

		request('wpr_save_pair_style', data)
			.done(function (response) {
				if (!response.success) {
					showMessage(response.data && response.data.message ? response.data.message : wprAdmin.i18n.error, 'error');
					return;
				}

				showMessage(response.data.message || wprAdmin.i18n.saved);
				if (itemsById[itemId]) {
					Object.assign(itemsById[itemId], collectStyleData(scope), { use_custom_style: 1 });
				}
				updatePreview();
				loadPairs();
			})
			.fail(function (xhr) {
				const msg = xhr.responseJSON && xhr.responseJSON.data && xhr.responseJSON.data.message
					? xhr.responseJSON.data.message
					: wprAdmin.i18n.error;

				showMessage(msg, 'error');
			});
	});

	$(document).on('change', '.wpr-toggle-active', function () {
		const $toggle = $(this);
		const $card = $toggle.closest('.wpr-pair-card');
		const itemId = String($card.data('id'));
		const active = $toggle.is(':checked') ? 1 : 0;

		$toggle.prop('disabled', true);

		request('wpr_toggle_pair', { id: itemId, is_active: active })
			.done(function (response) {
				if (!response.success) {
					$toggle.prop('checked', !active);
					showMessage(response.data && response.data.message ? response.data.message : wprAdmin.i18n.error, 'error');
					return;
				}

				if (itemsById[itemId]) {
					itemsById[itemId].is_active = active;
				}

				showMessage(response.data && response.data.message ? response.data.message : wprAdmin.i18n.saved);
			})
			.fail(function (xhr) {
				$toggle.prop('checked', !active);

				const msg = xhr.responseJSON && xhr.responseJSON.data && xhr.responseJSON.data.message
					? xhr.responseJSON.data.message
					: wprAdmin.i18n.error;

				showMessage(msg, 'error');
			})
			.always(function () {
				$toggle.prop('disabled', false);
			});
	});

	$(document).on('click', '.wpr-delete', function (event) {
		event.preventDefault();

		const itemId = $(this).data('id');

		if (!window.confirm(wprAdmin.i18n.confirmDelete)) {
			return;
		}

		request('wpr_delete_pair', { id: itemId })
			.done(function (response) {
				if (!response.success) {
					showMessage(response.data && response.data.message ? response.data.message : wprAdmin.i18n.error, 'error');
					return;
				}

				showMessage(wprAdmin.i18n.deleted);
				loadPairs();
			})
			.fail(function () {
				showMessage(wprAdmin.i18n.error, 'error');
			});
	});

	$('#wpr-reset').on('click', resetForm);

	$(document).on('change', '#wpr-enable-google-fonts', function () {
		const $field = $(this);
		const enabled = $field.is(':checked') ? 1 : 0;

		filterFontOptions();
		$field.prop('disabled', true).closest('.wpr-binary-switch').addClass('is-saving');

		request('wpr_save_google_fonts', { enable_google_fonts: enabled })
			.done(function (response) {
				if (!response.success) {
					showMessage(response.data && response.data.message ? response.data.message : wprAdmin.i18n.error, 'error');
					$field.prop('checked', !enabled);
					filterFontOptions();
					return;
				}

				showMessage(
					response.data && response.data.message
						? response.data.message
						: (enabled ? wprAdmin.i18n.googleFontsEnabled : wprAdmin.i18n.googleFontsDisabled)
				);

				if (activePreview.type === 'global') {
					updatePreview();
				}
			})
			.fail(function () {
				$field.prop('checked', !enabled);
				filterFontOptions();
				showMessage(wprAdmin.i18n.error, 'error');
			})
			.always(function () {
				$field.prop('disabled', false).closest('.wpr-binary-switch').removeClass('is-saving');
			});
	});

	$(document).on('change', 'select[data-style-field="text_effect"]', function () {
		const id = $(this).attr('id') || '';
		const scope = id.replace(/^wpr-/, '').replace(/-text-effect$/, '');
		updateEffectOptions(scope);
	});

	$(document).on('input change', 'input[type="range"]', function () {
		updateRangeOutput(this);
	});



	const sectionResetFields = {
		typography: {
			font_family: 'inherit',
			font_size: '',
			font_size_unit: 'px',
			font_weight: '',
			line_height: '',
			font_style: 'normal',
			text_decoration: 'none',
			text_transform: 'none',
			letter_spacing: '',
			letter_spacing_unit: 'px',
			word_spacing: '',
			word_spacing_unit: 'px',
			white_space: ''
		},
		colors: {
			color: '',
			background_color: '',
			bg_gradient_enabled: '0',
			bg_gradient_color_1: '#24afab',
			bg_gradient_color_2: '#6c5ce7',
			bg_gradient_type: 'linear',
			bg_gradient_angle: '90',
			bg_gradient_position: 'center',
			gradient_enabled: '0',
			gradient_color_1: '#24afab',
			gradient_color_2: '#6c5ce7',
			gradient_type: 'linear',
			gradient_angle: '90',
			gradient_position: 'center',
			text_shadow_enabled: '0',
			text_shadow_color: '#000000',
			text_shadow_x: '0',
			text_shadow_y: '2',
			text_shadow_blur: '8'
		},
		spacing: {
			padding_t: '',
			padding_r: '',
			padding_b: '',
			padding_l: '',
			padding_unit: 'px'
		},
		border: {
			border_width: '0',
			border_width_unit: 'px',
			border_width_t: '',
			border_width_r: '',
			border_width_b: '',
			border_width_l: '',
			border_style: 'solid',
			border_color: '#24afab',
			border_radius_t: '',
			border_radius_r: '',
			border_radius_b: '',
			border_radius_l: '',
			border_radius_unit: 'px'
		},
		effects: {
			text_effect: 'none',
			animation_duration: '600',
			animation_delay: '0',
			animation_loop: '0',
			animation_timing: 'ease-in-out',
			effect_color: '#24afab',
			effect_color_2: '#6c5ce7',
			effect_strength: '12',
			effect_blur: '10'
		},
		linkseo: {
			custom_class: '',
			custom_id: '',
			pair_custom_css: '',
			link_enabled: '0',
			link_url: '',
			link_target: '_self',
			link_rel_nofollow: '0',
			link_rel_sponsored: '0',
			link_rel_noopener: '1',
			link_title: '',
			link_aria_label: '',
			link_class: '',
			link_id: '',
			link_color: '',
			link_hover_color: '',
			link_underline_hover: '1'
		}
	};

	function getScopeFromField($field) {
		const id = $field.attr('id') || '';
		const field = $field.data('style-field');

		if (!id || !field) {
			return '';
		}

		return id.replace(/^wpr-/, '').replace(new RegExp('-' + String(field).replaceAll('_', '-') + '$'), '');
	}

	function resetSection(scope, group) {
		const fields = sectionResetFields[group] || {};

		Object.keys(fields).forEach(function (field) {
			setStyleValue(scope, field, fields[field]);
		});

		updateAllConditionalControls(scope);
		updatePreview();
	}

	function syncLinkedBoxField($input) {
		const $box = $input.closest('.wpr-box-control');
		const $lock = $box.find('.wpr-link-box');

		if (!$lock.hasClass('is-linked')) {
			return;
		}

		const value = $input.val();
		$box.find('input[type="number"][data-box-field]').not($input).val(value);
	}


	$(document).on('input change keyup', '.wpr-style-editor [data-style-field]', function () {
		const $field = $(this);
		const scope = getScopeFromField($field);

		if ($field.data('box-field')) {
			syncLinkedBoxField($field);
		}

		if (scope) {
			updateAllConditionalControls(scope);
		}

		if (activePreview.scope === scope || (activePreview.type === 'global' && scope === 'global')) {
			updatePreview();
		}
	});


	$(document).on('click', '.wpr-color-swatch', function (event) {
		event.preventDefault();
		event.stopPropagation();

		const $row = $(this).closest('.wpr-color-row');
		const isOpen = $row.hasClass('is-open');

		$('.wpr-color-row.is-open').removeClass('is-open');

		if (!isOpen) {
			$row.addClass('is-open');
			$row.find('.wpr-color-hex').trigger('focus').trigger('select');
		}
	});

	$(document).on('click', '.wpr-modern-color-popover', function (event) {
		event.stopPropagation();
	});

	$(document).on('click', function () {
		$('.wpr-color-row.is-open').removeClass('is-open');
	});

	$(document).on('input change', '.wpr-color-native', function () {
		const $row = $(this).closest('.wpr-color-row');
		const $field = $row.find('.wpr-color-field');
		const value = normalizeHexColor($(this).val());

		$field.val(value).trigger('change');
	});

	$(document).on('input change', '.wpr-color-hex', function () {
		const $row = $(this).closest('.wpr-color-row');
		const $field = $row.find('.wpr-color-field');
		const value = normalizeHexColor($(this).val());

		if (value || String($(this).val()).trim() === '') {
			$field.val(value).trigger('change');
		}
	});

	$(document).on('click', '.wpr-color-clear', function (event) {
		event.preventDefault();
		const $row = $(this).closest('.wpr-color-row');
		$row.find('.wpr-color-field').val('').trigger('change');
	});

	$(document).on('click', '.wpr-color-copy', function (event) {
		event.preventDefault();
		const $row = $(this).closest('.wpr-color-row');
		const value = normalizeHexColor($row.find('.wpr-color-field').val());

		if (!value) {
			return;
		}

		if (navigator.clipboard && navigator.clipboard.writeText) {
			navigator.clipboard.writeText(value).catch(function () {});
		}
	});

	$(document).on('change', '.wpr-color-field', function () {
		updateModernColorUI($(this));

		if (activePreview.scope) {
			updatePreview();
		}
	});


	$(document).on('click', '.wpr-reset-section', function (event) {
		event.preventDefault();
		event.stopPropagation();

		const $panel = $(this).closest('.wpr-editor-panel');
		const group = $(this).data('reset-group') || $panel.data('reset-group');
		const $field = $panel.find('[data-style-field]').first();
		const scope = getScopeFromField($field);

		if (scope && group) {
			resetSection(scope, group);
		}
	});

	$(document).on('click', '.wpr-link-box', function (event) {
		event.preventDefault();

		const $button = $(this);
		const linked = !$button.hasClass('is-linked');

		$button.toggleClass('is-linked', linked);
		$button.attr('aria-pressed', linked ? 'true' : 'false');
		$button.text(linked ? '🔒' : '🔓');

		if (linked) {
			const $box = $button.closest('.wpr-box-control');
			const $first = $box.find('input[type="number"][data-box-field]').first();
			syncLinkedBoxField($first);
			updatePreview();
		}
	});

	$(document).on('click', '.wpr-support-close', function (event) {
		event.preventDefault();
		$(this).closest('.wpr-support-card').slideUp(160);
	});


	$(document).on('click', '.wpr-preview-theme', function () {
		const theme = $(this).data('preview-theme') || 'light';
		$('.wpr-preview-theme').removeClass('is-active');
		$(this).addClass('is-active');

		$('#wpr-live-preview, #wpr-dock-preview')
			.removeClass('is-light is-dark')
			.addClass(theme === 'dark' ? 'is-dark' : 'is-light');
	});

	$(document).on('click', '#wpr-preview-replay', function (event) {
		event.preventDefault();
		replayPreviewAnimation();
	});

	$(document).on('click', '#wpr-regenerate-css', function (event) {
		event.preventDefault();

		const $button = $(this);
		const $result = $('#wpr-css-regenerate-result');

		$result.removeClass('is-success is-error').hide().html('');

		$button.prop('disabled', true);

		$.ajax({
			url: wprAdmin.ajaxUrl,
			type: 'POST',
			dataType: 'json',
			data: {
				action: 'wpr_regenerate_css',
				nonce: wprAdmin.nonce
			},
			success: function (response) {

				if (!response.success) {
					$result
						.addClass('is-error')
						.html(response.data.message || 'Error')
						.fadeIn();

					return;
				}

				let html = response.data.message || 'CSS regenerated';

				if (response.data.url) {
					html += ' — <a href="' + response.data.url + '" target="_blank">hier ansehen</a>';
				}

				$result
					.addClass('is-success')
					.html(html)
					.fadeIn();
			},
			error: function (xhr) {

				let message = 'AJAX Error';

				if (
					xhr.responseJSON &&
					xhr.responseJSON.data &&
					xhr.responseJSON.data.message
				) {
					message = xhr.responseJSON.data.message;
				}

				$result
					.addClass('is-error')
					.html(message)
					.fadeIn();
			},
			complete: function () {
				$button.prop('disabled', false);
			}
		});
	});
	
	$(document).on('input', '#wpr-pair-search', applyPairFilters);

	$(document).on('click', '.wpr-browser-filter', function (event) {
		event.preventDefault();
		$('.wpr-browser-filter').removeClass('is-active');
		$(this).addClass('is-active');
		applyPairFilters();
	});

	$(document).on('click', '#wpr-scroll-add', function (event) {
		event.preventDefault();
		const $target = $('#wpr-form').closest('.wpr-card');
		if ($target.length) {
			$('html, body').animate({ scrollTop: $target.offset().top - 40 }, 220);
			$('#wpr-original-word').trigger('focus');
		}
	});


	$(document).on('change', '#wpr-admin-theme-toggle', function () {
		applyAdminTheme($(this).is(':checked') ? 'dark' : 'light');
	});

	$(document).on('click', '.wpr-favorite', function (event) {
		event.preventDefault();
		event.stopPropagation();
		const id = String($(this).data('id'));
		let favorites = getFavorites();
		if (favorites.includes(id)) {
			favorites = favorites.filter(function (value) { return value !== id; });
		} else {
			favorites.push(id);
		}
		saveFavorites(favorites);
		updateFavoriteButtons();
		updateBrowserCounts();
		applyPairFilters();
	});


	$(document).on('click', '#wpr-export-all-pairs', function (event) {
		event.preventDefault();
		const items = Object.values(itemsById || {}).filter(function (item, index, arr) {
			return item && item.id && arr.findIndex(function (check) { return String(check.id) === String(item.id); }) === index;
		});
		const payload = {
			exported_at: new Date().toISOString(),
			plugin: 'WordPair Replacer',
			version: wprAdmin.version || '',
			items: items
		};
		const blob = new Blob([JSON.stringify(payload, null, 2)], { type: 'application/json' });
		const url = URL.createObjectURL(blob);
		const a = document.createElement('a');
		a.href = url;
		a.download = 'wordpair-replacer-export.json';
		document.body.appendChild(a);
		a.click();
		document.body.removeChild(a);
		URL.revokeObjectURL(url);
	});

	$(document).on('click', '#wpr-import-json-button', function (event) {
		event.preventDefault();
		const raw = String($('#wpr-import-json').val() || '').trim();
		if (!raw) {
			showMessage('Keine Importdaten gefunden.', 'error');
			return;
		}

		let payload;
		try {
			payload = JSON.parse(raw);
		} catch (e) {
			showMessage('JSON konnte nicht gelesen werden.', 'error');
			return;
		}

		const items = Array.isArray(payload) ? payload : (Array.isArray(payload.items) ? payload.items : [payload]);
		let imported = 0;
		let failed = 0;

		function importNext(index) {
			if (index >= items.length) {
				showMessage(imported + ' Wortpaare importiert' + (failed ? ', ' + failed + ' Fehler' : '') + '.');
				loadPairs();
				return;
			}

			const item = Object.assign({}, items[index]);
			delete item.id;
			if (!item.original_word || !item.replacement_word) {
				failed++;
				importNext(index + 1);
				return;
			}

			request('wpr_save_pair', item)
				.done(function (response) {
					if (response && response.success) {
						imported++;
					} else {
						failed++;
					}
				})
				.fail(function () { failed++; })
				.always(function () { importNext(index + 1); });
		}

		importNext(0);
	});


	$(document).on('click', '.wpr-duplicate-pair', function (event) {
		event.preventDefault();
		const itemId = String($(this).data('id'));
		const item = itemsById[itemId];
		if (!item) {
			showMessage(wprAdmin.i18n.error, 'error');
			return;
		}
		const data = Object.assign({}, item);
		delete data.id;
		data.original_word = (item.original_word || 'Keyword') + ' Copy';
		data.replacement_word = item.replacement_word || 'Replacement';
		data.is_active = 0;
		data.use_custom_style = item.use_custom_style || 1;
		request('wpr_save_pair', data)
			.done(function (response) {
				if (!response.success) {
					showMessage(response.data && response.data.message ? response.data.message : wprAdmin.i18n.error, 'error');
					return;
				}
				showMessage('Wortpaar dupliziert.');
				loadPairs();
			})
			.fail(function () { showMessage(wprAdmin.i18n.error, 'error'); });
	});

	$(document).on('click', '.wpr-export-pair', function (event) {
		event.preventDefault();
		const itemId = String($(this).data('id'));
		const item = itemsById[itemId];
		if (!item) {
			showMessage(wprAdmin.i18n.error, 'error');
			return;
		}
		const blob = new Blob([JSON.stringify(item, null, 2)], { type: 'application/json' });
		const url = URL.createObjectURL(blob);
		const a = document.createElement('a');
		a.href = url;
		a.download = 'wordpair-' + itemId + '.json';
		document.body.appendChild(a);
		a.click();
		document.body.removeChild(a);
		URL.revokeObjectURL(url);
	});



	$(document).on('click', '.wpr-vertical-tab', function (event) {
		event.preventDefault();
		const target = $(this).data('target');
		const $editor = $(this).closest('.wpr-style-editor');
		$editor.find('.wpr-vertical-tab').removeClass('is-active');
		$(this).addClass('is-active');
		$editor.find('> .wpr-editor-panel').prop('open', false);
		$editor.find('> .wpr-editor-panel[data-tab-id="' + target + '"]').prop('open', true);
	});


	$(document).on('click', '.wpr-dock-body .wpr-editor-panel > summary', function () {
		const $panel = $(this).closest('.wpr-editor-panel');
		const willOpen = !$panel.prop('open');
		const $all = $panel.closest('.wpr-style-editor').find('.wpr-editor-panel');

		window.setTimeout(function () {
			if (willOpen) {
				$all.not($panel).prop('open', false);
				$panel.prop('open', true);
			} else if (!$all.filter('[open]').length) {
				$panel.prop('open', true);
			}
		}, 0);
	});

	function initInfoTips($context) {
		const isGerman = String(wprAdmin.pluginLanguage || '').toLowerCase().indexOf('de') === 0;
		const de = {
			'Font family': 'Legt die Schriftart für das ausgewählte Wortpaar fest. Beispiel: Georgia für einen klassischen Look.',
			'Schriftart': 'Legt die Schriftart für das ausgewählte Wortpaar fest. Beispiel: Georgia für einen klassischen Look.',
			'Font size': 'Bestimmt die sichtbare Textgröße. Beispiel: 18px für hervorgehobene Begriffe.',
			'Schriftgröße': 'Bestimmt die sichtbare Textgröße. Beispiel: 18px für hervorgehobene Begriffe.',
			'Line height': 'Steuert den Zeilenabstand. Sinnvoll, wenn ein Wort mit größerer Schrift oder Padding gesetzt wird.',
			'Zeilenhöhe': 'Steuert den Zeilenabstand. Sinnvoll, wenn ein Wort mit größerer Schrift oder Padding gesetzt wird.',
			'Font weight': 'Legt die Schriftstärke fest. Beispiel: 700 für fett, 900 für extra kräftig.',
			'Schriftstärke': 'Legt die Schriftstärke fest. Beispiel: 700 für fett, 900 für extra kräftig.',
			'Font style': 'Aktiviert einen Schriftschnitt wie kursiv. Nützlich für dezente Betonungen.',
			'Schriftstil': 'Aktiviert einen Schriftschnitt wie kursiv. Nützlich für dezente Betonungen.',
			'Text decoration': 'Fügt Unterstreichung, Überstreichung oder Durchstreichung hinzu.',
			'Textdekoration': 'Fügt Unterstreichung, Überstreichung oder Durchstreichung hinzu.',
			'Text transform': 'Ändert die Schreibweise, etwa GROSSBUCHSTABEN oder Capitalize.',
			'Texttransform': 'Ändert die Schreibweise, etwa GROSSBUCHSTABEN oder Capitalize.',
			'Letter spacing': 'Steuert den Abstand zwischen einzelnen Zeichen. Kleine Werte wirken oft hochwertiger.',
			'Zeichenabstand': 'Steuert den Abstand zwischen einzelnen Zeichen. Kleine Werte wirken oft hochwertiger.',
			'Word spacing': 'Steuert den Abstand zwischen Wörtern im ersetzten Text.',
			'Wortabstand': 'Steuert den Abstand zwischen Wörtern im ersetzten Text.',
			'Line wrapping': 'Legt fest, ob das ersetzte Wort umbrechen darf oder in einer Zeile bleiben soll.',
			'Umbruch': 'Legt fest, ob das ersetzte Wort umbrechen darf oder in einer Zeile bleiben soll.',
			'Text color': 'Ändert die Farbe des Textes. Beispiel: Akzentfarbe für wichtige Keywords.',
			'Textfarbe': 'Ändert die Farbe des Textes. Beispiel: Akzentfarbe für wichtige Keywords.',
			'Background color': 'Setzt eine Hintergrundfarbe hinter das Wort. Ideal für Badge- oder Highlight-Effekte.',
			'Hintergrundfarbe': 'Setzt eine Hintergrundfarbe hinter das Wort. Ideal für Badge- oder Highlight-Effekte.',
			'Background gradient': 'Erstellt einen Verlauf als Hintergrund. Beispiel: dezenter Verlauf für Callout-Wörter.',
			'Hintergrund-Verlauf': 'Erstellt einen Verlauf als Hintergrund. Beispiel: dezenter Verlauf für Callout-Wörter.',
			'Text gradient': 'Füllt die Buchstaben mit einem Farbverlauf statt mit einer einzelnen Farbe.',
			'Text-Verlauf': 'Füllt die Buchstaben mit einem Farbverlauf statt mit einer einzelnen Farbe.',
			'Text Shadow': 'Fügt einen Schatten hinzu, um Tiefe, Glow oder besseren Kontrast zu erzeugen.',
			'Padding': 'Steuert den Innenabstand zwischen Text und Hintergrund oder Rahmen.',
			'Border width': 'Legt die Stärke des Rahmens fest. Nutze 0, wenn kein Rahmen erscheinen soll.',
			'Rahmenstärke': 'Legt die Stärke des Rahmens fest. Nutze 0, wenn kein Rahmen erscheinen soll.',
			'Border style': 'Legt fest, ob der Rahmen durchgezogen, gestrichelt oder gepunktet dargestellt wird.',
			'Rahmenstil': 'Legt fest, ob der Rahmen durchgezogen, gestrichelt oder gepunktet dargestellt wird.',
			'Border color': 'Bestimmt die Farbe des Rahmens.',
			'Rahmenfarbe': 'Bestimmt die Farbe des Rahmens.',
			'Border radius': 'Rundet die Ecken des Hintergrunds oder Rahmens ab.',
			'Link URL': 'Verlinkt das ersetzte Wort automatisch. Für SEO sind interne Links besonders nützlich.',
			'Link-Titel': 'Optionales title-Attribut für zusätzliche Link-Informationen.',
			'ARIA Label': 'Barrierefreier Linktext für Screenreader und bessere Zugänglichkeit.',
			'Custom CSS class': 'Zusätzliche CSS-Klasse für eigenes Styling, Tracking oder JavaScript.',
			'Eigene CSS-Klasse': 'Zusätzliche CSS-Klasse für eigenes Styling, Tracking oder JavaScript.',
			'Custom CSS ID': 'Optionale eindeutige ID für gezielte CSS- oder JavaScript-Ansteuerung.',
			'Eigene CSS-ID': 'Optionale eindeutige ID für gezielte CSS- oder JavaScript-Ansteuerung.',
			'Öffnen': 'Legt fest, ob der Link im gleichen Tab oder in einem neuen Fenster geöffnet wird. Für externe Links ist ein neues Fenster oft sinnvoll.',
			'Interne Seite suchen': 'Wählt eine vorhandene Seite, einen Beitrag oder öffentlichen Inhalt aus und übernimmt dessen URL automatisch.',
			'nofollow': 'Setzt rel="nofollow". Suchmaschinen sollen diesem Link dann in der Regel nicht als Empfehlung folgen.',
			'sponsored': 'Kennzeichnet bezahlte oder gesponserte Links mit rel="sponsored".',
			'noopener': 'Erhöht die Sicherheit bei Links in neuen Fenstern und sollte bei target="_blank" aktiv bleiben.',
			'Linkfarbe': 'Setzt eine eigene Farbe für den Linkzustand dieses Wortpaares.',
			'Hover-Farbe': 'Setzt die Farbe, wenn Besucher mit der Maus über den Link fahren.',
			'Eigenes CSS': 'Eigene CSS-Deklarationen nur für dieses Wortpaar. Beispiel: text-shadow:0 0 12px rgba(255,51,102,.35);',
			'Effekt': 'Wählt die Animation oder den visuellen Texteffekt für dieses Wortpaar.',
			'Dauer in ms': 'Legt fest, wie lange ein Animationsdurchlauf dauert. Höhere Werte wirken ruhiger.',
			'Verzögerung in ms': 'Startverzögerung der Animation in Millisekunden.',
			'Timing': 'Steuert die Bewegungskurve der Animation, zum Beispiel weich ein- und auslaufend.',
			'Effektfarbe': 'Primäre Farbe für Effekte wie Glow, Shimmer oder Glitch.',
			'Zweite Effektfarbe': 'Sekundäre Farbe für mehrfarbige Effekte wie Gradient oder Glitch.',
			'Effektstärke in px': 'Steuert die Intensität einiger Effekte, zum Beispiel Textkontur oder 3D-Tiefe.',
			'Glow/Blur in px': 'Steuert die Unschärfe beziehungsweise den Leuchteffekt.'

		};
		const en = {
			'Font family': 'Sets the font family for the selected word pair. Example: Georgia for a classic look.',
			'Schriftart': 'Sets the font family for the selected word pair. Example: Georgia for a classic look.',
			'Font size': 'Controls the visible text size. Example: 18px for emphasized keywords.',
			'Schriftgröße': 'Controls the visible text size. Example: 18px for emphasized keywords.',
			'Line height': 'Controls vertical line spacing. Useful when using larger text or padding.',
			'Zeilenhöhe': 'Controls vertical line spacing. Useful when using larger text or padding.',
			'Font weight': 'Controls text weight. Example: 700 for bold, 900 for extra bold.',
			'Schriftstärke': 'Controls text weight. Example: 700 for bold, 900 for extra bold.',
			'Font style': 'Applies a font style such as italic. Useful for subtle emphasis.',
			'Schriftstil': 'Applies a font style such as italic. Useful for subtle emphasis.',
			'Text decoration': 'Adds underline, overline or line-through decoration.',
			'Textdekoration': 'Adds underline, overline or line-through decoration.',
			'Text transform': 'Changes text casing, for example uppercase or capitalize.',
			'Texttransform': 'Changes text casing, for example uppercase or capitalize.',
			'Letter spacing': 'Controls spacing between individual letters. Small values often look more premium.',
			'Zeichenabstand': 'Controls spacing between individual letters. Small values often look more premium.',
			'Word spacing': 'Controls spacing between words inside the replacement text.',
			'Wortabstand': 'Controls spacing between words inside the replacement text.',
			'Line wrapping': 'Controls whether the replacement may wrap or should stay on one line.',
			'Umbruch': 'Controls whether the replacement may wrap or should stay on one line.',
			'Text color': 'Changes the text color. Example: use an accent color for important keywords.',
			'Textfarbe': 'Changes the text color. Example: use an accent color for important keywords.',
			'Background color': 'Adds a background color behind the word. Great for badges or highlights.',
			'Hintergrundfarbe': 'Adds a background color behind the word. Great for badges or highlights.',
			'Background gradient': 'Creates a gradient background behind the text.',
			'Hintergrund-Verlauf': 'Creates a gradient background behind the text.',
			'Text gradient': 'Fills the letters with a gradient instead of a single text color.',
			'Text-Verlauf': 'Fills the letters with a gradient instead of a single text color.',
			'Text Shadow': 'Adds a shadow to create depth, glow or stronger contrast.',
			'Padding': 'Controls inner spacing between the text and its background or border.',
			'Border width': 'Controls border thickness. Use 0 if no border should be visible.',
			'Rahmenstärke': 'Controls border thickness. Use 0 if no border should be visible.',
			'Border style': 'Controls whether the border is solid, dashed or dotted.',
			'Rahmenstil': 'Controls whether the border is solid, dashed or dotted.',
			'Border color': 'Controls the border color.',
			'Rahmenfarbe': 'Controls the border color.',
			'Border radius': 'Rounds the corners of the background or border.',
			'Link URL': 'Automatically links the replaced word. Internal links are especially useful for SEO.',
			'Link-Titel': 'Optional title attribute with additional link information.',
			'ARIA Label': 'Accessible link label for screen readers and better usability.',
			'Custom CSS class': 'Additional CSS class for custom styling, tracking or JavaScript.',
			'Eigene CSS-Klasse': 'Additional CSS class for custom styling, tracking or JavaScript.',
			'Custom CSS ID': 'Optional unique ID for precise CSS or JavaScript targeting.',
			'Eigene CSS-ID': 'Optional unique ID for precise CSS or JavaScript targeting.',
			'Öffnen': 'Controls whether the link opens in the same tab or a new window. New windows are often useful for external links.',
			'Interne Seite suchen': 'Selects an existing page, post or public content item and automatically uses its URL.',
			'nofollow': 'Adds rel="nofollow". Search engines should generally not treat this link as an endorsement.',
			'sponsored': 'Marks paid or sponsored links with rel="sponsored".',
			'noopener': 'Improves security for links that open in new windows and should stay enabled for target="_blank".',
			'Linkfarbe': 'Sets a custom link color for this word pair.',
			'Hover-Farbe': 'Sets the color when visitors hover the link.',
			'Eigenes CSS': 'Custom CSS declarations scoped to this word pair. Example: text-shadow:0 0 12px rgba(255,51,102,.35);',
			'Effekt': 'Selects the animation or visual text effect for this word pair.',
			'Dauer in ms': 'Controls how long one animation cycle takes. Higher values feel calmer.',
			'Verzögerung in ms': 'Sets the animation start delay in milliseconds.',
			'Timing': 'Controls the animation easing curve, for example smooth ease-in-out.',
			'Effektfarbe': 'Primary color for effects like glow, shimmer or glitch.',
			'Zweite Effektfarbe': 'Secondary color for multi-color effects like gradients or glitch.',
			'Effektstärke in px': 'Controls the intensity of some effects, such as stroke or 3D depth.',
			'Glow/Blur in px': 'Controls the blur or glow intensity.'

		};
		const tips = isGerman ? de : en;

		$context.find('.wpr-control > span, .wpr-color-row-label strong, .wpr-range-label > span, .wpr-box-head > strong, .wpr-option-box-head label span').each(function () {
			const $label = $(this);
			if ($label.find('.wpr-info-tip').length) {
				return;
			}
			const labelText = $.trim($label.clone().children().remove().end().text());
			const message = tips[labelText];
			if (!message) {
				return;
			}
			$label.append('<span class="wpr-info-tip" tabindex="0" aria-label="' + esc(message) + '" data-tooltip="' + esc(message) + '">i</span>');
		});
	}


	$(document).on('change', '.wpr-internal-link-select', function () {
		const url = $(this).val();
		if (!url) {
			return;
		}
		const $field = $(this).closest('.wpr-editor-panel-body').find('[data-style-field="link_url"]').first();
		$field.val(url).trigger('input').trigger('change');
	});


	$(document).on('submit', '#wpr-support-ticket-form', function (event) {
		event.preventDefault();

		const $form = $(this);
		const $button = $form.find('.wpr-support-submit');
		const $result = $('#wpr-support-ticket-result');
		const originalText = $button.text();

		$result.removeClass('is-success is-error').hide().html('');
		$button.prop('disabled', true).text(i18n.supportSending || 'Sending support request...');

		request('wpr_submit_support_ticket', {
			name: $('#wpr-support-name').val(),
			email: $('#wpr-support-email').val(),
			website: $('#wpr-support-website').val(),
			type: $('#wpr-support-type').val(),
			priority: $('#wpr-support-priority').val(),
			message: $('#wpr-support-message').val(),
			include_diagnostics: $('#wpr-support-include-diagnostics').is(':checked') ? 1 : 0,
			company: $('#wpr-support-company').val(),
			client_timezone: (Intl.DateTimeFormat().resolvedOptions().timeZone || ''),
			client_local_time: new Date().toLocaleString()
		})
			.done(function (response) {
				if (!response.success) {
					$result.addClass('is-error').html(response.data && response.data.message ? response.data.message : wprAdmin.i18n.error).fadeIn();
					return;
				}

				const reference = response.data && response.data.reference ? '<br><strong>' + esc(response.data.reference) + '</strong>' : '';
				$result.addClass('is-success').html((response.data.message || i18n.supportSent || 'Support request sent.') + reference).fadeIn();
				if (response.data && response.data.history) {
					$('#wpr-local-ticket-history').html(response.data.history);
				}
				$form[0].reset();
				$('#wpr-support-website').val(wprAdmin.frontendUrl || '');
				$('#wpr-support-include-diagnostics').prop('checked', false);
			})
			.fail(function (xhr) {
				const msg = xhr.responseJSON && xhr.responseJSON.data && xhr.responseJSON.data.message ? xhr.responseJSON.data.message : wprAdmin.i18n.error;
				const reference = xhr.responseJSON && xhr.responseJSON.data && xhr.responseJSON.data.reference ? '<br><strong>' + esc(xhr.responseJSON.data.reference) + '</strong>' : '';
				$result.addClass('is-error').html(esc(msg) + reference).fadeIn();
			})
			.always(function () {
				$button.prop('disabled', false).text(originalText);
			});
	});


	$(document).on('click', '[data-ticket-filter]', function (event) {
		event.preventDefault();

		const filter = $(this).data('ticket-filter') || 'open';
		$('.wpr-ticket-filter-pills button').removeClass('is-active');
		$(this).addClass('is-active');

		$('.wpr-ticket-item').each(function () {
			const status = $(this).data('ticket-status') || 'open';
			$(this).toggle(filter === 'all' || filter === status);
		});
	});

	$(document).on('click', '.wpr-copy-ticket-id', function (event) {
		event.preventDefault();
		const ticketId = $(this).data('ticket-id') || '';
		if (!ticketId) {
			return;
		}
		if (window.navigator && window.navigator.clipboard) {
			window.navigator.clipboard.writeText(ticketId);
		}
		$(this).text('Copied');
		const button = this;
		window.setTimeout(function () {
			$(button).text('Copy Ticket ID');
		}, 1400);
	});

	$(document).on('click', '.wpr-ticket-status-toggle', function (event) {
		event.preventDefault();

		const $button = $(this);
		const reference = $button.data('ticket-id') || '';
		const status = $button.data('status') || 'open';
		const original = $button.text();

		$button.prop('disabled', true).text('Updating...');

		request('wpr_update_support_ticket_status', {
			reference: reference,
			status: status
		})
			.done(function (response) {
				if (!response.success) {
					showMessage(response.data && response.data.message ? response.data.message : wprAdmin.i18n.error, 'error');
					return;
				}
				if (response.data && response.data.html) {
					$('#wpr-local-ticket-history').html(response.data.html);
				}
			})
			.fail(function (xhr) {
				const msg = xhr.responseJSON && xhr.responseJSON.data && xhr.responseJSON.data.message ? xhr.responseJSON.data.message : wprAdmin.i18n.error;
				showMessage(msg, 'error');
			})
			.always(function () {
				$button.prop('disabled', false).text(original);
			});
	});


	$(document).on('click', '.wpr-apply-preset', function (event) {
		event.preventDefault();
		const $toolbar = $(this).closest('.wpr-preset-toolbar');
		const scope = $toolbar.data('scope') || activePreview.scope;
		const preset = presetById($toolbar.find('.wpr-preset-select').val());
		applyPresetToScope(preset, scope);
	});

	$(document).on('click', '.wpr-save-current-preset', function (event) {
		event.preventDefault();
		const scope = $(this).closest('.wpr-preset-toolbar').data('scope') || activePreview.scope;
		if (!scope) {
			showMessage(wprAdmin.i18n.error, 'error');
			return;
		}
		const name = window.prompt(i18n.presetName || 'Preset name');
		if (!name) {
			return;
		}
		const description = window.prompt('Description', '') || '';
		const previewKeyword = $('#wpr-dock-preview-word').text() || activePreview.replacement || 'WordPress';
		request('wpr_save_preset', {
			name: name,
			description: description,
			preview_keyword: previewKeyword,
			preset_data: JSON.stringify({ style: collectStyleData(scope) })
		})
			.done(function (response) {
				if (!response.success) {
					showMessage(response.data && response.data.message ? response.data.message : wprAdmin.i18n.error, 'error');
					return;
				}
				allPresets = response.data && response.data.presets ? response.data.presets : allPresets;
				renderPresetLibrary();
				renderPresetSelect($('.wpr-preset-select'));
				showMessage(response.data.message || i18n.presetSaved || 'Preset saved.');
			})
			.fail(function (xhr) {
				const msg = xhr.responseJSON && xhr.responseJSON.data && xhr.responseJSON.data.message ? xhr.responseJSON.data.message : wprAdmin.i18n.error;
				showMessage(msg, 'error');
			});
	});

	$(document).on('click', '.wpr-export-preset', function (event) {
		event.preventDefault();
		const preset = presetById($(this).data('id'));
		if (!preset) {
			return;
		}
		const payload = {
			plugin: 'WordPair Replacer',
			format: 'wprpreset',
			version: wprAdmin.version || '2.x',
			preset: {
				name: preset.name,
				description: preset.description,
				preview_keyword: preset.preview_keyword,
				style: preset.style
			}
		};
		const blob = new Blob([JSON.stringify(payload, null, 2)], { type: 'application/json' });
		const url = URL.createObjectURL(blob);
		const a = document.createElement('a');
		a.href = url;
		a.download = String(preset.slug || preset.name || 'preset').toLowerCase().replace(/[^a-z0-9]+/g, '-') + '.wprpreset';
		document.body.appendChild(a);
		a.click();
		document.body.removeChild(a);
		URL.revokeObjectURL(url);
	});

	$(document).on('click', '#wpr-import-preset-button', function (event) {
		event.preventDefault();
		const json = $('#wpr-preset-import-json').val();
		const $result = $('#wpr-preset-import-result');
		$result.removeClass('is-success is-error').hide().html('');
		request('wpr_import_preset', { preset_json: json })
			.done(function (response) {
				if (!response.success) {
					$result.addClass('is-error').text(response.data && response.data.message ? response.data.message : wprAdmin.i18n.error).fadeIn();
					return;
				}
				allPresets = response.data && response.data.presets ? response.data.presets : allPresets;
				renderPresetLibrary();
				renderPresetSelect($('.wpr-preset-select'));
				$('#wpr-preset-import-json').val('');
				$result.addClass('is-success').text(response.data.message || 'Preset imported.').fadeIn();
			})
			.fail(function (xhr) {
				const msg = xhr.responseJSON && xhr.responseJSON.data && xhr.responseJSON.data.message ? xhr.responseJSON.data.message : wprAdmin.i18n.error;
				$result.addClass('is-error').text(msg).fadeIn();
			});
	});

	$(document).on('click', '.wpr-delete-preset', function (event) {
		event.preventDefault();
		const id = $(this).data('id');
		if (!window.confirm('Delete this preset?')) {
			return;
		}
		request('wpr_delete_preset', { id: id })
			.done(function (response) {
				if (!response.success) {
					showMessage(response.data && response.data.message ? response.data.message : wprAdmin.i18n.error, 'error');
					return;
				}
				allPresets = response.data && response.data.presets ? response.data.presets : allPresets;
				renderPresetLibrary();
				renderPresetSelect($('.wpr-preset-select'));
				showMessage(response.data.message || 'Preset deleted.');
			});
	});

	$(document).ready(function () {
		applyAdminTheme(getSavedTheme());
		initColorPickers($(document));
		initInfoTips($(document));
		$('input[type="range"]').each(function () {
			updateRangeOutput(this);
		});
		filterFontOptions();
		syncSecurityProviderSwitches();
		updateAllConditionalControls('global');
		updatePreview();
		loadPairs();
		loadPresets();
	});
})(jQuery);

/* 2.2.9: Persist Settings accordion state without affecting primary Settings block. */
(function ($) {
	'use strict';

	const storageKey = 'wpr-settings-accordion-state';

	function readState() {
		try {
			return JSON.parse(window.localStorage.getItem(storageKey) || '{}') || {};
		} catch (error) {
			return {};
		}
	}

	function writeState(state) {
		try {
			window.localStorage.setItem(storageKey, JSON.stringify(state));
		} catch (error) {
			// localStorage may be disabled; accordions still work normally.
		}
	}

	function accordionId(element) {
		if ($(element).hasClass('wpr-security-monitor-settings')) {
			return 'security';
		}
		if ($(element).hasClass('wpr-settings-css-card')) {
			return 'css';
		}
		return '';
	}

	$(function () {
		const state = readState();
		$('.wpr-settings-accordion').each(function () {
			const id = accordionId(this);
			if (!id || typeof state[id] === 'undefined') {
				return;
			}
			this.open = !!state[id];
		});
	});

	$(document).on('toggle', '.wpr-settings-accordion', function () {
		const id = accordionId(this);
		if (!id) {
			return;
		}
		const state = readState();
		state[id] = this.open;
		writeState(state);
	});
})(jQuery);
