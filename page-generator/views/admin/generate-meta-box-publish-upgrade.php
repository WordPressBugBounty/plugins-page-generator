<?php
/**
 * Outputs the Upgrade message within the Publish metabox when adding/editing a Content Groups
 *
 * @package Page_Generator_Pro
 * @author WP Zinc
 */

?>
<div class="wpzinc-option highlight">
	<div class="full">
		<h4><?php esc_html_e( 'Post Types and Scheduling', 'page-generator' ); ?></h4>

		<p>
			<?php
			esc_html_e( 'Upgrade to Page Generator Pro to Generate Posts, Custom Post Types, schedule content and use your preferred Page Builder.', 'page-generator' );
			?>
		</p>

		<a href="<?php echo esc_attr( $this->base->dashboard->get_upgrade_url( 'settings_inline_upgrade' ) ); ?>" class="button button-primary" target="_blank"><?php esc_html_e( 'Upgrade', 'page-generator' ); ?></a>
	</div>
</div>
