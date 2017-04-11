# Rissc_Printformer
Magento 2 and Rissc Printformer

Installation

1. Add module repository to composer.json in magento root

    "repositories": [
        {
            "type": "git",
            "url": "git@bitbucket.org:risscstuttgart/rissc_printformer.git"
        }
    ],

2. Add "rissc/module-printformer":"~100.1.7" line to composer.json in magento root

    "require": {
        "rissc/module-printformer":"~100.1.7"
    }

3. Run ./composer.phar update
4. Run php bin/magento module:enable Rissc_Printformer
5. Run php bin/magento setup:upgrade
6. Run php bin/magento cache:flush
