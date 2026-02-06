dockerized-osticket
====================

**Contents**
- **Purpose:** Run osTicket inside containers using the provided `Dockerfile` and `docker-compose.yml`.
- **Stack:** PHP + Apache (containerized), MySQL/MariaDB (compose).

**Quick Start**

### Docker Installation

- **Prerequisites:** Docker and Docker Compose (v2) installed.
- **Build & run:**

```bash
docker compose up -d --build
```

- **Follow logs:**

```bash
docker compose logs -f
```

- **Stop + remove containers:**

```bash
docker compose down
```

### Manual Installation (Non-Docker)

For traditional LAMP/LEMP server deployments:

1. **Make the script executable:**

```bash
chmod +x manual-install.sh
```

2. **Run the installation script as root:**

```bash
sudo ./manual-install.sh
```

The script will:
- Backup your existing osTicket files (config, plugins, languages, attachments)
- Remove old installation files from `/var/www/html` (or your specified web root)
- Copy new osTicket files from `osTicket/upload/`
- Restore your backed-up configuration and data
- Set proper file permissions
- Clear cache

**Manual Installation Requirements:**
- PHP 7.4 or higher
- MySQL 5.5+ or MariaDB 10.0+
- Apache or Nginx web server
- Existing osTicket installation (if upgrading)

**Manual Steps (if not using script):**

```bash
# 1. Backup existing files
mkdir -p ~/osticket-backup
cp /var/www/html/include/ost-config.php ~/osticket-backup/
cp -r /var/www/html/include/plugins ~/osticket-backup/
cp -r /var/www/html/include/i18n ~/osticket-backup/
cp -r /var/www/html/attachments ~/osticket-backup/

# 2. Remove old files
cd /var/www/html
sudo rm -rf *

# 3. Copy new files
cd /path/to/dockerized-osticket
sudo cp -r osTicket/upload/* /var/www/html/

# 4. Restore backed-up files
sudo cp ~/osticket-backup/ost-config.php /var/www/html/include/
sudo cp -r ~/osticket-backup/plugins/* /var/www/html/include/plugins/
sudo cp -r ~/osticket-backup/i18n/* /var/www/html/include/i18n/
sudo cp -r ~/osticket-backup/attachments/* /var/www/html/attachments/

# 5. Set permissions (Debian/Ubuntu)
sudo chown -R www-data:www-data /var/www/html
sudo chmod -R 755 /var/www/html
sudo chmod -R 777 /var/www/html/attachments

# For CentOS/RHEL use: sudo chown -R apache:apache /var/www/html
```

**Project Structure (selected)**

- `docker-compose.yml`: Compose file to build and orchestrate containers.
- `Dockerfile`: Image build instructions for the PHP/Apache app container.
- `php.ini`: PHP configuration used by the container.
- `osTicket/`: Application source tree (official osTicket structure).
	- `osTicket/upload/`: Public webroot and PHP front-end files (index.php, login.php, tickets.php, etc.).
- `plugins/`: Optional plugins bundled with this repo (example: `recaptchav2/`).
- `languages/`

**Configuration & Customization**

- To change PHP settings, edit `php.ini` before building the image.

**Development Notes**

- For code changes in `osTicket/`, rebuild the container so the image picks up the changes (unless you mount the code at runtime):

```bash
docker compose up -d --build
```

- If using bind mounts for development, ensure file permissions are correct for the Apache/PHP user inside the container (commonly `www-data`).

**Troubleshooting**

- If the site returns permission errors, run a chown/chmod on the host for upload/storage folders so the webserver can write to them.
- If containers don't start, check `docker compose logs` for errors (DB connection, PHP extensions missing, etc.).
- Common fix: confirm Docker has enough resources (memory/disk) and required ports are free.

**Testing & Maintenance**

- Backup the `include/` and `upload/` directories and any DB dumps before upgrading osTicket or plugins.
- Apply security updates to PHP/OS images by rebuilding the image from a newer base when necessary.

**Contributing**

- Fork, create a feature branch, and open a pull request with a clear description of changes.

**License**

- This repository includes osTicket code and is subject to the upstream license. See the top-level `LICENSE` file for repository licensing.

**Contact / Notes**

- For local issues, inspect container logs and webserver error logs under the container. If you want, I can add a troubleshooting container or a small helper script to simplify DB backups and restores.

