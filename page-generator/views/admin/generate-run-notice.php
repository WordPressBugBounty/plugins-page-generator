<?php
/**
 * Outputs an error message when attempting to start generation via browser
 * fails.
 *
 * @package Page_Generator_Pro
 * @author WP Zinc
 */

?>
<div class="wrap">
	<h1 class="wp-heading-inline">
		<?php echo esc_html( $this->base->plugin->displayName ); ?>

		<span>
			<?php esc_html_e( 'Generate', 'page-generator' ); ?>
		</span>
	</h1>

	<?php
	// Output Success and/or Error Notices, if any exist.
	$this->base->get_class( 'notices' )->output_notices();
	?>

	<div class="wrap-inner">
		<!-- Return Button -->
		<a href="<?php echo esc_attr( $return_url ); ?>" class="button button-primary">
			<?php esc_html_e( 'Return to Group', 'page-generator' ); ?>
		</a>
	</div>
</div>
