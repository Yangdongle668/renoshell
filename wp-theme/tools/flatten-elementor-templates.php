<?php
/**
 * Renobattery — Elementor template flattener / validator
 *
 * Source JSONs in templates/elementor/*.json use two renobattery-only extensions:
 *   1. "_ref": "component-xxx.json"  → inline the referenced component content here
 *   2. "overrides": { "dot.path": value, ... }  → apply after inlining
 *
 * This CLI script:
 *   a) resolves _ref recursively
 *   b) applies overrides
 *   c) strips keys in DENYLIST (not valid Elementor)
 *   d) writes flat JSON to templates/elementor/dist/
 *
 * Run:
 *   php tools/flatten-elementor-templates.php
 *
 * Output is what you import into Elementor → Templates → Import.
 *
 * @package Renobattery
 */

declare( strict_types=1 );

const SRC_DIR  = __DIR__ . '/../templates/elementor';
const DIST_DIR = __DIR__ . '/../templates/elementor/dist';

// Keys that appear in source files but are NOT valid Elementor import settings.
// Their visual intent is fulfilled via assets/css/components.css (class-based).
const DENYLIST = [
	'backdrop_filter',
	'css_rules',
	'hover_transition',
	'card_overlay_gradient',
	'background_hover_color',
	'_css_filters_css_filter',
];

// Composition keys (renobattery extension) that must be resolved or removed.
const COMPOSITION_KEYS = [ '_ref', 'overrides' ];

function main() : int {
	if ( ! is_dir( SRC_DIR ) ) {
		fwrite( STDERR, "Source dir not found: " . SRC_DIR . "\n" );
		return 1;
	}
	if ( ! is_dir( DIST_DIR ) && ! mkdir( DIST_DIR, 0755, true ) ) {
		fwrite( STDERR, "Cannot create dist dir: " . DIST_DIR . "\n" );
		return 1;
	}

	$sources = glob( SRC_DIR . '/*.json' ) ?: [];
	$components = []; // cache of parsed components keyed by basename.
	foreach ( $sources as $path ) {
		$components[ basename( $path ) ] = json_decode( (string) file_get_contents( $path ), true, 512, JSON_THROW_ON_ERROR );
	}

	$count_ok = 0;
	$count_err = 0;

	foreach ( $sources as $path ) {
		$name = basename( $path );
		try {
			$doc = $components[ $name ];
			$doc = walk_resolve( $doc, $components );
			$doc = walk_strip_denylist( $doc );
			$json = json_encode( $doc, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE );
			file_put_contents( DIST_DIR . '/' . $name, $json );
			echo "[ok] {$name}\n";
			$count_ok++;
		} catch ( \Throwable $e ) {
			fwrite( STDERR, "[err] {$name}: " . $e->getMessage() . "\n" );
			$count_err++;
		}
	}

	echo "\n{$count_ok} ok, {$count_err} errors.\n";
	return $count_err === 0 ? 0 : 1;
}

/**
 * Recursively resolve `_ref` and apply `overrides` for any node.
 */
function walk_resolve( $node, array $components ) {
	if ( is_array( $node ) ) {
		// If this node has _ref, inline the referenced doc's content array.
		if ( isset( $node['_ref'] ) && is_string( $node['_ref'] ) ) {
			$ref = $node['_ref'];
			if ( ! isset( $components[ $ref ] ) ) {
				throw new \RuntimeException( "Unknown _ref: {$ref}" );
			}
			$target = $components[ $ref ];
			// A referenced component file has top-level 'content' array of sections.
			// When inlined at a section slot, take the first section (most common case).
			$inlined = $target['content'][0] ?? $target;

			// Merge siblings (id + outer settings overrides) onto the inlined section.
			$merged = $inlined;
			if ( isset( $node['id'] ) ) {
				$merged['id'] = $node['id'];
			}
			if ( isset( $node['overrides'] ) && is_array( $node['overrides'] ) ) {
				foreach ( $node['overrides'] as $dot_path => $value ) {
					$merged = apply_override( $merged, $dot_path, $value );
				}
			}
			return walk_resolve( $merged, $components );
		}

		// Recurse into children.
		foreach ( $node as $k => $v ) {
			$node[ $k ] = walk_resolve( $v, $components );
		}
		return $node;
	}
	return $node;
}

/**
 * Apply one override using a simple dot path like "elements.0.settings.title".
 */
function apply_override( array $root, string $dot_path, $value ) : array {
	$segments = explode( '.', $dot_path );
	$ref =& $root;
	foreach ( $segments as $seg ) {
		$key = is_numeric( $seg ) ? (int) $seg : $seg;
		if ( ! is_array( $ref ) || ! array_key_exists( $key, $ref ) ) {
			// silently ignore missing paths — caller can verify.
			return $root;
		}
		$ref =& $ref[ $key ];
	}
	$ref = $value;
	return $root;
}

/**
 * Remove denylisted keys anywhere in the tree.
 */
function walk_strip_denylist( $node ) {
	if ( is_array( $node ) ) {
		foreach ( DENYLIST as $bad ) {
			unset( $node[ $bad ] );
		}
		// Also strip composition keys in case any survived un-resolved.
		foreach ( COMPOSITION_KEYS as $ck ) {
			unset( $node[ $ck ] );
		}
		foreach ( $node as $k => $v ) {
			$node[ $k ] = walk_strip_denylist( $v );
		}
	}
	return $node;
}

exit( main() );
