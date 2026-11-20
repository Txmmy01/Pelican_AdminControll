# ADMIN Controll 1.0.0

ADMIN Controll is an administrator-only Pelican Panel plugin for managing multiple game servers from one dedicated control page.

## Highlights

- Dedicated administration page at `/admin/controll`.
- Select individual servers with checkboxes.
- Start, stop, and restart all selected servers.
- Combined live console for selected servers.
- Console output is labeled with the server name in the format `[SERVERNAME] (console output)`.
- Send one console command to all currently selected servers.
- Automatically includes newly created servers in the server list.
- Uses Pelican's server power actions and node WebSocket authentication for console access.
- Restricted to root administrators.

## Compatibility

- Pelican Panel: `1.0.0-rc.3`
- Plugin identifier: `admin-controll`
- Plugin version: `1.0.0`
- Author: `Txmmy01`

## Installation

1. Download `ADMIN-Controll.zip` from the repository.
2. Sign in to Pelican with a root administrator account.
3. Open **Admin → Plugins**.
4. Choose **Import** and upload the ZIP archive.
5. Install and enable the plugin.
6. Open **Admin → Controll**.

## Usage

1. Select the servers to manage.
2. Use **Start**, **Stop**, or **Restart** to run the corresponding action on the selected servers.
3. Review the shared live console. Each output line is prefixed with its server name.
4. Enter a console command and send it to the selected servers.

Only servers selected at the time of sending receive the console command.

## Build and validation

The repository includes a GitHub Actions workflow that checks PHP syntax and plugin metadata, builds the distributable ZIP, and commits the generated archive to the repository.

## Important testing note

The release package has passed the repository's automated PHP syntax, JSON metadata, and ZIP packaging checks. It has not been verified against a live Pelican installation or live Wings nodes. Test installation, server power actions, WebSocket connectivity, and console command delivery in a staging environment before using it on production servers.

## Package

Download: [ADMIN-Controll.zip](https://raw.githubusercontent.com/Txmmy01/Pelican_AdminControll/main/ADMIN-Controll.zip)

Source repository: https://github.com/Txmmy01/Pelican_AdminControll
