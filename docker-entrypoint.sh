#!/bin/bash
set -e

# 1. Start background services
service cron start

# 2. Wait for MySQL (Important so the web wizard doesn't error out immediately)
echo "Waiting for MySQL (db:3306) to become available..."
while ! timeout 1s bash -c "echo > /dev/tcp/db/3306" 2>/dev/null; do
    sleep 2
done
echo "MySQL is up!"

# 3. Prepare for Web Installation
# osTicket requires a writable include/ost-config.php to start the installer
CONFIG_FILE="/var/www/html/include/ost-config.php"
SAMPLE_FILE="/var/www/html/include/ost-sampleconfig.php"

if [ ! -f "$CONFIG_FILE" ]; then
    echo "No ost-config.php found. Creating from sample..."
    cp "$SAMPLE_FILE" "$CONFIG_FILE"
    # Set 0666 so the web user (www-data) can write the setup details to it
    chmod 0666 "$CONFIG_FILE"
fi

# 4. Ensure correct ownership for the web server
chown -R www-data:www-data /var/www/html

echo "Starting Apache... Visit http://localhost:PORT to finish setup."
exec apache2-foreground