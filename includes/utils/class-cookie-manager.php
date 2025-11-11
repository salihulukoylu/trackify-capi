<?php
/**
 * Cookie Manager
 * 
 * FBP ve FBC cookie yönetimi
 * 
 * @package Trackify_CAPI
 * @since 2.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class Trackify_CAPI_Cookie_Manager {
    
    /**
     * FBP cookie name
     * 
     * @var string
     */
    const FBP_COOKIE = '_fbp';
    
    /**
     * FBC cookie name
     * 
     * @var string
     */
    const FBC_COOKIE = '_fbc';
    
    /**
     * Cookie expiration (90 days)
     * 
     * @var int
     */
    const COOKIE_EXPIRATION = 7776000; // 90 days in seconds
    
    /**
     * Get FBP cookie
     * 
     * @return string|null
     */
    public static function get_fbp() {
        if ( isset( $_COOKIE[ self::FBP_COOKIE ] ) ) {
            return sanitize_text_field( wp_unslash( $_COOKIE[ self::FBP_COOKIE ] ) );
        }
        
        return null;
    }
    
    /**
     * Get FBC cookie
     * 
     * @return string|null
     */
    public static function get_fbc() {
        if ( isset( $_COOKIE[ self::FBC_COOKIE ] ) ) {
            return sanitize_text_field( wp_unslash( $_COOKIE[ self::FBC_COOKIE ] ) );
        }
        
        return null;
    }
    
    /**
     * Set FBP cookie
     * 
     * @param string $value
     * @return bool
     */
    public static function set_fbp( $value = null ) {
        if ( is_null( $value ) ) {
            $value = self::generate_fbp();
        }
        
        return setcookie(
            self::FBP_COOKIE,
            $value,
            time() + self::COOKIE_EXPIRATION,
            COOKIEPATH,
            COOKIE_DOMAIN,
            is_ssl(),
            true // HttpOnly
        );
    }
    
    /**
     * Set FBC cookie
     * 
     * @param string $value
     * @return bool
     */
    public static function set_fbc( $value ) {
        return setcookie(
            self::FBC_COOKIE,
            $value,
            time() + self::COOKIE_EXPIRATION,
            COOKIEPATH,
            COOKIE_DOMAIN,
            is_ssl(),
            true // HttpOnly
        );
    }
    
    /**
     * Generate FBP value
     * 
     * Format: fb.{domain_id}.{timestamp}
     * 
     * @return string
     */
    public static function generate_fbp() {
        $version = 'fb';
        $domain_id = 1; // Subdomain ID (1 for main domain)
        $timestamp = time() * 1000; // Milliseconds
        
        return "{$version}.{$domain_id}.{$timestamp}";
    }
    
    /**
     * Parse FBC from URL (fbclid parameter)
     * 
     * @return string|null
     */
    public static function parse_fbc_from_url() {
        if ( ! isset( $_GET['fbclid'] ) ) {
            return null;
        }
        
        $fbclid = sanitize_text_field( wp_unslash( $_GET['fbclid'] ) );
        $timestamp = time() * 1000; // Milliseconds
        
        return "fb.1.{$timestamp}.{$fbclid}";
    }
    
    /**
     * Initialize cookies
     * 
     * Automatically set FBP if not exists
     * Check for fbclid and set FBC
     */
    public static function init() {
        // FBP yoksa oluştur
        if ( ! self::get_fbp() ) {
            self::set_fbp();
        }
        
        // URL'de fbclid varsa FBC oluştur
        $fbc = self::parse_fbc_from_url();
        if ( $fbc && ! self::get_fbc() ) {
            self::set_fbc( $fbc );
        }
    }
    
    /**
     * Get all Facebook cookies
     * 
     * @return array
     */
    public static function get_all() {
        return array(
            'fbp' => self::get_fbp(),
            'fbc' => self::get_fbc(),
        );
    }
    
    /**
     * Clear all Facebook cookies
     */
    public static function clear_all() {
        setcookie( self::FBP_COOKIE, '', time() - 3600, COOKIEPATH, COOKIE_DOMAIN );
        setcookie( self::FBC_COOKIE, '', time() - 3600, COOKIEPATH, COOKIE_DOMAIN );
    }
}

// Auto-initialize on frontend
if ( ! is_admin() ) {
    add_action( 'init', array( 'Trackify_CAPI_Cookie_Manager', 'init' ) );
}