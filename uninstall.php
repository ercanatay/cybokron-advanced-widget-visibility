<?php
/**
 * Uninstall script for Cybokron Advanced Widget Visibility
 *
 * @package CybokronAdvancedWidgetVisibility
 */

// If uninstall not called from WordPress, exit.
if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}

$cybawv_settings = get_option( 'cybawv_settings', [] );

if ( ! empty( $cybawv_settings['delete_data_on_uninstall'] ) ) {
	// Remove cybawv_visibility from all widget instances.
	$cybawv_sidebars = get_option( 'sidebars_widgets', [] );
	foreach ( $cybawv_sidebars as $cybawv_sidebar_id => $cybawv_widgets ) {
		if ( ! is_array( $cybawv_widgets ) ) {
			continue;
		}
		foreach ( $cybawv_widgets as $cybawv_widget_id ) {
			// widget_id format: widget-type-N
			if ( ! preg_match( '/^(.+)-(\d+)$/', $cybawv_widget_id, $cybawv_matches ) ) {
				continue;
			}
			$cybawv_option    = 'widget_' . $cybawv_matches[1];
			$cybawv_instances = get_option( $cybawv_option, [] );
			if ( is_array( $cybawv_instances ) && isset( $cybawv_instances[ (int) $cybawv_matches[2] ]['cybawv_visibility'] ) ) {
				unset( $cybawv_instances[ (int) $cybawv_matches[2] ]['cybawv_visibility'] );
				update_option( $cybawv_option, $cybawv_instances );
			}
		}
	}
}

// Always remove plugin settings on uninstall.
delete_option( 'cybawv_settings' );
