Project Onus
========

Centralised Composer dependency management for WordPress projects.

This allows you to have one `vendor` directory for your entire project. No more clashing libraries between different plugins
that use Composer internally.

It works by going through every dependency of each plugin that has registered its use
of Composer with Onus using `WP_Onus::register()`, finds a working configuration of library versions, and installs it centrally to avoid having multiple versions of the same package installed.
Onus can also alert you of incompatible versions, ie. when one plugin requires Guzzle 1.0 and another plugin requires Guzzle 2.0.

This is a incomplete proof of concept. More info coming soon. Help welcome.

### Setup

Clone this plugin:

```
git clone https://github.com/khromov/wp-onus
```

Install dependencies:

```
cd wp-onus/
composer install
```

Clone the two example plugins that demonstrate how dependency management works across plugins:

```
git clone https://github.com/khromov/wp-onus-test-plugin-1
git clone https://github.com/khromov/wp-onus-test-plugin-2
```

Example of how the integration works in external plugins:

https://github.com/khromov/wp-onus-test-plugin-1/blob/master/wp-onus-test-plugin-1.php#L14


The steps for plugins are:
* Make sure to include at least a `composer.json` in the plugin root.
* Don't load `autoload.php` if this plugin is active.
* Register your root folder with this plugin so we can check for its `composer.json`.


Activate all three plugins.

### Resolve dependencies and build

Add `?rebuild_deps=1` to your site URL to trigger a rebuild. The new 
resolved dependency autoloader will be in `/wp-onus/resolved/vendor/autoload.php`.

This will be configurable in a future version. 