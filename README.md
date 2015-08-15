# Mitel 5000 CP PRTG Monitoring
This repo contains code to read status from Mitel 5000 AWS and push to PRTG.

The mitel 5000 doesn't provide any kind of SNMP, so instead we log into the web page periodically and push data to PRTG instead.

# Usage
Create `src/config.local.php` to include the variables from `src/config.php` that are appropriate to your environment, then schedule a cron to run `src/pushStatusToPRTG.php` as often as you care about polling the data.

You will also need to create a `HTTP Push Data Advanced BETA` sensor in PRTG for this to push data to.

The channel values will be 0 for "Good", 1 for "Fair" and 2 for "Unstable" and -1 for "Unknown". I would suggest erroring on anything non-0.
