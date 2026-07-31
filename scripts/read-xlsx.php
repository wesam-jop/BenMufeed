<?php

$path = $argv[1] ?? '';
if ($path === '' || ! is_readable($path)) {
    fwrite(STDERR, "Usage: php read-xlsx.php <file.xlsx>\n");
    exit(1);
}

function colIndex(string $col): int
{
    $n = 0;
    foreach (str_split($col) as $c) {
        $n = $n * 26 + (ord($c) - 64);
    }

    return $n - 1;
}

$zip = new ZipArchive();
if ($zip->open($path) !== true) {
    fwrite(STDERR, "Cannot open xlsx\n");
    exit(1);
}

$shared = [];
$sharedXml = $zip->getFromName('xl/sharedStrings.xml');
if ($sharedXml !== false) {
    $sx = simplexml_load_string($sharedXml);
    foreach ($sx->si as $si) {
        if (isset($si->t)) {
            $shared[] = (string) $si->t;
        } else {
            $parts = [];
            foreach ($si->r as $r) {
                $parts[] = (string) $r->t;
            }
            $shared[] = implode('', $parts);
        }
    }
}

$workbook = simplexml_load_string($zip->getFromName('xl/workbook.xml'));
$workbook->registerXPathNamespace('m', 'http://schemas.openxmlformats.org/spreadsheetml/2006/main');
$workbook->registerXPathNamespace('r', 'http://schemas.openxmlformats.org/officeDocument/2006/relationships');

$rels = simplexml_load_string($zip->getFromName('xl/_rels/workbook.xml.rels'));
$rels->registerXPathNamespace('p', 'http://schemas.openxmlformats.org/package/2006/relationships');
$relMap = [];
foreach ($rels->Relationship as $rel) {
    $relMap[(string) $rel['Id']] = (string) $rel['Target'];
}

$allSheets = [];
foreach ($workbook->sheets->sheet as $sheetInfo) {
    $name = (string) $sheetInfo['name'];
    $rid = (string) $sheetInfo->attributes('r', true)['id'];
    $target = 'xl/'.ltrim(str_replace('\\', '/', $relMap[$rid]), '/');

    $sheetXml = $zip->getFromName($target);
    if ($sheetXml === false) {
        continue;
    }

    $sheet = simplexml_load_string($sheetXml);
    $rows = [];
    foreach ($sheet->sheetData->row as $row) {
        $line = [];
        foreach ($row->c as $c) {
            $ref = (string) $c['r'];
            preg_match('/^([A-Z]+)/', $ref, $m);
            $idx = colIndex($m[1]);
            $type = (string) ($c['t'] ?? '');
            $val = $type === 's' ? ($shared[(int) $c->v] ?? '') : (string) ($c->v ?? '');
            $line[$idx] = trim($val);
        }
        if ($line !== []) {
            ksort($line);
            $rows[] = array_values($line);
        }
    }
    $allSheets[$name] = $rows;
}

$zip->close();

echo json_encode($allSheets, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
