<?php
/**
 * Pet Table presentation template.
 *
 * Rendered from Pet_Table_Widget::render(). Expects these variables in scope:
 *
 * @var array  $pets          Normalized pets (id, name, category, status, photo_url).
 * @var int    $rows_per_page Rows to show per page (client-side pagination).
 * @var bool   $show_photo    Whether to render the photo column.
 * @var string $status        Resolved status filter.
 * @var string $status_label  Human label for the status.
 * @var bool   $is_edit_mode  Whether rendering inside the Elementor editor.
 *
 * @package WP_Petstore_Directory
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$is_edit_mode = ! empty( $is_edit_mode );

// Empty state — a valid outcome (the API returns HTTP 200 + [] for statuses
// with no pets), distinct from the error state handled in the widget.
if ( empty( $pets ) ) :
	// An empty pending/sold set is normal, but reads as a bug in the editor, so
	// the builder gets an actionable hint while visitors get a plain message.
	if ( $is_edit_mode ) {
		$empty_message = sprintf(
			/* translators: %s: status label, e.g. "Available". */
			esc_html__( 'No pets returned for status “%s”. Try a different status filter, or verify the API under Settings → Petstore Directory.', 'wp-petstore-directory' ),
			esc_html( $status_label )
		);
	} else {
		$empty_message = sprintf(
			/* translators: %s: status label, e.g. "Available". */
			esc_html__( 'No pets found for status “%s”.', 'wp-petstore-directory' ),
			esc_html( $status_label )
		);
	}
	?>
	<div class="wppd-pet-table wppd-pet-table--message">
		<?php // $empty_message is built from escaped parts above. ?>
		<p class="wppd-empty"><?php echo $empty_message; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></p>
	</div>
	<?php
	return;
endif;

$colspan = $show_photo ? 4 : 3;
?>
<div class="wppd-pet-table" data-rows-per-page="<?php echo esc_attr( (string) $rows_per_page ); ?>">
	<table>
		<thead>
			<tr>
				<?php if ( $show_photo ) : ?>
					<th scope="col" class="wppd-col-photo"><?php esc_html_e( 'Photo', 'wp-petstore-directory' ); ?></th>
				<?php endif; ?>
				<th scope="col"><?php esc_html_e( 'Name', 'wp-petstore-directory' ); ?></th>
				<th scope="col"><?php esc_html_e( 'Category', 'wp-petstore-directory' ); ?></th>
				<th scope="col"><?php esc_html_e( 'Status', 'wp-petstore-directory' ); ?></th>
			</tr>
		</thead>
		<tbody>
			<?php foreach ( $pets as $pet ) : ?>
				<tr class="wppd-row">
					<?php if ( $show_photo ) : ?>
						<td class="wppd-col-photo">
							<?php if ( '' !== $pet['photo_url'] ) : ?>
								<img class="wppd-photo"
									src="<?php echo esc_url( $pet['photo_url'] ); ?>"
									alt="<?php echo esc_attr( '' !== $pet['name'] ? $pet['name'] : __( 'Pet photo', 'wp-petstore-directory' ) ); ?>"
									loading="lazy" />
							<?php else : ?>
								<span class="wppd-no-photo" aria-hidden="true">—</span>
							<?php endif; ?>
						</td>
					<?php endif; ?>
					<td class="wppd-col-name"><?php echo esc_html( '' !== $pet['name'] ? $pet['name'] : '—' ); ?></td>
					<td class="wppd-col-category"><?php echo esc_html( '' !== $pet['category'] ? $pet['category'] : '—' ); ?></td>
					<td class="wppd-col-status">
						<?php if ( '' !== $pet['status'] ) : ?>
							<span class="wppd-status wppd-status--<?php echo esc_attr( sanitize_html_class( $pet['status'] ) ); ?>">
								<?php echo esc_html( $pet['status'] ); ?>
							</span>
						<?php else : ?>
							<?php echo esc_html( '—' ); ?>
						<?php endif; ?>
					</td>
				</tr>
			<?php endforeach; ?>
		</tbody>
	</table>

	<nav class="wppd-pagination" hidden aria-label="<?php esc_attr_e( 'Pet table pagination', 'wp-petstore-directory' ); ?>">
		<button type="button" class="wppd-prev" rel="prev"><?php esc_html_e( 'Previous', 'wp-petstore-directory' ); ?></button>
		<span class="wppd-page-status" aria-live="polite"></span>
		<button type="button" class="wppd-next" rel="next"><?php esc_html_e( 'Next', 'wp-petstore-directory' ); ?></button>
	</nav>
</div>
<?php
unset( $colspan );
