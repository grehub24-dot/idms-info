<?php
declare(strict_types=1);

require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../db.php';

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    json_response(['error' => 'Method not allowed'], 405);
}

header('Cache-Control: public, s-maxage=900, stale-while-revalidate=3600');

function ensure_news_table(PDO $pdo): void {
    $pdo->exec(
        "CREATE TABLE IF NOT EXISTS news ("
        . "id INT UNSIGNED NOT NULL AUTO_INCREMENT,"
        . "title VARCHAR(255) NOT NULL,"
        . "published_at DATE NULL,"
        . "content TEXT NULL,"
        . "source_url VARCHAR(512) NOT NULL,"
        . "image_url VARCHAR(512) NULL,"
        . "created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,"
        . "updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,"
        . "PRIMARY KEY (id),"
        . "UNIQUE KEY uniq_source_url (source_url)"
        . ") ENGINE=InnoDB DEFAULT CHARSET=utf8mb4"
    );
}

function upsert_news_items(PDO $pdo, array $items): void {
    if (empty($items)) {
        return;
    }

    $stmt = $pdo->prepare(
        "INSERT INTO news (title, published_at, content, source_url, image_url) "
        . "VALUES (?, ?, ?, ?, ?) "
        . "ON DUPLICATE KEY UPDATE "
        . "title = VALUES(title), "
        . "published_at = VALUES(published_at), "
        . "content = VALUES(content), "
        . "image_url = VALUES(image_url)"
    );

    foreach ($items as $item) {
        $title = isset($item['title']) ? (string)$item['title'] : '';
        $published = isset($item['published_at']) ? (string)$item['published_at'] : '';
        $content = isset($item['content']) ? (string)$item['content'] : '';
        $sourceUrl = isset($item['source_url']) ? (string)$item['source_url'] : '';
        $imageUrl = isset($item['image_url']) ? (string)$item['image_url'] : null;

        if ($title === '' || $sourceUrl === '') {
            continue;
        }

        $publishedDate = null;
        if ($published !== '') {
            $ts = strtotime($published);
            if ($ts !== false) {
                $publishedDate = date('Y-m-d', $ts);
            }
        }

        $stmt->execute([$title, $publishedDate, $content, $sourceUrl, $imageUrl]);
    }
}

function scrape_aamusted_news(int $limit = 12): array {
    $url = 'https://aamusted.edu.gh/news/';

    $context = stream_context_create([
        'http' => [
            'method' => 'GET',
            'header' => "User-Agent: INFOTESS-NewsBot/1.0\r\nAccept: text/html\r\n",
            'timeout' => 8,
        ],
        'https' => [
            'method' => 'GET',
            'header' => "User-Agent: INFOTESS-NewsBot/1.0\r\nAccept: text/html\r\n",
            'timeout' => 8,
        ],
    ]);

    $html = @file_get_contents($url, false, $context);
    if ($html === false || trim($html) === '') {
        return [];
    }

    $prevUseErrors = libxml_use_internal_errors(true);
    $dom = new DOMDocument();
    $loaded = @$dom->loadHTML($html);
    libxml_clear_errors();
    libxml_use_internal_errors($prevUseErrors);

    if (!$loaded) {
        return [];
    }

    $xpath = new DOMXPath($dom);
    $articles = $xpath->query('//article');
    if (!$articles || $articles->length === 0) {
        $articles = $xpath->query("//*[contains(concat(' ', normalize-space(@class), ' '), ' post ') or contains(concat(' ', normalize-space(@class), ' '), ' type-post ')][self::article or self::div]");
    }
    if (!$articles || $articles->length === 0) {
        return [];
    }

    $items = [];
    foreach ($articles as $article) {
        if (count($items) >= $limit) {
            break;
        }

        $titleNode = $xpath->query('.//h1//a | .//h2//a | .//h3//a', $article)->item(0);
        $title = $titleNode ? trim((string)$titleNode->textContent) : '';
        $href = $titleNode ? (string)$titleNode->getAttribute('href') : '';
        if ($href !== '' && str_starts_with($href, '/')) {
            $href = 'https://aamusted.edu.gh' . $href;
        }

        $timeNode = $xpath->query('.//time', $article)->item(0);
        $published = $timeNode ? (string)$timeNode->getAttribute('datetime') : '';
        if ($published === '' && $timeNode) {
            $published = trim((string)$timeNode->textContent);
        }
        if ($published !== '') {
            $ts = strtotime($published);
            if ($ts !== false) {
                $published = date('Y-m-d', $ts);
            }
        }

        $imgNode = $xpath->query('.//img', $article)->item(0);
        $img = $imgNode ? (string)$imgNode->getAttribute('src') : '';
        if ($img === '' && $imgNode) {
            $img = (string)$imgNode->getAttribute('data-src');
        }
        if ($img !== '' && str_starts_with($img, '/')) {
            $img = 'https://aamusted.edu.gh' . $img;
        }

        $excerptNode = $xpath->query('.//p', $article)->item(0);
        $excerpt = $excerptNode ? trim((string)$excerptNode->textContent) : '';

        if ($title === '' || $href === '') {
            continue;
        }

        $items[] = [
            'title' => $title,
            'published_at' => $published !== '' ? $published : date('Y-m-d'),
            'content' => $excerpt,
            'source_url' => $href,
            'image_url' => $img,
        ];
    }

    return $items;
}

try {
    $pdo = db();

    ensure_news_table($pdo);

    $db_news = [];
    $stmt = $pdo->query("SELECT title, published_at, content, source_url, image_url FROM news ORDER BY published_at DESC, id DESC LIMIT 12");
    $db_news = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $fallback_items = [
        [
            'title' => 'AAMUSTED Honours Former Staff',
            'published_at' => '2026-03-05',
            'content' => 'The Akenten Appiah-Menka University of Skills Training and Entrepreneurial Development (AAMUSTED) has presented a brand-new Royal Super Motorcycle to ...',
            'source_url' => 'https://aamusted.edu.gh/aamusted-honours-former-staff/',
            'image_url' => 'images/aamusted.jpg'
        ],
        [
            'title' => 'AAMUSTED’s First Valedictorian',
            'published_at' => '2026-02-04',
            'content' => 'The Akenten Appiah-Menka University of Skills Training and Entrepreneurial Development (AAMUSTED) made history with the delivery of its first-ever val...',
            'source_url' => 'https://aamusted.edu.gh/aamusteds-first-valedictorian/',
            'image_url' => 'images/aamusted.jpg'
        ]
    ];

    if (empty($db_news)) {
        upsert_news_items($pdo, $fallback_items);
        $stmt = $pdo->query("SELECT title, published_at, content, source_url, image_url FROM news ORDER BY published_at DESC, id DESC LIMIT 12");
        $db_news = $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    $scraped = [];
    try {
        $scraped = scrape_aamusted_news(12);
    } catch (Throwable $e) {
        $scraped = [];
    }

    if (!empty($scraped)) {
        upsert_news_items($pdo, $scraped);
        $stmt = $pdo->query("SELECT title, published_at, content, source_url, image_url FROM news ORDER BY published_at DESC, id DESC LIMIT 12");
        $news_items = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $source = 'scrape_db';
    } else {
        $news_items = $db_news;
        $source = 'db';
    }

    json_response(['ok' => true, 'news' => $news_items, 'source' => $source]);
} catch (Exception $e) {
    json_response(['ok' => false, 'error' => 'Failed to fetch news: ' . $e->getMessage()], 500);
}
