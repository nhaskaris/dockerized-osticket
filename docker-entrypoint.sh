#!/bin/bash
set -e

# 1. Start background services
service cron start

# 2. Wait for MySQL (Ensures the DB is ready before we attempt installation)
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
    
    # 4. Start Apache in background to handle the install request
    apache2-foreground &
    APACHE_PID=$!
    
    # Wait for Apache to be ready to accept the curl request
    sleep 5
    
    # 5. Automatically submit the installer form using URL-encoding for safety
    echo "Submitting installation form..."
    
    curl -s -X POST http://localhost/setup/install.php \
        --data-urlencode "s=install" \
        --data-urlencode "name=${OST_NAME:-osTicket Support}" \
        --data-urlencode "email=${OST_EMAIL:-support@example.com}" \
        --data-urlencode "fname=${OST_FNAME:-Admin}" \
        --data-urlencode "lname=${OST_LNAME:-User}" \
        --data-urlencode "admin_email=${OST_ADMIN_EMAIL:-admin@example.com}" \
        --data-urlencode "username=${OST_USERNAME:-admin}" \
        --data-urlencode "passwd=${OST_PASSWD:-admin123}" \
        --data-urlencode "passwd2=${OST_PASSWD:-admin123}" \
        --data-urlencode "prefix=${OST_DB_PREFIX:-ost_}" \
        --data-urlencode "dbhost=${OST_DB_HOST:-db}" \
        --data-urlencode "dbname=${OST_DB_NAME:-osticket}" \
        --data-urlencode "dbuser=${OST_DB_USER:-osticket}" \
        --data-urlencode "dbpass=${OST_DB_PASS:-password}" \
        --data-urlencode "timezone=${OST_TIMEZONE:-UTC}" \
        --data-urlencode "lang_id=1" > /tmp/install_response.txt 2>&1
    
    # 6. Wait for installation to finish writing to the config file
    echo "Waiting for installation to finalize..."
    SUCCESS=0
    for i in {1..30}; do
        if grep -q "define('OSTINSTALLED',TRUE);" "$CONFIG_FILE" 2>/dev/null; then
            echo "Installation completed successfully!"
            SUCCESS=1
            break
        fi
        sleep 2
    done
    
    if [ $SUCCESS -eq 1 ]; then
        # Rename setup directory for security
        if [ -d "/var/www/html/setup" ]; then
            mv /var/www/html/setup /var/www/html/setup_disabled_$(date +%s)
            echo "Setup directory secured."
        fi

        # 7. Optional dev seeding: create a test user and disable email verification
        if [ "${DEV_SEED_USER:-0}" = "1" ]; then
            echo "Seeding dev user and disabling email verification..."
            cat > /tmp/dev-seed.php <<'PHPCODE'
<?php
require_once '/var/www/html/bootstrap.php';
require_once INCLUDE_DIR . 'class.osticket.php';
require_once INCLUDE_DIR . 'class.user.php';
require_once INCLUDE_DIR . 'class.config.php';

$ost = osTicket::start();

// Disable client email verification directly in DB
$sql = "UPDATE " . CONFIG_TABLE . " SET `value` = '0' WHERE `namespace` = 'core' AND `key` = 'client_verify_email'";
db_query($sql);

$email = getenv('DEV_USER_EMAIL') ?: 'test@test.gr';
$username = getenv('DEV_USER_USERNAME') ?: 'test_user';
$password = getenv('DEV_USER_PASSWORD') ?: 'test_password';

if (!($user = User::lookupByEmail($email))) {
    $user = User::fromVars(array(
        'email' => $email,
        'name'  => 'Dev Test User',
    ));

    if ($user && $user->save()) {
        $account = UserAccount::create(array(
            'user_id' => $user->getId(),
            'status'  => 1,
            'timezone' => 'UTC',
        ));
        $account->setPolicy('backend', 'local');
        $account->setPolicy('username', $username);
        $account->setPassword($password);
        
        if ($account->save()) {
            echo "Successfully seeded dev user: $username / $email\n";
        }
    }
} else {
    echo "Dev user already exists\n";
}
PHPCODE
            php /tmp/dev-seed.php || echo "Dev seeding script failed"
        fi

        # Lock down config file and ownership post-install
        chmod 0644 "$CONFIG_FILE"
        chown -R www-data:www-data /var/www/html
    else
        echo "Installation timed out or failed. Check /tmp/install_response.txt"
    fi
    
    # Apache is already running in the background, we must wait on it
    wait $APACHE_PID
else
    echo "osTicket already installed."
    chown -R www-data:www-data /var/www/html
    echo "Starting Apache..."
    exec apache2-foreground
fi