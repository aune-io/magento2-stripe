# Magento 2 Stripe Payments
Stripe payments integration module for Magento 2.

[![Build Status](https://travis-ci.org/aune-io/magento2-stripe.svg?branch=master)](https://travis-ci.org/aune-io/magento2-stripe)
[![Coverage Status](https://coveralls.io/repos/github/aune-io/magento2-stripe/badge.svg?branch=master)](https://coveralls.io/github/aune-io/magento2-stripe?branch=master)
[![Latest Stable Version](https://poser.pugx.org/aune-io/magento2-stripe/v/stable)](https://packagist.org/packages/aune-io/magento2-stripe)
[![Latest Unstable Version](https://poser.pugx.org/aune-io/magento2-stripe/v/unstable)](https://packagist.org/packages/aune-io/magento2-stripe)
[![Total Downloads](https://poser.pugx.org/aune-io/magento2-stripe/downloads)](https://packagist.org/packages/aune-io/magento2-stripe)
[![License](https://poser.pugx.org/aune-io/magento2-stripe/license)](https://packagist.org/packages/aune-io/magento2-stripe)

## System requirements
This extension supports the following versions of Magento:

*	Community Edition (CE) versions 2.1.x, 2.2.x and 2.3.x
*	Enterprise Edition (EE) versions 2.1.x, 2.2.x and 2.3.x

## Installation
1. Require the module via Composer
```bash
$ composer require aune-io/magento2-stripe
```

2. Enable the module
```bash
$ bin/magento module:enable Aune_Stripe
$ bin/magento setup:upgrade
```

3. Login to the admin
4. Go to Stores > Configuration > Sales > Payment Methods > Aune - Stripe
5. Enter your Stripe API Keys and set the payment method as active
6. (Optional) Enable customer storing in Stripe or Vault to allow customers to reuse their payment methods

## SCA
Version 4 of the extension supports SCA with the following warnings:
* vaulting it's not backward compatibile: previous versions of the extension used the source id as gateway token, version 4 uses the payment method id. If you are upgrading, you can either empty the vault or write a script to change the gateway token.
* partial capture will refund the remaining amount: this is due to how capturing a payment intent works, see the official documentation [here](https://stripe.com/docs/api/payment_intents/capture#capture_payment_intent-amount_to_capture).
* payment intent confermation is done on the frontend: it is strongly reccommended to use _Authorize_ as _Payment Action_ and capture payments when generating the Magento invoice, to avoid having captured transaction without orders in case of timeouts or server errors.

## Authors, contributors and maintainers

Author:
- [Renato Cason](https://github.com/renatocason)

## License
Licensed under the Open Software License version 3.0
