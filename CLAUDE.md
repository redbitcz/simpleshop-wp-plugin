# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Overview

SimpleShop is a WordPress plugin (`redbit/simpleshop-wp-plugin`, WP slug `simpleshop-cz`) that connects a WordPress/MioWeb site to a SimpleShop / Vyfakturuj sales system. Its two jobs are:

1. **Member sections** — restrict access to pages/posts and to individual pieces of content (Gutenberg blocks / shortcodes) based on group membership, subscription dates, and time-based release rules.
2. **Sales forms** — embed a SimpleShop-hosted selling form via a shortcode or a Gutenberg block.

Requires PHP >= 7.4 and WordPress >= 6.6. UI strings and many code comments are in Czech.

## Commands

```bash
composer install              # PHP deps + generates classmap autoloader (src/ is classmapped, not PSR-4)
npm install                   # JS deps (@wordpress/scripts based)
npm run build                 # Build Gutenberg block: js/gutenberg/ss-gutenberg.js -> build/ss-gutenberg.js
npm run start                 # Same build in watch mode

php deploy.php v1.2.3         # Full release build -> dist/simpleshop-cz-v1.2.3.zip
                              # Runs composer install + npm build, zips the plugin (excludes per deploy-exclude.lst),
                              # and stamps the version into simpleshop-cz.php and readme.txt.
                              # Version MUST match /^v\d+\.\d+\.\d+(-suffix)?$/ (e.g. v1.2.3 or v1.2.3-beta).
```

There is **no test suite, linter, or CI config** in this repo. `readme.txt` is the WordPress.org readme (not `README.md`); the changelog lives in GitHub Releases.

### Building a release ZIP

`deploy.php` is the canonical way to produce a distributable. Note that in the checked-in source, the version is the literal string `dev-master` (in `simpleshop-cz.php` header, the `SIMPLESHOP_PLUGIN_VERSION` constant, and `readme.txt`'s `Stable tag: trunk`) — `deploy.php` rewrites these into the copy inside the ZIP. Do not hardcode a real version number into the tracked files.

## Architecture

### Bootstrap and object graph

`simpleshop-cz.php` (plugin entry) requires the Composer autoloader, defines `SIMPLESHOP_PLUGIN_VERSION` and `SIMPLESHOP_PREFIX` (`_ssc_`), then calls `SimpleShop::getInstance()`.

- `SimpleShop` (src/SimpleShop.php) is a static singleton holder returning the one `Plugin` instance.
- `Plugin` (src/Plugin.php) is the **composition root**. Its `init()` constructs every subsystem once: `Settings`, `Access`, `Admin`, `Group`, `Rest`, `Cron`, `Metaboxes`, `Shortcodes`, and (if Gutenberg is available) `Gutenberg`. Each subsystem registers its own WordPress hooks in its constructor. `Plugin` also owns the API credentials and is the factory for the API client.
- `Loader` (src/Loader.php) is a deprecated back-compat shim; new code should always go through `SimpleShop::getInstance()`.

Everything lives in the `Redbit\SimpleShop\WpPlugin` namespace. `src/` is loaded via Composer **classmap** (see `composer.json` `autoload.classmap`), so filename == classname but there is no strict PSR-4 directory rule.

### Access-control model (the core domain)

The restriction logic is spread across a few cooperating classes; understanding their split matters:

- **`Group`** (src/Group.php) — Groups are a custom post type `ssc_group`. A user's group IDs are stored in user meta `_ssc_user_groups`. Handles membership add/list/check.
- **`Membership`** (src/Membership.php) — Per-user, per-group dates. Reads/writes user meta `_ssc_group_subscription_date_{group_id}` (subscription start) and `_ssc_group_subscription_valid_to_{group_id}` (expiry; the sentinel `1970-01-01` means "no expiry"). `is_valid_for_group()` is the authority on whether a membership is currently active.
- **`Access`** (src/Access.php) — The policy engine. Two distinct entry points:
  - `user_can_view_post()` — page/post-level gate. Combines post meta (`_ssc_groups`, plus `_ssc_`-prefixed `date_to_access`, `date_until_to_access`, `days_to_access`, `expire_days_after_registration`) with the user's group memberships. Wired to `template_redirect` to redirect unauthorized visitors.
  - `user_can_view_content($args)` — content-level gate for a single block/shortcode, driven by explicit args (`group_ids`, `is_member`, `is_logged_in`, `specific_date_from/to`, `days_to_view`).
  - Admins/editors bypass access (`user_is_admin()`, filterable via `ssc_user_is_admin`). Also handles login redirects, hiding protected nav-menu items, hiding protected posts from RSS, and sending the "welcome" email with generated password + page links.

Post/block meta keys use the `_ssc_` prefix (the `SIMPLESHOP_PREFIX` constant). When adding new restriction rules, both `Access` entry points and the metabox UI generally need matching changes.

### Content surfaces

- **`Shortcodes`** (src/Shortcodes.php) — Registers `[SimpleShop-form]` (embeds the remote SimpleShop form JS) and `[SimpleShop-content]` (gates inner content via `Access::user_can_view_content`).
- **`Gutenberg`** (src/Gutenberg.php) — Registers the `simpleshop/simpleshop-form` block (rendered server-side by delegating to `Shortcodes::simple_shop_form`) and, via the `render_block` filter, hides any block whose `simpleShop*` attributes fail `Access::user_can_view_content`. Note the back-compat mapping from the old single `simpleShopGroup` attr to the newer `simpleShopGroups` array. The block's JS source is `js/gutenberg/ss-gutenberg.js`; the built artifact `build/ss-gutenberg.js` is git-ignored and produced by `npm run build`.
- **`Metaboxes`** (src/Metaboxes.php) — CMB2-based admin metaboxes for setting the per-post restriction meta. CMB2 comes from Composer (`cmb2/cmb2`, plus the post-search-field add-on) and is force-loaded via `composer.json` `autoload.files`.
- **`Admin`** / **`Settings`** (src/Admin.php, src/Settings.php) — Plugin admin pages and options. Options use the `ssc_` prefix; read them with `Settings::ssc_get_option()`. Credentials are `ssc_api_email` / `ssc_api_key`.

### External integration

- **`Rest`** (src/Rest.php) — REST controller under namespace `simpleshop/v1`. `POST /add-member` is how SimpleShop's backend provisions members: it creates the WP user if needed, adds them to the requested groups with valid-from/valid-to dates, schedules or sends the welcome email, and works on multisite. **Auth is a shared-secret hash**: `create_item_permissions_check()` compares `sha1(api_key)` against the request's `hash` param (`Plugin::validate_secure_key`). It also uses a MySQL named lock (`Plugin::lock()`/`releaseLock()`) to serialize member creation.
- **`Vyfakturuj\VyfakturujAPI`** (src/Vyfakturuj/VyfakturujAPI.php) — Bundled client for the SimpleShop/Vyfakturuj API. Get a configured instance via `Plugin::get_api_client()`; endpoint defaults to `https://api.simpleshop.cz/2.0/` unless overridden by the `ssc_api_endpoint_url` option.
- **`Cron`** (src/Cron.php) — Daily WP-cron event `ssc_send_user_has_access_to_post_notification` that emails users when time-delayed content becomes available (guarded by per-user/per-post "already sent" meta).

### MioWeb compatibility

Several code paths special-case MioWeb (a WordPress-based site builder) — e.g. `Access::mioweb_remove_login_redirect()` strips MioWeb's login redirect. Keep this in mind when touching login/redirect behavior.

## Conventions

- WordPress coding style: tabs for indentation, snake_case methods, Yoda-ish comparisons in places. Match the surrounding file.
- Loose comparisons (`==`/`!=`) are used deliberately in some access checks; be careful before "fixing" them.
- New user-facing strings use the `simpleshop-cz` text domain. The `tools/i18n/` scripts (`makepot.php`, `extract.php`, etc.) are the WordPress i18n toolkit for generating POT files.
