<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'CR_WPML' ) ) :

	class CR_WPML {

		public static function translate_admin( $fields ) {
			if ( has_action( 'wpml_register_single_string' ) ) {
				//tab = Review Reminder
				$fields_to_translate = array(
					'ivole_email_subject',
					'ivole_email_heading',
					'ivole_email_body',
					'ivole_form_header',
					'ivole_form_body',
					'ivole_email_from',
					'ivole_email_from_name',
					'ivole_email_replyto',
					'ivole_email_footer',
					'ivole_email_subject_coupon',
					'ivole_email_heading_coupon',
					'ivole_email_body_coupon',
					'ivole_customer_consent_text'
				);
				foreach ( $fields_to_translate as $field_to_translate ) {
					if ( isset( $fields[$field_to_translate] ) ) {
						$fields[$field_to_translate] = wp_unslash( $fields[$field_to_translate] );
						switch ($field_to_translate) {
							case 'ivole_email_body':
							$fields[$field_to_translate] = wp_kses_post( $fields[$field_to_translate] );
							break;
							case 'ivole_email_body_coupon':
							$fields[$field_to_translate] = wp_kses_post( $fields[$field_to_translate] );
							break;
							default:
							$fields[$field_to_translate] = wc_clean( $fields[$field_to_translate] );
							break;
						}
						do_action( 'wpml_register_single_string', 'ivole', $field_to_translate, $fields[$field_to_translate] );
					}
				}
			}
		}
	}

endif;
