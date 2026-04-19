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
	];

	public static function init() : void {
		add_action( 'elementor/init',                    [ __CLASS__, 'register_category' ] );
		add_action( 'elementor/widgets/register',        [ __CLASS__, 'register_widgets' ] );
		add_action( 'elementor/editor/after_enqueue_scripts', [ __CLASS__, 'enqueue_editor_assets' ] );
	}

	public static function register_category( \Elementor\Elements_Manager $elements_manager = null ) : void {
		$manager = $elements_manager ?: \Elementor\Plugin::instance()->elements_manager;
		$manager->add_category(
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
