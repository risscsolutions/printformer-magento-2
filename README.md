# Git Tags für Composer
Composer braucht ein v als Versionsprefix, ansonsten sieht er das Package als unstable an

Bad: 100.8.99
Good: v100.8.99


# Rissc_Printformer
Magento 2 and Rissc Printformer

Installation

1. Add module into app/code with corresponding namespace

2. Make sure to require dependencies into composer.json: (compare composer.json's) 
    ```
      "guzzlehttp/guzzle": "6.4.1",
      "lcobucci/jwt": "3.3.1"
    ```

(you can verify it in the vendor-folder or in the composer.lock file)

3. Enable module "Rissc_Printformer" and run magento compilation steps / deployments
