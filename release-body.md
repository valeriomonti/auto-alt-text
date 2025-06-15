## [v2.4.0] - 2025-06-15

### Changed
- Introduce two new plugin-specific constants, `AATXT_ENCRYPTION_KEY` and `AATXT_ENCRYPTION_SALT`, allowing API keys to be encrypted independently of WordPress’s own salts.
- Implement automatic migration of existing encrypted API keys: on upgrade, keys encrypted with the old WP salts are decrypted (if possible) and re-encrypted with the new plugin key.
- Add an optional info notice on the Auto Alt Text options page showing the ready-to-copy `define()` statements for `wp-config.php`.
- Maintain full backward-compatibility: if the new constants are not defined, the plugin continues to use the WordPress `LOGGED_IN_KEY`/`LOGGED_IN_SALT` fallback without interruption.  