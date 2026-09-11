<?php

namespace App\Support;

use Illuminate\View\FileViewFinder;

class InertiaModuleViewFinder extends FileViewFinder
{
    /** @var list<string> */
    private const MODULE_AREAS = [
        'admin',
        'pos',
        'kitchen',
        'customer',
        'storefront',
        'auth',
        'settings',
    ];

    public static function resolveComponentPath(string $name): string
    {
        $slash = strpos($name, '/');
        $head = $slash === false ? $name : substr($name, 0, $slash);
        $rest = $slash === false ? '' : substr($name, $slash + 1);

        if (in_array($head, self::MODULE_AREAS, true)) {
            return $rest !== '' ? "{$head}/pages/{$rest}" : "{$head}/pages/index";
        }

        return "shared/pages/{$name}";
    }

    public function find($view): string
    {
        $resolved = self::resolveComponentPath($view);

        try {
            return parent::find($resolved);
        } catch (\InvalidArgumentException) {
            return parent::find($view);
        }
    }
}
