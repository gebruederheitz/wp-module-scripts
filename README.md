# Wordpress Module Scripts

_Simple setup for module and nomodule script tags in Wordpress_

---

Most of the credit goes to [kylereicks](https://github.com/kylereicks), who
demonstrated in principle how a module/nomodule setup can be realized with
Wordpress' script loading mechanism in https://github.com/kylereicks/wp-script-module-nomodule.

I've wrapped it all in a reusable class, improved some of the regexes and logic,
and added the convenience method for registering / enqueueing the scripts with
the required data declared in the first place.

## Installation

```shell
composer require gebruederheitz/wp-module-scripts
```

## Usage

The simplest usage involves instantiating the handler (so it can register its
action hook callback) and adding the required extra data to a script:

```php
// functions.php or similar
use GebruederHeitz\Wordpress\ModuleScriptHandler;

// Initialize once so the action can be hooked
ModuleScriptHandler::getInstance();

// The script that has been registered (or enqueued) with the handle
// 'my-legacy-script' will now have the 'nomodule' attribute added to it.
wp_script_add_data('my-legacy-script', 'nomodule', true);
// This will be "type='module'":
wp_script_add_data('my-esm-script', 'type', 'module');

```

### Using the utility method for registering / enqueueing

The utility method `register()` will handle both the registration / enqueueing
of the scripts and the addition of the required attributes in one go.

> [!NOTE]
> All scripts are loaded asynchronously in the "footer". The script paths are
> passed through `get_theme_file_uri()`.


```php
// functions.php or similar
use GebruederHeitz\Wordpress\ModuleScriptHandler;

// Initialize once so the action can be hooked
ModuleScriptHandler::getInstance();

add_action('wp_enqueue_scripts', function() {
    // ModuleScriptHandler is a Singleton, so this will retrieve the instance
    // initialized above
    $handler = ModuleScriptHandler::getInstance();
    // Simple usage: register your hybrid build
    // Paths are passed through `get_theme_file_uri()`
    $handler->register('my-hybrid-script', 'path/to/module.js', 'path/to/nomodule.js');
    
    // Adds cache busting to the nomodule script (because version is supplied),
    // immediately enqueues both scripts, and returns the handles.
    [$moduleHandle, $nomoduleHandle] = $handler->register(
        'my-hybrid-script',
        modulePath: 'js/main.esm.v4.0.1.js',
        nomodulePath: 'js/main.legacy.js',
        dependencies: ['jquery'],
        enqueue: true,
        version: '4.0.1',
    );
});
```

```html
<script 
    type="module" 
    async
    src="$theme/path/to/module.js" 
    id="my-hybrid-script-module"
></script>
<script 
    nomodule 
    type="text/javascript" 
    async 
    src="$theme/path/to/nomodule.js" 
    id="my-hybrid-script-nomodule"
></script>

<script 
    type="module" 
    async
    src="$theme/js/main.esm.v4.0.1.js" 
    id="my-hybrid-script-module"
></script>
<script 
    nomodule 
    type="text/javascript" 
    async
    src="$theme/js/main.legacy.js?ver=4.0.1"
    id="my-hybrid-script-nomodule"
></script>
```

### Advanced Usage

```php
    [$moduleHandle, $nomoduleHandle] = $handler->register(
        'my-hybrid-script',
        modulePath: 'js/main.esm.v4.0.1.js',
        nomodulePath: 'js/main.legacy.js',
        version: '4.0.1',
    );
    
    wp_localize_script($moduleHandle, 'myData', ['foo' => 'bar']);
    if ($condition) {
        wp_enqueue_script($moduleHandle);
    }

    // You can also register/enqueue only a module script by passing only the
    // modulePath...
    [$moduleHandle, $nomoduleHandle] = $handler->register(
        'my-module-only-script',
        modulePath: 'js/main.esm.v4.0.1.js',
        version: '4.0.1',
        enqueue: true,
    );
    
    // ...or a "generic" script with no special attributes whatsoever.
    [$moduleHandle, $nomoduleHandle] = $handler->register(
        'my-generic-script',
        nomodulePath: 'js/main.js',
        version: '4.0.1',
        enqueue: true,
    );
```

```html
<script type="text/javascript" id="my-hybrid-scripts-module-js-extra">
    /* <![CDATA[ */
    var myData = {"foo":"bar"};
    /* ]]> */
</script>
<script 
    type="module" 
    async 
    src="$theme/js/main.esm.v4.0.1.js"
    id="my-hybrid-script-module"
></script>
<!-- nomodule script has not been enqueued -->

<script
    type="module"
    async
    src="$theme/js/main.esm.v4.0.1.js"
    id="my-module-only-script-module"
></script>

<script 
    type="text/javascript"
    async 
    src="$theme/js/main.js?ver=4.0.1"
    id="my-generic-script-nomodule"
></script>
```
