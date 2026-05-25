#!/bin/bash
set -e
LOG="/var/log/bridgebox-setup.log"
FLAG="/etc/bridgebox/setup_done"
#!/bin/bash
# One-time setup script for BridgeBox (safer + idempotent + dry-run)
# Usage: onetimesetup.sh [--dry-run] [--no-reboot]

set -euo pipefail

LOG="/var/log/bridgebox-setup.log"
FLAG="/etc/bridgebox/setup_done"
APP_DIR="/var/www/bridgebox"
DB_FILE="$APP_DIR/database/database.sqlite"

# Defaults for flags
DRY_RUN=false
NO_REBOOT=false

usage() {
	cat <<EOF
BridgeBox one-time setup
Usage: $0 [--dry-run] [--no-reboot] [-h|--help]
	--dry-run    Print actions instead of performing them (safe testing)
	--no-reboot  Do not reboot after setup
	-h, --help   Show this help
EOF
}

# Simple arg parsing
while [[ $# -gt 0 ]]; do
	case "$1" in
		--dry-run) DRY_RUN=true; shift ;;
		--no-reboot) NO_REBOOT=true; shift ;;
		-h|--help) usage; exit 0 ;;
		*) echo "Unknown option: $1"; usage; exit 1 ;;
	esac
done

log() {
	local msg="$1"
	if [ "$DRY_RUN" = true ]; then
		echo "[DRY-RUN] $msg"
	else
		echo "$msg" | tee -a "$LOG"
	fi
}

# Run command, respecting dry-run. Returns command exit code (but doesn't exit script).
run() {
	local cmd="$1"
	log "RUN: $cmd"
	if [ "$DRY_RUN" = true ]; then
		return 0
	fi
	bash -c "$cmd" >> "$LOG" 2>&1
	return $?
}

# Run command but do not let non-zero exit stop the script; log result.
run_allow_fail() {
	local cmd="$1"
	run "$cmd"
	local rc=$?
	if [ $rc -ne 0 ]; then
		log "WARNING: command failed (rc=$rc): $cmd"
	fi
	return $rc
}

# Write file only if content differs (idempotent). Respects dry-run.
write_if_changed() {
	local path="$1"
	local tmp
	tmp=$(mktemp)
	cat > "$tmp"
	if [ -f "$path" ]; then
		if cmp -s "$tmp" "$path"; then
			rm -f "$tmp"
			log "No change for $path"
			return 0
		fi
	fi
	if [ "$DRY_RUN" = true ]; then
		log "[DRY-RUN] Would write $path (changed)"
		rm -f "$tmp"
		return 0
	fi
	mv "$tmp" "$path"
	log "Wrote $path"
}

log "BridgeBox Setup Started"

# If not root and not dry-run, abort early
if [ "$(id -u)" -ne 0 ] && [ "$DRY_RUN" = false ]; then
	echo "This script must be run as root. Use --dry-run to test without root." >&2
	exit 1
fi

if [ -f "$FLAG" ]; then
	log "Setup already completed (flag $FLAG present). Exiting."
	exit 0
fi

log "Checking package manager..."
if command -v apt-get >/dev/null 2>&1; then
	PKG_CMD="apt-get"
else
	log "No apt-get found; package install step will be skipped"
	PKG_CMD=""
fi

if [ -n "$PKG_CMD" ]; then
	log "Updating system packages"
	run "$PKG_CMD update"

	PACKAGES=(nginx php-fpm php-sqlite3 php-xml php-mbstring php-curl php-zip unzip network-manager)
	log "Installing packages: ${PACKAGES[*]}"
	run "$PKG_CMD -y install ${PACKAGES[*]}"
fi

log "Enabling nginx service (if present)"
if command -v systemctl >/dev/null 2>&1; then
	run_allow_fail "systemctl enable nginx"
else
	log "systemctl not available; skipping service enable"
fi

log "Configuring hotspot (NetworkManager)"
if command -v nmcli >/dev/null 2>&1; then
	# If a Hotspot connection exists, modify it; otherwise create it. Keep idempotent.
	if nmcli -t -f NAME connection show | grep -qx "Hotspot"; then
		log "Hotspot connection exists; modifying settings"
		run_allow_fail "nmcli connection modify Hotspot connection.autoconnect yes"
		run_allow_fail "nmcli connection modify Hotspot connection.autoconnect-priority 100"
		run_allow_fail "nmcli connection modify Hotspot ipv4.method shared"
		run_allow_fail "nmcli connection modify Hotspot ipv4.addresses 192.168.4.1/24"
	else
		log "Creating Hotspot connection"
		run_allow_fail "nmcli dev wifi hotspot ifname wlan0 ssid LMS_OFFLINE password OfflineLMS12345"
		run_allow_fail "nmcli connection modify Hotspot connection.autoconnect yes"
	fi
else
	log "nmcli not found; skipping hotspot configuration"
fi

log "Setting up application directory and DB"
run "mkdir -p '$APP_DIR/database'"
if [ "$DRY_RUN" = false ]; then
	touch "$DB_FILE"
	chown -R www-data:www-data "$APP_DIR" || log "chown failed (maybe www-data not present)"
	chmod -R 775 "$APP_DIR/storage" "$APP_DIR/bootstrap/cache" || log "chmod failed (paths may not exist yet)"
else
	log "[DRY-RUN] Would create DB file: $DB_FILE and adjust ownership/permissions"
fi

log "Configuring nginx site"
NGINX_SITE=/etc/nginx/sites-available/bridgebox
write_if_changed "$NGINX_SITE" <<'NGINX_EOF'
server {
		listen 80;
		server_name _;
		root /var/www/bridgebox/public;
		index index.php;
		location / {
				try_files $uri $uri/ /index.php?$query_string;
		}
		location ~ \.php$ {
				include snippets/fastcgi-php.conf;
				fastcgi_pass unix:/run/php/php-fpm.sock;
		}
}
NGINX_EOF

if [ "$DRY_RUN" = false ]; then
	ln -sf "$NGINX_SITE" /etc/nginx/sites-enabled/bridgebox
	rm -f /etc/nginx/sites-enabled/default || true
	run_allow_fail "systemctl restart nginx"
else
	log "[DRY-RUN] Would enable nginx site and restart nginx"
fi

log "Running Laravel artisan tasks (best-effort; failures are logged)"
if [ -d "$APP_DIR" ]; then
	if [ "$DRY_RUN" = false ]; then
		cd "$APP_DIR"
		run_allow_fail "php artisan key:generate --force"
		run_allow_fail "php artisan migrate --force"
		run_allow_fail "php artisan config:cache"
		run_allow_fail "php artisan route:cache"
		run_allow_fail "php artisan view:cache"
	else
		log "[DRY-RUN] Would run artisan commands in $APP_DIR"
	fi
else
	log "App directory $APP_DIR does not exist; skipping artisan commands"
fi

# Finalize: write flag and reboot (unless asked not to)
if [ "$DRY_RUN" = false ]; then
	mkdir -p /etc/bridgebox
	touch "$FLAG"
	log "Setup complete. Flag $FLAG created."
	if [ "$NO_REBOOT" = false ]; then
		log "Rebooting system now"
		run "reboot"
	else
		log "Reboot suppressed by --no-reboot"
	fi
else
	log "[DRY-RUN] Setup complete (dry-run). No changes were written."
fi