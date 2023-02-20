<?php

/**
 * Add notification after 1 week of lite version installation
 * Recurrence: 40 Days
 *
 * @since 7.12.3
 */
final class ExactMetrics_Notification_Install_AIOSEO extends ExactMetrics_Notification_Event {

	public $notification_id = 'exactmetrics_notification_install_aioseo';
	public $notification_interval = 30; // in days
	public $notification_type = array( 'basic', 'lite', 'master', 'plus', 'pro' );
	public $notification_icon = 'star';
	public $notification_category = 'insight';
	public $notification_priority = 2;

	/**
	 * Build Notification
	 *
	 * @return array $notification notification is ready to add
	 *
	 * @since 7.12.3
	 */
	public function prepare_notification_data( $notification ) {

		$seo_plugin_active = function_exists( 'YoastSEO' ) || function_exists( 'aioseo' );

		if ( ! $seo_plugin_active ) {
			$notification['title']   = __( 'Install All-In-One SEO', 'google-analytics-dashboard-for-wp' );
			$notification['content'] = __( 'Install All in One SEO to optimize your site for better search engine rankings.', 'google-analytics-dashboard-for-wp' );

			return $notification;
		}

		return false;
	}

}

// initialize the class
new ExactMetrics_Notification_Install_AIOSEO();
