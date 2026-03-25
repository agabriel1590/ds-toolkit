<?php
if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * Forminator {email_partner} Variable
 *
 * Adds a custom variable {email_partner} to Forminator forms.
 * When a form contains {email_partner}, it is replaced with the value
 * of the ACF options field "partner_email". Falls back to the configured
 * fallback email if ACF returns an empty value.
 *
 * Typical use: set {email_partner} as a recipient in a Forminator notification.
 */
class DS_Forminator_Email_Partner {

    const DEFAULT_FALLBACK = 'designshop@leagueapps.com';

    private $settings;

    public function __construct( $settings = array() ) {
        $this->settings = $settings;
    }

    public function init() {
        add_filter( 'forminator_replace_variables', array( $this, 'replace_variable' ), 10, 4 );
    }

    public function replace_variable( $content, $post_id = null, $field = null, $form_id = null ) {
        if ( strpos( $content, '{email_partner}' ) === false ) {
            return $content;
        }

        $email_partner = function_exists( 'get_field' ) ? get_field( 'partner_email', 'option' ) : '';

        if ( empty( $email_partner ) ) {
            $email_partner = ! empty( $this->settings['forminator_email_partner_fallback'] )
                ? $this->settings['forminator_email_partner_fallback']
                : self::DEFAULT_FALLBACK;
        }

        return str_replace( '{email_partner}', sanitize_email( $email_partner ), $content );
    }
}
