#!/bin/bash

. /usr/local/emhttp/plugins/tailscale/log.sh

log "Restarting Tailscale in 5 seconds"
echo "sleep 5 ; /usr/local/emhttp/plugins/tailscale/update-settings.sh" | at now 2>/dev/null
