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

Create an `knock-knock.php` file under your `/config` directory with the following options available to you. You can also use multi-environment options to change these per environment.

```php
<?php

return [
    '*' => [
        'enabled' => false,
        'password' => 'superSecretPassword',
        'siteSettings' => [],
    ],
    'staging' => [
        'enabled' => true,
    ],
];
```

### Configuration options

- `enabled` - Whether password protection should be enabled. Useful in multi-environment scenarios.
- `password` - The password users will need to enter to access the site.
- `template` - Provide a custom template to be shown instead of the default one.
- `siteSettings` - See below on how to configure.

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

### Credits
Based on [KnockKock](https://github.com/dgrigg/knockknock) for Craft 2.

## Show your Support
Knock Knock is licensed under the MIT license, meaning it will always be free and open source – we love free stuff! If you'd like to show your support to the plugin regardless, buy us a :beers:. Please note that this does not entitle you to any form of support, and is completely optional.

[![Beerpay](https://beerpay.io/verbb/knock-knock/badge.svg?style=beer-square)](https://beerpay.io/verbb/knock-knock)

<h2></h2>

<a href="https://verbb.io" target="_blank">
  <img width="100" src="https://verbb.io/assets/img/verbb-pill.svg">
</a>
