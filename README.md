# magento-2 plugin

## Supported magento version 2.3.X, 2.4.X 
The plugin has been tested with most versions of Magento at every iteration. We recommend using the latest version of Magento, but if that is not possible for some reason, test the plugin with your Magento version and it would probably function properly.


1. Upload app/code/Gateway (all files and folder) at you server end
2. Run below command:
	* php bin/magento module:enable Gateway_Tap
	* php bin/magento setup:upgrade
	* php bin/magento setup:static-content:deploy
Configuration:
1. goto Admin->Store->Configuration->Sales->Payment Method->Tap, and fill the details here and save them
	* Enabled - Yes
    * Title - Tap
	* Test Public Key - pk_test_********************jYzh
	* Test Secret Key - sk_test_********************tg5y
	* Staging Mode - Yes
	
goto Admin->System->Cache Management and Clear all Cache
Now you can collect payment via Tap

## Screenshots
 

![tap_payments_configurations](https://github.com/Tap-Payments/magento-plugin/assets/36191420/35cf3bc1-6283-4680-bc30-47c9e5c0673e)
