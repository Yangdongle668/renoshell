<?php
/**
 * Renobattery — Taxonomy Filter widget
 *
 * Pill-style ajax filter for archive pages. Works with product_cat + application_cat.
 *
 * @package Renobattery
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Renobattery_Widget_Taxonomy_Filter extends Renobattery_Base_Widget {

	public function get_name() : string {
		return 'renobattery-taxonomy-filter';
	}

	public function get_title() : string {
		return __( 'RB Taxonomy Filter', 'renobattery' );
	}

	public function get_icon() : string {
		return 'eicon-filter';
	}

	public function get_script_depends() : array {
		return [ 'rb-filter' ];
	}

	protected function register_controls() : void {
		$this->start_controls_section(
			'rb_content',
			[ 'label' => __( 'Filter', 'renobattery' ) ]
		);

		$this->add_control( 'taxonomies', [
			'label'       => __( 'Taxonomies', 'renobattery' ),
			'type'        => \Elementor\Controls_Manager::SELECT2,
			'multiple'    => true,
			'default'     => [ 'product_cat' ],
			'options'     => $this->get_taxonomy_options(),
		] );

		$this->add_control( 'show_count', [
			'label'        => __( 'Show post count', 'renobattery' ),
			'type'         => \Elementor\Controls_Manager::SWITCHER,
			'return_value' => 'yes',
			'default'      => 'yes',
		] );

		$this->add_control( 'mode', [
			'label'   => __( 'Mode', 'renobattery' ),
			'type'    => \Elementor\Controls_Manager::SELECT,
			'default' => 'ajax',
			'options' => [ 'ajax' => 'AJAX (live)', 'link' => 'Full page reload' ],
		] );

		$this->add_control( 'show_sort', [
			'label'        => __( 'Show sort dropdown', 'renobattery' ),
			'type'         => \Elementor\Controls_Manager::SWITCHER,
			'return_value' => 'yes',
			'default'      => 'yes',
		] );

		$this->end_controls_section();

		$this->start_controls_section(
			'rb_style',
			[ 'label' => __( 'Pill style', 'renobattery' ), 'tab' => \Elementor\Controls_Manager::TAB_STYLE ]
		);

		$this->add_control( 'pill_active_bg', [
			'label'     => __( 'Active BG', 'renobattery' ),
			'type'      => \Elementor\Controls_Manager::COLOR,
			'default'   => '#0A0A0A',
			'selectors' => [ '{{WRAPPER}} .rb-pill.is-active' => 'background:{{VALUE}};' ],
		] );
		$this->add_control( 'pill_active_color', [
			'label'     => __( 'Active text', 'renobattery' ),
			'type'      => \Elementor\Controls_Manager::COLOR,
			'default'   => '#FFFFFF',
			'selectors' => [ '{{WRAPPER}} .rb-pill.is-active' => 'color:{{VALUE}};' ],
		] );
		$this->add_control( 'pill_inactive_bg', [
			'label'     => __( 'Inactive BG', 'renobattery' ),
			'type'      => \Elementor\Controls_Manager::COLOR,
			'default'   => '#F4F4F5',
			'selectors' => [ '{{WRAPPER}} .rb-pill' => 'background:{{VALUE}};' ],
		] );
		$this->add_control( 'pill_inactive_color', [
			'label'     => __( 'Inactive text', 'renobattery' ),
			'type'      => \Elementor\Controls_Manager::COLOR,
			'default'   => '#52525B',
			'selectors' => [ '{{WRAPPER}} .rb-pill' => 'color:{{VALUE}};' ],
		] );

		$this->end_controls_section();
	}

	private function get_taxonomy_options() : array {
		$taxes = get_taxonomies( [ 'public' => true ], 'objects' );
		$out   = [];
		foreach ( $taxes as $slug => $obj ) {
			$out[ $slug ] = $obj->labels->singular_name;
		}
		return $out;
	}

	protected function render() : void {
		$settings   = $this->get_settings_for_display();
		$taxonomies = (array) ( $settings['taxonomies'] ?? [ 'product_cat' ] );
		$show_count = ( 'yes' === ( $settings['show_count'] ?? 'yes' ) );
		$mode       = $settings['mode'] ?? 'ajax';
		$show_sort  = ( 'yes' === ( $settings['show_sort'] ?? 'yes' ) );
		$current    = is_tax() ? get_queried_object_id() : 0;
		?>
		<div class="rb-filterbar" data-rb-filter data-rb-mode="<?php echo esc_attr( $mode ); ?>">
			<?php foreach ( $taxonomies as $tax ) :
				$terms = get_terms( [ 'taxonomy' => $tax, 'hide_empty' => true ] );
				if ( is_wp_error( $terms ) || empty( $terms ) ) { continue; }
				$tax_obj = get_taxonomy( $tax );
				?>
				<div class="rb-filterbar__group" data-rb-tax="<?php echo esc_attr( $tax ); ?>">
					<span class="rb-eyebrow rb-filterbar__label"><?php echo esc_html( $tax_obj->labels->name ?? $tax ); ?></span>
					<div class="rb-filterbar__pills">
						<button class="rb-pill<?php echo $current === 0 ? ' is-active' : ''; ?>" data-rb-term="all" type="button">
							<?php esc_html_e( 'All', 'renobattery' ); ?>
						</button>
						<?php foreach ( $terms as $term ) :
							$active = $current === (int) $term->term_id ? ' is-active' : '';
							$href   = get_term_link( $term );
							?>
							<?php if ( 'link' === $mode && ! is_wp_error( $href ) ) : ?>
								<a class="rb-pill<?php echo esc_attr( $active ); ?>" href="<?php echo esc_url( $href ); ?>" data-rb-term="<?php echo esc_attr( $term->slug ); ?>">
									<?php echo esc_html( $term->name ); ?>
									<?php if ( $show_count ) : ?><span class="rb-pill__count"><?php echo (int) $term->count; ?></span><?php endif; ?>
								</a>
							<?php else : ?>
								<button class="rb-pill<?php echo esc_attr( $active ); ?>" type="button" data-rb-term="<?php echo esc_attr( $term->slug ); ?>">
									<?php echo esc_html( $term->name ); ?>
									<?php if ( $show_count ) : ?><span class="rb-pill__count"><?php echo (int) $term->count; ?></span><?php endif; ?>
								</button>
							<?php endif; ?>
						<?php endforeach; ?>
					</div>
				</div>
			<?php endforeach; ?>

			<?php if ( $show_sort ) : ?>
				<div class="rb-filterbar__sort">
					<label for="rb-sort" class="rb-eyebrow"><?php esc_html_e( 'Sort', 'renobattery' ); ?></label>
					<select id="rb-sort" data-rb-sort>
						<option value="date-desc"><?php esc_html_e( 'Newest', 'renobattery' ); ?></option>
						<option value="capacity-desc"><?php esc_html_e( 'Highest capacity', 'renobattery' ); ?></option>
						<option value="title-asc"><?php esc_html_e( 'A → Z', 'renobattery' ); ?></option>
					</select>
				</div>
			<?php endif; ?>
		</div>
		<?php
	}
}
