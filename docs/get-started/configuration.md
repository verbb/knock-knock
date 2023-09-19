# Configuration
Create a `knock-knock.php` file under your `/config` directory with the following options available to you. You can also use multi-environment options to change these per environment.

The below shows the defaults already used by Knock Knock, so you don't need to add these options unless you want to modify the values.

```php
<?php

return [
    '*' => [
        'enabled' => false,
        'enableCpProtection' => false,
        'loginPath' => 'knock-knock/who-is-there',
        'template' => '',
        'forcedRedirect' => '',
        'password' => 'superSecretPassword',
        'siteSettings' => [],
        'checkInvalidLogins' => false,
        'invalidLoginWindowDuration' => '3600',
        'maxInvalidLogins' => 10,
        'allowIps' => [],
        'denyIps' => [],
        'useRemoteIp' => false,
        'protectedUrls' => [],
        'unprotectedUrls' => [],
    ],
    'staging' => [
        'enabled' => true,
    ],
];
```

## Configuration options
- `enabled` - Whether password protection should be enabled. Useful in multi-environment scenarios.
- `enableCpProtection` - Whether password protection for the control panel should be enabled. By default, only the front-end is protected.
- `password` - The password users will need to enter to access the site.
- `loginPath` - The path to be used when to challenge is shown to the user.
- `template` - Provide a path to a custom template to be shown instead of the default one.
- `forcedRedirect` - Provide a URL to be redirected to when logging in. Knock Knock will try and redirect to the referring URL, but you may want to enforce a specific URL to always go to.
- `siteSettings` - See below on how to configure.
- `checkInvalidLogins` - Whether to check and log invalid logins. This will lock IP addresses out of the system in certain circumstances, but can help against brute-force logins.
- `invalidLoginWindowDuration` - The amount of time to track invalid login attempts for an IP, for determining if Knock Knock should lock the IP out.
- `maxInvalidLogins` - The number of invalid login attempts Knock Knock will allow within the specified duration before the IP gets locked.
- `allowIps` - Provide IP Addresses that should be exempt from lockouts out automatically.
- `denyIps` - Provide IP Addresses that should be locked out automatically.
- `useRemoteIp` - Whether to use the Remote IP address of the user to compare their IP against. If security if your primary concern, consider turning this on. This may not accurately report users behind proxies, so use with caution.
- `protectedUrls` - A list of specific URLs to only protect. Regex is also supported (for example `/some-channel/(.*)`).
- `unprotectedUrls` - A list of specific URLs to not protect. Regex is also supported (for example `/some-channel/(.*)`).

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

## Control Panel
You can also manage configuration settings through the Control Panel by visiting Settings → Knock Knock.
