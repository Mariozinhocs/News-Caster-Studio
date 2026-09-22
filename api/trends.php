<?php
header('Content-Type: application/json; charset=utf-8');

$requestedTopic = isset($_GET['topic']) ? trim(mb_strtolower($_GET['topic'])) : 'all';

// Mapeamento de feeds RSS por slug de categoria do portal
$specificFeeds = [
    'amazonas' => [
        [
            'category' => 'Amazonas',
            'source'   => 'G1 AM',
            'url'      => 'https://g1.globo.com/rss/g1/am/amazonas/'
        ],
        [
            'category' => 'Amazonas & Manaus',
            'source'   => 'Google News AM',
            'url'      => 'https://news.google.com/rss/search?q=Amazonas+OR+Manaus&hl=pt-BR&gl=BR&ceid=BR:pt-419'
        ]
    ],
    'manaus' => [
        [
            'category' => 'Manaus',
            'source'   => 'Google News Manaus',
            'url'      => 'https://news.google.com/rss/search?q=Manaus+AM&hl=pt-BR&gl=BR&ceid=BR:pt-419'
        ]
    ],
    'politica' => [
        [
            'category' => 'Política',
            'source'   => 'Google News Política',
            'url'      => 'https://news.google.com/rss/headlines/section/topic/POLITICS?hl=pt-BR&gl=BR&ceid=BR:pt-419'
        ]
    ],
    'brasil' => [
        [
            'category' => 'Brasil',
            'source'   => 'Agência Brasil',
            'url'      => 'https://agenciabrasil.ebc.com.br/rss/ultimasnoticias/feed.xml'
        ],
        [
            'category' => 'Brasil',
            'source'   => 'Google News Brasil',
            'url'      => 'https://news.google.com/rss/search?q=Brasil+Noticias&hl=pt-BR&gl=BR&ceid=BR:pt-419'
        ]
    ],
    'economia' => [
        [
            'category' => 'Economia',
            'source'   => 'Google News Economia',
            'url'      => 'https://news.google.com/rss/headlines/section/topic/BUSINESS?hl=pt-BR&gl=BR&ceid=BR:pt-419'
        ]
    ],
    'tech' => [
        [
            'category' => 'Tech',
            'source'   => 'Google News Tecnologia',
            'url'      => 'https://news.google.com/rss/headlines/section/topic/TECHNOLOGY?hl=pt-BR&gl=BR&ceid=BR:pt-419'
        ]
    ],
    'entretenimento' => [
        [
            'category' => 'Entretenimento',
            'source'   => 'Google News Pop',
            'url'      => 'https://news.google.com/rss/headlines/section/topic/ENTERTAINMENT?hl=pt-BR&gl=BR&ceid=BR:pt-419'
        ]
    ],
    'saude' => [
        [
            'category' => 'Saúde',
            'source'   => 'Google News Saúde',
            'url'      => 'https://news.google.com/rss/headlines/section/topic/HEALTH?hl=pt-BR&gl=BR&ceid=BR:pt-419'
        ]
    ],
    'meio-ambiente' => [
        [
            'category' => 'Meio Ambiente',
            'source'   => 'Google News Meio Ambiente',
            'url'      => 'https://news.google.com/rss/search?q=Meio+Ambiente+OR+Amazonia+OR+COP30&hl=pt-BR&gl=BR&ceid=BR:pt-419'
        ]
    ]
];

// Seleciona os feeds com base no tópico selecionado
if ($requestedTopic === 'all' || $requestedTopic === '') {
    $rssFeeds = [
        [
            'category' => 'Amazonas',
            'source'   => 'G1 AM',
            'url'      => 'https://g1.globo.com/rss/g1/am/amazonas/'
        ],
        [
            'category' => 'Manaus & AM',
            'source'   => 'Google News AM',
            'url'      => 'https://news.google.com/rss/search?q=Manaus+OR+Amazonas&hl=pt-BR&gl=BR&ceid=BR:pt-419'
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
} elseif (isset($specificFeeds[$requestedTopic])) {
    $rssFeeds = $specificFeeds[$requestedTopic];
} else {
    // Para qualquer outra categoria vinda do portal (ex: 'parintins', 'cop30', 'celebridades', etc.)
    $searchTerm = str_replace('-', ' ', $requestedTopic);
    $rssFeeds = [
        [
            'category' => mb_convert_case($searchTerm, MB_CASE_TITLE, "UTF-8"),
            'source'   => 'Google News Search',
            'url'      => 'https://news.google.com/rss/search?q=' . urlencode($searchTerm) . '&hl=pt-BR&gl=BR&ceid=BR:pt-419'
        ]
    ];
}

$items = [];
$seenTitles = [];

function fetchRss($url) {
    if (!function_exists('curl_init')) {
        $opts = ['http' => ['header' => "User-Agent: Mozilla/5.0\r\n", 'timeout' => 6]];
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

    libxml_use_internal_errors(true);
    $xml = @simplexml_load_string($xmlData);
    if (!$xml || !isset($xml->channel->item)) continue;

    $count = 0;
    foreach ($xml->channel->item as $entry) {
        if ($count >= 6) break; // Até 6 por feed

        $rawTitle = trim((string)$entry->title);
        $rawLink  = trim((string)$entry->link);
        $rawDesc  = trim((string)$entry->description);

        $cleanTitle = html_entity_decode(strip_tags($rawTitle), ENT_QUOTES, 'UTF-8');
        $cleanDesc  = html_entity_decode(strip_tags($rawDesc), ENT_QUOTES, 'UTF-8');

        // Normalização para evitar duplicatas
        $normKey = mb_strtolower(preg_replace('/[^a-zA-Z0-9À-ÿ]/', '', $cleanTitle));
        if (empty($normKey) || isset($seenTitles[$normKey])) continue;
        $seenTitles[$normKey] = true;

        if (mb_strlen($cleanTitle) < 12) continue;

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

// Ordenar priorizando itens com informações de categoria no topo
usort($items, function($a, $b) {
    $isAM_a = (strpos($a['category'], 'Amazonas') !== false || strpos($a['category'], 'Manaus') !== false) ? 1 : 0;
    $isAM_b = (strpos($b['category'], 'Amazonas') !== false || strpos($b['category'], 'Manaus') !== false) ? 1 : 0;
    return $isAM_b - $isAM_a;
});

// Numerar Ranks de 1 a N (máximo 10 itens)
$rankedItems = [];
$rank = 1;
foreach (array_slice($items, 0, 10) as $it) {
    $it['rank'] = $rank++;
    $rankedItems[] = $it;
}

echo json_encode([
    'success' => true,
    'count'   => count($rankedItems),
    'trends'  => $rankedItems
]);
