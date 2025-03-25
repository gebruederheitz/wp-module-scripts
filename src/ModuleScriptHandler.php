<?php

namespace Gebruederheitz\Wordpress;

use Gebruederheitz\SimpleSingleton\SingletonAble;

/**
 * Credits go to https://github.com/kylereicks, I've improved some of the regexes
 * and logic.
 * See https://github.com/kylereicks/wp-script-module-nomodule
 *
 */
class ModuleScriptHandler
{
    use SingletonAble;

    protected function __construct()
    {
        add_filter('script_loader_tag', [$this, 'onScriptLoaderTag'], 10, 2);
    }

    public function onScriptLoaderTag(string $tag, string $handle): string
    {
        return $this->addModuleNomodule($tag, $handle);
    }

    public function addModuleNomodule(string $tag, string $handle): string
    {
        global $wp_scripts;

        if (!empty($wp_scripts->registered[$handle]->extra['type'])) {
            $re = '/(^.*\stype=["\'])([^"\']*?)(["\'].*$)/';
            $type = $wp_scripts->registered[$handle]->extra['type'];

            if (preg_match($re, $tag, $match)) {
                $replacement = '$1' . esc_attr($type) . '$3';
                $tag = preg_replace($re, $replacement, $tag);
            } else {
                $replacement = '<script type="' . esc_attr($type) . '" ';
                $tag = str_replace('<script ', $replacement, $tag);
            }
        }

        if (
            $tag &&
            !empty($wp_scripts->registered[$handle]->extra['nomodule'])
        ) {
            if (
                !preg_match(
                    '/\snomodule([=\s]([\'\"])((?!\2).+?[^\\\])\2)?/',
                    $tag,
                    $match,
                )
            ) {
                $tag = str_replace('<script ', '<script nomodule ', $tag);
            }
        }

        return $tag ?: '';
    }

    /**
     * To register or enqueue a "regular" script only, leave $modulePath empty.
     *
     * This method does not handle timing, so make sure you call it at the
     * recommended time (wp_enqueue_scripts or admin_enqueue_scripts).
     *
     * Assumes the module entry points are cache-busted on the build side with
     * a hash in the filename.
     * If $enqueue is set to true it will not only register, but also enqueue
     * the script(s).
     * Will return both handles, no matter which scripts have actually been
     * registered / enqueued.
     *
     * @param string[] $dependencies
     *
     * @return array{string, string} module and nomodule script handles
     */
    public function register(
        string $handle,
        string $modulePath = null,
        string $nomodulePath = null,
        array $dependencies = [],
        bool $enqueue = false,
        ?string $version = null
    ): array {
        $nomoduleScriptHandle = $handle . '-nomodule';
        $moduleScriptHandle = $handle . '-module';
        $registerOrEnqueue = $enqueue
            ? 'wp_enqueue_script'
            : 'wp_register_script';

        if ($nomodulePath) {
            $registerOrEnqueue(
                $nomoduleScriptHandle,
                get_theme_file_uri($nomodulePath),
                $dependencies,
                $version,
                [
                    'in_footer' => true,
                    'strategy' => 'async',
                ],
            );

            // If we _only_ have a nomodule script, we don't mark it as such
            // in order to have all browsers load it.
            if ($modulePath) {
                wp_script_add_data($nomoduleScriptHandle, 'nomodule', true);
            }
        }

        if ($modulePath) {
            $registerOrEnqueue(
                $moduleScriptHandle,
                get_theme_file_uri($modulePath),
                $dependencies,
                null, // Cache-busting must be done via rollup hash so the same file doesn't get requested twice
                [
                    'in_footer' => true,
                    'strategy' => 'async',
                ],
            );
            wp_script_add_data($moduleScriptHandle, 'type', 'module');
        }

        return [$moduleScriptHandle, $nomoduleScriptHandle];
    }
}
