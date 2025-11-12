<?php
/**
 * WooCommerce Integration Stub
 *
 * Bu dosya eksik olduğu için basit bir proxy/stub sağlar. Gerçek entegrasyon
 * zaten `class-woocommerce.php` içinde tanımlıysa, bu stub onun örneğini
 * yaratır; aksi halde sınıf boş bir yapı sağlar ki `class-core.php` require_once
 * çağrısını hata vermeden gerçekleştirsin.
 *
 * @package Trackify_CAPI
 * @since 2.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

if ( ! class_exists( 'Trackify_CAPI_WooCommerce_Integration' ) ) {
	class Trackify_CAPI_WooCommerce_Integration {
		/**
		 * Settings component.
		 * @var mixed
		 */
		protected $settings;

		/**
		 * Event handler component.
		 * @var mixed
		 */
		protected $event_handler;

		/**
		 * Underlying implementation if available.
		 * @var object|null
		 */
		protected $impl = null;

		/**
		 * Constructor.
		 *
		 * Accepts the same args as class-core.php passes but ignores them if
		 * not needed. If Trackify_CAPI_Woocommerce (mevcut entegrasyon) sınıfı
		 * tanımlıysa onun örneğini oluşturur.
		 *
		 * @param mixed $settings Settings component.
		 * @param mixed $event_handler Event handler component.
		 */
		public function __construct( $settings = null, $event_handler = null ) {
			$this->settings      = $settings;
			$this->event_handler = $event_handler;

			if ( class_exists( 'Trackify_CAPI_Woocommerce' ) ) {
				// Mevcut entegrasyon sınıfı kendi constructor'ı içinde hook'ları bağlar.
				try {
					$this->impl = new Trackify_CAPI_Woocommerce();
				} catch ( Exception $e ) {
					// Hata olursa sessizce bırak.
					$this->impl = null;
				}
			}
		}

		/**
		 * Magic proxy method.
		 *
		 * Çağrılan metot gerçek implementasyonda varsa aktar.
		 *
		 * @param string $name Method name.
		 * @param array  $arguments Method arguments.
		 */
		public function __call( $name, $arguments ) {
			if ( $this->impl && method_exists( $this->impl, $name ) ) {
				return call_user_func_array( array( $this->impl, $name ), $arguments );
			}

			return null;
		}
	}
}
