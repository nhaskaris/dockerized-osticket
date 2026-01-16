#!/bin/bash

# 1. Start background services
service cron start

# 2. Wait for MySQL (Important so the web wizard doesn't error out immediately)
echo "Waiting for MySQL (db:3306) to become available..."
while ! timeout 1s bash -c "echo > /dev/tcp/db/3306" 2>/dev/null; do
    sleep 2
done
echo "MySQL is up!"

# 3. Prepare for Installation
CONFIG_FILE="/var/www/html/include/ost-config.php"
SAMPLE_FILE="/var/www/html/include/ost-sampleconfig.php"

if [ ! -f "$CONFIG_FILE" ] || ! grep -q "define('OSTINSTALLED',TRUE);" "$CONFIG_FILE"; then
    echo "osTicket not installed. Preparing for automatic installation..."
    
    # Create writable config file from sample
    cp "$SAMPLE_FILE" "$CONFIG_FILE"
    chmod 0666 "$CONFIG_FILE"
    
    # 4. Start Apache in background
    apache2-foreground &
    APACHE_PID=$!
    
    # Wait for Apache to start
    echo "Waiting for Apache to start..."
    sleep 3
    
    # 5. Automatically submit the installer form
    echo "Submitting installation form with environment variables..."
    
    curl -s -X POST http://localhost/setup/install.php \
        -d "s=install" \
        -d "name=$(printf '%s\n' "${OST_NAME:-osTicket}" | sed 's/[&/\]/\\&/g')" \
        -d "email=${OST_EMAIL:-support@example.com}" \
        -d "fname=${OST_FNAME:-Admin}" \
        -d "lname=${OST_LNAME:-User}" \
        -d "admin_email=${OST_ADMIN_EMAIL:-admin@example.com}" \
        -d "username=${OST_USERNAME:-admin}" \
        -d "passwd=${OST_PASSWD:-admin}" \
        -d "passwd2=${OST_PASSWD2:-admin}" \
        -d "prefix=${OST_DB_PREFIX:-ost_}" \
        -d "dbhost=${OST_DB_HOST:-db}" \
        -d "dbname=${OST_DB_NAME:-osticket}" \
        -d "dbuser=${OST_DB_USER:-osticket}" \
        -d "dbpass=${OST_DB_PASS:-password}" \
        -d "timezone=${OST_TIMEZONE:-UTC}" \
        -d "lang_id=1" > /tmp/install_response.txt 2>&1
    
    echo "Installation response saved."
    
    # Wait a moment for the installation to complete
    sleep 5
    
    # Check if installation was successful by looking for the success page or config file
    if grep -q "OSTINSTALLED.*TRUE" "$CONFIG_FILE" 2>/dev/null; then
        echo "Installation completed successfully!"
        
        # Rename setup directory for security
        if [ -d "/var/www/html/setup" ]; then
            mv /var/www/html/setup /var/www/html/setup_disabled_$(date +%s)
            echo "Setup directory secured."
        fi

        # Lock down config file and ownership post-install
        chmod 0644 "$CONFIG_FILE"
        chown -R www-data:www-data /var/www/html
    else
        echo "Installation may still be in progress or encountered an issue."
        echo "Check /tmp/install_response.txt for details."
    fi
    
    # Keep Apache running
    wait $APACHE_PID
else
    echo "osTicket already installed."
    # 4. Ensure correct ownership for the web server
    chown -R www-data:www-data /var/www/html
    echo "Starting Apache..."
    exec apache2-foreground
fi