<?php

namespace App\Http\Controllers\Restaurant;

use App\Http\Controllers\Controller;
use App\Models\RestaurantTable;
use App\Services\TableQrCardRenderer;
use Illuminate\Http\Response;
use ZipArchive;

class TableQrController extends Controller
{
    public function single(RestaurantTable $table, TableQrCardRenderer $renderer): Response
    {
        $filename = 'table-'.$table->number.'-qr.png';

        return response($renderer->render($table), 200, [
            'Content-Type' => 'image/png',
            'Content-Disposition' => 'attachment; filename="'.$filename.'"',
        ]);
    }

    public function venue(TableQrCardRenderer $renderer): Response
    {
        return response($renderer->renderVenue(), 200, [
            'Content-Type' => 'image/png',
            'Content-Disposition' => 'attachment; filename="venue-qr.png"',
        ]);
    }

    public function all(TableQrCardRenderer $renderer): Response
    {
        $tables = RestaurantTable::query()
            ->where('is_active', true)
            ->orderBy('number')
            ->get();

        $zipPath = tempnam(sys_get_temp_dir(), 'table-qr-');
        if ($zipPath === false) {
            abort(500, 'تعذّر إنشاء ملف التصدير.');
        }

        $zipFile = $zipPath.'.zip';
        rename($zipPath, $zipFile);

        $zip = new ZipArchive;
        if ($zip->open($zipFile, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
            @unlink($zipFile);
            abort(500, 'تعذّر إنشاء ملف ZIP.');
        }

        foreach ($tables as $table) {
            $zip->addFromString(
                'table-'.$table->number.'-qr.png',
                $renderer->render($table),
            );
        }

        $zip->close();

        $contents = file_get_contents($zipFile);
        @unlink($zipFile);

        if ($contents === false) {
            abort(500, 'تعذّر قراءة ملف التصدير.');
        }

        return response($contents, 200, [
            'Content-Type' => 'application/zip',
            'Content-Disposition' => 'attachment; filename="tables-qr.zip"',
        ]);
    }
}
