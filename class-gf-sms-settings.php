<?php
/**
 * Extend Gravity Forms plugin to include 'SMS Responder' settings page within GF settings menu.
 *
 * Adds menu to: WP-Admin > Forms > Settings > SMS Responder
 *
 * Allows for customizations:
 * - Enable/Disable entire SMS responder
 * - Input program page IDs for SMS responder
 * - Manage Quiq API key & authorization
 * - Manage text message content
 * - Data integrations: Set Supplier ID and Lead Routing Group for DoublePositive & Eloqua
 * - Other inputs as needed? (Ideally want to avoid having to do a new code release for minor changes that could just be handled via wp-admin)
 */

/**
 * GF_NUS_SMS_Settings class
 * GFAddOn ref: https://docs.gravityforms.com/gfaddon/
 *
 * @todo Better UI for API Keys form fields, to prevent accidental deletion/screw ups
 * @todo General form UI improvements (I'm sure GF has a lot of built-in helpers, look into at some point)
 * @todo Cleaner way to add/remove target program IDs (currently a comma-separated list of page IDs)
 */
class GF_NUS_SMS_Settings extends GFAddOn {

	/**
	 * Version number of the Add-On
	 *
	 * @var string
	 */
	protected $_version = GF_NUS_SMS_VER;

	/**
	 * Gravity Forms minimum version requirement
	 *
	 * @var string
	 */
	protected $_min_gravityforms_version = '2';

	/**
	 * URL-friendly identifier used for form settings, add-on settings, text domain localization...
	 *
	 * @var string
	 */
	protected $_slug = 'gf-nus-sms-settings';

	/**
	 * Relative path to the plugin from the plugins folder
	 *
	 * @var string
	 */
	protected $_path = 'nuedu-forms/nuedu-forms.php';

	/**
	 * Full path the the plugin
	 *
	 * @var string
	 */
	protected $_full_path = __FILE__;

	/**
	 * Title of the plugin to be used on the settings page, form settings and plugins page
	 *
	 * @var string
	 */
	protected $_title = 'Gravity Forms - SMS RFI Responder';

	/**
	 * Short version of the plugin title to be used on menus and other places where a less verbose string is useful
	 *
	 * @var string
	 */
	protected $_short_title = 'SMS Responder';

	/**
	 * If available, contains an instance of this class.
	 *
	 * @var object
	 */
	private static $_instance = null;


	/** Singleton */
	public static function get_instance() {
		if ( null === self::$_instance ) {
			self::$_instance = new self();
		}

		return self::$_instance;
	}



	/**
	 * GravityForms AddOn built-in method. Needed to populate admin settings.
	 */
	public function init_admin() {
		parent::init_admin();
	}


	/**
	 * Configures the settings which should be rendered on the add-on settings tab.
	 *
	 * @return array
	 */
	public function plugin_settings_fields() {
		return [
			[
				'title'       => esc_html__( 'Basic Configuration', 'national-university' ),
				'description' => esc_html__( 'SMS Responder will only be enabled on RFI forms for the specified page IDs.', 'national-university' ),
				'fields'      => [
					[
						'name'    => 'is_sms_pilot_enabled',
						'type'    => 'checkbox',
						'label'   => esc_html__( 'SMS Pilot Enabled', 'national-university' ),
						'tooltip' => esc_html__( 'Turn program on/off', 'national-university' ),
						'choices' => [
							[
								'name'  => 'sms_pilot_enabled',
								'label' => esc_html__( 'Enabled', 'national-university' ),
							],
						],
					],
					[
						'name'        => 'sms_pilot_program_page_ids',
						'type'        => 'text',
						'class'       => 'medium',
						'label'       => esc_html__( 'Program Page IDs', 'national-university' ),
						'description' => esc_html__( 'Comma delimited values.', 'national-university' ),
						'tooltip'     => esc_html__( 'The IDs of the program pages we want to use SMS responder on.', 'national-university' ),
					],
				],
			],
			[
				'title'       => esc_html__( 'SMS Configuration', 'national-university' ),
				'description' => esc_html__( 'SMS Configuration' ),
				'fields'      => [
					[
						'name'        => 'sms_quiq_message_content_1',
						'type'        => 'text',
						'class'       => 'medium',
						'label'       => esc_html__( 'SMS Message Content #1', 'national-university' ),
						'description' => esc_html__( 'Text content for SMS.', 'national-university' ),
						'tooltip'     => esc_html__( 'Plain text. Keep in mind character limit for text messages.', 'national-university' ),
					],
					[
						'name'        => 'sms_quiq_message_content_2',
						'type'        => 'text',
						'class'       => 'medium',
						'label'       => esc_html__( 'SMS Message Content #2', 'national-university' ),
						'description' => esc_html__( 'Text content for SMS.', 'national-university' ),
						'tooltip'     => esc_html__( 'Plain text. Keep in mind character limit for text messages.', 'national-university' ),
					],
					[
						'name'        => 'sms_quiq_contact_point',
						'type'        => 'text',
						'class'       => 'medium',
						'label'       => esc_html__( 'Quiq Contact Point Account (REQUIRED)', 'national-university' ),
						'description' => esc_html__( 'Contact Point that the SMS text will be sent from.', 'national-university' ),
						'tooltip'     => esc_html__( 'Configure via <a href="https://nus.goquiq.com/app/admin/contact-points" target="_blank">Quiq Admin Panel</a>', 'national-university' ),
					],
					[
						'name'        => 'sms_quiq_topic_1',
						'type'        => 'text',
						'class'       => 'medium',
						'label'       => esc_html__( 'Quiq Topic Category for SMS #1 (REQUIRED)', 'national-university' ),
						'description' => esc_html__( 'Topic Category that Quiq will group sent notifications into. ', 'national-university' ),
						'tooltip'     => esc_html__( 'Configure via <a href="Configure via <a href="https://nus.goquiq.com/app/admin/contact-points" target="_blank">Quiq Admin Panel</a>" target="_blank">Quiq Admin Panel</a>', 'national-university' ),
					],
					[
						'name'        => 'sms_quiq_topic_2',
						'type'        => 'text',
						'class'       => 'medium',
						'label'       => esc_html__( 'Quiq Topic Category for SMS #2 (REQUIRED)', 'national-university' ),
						'description' => esc_html__( 'Topic Category that Quiq will group sent notifications into. ', 'national-university' ),
						'tooltip'     => esc_html__( 'Configure via <a href="Configure via <a href="https://nus.goquiq.com/app/admin/contact-points" target="_blank">Quiq Admin Panel</a>" target="_blank">Quiq Admin Panel</a>', 'national-university' ),
					],
				],
			],
			[
				'title'       => esc_html__( 'DoublePositive Configuration', 'national-university' ),
				'description' => esc_html__( 'DoublePositive Configuration' ),
				'fields'      => [
					[
						'name'        => 'sms_doublepositive_supplier_id',
						'type'        => 'text',
						'class'       => 'medium',
						'label'       => esc_html__( 'Supplier ID for SMS Submissions', 'national-university' ),
						'description' => esc_html__( 'Prevents DoublePositive from phone call follow-up', 'national-university' ),
						'tooltip'     => esc_html__( '&nbsp;', 'national-university' ),
					],
				],
			],
			[
				'title'       => esc_html__( 'Quiq API Configuration', 'national-university' ),
				'description' => esc_html__( 'Quiq API Configuration. nus.goquiq.com -> Admin panel for user "NUS_QUIQ_API"', 'national-university' ),
				'fields'      => [
					[
						'name'        => 'sms_quiq_api_key',
						'type'        => 'text',
						'class'       => 'medium',
						'label'       => esc_html__( 'Quiq API Key', 'national-university' ),
						'description' => esc_html__( 'Quiq API Key', 'national-university' ),
						'tooltip'     => esc_html__( '&nbsp;', 'national-university' ),
					],
					[
						'name'        => 'sms_quiq_api_secret',
						'type'        => 'text',
						'class'       => 'medium',
						'label'       => esc_html__( 'Quiq API Secret', 'national-university' ),
						'description' => esc_html__( 'Quiq API Secret', 'national-university' ),
						'tooltip'     => esc_html__( '&nbsp;', 'national-university' ),
					],
					[
						'name'        => 'sms_quiq_api_access_token',
						'type'        => 'text',
						'class'       => 'medium',
						'label'       => esc_html__( 'Quiq API Access Token', 'national-university' ),
						'description' => esc_html__( 'Alternate Authentication Method. Not currently used, but placeholder in case we need it for future dev.', 'national-university' ),
						'tooltip'     => esc_html__( '&nbsp;', 'national-university' ),
					],
					[
						'name'        => 'sms_quiq_api_token_id',
						'type'        => 'text',
						'class'       => 'medium',
						'label'       => esc_html__( 'Quiq API Token ID', 'national-university' ),
						'description' => esc_html__( 'Alternate Authentication Method. Not currently used, but placeholder in case we need it for future dev.', 'national-university' ),
						'tooltip'     => esc_html__( '&nbsp;', 'national-university' ),
					],
				],
			],
		];
	}
}