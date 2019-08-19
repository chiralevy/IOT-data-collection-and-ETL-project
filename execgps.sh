#!/bin/sh -e
ubus call gps info >> /tmp/mounts/SD-P1/gps/gpsoutput`date +"%Y-%m-%d_%H%M%S"`.txt
