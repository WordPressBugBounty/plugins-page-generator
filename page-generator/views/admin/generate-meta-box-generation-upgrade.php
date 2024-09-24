<?php
/**
 * Outputs the Upgrade message within the Generation metabox when adding/editing a Content Groups
 *
 * @package Page_Generator_Pro
 * @author WP Zinc
 */

?>
<div class="wpzinc-option highlight">
	<div class="full">
		<h4><?php esc_html_e( 'Methods and Overwriting', 'page-generator' ); ?></h4>

		<p>
			<?php
			esc_html_e( 'Resume Generation, choose whether to overwrite specific sections or skip Page Generation if content already exists.', 'page-generator' );
			?>
		</p>

		<a href="<?php echo esc_attr( $this->base->dashboard->get_upgrade_url( 'settings_inline_upgrade' ) ); ?>" class="button button-primary" target="_blank"><?php esc_html_e( 'Upgrade', 'page-generator' ); ?></a>
	</div>
</div>
