<?php
/**
 * Thrive Themes - https://thrivethemes.com
 *
 * @package TCB2.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Silence is golden
}

class TCB_Contact_Form_Submit_Element extends TCB_Element_Abstract {

	public function name() {
		return __( 'Contact Form Submit', 'thrive-cb' );
	}

	public function identifier() {
		return '.tve-cf-submit';
	}

	public function has_hover_state() {
		return true;
	}

	public function hide() {
		return true;
	}

	public function own_components() {

		$controls_default_config = array(
			'css_suffix' => ' button',
			'css_prefix' => '#tve_editor ',
		);

		$submit = array(
			'contact_form_submit' => array(
				'config' => array(
					'icon_side'   => array(
						'css_suffix' => ' .thrv_icon',
						'css_prefix' => '#tve_editor ',
						'config'     => array(
							'name'    => __( 'Icon Side', 'thrive-cb' ),
							'buttons' => array(
								array(
									'value' => 'left',
									'text'  => __( 'Left', 'thrive-cb' ),
								),
								array(
									'value' => 'right',
									'text'  => __( 'Right', 'thrive-cb' ),
								),
							),
						),
					),
					'MasterColor'  => array(
						'css_suffix' => ' button',
						'css_prefix' => '#tve_editor ',
						'config'     => array(
							'default'             => '000',
							'label'               => __( 'Master Color', 'thrive-cb' ),
							'important'           => true,
							'affected_components' => array( 'shadow', 'background', 'borders' ),
							'options'             => array(
								'showGlobals' => false,
							),
						),
					),
					'ButtonWidth'  => array(
						'css_prefix' => '#tve_editor ',
						'config'     => array(
							'default' => '100',
							'min'     => '10',
							'max'     => '100',
							'label'   => __( 'Button width', 'thrive-cb' ),
							'um'      => array( '%' ),
							'css'     => 'width',
						),
						'extends'    => 'Slider',
					),
					'ButtonAlign'  => array(
						'config'  => array(
							'name'    => __( 'Button Align', 'thrive-cb' ),
							'buttons' => array(
								array(
									'icon'    => 'a_left',
									'text'    => '',
									'value'   => 'left',
									'default' => true,
								),
								array(
									'icon'  => 'a_center',
									'text'  => '',
									'value' => 'center',
								),
								array(
									'icon'  => 'a_right',
									'text'  => '',
									'value' => 'right',
								),
								array(
									'icon'  => 'a_full-width',
									'text'  => '',
									'value' => 'justify',
								),
							),
						),
						'extends' => 'ButtonGroup',
					),
					'style'        => array(
						'css_suffix' => ' button',
						'css_prefix' => '#tve_editor ',
						'config'     => array(
							'label'   => __( 'Style', 'thrive-cb' ),
							'items'   => array(
								'default'      => __( 'Default', 'thrive-cb' ),
								'ghost'        => __( 'Ghost', 'thrive-cb' ),
								'rounded'      => __( 'Rounded', 'thrive-cb' ),
								'full_rounded' => __( 'Full Rounded', 'thrive-cb' ),
								'gradient'     => __( 'Gradient', 'thrive-cb' ),
								'elevated'     => __( 'Elevated', 'thrive-cb' ),
								'border_1'     => __( 'Border 1', 'thrive-cb' ),
								'border_2'     => __( 'Border 2', 'thrive-cb' ),
							),
							'default' => 'default',
						),
					),
				),
			),
			'typography'          => array(
				'config' => array(
					'FontSize'      => $controls_default_config,
					'FontColor'     => $controls_default_config,
					'TextAlign'     => $controls_default_config,
					'TextStyle'     => $controls_default_config,
					'TextTransform' => $controls_default_config,
					'FontFace'      => $controls_default_config,
					'LineHeight'    => $controls_default_config,
					'LetterSpacing' => $controls_default_config,
				),
			),
			'layout'              => array(
				'disabled_controls' => array(
					'Width',
					'Height',
					'Alignment',
					'.tve-advanced-controls',
				),
				'config'            => array(
					'MarginAndPadding' => $controls_default_config,
				),
			),
			'borders'             => array(
				'config' => array(
					'Borders' => $controls_default_config,
					'Corners' => $controls_default_config,
				),
			),
			'animation'           => array(
				'hidden' => true,
			),
			'responsive'          => array(
				'hidden' => true,
			),
			'background'          => array(
				'config' => array(
					'ColorPicker' => $controls_default_config,
					'PreviewList' => $controls_default_config,
				),
			),
			'shadow'              => array(
				'config' => $controls_default_config,
			),
			'styles-templates'    => array(
				'config' => array(
					'to' => 'button',
				),
			),
		);

		return array_merge( $submit, $this->shared_styles_component() );
	}
}
