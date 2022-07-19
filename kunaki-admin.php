<?php
class WooCommerceKunakiSettingsPage
{
    /**
     * Holds the values to be used in the fields callbacks
     */
    private $options;

    /**
     * Start up
     */
    public function __construct()
    {
        add_action( 'admin_menu', array( $this, 'add_plugin_page' ) );
        add_action( 'admin_init', array( $this, 'page_init' ) );
    }

    /**
     * Add options page
     */
    public function add_plugin_page()
    {
        // This page will be under "Settings"
        add_options_page(
            'Kunaki Settings Admin',
            'Kunaki Settings',
            'manage_options',
            'kunaki-admin',
            array( $this, 'create_admin_page' )
        );
    }

    /**
     * Options page callback
     */
    public function create_admin_page()
    {
        // Set class property
        $this->options1 = get_option( '_kunaki_email' );
        $this->options2 = get_option( '_kunaki_password' );
        $this->options4 = get_option( '_kunaki_max_domestic_cost' );
        $this->options5 = get_option( '_kunaki_max_international_cost' );
        ?>
        <div class="wrap">
            <h2>Kunaki Settings</h2>
            <form method="post" action="options.php">
            <?php
                // This prints out all hidden setting fields
                settings_fields( 'kunaki_group' );
                do_settings_sections( 'kunaki-admin' );
                submit_button();
            ?>
            </form>
        </div>
        <?php
    }

    /**
     * Register and add settings
     */
    public function page_init()
    {
        register_setting(
            'kunaki_group', // Option group
            '_kunaki_email', // Option name
            array( $this, 'sanitize' ) // Sanitize
        );

        register_setting(
            'kunaki_group', // Option group
            '_kunaki_password', // Option name
            array( $this, 'sanitize' ) // Sanitize
        );

        register_setting(
            'kunaki_group', // Option group
            '_kunaki_max_domestic_cost', // Option name
            array( $this, 'sanitize' ) // Sanitize
        );

        register_setting(
            'kunaki_group', // Option group
            '_kunaki_max_international_cost', // Option name
            array( $this, 'sanitize' ) // Sanitize
        );

        add_settings_section(
            'setting_section_id', // ID
            'Kunaki Settings', // Title
            array( $this, 'print_section_info' ), // Callback
            'kunaki-admin' // Page
        );

        add_settings_field(
            '_kunaki_email', // ID
            'Kunaki Email Address', // Title
            array( $this, 'kunaki_email_callback' ), // Callback
            'kunaki-admin', // Page
            'setting_section_id' // Section
        );

        add_settings_field(
            '_kunaki_password', // ID
            'Kunaki Password', // Title
            array( $this, 'kunaki_password_callback' ), // Callback
            'kunaki-admin', // Page
            'setting_section_id' // Section
        );

        add_settings_field(
            '_kunaki_max_domestic_cost', // ID
            'Maximum Domestic Shipping Cost', // Title
            array( $this, 'kunaki_max_domestic_cost_callback' ), // Callback
            'kunaki-admin', // Page
            'setting_section_id' // Section
        );

        add_settings_field(
            '_kunaki_max_international_cost', // ID
            'Maximum International Shipping Cost', // Title
            array( $this, 'kunaki_max_international_cost_callback' ), // Callback
            'kunaki-admin', // Page
            'setting_section_id' // Section
        );
    }

    /**
     * Sanitize each setting field as needed
     *
     * @param array $input Contains all settings fields as array keys
     */
    public function sanitize( $input )
    {
        $new_input = array();
        if( isset( $input['_kunaki_email'] ) )
            $new_input['_kunaki_email'] = $input['_kunaki_email'];
        if( isset( $input['_kunaki_password'] ) )
            $new_input['_kunaki_password'] = $input['_kunaki_password'];
        if( isset( $input['_kunaki_max_domestic_cost'] ) )
            $new_input['_kunaki_max_domestic_cost'] = $input['_kunaki_max_domestic_cost'];
        if( isset( $input['_kunaki_max_international_cost'] ) )
            $new_input['_kunaki_max_international_cost'] = $input['_kunaki_max_international_cost'];

        return $new_input;
    }

    /**
     * Print the Section text
     */
    public function print_section_info()
    {
        print 'Enter the settings for Kunaki:';
    }

    /**
     * Get the settings option array and print one of its values
     */
    public function kunaki_email_callback()
    {
        printf(
            '<input type="text" id="_kunaki_email" name="_kunaki_email[_kunaki_email]" value="%s" />',
            isset( $this->options1['_kunaki_email'] ) ? esc_attr( $this->options1['_kunaki_email']) : ''
        );
    }

    public function kunaki_password_callback()
    {
        printf(
            '<input type="text" id="_kunaki_password" name="_kunaki_password[_kunaki_password]" value="%s" />',
            isset( $this->options2['_kunaki_password'] ) ? esc_attr( $this->options2['_kunaki_password']) : ''
        );
    }

    public function kunaki_max_domestic_cost_callback()
    {
        printf(
            '<input type="text" id="_kunaki_max_domestic_cost" name="_kunaki_max_domestic_cost[_kunaki_max_domestic_cost]" value="%s" />',
            isset( $this->options4['_kunaki_max_domestic_cost'] ) ? esc_attr( $this->options4['_kunaki_max_domestic_cost']) : ''
        );
    }

    public function kunaki_max_international_cost_callback()
    {
        printf(
            '<input type="text" id="_kunaki_max_international_cost" name="_kunaki_max_international_cost[_kunaki_max_international_cost]" value="%s" />',
            isset( $this->options5['_kunaki_max_international_cost'] ) ? esc_attr( $this->options5['_kunaki_max_international_cost']) : ''
        );
    }
}

if( is_admin() ) $woocommerce_kunaki_page = new WooCommerceKunakiSettingsPage();
