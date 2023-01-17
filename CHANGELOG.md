# The Printformer-Extension Changelog

All notable changes to this project will be documented in this file.

<br><br>

## [100.9.3] 17-01-2023

### Fixed
- Fixed issue where on older orders without child-product, reorder -> click on cart-item failed with error message

<br><br>

## [100.9.2] 16-01-2023

### Added
- Adding logic for simple products to assign printformer templates. The setting to activate is
  "Use Configurable Product filter to get Printformer Products" = "no" - default is yes/disabled (WEM-1690)
- Added config "Save draft on wishlist" and logic to add drafts into wishlist-item
  and move between cart-item and wishlist-item (WEM-1874, WEM-1690, WEM-1940)
- Added functionality with new setting use_all_stores_default_templates_if_no_templates_assigned_on_store to load 
  default templates, if it has  permission over a new setting and if api keys in specified store and the default store 
  are identical (WEM-1958)

### Fixed
- Multiple printformer products on same product can be assigned and used in checkout process correctly (WEM-1690)
- Preselection after editor exit is adjusted for default magento swatches renderer js file, (magento 2.4.5 can still
  produce issues cause this swatches renderer file is broken in magento c.e. 2.4.5 (WEM-1749)
- After draft creation from guest user, when we switch to other user, we have exceptions caused by different draft-owner
  when, entering draft again. This is fixed now with printformer merge identifier command to transfer all rights from
  guest user identifier to logged user identifier (WEM-1750)

<br><br>

## [100.8.71] - 13-12-2022
### Fixed
- Fix sales plugin checkout error - WEM-1895 & WEM-1972
- Fix product type check error caused on php Version 7.4 - WEM-1760
  <br>

<br><br>

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

<br><br>

## [100.8.69] - 01-10-2022

### Fixed
- after redirect to cart when leaving editor, the variants chosen are lost - WEM-1851

<br><br>

## [100.8.68] - 30-09-2022

### Fixed
- Sync issues over specific pf-configurations (default disabled) - WEM-1834

### Added
- Show mandator-name in core config - WEM-1354

<br><br>

## [100.8.67] - 30-09-2022

### Fixed
- Url malformed with "store config only" fixing issue without default-config, creating user pf-identifier - (WEM-1775)

### Added
- New configuration (yes, no) to transfer user data over payload with attributes to printformer via api-ext/user endpoint - (WEM-1771)

<br><br>

## [100.8.66] - 30-09-2022

### Changed
- New Magento Version "2.4.4" Magento Open Source" (with php 8)

<br>

### Changed code for php 8.1 compatibility changes about
- Passing null to non-nullable internal function parameters is deprecated
- Passing null to first parameter ($string) of type string is deprecated
- Deprecate required parameters after optional parameters in function/method signatures