<?php

namespace App\Filament\Pages;

use App\Support\AdminPageHelp\Registry;
use BackedEnum;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;

class PageHelpDocumentation extends Page
{
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedQuestionMarkCircle;

    protected static ?string $slug = 'help/{topic}';

    protected static bool $shouldRegisterNavigation = false;

    protected string $view = 'filament.pages.page-help-documentation';

    protected static ?string $title = 'شرح الصفحة';

    public string $topic = '';

    /** @var array<string, mixed>|null */
    public ?array $help = null;

    public function mount(string $topic): void
    {
        $this->topic = $topic;
        $this->help = Registry::get($topic);

        if ($this->help === null) {
            abort(404);
        }
    }

    public function getTitle(): string
    {
        return (string) ($this->help['title'] ?? 'شرح الصفحة');
    }

    public static function canAccess(): bool
    {
        return auth()->user()?->is_admin ?? false;
    }
}
