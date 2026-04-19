<?php
/**
 * Renobattery — Mega Menu Panel widget
 *
 * A full-width flyout panel triggered from a top-level nav item.
 * Renders an N-column grid of image tiles sourced from:
 *   - a taxonomy (each term becomes a tile), or
 *   - a manual list of tiles
 *
 * Hook-up:
 *   1. Place this widget inside the header template, outside the main nav.
 *   2. Give the widget a unique "trigger ID" (e.g. "mm-products").
 *   3. On the nav menu item that should open it, add CSS class
 *      `has-rb-megamenu` and anchor rel `#mm-products` (or a matching
 *      data attribute set in the menu item's anchor attributes).
 *   4. assets/js/megamenu.js listens for hover/focus and toggles .is-open.
 *
 * @package Renobattery
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Renobattery_Widget_Mega_Menu extends Renobattery_Base_Widget {

	public function get_name() : string {
		return 'renobattery-mega-menu';
	}

	public function get_title() : string {
		return __( 'RB Mega Menu Panel', 'renobattery' );
	}

	public function get_icon() : string {
		return 'eicon-mega-menu';
	}

	protected function register_controls() : void {
		$this->start_controls_section( 'rb_id', [ 'label' => __( 'Identity', 'renobattery' ) ] );

		$this->add_control( 'trigger_id', [
			'label'       => __( 'Trigger ID', 'renobattery' ),
			'description' => __( 'Set a matching ID on the nav item (e.g. mm-products).', 'renobattery' ),
			'type'        => \Elementor\Controls_Manager::TEXT,
			'default'     => 'mm-products',
		] );

		$this->add_control( 'heading', [
			'label'   => __( 'Panel heading (optional)', 'renobattery' ),
			'type'    => \Elementor\Controls_Manager::TEXT,
			'default' => '',
		] );

		$this->end_controls_section();

		$this->start_controls_section( 'rb_data', [ 'label' => __( 'Tiles', 'renobattery' ) ] );

		$this->add_control( 'source', [
			'label'   => __( 'Source', 'renobattery' ),
			'type'    => \Elementor\Controls_Manager::SELECT,
			'default' => 'taxonomy',
			'options' => [
				'taxonomy' => 'Taxonomy terms',
				'manual'   => 'Manual tiles',
			],
		] );

		$this->add_control( 'taxonomy', [
			'label'     => __( 'Taxonomy', 'renobattery' ),
			'type'      => \Elementor\Controls_Manager::SELECT,
			'default'   => 'product_cat',
			'options'   => $this->taxonomy_options(),
			'condition' => [ 'source' => 'taxonomy' ],
		] );

		$this->add_control( 'max_tiles', [
			'label'     => __( 'Max tiles', 'renobattery' ),
			'type'      => \Elementor\Controls_Manager::NUMBER,
			'default'   => 6,
			'min'       => 1,
			'max'       => 12,
			'condition' => [ 'source' => 'taxonomy' ],
		] );

		$repeater = new \Elementor\Repeater();
		$repeater->add_control( 'title',    [ 'label' => 'Title',    'type' => \Elementor\Controls_Manager::TEXT ] );
		$repeater->add_control( 'subtitle', [ 'label' => 'Subtitle', 'type' => \Elementor\Controls_Manager::TEXT ] );
		$repeater->add_control( 'image',    [ 'label' => 'Image',    'type' => \Elementor\Controls_Manager::MEDIA ] );
		$repeater->add_control( 'url',      [ 'label' => 'Link',     'type' => \Elementor\Controls_Manager::URL ] );

		$this->add_control( 'tiles', [
			'label'       => __( 'Manual tiles', 'renobattery' ),
			'type'        => \Elementor\Controls_Manager::REPEATER,
			'fields'      => $repeater->get_controls(),
			'default'     => [],
			'title_field' => '{{{ title }}}',
			'condition'   => [ 'source' => 'manual' ],
		] );

		$this->end_controls_section();

		$this->start_controls_section( 'rb_cta', [ 'label' => __( 'Footer CTA', 'renobattery' ) ] );

		$this->add_control( 'cta_text', [
			'label' => __( 'CTA text', 'renobattery' ),
			'type'  => \Elementor\Controls_Manager::TEXT,
			'default' => __( 'View all', 'renobattery' ),
		] );

		$this->add_control( 'cta_link', [
			'label'  => __( 'CTA link', 'renobattery' ),
			'type'   => \Elementor\Controls_Manager::URL,
			'default'=> [ 'url' => '/products/' ],
		] );

		$this->end_controls_section();

		$this->start_controls_section( 'rb_style', [
			'label' => __( 'Style', 'renobattery' ),
			'tab'   => \Elementor\Controls_Manager::TAB_STYLE,
		] );

		$this->add_control( 'columns', [
			'label'   => __( 'Columns', 'renobattery' ),
			'type'    => \Elementor\Controls_Manager::SELECT,
			'default' => '3',
			'options' => [ '2' => '2', '3' => '3', '4' => '4' ],
		] );

		$this->add_control( 'bg_color', [
			'label'     => __( 'Background', 'renobattery' ),
			'type'      => \Elementor\Controls_Manager::COLOR,
			'default'   => '#0A0A0A',
			'selectors' => [ '{{WRAPPER}} .rb-megamenu' => 'background: {{VALUE}};' ],
		] );

		$this->end_controls_section();
	}

	private function taxonomy_options() : array {
		$opts  = [];
		foreach ( get_taxonomies( [ 'public' => true ], 'objects' ) as $slug => $obj ) {
			$opts[ $slug ] = $obj->labels->singular_name;
		}
		return $opts;
	}

	protected function render() : void {
		$settings = $this->get_settings_for_display();
		$tiles    = $this->collect_tiles( $settings );
		if ( empty( $tiles ) ) {
			return;
		}

		$id      = sanitize_title( $settings['trigger_id'] ?? 'mm-panel' );
		$heading = $settings['heading'] ?? '';
		$cols    = (int) ( $settings['columns'] ?? 3 );
		$cta_t   = $settings['cta_text'] ?? '';
		$cta_u   = $settings['cta_link']['url'] ?? '';
		?>
		<div class="rb-megamenu" id="<?php echo esc_attr( $id ); ?>" data-rb-megamenu hidden>
			<div class="rb-megamenu__inner">
				<?php if ( $heading ) : ?>
					<p class="rb-eyebrow rb-megamenu__heading"><?php echo esc_html( $heading ); ?></p>
				<?php endif; ?>
				<div class="rb-megamenu__grid" style="--rb-mm-cols: <?php echo (int) $cols; ?>;">
					<?php foreach ( $tiles as $tile ) :
						$img = $tile['image'] ?? '';
						$href= $tile['url'] ?? '#'; ?>
						<a class="rb-megamenu__tile" href="<?php echo esc_url( $href ); ?>">
							<?php if ( $img ) : ?>
								<span class="rb-megamenu__thumb">
									<img src="<?php echo esc_url( $img ); ?>" alt="" loading="lazy" decoding="async">
								</span>
							<?php endif; ?>
							<span class="rb-megamenu__body">
								<span class="rb-megamenu__title"><?php echo esc_html( $tile['title'] ?? '' ); ?></span>
								<?php if ( ! empty( $tile['subtitle'] ) ) : ?>
									<span class="rb-megamenu__subtitle"><?php echo esc_html( $tile['subtitle'] ); ?></span>
								<?php endif; ?>
							</span>
						</a>
					<?php endforeach; ?>
				</div>
				<?php if ( $cta_t && $cta_u ) : ?>
					<a class="rb-megamenu__cta" href="<?php echo esc_url( $cta_u ); ?>">
						<?php echo esc_html( $cta_t ); ?>
						<span aria-hidden="true">→</span>
					</a>
				<?php endif; ?>
			</div>
		</div>
		<?php
	}

	private function collect_tiles( array $settings ) : array {
		if ( ( $settings['source'] ?? 'taxonomy' ) === 'manual' ) {
			$out = [];
			foreach ( (array) ( $settings['tiles'] ?? [] ) as $row ) {
				$out[] = [
					'title'    => $row['title'] ?? '',
					'subtitle' => $row['subtitle'] ?? '',
					'image'    => $row['image']['url'] ?? '',
					'url'      => $row['url']['url'] ?? '#',
				];
			}
			return $out;
		}

		$tax   = sanitize_key( $settings['taxonomy'] ?? 'product_cat' );
		$max   = max( 1, (int) ( $settings['max_tiles'] ?? 6 ) );
		$terms = get_terms( [ 'taxonomy' => $tax, 'hide_empty' => false, 'number' => $max ] );
		if ( is_wp_error( $terms ) || empty( $terms ) ) {
			return [];
		}

		$out = [];
		foreach ( $terms as $term ) {
			$image_id = (int) get_term_meta( $term->term_id, 'image_id', true );
			$img      = $image_id ? wp_get_attachment_image_url( $image_id, 'rb-thumb' ) : '';
			$out[] = [
				'title'    => $term->name,
				'subtitle' => $term->count ? sprintf( _n( '%d product', '%d products', $term->count, 'renobattery' ), $term->count ) : '',
				'image'    => $img,
				'url'      => get_term_link( $term ),
			];
		}
		return $out;
	}
}
