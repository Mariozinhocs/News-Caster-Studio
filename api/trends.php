<?php
header('Content-Type: application/json; charset=utf-8');

// Feeds RSS de Notícias
$rssFeeds = [
    [
        'category' => 'Amazonas',
        'source'   => 'G1 AM',
        'url'      => 'https://g1.globo.com/rss/g1/am/amazonas/'
    ],
    [
        'category' => 'Manaus & AM',
        'source'   => 'Google News AM',
        'url'      => 'https://news.google.com/rss/search?q=Manaus+OR+Amazonas+OR+David+Almeida&hl=pt-BR&gl=BR&ceid=BR:pt-419'
    ],
    [
        'category' => 'Política',
        'source'   => 'Google News Política',
        'url'      => 'https://news.google.com/rss/headlines/section/topic/POLITICS?hl=pt-BR&gl=BR&ceid=BR:pt-419'
    ],
    [
        'category' => 'Brasil',
        'source'   => 'Agência Brasil',
        'url'      => 'https://agenciabrasil.ebc.com.br/rss/ultimasnoticias/feed.xml'
    ]
];

$items = [];
$seenTitles = [];

function fetchRss($url) {
    if (!function_exists('curl_init')) {
        $opts = ['http' => ['header' => "User-Agent: Mozilla/5.0\r\n", 'timeout' => 5]];
        return @file_get_contents($url, false, stream_context_create($opts));
    }

    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36');
    curl_setopt($ch, CURLOPT_TIMEOUT, 6);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    $data = curl_exec($ch);
    curl_close($ch);
    return $data;
}

foreach ($rssFeeds as $feed) {
    $xmlData = fetchRss($feed['url']);
    if (empty($xmlData)) continue;

    // Tentar carregar XML
    libxml_use_internal_errors(true);
    $xml = @simplexml_load_string($xmlData);
    if (!$xml || !isset($xml->channel->item)) continue;

    $count = 0;
    foreach ($xml->channel->item as $entry) {
        if ($count >= 4) break; // Pegar até 4 por feed

        $rawTitle = trim((string)$entry->title);
        $rawLink = trim((string)$entry->link);
        $rawDesc = trim((string)$entry->description);

        $cleanTitle = html_entity_decode(strip_tags($rawTitle), ENT_QUOTES, 'UTF-8');
        $cleanDesc = html_entity_decode(strip_tags($rawDesc), ENT_QUOTES, 'UTF-8');

        // Normalização para evitar duplicatas
        $normKey = mb_strtolower(preg_replace('/[^a-zA-Z0-9À-ÿ]/', '', $cleanTitle));
        if (empty($normKey) || isset($seenTitles[$normKey])) continue;
        $seenTitles[$normKey] = true;

        if (mb_strlen($cleanTitle) < 15) continue;

        $items[] = [
            'title'       => $cleanTitle,
            'description' => mb_substr($cleanDesc, 0, 220) . (mb_strlen($cleanDesc) > 220 ? '...' : ''),
            'link'        => $rawLink,
            'category'    => $feed['category'],
            'source'      => $feed['source']
        ];
        $count++;
    }
}

// Embaralhar levemente ou ordenar por relevância
usort($items, function($a, $b) {
    // Priorizar Amazonas e Manaus no topo
    $isAM_a = (strpos($a['category'], 'Amazonas') !== false || strpos($a['category'], 'Manaus') !== false) ? 1 : 0;
    $isAM_b = (strpos($b['category'], 'Amazonas') !== false || strpos($b['category'], 'Manaus') !== false) ? 1 : 0;
    return $isAM_b - $isAM_a;
});

// Numerar Ranks de 1 a N
$rankedItems = [];
$rank = 1;
foreach (array_slice($items, 0, 8) as $it) {
    $it['rank'] = $rank++;
    $rankedItems[] = $it;
}

echo json_encode([
    'success' => true,
    'count'   => count($rankedItems),
    'trends'  => $rankedItems
]);
