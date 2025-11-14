<?php
// Minimal test bootstrap. We don't bootstrap WordPress here; tests should
// avoid WordPress-specific globals or mock them as needed.

// Simple autoload for plugin files
spl_autoload_register( function ( $class ) {
    $prefix = 'Trackify_CAPI_';
    if ( 0 !== strpos( $class, $prefix ) ) {
        return;
    }

    $relative = strtolower( str_replace( $prefix, '', $class ) );
    $path = __DIR__ . '/../includes/' . 'class-' . str_replace( '_', '-', $relative ) . '.php';
    if ( file_exists( $path ) ) {
        require_once $path;
    }
} );

// Provide minimal WP functions used in tests to avoid fatal errors
if ( ! function_exists( 'esc_html' ) ) {
    function esc_html( $text ) {
        return htmlspecialchars( $text, ENT_QUOTES, 'UTF-8' );
    }
}

