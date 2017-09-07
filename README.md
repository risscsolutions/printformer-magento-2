# Rissc_Printformer
Magento 2 and Rissc Printformer

Installation

1. Add module repository to composer.json in magento root

    ```PHP
    "repositories": [
        {
            "type": "git",
            "url": "git@bitbucket.org:risscstuttgart/rissc_printformer.git"
        }
    ],
    ```

2. Execute in Terminal:
    ```PHP
    ./composer.phar require rissc/module-printformer -o
    ```

4. Run `php bin/magento module:enable Rissc_Printformer`
5. Run `php bin/magento setup:upgrade`
6. Run `php bin/magento cache:flush`