# magento-2 plugin
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
 
![Knet Configuration Page screenshot 01](https://content.screencast.com/users/m.khan3005/folders/Capture/media/587b7d7b-5ff1-4538-8e38-e7e89dcc04ee/LWR_Recording.png)
![Tap Configuration Page screenshot 02](https://content.screencast.com/users/m.khan3005/folders/Capture/media/295eeae9-4b00-4e12-ba9d-3833f4476f2d/LWR_Recording.png)



