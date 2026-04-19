<?php
/**
 * Renobattery — Download Card widget
 *
 * Single- or multi-file download card with icon + label + size badge.
 *
 * @package Renobattery
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Renobattery_Widget_Download_Card extends Renobattery_Base_Widget {

	public function get_name() : string {
		return 'renobattery-download-card';
	}

	public function get_title() : string {
		return __( 'RB Download Card', 'renobattery' );
	}

	public function get_icon() : string {
		return 'eicon-download-button';
	}

	protected function register_controls() : void {
		$this->start_controls_section(
			'rb_content',
			[ 'label' => __( 'Content', 'renobattery' ) ]
		);

		$this->add_control( 'icon', [
			'label'   => __( 'Icon', 'renobattery' ),
			'type'    => \Elementor\Controls_Manager::SELECT,
			'default' => 'file-pdf',
			'options' => [
				'file-pdf'     => 'PDF',
				'file-excel'   => 'Excel',
				'file-zip'     => 'ZIP',
				'certificate'  => 'Certificate',
				'image'        => 'Image',
			],
		] );

		$this->add_control( 'title', [
			'label'   => __( 'Title', 'renobattery' ),
			'type'    => \Elementor\Controls_Manager::TEXT,
			'default' => __( 'Datasheet', 'renobattery' ),
		] );

		$this->add_control( 'subtitle', [
			'label'   => __( 'Subtitle', 'renobattery' ),
			'type'    => \Elementor\Controls_Manager::TEXT,
			'default' => __( 'Technical specifications', 'renobattery' ),
		] );

		$this->add_control( 'source_type', [
			'label'   => __( 'Source', 'renobattery' ),
			'type'    => \Elementor\Controls_Manager::SELECT,
			'default' => 'acf_single',
			'options' => [
				'acf_single'   => 'ACF single file field',
				'acf_repeater' => 'ACF repeater (cert list)',
				'manual'       => 'Manual file upload',
			],
		] );

		$this->add_control( 'acf_field', [
			'label'   => __( 'ACF field name', 'renobattery' ),
			'type'    => \Elementor\Controls_Manager::TEXT,
			'default' => 'datasheet_pdf',
			'condition' => [ 'source_type' => [ 'acf_single', 'acf_repeater' ] ],
		] );

		$this->add_control( 'manual_file', [
			'label'     => __( 'File', 'renobattery' ),
			'type'      => \Elementor\Controls_Manager::MEDIA,
			'media_types' => [ 'application', 'image' ],
			'condition' => [ 'source_type' => 'manual' ],
		] );

		$this->end_controls_section();

		$this->start_controls_section(
			'rb_style',
			[ 'label' => __( 'Style', 'renobattery' ), 'tab' => \Elementor\Controls_Manager::TAB_STYLE ]
		);

		$this->add_control( 'bg_color', [
			'label'     => __( 'Background', 'renobattery' ),
			'type'      => \Elementor\Controls_Manager::COLOR,
			'default'   => '#FAFAFA',
			'selectors' => [ '{{WRAPPER}} .rb-download-card' => 'background:{{VALUE}};' ],
		] );

		$this->add_control( 'text_color', [
			'label'     => __( 'Text color', 'renobattery' ),
			'type'      => \Elementor\Controls_Manager::COLOR,
			'default'   => '#0A0A0A',
			'selectors' => [ '{{WRAPPER}} .rb-download-card' => 'color:{{VALUE}};' ],
		] );

		$this->end_controls_section();
	}

	protected function render() : void {
		$settings = $this->get_settings_for_display();
		$files    = $this->collect_files( $settings );

		if ( empty( $files ) ) {
			return;
		}

		$icon     = sanitize_key( $settings['icon'] ?? 'file-pdf' );
		$title    = $settings['title'] ?? '';
		$subtitle = $settings['subtitle'] ?? '';

		if ( count( $files ) === 1 ) {
			$f = $files[0];
			?>
			<a class="rb-download-card" href="<?php echo esc_url( $f['url'] ); ?>" target="_blank" rel="noopener" download>
				<span class="rb-download-card__icon" data-icon="<?php echo esc_attr( $icon ); ?>" aria-hidden="true"></span>
				<span class="rb-download-card__body">
					<span class="rb-download-card__title"><?php echo esc_html( $title ); ?></span>
					<?php if ( $subtitle ) : ?>
						<span class="rb-download-card__subtitle"><?php echo esc_html( $subtitle ); ?></span>
					<?php endif; ?>
				</span>
				<span class="rb-download-card__meta">
					<?php if ( ! empty( $f['size'] ) ) : ?><span class="rb-download-card__size"><?php echo esc_html( $f['size'] ); ?></span><?php endif; ?>
					<span class="rb-download-card__arrow" aria-hidden="true">↓</span>
				</span>
			</a>
			<?php
			return;
		}

		// Multi-file (cert list etc.)
		?>
		<div class="rb-download-card rb-download-card--list">
			<div class="rb-download-card__head">
				<span class="rb-download-card__icon" data-icon="<?php echo esc_attr( $icon ); ?>" aria-hidden="true"></span>
				<span class="rb-download-card__body">
					<span class="rb-download-card__title"><?php echo esc_html( $title ); ?></span>
					<?php if ( $subtitle ) : ?><span class="rb-download-card__subtitle"><?php echo esc_html( $subtitle ); ?></span><?php endif; ?>
				</span>
			</div>
			<ul class="rb-download-card__list">
				<?php foreach ( $files as $f ) : ?>
					<li>
						<a href="<?php echo esc_url( $f['url'] ); ?>" target="_blank" rel="noopener" download>
							<?php echo esc_html( $f['label'] ?? basename( wp_parse_url( $f['url'], PHP_URL_PATH ) ) ); ?>
							<span class="rb-download-card__arrow" aria-hidden="true">↓</span>
						</a>
					</li>
				<?php endforeach; ?>
			</ul>
		</div>
		<?php
	}

	private function collect_files( array $settings ) : array {
		$type = $settings['source_type'] ?? 'acf_single';

		if ( 'manual' === $type ) {
			$url = $settings['manual_file']['url'] ?? '';
			return $url ? [ [ 'url' => $url, 'label' => basename( $url ), 'size' => $this->filesize_attachment( $settings['manual_file']['id'] ?? 0 ) ] ] : [];
		}

		if ( ! function_exists( 'get_field' ) ) {
			return [];
		}

		$field = sanitize_key( $settings['acf_field'] ?? '' );
		if ( '' === $field ) {
			return [];
		}

		$value = get_field( $field );

		if ( 'acf_single' === $type ) {
			if ( is_array( $value ) && ! empty( $value['url'] ) ) {
				return [ [ 'url' => $value['url'], 'label' => $value['filename'] ?? '', 'size' => isset( $value['filesize'] ) ? size_format( (int) $value['filesize'] ) : '' ] ];
			}
			if ( is_string( $value ) && $value ) {
				return [ [ 'url' => $value, 'label' => basename( $value ), 'size' => '' ] ];
			}
			return [];
		}

		if ( 'acf_repeater' === $type && is_array( $value ) ) {
			$out = [];
			foreach ( $value as $row ) {
				// Expect rows like { cert_name, cert_logo: { url } } or generic { label, file: { url } }
				$url = $row['file']['url'] ?? $row['cert_logo']['url'] ?? '';
				if ( ! $url ) { continue; }
				$out[] = [
					'url'   => $url,
					'label' => $row['label'] ?? $row['cert_name'] ?? basename( $url ),
				];
			}
			return $out;
		}

		return [];
	}

	private function filesize_attachment( int $id ) : string {
		if ( ! $id ) {
			return '';
		}
		$path = get_attached_file( $id );
		return ( $path && file_exists( $path ) ) ? size_format( filesize( $path ) ) : '';
	}
}
