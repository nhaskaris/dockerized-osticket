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

**Contributing**

- Fork, create a feature branch, and open a pull request with a clear description of changes.

**License**

- This repository includes osTicket code and is subject to the upstream license. See the top-level `LICENSE` file for repository licensing.

