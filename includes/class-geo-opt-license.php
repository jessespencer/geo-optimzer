<?php

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class Geo_Opt_License {

    public function init(): void {
        add_action( 'admin_init', array( $this, 'check_license' ) );
    }

    public function check_license(): void {
        $key = get_option( 'geo_opt_license_key', '' );
        if ( $key !== '' ) {
            $this->validate_license( $key );
        }
    }

    public function validate_license( string $key ): bool {
        // Stub: license validation logic to be implemented
        return true;
    }

    public function is_licensed(): bool {
        // Stub: always returns true until licensing is wired up
        return true;
    }

    public function get_license_status(): string {
        // Stub: returns active until licensing is wired up
        return 'active';
    }

    public function revoke_license(): void {
        // Stub: license revocation logic to be implemented
    }
}
