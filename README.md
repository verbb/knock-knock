# Knock Knock Plugin for Craft CMS

Password protect your entire Craft website front-end with a single password. A fast and easy way to lock down access to your website, without worrying about Apache or Nginx configuration.

## Installation
You can install Knock Knock via the plugin store, or through Composer.

### Craft Plugin Store
To install **Knock Knock**, navigate to the _Plugin Store_ section of your Craft control panel, search for `Knock Knock`, and click the _Try_ button.

### Composer
You can also add the package to your project using Composer.

1. Open your terminal and go to your Craft project:

        cd /path/to/project

2. Then tell Composer to load the plugin:
    
        composer require verbb/knock-knock

3. In the Control Panel, go to Settings → Plugins and click the “Install” button for Knock Knock.

### Usage
In the Control Panel, go to Settings → Knock Knock, and enter a password. Anyone visiting your website will need to enter the password to see the website.

## Configuration

Create a `knock-knock.php` file under your `/config` directory with the following options available to you. You can also use multi-environment options to change these per environment.

```php
<?php

return [
    '*' => [
        'enabled' => false,
        'loginPath' => 'knock-knock/who-is-there',
        'template' => '',
        'forcedRedirect' => '',
        'password' => 'superSecretPassword',
        'siteSettings' => [],

        'checkInvalidLogins' => false,
        'invalidLoginWindowDuration' => '3600',
        'maxInvalidLogins' => 10,
        'whitelistIps' => '',
        'blacklistIps' => '',
        
        'protectedUrls' = '',
    ],
    'staging' => [
        'enabled' => true,
    ],
];
```

### Configuration options

- `enabled` - Whether password protection should be enabled. Useful in multi-environment scenarios.
- `password` - The password users will need to enter to access the site.
- `loginPath` - The path to be used when to challenge is shown to the user.
- `template` - Provide a path to a custom template to be shown instead of the default one.
- `forcedRedirect` - Provide a URL to be redirected to when logging in. Knock Knock will try and redirect to the referring URL, but you may want to enforce a specific URL to always go to.
- `siteSettings` - See below on how to configure.
- `checkInvalidLogins` - Whether to check and log invalid logins. This will lock IP addresses out of the system in certain circumstances, but can help against brute-force logins..
- `invalidLoginWindowDuration` - The amount of time to track invalid login attempts for an IP, for determining if Knock Knock should lock the IP out.
- `maxInvalidLogins` - The number of invalid login attempts Knock Knock will allow within the specified duration before the IP gets locked.
- `whitelistIps` - Provide IP Addresses that should be exempt from lockouts out automatically.
- `blacklistIps` - Provide IP Addresses that should be locked out automatically.
- `protectedUrls` - A line-break delimited list of specific URLs to only protect.

### Protected URLs

If you define your protected URLs in the config file, you'll need to provide them as line-break-delimited values. For example: `/test\r\n/another-test`.

Regex is also supported (for example `/some-channel/(.*)`).

### Multi-site configuration

The above will set the values globally, for all sites. These global values will override each setting for each site, so they'll always be the same. If you want to set these values per-site, do not include them at the top level. For example:

```php
<?php

return [
    '*' => [
        // Don't do this for multi-site specific settings
        'enabled' => true,
        'password' => 'superSecretPassword',

        // Instead, do this:
        'siteSettings' => [
            'siteHandle' => [
                'enabled' => true,
                'password' => 'superSecretPassword',
            ],
            'anotherSiteHandle' => [
                'enabled' => true,
                'password' => 'anotherSecretPassword',
            ],
        ]
    ],
];
```

If you keep the top level `enabled`, `password`, etc settings, they'll override your settings for each site.

### Security
You can opt to log users' attempts to login to Craft to prevent brute-force attempts. Use the config settings to manage this.

**Important:** You must also enable [storeUserIps](https://docs.craftcms.com/v3/config/config-settings.html#storeuserips) in your `general.php` file.

### Credits
Based on [KnockKock](https://github.com/dgrigg/knockknock) for Craft 2.

## Show your Support
Knock Knock is licensed under the MIT license, meaning it will always be free and open source – we love free stuff! If you'd like to show your support to the plugin regardless, buy us a :beers:. Please note that this does not entitle you to any form of support, and is completely optional.

[![Beerpay](https://beerpay.io/verbb/knock-knock/badge.svg?style=beer-square)](https://beerpay.io/verbb/knock-knock)

<h2></h2>

<a href="https://verbb.io" target="_blank">
  <img width="100" src="https://verbb.io/assets/img/verbb-pill.svg">
</a>
