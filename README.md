# We.Rock.WP.Safety.Lite

We.Rock.WP.Safety.Lite is a lightweight WordPress must-use (MU) plugin for practical WordPress security hardening. It reduces information exposed by login errors, anonymous author URLs, and WordPress REST API user routes; removes XML-RPC pingback methods and the `X-Pingback` header; and adds administrator-only Site Health checks. The plugin uses WordPress Core hooks and has no runtime Composer dependencies.

## Install the WordPress MU plugin

1. Copy `wp-content/mu-plugins/we-rock-wp-safety-lite.php` and the `wp-content/mu-plugins/we-rock-wp-safety-lite/` directory into the matching paths of a WordPress 6.5+ installation running PHP 7.4+.
2. Confirm that the loader PHP file is directly inside `wp-content/mu-plugins/`. WordPress loads MU plugins automatically.
3. Open **Tools → Site Health** in WordPress and review the We.Rock checks.
4. Test the homepage, login, administrator area, media, and REST API using the [smoke-test checklist](wp-content/mu-plugins/we-rock-wp-safety-lite/deploy/SMOKE-TEST-CHECKLIST.md).

## Use the hardening features

The default settings provide generic Core login errors, return HTTP 404 for anonymous numeric author probes and Core REST user enumeration, and disable pingback methods. The plugin also reports security-related site settings in Site Health. XML-RPC authentication and Application Passwords stay available by default so existing integrations can continue to work.

Define feature constants in `wp-config.php` before WordPress loads if you need to change a default. See the [configuration reference](wp-content/mu-plugins/we-rock-wp-safety-lite/README.md#konfiguration) for the available constants and the global kill switch.

## Apply the required server settings manually

This project is designed to be used together with server-level settings in **both** the WordPress root `.htaccess` file and `wp-content/uploads/.htaccess`. The MU plugin does not write either file. Follow [MANUAL-HARDENING.txt](MANUAL-HARDENING.txt) to review and manually apply the extracted We.Rock hardening blocks. Back up the existing files, adapt the rules to the web server and site, test on staging, and check the site immediately after each change. The rules include a long-lived HSTS header and a Content Security Policy baseline, so review their effect before applying them to a live site.

For a maintained web application firewall, we recommend [NinjaFirewall (WP Edition) by NinTechNet](https://wordpress.org/plugins/ninjafirewall/). See the [NinTechNet website](https://nintechnet.com/ninjafirewall/wp-edition/) for the vendor's product information. NinjaFirewall is a separate WordPress plugin that you install and configure independently.

## License

MIT. See [LICENSE](LICENSE).
