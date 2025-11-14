<?php
/**
 * LifterLMS Integration
 *
 * LifterLMS kurs ve üyelik işlemlerini izler: kurs görüntüleme, kayıt,
 * tamamlama ve satın alma event'leri Trackify CAPI'ye gönderilir.
 *
 * @package Trackify_CAPI
 * @since 2.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'Trackify_CAPI_LifterLMS' ) ) {
	class Trackify_CAPI_LifterLMS extends Trackify_CAPI_Abstract_Integration {

		/**
		 * Integration ID.
		 * @var string
		 */
		protected $id = 'lifterlms';

		/**
		 * Integration name.
		 * @var string
		 */
		protected $name = 'LifterLMS';

		/**
		 * Constructor.
		 *
		 * @param mixed $settings Settings (unused, for core compatibility).
		 * @param mixed $event_handler Event handler (unused, for core compatibility).
		 */
		public function __construct( $settings = null, $event_handler = null ) {
			parent::__construct();
		}

		/**
		 * Initialize hooks.
		 */
		protected function init_hooks() {
			// Enrollment.
			add_action( 'lifterlms_user_enrolled', array( $this, 'track_enroll' ), 10, 2 );

			// Course complete.
			add_action( 'lifterlms_course_completed', array( $this, 'track_complete' ), 10, 2 );

			// ViewContent for single course.
			add_action( 'wp', array( $this, 'maybe_track_course_view' ) );
		}

		/**
		 * Check if plugin is active.
		 *
		 * @return bool True if LifterLMS is active.
		 */
		protected function is_plugin_active() {
			return class_exists( 'LLMS' ) || function_exists( 'lifterlms' );
		}

		public function maybe_track_course_view() {
			if ( ! is_singular() ) {
				return;
			}

			global $post;
			if ( ! $post ) {
				return;
			}

			// LifterLMS course post type is 'course'
			if ( isset( $post->post_type ) && in_array( $post->post_type, array( 'course' ), true ) ) {
				$course_id = $post->ID;
				$event_id = trackify_capi_generate_event_id( 'view', $course_id );

				if ( $this->is_duplicate_event( $event_id ) ) {
					return;
				}

				$custom_data = array(
					'content_ids' => array( (string) $course_id ),
					'content_type' => 'product',
					'content_name' => get_the_title( $course_id ),
					'value' => 0,
					'currency' => $this->get_currency(),
				);

				$this->send_event( 'ViewContent', $custom_data, $this->get_user_data(), $event_id );
			}
		}

		/**
		 * Enrollment handler
		 * @param int $user_id
		 * @param int $product_id Course or membership id
		 */
		public function track_enroll( $user_id, $product_id ) {
			$event_id = trackify_capi_generate_event_id( 'purchase', $user_id . '_' . $product_id );

			if ( $this->is_duplicate_event( $event_id ) ) {
				return;
			}

			$custom_data = array(
				'content_ids' => array( (string) $product_id ),
				'content_type' => 'product',
				'content_name' => get_the_title( $product_id ),
				'value' => 0,
				'currency' => $this->get_currency(),
			);

			$this->send_event( 'Purchase', $custom_data, $this->get_user_data(), $event_id );
		}

		/**
		 * Course complete handler
		 * @param int $user_id
		 * @param int $course_id
		 */
		public function track_complete( $user_id, $course_id ) {
			$event_id = trackify_capi_generate_event_id( 'complete', $user_id . '_' . $course_id );

			if ( $this->is_duplicate_event( $event_id ) ) {
				return;
			}

			$custom_data = array(
				'content_ids' => array( (string) $course_id ),
				'content_type' => 'product',
				'content_name' => get_the_title( $course_id ),
				'value' => 0,
				'currency' => $this->get_currency(),
			);

			$this->send_event( 'CompleteRegistration', $custom_data, $this->get_user_data(), $event_id );
		}
	}
}

