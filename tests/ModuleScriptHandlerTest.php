<?php

namespace Gebruederheitz\Wordpress\Tests;

use Gebruederheitz\Wordpress\ModuleScriptHandler;
use PHPUnit\Framework\TestCase;

class ModuleScriptHandlerTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        global $wp_scripts;
        $wp_scripts = new \stdClass();
        $wp_scripts->registered = [];
        $wp_scripts->enqueued = [];
    }

    public function testAddModuleNomoduleAddsTypeModuleAttribute(): void
    {
        global $wp_scripts;
        $handle = 'my-esm-script';
        $wp_scripts->registered[$handle] = (object) [
            'extra' => ['type' => 'module'],
        ];

        $handler = ModuleScriptHandler::getInstance();
        $tag = $handler->addModuleNomodule(
            '<script src="foo.js" id="' . $handle . '"></script>',
            $handle,
        );

        $this->assertStringContainsString('type="module"', $tag);
    }

    public function testAddModuleNomoduleAddsNomoduleAttribute(): void
    {
        global $wp_scripts;
        $handle = 'my-legacy-script';
        $wp_scripts->registered[$handle] = (object) [
            'extra' => ['nomodule' => true],
        ];

        $handler = ModuleScriptHandler::getInstance();
        $tag = $handler->addModuleNomodule(
            '<script src="bar.js" id="' . $handle . '"></script>',
            $handle,
        );

        $this->assertStringContainsString('nomodule', $tag);
    }

    public function testOnScriptLoaderTagDelegatesToAddModuleNomodule(): void
    {
        global $wp_scripts;
        $handle = 'delegate-script';
        $wp_scripts->registered[$handle] = (object) [
            'extra' => ['type' => 'module'],
        ];

        $handler = ModuleScriptHandler::getInstance();
        $tag = $handler->onScriptLoaderTag(
            '<script src="baz.js"></script>',
            $handle,
        );

        $this->assertStringContainsString('type="module"', $tag);
    }

    public function testRegisterRegistersModuleAndNomoduleScriptsAndReturnsHandles(): void
    {
        global $wp_scripts;

        $handler = ModuleScriptHandler::getInstance();
        [$moduleHandle, $nomoduleHandle] = $handler->register(
            'my-hybrid-script',
            'js/main.esm.js',
            'js/main.legacy.js',
        );

        $this->assertSame('my-hybrid-script-module', $moduleHandle);
        $this->assertSame('my-hybrid-script-nomodule', $nomoduleHandle);

        $this->assertArrayHasKey($moduleHandle, $wp_scripts->registered);
        $this->assertArrayHasKey($nomoduleHandle, $wp_scripts->registered);

        $this->assertSame(
            'module',
            $wp_scripts->registered[$moduleHandle]->extra['type'],
        );
        $this->assertTrue(
            $wp_scripts->registered[$nomoduleHandle]->extra['nomodule'],
        );
    }
}
