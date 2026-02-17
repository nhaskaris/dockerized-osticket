#!/bin/bash

###########################################
# osTicket Manual Update Script
# Automates updates to non-Docker environments
###########################################

set -e  # Exit on any error

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
NC='\033[0m' # No Color

# Default values
WEB_ROOT="/var/www/html"
BACKUP_DIR="$HOME/osticket-backup-$(date +%Y%m%d_%H%M%S)"
WEB_USER="www-data"
WEB_GROUP="www-data"

# Detect OS and set appropriate web user
if [ -f /etc/redhat-release ]; then
    WEB_USER="apache"
    WEB_GROUP="apache"
fi

# Function to apply PHP settings from php.ini file
apply_php_settings() {
    local php_config_file=$1
    local php_ini_source=$2
    
    if [ ! -f "$php_config_file" ]; then
        echo "  ⚠ PHP config file not found: $php_config_file"
        return 1
    fi
    
    if [ ! -f "$php_ini_source" ]; then
        echo "  ⚠ Source php.ini not found: $php_ini_source"
        return 1
    fi
    
    # Backup original php.ini
    cp "$php_config_file" "$php_config_file.backup-$(date +%s)"
    
    # Read settings from php.ini and apply to Apache PHP configuration
    while IFS= read -r line; do
        # Skip empty lines and comments
        [[ -z "$line" || "$line" =~ ^[[:space:]]*\# ]] && continue
        
        if [[ "$line" =~ ^([^=]+)=(.+)$ ]]; then
            setting_name="${BASH_REMATCH[1]}"
            setting_value="${BASH_REMATCH[2]}"
            
            # Escape special characters for sed
            escaped_setting_name=$(echo "$setting_name" | sed 's/[\/&]/\\&/g')
            escaped_setting_value=$(echo "$setting_value" | sed 's/[\/&]/\\&/g')
            
            # Check if setting already exists in php.ini
            if grep -q "^${setting_name}\s*=" "$php_config_file"; then
                # Update existing setting
                sed -i "s/^${escaped_setting_name}\s*=.*/${escaped_setting_name}=${escaped_setting_value}/" "$php_config_file"
                echo "  ✓ Updated: $setting_name = $setting_value"
            else
                # Add new setting if not commented out
                if ! grep -q "^;\s*${setting_name}\s*=" "$php_config_file"; then
                    echo "$setting_name=$setting_value" >> "$php_config_file"
                    echo "  ✓ Added: $setting_name = $setting_value"
                else
                    # Uncomment existing commented setting
                    sed -i "s/^;\s*${escaped_setting_name}\s*=.*;/${escaped_setting_name}=${escaped_setting_value}/" "$php_config_file"
                    echo "  ✓ Uncommented and updated: $setting_name = $setting_value"
                fi
            fi
        fi
    done < "$php_ini_source"
}

# Function to enable Apache rewrite module
enable_apache_rewrite() {
    if command -v a2enmod &> /dev/null; then
        if a2enmod rewrite 2>/dev/null; then
            echo "  ✓ Apache mod_rewrite enabled"
            return 0
        else
            echo "  ⚠ Failed to enable mod_rewrite"
            return 1
        fi
    else
        echo "  ⚠ a2enmod command not found (Apache not installed?)"
        return 1
    fi
}

echo -e "${GREEN}======================================${NC}"
echo -e "${GREEN}osTicket Manual Update Script${NC}"
echo -e "${GREEN}======================================${NC}"
echo ""

# Check if running as root
if [ "$EUID" -ne 0 ]; then 
    echo -e "${RED}Error: This script must be run as root or with sudo${NC}"
    exit 1
fi

# Get current script directory
SCRIPT_DIR="$( cd "$( dirname "${BASH_SOURCE[0]}" )" && pwd )"

# Check if osTicket/upload exists
if [ ! -d "$SCRIPT_DIR/osTicket/upload" ]; then
    echo -e "${RED}Error: osTicket/upload directory not found!${NC}"
    echo "Make sure you're running this script from the dockerized-osticket directory"
    exit 1
fi

# Prompt for web root
echo -e "${YELLOW}Enter web root directory [default: $WEB_ROOT]:${NC}"
read -r input_web_root
if [ -n "$input_web_root" ]; then
    WEB_ROOT="$input_web_root"
fi

# Check if web root exists
if [ ! -d "$WEB_ROOT" ]; then
    echo -e "${RED}Error: Web root directory $WEB_ROOT does not exist!${NC}"
    exit 1
fi

# Confirm before proceeding
echo ""
echo -e "${YELLOW}This script will:${NC}"
echo "  1. Backup existing osTicket files to: $BACKUP_DIR"
echo "  2. Remove all files from: $WEB_ROOT"
echo "  3. Copy new osTicket files from: $SCRIPT_DIR/osTicket/upload"
echo "  4. Restore configuration, plugins, and images"
echo "  5. Configure PHP settings for optimal performance"
echo "  6. Enable Apache mod_rewrite for API support"
echo "  7. Set file permissions"
echo ""
echo -e "${RED}WARNING: This will delete all files in $WEB_ROOT${NC}"
echo -e "${YELLOW}Do you want to continue? (yes/no):${NC}"
read -r confirm

if [ "$confirm" != "yes" ]; then
    echo "Update cancelled."
    exit 0
fi

echo ""
echo -e "${GREEN}Starting update...${NC}"
echo ""

# Step 1: Create backup directory
echo -e "${YELLOW}[1/7] Creating backup directory...${NC}"
mkdir -p "$BACKUP_DIR"
echo "Backup directory created: $BACKUP_DIR"

# Step 2: Backup existing files
echo -e "${YELLOW}[2/7] Backing up existing osTicket files...${NC}"

if [ -f "$WEB_ROOT/include/ost-config.php" ]; then
    cp "$WEB_ROOT/include/ost-config.php" "$BACKUP_DIR/"
    echo "  ✓ Backed up ost-config.php"
else
    echo "  ⚠ No ost-config.php found (not an update?)"
fi

if [ -d "$WEB_ROOT/include/plugins" ]; then
    cp -r "$WEB_ROOT/include/plugins" "$BACKUP_DIR/"
    echo "  ✓ Backed up plugins/"
else
    echo "  ⚠ No plugins directory found"
fi

if [ -d "$WEB_ROOT/include/i18n" ]; then
    cp -r "$WEB_ROOT/include/i18n" "$BACKUP_DIR/"
    echo "  ✓ Backed up i18n/"
else
    echo "  ⚠ No i18n directory found"
fi

if [ -d "$WEB_ROOT/attachments" ]; then
    cp -r "$WEB_ROOT/attachments" "$BACKUP_DIR/"
    echo "  ✓ Backed up attachments/"
else
    echo "  ⚠ No attachments directory found"
fi

# --- NEW: Backup Images ---
if [ -d "$WEB_ROOT/images" ]; then
    cp -r "$WEB_ROOT/images" "$BACKUP_DIR/"
    echo "  ✓ Backed up images/"
else
    echo "  ⚠ No images directory found"
fi
# ---------------------------

# Step 3: Remove old files
echo -e "${YELLOW}[3/7] Removing old files...${NC}"
rm -rf "${WEB_ROOT:?}"/*
echo "  ✓ Old files removed"

# Step 4: Copy new osTicket files
echo -e "${YELLOW}[4/7] Copying new osTicket files...${NC}"
cp -r "$SCRIPT_DIR/osTicket/upload/"* "$WEB_ROOT/"
echo "  ✓ New files copied"

# Step 5: Restore backed-up files
echo -e "${YELLOW}[5/7] Restoring configuration, plugins, and images...${NC}"

if [ -f "$BACKUP_DIR/ost-config.php" ]; then
    cp "$BACKUP_DIR/ost-config.php" "$WEB_ROOT/include/"
    echo "  ✓ Restored ost-config.php"
fi

if [ -d "$BACKUP_DIR/plugins" ]; then
    cp -r "$BACKUP_DIR/plugins/"* "$WEB_ROOT/include/plugins/"
    echo "  ✓ Restored plugins/"
fi

if [ -d "$BACKUP_DIR/i18n" ]; then
    cp -r "$BACKUP_DIR/i18n/"* "$WEB_ROOT/include/i18n/"
    echo "  ✓ Restored i18n/"
fi

if [ -d "$BACKUP_DIR/attachments" ]; then
    cp -r "$BACKUP_DIR/attachments/"* "$WEB_ROOT/attachments/"
    echo "  ✓ Restored attachments/"
fi

# --- NEW: Restore Images ---
if [ -d "$BACKUP_DIR/images" ]; then
    cp -r "$BACKUP_DIR/images/"* "$WEB_ROOT/images/"
    echo "  ✓ Restored images/"
fi
# ---------------------------

# Step 6: Configure PHP settings
echo -e "${YELLOW}[6/7] Configuring PHP settings...${NC}"
# Find Apache PHP config files
PHP_CONFIG_FOUND=0
for php_config in /etc/php/*/apache2/php.ini /etc/php.ini /usr/local/etc/php.ini; do
    if [ -f "$php_config" ]; then
        if apply_php_settings "$php_config" "$SCRIPT_DIR/php.ini"; then
            PHP_CONFIG_FOUND=1
        fi
    fi
done

if [ $PHP_CONFIG_FOUND -eq 0 ]; then
    echo "  ⚠ Could not find Apache PHP configuration file"
    echo "    Please manually configure PHP with these settings from $SCRIPT_DIR/php.ini"
fi

# Step 7: Enable Apache rewrite module
echo -e "${YELLOW}[7/7] Enabling Apache mod_rewrite...${NC}"
if enable_apache_rewrite; then
    echo "  ✓ Apache mod_rewrite module enabled for API support"
    # Restart Apache to apply changes
    if command -v systemctl &> /dev/null; then
        systemctl restart apache2 2>/dev/null || systemctl restart httpd 2>/dev/null || echo "  ⚠ Could not auto-restart Apache, please restart manually"
    elif command -v service &> /dev/null; then
        service apache2 restart 2>/dev/null || service httpd restart 2>/dev/null || echo "  ⚠ Could not auto-restart Apache, please restart manually"
    fi
else
    echo "  ⚠ Apache mod_rewrite may not be properly configured"
fi

# Set permissions
echo -e "${YELLOW}Setting file permissions...${NC}"
chown -R "$WEB_USER:$WEB_GROUP" "$WEB_ROOT"

# Set base permissions for the whole site
chmod -R 755 "$WEB_ROOT"

# Set attachments to writable if it exists
if [ -d "$WEB_ROOT/attachments" ]; then
    chmod -R 770 "$WEB_ROOT/attachments"
fi

if [ -f "$WEB_ROOT/include/ost-config.php" ]; then
    chmod 640 "$WEB_ROOT/include/ost-config.php"
fi

# --- NEW: Fix Image Permissions ---
if [ -d "$WEB_ROOT/images" ]; then
    # Directories must be 755 to be accessible
    find "$WEB_ROOT/images" -type d -exec chmod 755 {} \;
    # Files inside are set to 644
    find "$WEB_ROOT/images" -type f -exec chmod 644 {} \;
    echo "  ✓ Images permissions set (Dirs: 755, Files: 644)"
fi
# ----------------------------------

echo "  ✓ Permissions set (Owner: $WEB_USER:$WEB_GROUP)"

# Final summary
echo ""
echo -e "${GREEN}======================================${NC}"
echo -e "${GREEN}Update Complete!${NC}"
echo -e "${GREEN}======================================${NC}"
echo ""
echo "Backup location: $BACKUP_DIR"
echo "Web root: $WEB_ROOT"
echo ""
echo -e "${YELLOW}Next Steps:${NC}"
echo "  1. Visit your osTicket URL in a browser"
echo "  2. Log in to the admin panel"
echo "  3. Verify all functionality works correctly"
echo ""
echo -e "${GREEN}Update successful!${NC}"