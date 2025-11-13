#!/bin/bash
apt-get update -qq
apt-get install -y python3-pip
pip3 install --upgrade pip --break-system-packages
pip3 install -r requirements.txt --break-system-packages


3. **In Azure Portal**, go to your App Service → Configuration → General settings → Startup Command and add:

/home/site/wwwroot/startup.sh
