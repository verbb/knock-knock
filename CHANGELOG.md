# Changelog

## 3.0.0-beta.1 - 2024-03-04

### Changed
- Now requires PHP `8.2.0+`.
- Now requires Craft `5.0.0-beta.1+`.

## 2.0.11 - 2023-11-09

### Changed
- Action request endpoints are now no longer protected.

### Fixed
- Fix too-early call on `User::getIdentity()` when plugin is disabled, for better performance.

## 2.0.10 - 2023-06-05

### Fixed
- Fix an error when checking the enabled state of the plugin.

## 2.0.9 - 2023-05-27

### Added
- Add `Cache-Control` headers when redirecting.

### Fixed
- Fix an incorrect check for enabled state for multi-site installs.

## 2.0.8 - 2023-03-02

### Fixed
- Fix `unprotectedUrls` as a config option not working correctly.

## 2.0.7 - 2023-01-18

### Changed
- Only admins are now allowed to access plugin settings.

### Fixed
- Fix Protected URLs/Unprotected URLs not working correctly for multiple values when set via the control panel.

## 2.0.6 - 2022-12-10

### Added
- Add `enableCpProtection` plugin setting.

### Fixed
- Fix login challenge when accessing the control panel.

## 2.0.5 - 2022-11-16

### Fixed
- Fix an error introduced in 2.0.4.

## 2.0.4 - 2022-11-15

### Added
- Add support for Cloudflare remote IP checking.
- Add support to block CP-based pages, not just site-based requests.

## 2.0.3 - 2022-10-19

### Fixed
- Fix an issue with live preview checks for access control.

## 2.0.2 - 2022-07-13

### Fixed
- Fix `protectedUrls`, and `unprotectedUrls` URLs partial-matching incorrectly due to Regex rules.
- Better normalising of `allowIps`, `denyIps`, `protectedUrls`, and `unprotectedUrls` settings.
- Revert infinite loop check, which results in incorrect redirect URLs.

## 2.0.1 - 2022-07-12

### Fixed
- Fix an error when installing the plugin.

## 2.0.0 - 2022-07-10

### Added
- Add resave console command for elements.
- Add checks for registering events for performance.
- Add `archiveTableIfExists()` to install migration.

### Changed
- Now requires PHP `8.0.2+`.
- Now requires Craft `4.0.0+`.
- Now requires Knock Knock `1.2.16` in order to update from Craft 3.
- Rename service classes.
- Rename base plugin methods.
- Use `Application::EVENT_INIT` to test access to ensure Craft is initialized properly.

## 1.2.17 - 2022-07-10

### Added
- Allow arrays in config settings for `allowIps`, `denyIps`, `protectedUrls`, `unprotectedUrls`. (thanks @Diewy).

### Fixed
- Fix a potential infinite redirect loop if changing from `http` to `https`.

## 1.2.16 - 2021-09-17

### Fixed
- Fix site-based custom templates not working correctly.

## 1.2.15 - 2021-06-30

### Added
- Add support for custom CP-based templates. (thanks @seibert-io).
- Add support for IPv4 and IPv6 CIDR blocks in allowIps and denyIps config. (thanks @onstuimig).

### Changed
- Deny access to settings for non-admins.

### Fixed
- Fix redirect URL not using the referrer URL after logging in.

## 1.2.14 - 2020-11-29

### Fixed
- Fix potential error redirecting to non-site URLs after login. In some cases, this caused redirecting to a cpresources asset.
- Fix cookie not respecting the Craft `defaultCookieDomain` config setting.

## 1.2.13 - 2020-09-10

### Fixed
- Fix incorrect `loginUrl` route, causing issues on some site setups (subdirectory installs).

## 1.2.12 - 2020-08-14

### Added
- Allow env variables to be used in allow/deny IPs.

### Fixed
- Fix login path not resolving correctly for some multi-site installs.

## 1.2.11 - 2020-08-10

### Fixed
- Fix challenge URL not being correct for nested URLs.

## 1.2.10 - 2020-07-13

### Added
- Add `useRemoteIp` to opt-in to more stricter IP checks if security is your concern.

### Fixed
- Revert behaviour of using remote IP for checking user IP. Too many issues and edge-cases.

## 1.2.9.2 - 2020-06-22

### Fixed
- Fix potential issue splitting multi-line settings (allowIps, denyIps, protectedUrls).

## 1.2.9.1 - 2020-06-18

### Fixed
- Fix error introduced in 1.2.9.

## 1.2.9 - 2020-06-17

### Deprecated
- Deprecate `whitelistIps`. Use `allowIps` instead.
- Deprecate `blacklistIps`. Use `denyIps` instead.

## 1.2.8 - 2020-05-20 [CRITICAL]

### Fixed
- Fix fetching the IP for a user that could allow spoofing via headers. Vulnerability `IP Whitelist bypass` reported by Paweł Hałdrzyński.
- Ensure redirect param is validated to prevent malicious redirection. For custom forms, please update the redirect input to use `{{ redirect | hash }}` otherwise logins will not work. Vulnerability `Open-redirect` reported by Paweł Hałdrzyński.

## 1.2.7 - 2020-04-21

### Added
- Add `forcedRedirect` to force a redirected URL once logging in.

## 1.2.6 - 2020-04-16

### Fixed
- Fix logging error `Call to undefined method setFileLogging()`.

## 1.2.5 - 2020-04-15

### Changed
- File logging now checks if the overall Craft app uses file logging.
- Log files now only include `GET` and `POST` additional variables.

## 1.2.4.2 - 2020-04-01

### Fixed
- Realllly fix live preview from cross-domains.

## 1.2.4.1 - 2020-03-31

### Fixed
- Fix error thrown for console requests.

## 1.2.4 - 2020-03-31

### Fixed
- Re-organise access testing code, and support cross-domain live preview (properly, through tokens).

## 1.2.3 - 2020-03-30

### Fixed
- Exclude live preview requests from blocking access.

## 1.2.2 - 2020-03-14

### Fixed
- Fix asset bundles causing style issues in the CP.

## 1.2.1 - 2020-02-25

### Added
- Add support for Regex in protected URLs.

### Fixed
- Fix protected URL comparison taking into account query strings, when it shouldn't.

## 1.2.0 - 2020-01-30

### Added
- Add Craft 3.4 compatibility.

## 1.1.2 - 2020-01-07

### Fixed
- Fix `yii\base\InvalidConfigException` error thrown in some instances.

## 1.1.1 - 2019-11-27

### Added
- Added Custom login path. Thanks @X-Tender.
- Allow IPs to be whitelisted from login protection.
- Add Protected URLs to set specific URLs (and only those) for password protection.

### Fixed
- Update redirect input.
= Fix redirection after login.

## 1.1.0 - 2019-06-05

### Added
- Add lock-out and security behaviour.
- Add multi-site settings.
- Add custom template setting.
- New icon.
- Add override notice for settings fields.

## 1.0.3 - 2019-02-09

### Fixed
- Fix console requests throwing an error.

## 1.0.2 - 2019-02-02

### Changed
- Downgrade requirement to Craft 3.0.x.

### Fixed
- Fix settings not saving.

## 1.0.1 - 2019-01-30

### Added
- Added `enabled` setting.

## 1.0.0 - 2019-01-26

- Initial release.
