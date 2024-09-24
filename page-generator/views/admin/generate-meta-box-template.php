<?php
/**
 * Outputs the Template metabox when adding/editing a Content Groups
 *
 * @package Page_Generator_Pro
 * @author WP Zinc
 */

// Output Template Options for Pages.
$template = ( isset( $this->settings['pageTemplate']['page'] ) ? $this->settings['pageTemplate']['page'] : '' );
?>
<div class="wpzinc-option post-type-conditional page>">
	<div class="full">
		<select name="<?php echo esc_attr( $this->base->plugin->name ); ?>[pageTemplate][page]" id="page_template" size="1" class="widefat">
			<option value="default"<?php selected( $template, 'default' ); ?>>
				<?php esc_attr_e( 'Default Template', 'page-generator' ); ?>
			</option>
			<?php page_template_dropdown( $template, 'page' ); ?>
		</select>
	</div>
</div>
<?php
