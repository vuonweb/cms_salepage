<?php
/**
 * Thrive Themes - https://thrivethemes.com
 *
 * @package TCB2.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Silence is golden
}
?>
<div id="tve-lead_generation_checkbox-component" class="tve-component" data-view="LeadGenerationCheckbox">
    <div class="dropdown-header" data-prop="docked">
		<?php echo __( 'Main Checkbox', 'thrive-cb' ); ?>
        <i></i>
    </div>

    <div class="dropdown-content">
        <div class="control-toggle c-regular">
            <div class="tve-control" data-key="columns_number"></div>
            <hr>
            <div class="tve-control" data-key="required" data-view="Checkbox"></div>
        </div>
        <div class="control-toggle c-consent">
            <div class="tve-control" data-key="consent" data-view="LGConsent"></div>
        </div>
    </div>
</div>
