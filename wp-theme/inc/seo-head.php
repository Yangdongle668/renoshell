<?php
/**
 * Renobattery — Head meta fallbacks
 *
 * Emits canonical, meta description, Open Graph and Twitter Card tags
 * ONLY when no dedicated SEO plugin is active. When Rank Math / Yoast
 * is detected, this file defers to them.
 *
 * Always emits theme-color meta.
 *
 * @package Renobattery
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Theme color is always safe — never clashes with plugins.
add_action( 'wp_head', 'renobattery_emit_theme_color', 3 );
function renobattery_emit_theme_color() : void {
	echo "\n<meta name=\"theme-color\" content=\"#0A0A0A\">\n";
}

// Fallback head meta — guard against SEO plugins.
add_action( 'wp_head', 'renobattery_emit_seo_fallback', 4 );
function renobattery_emit_seo_fallback() : void {
	if ( renobattery_seo_plugin_active() ) {
		return;
	}

	$url = renobattery_current_url();
	$title = wp_get_document_title();
	$desc  = renobattery_derive_description();
	$img   = renobattery_derive_og_image();
	$type  = is_singular() ? 'article' : 'website';
	$site  = get_bloginfo( 'name' );
	$locale = get_locale();

	echo "\n";
	printf( "<link rel=\"canonical\" href=\"%s\">\n", esc_url( $url ) );
	if ( $desc ) {
		printf( "<meta name=\"description\" content=\"%s\">\n", esc_attr( $desc ) );
	}

	// Robots fallback for thin pages.
	if ( is_search() || is_404() || is_author() ) {
		echo "<meta name=\"robots\" content=\"noindex,follow\">\n";
	}

	// Open Graph.
	printf( "<meta property=\"og:site_name\" content=\"%s\">\n", esc_attr( $site ) );
	printf( "<meta property=\"og:locale\" content=\"%s\">\n",    esc_attr( $locale ) );
	printf( "<meta property=\"og:type\" content=\"%s\">\n",      esc_attr( $type ) );
	printf( "<meta property=\"og:title\" content=\"%s\">\n",     esc_attr( $title ) );
	printf( "<meta property=\"og:url\" content=\"%s\">\n",       esc_url( $url ) );
	if ( $desc ) {
		printf( "<meta property=\"og:description\" content=\"%s\">\n", esc_attr( $desc ) );
	}
	if ( $img ) {
		printf( "<meta property=\"og:image\" content=\"%s\">\n", esc_url( $img ) );
	}

	// Twitter Card.
	echo "<meta name=\"twitter:card\" content=\"summary_large_image\">\n";
	printf( "<meta name=\"twitter:title\" content=\"%s\">\n", esc_attr( $title ) );
	if ( $desc ) {
		printf( "<meta name=\"twitter:description\" content=\"%s\">\n", esc_attr( $desc ) );
	}
	if ( $img ) {
		printf( "<meta name=\"twitter:image\" content=\"%s\">\n", esc_url( $img ) );
	}
}

function renobattery_seo_plugin_active() : bool {
	return defined( 'RANK_MATH_VERSION' )
		|| defined( 'WPSEO_VERSION' )
		|| defined( 'SEOPRESS_VERSION' )
		|| class_exists( 'All_in_One_SEO_Pack' );
}

function renobattery_current_url() : string {
	if ( is_singular() ) {
		return (string) get_permalink();
	}
	if ( is_post_type_archive() ) {
		return (string) get_post_type_archive_link( get_post_type() );
	}
	if ( is_tax() || is_category() || is_tag() ) {
		return (string) get_term_link( get_queried_object() );
	}
	if ( is_home() || is_front_page() ) {
		return home_url( '/' );
	}
	// Fallback: reconstruct from server vars.
	$scheme = is_ssl() ? 'https' : 'http';
	$host   = $_SERVER['HTTP_HOST'] ?? parse_url( home_url(), PHP_URL_HOST );
	$uri    = strtok( $_SERVER['REQUEST_URI'] ?? '/', '?' );
	return esc_url_raw( $scheme . '://' . $host . $uri );
}

function renobattery_derive_description() : string {
	if ( is_singular() ) {
		$excerpt = get_the_excerpt();
		if ( $excerpt ) {
			return wp_trim_words( wp_strip_all_tags( $excerpt ), 30, '…' );
		}
		$content = (string) get_post_field( 'post_content', get_queried_object() );
		return wp_trim_words( wp_strip_all_tags( $content ), 30, '…' );
	}
	if ( is_post_type_archive() ) {
		$pt = get_post_type_object( get_post_type() );
		return $pt ? (string) $pt->description : '';
	}
	if ( is_tax() || is_category() || is_tag() ) {
		return term_description( get_queried_object() );
	}
	return (string) get_bloginfo( 'description' );
}

function renobattery_derive_og_image() : string {
	if ( is_singular() && has_post_thumbnail() ) {
		return (string) get_the_post_thumbnail_url( get_queried_object_id(), 'rb-hero' );
	}
	// Fallback to site logo.
	$logo_id = (int) get_theme_mod( 'custom_logo' );
	if ( $logo_id ) {
		return (string) wp_get_attachment_image_url( $logo_id, 'full' );
	}
	return '';
}
