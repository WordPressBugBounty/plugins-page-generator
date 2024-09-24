<?php
/**
 * Outputs the Discussion metabox when adding/editing a Content Groups
 *
 * @package Page_Generator_Pro
 * @author WP Zinc
 */

?>
<div class="wpzinc-option">
	<div class="left">
		<label for="comments"><?php esc_html_e( 'Allow comments?', 'page-generator' ); ?></label>
	</div>
	<div class="right">
		<input type="checkbox" id="comments" name="<?php echo esc_attr( $this->base->plugin->name ); ?>[comments]" value="1"<?php checked( $this->settings['comments'], 1 ); ?> />

		<p class="description">
			<?php esc_html_e( 'If checked, a comments form will be displayed on every generated Page/Post.  It is your Theme\'s responsibility to honor this setting.', 'page-generator' ); ?>
		</p>
	</div>
</div>
<div class="wpzinc-option">
	<div class="left">
		<label for="trackbacks"><?php esc_html_e( 'Allow track / pingbacks?', 'page-generator' ); ?></label>
	</div>
	<div class="right">
		<input type="checkbox" id="trackbacks" name="<?php echo esc_attr( $this->base->plugin->name ); ?>[trackbacks]" value="1"<?php checked( $this->settings['trackbacks'], 1 ); ?> />
	</div>
</div>
