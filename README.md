
# **Manual Install / Upgrade**
- Locate the /app/code directory which should be under the magento root installation.
- If the code folder is not there, create it.
- Remove the "Emarsys" directory from "MAGENTO_ROOT/app/code" directory if exists
- Download the latest files from master branch and extract the contents
- Copy "emarsys/app/code/Emarsys" from the downloaded codebase to the "MAGENTO_ROOT/app/code" directory on you Magento instance


### Magento Setup
- Make sure you have the correct file and folder permissions set on your magento installation so that the magnento store can install the app.
- Refer to the Magento 2 documentation for full instructions on how to install an app, the commands should be similar to the following:
```php bin/magento setup:upgrade - This tells magento to install the app.```
```php bin/magento setup:static-content:deploy - This tells magento to deploy static files.```
```php bin/magento cache:flush - This flushes the cache so the app appears in the admin area correctly.```

# Release Notes

## v1.0.4

- Fixed compile errors in production mode
