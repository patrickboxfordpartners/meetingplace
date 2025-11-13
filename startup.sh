#!/bin/bash
pip install --upgrade pip
pip install -r requirements.txt
```

3. **In Azure Portal**, go to your App Service → Configuration → General settings → Startup Command and add:
```
/home/site/wwwroot/startup.sh
