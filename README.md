# payever OXID-eShop module

This repository contains payever payments module for [OXID e-commerce platform](https://www.oxid-esales.com/en/e-commerce-platform/shop-systems/). 

## OXID versions support

Currently, this module is compatible with OXID-eShop Community Edition starting from **4.7.X** version and up.

## Installation

## Local shop setup
- official docs https://docs.oxid-esales.com/developer/en/6.0/getting_started/installation/eshop_installation.html
- demo data https://github.com/OXID-eSales/oxideshop-demodata-installer

## Local plugin install
- https://docs.oxid-esales.com/developer/en/6.2/development/modules_components_themes/project/module_configuration/modules_installation.html
- use admin interface see guide. Plugin location `<shop_dir>/source/modules`


# Testing
### Behat
Before running behat tests make sure that you have:

- ran `composer install`
- set proper `behat.yml` config values (see `behat.yml.dist`)
- started selenium2 server ([download](https://www.seleniumhq.org/download/)) `java -jar /path/to/selenuim-standalone.jar`
- `chromedriver` is available in your `PATH` env variable ([download](http://chromedriver.chromium.org/downloads) or `brew install chromedriver` for Mac OS)
- install `chrome` https://googlechromelabs.github.io/chrome-for-testing/#stable
- `chromedriver` is available in your `PATH` env variable ([download](http://chromedriver.chromium.org/downloads) or `brew install chromedriver` for Mac OS)
- Important: `chromedriver` must be same version as `chrome` browser version
- selenium run in background `java -jar /<path>/selenium-server-standalone-x.xx.x.jar -Dwebdriver.chrome.driver=/<path>/chromedriver  > /dev/null 2>&1 &`
- payever stub server run `./vendor/payever/plugins-stub/bin/stub-server localhost:9090`
- behat test run `./vendor/behat/behat/bin/behat --config behat.yml`

### Unittest
- simple unittest `./vendor/bin/phpunit -c phpunit.xml`
- HTML codecov report (xdebug php module require) `php -d xdebug.mode=coverage ./vendor/bin/phpunit -c ./phpunit.xml --coverage-html /var/www/html/clover`

### Phpmd https://phpmd.org/documentation/
- `./vendor/bin/phpmd ./src/payever text ./phpmd.xml`

### Phpcs
- `./vendor/bin/phpcs --standard=./phpcs.xml ./src/payever`

### Option 1: Download from OXID exchange

Please go to [module download page](https://exchange.oxid-esales.com/Order-and-Delivery/Payment/payever-Your-checkout-everywhere-1-1-0-Stable-CE-4-0-x-4-9-x.html#versionTab) and download the latest version. 

(NOTE: The order inside "Versions" tab is arbitrary, so please make sure to pick the latest version)

### Option 2: Install via Composer 

_NOTE: For OXID-eShop version 6 and above only_

Run the following CLI command inside your shop root directory:

```
composer require payever/payever-integration-oxid
```  

## User documentation

You can find the user guide [here](https://support.payever.org/hc/en-us/articles/360023900334-OXID).
