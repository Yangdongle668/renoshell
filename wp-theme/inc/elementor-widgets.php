<?php
/**
 * Renobattery — Elementor custom widgets registry
 *
 * @package Renobattery
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class Renobattery_Elementor {

	const WIDGET_CATEGORY_SLUG = 'renobattery';

	const WIDGETS = [
		'logo-marquee'     => 'Renobattery_Widget_Logo_Marquee',
		'spec-table'       => 'Renobattery_Widget_Spec_Table',
		'taxonomy-filter'  => 'Renobattery_Widget_Taxonomy_Filter',
		'download-card'    => 'Renobattery_Widget_Download_Card',
		'mega-menu'        => 'Renobattery_Widget_Mega_Menu',
	];

	public static function init() : void {
		// NOTE: use 'elementor/elements/categories_registered' — it is the
		// canonical hook that passes the Elements_Manager. The older
		// 'elementor/init' hook has NO arguments; hooking a typed callback
		// to it causes a PHP 8 TypeError ("string given").
		add_action( 'elementor/elements/categories_registered', [ __CLASS__, 'register_category' ] );
		add_action( 'elementor/widgets/register',               [ __CLASS__, 'register_widgets' ] );
		add_action( 'elementor/editor/after_enqueue_scripts',   [ __CLASS__, 'enqueue_editor_assets' ] );
	}

	public static function register_category( \Elementor\Elements_Manager $elements_manager ) : void {
		$elements_manager->add_category(
			self::WIDGET_CATEGORY_SLUG,
			[
				'title' => __( 'Renobattery', 'renobattery' ),
				'icon'  => 'eicon-lightning',
			]
		);
	}

	public static function register_widgets( \Elementor\Widgets_Manager $widgets_manager ) : void {
		$base = get_stylesheet_directory() . '/widgets/';

		require_once $base . 'class-base-widget.php';

		foreach ( self::WIDGETS as $slug => $class ) {
			$file = $base . 'class-' . $slug . '.php';
			if ( file_exists( $file ) ) {
				require_once $file;
				if ( class_exists( $class ) ) {
					$widgets_manager->register( new $class() );
				}
			}
		}
	}

	public static function enqueue_editor_assets() : void {
		wp_enqueue_style( 'rb-editor', get_stylesheet_directory_uri() . '/assets/css/editor.css', [], wp_get_theme()->get( 'Version' ) );
	}
}

Renobattery_Elementor::init();
