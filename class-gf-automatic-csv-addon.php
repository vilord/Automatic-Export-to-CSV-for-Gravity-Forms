<?php

GFForms::include_addon_framework();

class GFAutomaticCSVAddOn extends GFAddOn {

    protected $_version = GF_AUTOMATIC_CSV_VERSION;
    protected $_min_gravityforms_version = '1.9';
    protected $_slug = 'gravityforms-automatic-csv-export';
    protected $_path = 'gravityforms-automatic-csv-export/gravityforms-automatic-csv-export.php';
    protected $_full_path = __FILE__;
    protected $_title = 'Gravity Forms Automatic CSV Export Add-On';
    protected $_short_title = 'Automatic CSV Export';

    private static $_instance = null;

    public static function get_instance() {
        if ( self::$_instance == null ) {
            self::$_instance = new GFAutomaticCSVAddOn();
        }

        return self::$_instance;
    }

    public function init() {
        parent::init();
        add_filter( 'gform_submit_button', array( $this, 'form_submit_button' ), 10, 2 );
    }

    public function scripts() {
        $scripts = array(
            array(
                'handle'  => 'my_script_js',
                'src'     => $this->get_base_url() . '/js/my_script.js',
                'version' => $this->_version,
                'deps'    => array( 'jquery' ),
                'strings' => array(
                    'first'  => esc_html__( 'First Choice', 'csvexport' ),
                    'second' => esc_html__( 'Second Choice', 'csvexport' ),
                    'third'  => esc_html__( 'Third Choice', 'csvexport' )
                ),
                'enqueue' => array(
                    array(
                        'admin_page' => array( 'form_settings' ),
                        'tab'        => 'csvexport'
                    )
                )
            ),

        );

        return array_merge( parent::scripts(), $scripts );
    }

    public function styles() {
        $styles = array(
            array(
                'handle'  => 'my_styles_css',
                'src'     => $this->get_base_url() . '/css/my_styles.css',
                'version' => $this->_version,
                'enqueue' => array(
                    array( 'field_types' => array( 'poll' ) )
                )
            )
        );

        return array_merge( parent::styles(), $styles );
    }

    function form_submit_button( $button, $form ) {
        $settings = $this->get_form_settings( $form );
        if ( isset( $settings['enabled'] ) && true == $settings['enabled'] ) {
            $text   = $this->get_plugin_setting( 'mytextbox' );
            $button = "<div>{$text}</div>" . $button;
        }

        return $button;
    }

    // public function plugin_settings_fields() {
    //     return array(
    //         array(
    //             'title'  => esc_html__( 'Simple Add-On Settings', 'csvexport' ),
    //             'fields' => array(
    //                 array(
    //                     'name'              => 'mytextbox',
    //                     'tooltip'           => esc_html__( 'This is the tooltip', 'csvexport' ),
    //                     'label'             => esc_html__( 'This is the label', 'csvexport' ),
    //                     'type'              => 'text',
    //                     'class'             => 'small',
    //                     'feedback_callback' => array( $this, 'is_valid_setting' ),
    //                 )
    //             )
    //         )
    //     );
    // }

    public function form_settings_fields( $form ) {
        return array(
            array(
                'title'  => esc_html__( 'Simple Form Settings', 'csvexport' ),
                'fields' => array(
                    array(
                        'label'   => esc_html__( 'Enable Automatic export', 'csvexport' ),
                        'type'    => 'checkbox',
                        'name'    => 'enable_export',
                        'tooltip' => esc_html__( 'This will enable the automatic export of csv for this form.', 'csvexport' ),
                        'choices' => array(
                            array(
                                'label' => esc_html__( 'Enabled', 'csvexport' ),
                                'name'  => 'enabled',
                            ),
                        ),
                    ),

                    array(
                        'label'   => esc_html__( 'CSV export frequency', 'csvexport' ),
                        'type'    => 'select',
                        'name'    => 'csv_export_frequency',
                        'tooltip' => esc_html__( 'This determines how frequently the export will be run and emailed to you.', 'csvexport' ),
                        'choices' => array(
                            array(
                                'label' => esc_html__( 'Hourly', 'csvexport' ),
                                'value' => 'hourly',
                            ),
                            array(
                                'label' => esc_html__( 'Daily', 'csvexport' ),
                                'value' => 'daily',
                            ),
                            array(
                                'label' => esc_html__( 'Weekly', 'csvexport' ),
                                'value' => 'weekly',
                            ),
                            array(
                                'label' => esc_html__( 'Monthly', 'csvexport' ),
                                'value' => 'monthly',
                            ),
                        ),
                    ),
                    array(
                        'label'             => esc_html__( 'Email Address', 'csvexport' ),
                        'type'              => 'text',
                        'name'              => 'email_address',
                        'tooltip'           => esc_html__( 'The csv will be sent to this email address', 'csvexport' ),
                        'class'             => 'medium',
                        // 'feedback_callback' => array( $this, 'is_valid_setting' ),
                    ),
                ),
            ),
        );
    }

    public function settings_my_custom_field_type( $field, $echo = true ) {
        echo '<div>' . esc_html__( 'My custom field contains a few settings:', 'csvexport' ) . '</div>';

        // get the text field settings from the main field and then render the text field
        $text_field = $field['args']['text'];
        $this->settings_text( $text_field );

        // get the checkbox field settings from the main field and then render the checkbox field
        $checkbox_field = $field['args']['checkbox'];
        $this->settings_checkbox( $checkbox_field );
    }

    public function is_valid_setting( $value ) {
        return strlen( $value ) < 10;
    }

}