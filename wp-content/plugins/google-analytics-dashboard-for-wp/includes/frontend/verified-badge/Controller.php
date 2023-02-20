<?php

class ExactMetrics_VerifiedBadge_Controller
{

	/**
	 * Current class instance.
	 *
	 * @since 8.9.0
	 *
	 * @var ExactMetrics_VerifiedBadge_Controller
	 */
	private static $instance;

	/**
	 * Name of the shortcode
	 *
	 * @since 8.9.0
	 *
	 * @var string
	 */
	private $shortcode_key = 'exactmetrics-badge';

	/**
	 * Get instance from current class.
	 *
	 * @since 8.9.0
	 *
	 * @return ExactMetrics_VerifiedBadge_Controller
	 */
	public static function get_instance()
	{
		if (!isset(self::$instance) && !(self::$instance instanceof ExactMetrics_VerifiedBadge_Controller)) {
			self::$instance = new ExactMetrics_VerifiedBadge_Controller();
		}

		return self::$instance;
	}

	/**
	 * Entry point for this class to start adding callbacks for WP hooks.
	 *
	 * @since 8.9.0
	 *
	 * @return void
	 */
	public function run()
	{
		$this->register_shortcode();
		$this->add_hooks();
	}

	/**
	 * Register the shortcode.
	 *
	 * @since 8.9.0
	 *
	 * @return void
	 */
	private function register_shortcode()
	{
		if (empty($this->shortcode_key)) {
			return;
		}

		add_shortcode($this->shortcode_key, array($this, 'render_badge'));
	}

	/**
	 * Render badge HTML based on the passed attributes.
	 *
	 * @param array $atts Attributes used to customize the badge.
	 *
	 * @since 8.9.0
	 *
	 * @return string
	 */
	public function render_badge($atts)
	{
		$atts = shortcode_atts(array(
			'appearance' => 'light',
			'position'   => 'center'
		), $atts, $this->shortcode_key);

		$img_src =  esc_url(plugins_url('assets/images/exactmetrics-badge-' . (in_array($atts['appearance'], array('light', 'dark'), true) ? $atts['appearance'] : 'light') . '.svg', EXACTMETRICS_PLUGIN_FILE));

		return sprintf(
			'<div style="text-align: %1$s;"><a href="%2$s" target="_blank" rel="nofollow"><img style="display: inline-block" alt="%3$s" title="%3$s" src="%4$s"/></a></div>',
			(in_array($atts['position'], array('left', 'center', 'right'), true) ? $atts['position'] : 'center'),
			$this->get_link(),
			__('Verified by ExactMetrics', 'google-analytics-dashboard-for-wp'),
			$img_src
		);
	}

	private function get_link()
	{
		return add_query_arg(
			array(
				'utm_source'   => 'verifiedBadge',
				'utm_medium'   => 'verifiedBadge',
				'utm_campaign' => 'verifiedbyExactMetrics',
			),
			'https://www.exactmetrics.com/'
		);
	}

	/**
	 * Start adding callbacks for WP Hooks.
	 *
	 * @since 8.9.0
	 *
	 * @return void
	 */
	public function add_hooks()
	{
		add_action('wp_footer', array($this, 'show_automatic_footer_badge'), 1000);
		add_action('wp_loaded', array($this, 'save_default_settings'));
	}

	/**
	 * Save default settings
	 *
	 * @since 8.9.0
	 *
	 * @return void
	 */
	public function save_default_settings()
	{
		$appearance = exactmetrics_get_option('verified_appearance', false);
		$position = exactmetrics_get_option('verified_position', false);
		if (!$appearance) {
			exactmetrics_update_option('verified_appearance', 'light');
		}
		if (!$position) {
			exactmetrics_update_option('verified_position', 'center');
		}
	}

	/**
	 * Show badge in footer if Automatic mode is enabled.
	 *
	 * @since 8.9.0
	 *
	 * @return void
	 */
	public function show_automatic_footer_badge()
	{
		if (!exactmetrics_get_option('verified_automatic')) {
			return;
		}

		$atts = array(
			'appearance' => exactmetrics_get_option('verified_appearance'),
			'position'   => exactmetrics_get_option('verified_position'),
		);

		echo $this->render_badge($atts);
	}
}

ExactMetrics_VerifiedBadge_Controller::get_instance()->run();
