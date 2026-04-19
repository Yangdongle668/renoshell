<?php
/**
 * Renobattery — Base abstract widget
 *
 * All custom widgets extend this to inherit shared controls
 * (eyebrow/title scaffolding, animation group, section wrapper).
 *
 * @package Renobattery
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

abstract class Renobattery_Base_Widget extends \Elementor\Widget_Base {

	public function get_categories() : array {
		return [ Renobattery_Elementor::WIDGET_CATEGORY_SLUG ];
	}

	public function get_icon() : string {
		return 'eicon-lightning';
	}

	public function get_keywords() : array {
		return [ 'renobattery', 'rb', 'battery' ];
	}

	public function get_style_depends() : array {
		return [ 'rb-components' ];
	}

	protected function add_section_animation_controls() : void {
		$this->start_controls_section(
			'rb_animation',
			[
				'label' => __( 'Animation', 'renobattery' ),
				'tab'   => \Elementor\Controls_Manager::TAB_STYLE,
			]
		);

		$this->add_control(
			'rb_reveal',
			[
				'label'        => __( 'Reveal on scroll', 'renobattery' ),
				'type'         => \Elementor\Controls_Manager::SWITCHER,
				'label_on'     => __( 'Yes', 'renobattery' ),
				'label_off'    => __( 'No', 'renobattery' ),
				'return_value' => 'yes',
				'default'      => 'yes',
			]
		);

		$this->add_control(
			'rb_reveal_delay',
			[
				'label'      => __( 'Delay (ms)', 'renobattery' ),
				'type'       => \Elementor\Controls_Manager::NUMBER,
				'default'    => 0,
				'min'        => 0,
				'max'        => 2000,
				'step'       => 60,
				'condition'  => [ 'rb_reveal' => 'yes' ],
			]
		);

		$this->end_controls_section();
	}

	protected function reveal_attrs( array $settings ) : string {
		if ( empty( $settings['rb_reveal'] ) || 'yes' !== $settings['rb_reveal'] ) {
			return '';
		}
		$delay = isset( $settings['rb_reveal_delay'] ) ? (int) $settings['rb_reveal_delay'] : 0;
		return sprintf(
			' class="rb-anim-reveal" data-rb-reveal style="transition-delay:%dms"',
			$delay
		);
	}
}
