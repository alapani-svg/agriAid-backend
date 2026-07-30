<?php

namespace App\Identity\Domain\ValueObjects;

enum OTPPurpose: string
{
    case LOGIN = 'login';
    case PASSWORD_RESET = 'password_reset';
    case EMAIL_VERIFICATION = 'email_verification';
    case PHONE_VERIFICATION = 'phone_verification';

    public static function fromString(string $purpose): self
    {
        return self::tryFrom($purpose) ?? throw new \InvalidArgumentException("Invalid OTP purpose: {$purpose}");
    }
}
