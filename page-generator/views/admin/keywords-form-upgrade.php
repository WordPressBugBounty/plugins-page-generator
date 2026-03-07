<?php
/**
 * Outputs the Upgrade message when adding/editing a Keyword
 *
 * @package Page_Generator_Pro
 * @author WP Zinc
 */

?>
<div class="wpzinc-option highlight">
	<div class="right">
		<h4><?php esc_html_e( 'Advanced Keyword Functionality', 'page-generator' ); ?></h4>

		<p>
			<?php
			esc_html_e( 'Define an Airtable, CSV File/URL, Database, RSS Feed or Spreadsheet as a Keyword Source; automatically generate location keywords, extract term colums/subsets.', 'page-generator' );
			?>
		</p>

		<a href="<?php echo esc_attr( $this->base->dashboard->get_upgrade_url( 'settings_inline_upgrade' ) ); ?>" class="button" target="_blank"><?php esc_html_e( 'Upgrade', 'page-generator' ); ?></a>
	</div>
</div>
