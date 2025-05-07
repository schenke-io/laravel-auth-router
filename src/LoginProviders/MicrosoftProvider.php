<?php

namespace SchenkeIo\LaravelAuthRouter\LoginProviders;

/**
 * Social login with Microsoft
 *
 * Go to Azure Portal, navigate to Azure Active Directory (or Entra ID), select "App registrations," register a new application, find Application (client) ID on "Overview," generate Client Secret under "Certificates & secrets."
 * @link https://portal.azure.com/
 */
class MicrosoftProvider extends SocialiteBaseProvider {}
