<?php
/**
 * Renobattery — Logo Marquee widget
 *
 * Infinite horizontal-scroll logo strip. CSS-only animation.
 *
 * @package Renobattery
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Renobattery_Widget_Logo_Marquee extends Renobattery_Base_Widget {

	public function get_name() : string {
		return 'renobattery-logo-marquee';
	}

	public function get_title() : string {
		return __( 'RB Logo Marquee', 'renobattery' );
	}

	public function get_icon() : string {
		return 'eicon-slider-push';
	}

	protected function register_controls() : void {
		$this->start_controls_section(
			'rb_content',
			[ 'label' => __( 'Logos', 'renobattery' ) ]
		);

		$repeater = new \Elementor\Repeater();
		$repeater->add_control( 'image', [
			'label' => __( 'Logo', 'renobattery' ),
			'type'  => \Elementor\Controls_Manager::MEDIA,
		] );
		$repeater->add_control( 'alt', [
			'label' => __( 'Alt text', 'renobattery' ),
			'type'  => \Elementor\Controls_Manager::TEXT,
		] );
		$repeater->add_control( 'url', [
			'label' => __( 'Link (optional)', 'renobattery' ),
			'type'  => \Elementor\Controls_Manager::URL,
		] );

		$this->add_control( 'logos', [
			'label'   => __( 'Logo list', 'renobattery' ),
			'type'    => \Elementor\Controls_Manager::REPEATER,
			'fields'  => $repeater->get_controls(),
			'default' => [],
			'title_field' => '{{{ alt }}}',
		] );

		$this->end_controls_section();

		$this->start_controls_section(
			'rb_style',
			[ 'label' => __( 'Style', 'renobattery' ), 'tab' => \Elementor\Controls_Manager::TAB_STYLE ]
		);

		$this->add_control( 'speed_s', [
			'label'   => __( 'Speed (seconds per loop)', 'renobattery' ),
			'type'    => \Elementor\Controls_Manager::NUMBER,
			'default' => 40,
			'min'     => 10,
			'max'     => 120,
		] );

		$this->add_control( 'direction', [
			'label'   => __( 'Direction', 'renobattery' ),
			'type'    => \Elementor\Controls_Manager::SELECT,
			'default' => 'left',
			'options' => [ 'left' => 'Left', 'right' => 'Right' ],
		] );

		$this->add_responsive_control( 'logo_height_px', [
			'label'   => __( 'Logo height (px)', 'renobattery' ),
			'type'    => \Elementor\Controls_Manager::NUMBER,
			'default' => 28,
			'selectors' => [ '{{WRAPPER}} .rb-marquee__track img' => 'height: {{VALUE}}px;' ],
		] );

		$this->add_control( 'grayscale', [
			'label'        => __( 'Grayscale', 'renobattery' ),
			'type'         => \Elementor\Controls_Manager::SWITCHER,
			'return_value' => 'yes',
			'default'      => 'yes',
			'selectors'    => [
				'{{WRAPPER}} .rb-marquee__track img' => 'filter: grayscale(100%); opacity: 0.6;',
				'{{WRAPPER}} .rb-marquee__track img:hover' => 'filter: none; opacity: 1;',
			],
		] );

		$this->add_control( 'gap_px', [
			'label'   => __( 'Gap between logos (px)', 'renobattery' ),
			'type'    => \Elementor\Controls_Manager::NUMBER,
			'default' => 48,
			'selectors' => [ '{{WRAPPER}} .rb-marquee__track' => 'gap: {{VALUE}}px;' ],
		] );

		$this->end_controls_section();
	}

	protected function render() : void {
		$settings = $this->get_settings_for_display();
		$logos    = $settings['logos'] ?? [];
		if ( empty( $logos ) ) {
			return;
		}

		$speed = (int) ( $settings['speed_s'] ?? 40 );
		$dir   = ( 'right' === ( $settings['direction'] ?? 'left' ) ) ? 'reverse' : 'normal';

		// Duplicate for seamless loop.
		$track = array_merge( $logos, $logos );
		?>
		<div class="rb-marquee" data-rb-marquee style="--rb-marquee-speed: <?php echo esc_attr( $speed ); ?>s; --rb-marquee-direction: <?php echo esc_attr( $dir ); ?>;">
			<div class="rb-marquee__track">
				<?php foreach ( $track as $item ) :
					$src = $item['image']['url'] ?? '';
					if ( ! $src ) { continue; }
					$alt  = $item['alt'] ?? '';
					$link = $item['url']['url'] ?? '';
					$img  = sprintf( '<img src="%s" alt="%s" loading="lazy" decoding="async">', esc_url( $src ), esc_attr( $alt ) );
					echo $link
						? sprintf( '<a class="rb-marquee__item" href="%s" rel="noopener">%s</a>', esc_url( $link ), $img )
						: sprintf( '<span class="rb-marquee__item">%s</span>', $img );
				endforeach; ?>
			</div>
		</div>
		<?php
	}
}
