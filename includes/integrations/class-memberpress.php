<?php
/**
 * MemberPress Integration
 *
 * MemberPress üyelik işlemlerini izler: üyelik satın alma, abonelik yenileme,
 * üyelik görüntüleme gibi olayları Trackify CAPI'ye gönderir.
 *
 * @package Trackify_CAPI
 * @since 2.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'Trackify_CAPI_MemberPress' ) ) {
	class Trackify_CAPI_MemberPress extends Trackify_CAPI_Abstract_Integration {

		/**
		 * Integration ID.
		 * @var string
		 */
		protected $id = 'memberpress';

		/**
		 * Integration name.
		 * @var string
		 */
		protected $name = 'MemberPress';

		/**
		 * Constructor.
		 *
		 * @param mixed $settings Settings (unused, for core compatibility).
		 * @param mixed $event_handler Event handler (unused, for core compatibility).
		 */
		public function __construct( $settings = null, $event_handler = null ) {
			// Accept optional args to be compatible with core instantiation.
			parent::__construct();
		}

		/**
		 * Initialize hooks.
		 */
		protected function init_hooks() {
			// Purchase: yeni üyelik satın alındığında.
			add_action( 'mepr-event-subscription-created', array( $this, 'track_purchase' ), 10, 2 );

			// Subscription renewed.
			add_action( 'mepr-event-subscription-renewed', array( $this, 'track_subscribe' ), 10, 2 );

			// ViewContent: membership detay sayfası (örnek).
			add_action( 'wp', array( $this, 'maybe_track_membership_view' ) );
		}

		/**
		 * Check if plugin is active.
		 *
		 * @return bool True if MemberPress is active.
		 */
		protected function is_plugin_active() {
			return class_exists( 'MeprSubscription' ) || class_exists( 'MemberPress' );
		}

		public function maybe_track_membership_view() {
			if ( ! is_singular() ) {
				return;
			}

			global $post;

			if ( ! $post ) {
				return;
			}

			// Basit heuristic: MemberPress membership post type 'memberpressproduct'
			if ( isset( $post->post_type ) && in_array( $post->post_type, array( 'memberpressproduct', 'mepr-product' ), true ) ) {
				$membership_id = $post->ID;
				$event_id = trackify_capi_generate_event_id( 'view', $membership_id );

				if ( $this->is_duplicate_event( $event_id ) ) {
					return;
				}

				$custom_data = array(
					'content_ids' => array( (string) $membership_id ),
					'content_type' => 'product',
					'content_name' => get_the_title( $membership_id ),
					'value' => 0,
					'currency' => $this->get_currency(),
				);

				$this->send_event( 'ViewContent', $custom_data, $this->get_user_data(), $event_id );
			}
		}

		/**
		 * Satın alma event'i (MemberPress subscription created)
		 * @param int $subscription_id
		 * @param object $subscription
		 */
		public function track_purchase( $subscription_id, $subscription ) {
			// Subscription objesinde ürün/plan bilgisi olabilir
			$event_id = trackify_capi_generate_event_id( 'purchase', $subscription_id );

			if ( $this->is_duplicate_event( $event_id ) ) {
				return;
			}

			$product_id = null;
			$amount = 0;

			if ( is_object( $subscription ) ) {
				if ( isset( $subscription->product_id ) ) {
					$product_id = $subscription->product_id;
				}
				if ( isset( $subscription->amount ) ) {
					$amount = (float) $subscription->amount;
				}
			}

			$content_ids = $product_id ? array( (string) $product_id ) : array();

			$custom_data = array(
				'content_ids' => $content_ids,
				'content_type' => 'product',
				'content_name' => $product_id ? get_the_title( $product_id ) : 'MemberPress Membership',
				'value' => (float) $amount,
				'currency' => $this->get_currency(),
			);

			$this->send_event( 'Purchase', $custom_data, $this->get_user_data(), $event_id );

			// Mark tracked meta to avoid duplicate
			if ( function_exists( 'update_post_meta' ) ) {
				update_post_meta( $subscription_id, '_trackify_capi_purchase_tracked', 1 );
			}
		}

		/**
		 * Subscription renewed handler
		 */
		public function track_subscribe( $subscription_id, $subscription ) {
			$event_id = trackify_capi_generate_event_id( 'subscribe', $subscription_id );

			if ( $this->is_duplicate_event( $event_id ) ) {
				return;
			}

			$amount = isset( $subscription->amount ) ? (float) $subscription->amount : 0;

			$custom_data = array(
				'content_ids' => isset( $subscription->product_id ) ? array( (string) $subscription->product_id ) : array(),
				'content_type' => 'product',
				'content_name' => isset( $subscription->product_id ) ? get_the_title( $subscription->product_id ) : 'MemberPress Membership',
				'value' => $amount,
				'currency' => $this->get_currency(),
			);

			$this->send_event( 'Subscribe', $custom_data, $this->get_user_data(), $event_id );
		}
	}
}

