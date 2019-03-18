<?php
/**
 * Thrive Themes - https://thrivethemes.com
 *
 * @package thrive-visual-editor
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Silence is golden!
}
$name_placeholder = '<span class="element-name"></span>';
?>

<h2 class="tcb-modal-title">
	<?php if ( defined( 'TVE_STAGING_TEMPLATES' ) && TVE_STAGING_TEMPLATES ) : ?>
		<span style="color: #810000"><?php echo __( 'Warning! The templates listed here are only used for testing purposes', 'thrive-cb' ); ?></span>
	<?php else : ?>
		<?php echo sprintf( esc_html( __( 'Choose %s Template', 'thrive-cb' ) ), $name_placeholder ); ?>
	<?php endif ?>
</h2>
<div class="status tpl-ajax-status">Fetching data ...</div>
<div class="error-container"></div>
<div class="tve-templates-wrapper">
	<div class="content-templates" id="cloud-templates"></div>
</div>

<div class="tcb-modal-footer clearfix mt-20 control-grid flex-end">
	<button type="button" class="tcb-right tve-button medium green tcb-modal-save">
		<?php echo __( 'Choose Template', 'thrive-cb' ) ?>
	</button>
</div>

