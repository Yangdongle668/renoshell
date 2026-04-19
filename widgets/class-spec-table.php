<?php
/**
 * Renobattery — Spec Table widget
 *
 * Renders a two-column label/value spec table.
 * Data source: ACF repeater (spec_table) on the current post, or manual rows.
 *
 * @package Renobattery
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Renobattery_Widget_Spec_Table extends Renobattery_Base_Widget {

	public function get_name() : string {
		return 'renobattery-spec-table';
	}

	public function get_title() : string {
		return __( 'RB Spec Table', 'renobattery' );
	}

	public function get_icon() : string {
		return 'eicon-table';
	}

	protected function register_controls() : void {
		$this->start_controls_section(
			'rb_content',
			[ 'label' => __( 'Data', 'renobattery' ) ]
		);

		$this->add_control( 'source', [
			'label'   => __( 'Source', 'renobattery' ),
			'type'    => \Elementor\Controls_Manager::SELECT,
			'default' => 'acf:spec_table',
			'options' => [
				'acf:spec_table' => 'ACF: spec_table (current post)',
				'manual'         => 'Manual rows',
			],
		] );

		$repeater = new \Elementor\Repeater();
		$repeater->add_control( 'spec_label', [
			'label' => __( 'Label', 'renobattery' ),
			'type'  => \Elementor\Controls_Manager::TEXT,
		] );
		$repeater->add_control( 'spec_value', [
			'label' => __( 'Value', 'renobattery' ),
			'type'  => \Elementor\Controls_Manager::TEXT,
		] );

		$this->add_control( 'rows', [
			'label'       => __( 'Rows', 'renobattery' ),
			'type'        => \Elementor\Controls_Manager::REPEATER,
			'fields'      => $repeater->get_controls(),
			'default'     => [],
			'title_field' => '{{{ spec_label }}}',
			'condition'   => [ 'source' => 'manual' ],
		] );

		$this->add_control( 'columns', [
			'label'   => __( 'Columns', 'renobattery' ),
			'type'    => \Elementor\Controls_Manager::SELECT,
			'default' => '2',
			'options' => [ '1' => '1', '2' => '2', '3' => '3' ],
		] );

		$this->end_controls_section();

		$this->start_controls_section(
			'rb_style',
			[ 'label' => __( 'Style', 'renobattery' ), 'tab' => \Elementor\Controls_Manager::TAB_STYLE ]
		);

		$this->add_control( 'striped', [
			'label'        => __( 'Striped rows', 'renobattery' ),
			'type'         => \Elementor\Controls_Manager::SWITCHER,
			'return_value' => 'yes',
			'default'      => 'yes',
		] );

		$this->add_control( 'label_color', [
			'label'     => __( 'Label color', 'renobattery' ),
			'type'      => \Elementor\Controls_Manager::COLOR,
			'default'   => '#71717A',
			'selectors' => [ '{{WRAPPER}} .rb-spec-table__label' => 'color: {{VALUE}};' ],
		] );

		$this->add_control( 'value_color', [
			'label'     => __( 'Value color', 'renobattery' ),
			'type'      => \Elementor\Controls_Manager::COLOR,
			'default'   => '#0A0A0A',
			'selectors' => [ '{{WRAPPER}} .rb-spec-table__value' => 'color: {{VALUE}};' ],
		] );

		$this->add_control( 'border_color', [
			'label'     => __( 'Border color', 'renobattery' ),
			'type'      => \Elementor\Controls_Manager::COLOR,
			'default'   => '#E5E5E7',
			'selectors' => [ '{{WRAPPER}} .rb-spec-table__row' => 'border-color: {{VALUE}};' ],
		] );

		$this->add_responsive_control( 'row_padding_y', [
			'label'     => __( 'Row padding Y (px)', 'renobattery' ),
			'type'      => \Elementor\Controls_Manager::NUMBER,
			'default'   => 20,
			'selectors' => [ '{{WRAPPER}} .rb-spec-table__row' => 'padding-top: {{VALUE}}px; padding-bottom: {{VALUE}}px;' ],
		] );

		$this->end_controls_section();
	}

	protected function render() : void {
		$settings = $this->get_settings_for_display();
		$rows     = $this->collect_rows( $settings );

		if ( empty( $rows ) ) {
			if ( \Elementor\Plugin::$instance->editor->is_edit_mode() ) {
				echo '<p class="rb-placeholder">' . esc_html__( 'No spec rows yet — add ACF rows or switch to Manual.', 'renobattery' ) . '</p>';
			}
			return;
		}

		$cols    = max( 1, min( 3, (int) ( $settings['columns'] ?? 2 ) ) );
		$striped = ( 'yes' === ( $settings['striped'] ?? 'yes' ) ) ? ' is-striped' : '';
		?>
		<dl class="rb-spec-table<?php echo esc_attr( $striped ); ?>" style="--rb-spec-cols: <?php echo (int) $cols; ?>;">
			<?php foreach ( $rows as $row ) :
				$label = $row['spec_label'] ?? '';
				$value = $row['spec_value'] ?? '';
				if ( '' === $label && '' === $value ) { continue; } ?>
				<div class="rb-spec-table__row">
					<dt class="rb-spec-table__label"><?php echo esc_html( $label ); ?></dt>
					<dd class="rb-spec-table__value"><?php echo wp_kses_post( $value ); ?></dd>
				</div>
			<?php endforeach; ?>
		</dl>
		<?php
	}

	private function collect_rows( array $settings ) : array {
		$source = $settings['source'] ?? 'acf:spec_table';

		if ( 'manual' === $source ) {
			return is_array( $settings['rows'] ?? null ) ? $settings['rows'] : [];
		}

		if ( 0 === strpos( $source, 'acf:' ) && function_exists( 'get_field' ) ) {
			$field = substr( $source, 4 );
			$acf   = get_field( $field );
			return is_array( $acf ) ? $acf : [];
		}

		return [];
	}
}
