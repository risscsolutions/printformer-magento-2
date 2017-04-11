# Rissc_Printformer
Magento 2 and Rissc Printformer

Installation

1. Add module repository to composer.json in magento root

    "repositories": [
        {
            "type": "git",
            "url": "git@github.com:babenkocommerce/Rissc_Printformer.git"
        }
    ],

2. Add "rissc/module-printformer":"dev-master" line to composer.json in magento root

    "require": {
        "rissc/module-printformer":"dev-master"
    }

3. Run composer update
4. Run ./bin/magento module:enable Rissc_Printformer
5. Run ./bin/magento setup:upgrade
6. Run ./bin/magento cache:flush
7. Run rm -rf var/generation/*
