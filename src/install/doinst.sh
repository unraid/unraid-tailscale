( cd etc/rc.d ; rm -rf rc.tailscale )
( cd etc/rc.d ; ln -sf /usr/local/emhttp/plugins/tailscale/rc.tailscale rc.tailscale )
( cd etc/cron.daily ; rm -rf tailscale-daily )
( cd etc/cron.daily ; rm -rf check-tailscale-key )
( cd etc/cron.daily ; ln -sf /usr/local/emhttp/plugins/tailscale/daily.sh tailscale-daily )
( cd usr/local/emhttp/plugins/tailscale/event ; rm -rf array_started )
( cd usr/local/emhttp/plugins/tailscale/event ; ln -sf ../restart.sh array_started )
( cd usr/local/emhttp/plugins/tailscale/event ; rm -rf stopped )
( cd usr/local/emhttp/plugins/tailscale/event ; ln -sf ../restart.sh stopped )

chmod 0644 /etc/logrotate.d/tailscale
chown root:root /etc/logrotate.d/tailscale