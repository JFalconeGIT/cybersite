<?php
// cyber_news_fetcher.php
// A simple PHP-based website introducing Jayden Falcone with an automatically updating cybersecurity news index.
// This script fetches the latest cybersecurity news from Krebs on Security RSS feed (updates on page load).
// For production, consider caching or cron jobs for true "automatic" updates without full reload.

// Define the RSS feed URL for cybersecurity news
$rss_url = 'https://krebsonsecurity.com/feed/';

// Function to fetch and parse RSS feed
function fetchCyberNews($url, $limit = 5) {
    $news_items = [];
    if (($rss_content = @file_get_contents($url)) === false) {
        return $news_items; // Return empty on failure
    }
    
    $rss = simplexml_load_string($rss_content);
    if ($rss === false) {
        return $news_items;
    }
    
    // Namespace fix for RSS
    $rss->registerXPathNamespace('atom', 'http://www.w3.org/2005/Atom');
    
    foreach ($rss->channel->item as $item) {
        if (count($news_items) >= $limit) {
            break;
        }
        $news_items[] = [
            'title' => (string)$item->title,
            'link' => (string)$item->link,
            'description' => (string)$item->description,
            'pubDate' => (string)$item->pubDate
        ];
    }
    
    return $news_items;
}

// Fetch the news
$cyber_news = fetchCyberNews($rss_url);

// Start HTML output
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Jayden Falcone - Cybersecurity Enthusiast</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 0; padding: 0; background-color: #f4f4f4; color: #333; }
        header { background-color: #1a1a1a; color: #fff; padding: 20px; text-align: center; }
        .container { max-width: 800px; margin: 20px auto; padding: 20px; background: white; box-shadow: 0 0 10px rgba(0,0,0,0.1); }
        .about { margin-bottom: 40px; }
        .news-section { margin-top: 40px; }
        .news-item { border-bottom: 1px solid #ddd; padding: 10px 0; }
        .news-item h3 { margin: 0 0 5px 0; }
        .news-item a { color: #007bff; text-decoration: none; }
        .news-item a:hover { text-decoration: underline; }
        .error { color: #ff0000; text-align: center; }
    </style>
</head>
<body>
    <header>
        <h1>Jayden Falcone</h1>
        <p>Cybersecurity Enthusiast | Aspiring Ethical Hacker | Keeping the Digital World Safe</p>
    </header>
    
    <div class="container">
        <section class="about">
            <h2>About Me</h2>
            <p>Hi, I'm Jayden Falcone, a passionate cybersecurity professional with a keen interest in threat intelligence, ethical hacking, and digital forensics. With a background in computer science and hands-on experience in penetration testing, I stay ahead of the curve by monitoring the latest cyber threats and vulnerabilities.</p>
            <p>This website serves as my personal hub to introduce myself and share an up-to-date index of relevant cybersecurity information. Whether it's breaking news on ransomware attacks or tips on secure coding, I'm committed to fostering awareness and knowledge in the cyber domain.</p>
            <p>Contact: jayden.falcone@example.com | LinkedIn: <a href="https://linkedin.com/in/jaydenfalcone" target="_blank">linkedin.com/in/jaydenfalcone</a></p>
        </section>
        
        <section class="news-section">
            <h2>Latest Cybersecurity News Index</h2>
            <p>This index automatically updates with the most recent articles from trusted sources like Krebs on Security. Reload the page for fresh content.</p>
            <?php if (empty($cyber_news)): ?>
                <p class="error">Unable to fetch latest news at the moment. Please try again later.</p>
            <?php else: ?>
                <?php foreach ($cyber_news as $item): ?>
                    <div class="news-item">
                        <h3><a href="<?php echo htmlspecialchars($item['link']); ?>" target="_blank"><?php echo htmlspecialchars($item['title']); ?></a></h3>
                        <p><em>Published: <?php echo htmlspecialchars($item['pubDate']); ?></em></p>
                        <p><?php echo htmlspecialchars(substr(strip_tags($item['description']), 0, 200)); ?>...</p>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </section>
    </div>
    
    <footer style="text-align: center; padding: 10px; background-color: #1a1a1a; color: #fff; margin-top: 20px;">
        <p>&copy; 2025 Jayden Falcone. All rights reserved.</p>
    </footer>
</body>
</html>
