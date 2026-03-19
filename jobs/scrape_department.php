<?php
require_once __DIR__ . '/includes/db.php';

function scrape_news_and_events() {
    global $pdo;
    
    // Target URLs (Main University News and Departmental)
    $urls = [
        'https://aamusted.edu.gh/news-events/',
        'https://aamusted.edu.gh/category/news/'
    ];

    foreach ($urls as $url) {
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/91.0.4472.124 Safari/537.36');
        $html = curl_exec($ch);
        curl_close($ch);

        if (!$html) continue;

        // Basic DOM parsing to extract news items
        // In a real scenario, use a library like Guzzle and DomCrawler
        $dom = new DOMDocument();
        @$dom->loadHTML($html);
        $xpath = new DOMXPath($dom);

        // Find common news article patterns (WordPress-specific tags)
        $articles = $xpath->query("//article[contains(@class, 'post')]");
        
        foreach ($articles as $article) {
            $titleNode = $xpath->query(".//h2[contains(@class, 'entry-title')]/a", $article)->item(0);
            $imgNode = $xpath->query(".//img", $article)->item(0);
            $descNode = $xpath->query(".//div[contains(@class, 'entry-content')]//p", $article)->item(0);
            
            if ($titleNode) {
                $title = trim($titleNode->nodeValue);
                $link = $titleNode->getAttribute('href');
                $image = $imgNode ? $imgNode->getAttribute('src') : null;
                $description = $descNode ? trim($descNode->nodeValue) : '';

                // Insert into News table
                try {
                    $stmt = $pdo->prepare("INSERT IGNORE INTO news (title, content, source_url, image_url, published_at) VALUES (?, ?, ?, ?, NOW())");
                    $stmt->execute([$title, $description, $link, $image]);
                } catch (Exception $e) {
                    // Ignore duplicate key errors or log them
                }
            }
        }
    }
    
    // Scrape events similarly (adjust selectors for events)
}

// Manual trigger check or hourly cron setup
scrape_news_and_events();
echo "News and events scraping complete!";
?>