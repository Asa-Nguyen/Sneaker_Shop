<?php

class ExactMetrics_WP_Site_Health {
	public function __construct() {
		add_filter( 'site_status_tests', array( $this, 'add_tests' ) );
	}

	public function add_tests( $tests ) {
		$tests['direct']['exactmetrics_dual_tracking'] = array(
			'label' => __( 'ExactMetrics Dual Tracking', 'google-analytics-dashboard-for-wp' ),
			'test'  => array( $this, 'test_dual_tracking' ),
		);

		return $tests;
	}

	public function test_dual_tracking() {

		$has_v4_id = strlen( exactmetrics_get_v4_id() ) > 0;

		if ( $has_v4_id ) {
			return false;
		}

		$setup_link = add_query_arg( array(
			'page'                      => 'exactmetrics_settings',
			'exactmetrics-scroll'    => 'exactmetrics-dual-tracking-id',
			'exactmetrics-highlight' => 'exactmetrics-dual-tracking-id',
		), admin_url( 'admin.php' ) );

		return array(
			'label'       => __( 'Enable Google Analytics 4', 'google-analytics-dashboard-for-wp' ),
			'status'      => 'critical',
			'badge'       => array(
				'label' => __( 'ExactMetrics', 'google-analytics-dashboard-for-wp' ),
				'color' => 'blue',
			),
			'description' => __( 'Starting July 1, 2023, Google\'s Universal Analytics (GA3) will not accept any new traffic or event data. Upgrade to Google Analytics 4 today to be prepared for the sunset.', 'google-analytics-dashboard-for-wp' ),
			'actions'     => sprintf(
				'<p><a href="%s" target="_blank" rel="noopener noreferrer">%s</a></p>',
				$setup_link,
				__( 'Set Up Dual Tracking', 'google-analytics-dashboard-for-wp' )
			),
			'test'        => 'exactmetrics_dual_tracking',
		);
	}
}

new ExactMetrics_WP_Site_Health();
