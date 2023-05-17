[<img src="view/adminhtml/web/images/rissc_logo_2020.png" width="100" height="100">](https://www.rissc.de/web2print-mit-magento2/)

# Rissc Printformer Extension for Magento 2

### COMPOSER INSTALLATION

1. Check for github access
  - auth.json contains f.e.
    `{"github-oauth": {"github.com": "XXXXXXXX"}}`
  - composer.json f.e.
    `{"repositories": [{"type": "vcs","url": "https://github.com/risscsolutions/printformer-magento-2.git"}]}`
2. On project folder run: `./composer.phar require rissc/module-printformer -vvv`
3. Verify suggested main application version / run: `./composer.phar update rissc/module-printformer`

<br>

### MANUAL INSTALLATION

1. Add module into app/code with corresponding namespace
2. Make sure to require dependencies into composer.json: (compare composer.json's)
- lcobucci/jwt
- guzzlehttp/guzzle
- erusev/parsedown

<br>

### COMPATIBILITY

#### Our module is tested currently with magento version 2.4.5 [SYSTEM REQUIREMENTS](https://experienceleague.adobe.com/docs/commerce-operations/installation-guide/system-requirements.html)

**All relevant changes are noted in our [Changelog](CHANGELOG.md)**

<br>

### IMPORTANT UPDATE INFORMATION
#### Deprecation info about releases
CHANGELOG.md will be renamed to changelog_old.md and removed in the future. Our new versioning process will cover
conventional commits and semantic versioning. 