#!/bin/sh

log() {
  LOG_TIME=`date '+%Y/%m/%d %H:%M:%S'`
  CALLER=`basename "$0"`
  echo "$LOG_TIME $CALLER: $1" >> /var/log/tailscale-utils.log
}