<?php

namespace App\Services;

/** Only approved customer messages leave the payment boundary, never provider details. */
class PaymentErrorMessage
{
    public static function from(array $error): ?string
    {
        $code = $error['sub_code'] ?? $error['decline_code'] ?? $error['code'] ?? '';
        return match ($code) {
            'insufficient_funds' => 'Insufficient funds. Use another card or payment method.',
            'expired_card', 'card_expired' => 'This card has expired. Use a different card.',
            'card_number_invalid', 'invalid_number', 'incorrect_number', 'card_type_mismatch' => 'Check your card number and try again.',
            'cvc_invalid', 'cvc_incorrect', 'invalid_cvc', 'incorrect_cvc' => 'Check your card security code (CVC) and try again.',
            'authentication_error', 'authentication_failed' => 'Bank verification was unsuccessful. Try again and complete the bank approval step, or use another payment method.',
            'credit_limit_exceeded', 'debit_card_usage_limit_exceeded', 'amount_allowed_exceeded' => 'Your card limit was reached. Use another card or contact your bank.',
            'card_not_supported', 'card_not_accepted', 'card_unauthorized', 'currency_not_supported_by_card_issuer' => 'This card cannot be used for this payment. Use another card or payment method.',
            'card_declined', 'generic_decline', 'do_not_honor', 'payment_refused', 'issuer_declined', 'call_card_issuer', 'processor_declined', 'acquirer_declined',
            'fraudulent', 'highest_risk_level', 'lost_card', 'pickup_card', 'processor_blocked', 'restricted_card', 'stolen_card', 'blocked' => 'Your card was declined. Use another card or contact your bank.',
            default => null,
        };
    }
}
