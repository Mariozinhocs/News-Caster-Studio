<?php
header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Método não permitido. Use POST.']);
    exit;
}

$rawInput = file_get_contents('php://input');
$data = json_decode($rawInput, true);

$prompt = trim($data['prompt'] ?? '');
$url = trim($data['url'] ?? '');
$tone = trim($data['tone'] ?? 'jornalistico');

if (empty($prompt) && empty($url)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Forneça uma pauta/tema ou um link de notícia.']);
    exit;
}

function fetchUrlContent($targetUrl) {
    if (!function_exists('curl_init')) {
        $opts = ['http' => ['header' => "User-Agent: Mozilla/5.0\r\n", 'timeout' => 7]];
        return @file_get_contents($targetUrl, false, stream_context_create($opts));
    }
    $ch = curl_init($targetUrl);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
    curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36');
    curl_setopt($ch, CURLOPT_TIMEOUT, 8);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    $data = curl_exec($ch);
    curl_close($ch);
    return $data;
}

$scrapedTitle = '';
$scrapedParagraphs = [];

// Se não tiver URL direta mas tiver Prompt, buscar notícia real relacionada no Google News RSS
if (empty($url) && !empty($prompt)) {
    $searchRssUrl = 'https://news.google.com/rss/search?q=' . urlencode($prompt) . '&hl=pt-BR&gl=BR&ceid=BR:pt-419';
    $rssData = fetchUrlContent($searchRssUrl);
    if (!empty($rssData)) {
        libxml_use_internal_errors(true);
        $xml = @simplexml_load_string($rssData);
        if ($xml && isset($xml->channel->item[0])) {
            $topItem = $xml->channel->item[0];
            $scrapedTitle = html_entity_decode(strip_tags((string)$topItem->title), ENT_QUOTES, 'UTF-8');
            $scrapedTitle = preg_replace('/(\s*[\-\|]\s*.*)$/i', '', $scrapedTitle);
            $rawDesc = html_entity_decode(strip_tags((string)$topItem->description), ENT_QUOTES, 'UTF-8');
            if (!empty($rawDesc)) {
                $scrapedParagraphs[] = $rawDesc;
            }
        }
    }
}

// Se tiver URL direta (ou encontrada), fazer extração da página
if (!empty($url) && filter_var($url, FILTER_VALIDATE_URL)) {
    $html = fetchUrlContent($url);
    if (!empty($html)) {
        // Limpar blocos de script e style antes de extrair qualquer texto
        $html = preg_replace('/<(script|style)\b[^>]*>(.*?)<\/\1>/is', '', $html);

        if (preg_match('/<title>(.*?)<\/title>/is', $html, $m)) {
            $scrapedTitle = trim(html_entity_decode(strip_tags($m[1]), ENT_QUOTES, 'UTF-8'));
            $scrapedTitle = preg_replace('/(\s*[\-\|]\s*.*)$/i', '', $scrapedTitle);
        }
        preg_match_all('/<p[^>]*>(.*?)<\/p>/is', $html, $matches);
        if (!empty($matches[1])) {
            foreach ($matches[1] as $pText) {
                $cleanP = trim(html_entity_decode(strip_tags($pText), ENT_QUOTES, 'UTF-8'));
                if (mb_strlen($cleanP) > 60 && !preg_match('/(cookie|direitos|termos|privacidade|publicidade)/i', $cleanP)) {
                    $scrapedParagraphs[] = $cleanP;
                }
            }
        }
    }
}

// Montar Título e Corpo Factual
$headline = !empty($scrapedTitle) ? $scrapedTitle : $prompt;
if (mb_strlen($headline) < 15) {
    $headline = "Notícia Baré: " . $headline;
}

$bodyContent = [];

if (!empty($scrapedParagraphs)) {
    // Caso tenhamos fatos extraídos de matérias reais
    $pCount = count($scrapedParagraphs);
    $lead = $scrapedParagraphs[0];
    
    $bodyContent[] = "<p><b>" . mb_substr($lead, 0, 160) . "</b>" . mb_substr($lead, 160) . "</p>";

    if ($pCount > 1) {
        $bodyContent[] = "<p>" . $scrapedParagraphs[1] . "</p>";
    }

    $bodyContent[] = "<h2>Contexto e Desdobramentos</h2>";

    if ($pCount > 2) {
        $bodyContent[] = "<p>" . $scrapedParagraphs[2] . "</p>";
    }
    if ($pCount > 3) {
        $bodyContent[] = "<p>" . $scrapedParagraphs[3] . "</p>";
    } else {
        $bodyContent[] = "<p>A repercussão em torno de <b>" . htmlspecialchars($headline) . "</b> mobiliza a opinião pública e lideranças do setor, que aguardam novos posicionamentos oficiais.</p>";
    }

    $bodyContent[] = "<h3>Próximos Passos</h3>";
    if ($pCount > 4) {
        $bodyContent[] = "<p>" . $scrapedParagraphs[4] . "</p>";
    } else {
        $bodyContent[] = "<p>O portal <b>Notícia Baré</b> segue acompanhando o desdobramento das informações e trará atualizações em tempo real.</p>";
    }

} else {
    // Caso seja apenas um tema/pauta sem página externa
    $bodyContent[] = "<p><b>" . htmlspecialchars($headline) . "</b> é o tema central em pauta no Notícia Baré. A cobertura traz os principais fatos e desdobramentos que movimentam a região do Amazonas e o cenário nacional.</p>";
    $bodyContent[] = "<h2>Panorama dos Fatos</h2>";
    $bodyParagraph = "Em relação a " . htmlspecialchars($prompt) . ", autoridades e lideranças acompanham os desdobramentos com atenção. As medidas discutidas visam impactar diretamente a gestão pública, o desenvolvimento econômico e o cotidiano da população.";
    $bodyContent[] = "<p>" . $bodyParagraph . "</p>";
    $bodyContent[] = "<h3>Impactos e Perspectivas</h3>";
    $bodyContent[] = "<p>A expectativa é que novas diretrizes sejam apresentadas nas próximas reuniões oficiais. O acompanhamento dos desdobramentos permanece prioritário para garantir transparência aos leitores do Notícia Baré.</p>";
}

$formattedContent = implode("\n\n", $bodyContent);

// Montar Legenda do Instagram
$leadExcerpt = !empty($scrapedParagraphs) ? $scrapedParagraphs[0] : $prompt;
$igCaption = "📢 <strong>" . htmlspecialchars($headline) . "</strong>\n\n" .
             mb_substr($leadExcerpt, 0, 180) . "...\n\n" .
             "👉 Confira a matéria completa e todos os detalhes no portal Notícia Baré! Link na bio. 🗞️✨";

// Gerar Hashtags Inteligentes
$words = preg_split('/\s+/', $headline);
$tags = ['#NoticiaBare', '#Amazonas', '#Manaus', '#Jornalismo'];
foreach ($words as $w) {
    $cleanW = preg_replace('/[^a-zA-Z0-9À-ÿ]/', '', $w);
    if (mb_strlen($cleanW) > 4 && count($tags) < 8) {
        $tag = '#' . ucfirst(mb_strtolower($cleanW));
        if (!in_array($tag, $tags)) {
            $tags[] = $tag;
        }
    }
}

echo json_encode([
    'success'           => true,
    'title'             => $headline,
    'content'           => $formattedContent,
    'instagram_caption' => $igCaption,
    'hashtags'          => implode(' ', $tags),
    'extracted_facts'   => count($scrapedParagraphs) > 0
]);
