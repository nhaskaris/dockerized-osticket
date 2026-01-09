dockerized-osticket
====================

**Contents**
- **Purpose:** Run osTicket inside containers using the provided `Dockerfile` and `docker-compose.yml`.
- **Stack:** PHP + Apache (containerized), MySQL/MariaDB (compose).

**Quick Start**

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

--
Generated README for this workspace. Edit `README.md` to add project-specific deploy or environment notes.

