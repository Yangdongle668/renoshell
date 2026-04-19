<?php
/**
 * Renobattery — ACF field groups (PHP registration)
 * Source: docs/step-01-architecture.json
 *
 * @package Renobattery
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

add_action( 'acf/init', 'renobattery_register_acf_fields' );
function renobattery_register_acf_fields() : void {
	if ( ! function_exists( 'acf_add_local_field_group' ) ) {
		return;
	}

	// -------- PRODUCT SPECS --------
	acf_add_local_field_group( [
		'key'    => 'group_rb_product_specs',
		'title'  => __( 'Product Specs', 'renobattery' ),
		'position' => 'normal',
		'style'    => 'default',
		'location' => [ [ [ 'param' => 'post_type', 'operator' => '==', 'value' => 'product' ] ] ],
		'fields' => [
			[ 'key' => 'field_rb_model_code', 'name' => 'model_code', 'label' => 'Model Code', 'type' => 'text', 'required' => 1 ],
			[ 'key' => 'field_rb_capacity',   'name' => 'battery_capacity', 'label' => 'Capacity (Ah)', 'type' => 'number', 'append' => 'Ah' ],
			[ 'key' => 'field_rb_voltage',    'name' => 'nominal_voltage',  'label' => 'Voltage (V)',   'type' => 'number', 'append' => 'V' ],
			[ 'key' => 'field_rb_energy',     'name' => 'energy_kwh',       'label' => 'Energy (kWh)',  'type' => 'number', 'append' => 'kWh' ],
			[ 'key' => 'field_rb_cycles',     'name' => 'cycle_life',       'label' => 'Cycle Life',    'type' => 'number', 'append' => 'cycles' ],
			[
				'key' => 'field_rb_chemistry', 'name' => 'chemistry', 'label' => 'Chemistry', 'type' => 'select',
				'choices' => [ 'lfp' => 'LiFePO4', 'nmc' => 'NMC', 'nca' => 'NCA', 'lto' => 'LTO', 'pb' => 'Lead-Acid' ],
			],
			[
				'key' => 'field_rb_dimensions', 'name' => 'dimensions', 'label' => 'Dimensions (mm)', 'type' => 'group',
				'sub_fields' => [
					[ 'key' => 'field_rb_dim_l', 'name' => 'length', 'label' => 'Length', 'type' => 'number' ],
					[ 'key' => 'field_rb_dim_w', 'name' => 'width',  'label' => 'Width',  'type' => 'number' ],
					[ 'key' => 'field_rb_dim_h', 'name' => 'height', 'label' => 'Height', 'type' => 'number' ],
				],
			],
			[ 'key' => 'field_rb_weight',      'name' => 'weight_kg',      'label' => 'Weight (kg)',    'type' => 'number' ],
			[ 'key' => 'field_rb_ip',          'name' => 'ip_rating',      'label' => 'IP Rating',      'type' => 'text' ],
			[ 'key' => 'field_rb_temp',        'name' => 'operating_temp', 'label' => 'Operating Temp', 'type' => 'text' ],
			[
				'key' => 'field_rb_certs', 'name' => 'certifications', 'label' => 'Certifications', 'type' => 'repeater',
				'sub_fields' => [
					[ 'key' => 'field_rb_cert_name', 'name' => 'cert_name', 'label' => 'Name', 'type' => 'text' ],
					[ 'key' => 'field_rb_cert_logo', 'name' => 'cert_logo', 'label' => 'Logo', 'type' => 'image', 'return_format' => 'array' ],
				],
			],
			[ 'key' => 'field_rb_datasheet',   'name' => 'datasheet_pdf',  'label' => 'Datasheet PDF',  'type' => 'file', 'return_format' => 'url' ],
			[ 'key' => 'field_rb_manual',      'name' => 'manual_pdf',     'label' => 'User Manual',    'type' => 'file', 'return_format' => 'url' ],
			[ 'key' => 'field_rb_gallery',     'name' => 'gallery',        'label' => 'Product Gallery','type' => 'gallery', 'return_format' => 'array' ],
			[ 'key' => 'field_rb_hero_video',  'name' => 'hero_video_mp4', 'label' => 'Hero Video (MP4)','type'=> 'file', 'return_format' => 'url' ],
			[
				'key' => 'field_rb_spec_table', 'name' => 'spec_table', 'label' => 'Spec Table Rows', 'type' => 'repeater',
				'sub_fields' => [
					[ 'key' => 'field_rb_spec_label', 'name' => 'spec_label', 'label' => 'Label', 'type' => 'text' ],
					[ 'key' => 'field_rb_spec_value', 'name' => 'spec_value', 'label' => 'Value', 'type' => 'text' ],
				],
			],
		],
	] );

	// -------- CASE STUDY --------
	acf_add_local_field_group( [
		'key'    => 'group_rb_case_study',
		'title'  => __( 'Case Study', 'renobattery' ),
		'location' => [ [ [ 'param' => 'post_type', 'operator' => '==', 'value' => 'case_study' ] ] ],
		'fields' => [
			[ 'key' => 'field_rb_cs_client',   'name' => 'client_name',   'label' => 'Client',     'type' => 'text' ],
			[ 'key' => 'field_rb_cs_location', 'name' => 'location',      'label' => 'Location',   'type' => 'text' ],
			[ 'key' => 'field_rb_cs_kwh',      'name' => 'installed_kwh', 'label' => 'Installed kWh', 'type' => 'number', 'append' => 'kWh' ],
			[
				'key' => 'field_rb_cs_products', 'name' => 'products_used', 'label' => 'Products Used',
				'type' => 'relationship', 'post_type' => [ 'product' ], 'return_format' => 'object',
			],
			[
				'key' => 'field_rb_cs_kpi', 'name' => 'results_kpi', 'label' => 'Results KPIs', 'type' => 'repeater',
				'sub_fields' => [
					[ 'key' => 'field_rb_cs_kpi_l', 'name' => 'kpi_label', 'label' => 'Label', 'type' => 'text' ],
					[ 'key' => 'field_rb_cs_kpi_v', 'name' => 'kpi_value', 'label' => 'Value', 'type' => 'text' ],
				],
			],
			[ 'key' => 'field_rb_cs_hero',    'name' => 'hero_image', 'label' => 'Hero Image', 'type' => 'image',   'return_format' => 'array' ],
			[ 'key' => 'field_rb_cs_gallery', 'name' => 'gallery',    'label' => 'Gallery',    'type' => 'gallery', 'return_format' => 'array' ],
		],
	] );

	// -------- APPLICATION --------
	acf_add_local_field_group( [
		'key'      => 'group_rb_application',
		'title'    => __( 'Application', 'renobattery' ),
		'location' => [ [ [ 'param' => 'post_type', 'operator' => '==', 'value' => 'application' ] ] ],
		'fields'   => [
			[ 'key' => 'field_rb_app_icon', 'name' => 'icon_svg', 'label' => 'Icon', 'type' => 'image', 'return_format' => 'array' ],
			[ 'key' => 'field_rb_app_bg',   'name' => 'hero_bg',  'label' => 'Hero BG', 'type' => 'image', 'return_format' => 'array' ],
			[
				'key' => 'field_rb_app_benefits', 'name' => 'benefits', 'label' => 'Benefits', 'type' => 'repeater',
				'sub_fields' => [
					[ 'key' => 'field_rb_app_bt', 'name' => 'title',   'label' => 'Title',   'type' => 'text' ],
					[ 'key' => 'field_rb_app_bs', 'name' => 'summary', 'label' => 'Summary', 'type' => 'textarea' ],
				],
			],
			[
				'key' => 'field_rb_app_rec', 'name' => 'recommended_products', 'label' => 'Recommended Products',
				'type' => 'relationship', 'post_type' => [ 'product' ], 'return_format' => 'object',
			],
		],
	] );

	// -------- GLOBAL OPTIONS --------
	acf_add_local_field_group( [
		'key'    => 'group_rb_global_options',
		'title'  => __( 'Global Site Options', 'renobattery' ),
		'location' => [ [ [ 'param' => 'options_page', 'operator' => '==', 'value' => 'renobattery-options' ] ] ],
		'fields' => [
			[ 'key' => 'field_rb_opt_phone',    'name' => 'company_phone',    'label' => 'Phone',   'type' => 'text' ],
			[ 'key' => 'field_rb_opt_email',    'name' => 'company_email',    'label' => 'Email',   'type' => 'email' ],
			[ 'key' => 'field_rb_opt_address',  'name' => 'company_address',  'label' => 'Address', 'type' => 'textarea' ],
			[
				'key' => 'field_rb_opt_social', 'name' => 'social_links', 'label' => 'Social Links', 'type' => 'repeater',
				'sub_fields' => [
					[ 'key' => 'field_rb_opt_sp', 'name' => 'platform', 'label' => 'Platform', 'type' => 'text' ],
					[ 'key' => 'field_rb_opt_su', 'name' => 'url',      'label' => 'URL',      'type' => 'url' ],
				],
			],
			[ 'key' => 'field_rb_opt_cta_title', 'name' => 'footer_cta_title', 'label' => 'Footer CTA Title', 'type' => 'text' ],
			[ 'key' => 'field_rb_opt_cta_text',  'name' => 'footer_cta_text',  'label' => 'Footer CTA Text',  'type' => 'textarea' ],
		],
	] );
}
