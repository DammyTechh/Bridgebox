# Admin Actions Sudoers Setup

This app can run admin actions (start/stop services, hotspot control, reboot/shutdown) using Symfony Process.
These commands require sudo without a password. If sudo is not configured, actions will fail safely and log an error.

## Environment Flags

Set these in `.env`:

```
ADMIN_ACTIONS_ENABLED=true
ADMIN_ACTIONS_ALLOW_SUDO=true
```

## Example sudoers file

Create a file like `/etc/sudoers.d/bridgebox` and replace `www-data` with the user running PHP/FPM.
Adjust service names to match your system if needed.

```
www-data ALL=(root) NOPASSWD: /bin/systemctl start nginx
www-data ALL=(root) NOPASSWD: /bin/systemctl stop nginx
www-data ALL=(root) NOPASSWD: /bin/systemctl start php-fpm
www-data ALL=(root) NOPASSWD: /bin/systemctl stop php-fpm
www-data ALL=(root) NOPASSWD: /usr/bin/nmcli con up Hotspot
www-data ALL=(root) NOPASSWD: /usr/bin/nmcli con down Hotspot
www-data ALL=(root) NOPASSWD: /sbin/reboot
www-data ALL=(root) NOPASSWD: /sbin/shutdown -h now
```

Notes:
- If your PHP service is `php8.2-fpm` (or similar), update the sudoers entries to match.
- Keep sudoers files restrictive and validate with `visudo -cf /etc/sudoers.d/bridgebox`.
