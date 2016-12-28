<?php
/**
 * Plugin Name:       Personalize Login
 * Description:       A plugin that replaces the WordPress login flow with a custom page.
 * Version:           1.0.0
 * Author:            Jarkko Laine
 * License:           GPL-2.0+
 * Text Domain:       personalize-login
 */
 
class Personalize_Login_Plugin {
    /**
     * Initializes the plugin.
     *
     * To keep the initialization fast, only add filter and action
     * hooks in the constructor.
     */
    public function __construct() {
        add_shortcode( 'custom-login-form', array( $this, 'render_login_form' ) );
    }
    
    /**
    * Plugin activation hook.
    *
    * Creates all WordPress pages needed by the plugin.
    */
    public static function plugin_activated() {
        // Information needed for creating the plugin's pages
        $page_definitions = array(
            'member-login' => array(
                'title' => __( 'Log In', 'personalize-login' ),
                'content' => '[custom-login-form]'
            ),
            'member-account' => array(
                'title' => __( 'Your Account', 'personalize-login' ),
                'content' => '[account-info]'
            ),
        );
    
        foreach ( $page_definitions as $slug => $page ) {
            // Check that the page doesn't exist already
            $query = new WP_Query( 'pagename=' . $slug );
            if ( ! $query->have_posts() ) {
                // Add the page using the data from the array above
                wp_insert_post(
                    array(
                        'post_content'   => $page['content'],
                        'post_name'      => $slug,
                        'post_title'     => $page['title'],
                        'post_status'    => 'publish',
                        'post_type'      => 'page',
                        'ping_status'    => 'closed',
                        'comment_status' => 'closed',
                    )
                );
            }
        }
    }
    
    /**
    * A shortcode for rendering the login form.
    *
    * @param  array   $attributes  Shortcode attributes.
    * @param  string  $content     The text content for shortcode. Not used.
    *
    * @return string  The shortcode output
    */
    public function render_login_form( $attributes, $content = null ) {
        // Parse shortcode attributes
        $default_attributes = array( 'show_title' => false );
        $attributes = shortcode_atts( $default_attributes, $attributes );
        $show_title = $attributes['show_title'];
    
        if ( is_user_logged_in() ) {
            return __( 'You are already signed in.', 'personalize-login' );
        }
        
        // Pass the redirect parameter to the WordPress login functionality: by default,
        // don't specify a redirect, but if a valid redirect URL has been passed as
        // request parameter, use it.
        $attributes['redirect'] = '';
        if ( isset( $_REQUEST['redirect_to'] ) ) {
            $attributes['redirect'] = wp_validate_redirect( $_REQUEST['redirect_to'], $attributes['redirect'] );
        }
        
        // Render the login form using an external template
        return $this->get_template_html( 'login-form', $attributes );
    }

    /**
    * Renders the contents of the given template to a string and returns it.
    *
    * @param string $template_name The name of the template to render (without .php)
    * @param array  $attributes    The PHP variables for the template
    *
    * @return string               The contents of the template.
    */
    private function get_template_html( $template_name, $attributes = null ) {
        if ( ! $attributes ) {
            $attributes = array();
        }
    
        ob_start();
    
        do_action( 'personalize_login_before_' . $template_name );
    
        require( 'templates/' . $template_name . '.php');
    
        do_action( 'personalize_login_after_' . $template_name );
    
        $html = ob_get_contents();
        ob_end_clean();
    
        return $html;
    }     
}
 
// Initialize the plugin
$personalize_login_pages_plugin = new Personalize_Login_Plugin();

// Create the custom pages at plugin activation
register_activation_hook( __FILE__, array( 'Personalize_Login_Plugin', 'plugin_activated' ) );