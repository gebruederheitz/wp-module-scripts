<?php

/**
 * Minimal PHPUnit bootstrap.
 *
 * Deliberately does NOT load the full WordPress install under .wp/ — it only
 * stubs the handful of WordPress functions that ModuleScriptHandler actually
 * calls, so the test suite stays fast and independent of a WP checkout.
 */

require_once __DIR__ . '/../vendor/autoload.php';

if (!function_exists('add_filter')) {
    function add_filter($hook, $callback, $priority = 10, $acceptedArgs = 1)
    {
        // No-op: tests call the handler's methods directly instead of
        // dispatching through a real WordPress hook system.
        return true;
    }
}

if (!function_exists('add_action')) {
    function add_action($hook, $callback, $priority = 10, $acceptedArgs = 1)
    {
        return true;
    }
}

if (!function_exists('esc_attr')) {
    function esc_attr($text)
    {
        return htmlspecialchars((string) $text, ENT_QUOTES);
    }
}

if (!function_exists('get_theme_file_uri')) {
    function get_theme_file_uri($path = '')
    {
        return 'https://example.test/wp-content/themes/test-theme/' . ltrim(
            (string) $path,
            '/',
        );
    }
}

/**
 * Registers/records a script the same way WP_Scripts would, storing it on
 * the global $wp_scripts stub so wp_script_add_data() and
 * ModuleScriptHandler::addModuleNomodule() can find it via
 * $wp_scripts->registered[$handle]->extra.
 */
function __gh_test_stub_register_script(
    $handle,
    $src,
    $deps = [],
    $ver = false,
    $args = []
) {
    global $wp_scripts;

    if (!isset($wp_scripts) || !is_object($wp_scripts)) {
        $wp_scripts = new stdClass();
        $wp_scripts->registered = [];
        $wp_scripts->enqueued = [];
    }

    $script = new stdClass();
    $script->handle = $handle;
    $script->src = $src;
    $script->deps = $deps;
    $script->ver = $ver;
    $script->args = $args;
    $script->extra = [];

    $wp_scripts->registered[$handle] = $script;

    return true;
}

if (!function_exists('wp_register_script')) {
    function wp_register_script(
        $handle,
        $src,
        $deps = [],
        $ver = false,
        $args = []
    ) {
        return __gh_test_stub_register_script($handle, $src, $deps, $ver, $args);
    }
}

if (!function_exists('wp_enqueue_script')) {
    function wp_enqueue_script(
        $handle,
        $src = '',
        $deps = [],
        $ver = false,
        $args = []
    ) {
        global $wp_scripts;

        $result = __gh_test_stub_register_script(
            $handle,
            $src,
            $deps,
            $ver,
            $args,
        );
        $wp_scripts->enqueued[$handle] = true;

        return $result;
    }
}

if (!function_exists('wp_script_add_data')) {
    function wp_script_add_data($handle, $key, $value)
    {
        global $wp_scripts;

        if (
            isset($wp_scripts) &&
            isset($wp_scripts->registered[$handle])
        ) {
            $wp_scripts->registered[$handle]->extra[$key] = $value;

            return true;
        }

        return false;
    }
}
