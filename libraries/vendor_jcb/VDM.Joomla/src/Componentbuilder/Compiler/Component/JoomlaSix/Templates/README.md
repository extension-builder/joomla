# Joomla 6 component template sources

`Component.Template` resolves the source copied by both component structure
builders. The service provider selects the compiler's requested target major,
independently of the Joomla version running JCB.

Joomla 3, 4, and 5 use the configured template directory unchanged. Joomla 6
uses an existing `<original filename>.tpl` file in this directory when compiling
from the built-in `compiler/joomla_4` directory. Missing overrides fall back to
the original shared template. Only ordinary file names participate; directories
and explicit custom/full static sources keep their existing resolution.

For example, `ADMIN_VIEW_MODEL.php.tpl` replaces the source of
`ADMIN_VIEW_MODEL.php` for Joomla 6. The settings key, destination filename,
placeholder expansion, and file registry remain unchanged. The `.tpl` suffix
identifies compiler input, not a PHP class loaded by the library autoloader.

## Custom compiler directories

A configured custom `compiler_path` remains authoritative. Joomla 6 does not
apply this overlay to a different template root or fill missing custom files
from this directory. To adopt a native template in a custom compiler tree,
copy the desired `.php.tpl` contents to `joomla_4/<original filename>.php` in
that tree, removing the final `.tpl` suffix from the filename. Keep the custom
settings and other templates alongside it as before. A custom directory used
for multiple Joomla targets should retain the templates appropriate to those
targets; use a separate configured compiler tree for Joomla 6 when its source
must differ from Joomla 4 or 5.
