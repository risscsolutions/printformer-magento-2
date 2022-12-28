# The Printformer-Extension Changelog

All notable changes to this project will be documented in this file.

## [100.8.71] - 13-12-2022
### Fixed
- Fix sales plugin checkout error - WEM-1895 & WEM-1972
- Fix product type check error caused on php Version 7.4 - WEM-1760
<br>

## [100.8.70] - 14-11-2022
### Fixed
- After redirect to product on stage konfig / version buttons remains disabled - WEM-1866
- PDF Processing, status pending is met, but no print file was generated - WEM-1856
- Missing move to wishlist and edit button - WEM-1907 
- Issues with cart-stability in Magento on config "redirect to cart with expected auto add to cart" - WEM-1905
### Changed
- Printformer preselect configurable product swatches after printformer close. - WEM-1749
### External Pull Request
- Reorder issue when product-id is used - Fix plugin parameter type. #17 Pull Request (nuzil Lyzun Oleksandr)
<br>

## [100.8.69] - 01-10-2022
### Fixed
- after redirect to cart when leaving editor, the variants chosen are lost - WEM-1851
<br>

## [100.8.68] - 30-09-2022
### Fixed
- Sync issues over specific pf-configurations (default disabled) - WEM-1834
### Added
- Show mandator-name in core config - WEM-1354
<br>

## [100.8.67] - 30-09-2022
### Fixed
- Url malformed with "store config only" fixing issue without default-config, creating user pf-identifier - (WEM-1775)
### Added
- New configuration (yes, no) to transfer user data over payload with attributes to printformer via api-ext/user endpoint - (WEM-1771)
<br>

## [100.8.66] - 30-09-2022
### Changed
- New Magento Version "2.4.4" Magento Open Source" (with php 8)
- Changed code for php 8.1 compatibility changes about
  - Passing null to non-nullable internal function parameters is deprecated
  - Passing null to first parameter ($string) of type string is deprecated
  - Deprecate required parameters after optional parameters in function/method signatures