<?php
/**
 * Calculator shortcode and rendering - FIXED VERSION
 *
 * @package    Cleaning_Price_Calculator
 * @subpackage Cleaning_Price_Calculator/public
 */

class CPC_Calculator {
    
    /**
     * Track if assets are enqueued
     */
    private static $assets_loaded = false;
    
    /**
     * Render the calculator shortcode
     */
    public function render_calculator($atts) {
        // Force load assets when shortcode is rendered
        $this->force_load_assets();
        
        $atts = shortcode_atts(array(
            'title' => __('Cleaning Price Calculator', 'cleaning-price-calculator'),
        ), $atts, 'cleaning_price_calculator');
        
        ob_start();
        require CPC_PLUGIN_DIR . 'public/views/calculator.php';
        return ob_get_clean();
    }
    
    /**
     * Force load CSS and JS assets
     */
    private function force_load_assets() {
        // Only load once
        if (self::$assets_loaded) {
            return;
        }
        
        self::$assets_loaded = true;
        
        // Enqueue CSS
        wp_enqueue_style(
            'cleaning-price-calculator',
            CPC_PLUGIN_URL . 'assets/css/frontend.css',
            array(),
            CPC_VERSION,
            'all'
        );
        
        // Add inline custom colors
        $primary_color = get_option('cpc_primary_color', '#2563eb');
        $button_color = get_option('cpc_button_color', '#10b981');
        $accent_color = get_option('cpc_accent_color', '#f59e0b');
        
        $custom_css = "
            .cpc-calculator {
                --cpc-primary: {$primary_color};
                --cpc-button: {$button_color};
                --cpc-accent: {$accent_color};
            }
        ";
        
        wp_add_inline_style('cleaning-price-calculator', $custom_css);
        
        // Enqueue JS
        wp_enqueue_script(
            'cleaning-price-calculator',
            CPC_PLUGIN_URL . 'assets/js/frontend.js',
            array('jquery'),
            CPC_VERSION,
            true
        );
        
        // Localize script
        wp_localize_script('cleaning-price-calculator', 'cpcFrontend', array(
            'ajaxurl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('cpc_frontend_nonce'),
            'currency' => get_option('cpc_currency', 'EUR'),
            'formDisplay' => get_option('cpc_quote_form_display', 'modal'),
            'strings' => array(
                'addRoom' => __('Please add at least one room', 'cleaning-price-calculator'),
                'removeRoom' => __('Remove', 'cleaning-price-calculator'),
                'selectRoomType' => __('Select Room Type', 'cleaning-price-calculator'),
                'area' => __('Area (m²)', 'cleaning-price-calculator'),
                'subtotal' => __('Subtotal', 'cleaning-price-calculator'),
                'total' => __('Total Price', 'cleaning-price-calculator'),
                'requiredField' => __('Please fill in all required fields', 'cleaning-price-calculator'),
                'invalidEmail' => __('Please enter a valid email address', 'cleaning-price-calculator'),
                'submitting' => __('Submitting...', 'cleaning-price-calculator'),
                'sending' => __('Sending your quote request...', 'cleaning-price-calculator'),
                'success' => __('Quote submitted successfully! We will contact you soon.', 'cleaning-price-calculator'),
                'error' => __('An error occurred. Please try again.', 'cleaning-price-calculator'),
            ),
        ));
    }
}