<?php
/**
 * Outputs the Generate via Browser screen
 *
 * @package Page_Generator_Pro
 * @author WP Zinc
 */

?>
<div class="wrap">
	<h1 class="wp-heading-inline">
		<?php echo esc_html( $this->base->plugin->displayName ); ?>

		<span>
			<?php
			printf(
				'%1$s &quot;%2$s&quot;',
				esc_html__( 'Generating', 'page-generator' ),
				esc_html( $settings['title'] )
			);
			?>
		</span>
	</h1>

	<hr class="wp-header-end" />

	<div class="wrap-inner">
		<p>
			<?php
			printf(
				'%s %s %s',
				esc_html__( 'Please be patient while content is generated. This can take a while if you have a lot of Pages to generate, and/or you are using Page Generator Shortcodes.', 'page-generator' ),
				'<a href="' . esc_attr( $this->base->plugin->documentation_url ) . '/generate/#page-generation" target="_blank">' . esc_html__( 'Read the Documentation', 'page-generator' ) . '</a>',
				esc_html__( 'to understand why.', 'page-generator' )
			);
			?>
			<br />

			<?php esc_html_e( 'Do not navigate away from this page until this script is done or all items will not be generated. You will be notified via this page when the process is completed.', 'page-generator' ); ?>
		</p>

		<!-- Progress Bar -->
		<div id="progress-bar"></div>
		<div id="progress">
			<span id="progress-number"><?php echo esc_html( $settings['resumeIndex'] ); ?></span>
			<span> / <?php echo esc_html( ( $settings['numberOfPosts'] + $settings['resumeIndex'] ) ); ?></span>
		</div>

		<!-- Status Updates -->
		<div id="log">
			<ul></ul>
		</div>

		<p>
			<!-- Cancel Button -->
			<a href="post.php?post=<?php echo esc_attr( $id ); ?>&amp;action=edit" class="button wpzinc-button-red page-generator-pro-generate-cancel-button">
				<?php esc_html_e( 'Stop Generation', 'page-generator' ); ?>
			</a>

			<!-- Return Button (display when generation routine finishes -->
			<a href="<?php echo esc_attr( $return_url ); ?>" class="button button-primary page-generator-pro-generate-return-button">
				<?php esc_html_e( 'Return to Group', 'page-generator' ); ?>
			</a>
		</p>
	</div>
</div>
