<?php

namespace SchenkeIo\LaravelAuthRouter\LoginProviders;

/**
 * Social login with Stripe
 *
 * Go to Stripe Dashboard, navigate to "Connect" -> "Settings" -> "OAuth settings" to find Client ID, navigate to "Developers" -> "API keys" to find Secret API Key (acts as client secret).
 *
 * @link https://dashboard.stripe.com/
 */
class StripeProvider extends SocialiteBaseProvider {}
