# Changelog

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
