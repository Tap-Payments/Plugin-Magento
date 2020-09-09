# magento-2 plugin
1. upload app/code/Gateway (all files and folder) at you server end
2. Run below command:
	1. php bin/magento module:enable Gateway_Tap
	2. php bin/magento setup:upgrade
	3. php bin/magento setup:static-content:deploy
Configuration:
1. goto Admin->Store->Configuration->Sales->Payment Method->Tap, and fill the details here and save them
	* Enabled - Yes
    * Title - Tap
	* Test Public Key - pk_test_Vlk842B1EA7tDN5QbrfGjYzh
	* Test Secret Key - sk_test_kovrMB0mupFJXfNZWx6Etg5y
	* Staging Mode - Yes
	
goto Admin->System->Cache Management and Clear all Cache
Now you can collect payment via Tap

## Screenshots
 
![Knet Configuration Page screenshot 01](https://content.screencast.com/users/m.khan3005/folders/Capture/media/b118761b-2783-42aa-837f-c980b5667b35/LWR_Recording.png)
![Tap Configuration Page screenshot 02](https://content.screencast.com/users/m.khan3005/folders/Capture/media/b118761b-2783-42aa-837f-c980b5667b35/LWR_Recording.png)



