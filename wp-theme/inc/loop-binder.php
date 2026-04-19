<?php
/**
 * Renobattery — Loop Grid binder
 *
 * One-click tool that rewrites imported Elementor templates so posts widgets
 * using the renobattery "product-card" / "case-card" / "blog-card" skin
 * become Loop Grid widgets bound to the corresponding Loop Item template ID.
 *
 * Why it exists:
 *   Our dist templates ship "widgetType":"posts" + "template":"product-card".
 *   Elementor Pro's actual loop widget needs "widgetType":"loop-grid" +
 *   "template_id":<numeric id>. We can't know the ID at build time — it's
 *   assigned when the Loop Item template is imported into this specific WP.
 *
 * Safety:
 *   - Only modifies post_type = elementor_library
 *   - Requires `manage_options` capability + nonce + typed confirmation
 *   - Always writes full backup to `_elementor_data_rb_loop_backup_<ts>` first
 *   - Restore button reverts the most recent backup
 *   - Preview mode is read-only
 *
 * @package Renobattery
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class Renobattery_Loop_Binder {

	const PAGE_SLUG    = 'renobattery-loop-binder';
	const NONCE_ACTION = 'renobattery_loop_binder';
	const BACKUP_PREFIX = '_elementor_data_rb_loop_backup_';
	const CONFIRM_PHRASE = 'APPLY';

	/**
	 * Map: skin template name used in posts widget → Loop Item template title prefix.
	 */
	const TEMPLATE_MAP = [
		'product-card' => 'RB / Product Card',
		'case-card'    => 'RB / Case Study Card',
		'blog-card'    => 'RB / Blog Card',
	];

	public static function init() : void {
		add_action( 'admin_menu', [ __CLASS__, 'register_page' ] );
	}

	public static function register_page() : void {
		add_management_page(
			__( 'RB Loop Binder', 'renobattery' ),
			__( 'RB Loop Binder', 'renobattery' ),
			'manage_options',
			self::PAGE_SLUG,
			[ __CLASS__, 'render_page' ]
		);
	}

	public static function render_page() : void {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'Unauthorized.', 'renobattery' ), 403 );
		}

		$notice = self::maybe_handle_post();

		$map  = self::resolve_target_templates();
		$plan = self::build_plan( $map );

		?>
		<div class="wrap">
			<h1><?php esc_html_e( 'Renobattery Loop Binder', 'renobattery' ); ?></h1>

			<?php if ( $notice ) : ?>
				<div class="notice notice-<?php echo esc_attr( $notice['type'] ); ?>">
					<p><?php echo wp_kses_post( $notice['msg'] ); ?></p>
				</div>
			<?php endif; ?>

			<h2><?php esc_html_e( 'Loop Item templates found', 'renobattery' ); ?></h2>
			<?php if ( empty( $map ) ) : ?>
				<p><strong><?php esc_html_e( 'No matching Loop Item templates found.', 'renobattery' ); ?></strong>
					<?php esc_html_e( 'Import component-product-card.json / component-case-card.json / component-blog-card.json first.', 'renobattery' ); ?>
				</p>
			<?php else : ?>
				<table class="widefat striped" style="max-width: 640px;">
					<thead><tr><th>Skin key</th><th>Loop Item template</th><th>ID</th></tr></thead>
					<tbody>
						<?php foreach ( $map as $skin => $id ) :
							$title = get_the_title( $id ); ?>
							<tr>
								<td><code><?php echo esc_html( $skin ); ?></code></td>
								<td><?php echo esc_html( $title ); ?></td>
								<td><?php echo (int) $id; ?></td>
							</tr>
						<?php endforeach; ?>
					</tbody>
				</table>
			<?php endif; ?>

			<h2><?php esc_html_e( 'Templates that will be rewritten', 'renobattery' ); ?></h2>
			<?php if ( empty( $plan['changes'] ) ) : ?>
				<p>
					<?php esc_html_e( 'Nothing to do — no posts widgets referencing the tracked skins were found.', 'renobattery' ); ?>
				</p>
			<?php else : ?>
				<table class="widefat striped" style="max-width: 800px;">
					<thead>
						<tr><th>Target template</th><th>ID</th><th>Widget changes</th></tr>
					</thead>
					<tbody>
						<?php foreach ( $plan['changes'] as $row ) : ?>
							<tr>
								<td><?php echo esc_html( $row['title'] ); ?></td>
								<td><?php echo (int) $row['id']; ?></td>
								<td>
									<?php foreach ( $row['widgets'] as $w ) : ?>
										<div>
											<code>posts</code> · <code><?php echo esc_html( $w['skin'] ); ?></code>
											<span aria-hidden="true">→</span>
											<code>loop-grid</code> · <code>template_id: <?php echo (int) $w['target_id']; ?></code>
										</div>
									<?php endforeach; ?>
								</td>
							</tr>
						<?php endforeach; ?>
					</tbody>
				</table>
				<p>
					<strong><?php echo (int) $plan['widget_count']; ?></strong>
					<?php esc_html_e( 'widget(s) across', 'renobattery' ); ?>
					<strong><?php echo count( $plan['changes'] ); ?></strong>
					<?php esc_html_e( 'template(s) will be rewritten.', 'renobattery' ); ?>
				</p>
			<?php endif; ?>

			<?php if ( ! empty( $plan['changes'] ) ) : ?>
				<h2><?php esc_html_e( 'Apply changes', 'renobattery' ); ?></h2>
				<form method="post" action="" style="margin-top: 20px;">
					<?php wp_nonce_field( self::NONCE_ACTION ); ?>
					<input type="hidden" name="rb_action" value="apply">
					<p>
						<label>
							<?php
							printf(
								/* translators: %s is the literal word APPLY */
								esc_html__( 'Type %s to confirm:', 'renobattery' ),
								'<code>' . esc_html( self::CONFIRM_PHRASE ) . '</code>'
							);
							?>
							<input type="text" name="rb_confirm" required pattern="<?php echo esc_attr( self::CONFIRM_PHRASE ); ?>" style="width: 120px; margin-left: 8px;">
						</label>
					</p>
					<p>
						<button type="submit" class="button button-primary">
							<?php esc_html_e( 'Apply (backup will be created)', 'renobattery' ); ?>
						</button>
					</p>
				</form>
			<?php endif; ?>

			<?php $backups = self::list_backups();
			if ( ! empty( $backups ) ) : ?>
				<h2><?php esc_html_e( 'Backups available for restore', 'renobattery' ); ?></h2>
				<table class="widefat striped" style="max-width: 640px;">
					<thead><tr><th>Template</th><th>Backup key</th><th>Action</th></tr></thead>
					<tbody>
						<?php foreach ( $backups as $b ) : ?>
							<tr>
								<td><?php echo esc_html( $b['title'] ); ?></td>
								<td><code><?php echo esc_html( $b['key'] ); ?></code></td>
								<td>
									<form method="post" action="" style="margin:0;">
										<?php wp_nonce_field( self::NONCE_ACTION ); ?>
										<input type="hidden" name="rb_action" value="restore">
										<input type="hidden" name="rb_post"   value="<?php echo (int) $b['post_id']; ?>">
										<input type="hidden" name="rb_key"    value="<?php echo esc_attr( $b['key'] ); ?>">
										<button type="submit" class="button button-secondary"
											onclick="return confirm('<?php echo esc_js( __( 'Restore this backup? The current data will be overwritten.', 'renobattery' ) ); ?>');">
											<?php esc_html_e( 'Restore', 'renobattery' ); ?>
										</button>
									</form>
								</td>
							</tr>
						<?php endforeach; ?>
					</tbody>
				</table>
			<?php endif; ?>

			<h2><?php esc_html_e( 'Notes', 'renobattery' ); ?></h2>
			<ul style="list-style: disc; padding-left: 1.2em; max-width: 720px;">
				<li><?php esc_html_e( 'Modifies elementor_library templates only. Pages and single posts are never touched.', 'renobattery' ); ?></li>
				<li><?php esc_html_e( 'Every Apply writes a full backup under post_meta _elementor_data_rb_loop_backup_<timestamp>.', 'renobattery' ); ?></li>
				<li><?php esc_html_e( 'Elementor CSS cache for affected templates is cleared automatically.', 'renobattery' ); ?></li>
			</ul>
		</div>
		<?php
	}

	private static function maybe_handle_post() : ?array {
		if ( 'POST' !== ( $_SERVER['REQUEST_METHOD'] ?? '' ) ) {
			return null;
		}
		if ( ! current_user_can( 'manage_options' ) ) {
			return [ 'type' => 'error', 'msg' => esc_html__( 'Unauthorized.', 'renobattery' ) ];
		}
		check_admin_referer( self::NONCE_ACTION );

		$action = sanitize_key( wp_unslash( $_POST['rb_action'] ?? '' ) );

		if ( $action === 'apply' ) {
			$typed = trim( wp_unslash( $_POST['rb_confirm'] ?? '' ) );
			if ( $typed !== self::CONFIRM_PHRASE ) {
				return [ 'type' => 'error', 'msg' => esc_html__( 'Confirmation phrase did not match. No changes made.', 'renobattery' ) ];
			}
			$result = self::apply();
			return [
				'type' => $result['updated'] > 0 ? 'success' : 'warning',
				'msg'  => sprintf(
					/* translators: 1=widgets, 2=templates */
					esc_html__( 'Applied: %1$d widget(s) in %2$d template(s) rewritten. Backups saved.', 'renobattery' ),
					(int) $result['widgets'],
					(int) $result['updated']
				),
			];
		}

		if ( $action === 'restore' ) {
			$post_id = (int) ( $_POST['rb_post'] ?? 0 );
			$key     = sanitize_key( wp_unslash( $_POST['rb_key'] ?? '' ) );
			if ( ! $post_id || strpos( $key, self::BACKUP_PREFIX ) !== 0 ) {
				return [ 'type' => 'error', 'msg' => esc_html__( 'Invalid restore request.', 'renobattery' ) ];
			}
			$ok = self::restore( $post_id, $key );
			return $ok
				? [ 'type' => 'success', 'msg' => esc_html__( 'Backup restored.', 'renobattery' ) ]
				: [ 'type' => 'error',   'msg' => esc_html__( 'Restore failed.', 'renobattery' ) ];
		}

		return null;
	}

	/**
	 * Resolve skin name → Loop Item template post ID via title match.
	 *
	 * @return array<string, int>
	 */
	private static function resolve_target_templates() : array {
		$out = [];
		foreach ( self::TEMPLATE_MAP as $skin => $title_prefix ) {
			$q = new WP_Query( [
				'post_type'      => 'elementor_library',
				'post_status'    => 'publish',
				'posts_per_page' => 1,
				's'              => $title_prefix,
				'orderby'        => 'ID',
				'order'          => 'DESC',
				'fields'         => 'ids',
				'no_found_rows'  => true,
			] );
			// Belt-and-braces: verify title actually starts with the prefix.
			foreach ( $q->posts as $pid ) {
				$t = get_the_title( $pid );
				if ( strpos( $t, $title_prefix ) === 0 ) {
					$out[ $skin ] = (int) $pid;
					break;
				}
			}
			wp_reset_postdata();
		}
		return $out;
	}

	private static function build_plan( array $map ) : array {
		$changes      = [];
		$widget_count = 0;

		if ( empty( $map ) ) {
			return [ 'changes' => [], 'widget_count' => 0 ];
		}

		$skins = array_keys( $map );

		$targets = get_posts( [
			'post_type'      => 'elementor_library',
			'post_status'    => 'any',
			'posts_per_page' => -1,
			'fields'         => 'ids',
			'no_found_rows'  => true,
		] );

		foreach ( $targets as $pid ) {
			// Never rewrite the Loop Item source templates themselves.
			if ( in_array( $pid, $map, true ) ) {
				continue;
			}
			$raw = get_post_meta( $pid, '_elementor_data', true );
			if ( ! $raw ) {
				continue;
			}
			$data = json_decode( $raw, true );
			if ( ! is_array( $data ) ) {
				continue;
			}
			$hits = [];
			self::collect_hits( $data, $skins, $hits );
			if ( empty( $hits ) ) {
				continue;
			}
			$widgets_out = [];
			foreach ( $hits as $h ) {
				$widgets_out[] = [
					'skin'      => $h,
					'target_id' => $map[ $h ],
				];
				$widget_count++;
			}
			$changes[] = [
				'id'      => (int) $pid,
				'title'   => get_the_title( $pid ),
				'widgets' => $widgets_out,
			];
		}

		return [ 'changes' => $changes, 'widget_count' => $widget_count ];
	}

	/**
	 * Walk the Elementor element tree recursively and gather skin values
	 * from matching posts widgets.
	 */
	private static function collect_hits( array $node, array $allowed_skins, array &$hits ) : void {
		if ( isset( $node['elType'] ) && $node['elType'] === 'widget'
			&& ( $node['widgetType'] ?? '' ) === 'posts' ) {
			$skin = $node['settings']['template'] ?? '';
			if ( $skin && in_array( $skin, $allowed_skins, true ) ) {
				$hits[] = $skin;
			}
		}
		foreach ( [ 'elements' ] as $child_key ) {
			if ( isset( $node[ $child_key ] ) && is_array( $node[ $child_key ] ) ) {
				foreach ( $node[ $child_key ] as $child ) {
					if ( is_array( $child ) ) {
						self::collect_hits( $child, $allowed_skins, $hits );
					}
				}
			}
		}
		// Top-level array without a single root node:
		if ( ! isset( $node['elType'] ) ) {
			foreach ( $node as $maybe ) {
				if ( is_array( $maybe ) ) {
					self::collect_hits( $maybe, $allowed_skins, $hits );
				}
			}
		}
	}

	/**
	 * Recursively rewrite `posts` widgets → `loop-grid` + template_id.
	 * Returns the modified tree and a count via reference.
	 */
	private static function rewrite_tree( array $node, array $map, int &$count ) : array {
		if ( isset( $node['elType'] ) && $node['elType'] === 'widget'
			&& ( $node['widgetType'] ?? '' ) === 'posts' ) {
			$skin = $node['settings']['template'] ?? '';
			if ( $skin && isset( $map[ $skin ] ) ) {
				$node['widgetType'] = 'loop-grid';
				// Preserve grid-related settings; replace skin identity with template_id.
				$node['settings']['template_id']            = (int) $map[ $skin ];
				$node['settings']['skin']                   = '';
				$node['settings']['template']               = ''; // no longer a skin marker
				$node['settings']['_skin']                  = '';
				$node['settings']['_renobattery_converted'] = 1;
				$count++;
			}
		}
		if ( isset( $node['elements'] ) && is_array( $node['elements'] ) ) {
			foreach ( $node['elements'] as $i => $child ) {
				if ( is_array( $child ) ) {
					$node['elements'][ $i ] = self::rewrite_tree( $child, $map, $count );
				}
			}
		}
		return $node;
	}

	private static function apply() : array {
		$map  = self::resolve_target_templates();
		$plan = self::build_plan( $map );

		$updated = 0;
		$widgets = 0;

		foreach ( $plan['changes'] as $row ) {
			$pid = (int) $row['id'];
			$raw = get_post_meta( $pid, '_elementor_data', true );
			if ( ! $raw ) {
				continue;
			}
			$data = json_decode( $raw, true );
			if ( ! is_array( $data ) ) {
				continue;
			}

			// Backup (verify before modifying).
			$backup_key = self::BACKUP_PREFIX . time() . '_' . wp_generate_password( 4, false, false );
			$ok         = update_post_meta( $pid, $backup_key, $raw );
			if ( ! $ok ) {
				continue; // don't touch if backup didn't take.
			}

			$count = 0;
			if ( isset( $data[0] ) ) {
				// Array of top-level sections.
				foreach ( $data as $i => $top ) {
					if ( is_array( $top ) ) {
						$data[ $i ] = self::rewrite_tree( $top, $map, $count );
					}
				}
			} else {
				$data = self::rewrite_tree( $data, $map, $count );
			}

			if ( $count === 0 ) {
				// Nothing actually changed — clean up the useless backup.
				delete_post_meta( $pid, $backup_key );
				continue;
			}

			$new_raw = wp_json_encode( $data );
			if ( $new_raw === false ) {
				continue;
			}
			update_post_meta( $pid, '_elementor_data', wp_slash( $new_raw ) );
			delete_post_meta( $pid, '_elementor_css' );

			if ( class_exists( '\\Elementor\\Plugin' ) ) {
				// Flush Elementor's generated CSS for the post.
				\Elementor\Plugin::$instance->files_manager->clear_cache();
			}

			$updated++;
			$widgets += $count;
		}

		return [ 'updated' => $updated, 'widgets' => $widgets ];
	}

	private static function list_backups() : array {
		global $wpdb;
		$rows = $wpdb->get_results( $wpdb->prepare(
			"SELECT post_id, meta_key FROM {$wpdb->postmeta} WHERE meta_key LIKE %s ORDER BY meta_id DESC LIMIT 200",
			$wpdb->esc_like( self::BACKUP_PREFIX ) . '%'
		) );
		$out = [];
		foreach ( (array) $rows as $r ) {
			$out[] = [
				'post_id' => (int) $r->post_id,
				'title'   => get_the_title( (int) $r->post_id ),
				'key'     => (string) $r->meta_key,
			];
		}
		return $out;
	}

	private static function restore( int $post_id, string $backup_key ) : bool {
		if ( strpos( $backup_key, self::BACKUP_PREFIX ) !== 0 ) {
			return false;
		}
		$backup = get_post_meta( $post_id, $backup_key, true );
		if ( ! $backup ) {
			return false;
		}
		update_post_meta( $post_id, '_elementor_data', wp_slash( $backup ) );
		delete_post_meta( $post_id, '_elementor_css' );
		if ( class_exists( '\\Elementor\\Plugin' ) ) {
			\Elementor\Plugin::$instance->files_manager->clear_cache();
		}
		// Keep the backup meta — user may want to restore again.
		return true;
	}
}

Renobattery_Loop_Binder::init();
