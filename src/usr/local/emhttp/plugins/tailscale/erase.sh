#!/bin/bash

. /usr/local/php/unraid-tailscale-utils/log.sh

log "Stopping Tailscale"
/etc/rc.d/rc.tailscale stop

log "Erasing Configuration"
rm -f /boot/config/plugins/tailscale/tailscale.cfg
rm -rf  /boot/config/plugins/tailscale/state/

log "Restarting Tailscale"
/etc/rc.d/rc.tailscale start

sleep 10