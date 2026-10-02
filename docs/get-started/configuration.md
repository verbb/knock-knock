# Configuration

You can customise Knock Knock’s settings using a PHP configuration file. This is optional: each setting has a default, so you only need to include the values you want to change.

To override a setting, create `knock-knock.php` in your Craft project’s `/config` directory and return an array of setting names and values. For example, the following will include the control panel in password protection:

```php
<?php

return [
    'enableCpProtection' => true,
];
```

All other settings keep their defaults. Add any further settings you want to change to the same array. The options below explain the available settings and their defaults.

## Configuration Options

::: reference
### `enabled`

**Type:** `bool|string|Closure` · **Default:** `false`

Whether password protection should be enabled. Useful in multi-environment scenarios.
:::


::: reference
### `enableCpProtection`

**Type:** `bool|string` · **Default:** `false`

Whether password protection for the control panel should be enabled. By default, only the front-end is protected.
:::


::: reference
### `password`

**Type:** `string` · **Default:** `''`

The password users will need to enter to access the site. Protection fails closed when the effective value is empty, including when an environment variable is missing.
:::


::: reference
### `loginPath`

**Type:** `string` · **Default:** `''`

The path to be used when to challenge is shown to the user.
:::


::: reference
### `template`

**Type:** `string` · **Default:** `''`

Provide a path to a custom template to be shown instead of the default one.
:::


::: reference
### `forcedRedirect`

**Type:** `string` · **Default:** `''`

Provide a URL to be redirected to after the password is accepted. By default, Knock Knock returns each visitor to the protected page they originally requested, but you may want to enforce a specific destination instead.
:::


::: reference
### `cookieDuration`

**Type:** `string|int` · **Default:** `3600`

How long a visitor stays logged in for before being asked for the password again. Accepts a number of seconds, or any duration value Craft supports, like `'P1D'` for a day. Set to `0` for access to last until the visitor closes their browser. Note that this is a fixed expiry from the time of login, not an idle timeout.
:::


::: reference
### `siteSettings`

**Type:** `array` · **Default:** `[]`

See below on how to configure.
:::


::: reference
### `checkInvalidLogins`

**Type:** `bool` · **Default:** `false`

Whether to check and log invalid logins. This will lock IP addresses out of the system in certain circumstances, but can help against brute-force logins.
:::


::: reference
### `invalidLoginWindowDuration`

**Type:** `string` · **Default:** `'3600'`

The amount of time to track invalid login attempts for an IP, for determining if Knock Knock should lock the IP out.
:::


::: reference
### `maxInvalidLogins`

**Type:** `int` · **Default:** `10`

The number of invalid login attempts Knock Knock will allow within the specified duration before the IP gets locked.
:::


::: reference
### `allowIps`

**Type:** `array|string|null` · **Default:** `[]`

Provide IP addresses or CIDR blocks that should bypass the password gate and be exempt from automatic lockouts.
:::


::: reference
### `denyIps`

**Type:** `array|string|null` · **Default:** `[]`

Provide IP Addresses that should be locked out automatically.
:::


::: reference
### `useRemoteIp`

**Type:** `bool` · **Default:** `false`

Whether to use the immediate network peer’s IP address and ignore all forwarded headers. This is the strictest option, but behind a proxy it normally identifies the proxy rather than the visitor.

When this is disabled, Knock Knock still uses the direct peer unless it matches a concrete proxy IP address or CIDR in Craft’s `trustedHosts` setting and the forwarded header is permitted by that proxy entry and Craft’s `ipHeaders` setting. Craft’s default `trustedHosts` value of `any` is not treated as a trusted proxy boundary for password-gate access.
:::


::: reference
### `protectedUrls`

**Type:** `array|string|null` · **Default:** `[]`

A list of specific request paths to protect. Query strings are ignored, and exact paths are compared case-insensitively without leading or trailing slashes. An absolute entry must belong to the current site; its scheme and host are then removed before matching. Entries containing `(` retain the existing case-insensitive regex behavior (for example `/some-channel/(.*)`).
:::


::: reference
### `unprotectedUrls`

**Type:** `array|string|null` · **Default:** `[]`

A list of specific request paths to leave unprotected. Query strings are ignored, and exact paths are compared case-sensitively without leading or trailing slashes. An absolute entry must belong to the current site; its scheme and host are then removed before matching. Entries containing `(` retain the existing case-insensitive regex behavior (for example `/some-channel/(.*)`).
:::


### Multi-Site Configuration
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
