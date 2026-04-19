<?php
/**
 * Renobattery — Automated QA runner
 *
 * Runs every check from docs/step-10-qa.json that can be scripted.
 * Non-zero exit on any failure. Intended for CI.
 *
 * Usage:  php tools/qa-check.php
 */

declare( strict_types=1 );

const ROOT = __DIR__ . '/..';
const DIST = ROOT . '/templates/elementor/dist';
const SRC  = ROOT . '/templates/elementor';

$failures = [];
$report   = [];

function ok(   string $name, string $detail = '' ) : void { global $report; $report[] = [ 'ok', $name, $detail ]; }
function fail( string $name, string $detail = '' ) : void { global $report, $failures; $report[] = [ 'fail', $name, $detail ]; $failures[] = "$name: $detail"; }
function skip( string $name, string $detail = '' ) : void { global $report; $report[] = [ 'skip', $name, $detail ]; }

// ---------- a1: PHP lint ----------
$php_files = array_merge(
	glob( ROOT . '/*.php' ) ?: [],
	glob( ROOT . '/inc/*.php' ) ?: [],
	glob( ROOT . '/widgets/*.php' ) ?: [],
	glob( ROOT . '/tools/*.php' ) ?: []
);
$bad = [];
foreach ( $php_files as $f ) {
	$out = shell_exec( 'php -l ' . escapeshellarg( $f ) . ' 2>&1' );
	if ( strpos( (string) $out, 'No syntax errors' ) === false ) {
		$bad[] = basename( $f );
	}
}
$bad ? fail( 'a1 PHP lint', implode( ', ', $bad ) ) : ok( 'a1 PHP lint', count( $php_files ) . ' files' );

// ---------- a2: source JSON parse ----------
$src_jsons = glob( SRC . '/*.json' ) ?: [];
$bad = [];
foreach ( $src_jsons as $f ) {
	if ( json_decode( (string) file_get_contents( $f ), true ) === null && json_last_error() !== JSON_ERROR_NONE ) {
		$bad[] = basename( $f );
	}
}
$bad ? fail( 'a2 source JSON parse', implode( ', ', $bad ) ) : ok( 'a2 source JSON parse', count( $src_jsons ) . ' files' );

// ---------- a3: flatten tool runs clean ----------
$output = shell_exec( 'php ' . escapeshellarg( ROOT . '/tools/flatten-elementor-templates.php' ) . ' 2>&1' );
if ( preg_match( '/(\d+) ok, (\d+) errors/', (string) $output, $m ) ) {
	if ( (int) $m[2] === 0 && (int) $m[1] === count( $src_jsons ) ) {
		ok( 'a3 flatten tool', "{$m[1]} ok" );
	} else {
		fail( 'a3 flatten tool', "{$m[1]} ok, {$m[2]} errors" );
	}
} else {
	fail( 'a3 flatten tool', 'no summary line' );
}

// ---------- a4: dist has no invalid keys ----------
$dist_jsons = glob( DIST . '/*.json' ) ?: [];
$invalid_keys = [ '_ref', 'overrides', 'backdrop_filter', 'css_rules', 'hover_transition', 'card_overlay_gradient', 'background_hover_color', '_css_filters_css_filter' ];
$hits = [];
foreach ( $dist_jsons as $f ) {
	$txt = (string) file_get_contents( $f );
	foreach ( $invalid_keys as $k ) {
		if ( strpos( $txt, '"' . $k . '"' ) !== false ) {
			$hits[] = basename( $f ) . ':' . $k;
		}
	}
}
$hits ? fail( 'a4 dist denylist', implode( ', ', $hits ) ) : ok( 'a4 dist denylist', count( $dist_jsons ) . ' files clean' );

// ---------- a5 + a6: msgid parity & dedupe ----------
$pot = ROOT . '/languages/renobattery.pot';
$po  = ROOT . '/languages/zh_CN.po';
if ( file_exists( $pot ) && file_exists( $po ) ) {
	$extract = function( string $file ) : array {
		$t = (string) file_get_contents( $file );
		preg_match_all( '/^msgid "(.*)"$/m', $t, $m );
		return array_values( array_filter( $m[1] ?? [] ) );
	};
	$ids_pot = $extract( $pot );
	$ids_po  = $extract( $po );
	$dup_pot = array_unique( array_diff_assoc( $ids_pot, array_unique( $ids_pot ) ) );
	$dup_po  = array_unique( array_diff_assoc( $ids_po,  array_unique( $ids_po  ) ) );
	( count( $ids_pot ) === count( $ids_po ) )
		? ok( 'a5 msgid parity', count( $ids_pot ) . ' ↔ ' . count( $ids_po ) )
		: fail( 'a5 msgid parity', count( $ids_pot ) . ' pot vs ' . count( $ids_po ) . ' po' );
	( empty( $dup_pot ) && empty( $dup_po ) )
		? ok( 'a6 no dup msgids' )
		: fail( 'a6 no dup msgids', 'pot:' . implode( ',', $dup_pot ) . ' po:' . implode( ',', $dup_po ) );
} else {
	skip( 'a5/a6 i18n', 'pot/po missing' );
}

// ---------- a7: no console.log / debugger ----------
$js_files = glob( ROOT . '/assets/js/*.js' ) ?: [];
$hits = [];
foreach ( $js_files as $f ) {
	$t = (string) file_get_contents( $f );
	if ( preg_match( '/console\.log|debugger/', $t ) ) {
		$hits[] = basename( $f );
	}
}
$hits ? fail( 'a7 no console/debugger', implode( ', ', $hits ) ) : ok( 'a7 no console/debugger', count( $js_files ) . ' files' );

// ---------- a8: style.css header ----------
$style = (string) @file_get_contents( ROOT . '/style.css' );
( strpos( $style, 'Theme Name' ) !== false )
	? ok( 'a8 style.css header' )
	: fail( 'a8 style.css header', 'Theme Name missing' );

// ---------- a9: functions.php loads modules ----------
$fn = (string) @file_get_contents( ROOT . '/functions.php' );
$count_req = substr_count( $fn, 'require_once' );
( $count_req >= 7 )
	? ok( 'a9 functions.php requires', "$count_req modules" )
	: fail( 'a9 functions.php requires', "only $count_req" );

// ---------- a10: widget files match registry ----------
$registry_file = (string) @file_get_contents( ROOT . '/inc/elementor-widgets.php' );
preg_match_all( "/'([a-z-]+)'\\s*=>\\s*'(Renobattery_Widget_[A-Za-z_]+)'/", $registry_file, $m );
$missing = [];
foreach ( $m[1] ?? [] as $slug ) {
	if ( ! file_exists( ROOT . "/widgets/class-{$slug}.php" ) ) {
		$missing[] = $slug;
	}
}
$missing ? fail( 'a10 widget files', implode( ', ', $missing ) ) : ok( 'a10 widget files', count( $m[1] ?? [] ) . ' widgets' );

// ---------- Report ----------
echo "\n========= Renobattery QA =========\n";
foreach ( $report as [ $status, $name, $detail ] ) {
	$badge = [ 'ok' => "\033[32m ✓ \033[0m", 'fail' => "\033[31m ✗ \033[0m", 'skip' => "\033[33m - \033[0m" ][ $status ];
	printf( "%s %-30s %s\n", $badge, $name, $detail );
}
$total = count( $report );
$pass  = count( array_filter( $report, fn( $r ) => $r[0] === 'ok' ) );
$fail  = count( array_filter( $report, fn( $r ) => $r[0] === 'fail' ) );
echo "\n{$pass}/{$total} passed, {$fail} failed.\n";
exit( $fail === 0 ? 0 : 1 );
