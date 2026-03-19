<?php

declare(strict_types=1);

require_once __DIR__ . '/../config.php';

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    json_response(['error' => 'Method not allowed'], 405);
}

$limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 24;
$limit = max(1, min(60, $limit));

function http_get(string $url): string {
    $opts = [
        'http' => [
            'method' => 'GET',
            'header' => "User-Agent: Mozilla/5.0\r\nAccept: text/html\r\n",
            'timeout' => 15,
        ],
        'ssl' => [
            'verify_peer' => true,
            'verify_peer_name' => true,
        ],
    ];

    $ctx = stream_context_create($opts);
    $html = @file_get_contents($url, false, $ctx);
    if ($html === false || trim($html) === '') {
        throw new RuntimeException('Failed to fetch URL: ' . $url);
    }

    return $html;
}

function load_dom(string $html): DOMDocument {
    $prev = libxml_use_internal_errors(true);
    $dom = new DOMDocument();
    $dom->loadHTML($html);
    libxml_clear_errors();
    libxml_use_internal_errors($prev);
    return $dom;
}

function first_xpath_text(DOMXPath $xp, string $query): string {
    $n = $xp->query($query);
    if (!$n || $n->length === 0) {
        return '';
    }
    $text = trim((string)$n->item(0)->textContent);
    $text = preg_replace('/\s+/', ' ', $text);
    return trim((string)$text);
}

function normalize_space(string $text): string {
    $t = trim($text);
    $t = preg_replace('/\s+/', ' ', $t);
    return trim((string)$t);
}

function extract_profile(string $url): array {
    $html = http_get($url);
    $dom = load_dom($html);
    $xp = new DOMXPath($dom);

    $name = first_xpath_text($xp, '//main//*[self::h1][1]');
    if ($name === '') {
        $name = first_xpath_text($xp, '//*[self::h1][1]');
    }

    $photo = '';
    $photo = first_xpath_text($xp, "//meta[@property='og:image']/@content");
    if ($photo === '') {
        $photo = first_xpath_text($xp, "//meta[@name='twitter:image']/@content");
    }

    $email = '';
    $emailHref = first_xpath_text($xp, "//a[starts-with(@href,'mailto:')]/@href");
    if ($emailHref !== '') {
        $email = preg_replace('/^mailto:/', '', $emailHref);
    }

    $phone = '';
    $phoneHref = first_xpath_text($xp, "//a[starts-with(@href,'tel:')]/@href");
    if ($phoneHref !== '') {
        $phone = preg_replace('/^tel:/', '', $phoneHref);
    }

    $mainText = '';
    $main = $xp->query('//main');
    if ($main && $main->length > 0) {
        $mainText = normalize_space((string)$main->item(0)->textContent);
    } else {
        $mainText = normalize_space((string)$dom->textContent);
    }

    $faculty = preg_match('/\bFASME\b/i', $mainText) ? 'FASME' : '';

    $dept = '';
    if (preg_match('/\bDepartment of\s+([^\n]+?)(?:\bFASME\b|P\.?\s*O\.?\s*Box|Kumasi|\+233|$)/i', $mainText, $m)) {
        $dept = normalize_space('Department of ' . $m[1]);
    }

    $role = '';
    if (preg_match('/\b(Ag\.?\s*Head[^\n]*?|Acting Head[^\n]*?|Head of Department[^\n]*?|Assist\.?\s*Lecturer|Assistant Lecturer|Lecturer|Professor|Prof\.?[^\n]*?)\b/i', $mainText, $m)) {
        $role = normalize_space($m[0]);
    }

    if ($email === '' && preg_match('/[A-Z0-9._%+-]+@[A-Z0-9.-]+\.[A-Z]{2,}/i', $mainText, $m)) {
        $email = $m[0];
    }

    if ($phone === '' && preg_match('/\+?\d[\d\s().-]{7,}\d/', $mainText, $m)) {
        $phone = normalize_space($m[0]);
    }

    $bio = '';
    $paras = $xp->query("//main//p[normalize-space(.) != '']");
    if ($paras && $paras->length > 0) {
        $parts = [];
        for ($i = 0; $i < $paras->length; $i++) {
            $p = normalize_space((string)$paras->item($i)->textContent);
            if ($p === '') {
                continue;
            }
            if (mb_strlen($p) < 40) {
                continue;
            }
            $parts[] = $p;
            if (count($parts) >= 1) {
                break;
            }
        }
        $bio = $parts ? $parts[0] : '';
    }

    return [
        'name' => $name,
        'role' => $role,
        'faculty' => $faculty,
        'department' => $dept,
        'email' => $email,
        'phone' => $phone,
        'photo' => $photo,
        'profile_url' => $url,
        'bio' => $bio,
    ];
}

function extract_fasme_staff_urls(string $listingUrl): array {
    $html = http_get($listingUrl);
    $dom = load_dom($html);
    $xp = new DOMXPath($dom);

    $urls = [];
    $nodes = $xp->query("//a[starts-with(@href,'https://aamusted.edu.gh/staff/')]");
    if ($nodes) {
        for ($i = 0; $i < $nodes->length; $i++) {
            $href = (string)$nodes->item($i)->getAttribute('href');
            if ($href === '') {
                continue;
            }
            $urls[] = $href;
        }
    }

    $urls = array_values(array_unique($urls));
    sort($urls);
    return $urls;
}

try {
    $listingUrl = 'https://aamusted.edu.gh/fasme/staff/';
    $urls = extract_fasme_staff_urls($listingUrl);

    $urls = array_slice($urls, 0, $limit);

    $staff = [];
    foreach ($urls as $u) {
        try {
            $staff[] = extract_profile($u);
        } catch (Throwable $e) {
            $staff[] = [
                'name' => '',
                'role' => '',
                'faculty' => 'FASME',
                'department' => '',
                'email' => '',
                'phone' => '',
                'photo' => '',
                'profile_url' => $u,
                'bio' => '',
                'error' => 'Failed to fetch profile',
            ];
        }
    }

    json_response([
        'ok' => true,
        'source' => [
            'listing_url' => $listingUrl,
            'count' => count($staff),
        ],
        'staff' => $staff,
    ]);
} catch (Throwable $e) {
    json_response(['error' => 'Failed to fetch staff'], 500);
}
