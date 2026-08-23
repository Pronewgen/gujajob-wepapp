<?php

namespace App\Auth;

use Illuminate\Auth\EloquentUserProvider;
use Illuminate\Contracts\Auth\Authenticatable;

/**
 * Custom auth provider for SYS_USER.PASSWD which is stored as plain text (legacy).
 *
 * SECURITY NOTE: plain-text passwords are a known security risk.
 * Comparison is performed server-side only using hash_equals() (timing-safe).
 * The password value is NEVER sent to the client, stored in the session,
 * or written to any log.
 */
class OraclePlainTextUserProvider extends EloquentUserProvider
{
    /**
     * Override password validation to compare plain text using constant-time
     * comparison, preventing timing-based enumeration attacks.
     */
    public function validateCredentials(Authenticatable $user, array $credentials): bool
    {
        $submitted = (string) ($credentials['password'] ?? '');
        $stored    = $user->getAuthPassword();

        if ($stored === '' || $submitted === '') {
            return false;
        }

        return hash_equals($stored, $submitted);
    }

    /**
     * No-op: SYS_USER.PASSWD is a VARCHAR2(30) legacy plain-text column.
     * Writing a bcrypt hash would cause ORA-12899. Never rehash on login.
     */
    public function rehashPasswordIfRequired(Authenticatable $user, #[\SensitiveParameter] array $credentials, bool $force = false): void
    {
        // Intentionally empty — do NOT update SYS_USER.PASSWD during login.
    }
}
