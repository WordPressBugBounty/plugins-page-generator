<?php
/**
 * Outputs the Generation metabox when adding/editing a Content Groups
 *
 * @package Page_Generator_Pro
 * @author WP Zinc
 */

?>
<div class="wpzinc-option sidebar">
	<div class="left">
		<label for="method"><?php esc_html_e( 'Method', 'page-generator' ); ?></label>
	</div>
	<div class="right">
		<select name="<?php echo esc_attr( $this->base->plugin->name ); ?>[method]" id="method" size="1" class="widefat">
			<?php
			if ( is_array( $methods ) && count( $methods ) > 0 ) {
				foreach ( $methods as $method => $label ) {
					?>
					<option value="<?php echo esc_attr( $method ); ?>"<?php selected( $this->settings['method'], $method ); ?>>
						<?php echo esc_attr( $label ); ?>
					</option>
					<?php
				}
			}
			?>
		</select>
	</div>
	<p class="description">
		<strong><?php esc_html_e( 'All:', 'page-generator' ); ?></strong>
		<?php
		printf(
			/* translators: Post Type, Plural (e.g. Posts, Pages) */
			esc_html__( 'Generates %s for all possible combinations of terms across all keywords used.', 'page-generator' ),
			esc_html( $labels['plural'] )
		);
		?>
	</p>
	<p class="description">
		<strong><?php esc_html_e( 'Sequential:', 'page-generator' ); ?></strong>
		<?php esc_html_e( 'Honors the order of terms in each keyword used. Once all terms have been used in a keyword, the generator stops.', 'page-generator' ); ?>
	</p>
</div>

<div class="wpzinc-option sidebar">
	<div class="left">
		<label for="number_of_posts">
			<?php
			printf(
				/* translators: Post Type, Plural */
				esc_html__( 'No. %s', 'page-generator' ),
				esc_html( $labels['plural'] )
			);
			?>
		</label>
	</div>
	<div class="right">
		<input type="number" name="<?php echo esc_attr( $this->base->plugin->name ); ?>[numberOfPosts]" id="number_of_posts" value="<?php echo esc_attr( $this->settings['numberOfPosts'] ); ?>" step="1" min="0" class="widefat" />
	</div>
	<p class="description">
		<?php
		printf(
			/* translators: %1$s: Post Type, Plural, %2$s: Post Type, Plural */
			esc_html__( 'The number of %1$s to generate. If zero or blank, all %2$s will be generated.', 'page-generator' ),
			esc_html( $labels['plural'] ),
			esc_html( $labels['plural'] )
		);
		?>
	</p>
</div>

<?php
// Upgrade Notice.
if ( class_exists( 'Page_Generator' ) ) {
	require $this->base->plugin->folder . 'views/admin/generate-meta-box-generation-upgrade.php';
}
