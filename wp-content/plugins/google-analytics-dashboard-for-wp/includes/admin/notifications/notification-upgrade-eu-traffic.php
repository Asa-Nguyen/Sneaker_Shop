<?php

/**
 * Add notification for high EU Traffic
 * Recurrence: 30 Days
 *
 * @since 7.12.3
 */
final class ExactMetrics_Notification_Upgrade_EU_Traffic extends ExactMetrics_Notification_Event {

	public $notification_id = 'exactmetrics_notification_eu_traffic';
	public $notification_interval = 30; // in days
	public $notification_type = array( 'basic', 'lite' );
	public $notification_icon = 'star';
	public $notification_category = 'insight';
	public $notification_priority = 1;

	/**
	 * Build Notification
	 *
	 * @return array|false $notification notification is ready to add
	 *
	 * @since 7.12.3
	 */
	public function prepare_notification_data( $notification ) {

		$eu_countries = [
			'AT',
			'BE',
			'BG',
			'HR',
			'CY',
			'CZ',
			'DK',
			'EE',
			'FI',
			'FR',
			'DE',
			'GR',
			'HU',
			'IE',
			'IT',
			'LU',
			'MT',
			'NL',
			'PL',
			'PT',
			'RO',
			'SK',
			'SI',
			'ES',
			'SE'
		];

		$report = $this->get_report();
		if ( ! $report || ! $report['success'] ) {
			return false;
		}

		$sessions      = isset( $report['data']['infobox']['sessions']['value'] ) ? $report['data']['infobox']['sessions']['value'] : 0;
		$all_countries = isset( $report['data']['countries'] ) ? $report['data']['countries'] : [];

		$eu_sessions = 0;

		foreach ( $all_countries as $country ) {
			if ( in_array( $country['iso'], $eu_countries ) ) {
				$eu_sessions += intval( $country['sessions'] );
			}
		}

		if ( empty( $sessions ) ) {
			return false;
		}

		$eu_sessions_percentage = $eu_sessions / $sessions * 100;

		if ( $eu_sessions_percentage < 1 ) {
			return false;
		}

		$notification['title']   = __( 'Help Your Site Become GDPR Compliant', 'google-analytics-dashboard-for-wp' );
		$notification['content'] = __( 'Your site is receiving traffic from the EU. Help ensure your site is more compliant with GDPR by upgrading to ExactMetrics Pro and enable our EU Privacy addon.', 'google-analytics-dashboard-for-wp' );
		$notification['btns']    = array(
			"get_exactmetrics_pro" => array(
				'url'         => $this->get_upgrade_url(),
				'text'        => __( 'Get ExactMetrics Pro', 'google-analytics-dashboard-for-wp' ),
				'is_external' => true,
			),
		);

		return $notification;
	}
}

new ExactMetrics_Notification_Upgrade_EU_Traffic();
