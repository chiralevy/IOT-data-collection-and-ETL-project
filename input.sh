#!/bin/sh -e

ubus call gps info >> /tmp/mounts/SD-P1/gps/gpsoutput`date +"%Y-%m-%d_%H%M%S"`.txt
fswebcam --no-banner -r 1920x1080 /tmp/mounts/SD-P1/camera/snapshot`date +"%Y-%m-%d_%H%M%S"`.jpg
arecord -d 50 -N /tmp/mounts/SD-P1/audio/soundbite`date +"%Y-%m-%d_%H%M%S"`.wav