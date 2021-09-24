## Rissc Printformer Extension and Magento 2

### Installation

- 1: Add module into app/code with corresponding namespace

- 2: Make sure to require dependencies into composer.json: (compare composer.json's)
  - "lcobucci/jwt": "3.3.1" (you can verify it in the vendor-folder or in the composer.lock file)
  - "guzzlehttp/guzzle": "6.4.1",
  - "erusev/parsedown": "1.7.4" (you can verify it in the vendor-folder or in the composer.lock file)

- 3: Enable module "Rissc_Printformer" and run magento compilation steps / deployments

<br>

## Git Tags for Composer

### Composer needs a v as a version prefix, otherwise it sees the package as unstable

- Bad: 100.8.99
- Good: v100.8.99