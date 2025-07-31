#!/bin/bash

. /usr/local/emhttp/plugins/tailscale/log.sh

log "Stopping Tailscale"
/etc/rc.d/rc.tailscale stop

log "Erasing Configuration"
rm -f /boot/config/plugins/tailscale/tailscale.cfg
rm -rf  /boot/config/plugins/tailscale/state/

log "Restarting Tailscale"
echo "sleep 5 ; /usr/local/emhttp/plugins/tailscale/update-settings.sh" | at now 2>/dev/null