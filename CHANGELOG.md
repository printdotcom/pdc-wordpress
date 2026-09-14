# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.1.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [1.5.0]

### Added

- automatic purchasing: buy an order at Print.com when it hits a specified order status
- the error Print.com last returned is stored on the order item and shown on its row in the order panel
- preset accessories will be purchased

### Changed

- admin settings UI
- support for WordPress 7 and PHP 8.4
- placing an order waits up to 120 seconds, the order endpoint can take over a minute

### Fixed

- purchasing a single order item no longer purchases an item that was already bought
- a purchase that times out is recorded as unknown instead of failed, Print.com may have accepted it
- an order item is held while its purchase is in flight, so a second click, tab or job cannot buy it twice

## [1.4.0]

### Added

- purchase all feature
- filter to ov
- log for requests to print.com 
- customer email to shipping address

### Changed

- layout of the order view
- action handling in js
- _before_purchase_order_item filter to contain 
- retrieving preset url, will now check current product preset id

## [1.3.0]

- log to file

## [1.2.0]

### Added
- added filter to override the pdf url on an order
- compatibility layer for other plugins

## [1.1.1]

### Changed

- sort presets alphabetically

## [1.1.0]

### Added

- select a sku within a variation
- added woocommerce as required plugin
- added knowledge base link in readme

## [1.0.1]

### Added

- Initial version of the plug-in

### Changed

- fix: API Client did not send pdc-request-source due to header validation

## [1.0.0]

### Added

- Initial version of the plug-in