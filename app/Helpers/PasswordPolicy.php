<?php

namespace App\Helpers;

class PasswordPolicy
{
    /**
     * Validate password strength.
     * Rules: min 8 chars, 1 uppercase, 1 lowercase, 1 number, 1 special char.
     *
     * @return string|null  Error message, or null if the password is valid.
     */
    public static function validate(string $password): ?string
    {
        if (strlen($password) < 8) {
            return 'Password must be at least 8 characters long';
        }

        if (!preg_match('/[A-Z]/', $password)) {
            return 'Password must contain at least 1 uppercase letter';
        }

        if (!preg_match('/[a-z]/', $password)) {
            return 'Password must contain at least 1 lowercase letter';
        }

        if (!preg_match('/[0-9]/', $password)) {
            return 'Password must contain at least 1 number';
        }

        if (!preg_match('/[^A-Za-z0-9]/', $password)) {
            return 'Password must contain at least 1 special character (e.g. @, #, $, %, !)';
        }

        return null;
    }
}