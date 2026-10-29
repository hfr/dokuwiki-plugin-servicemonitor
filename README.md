# DokuWiki Service Monitor

A DokuWiki plugin that displays the status of configured services in a table and refreshes their status asynchronously using AJAX.

## Features

- Displays service names and their current status.
- Updates service statuses without reloading the page.
- Supports configurable automatic refresh intervals.
- Allows the monitored services to be configured in DokuWiki's plugin settings.
- Uses a server-side status-checking script to retrieve service information.

## Requirements

- A working [DokuWiki](https://www.dokuwiki.org/dokuwiki) installation.
- PHP support compatible with your DokuWiki version.
- A server-side service-status script accessible to the web server.
- Appropriate permissions for the web server to execute the status-checking script.

## Installation

### Manual installation

1. Navigate to your DokuWiki plugin directory:

   ```bash
   cd /path/to/dokuwiki/lib/plugins
   ```

2. Clone the repository into the plugin directory:

   ```bash
   git clone https://github.com/YOUR-USERNAME/dokuwiki-plugin-servicemonitor.git service-monitor
   ```

   Replace `YOUR-USERNAME` with the GitHub account or organization that owns the repository.

3. Verify that the plugin directory contains the expected files:

   ```text
   service-monitor/
   ├── syntax.php
   ├── script.js
   ├── style.css
   ├── conf/
   │   ├── default.php
   │   └── metadata.php
   └── ajax/
       └── status.php
   ```

4. Configure the services and status-checking script as described below.

5. Open a DokuWiki page containing the plugin syntax to verify that the status table is displayed.

## Configuration

Configure the services in DokuWiki's configuration manager under **Admin → Configuration Settings → Plugins → service-monitor**.

The exact configuration options depend on the plugin version.

The status-checking script is expected to be available on the server, for example:

```text
/usr/local/sbin/service-status
```

Make sure that:

- The script exists and is executable.
- The web server has permission to execute it.
- The script returns status information in the format expected by the plugin.
- Service checks work when executed as the web server user, not only as root or your personal account.

**Security note:** Do not grant the web server unnecessary privileges. The status-checking script should accept only expected service names and should not allow arbitrary command execution.

## Usage

Insert the following syntax into a DokuWiki page to display the service status table:

### Default refresh interval

```text
~~service-monitor~~
```

### Custom refresh interval

Specify the refresh interval in seconds using the `intervall` parameter:

```text
~~service-monitor(intervall=10)~~
```

For example, to refresh every 30 seconds:

```text
~~service-monitor(intervall=30)~~
```

The default refresh interval is 10 seconds.

The minimum supported interval is 0.5 seconds. Very short intervals can increase server load, particularly when multiple users have the page open simultaneously.

## How It Works

1. DokuWiki processes the plugin syntax and renders the service status table.
2. JavaScript requests updated status information through DokuWiki's AJAX dispatcher.
3. The server-side AJAX handler retrieves the service statuses.
4. The response is returned as JSON.
5. JavaScript updates the table without requiring a full page reload.

The AJAX request uses the following dispatcher pattern:

```text
lib/exe/ajax.php?call=plugin_servicemonitor_status
```

The server-side handler is located at:

```text
lib/plugins/service-monitor/ajax/status.php
```

The plugin relies on DokuWiki's AJAX infrastructure to dispatch the request.

## Updating the Plugin

If you installed the plugin by cloning the Git repository, update it from the plugin directory:

```bash
cd /path/to/dokuwiki/lib/plugins/service-monitor
git pull
```

If your default branch is `master`, update it explicitly:

```bash
git pull origin master
```

Before updating a production installation, consider backing up the current plugin directory and reviewing the incoming changes.

## Troubleshooting

### The status table does not appear

- Verify that the plugin is installed in `lib/plugins/service-monitor/`.
- Check that the plugin's `syntax.php` file is present.
- Confirm that the page contains valid plugin syntax.
- Check the DokuWiki and PHP logs for errors.

### Service statuses are not updated

- Open your browser's developer tools and inspect the Network tab.
- Check whether requests to `lib/exe/ajax.php?call=plugin_servicemonitor_status` succeed.
- Verify that the AJAX response contains the expected JSON data.
- Check the browser console for JavaScript errors.
- Confirm that the JavaScript file is loaded
