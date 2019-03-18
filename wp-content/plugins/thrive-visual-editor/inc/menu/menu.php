<?php
/**
 * Thrive Themes - https://thrivethemes.com
 *
 * @package thrive-visual-editor
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Silence is golden
}
$admin_base_url = admin_url( '/', is_ssl() ? 'https' : 'admin' );
// for some reason, the above line does not work in some instances
if ( is_ssl() ) {
	$admin_base_url = str_replace( 'http://', 'https://', $admin_base_url );
}
?>

<div id="tve-menu-component" class="tve-component" data-view="CustomMenu">
	<div class="action-group">
		<div class="dropdown-header" data-prop="docked">
			<div class="group-description">
				<?php echo __( 'Main Options', 'thrive-cb' ); ?>
			</div>
			<i></i>
		</div>
		<div class="dropdown-content">
			<div class="tve-menu-display-control">
				<div class="tve-control" data-view="MenuDisplay"></div>
			</div>
			<hr>
			<div class="tve-control tve-choose-menu-template" data-view="ModalPicker"></div>
			<div class="control-grid hide-tablet hide-mobile">
				<span class="blue-text">
					<svg class="tcb-icon tcb-icon-info"><use xlink:href="#icon-info"></use></svg>
					<a class="tve-edit-menu tve-wpmenu-info" href="<?php echo $admin_base_url; ?>nav-menus.php?action=edit&menu=0" target="_blank">
						<?php echo __( 'Click here to edit WordPress menu.', 'thrive-cb' ); ?>
					</a>
				</span>
			</div>
			<hr>
			<div class="tve-menu-spacing-control">
				<div class="tve-control btn-group-light" data-view="MenuSpacing"></div>
				<div class="tve-control tve-menu-spacing tve-menu-spacing-horizontal" data-view="HorizontalSpacing"></div>
				<div class="tve-control tve-menu-spacing tve-menu-spacing-vertical" data-view="VerticalSpacing"></div>
				<div class="tve-control tve-menu-spacing tve-menu-spacing-between" data-view="BetweenSpacing"></div>
			</div>
			<hr>
			<div class="tve-control tve-menu-style mt-10" data-key="MenuStyle" data-initializer="menuStyle"></div>
			<div class="tve-control tve-menu-dropdown-icon" data-key="DropdownIcon" data-initializer="dropdownIcon"></div>
			<div class="mobile-display hide-desktop">
				<div class="tve-control tve-mobile-icon mt-10" data-key="MobileIcon" data-initializer="mobileIcon"></div>
				<div class="tve-control tve-mobile-icon mt-10" data-key="IconColor" data-initializer="iconColor"></div>
				<div class="tve-control tve-mobile-icon mt-10" data-key="IconSize" data-initializer="iconSize"></div>
			</div>
			<div class="mobile-display mt-10">
				<div class="tve-control" data-view="MenuState"></div>
				<div class="tve-control tve-mobile-side mt-10" data-key="MobileSide" data-initializer="mobileSide"></div>
			</div>
			<div class="hide-tablet hide-mobile">
				<div class="tve-add-menu-item">
					<hr>
					<div class="control-grid pb-0">
						<div class="group-label grey-text mt-5">
							<?php echo __( 'Menu Items', 'thrive-cb' ) ?>
						</div>
						<button class="tve-button blue click" data-fn-click="add_menu_item">
							<?php echo __( 'Add new', 'thrive-cb' ) ?>
						</button>
					</div>
				</div>
				<div class="tve-control tve-order-list" data-key="OrderList" data-initializer="orderItems"></div>
			</div>
		</div>
	</div>
</div>

