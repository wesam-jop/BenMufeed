<?php

$path = 'c:/Users/wesam/OneDrive/Downloads/الوجبات_المنظمة.xlsx';
$z = new ZipArchive();
$z->open($path);
$xml = $z->getFromName('xl/worksheets/sheet1.xml');
$z->close();

function colIndex(string $col): int
{
    $n = 0;
    foreach (str_split($col) as $c) {
        $n = $n * 26 + (ord($c) - 64);
    }

    return $n - 1;
}

$sheet = simplexml_load_string($xml);
$rows = [];
foreach ($sheet->sheetData->row as $row) {
    $line = [];
    foreach ($row->c as $c) {
        $ref = (string) $c['r'];
        preg_match('/^([A-Z]+)/', $ref, $m);
        $idx = colIndex($m[1]);
        $type = (string) ($c['t'] ?? '');
        $val = (string) ($c->v ?? '');
        if ($type === 'inlineStr' && isset($c->is->t)) {
            $val = (string) $c->is->t;
        }
        $line[$idx] = trim($val);
    }
    ksort($line);
    $rows[] = array_values($line);
}

array_shift($rows); // header
$byCat = [];
foreach ($rows as $r) {
    if (count($r) < 3 || $r[0] === '' || $r[1] === '') {
        continue;
    }
    $cat = trim($r[0]);
    $byCat[$cat][] = ['name' => trim($r[1]), 'price' => (float) $r[2]];
}

echo 'Categories: ', count($byCat), PHP_EOL;
foreach ($byCat as $cat => $items) {
    echo $cat, ' (', count($items), ')', PHP_EOL;
}
echo 'Total meals: ', array_sum(array_map('count', $byCat)), PHP_EOL;

file_put_contents(__DIR__.'/meals-data.json', json_encode($byCat, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT));
