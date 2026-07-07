<?php
/**
 * HSM Login Customization Class
 *
 * Customizes the WordPress login screen with logo and branding
 *
 * @package HSM
 * @since 1.0.0
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Procedural shim for compatibility with themes/plugins that expect a global function
 * like `custom_login_logo`. This ensures the login styles are printed even if
 * other code hooks into the same action or overrides styles.
 */
if (!function_exists('hsm_custom_login_logo_shim')) {
    function hsm_custom_login_logo_shim() {
        // Debug log so we can tell if the shim runs
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log('HSM Login Shim: running hsm_custom_login_logo_shim');
        }
        // Attempt to load settings and print styles directly
        if (class_exists('HSM_General_Settings')) {
            try {
                $general_settings = new HSM_General_Settings();
                $login_customizer = new HSM_Login_Customization($general_settings);
                // Directly output styles (the method guards against disabled setting)
                $login_customizer->enqueue_login_styles();
            } catch (Exception $e) {
                if (defined('WP_DEBUG') && WP_DEBUG) {
                    error_log('HSM Login Shim error: ' . $e->getMessage());
                }
            }
        }
    }

    // Hook with a high priority so this runs after most other handlers and wins specificity
    add_action('login_enqueue_scripts', 'hsm_custom_login_logo_shim', 999);
    // Also hook into login_head to ensure styles are printed in environments that
    // rely on the head action instead of enqueue scripts.
    add_action('login_head', 'hsm_custom_login_logo_shim', 999);
}

class HSM_Login_Customization {

    /**
     * General settings instance
     *
     * @var HSM_General_Settings
     */
    private $general_settings;

    /**
     * Constructor
     *
     * @param HSM_General_Settings $general_settings General settings instance
     */
    public function __construct($general_settings) {
        $this->general_settings = $general_settings;
        $this->init_hooks();
    }

    /**
     * Initialize hooks
     */
    private function init_hooks() {
    // Always register hooks, but only apply styles if enabled
    add_action('login_enqueue_scripts', array($this, 'enqueue_login_styles'));
    // Also hook into login_head with high priority for compatibility with themes/plugins
    add_action('login_head', array($this, 'enqueue_login_styles'), 999);

        // Modify login header URL and title only if customization is enabled
        if ($this->general_settings->is_login_customization_enabled()) {
            add_filter('login_headerurl', array($this, 'login_header_url'));
            add_filter('login_headertitle', array($this, 'login_header_title'));
            add_action('login_footer', array($this, 'login_footer_content'));
        }
    }

    /**
     * Enqueue login page styles
     */
    public function enqueue_login_styles() {
        // Only apply custom styles if login customization is enabled
        if (!$this->general_settings->is_login_customization_enabled()) {
            return;
        }

        $logo_url = $this->general_settings->get_login_logo_url();
        $logo_width = $this->general_settings->get_login_logo_width();
        $logo_height = $this->general_settings->get_login_logo_height();
        $bg_color = $this->general_settings->get_login_background_color();
        $button_color = $this->general_settings->get_login_button_color();
        $button_hover_color = $this->general_settings->get_login_button_hover_color();

        ?>
        <style type="text/css">
            /* Custom login page styles */
            body.login {
                background: <?php echo esc_attr($bg_color); ?> !important;
            }

            <?php if (!empty($logo_url)): ?>
            /* Replace WP logo with brand logo; also hide any <img> fallback */
            .login h1 a {
                background-image: url('<?php echo esc_url($logo_url); ?>') !important;
                background-size: contain !important;
                background-repeat: no-repeat !important;
                background-position: center !important;
                width: <?php echo esc_attr($logo_width); ?>px !important;
                height: <?php echo esc_attr($logo_height); ?>px !important;
                margin: 0 auto !important;
                display: block !important;
                text-indent: -9999px !important; /* hide text fallback */
                overflow: hidden !important;
            }

            /* Some themes/plugins output an <img> inside the .login h1 a; hide it to ensure our background shows */
            .login h1 a img,
            #login h1 a img {
                display: none !important;
                visibility: hidden !important;
                height: 0 !important;
                width: 0 !important;
            }
            <?php endif; ?>

            .login form {
                box-shadow: 0 1px 3px rgba(0,0,0,.13) !important;
            }

            .wp-core-ui .button-primary {
                background: <?php echo esc_attr($button_color); ?> !important;
                border-color: <?php echo esc_attr($button_color); ?> !important;
                box-shadow: 0 1px 0 <?php echo esc_attr($button_color); ?> !important;
            }

            .wp-core-ui .button-primary:hover,
            .wp-core-ui .button-primary:focus {
                background: <?php echo esc_attr($button_hover_color); ?> !important;
                border-color: <?php echo esc_attr($button_hover_color); ?> !important;
                box-shadow: 0 1px 0 <?php echo esc_attr($button_hover_color); ?> !important;
            }

            .login #nav,
            .login #backtoblog {
                text-align: center;
            }

            .login #nav a,
            .login #backtoblog a {
                color: #50575e !important;
            }

            .login #nav a:hover,
            .login #backtoblog a:hover {
                color: <?php echo esc_attr($button_color); ?> !important;
            }
        </style>
        <?php
    }

    /**
     * Modify login header URL
     *
     * @param string $url Default login header URL
     * @return string Modified URL
     */
    public function login_header_url($url) {
        return home_url('/');
    }

    /**
     * Modify login header title
     *
     * @param string $title Default login header title
     * @return string Modified title
     */
    public function login_header_title($title) {
        return get_bloginfo('name') . ' - ' . __('Login', 'hsm-stripe');
    }

    /**
     * Add custom content to login footer
     */
    public function login_footer_content() {
        // Add debug info for troubleshooting
        echo '<!-- HSM Login Customization Active - ' . esc_html($this->general_settings->is_login_customization_enabled() ? 'Enabled' : 'Disabled') . ' -->';
        if ($this->general_settings->is_login_customization_enabled()) {
            $logo_url = $this->general_settings->get_login_logo_url();
            echo '<!-- Logo URL: ' . esc_html($logo_url ? $logo_url : 'Not set') . ' -->';
        }
    }

    /**
     * Get login customization settings for admin display
     *
     * @return array
     */
    public function get_settings_fields() {
        return array(
            'enable_login_customization' => array(
                'title' => __('Enable Login Customization', 'hsm-stripe'),
                'type' => 'checkbox',
                'description' => __('Enable custom branding for the WordPress login page.', 'hsm-stripe'),
                'default' => false
            ),
            'login_logo_url' => array(
                'title' => __('Login Logo URL', 'hsm-stripe'),
                'type' => 'text',
                'description' => __('URL of the logo image to display on the login page. Recommended size: 320x84px.', 'hsm-stripe'),
                'default' => '',
                'placeholder' => 'https://example.com/logo.png'
            ),
            'login_logo_width' => array(
                'title' => __('Logo Width (px)', 'hsm-stripe'),
                'type' => 'number',
                'description' => __('Width of the login logo in pixels.', 'hsm-stripe'),
                'default' => 320,
                'min' => 100,
                'max' => 500
            ),
            'login_logo_height' => array(
                'title' => __('Logo Height (px)', 'hsm-stripe'),
                'type' => 'number',
                'description' => __('Height of the login logo in pixels.', 'hsm-stripe'),
                'default' => 84,
                'min' => 50,
                'max' => 200
            ),
            'login_background_color' => array(
                'title' => __('Background Color', 'hsm-stripe'),
                'type' => 'color',
                'description' => __('Background color for the login page.', 'hsm-stripe'),
                'default' => '#f0f0f1'
            ),
            'login_button_color' => array(
                'title' => __('Button Color', 'hsm-stripe'),
                'type' => 'color',
                'description' => __('Primary color for login buttons.', 'hsm-stripe'),
                'default' => '#2271b1'
            ),
            'login_button_hover_color' => array(
                'title' => __('Button Hover Color', 'hsm-stripe'),
                'type' => 'color',
                'description' => __('Hover color for login buttons.', 'hsm-stripe'),
                'default' => '#135e96'
            )
        );
    }
}