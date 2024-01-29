/*          How to install Easebuzz Magento kit in the Mangeto Project         */

Steps: 
    1. Download Easebuzz Magento Plugin from Github
    2. Create new folder code in  your_magento_project/app/
    3. Paste Easebuzz kit in code folder
    4. Go to hte root directory of the your project
    5. Run Commands: 'php bin/magento setup:upgrade' and 'php bin/magento setup:di:compile'

Configuration Setup:
    1. go to the admin
    2. stores --> configuration --> sales --> payment methods --> Other Payment Methods --> Easebuzz payment
    3. Configure the Settings like - add key and salt and enable the plugin.
    4. Don't enble Iframe in Sandbox Env. 


Clear all caches