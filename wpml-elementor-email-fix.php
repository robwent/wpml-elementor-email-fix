<?php
/**
 * Plugin Name: WPML Elementor Email Fix
 * Plugin URI:  https://github.com/developer/wpml-elementor-email-fix
 * Description: Prevents WPML from translating Elementor Pro form email notification fields, which corrupts shortcodes like [all-fields].
 * Version:     1.0.0
 * Author:      Developer
 * Author URI:  https://github.com/developer
 * License:     GPL-2.0-or-later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Requires PHP: 7.4
 * Requires at least: 5.8
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Remove Elementor Pro form email fields from WPML translation.
 *
 * WPML registers all Elementor widget fields as translatable via the
 * `wpml_elementor_widgets_to_translate` filter. This includes the email
 * notification fields in the Form widget, which contain shortcodes like
 * [all-fields] and [field id="..."].
 *
 * When WPML's "Translate Everything" or automatic translation processes
 * these fields, the shortcodes get translated into the target language
 * (e.g. [tous-les-champs] in French). Elementor does not recognise the
 * translated tags, so they render literally in notification emails instead
 * of outputting the submitted form data.
 *
 * This plugin removes those email fields from translation to prevent the
 * corruption. The fields contain technical values, not user-facing prose,
 * so translating them is almost never desirable.
 */
add_action( 'init', function () {
	// Only run when both WPML and Elementor Pro are active.
	if ( ! class_exists( 'SitePress' ) || ! class_exists( 'ElementorPro\Plugin' ) ) {
		return;
	}

	add_filter( 'wpml_elementor_widgets_to_translate', function ( $widgets ) {
		if ( ! isset( $widgets['form'] ) ) {
			return $widgets;
		}

		// Email fields that contain shortcodes and should never be translated.
		$exclude = [
			'email_subject',
			'email_from_name',
			'email_content',
			'email_subject_2',
			'email_content_2',
		];

		// Remove from top-level fields.
		if ( isset( $widgets['form']['fields'] ) ) {
			foreach ( $exclude as $field_key ) {
				unset( $widgets['form']['fields'][ $field_key ] );
			}
		}

		// Remove from integration-class fields (some WPML versions nest fields here).
		if ( isset( $widgets['form']['integration-class'] ) ) {
			$widgets['form']['integration-class'] = array_values(
				array_filter(
					$widgets['form']['integration-class'],
					function ( $entry ) use ( $exclude ) {
						if ( is_string( $entry ) ) {
							return true; // Class name reference — keep it.
						}
						if ( is_array( $entry ) && isset( $entry['field'] ) ) {
							return ! in_array( $entry['field'], $exclude, true );
						}
						return true;
					}
				)
			);
		}

		return $widgets;
	} );
} );
