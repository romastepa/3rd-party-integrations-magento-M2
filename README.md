#Emarsys Magento 2 Integration

## Magento Setup

### Manually
- create folder `app/code/Emarsys/Emarsys/`
- `cd app/code/Emarsys/Emarsys/`
- `git clone https://github.com/romastepa/3rd-party-integrations-magento-M2.git .`
### Composer
- `composer require emarsys/emarsys`

#### from magento root folder
- `bin/magento module:enable Emarsys_Emarsys`
- `bin/magento setup:upgrade`
- `bin/magento setup:di:compile`
- `bin/magento cache:flush`


# Release Notes

## v1.0.19
- Magento 2.3.4 support
- Fixed errors and partly warnings of code standard magento2