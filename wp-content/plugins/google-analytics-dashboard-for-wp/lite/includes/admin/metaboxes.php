<?php
/**
 * ExactMetrics Metaboxes
 *
 * @since 8.5.1
 *
 * @package ExactMetrics
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'ExactMetrics_MetaBoxes' ) ) {
	class ExactMetrics_MetaBoxes {

		private static $_instance = null;

		public static function instance() {
			if ( is_null( self::$_instance ) ) {
				self::$_instance = new self();
			}

			return self::$_instance;
		}

		public function register_hooks() {
			add_action( 'exactmetrics_after_exclude_metabox', array(
				$this,
				'print_dummy_page_insights_metabox_html'
			) );
		}

		public function print_dummy_page_insights_metabox_html() {
			$is_v4 = 'ua' !== ExactMetrics()->auth->get_connected_type();
			?>
			<div class="exactmetrics-metabox lite" id="exactmetrics-metabox-page-insights">
				<a class="button" href="#" id="exactmetrics_show_page_insights">
					<?php _e( 'Show Page Insights', 'google-analytics-dashboard-for-wp' ); ?>
				</a>

				<div id="exactmetrics-page-insights-content">
					<div class="exactmetrics-page-insights__tabs">
						<a href="#" class="exactmetrics-page-insights__tabs-tab active"
						   data-tab="exactmetrics-last-30-days-content">
							<?php _e( 'Last 30 days', 'google-analytics-dashboard-for-wp' ); ?>
						</a>
						<a href="#" class="exactmetrics-page-insights__tabs-tab"
						   data-tab="exactmetrics-yesterday-content">
							<?php _e( 'Yesterday', 'google-analytics-dashboard-for-wp' ); ?>
						</a>
					</div>
					<div class="exactmetrics-page-insights-tabs-content">
						<div class="exactmetrics-page-insights-tabs-content__tab active"
							 id="exactmetrics-last-30-days-content">
							<div class="exactmetrics-page-insights-tabs-content__tab-items">

								<?php if ( ! $is_v4 ) { ?>
									<div class="exactmetrics-page-insights-tabs-content__tab-item">
										<div class="exactmetrics-page-insights-tabs-content__tab-item__result">
											<span>53.40%</span>
										</div>
										<div class="exactmetrics-page-insights-tabs-content__tab-item__title">
											<?php _e( 'Bounce Rate', 'google-analytics-dashboard-for-wp' ); ?>
										</div>
									</div>
								<?php } ?>

								<div class="exactmetrics-page-insights-tabs-content__tab-item">
									<div class="exactmetrics-page-insights-tabs-content__tab-item__result">
										<span>1m 43s</span>
									</div>
									<div class="exactmetrics-page-insights-tabs-content__tab-item__title">
										<?php _e( 'Time on Page', 'google-analytics-dashboard-for-wp' ); ?>
									</div>
								</div>

								<?php if ( ! $is_v4 ) { ?>
									<div class="exactmetrics-page-insights-tabs-content__tab-item">
										<div class="exactmetrics-page-insights-tabs-content__tab-item__result">
											<span>3.9s</span>
										</div>
										<div class="exactmetrics-page-insights-tabs-content__tab-item__title">
											<?php _e( 'Load Time', 'google-analytics-dashboard-for-wp' ); ?>
										</div>
									</div>
								<?php } ?>

								<div class="exactmetrics-page-insights-tabs-content__tab-item">
									<div class="exactmetrics-page-insights-tabs-content__tab-item__result">
										<span>19056</span>
									</div>
									<div class="exactmetrics-page-insights-tabs-content__tab-item__title">
										<?php _e( 'Entrances', 'google-analytics-dashboard-for-wp' ); ?>
									</div>
								</div>
								<div class="exactmetrics-page-insights-tabs-content__tab-item">
									<div class="exactmetrics-page-insights-tabs-content__tab-item__result">
										<span>26558</span>
									</div>
									<div class="exactmetrics-page-insights-tabs-content__tab-item__title">
										<?php _e( 'Page Views', 'google-analytics-dashboard-for-wp' ); ?>
									</div>
								</div>
								<div class="exactmetrics-page-insights-tabs-content__tab-item">
									<div class="exactmetrics-page-insights-tabs-content__tab-item__result">
										<span>13428</span>
									</div>
									<div class="exactmetrics-page-insights-tabs-content__tab-item__title">
										<?php _e( 'Exits', 'google-analytics-dashboard-for-wp' ); ?>
									</div>
								</div>

							</div>
						</div>
						<div class="exactmetrics-page-insights-tabs-content__tab"
							 id="exactmetrics-yesterday-content">
							<div class="exactmetrics-page-insights-tabs-content__tab-items">

								<?php if ( ! $is_v4 ) { ?>
									<div class="exactmetrics-page-insights-tabs-content__tab-item">
										<div class="exactmetrics-page-insights-tabs-content__tab-item__result">
											<span>53.40%</span>
										</div>
										<div class="exactmetrics-page-insights-tabs-content__tab-item__title">
											<?php _e( 'Bounce Rate', 'google-analytics-dashboard-for-wp' ); ?>
										</div>
									</div>
								<?php } ?>

								<div class="exactmetrics-page-insights-tabs-content__tab-item">
									<div class="exactmetrics-page-insights-tabs-content__tab-item__result">
										<span>1m 43s</span>
									</div>
									<div class="exactmetrics-page-insights-tabs-content__tab-item__title">
										<?php _e( 'Time on Page', 'google-analytics-dashboard-for-wp' ); ?>
									</div>
								</div>

								<?php if ( ! $is_v4 ) { ?>
									<div class="exactmetrics-page-insights-tabs-content__tab-item">
										<div class="exactmetrics-page-insights-tabs-content__tab-item__result">
											<span>3.9s</span>
										</div>
										<div class="exactmetrics-page-insights-tabs-content__tab-item__title">
											<?php _e( 'Load Time', 'google-analytics-dashboard-for-wp' ); ?>
										</div>
									</div>
								<?php } ?>

								<div class="exactmetrics-page-insights-tabs-content__tab-item">
									<div class="exactmetrics-page-insights-tabs-content__tab-item__result">
										<span>19056</span>
									</div>
									<div class="exactmetrics-page-insights-tabs-content__tab-item__title">
										<?php _e( 'Entrances', 'google-analytics-dashboard-for-wp' ); ?>
									</div>
								</div>
								<div class="exactmetrics-page-insights-tabs-content__tab-item">
									<div class="exactmetrics-page-insights-tabs-content__tab-item__result">
										<span>26558</span>
									</div>
									<div class="exactmetrics-page-insights-tabs-content__tab-item__title">
										<?php _e( 'Page Views', 'google-analytics-dashboard-for-wp' ); ?>
									</div>
								</div>
								<div class="exactmetrics-page-insights-tabs-content__tab-item">
									<div class="exactmetrics-page-insights-tabs-content__tab-item__result">
										<span>13428</span>
									</div>
									<div class="exactmetrics-page-insights-tabs-content__tab-item__title">
										<?php _e( 'Exits', 'google-analytics-dashboard-for-wp' ); ?>
									</div>
								</div>

							</div>
						</div>
					</div>

					<a class="button" href="#" id="exactmetrics_hide_page_insights">
						<?php _e( 'Hide Page Insights', 'google-analytics-dashboard-for-wp' ); ?>
					</a>
				</div>

			</div>
			<?php
		}
	}

	ExactMetrics_MetaBoxes::instance()->register_hooks();
}
