#!/bin/bash

###########################################
# osTicket Manual Installation Script
# Automates deployment to non-Docker environments
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

echo -e "${GREEN}======================================${NC}"
echo -e "${GREEN}osTicket Manual Installation Script${NC}"
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
echo ""
echo -e "${RED}WARNING: This will delete all files in $WEB_ROOT${NC}"
echo -e "${YELLOW}Do you want to continue? (yes/no):${NC}"
read -r confirm

if [ "$confirm" != "yes" ]; then
    echo "Installation cancelled."
    exit 0
fi

echo ""
echo -e "${GREEN}Starting installation...${NC}"
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
    echo "  ⚠ No ost-config.php found (fresh installation?)"
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
echo -e "${YELLOW}[3/7] Removing old installation files...${NC}"
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

# Step 6: Set permissions
echo -e "${YELLOW}[6/7] Setting file permissions...${NC}"
chown -R "$WEB_USER:$WEB_GROUP" "$WEB_ROOT"

# Set base permissions for the whole site
chmod -R 755 "$WEB_ROOT"
chmod -R 777 "$WEB_ROOT/attachments"

if [ -f "$WEB_ROOT/include/ost-config.php" ]; then
    chmod 644 "$WEB_ROOT/include/ost-config.php"
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

# Step 7: Clear cache
echo -e "${YELLOW}[7/7] Clearing cache...${NC}"
if [ -d "$WEB_ROOT/include/ost-cache" ]; then
    rm -rf "$WEB_ROOT/include/ost-cache/"*
    echo "  ✓ Cache cleared"
else
    echo "  ⚠ No cache directory found"
fi

# Final summary
echo ""
echo -e "${GREEN}======================================${NC}"
echo -e "${GREEN}Installation Complete!${NC}"
echo -e "${GREEN}======================================${NC}"
echo ""
echo "Backup location: $BACKUP_DIR"
echo "Web root: $WEB_ROOT"
echo ""
echo -e "${YELLOW}New Features Installed:${NC}"
echo "  • Notification toaster system"
echo "  • Empty message validation"
echo "  • Enhanced user experience"
echo ""
echo -e "${YELLOW}Next Steps:${NC}"
echo "  1. Visit your osTicket URL in a browser"
echo "  2. Log in to the admin panel"
echo "  3. Verify all functionality works correctly"
echo ""
echo -e "${GREEN}Installation successful!${NC}"