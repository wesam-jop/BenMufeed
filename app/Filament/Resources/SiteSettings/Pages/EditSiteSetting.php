<?php

namespace App\Filament\Resources\SiteSettings\Pages;

use App\Filament\Resources\SiteSettings\SiteSettingResource;
use App\Support\PhoneCountries;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditSiteSetting extends EditRecord
{
    protected static string $resource = SiteSettingResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }

    protected function mutateFormDataBeforeFill(array $data): array
    {
        $parsed = PhoneCountries::parse($data['whatsapp_phone'] ?? null);

        $data['whatsapp_country'] = PhoneCountries::optionKey($parsed['dial'], $parsed['iso']);
        $data['whatsapp_local_number'] = $parsed['local'];

        return $data;
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        $form = $this->form->getRawState();

        [$dial, $iso] = PhoneCountries::splitOptionKey(
            (string) ($form['whatsapp_country'] ?? PhoneCountries::defaultOptionKey()),
        );

        $local = (string) ($form['whatsapp_local_number'] ?? '');

        $data['whatsapp_phone'] = $local !== ''
            ? PhoneCountries::format($dial, $iso, $local)
            : $this->getRecord()->whatsapp_phone;

        unset($data['whatsapp_country'], $data['whatsapp_local_number']);

        return $data;
    }
}
