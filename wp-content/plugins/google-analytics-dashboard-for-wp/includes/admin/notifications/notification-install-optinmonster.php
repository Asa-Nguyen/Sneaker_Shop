<?php

/**
 * Add notification after 1 week of lite version installation
 * Recurrence: 40 Days
 *
 * @since 7.12.3
 */
final class ExactMetrics_Notification_Install_OptinMonster extends ExactMetrics_Notification_Event {

	public $notification_id = 'exactmetrics_notification_install_optinmonster';
	public $notification_interval = 30; // in days
	public $notification_type = array( 'basic', 'lite', 'master', 'plus', 'pro' );
	public $notification_icon = 'star';
	public $notification_category = 'insight';
	public $notification_priority = 3;

	/**
	 * Build Notification
	 *
	 * @return array $notification notification is ready to add
	 *
	 * @since 7.12.3
	 */
	public function prepare_notification_data( $notification ) {

		$report = $this->get_report();

		$sessions         = isset( $report['data']['infobox']['sessions']['value'] ) ? $report['data']['infobox']['sessions']['value'] : 0;
		$om_plugin_active = class_exists( 'OMAPI' );

		if ( $sessions > 1000 && ! $om_plugin_active ) {
			$notification['title']   = __( 'Increase Engagement on Your Site', 'google-analytics-dashboard-for-wp' );
			$notification['content'] = __( 'Get more leads and subscribers from your traffic by creating engaging campaigns with OptinMonster.', 'google-analytics-dashboard-for-wp' );

			return $notification;
		}

		return false;
	}

}

// initialize the class
new ExactMetrics_Notification_Install_OptinMonster();
