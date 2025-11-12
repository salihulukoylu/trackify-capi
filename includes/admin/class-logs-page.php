<?php
/**
 * Logs Page
 *
 * Basit bir admin logs sayfası stub'ı. Gerçek proje daha gelişmiş olabilir,
 * ama bu stub `class-core.php` tarafından require edildiğinde hatayı önler
 * ve admin arayüzünde temel logları görüntüleyebilir.
 *
 * @package Trackify_CAPI
 * @since 2.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'Trackify_CAPI_Logs_Page' ) ) {
	class Trackify_CAPI_Logs_Page {
		/**
		 * Trackify_CAPI_Settings instance.
		 * @var Trackify_CAPI_Settings
		 */
		private $settings;

		/**
		 * Trackify_CAPI_Logger instance.
		 * @var Trackify_CAPI_Logger
		 */
		private $logger;

		/**
		 * Constructor.
		 */
		public function __construct() {
			$this->settings = trackify_capi()->get_component( 'settings' );
			$this->logger   = trackify_capi()->get_component( 'logger' );
		}

		/**
		 * Render logs page.
		 */
		public function render() {
			$logs = array();
			if ( $this->logger ) {
				$logs = $this->logger->get_recent_logs( 50 );
			}

			?>
			<div class="wrap trackify-capi-admin">
				<h1><?php esc_html_e( 'Trackify CAPI - Event Logs', 'trackify-capi' ); ?></h1>

				<p><?php esc_html_e( 'Son 50 event log listeleniyor. Filtreleme ve gelişmiş işlemler için REST API kullanın.', 'trackify-capi' ); ?></p>

				<table class="wp-list-table widefat fixed striped">
					<thead>
						<tr>
							<th><?php esc_html_e( 'Event', 'trackify-capi' ); ?></th>
							<th><?php esc_html_e( 'Event ID', 'trackify-capi' ); ?></th>
							<th><?php esc_html_e( 'Status', 'trackify-capi' ); ?></th>
							<th><?php esc_html_e( 'Created At', 'trackify-capi' ); ?></th>
						</tr>
					</thead>
					<tbody>
						<?php if ( empty( $logs ) ) : ?>
							<tr><td colspan="4"><?php esc_html_e( 'Kayıt bulunamadı.', 'trackify-capi' ); ?></td></tr>
						<?php else : ?>
							<?php foreach ( $logs as $log ) : ?>
								<tr>
									<td><?php echo esc_html( $log['event_name'] ?? '' ); ?></td>
									<td><?php echo esc_html( $log['event_id'] ?? '' ); ?></td>
									<td><?php echo esc_html( $log['status'] ?? '' ); ?></td>
									<td><?php echo esc_html( $log['created_at'] ?? '' ); ?></td>
								</tr>
							<?php endforeach; ?>
						<?php endif; ?>
					</tbody>
				</table>
			</div>
			<?php
		}
	}
}
