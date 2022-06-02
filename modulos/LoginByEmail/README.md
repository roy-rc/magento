# Mage2 Module Customcode LoginByEmail

    ``customcode/module-loginbyemail``

 - [Main Functionalities](#markdown-header-main-functionalities)
 - [Installation](#markdown-header-installation)
 - [Configuration](#markdown-header-configuration)
 - [Specifications](#markdown-header-specifications)
 - [Attributes](#markdown-header-attributes)


## Main Functionalities
Login customer by email or code sent to his email address.

## Installation
\* = in production please use the `--keep-generated` option

### Type 1: Zip file

 - Unzip the zip file in `app/code/Customcode`
 - Enable the module by running `php bin/magento module:enable Customcode_LoginByEmail`
 - Apply database updates by running `php bin/magento setup:upgrade`\*
 - Flush the cache by running `php bin/magento cache:flush`

### Type 2: Composer

 - Make the module available in a composer repository for example:
    - private repository `repo.magento.com`
    - public repository `packagist.org`
    - public github repository as vcs
 - Add the composer repository to the configuration by running `composer config repositories.repo.magento.com composer https://repo.magento.com/`
 - Install the module composer by running `composer require customcode/module-loginbyemail`
 - enable the module by running `php bin/magento module:enable Customcode_LoginByEmail`
 - apply database updates by running `php bin/magento setup:upgrade`\*
 - Flush the cache by running `php bin/magento cache:flush`


## Configuration

 - Enable Login by Email (login_by_email/configuration/module_enabled)

 - Minutes between requests (login_by_email/configuration/minutes_between_request)


## Specifications

 - Controller
	- frontend > customcodelogin/customer/login

 - Controller
	- frontend > customcodelogin/customer/entercode



## Attributes



