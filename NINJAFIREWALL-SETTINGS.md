# Recommended NinjaFirewall settings

This is a portable, manually applied profile for [NinjaFirewall (WP Edition)](https://wordpress.org/plugins/ninjafirewall/). It was derived from a private NinjaFirewall 4.9.2 configuration export and checked against that version's setting labels. Apply it in the NinjaFirewall UI to the specific WordPress site, review compatibility, save each screen, and read the settings back. An AI agent may do these steps after the site owner authorizes work on that site.

The private export is not distributed. It contains an alert address, site paths, a blocked-page payload, and a large security-rule snapshot. Do not import it into another site. Use the current NinjaFirewall rules supplied by the plugin and keep rule updates enabled. The export alone does not establish which WAF operating mode is active or whether a File Check snapshot exists.

## Firewall and request inspection

Use **NinjaFirewall → Firewall Options** for status, response code, and IP anonymization, and **NinjaFirewall → Firewall Policies** for request inspection.

| NinjaFirewall control | Recommended setting |
| --- | --- |
| Firewall status | Enabled; verify the dashboard shows an operational WAF. |
| Enable NinjaFirewall for | HTTP and HTTPS traffic. |
| HTTP error code to return | 403 Forbidden. |
| IP anonymization | Yes; review the plugin's exceptions for private IPs and login protection. |
| File Uploads | Allow uploads. |
| Sanitise filenames | Off; test any separate upload policy used by the site. |
| Scan GET, POST, cookies, and HTTP_USER_AGENT | On. |
| Decode Base64-encoded POST variable | On. |
| Sanitise GET and cookies | Off. |
| Sanitise HTTP_USER_AGENT | On. |
| Scan HTTP_REFERER | Off. |
| Sanitise HTTP_REFERER | On. |
| Block POST requests without HTTP_REFERER | Off, to preserve legitimate callbacks and cron requests. |
| Block attempts to override PHP Superglobals | On. |
| Hide PHP notice and error messages | On. |
| Sanitise PHP_SELF, PATH_TRANSLATED, and PATH_INFO | On. |
| Users Whitelist | Administrator only; review this choice for the target site's role model. |

Keep the plugin's maintained detection rules current. Review site-specific WAF rule exceptions and scan scope in the target installation instead of copying them from an export.

## WordPress policies

Use **NinjaFirewall → Firewall Policies → Basic Policies**. Check each setting against the site's registration, publishing, API, and update workflows.

| NinjaFirewall control | Recommended setting |
| --- | --- |
| Block attempts to modify important WordPress settings | On. |
| Block user account creation and deletion | On, after checking registration and commerce workflows. |
| Block attempts to gain administrative privileges | On; multisite-wide extension stays off unless separately needed. |
| Block unauthorized publish, edit, or deletion of published posts | On. |
| Protect admin-ajax.php against bots | Off in this profile; assess separately if the site needs it. |
| Username enumeration through author archives, author sitemap, login, and feed | On. |
| Username enumeration through the REST API | Off; the MU plugin handles anonymous Core REST user routes. |
| Block all WordPress REST API access | Off; authenticated and site integration routes remain available. |
| Block all WordPress XML-RPC API access | On, after checking integrations that use XML-RPC. |
| Disable Application Passwords | Off; preserve integrations that use them. |
| Block POST requests in the themes folder | On if the target site does not require direct theme PHP endpoints. |
| Disable the plugin and theme editor | On. |
| Disable plugin and theme installation or updates | Off; security updates must remain possible. |
| Force HTTPS for admin and logins | Off in NinjaFirewall; verify HTTPS redirects through the actual server or hosting configuration. |
| Disable the WordPress fatal error handler | Off. |

The MU plugin's anonymous numeric author check and REST user-route check complement these settings. A NinjaFirewall-wide XML-RPC block makes its narrower XML-RPC switches unnecessary; keep those narrower switches off while the full block is active.

## HTTP response headers: leave NinjaFirewall controls off

In **Firewall Policies → Advanced Policies → HTTP response headers**, leave NinjaFirewall's X-Content-Type-Options, X-Frame-Options, X-XSS-Protection, HSTS, frontend and backend Content-Security-Policy, Referrer-Policy, custom response headers, and cookie HttpOnly/SameSite controls disabled. The private export has no active NinjaFirewall response-header policy; its encoded X-XSS-Protection position uses the plugin's “No” value.

The root block in [MANUAL-HARDENING.txt](MANUAL-HARDENING.txt) owns X-Content-Type-Options, X-Frame-Options, Referrer-Policy, Content-Security-Policy, HSTS, Permissions-Policy, and X-Permitted-Cross-Domain-Policies. This avoids setting the same security headers in two places. The documented .htaccess blocks do **not** set cookie HttpOnly or SameSite flags; assess those separately. HSTS also does not replace an HTTPS redirect.

## Notifications, monitoring, and updates

Use **NinjaFirewall → Event Notifications**, **Monitoring → File Guard / File Check**, and **Security Rules → Rules Updates**. Menu names may change in later plugin versions; match the control label and meaning before saving.

| NinjaFirewall control | Recommended setting |
| --- | --- |
| Alert on administrator login | On. |
| Alert on plugin upload, installation, and deactivation | On. |
| Alert on theme upload and installation | On. |
| Alert on WordPress Core update and administrator account changes | On. |
| Alert on important security updates | On. |
| Daily activity report and writing events to the firewall log | On. |
| PHP backtrace in important notifications | Medium verbosity. |
| Alert recipient | Set to the target site's authorized security contact; do not copy an address from an export. |
| File Guard | On; alert when a PHP script accessed by a request changed within 24 hours. |
| File Check scheduled scan | No schedule in this profile; inspect any existing snapshot and configure separately if required. |
| Automatic security-rule updates | On; check hourly and notify after updates. |
| Centralized Logging | Off unless the target has an approved destination and key. |

Alerts for plugin activation, update, and deletion and for theme activation, update, and deletion are off in this profile. Review whether the target needs those additional alerts rather than importing another site's recipients or exceptions.

## Agent readback

After saving, reopen each relevant NinjaFirewall screen and confirm the values. Check the WAF status, next rule-update time, File Guard status, and notification destination. Then verify the site's actual HTTPS response headers and the WordPress flows in [INSTALLATION.md](INSTALLATION.md). Do not mark the profile complete from a successful save response alone.
