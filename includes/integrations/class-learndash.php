<?php
/**
 * LearnDash Integration
 *
 * LearnDash kurs aktivitelerini izler: kurs görüntüleme, kayıt (enroll),
 * kurs tamamlama ve satın alma event'leri Trackify CAPI'ye gönderilir.
 *
 * @package Trackify_CAPI
 * @since 2.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'Trackify_CAPI_LearnDash' ) ) {
	class Trackify_CAPI_LearnDash extends Trackify_CAPI_Abstract_Integration {

		/**
		 * Integration ID.
		 * @var string
		 */
		protected $id = 'learndash';

		/**
		 * Integration name.
		 * @var string
		 */
		protected $name = 'LearnDash';

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
			// Course complete.
			add_action( 'learndash_course_completed', array( $this, 'track_complete' ), 10, 3 );

			// Enrollment (user enrolled).
			add_action( 'learndash_user_enrolled', array( $this, 'track_enroll' ), 10, 3 );

			// ViewContent for single course.
			add_action( 'wp', array( $this, 'maybe_track_course_view' ) );
		}

		/**
		 * Check if plugin is active.
		 *
		 * @return bool True if LearnDash is active.
		 */
		protected function is_plugin_active() {
			return class_exists( 'Learndash_Settings' ) || function_exists( 'learndash_is_active' );
		}

		public function maybe_track_course_view() {
			if ( ! is_singular() ) {
				return;
			}

			global $post;
			if ( ! $post ) {
				return;
			}

			// LearnDash post type is typically 'sfwd-courses'
			if ( isset( $post->post_type ) && in_array( $post->post_type, array( 'sfwd-courses', 'course' ), true ) ) {
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
		 * Enroll handler
		 * @param int $user_id
		 * @param int $course_id
		 * @param array $data
		 */
		public function track_enroll( $user_id, $course_id, $data = array() ) {
			$event_id = trackify_capi_generate_event_id( 'purchase', $user_id . '_' . $course_id );

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

			$this->send_event( 'Purchase', $custom_data, $this->get_user_data(), $event_id );
		}

		/**
		 * Course complete handler
		 * @param int $data
		 */
		public function track_complete( $data, $user_id = null, $course_id = null ) {
			// LearnDash sometimes passes different arg order; normalize
			if ( is_array( $data ) && isset( $data['user'] ) && isset( $data['course'] ) ) {
				$user_id = $data['user'];
				$course_id = $data['course'];
			}

			if ( ! $course_id || ! $user_id ) {
				return;
			}

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
