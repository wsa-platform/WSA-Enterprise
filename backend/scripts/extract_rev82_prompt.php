<?php

declare(strict_types=1);

$path = $argv[1] ?? '';
$out = $argv[2] ?? '';
if ($path === '' || $out === '') {
    fwrite(STDERR, "usage: extract_rev82_prompt.php <transcript.jsonl> <out.md>\n");
    exit(1);
}

$fh = fopen($path, 'rb');
if ($fh === false) {
    fwrite(STDERR, "cannot open transcript\n");
    exit(2);
}

$needle = 'REVISION 8.2';
$last = null;
while (($line = fgets($fh)) !== false) {
    if (strpos($line, $needle) === false) {
        continue;
    }
    if (strpos($line, 'FULL ENGINEERING AUTHORIZATION') === false) {
        continue;
    }
    $last = $line;
}
fclose($fh);

if ($last === null) {
    fwrite(STDERR, "prompt line not found\n");
    exit(3);
}

$j = json_decode($last, true);
if (! is_array($j)) {
    fwrite(STDERR, 'json fail: '.json_last_error_msg()."\n");
    exit(4);
}

$text = '';
foreach (($j['message']['content'] ?? []) as $part) {
    if (($part['type'] ?? '') === 'text') {
        $text .= (string) ($part['text'] ?? '');
    }
}

$text = preg_replace('/^\s*<timestamp>[\s\S]*?<\/timestamp>\s*/u', '', $text) ?? $text;
$text = preg_replace('/^\s*<user_query>\s*/u', '', $text) ?? $text;
$text = preg_replace('/\s*<\/user_query>\s*$/u', '', $text) ?? $text;

$dir = dirname($out);
if (! is_dir($dir) && ! mkdir($dir, 0777, true) && ! is_dir($dir)) {
    fwrite(STDERR, "cannot create out dir\n");
    exit(5);
}

file_put_contents($out, $text);
echo 'BYTES='.strlen($text).PHP_EOL;
echo 'SHA='.hash('sha256', $text).PHP_EOL;
echo 'HEAD='.substr(ltrim($text), 0, 100).PHP_EOL;
