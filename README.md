# We.Rock.WP.Safety.Lite

We.Rock.WP.Safety.Lite is a lightweight WordPress must-use (MU) plugin for practical WordPress security hardening. It reduces information exposed by login errors, anonymous author URLs, and WordPress REST API user routes; removes XML-RPC pingback methods and the `X-Pingback` header; and adds administrator-only Site Health checks, including checks for NinjaFirewall and WP 2FA. The plugin uses WordPress Core hooks and has no runtime Composer dependencies.

## Required security stack and responsibility

**Use this project only as a coordinated stack:** install the MU plugin, apply and verify the hardening blocks in **both** the WordPress root `.htaccess` and `wp-content/uploads/.htaccess`, and install and correctly configure NinjaFirewall (WP Edition). The installation described here is complete only when all three layers are working together on the target site. Follow [INSTALLATION.md](INSTALLATION.md), [MANUAL-HARDENING.txt](MANUAL-HARDENING.txt), and [NINJAFIREWALL-SETTINGS.md](NINJAFIREWALL-SETTINGS.md).

**Use this stack at your own risk.** The person or AI agent applying it must understand the target WordPress installation, Apache/LiteSpeed `.htaccess` behavior, NinjaFirewall policies, and the site's integrations. Make a restorable backup, test on staging, review every rule for the target, and verify the live behavior after installation. The site owner remains responsible for deciding whether the settings fit their site.

## Preferred installation: use an AI agent

Ask an AI agent with authorized access to the target WordPress installation to follow [INSTALLATION.md](INSTALLATION.md). The agent can inspect the existing site, install the MU plugin, merge both `.htaccess` blocks, configure NinjaFirewall from the [sanitized settings guide](NINJAFIREWALL-SETTINGS.md), install and configure WP 2FA, and verify each layer. This is the preferred installation path because it preserves site-specific rules and checks the result on the actual server.

## Manual installation of the MU component

1. Copy `wp-content/mu-plugins/we-rock-wp-safety-lite.php` and the `wp-content/mu-plugins/we-rock-wp-safety-lite/` directory into the matching paths of a WordPress 6.5+ installation running PHP 7.4+.
2. Confirm that the loader PHP file is directly inside `wp-content/mu-plugins/`. WordPress loads MU plugins automatically.
3. Open **Tools → Site Health** in WordPress and review the We.Rock checks.
4. Test the homepage, login, administrator area, media, and REST API using the [smoke-test checklist](wp-content/mu-plugins/we-rock-wp-safety-lite/deploy/SMOKE-TEST-CHECKLIST.md).

## Use the hardening features

The default settings provide generic Core login errors, return HTTP 404 for anonymous numeric author probes and Core REST user enumeration, and disable pingback methods. The plugin also reports security-related site settings in Site Health. XML-RPC authentication and Application Passwords stay available by default so existing integrations can continue to work.

Define feature constants in `wp-config.php` before WordPress loads if you need to change a default. See the [configuration reference](wp-content/mu-plugins/we-rock-wp-safety-lite/README.md#konfiguration) for the available constants and the global kill switch.

## Apply the required server settings manually

This project is designed to be used together with server-level settings in **both** the WordPress root `.htaccess` file and `wp-content/uploads/.htaccess`. The MU plugin does not write either file. Follow [MANUAL-HARDENING.txt](MANUAL-HARDENING.txt) to review and manually apply the extracted We.Rock hardening blocks. Back up the existing files, adapt the rules to the web server and site, test on staging, and check the site immediately after each change. The rules include a long-lived HSTS header and a Content Security Policy baseline, so review their effect before applying them to a live site.

For a maintained web application firewall, we recommend [NinjaFirewall (WP Edition) by NinTechNet](https://wordpress.org/plugins/ninjafirewall/). See the [NinTechNet website](https://nintechnet.com/ninjafirewall/wp-edition/) for the vendor's product information. NinjaFirewall is a separate WordPress plugin that you install and configure independently. The [sanitized settings guide](NINJAFIREWALL-SETTINGS.md) records the recommended configuration without publishing a site-specific export.

For WordPress account protection, we also recommend [WP 2FA by Melapress](https://wordpress.org/plugins/wp-2fa/). See the [Melapress product page](https://melapress.com/wordpress-2fa/) and [setup guide](https://melapress.com/support/kb/wp-2fa-plugin-getting-started/) for installation and configuration. Enable two-factor authentication for administrator and other privileged accounts according to the site's role model, have each user complete their own enrollment and store their backup codes securely, then verify login and recovery. WP 2FA is an additional recommended layer alongside the required MU plugin, `.htaccess`, and NinjaFirewall stack.

## License

MIT. See [LICENSE](LICENSE).
