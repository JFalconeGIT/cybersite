<?php
// index.php - Jayden Falcone's Cybersecurity Site with Mini-CMS
// Admin login: Visit /index.php?admin=login | Default: user 'admin' / pass 'changeme' (CHANGE IMMEDIATELY!)
// Data stored in /data/ (create folder manually with 755 perms, owned by www-data)

// Config
$admin_user = 'admin';  // CHANGE THIS
$admin_pass = 'changeme';  // CHANGE THIS (use a strong password)
$data_dir = __DIR__ . '/data/';
$bio_file = $data_dir . 'bio.json';
$news_file = $data_dir . 'custom_news.json';

// Ensure data dir exists
if (!is_dir($data_dir)) {
    mkdir($data_dir, 0755, true);
    // Default bio if missing
    if (!file_exists($bio_file)) {
        file_put_contents($bio_file, json_encode([
            'bio' => "Hi, I'm Jayden Falcone, a passionate cybersecurity professional with a keen interest in threat intelligence, ethical hacking, and digital forensics. With a background in computer science and hands-on experience in penetration testing, I stay ahead of the curve by monitoring the latest cyber threats and vulnerabilities.",
            'contact' => "Contact: jayden.falcone@mymail.champlain.edu | LinkedIn: <a href=\"https://linkedin.com/in/jaydenfalcone\" target=\"_blank\">linkedin.com/in/jaydenfalcone</a>"
        ], JSON_PRETTY_PRINT));
    }
    // Default custom news if missing
    if (!file_exists($news_file)) {
        file_put_contents($news_file, json_encode([], JSON_PRETTY_PRINT));
    }
}

// Simple auth function
function is_logged_in() {
    return isset($_SESSION['admin_logged_in']) && $_SESSION['admin_logged_in'] === true;
}
session_start();

// Handle admin login
if (isset($_GET['admin']) && $_GET['admin'] === 'login' && !is_logged_in()) {
    if ($_POST['user'] === $admin_user && $_POST['pass'] === $admin_pass) {
        $_SESSION['admin_logged_in'] = true;
        header('Location: index.php?admin=dashboard');
        exit;
    } else {
        $login_error = true;
    }
}

// Handle logout
if (isset($_GET['admin']) && $_GET['admin'] === 'logout') {
    session_destroy();
    header('Location: index.php');
    exit;
}

// Handle CMS actions (only if logged in)
if (is_logged_in() && isset($_GET['admin'])) {
    $action = $_GET['admin'];
    if ($action === 'update_bio' && $_POST) {
        $bio_data = [
            'bio' => $_POST['bio'] ?? '',
            'contact' => $_POST['contact'] ?? ''
        ];
        file_put_contents($bio_file, json_encode($bio_data, JSON_PRETTY_PRINT));
        header('Location: index.php?admin=dashboard');
        exit;
    } elseif ($action === 'add_news' && $_POST) {
        $custom_news = json_decode(file_get_contents($news_file), true) ?: [];
        $custom_news[] = [
            'title' => $_POST['title'],
            'link' => $_POST['link'],
            'description' => $_POST['description'],
            'date' => date('r')  // Auto-set pub date
        ];
        file_put_contents($news_file, json_encode($custom_news, JSON_PRETTY_PRINT));
        header('Location: index.php?admin=dashboard');
        exit;
    } elseif ($action === 'delete_news' && isset($_GET['id'])) {
        $custom_news = json_decode(file_get_contents($news_file), true) ?: [];
        unset($custom_news[$_GET['id']]);
        $custom_news = array_values($custom_news);  // Reindex
        file_put_contents($news_file, json_encode($custom_news, JSON_PRETTY_PRINT));
        header('Location: index.php?admin=dashboard');
        exit;
    }
}

// Fetch RSS (same as before)
$rss_url = 'https://krebsonsecurity.com/feed/';
function fetchCyberNews($url, $limit = 5) {
    $news_items = [];
    if (($rss_content = @file_get_contents($url)) === false) return $news_items;
    $rss = simplexml_load_string($rss_content);
    if ($rss === false) return $news_items;
    $rss->registerXPathNamespace('atom', 'http://www.w3.org/2005/Atom');
    foreach ($rss->channel->item as $item) {
        if (count($news_items) >= $limit) break;
        $news_items[] = [
            'title' => (string)$item->title,
            'link' => (string)$item->link,
            'description' => (string)$item->description,
            'pubDate' => (string)$item->pubDate
        ];
    }
    return $news_items;
}
$cyber_news = fetchCyberNews($rss_url);

// Load stored data
$bio_data = json_decode(file_get_contents($bio_file), true) ?: ['bio' => '', 'contact' => ''];
$custom_news = json_decode(file_get_contents($news_file), true) ?: [];

// Start HTML
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
        .admin-nav { background: #eee; padding: 10px; text-align: right; }
        .admin-form { background: #f9f9f9; padding: 20px; margin: 20px 0; border: 1px solid #ddd; }
        .admin-form textarea, .admin-form input { width: 100%; padding: 8px; margin: 5px 0; box-sizing: border-box; }
        .btn { background: #007bff; color: white; padding: 10px 15px; border: none; cursor: pointer; text-decoration: none; display: inline-block; margin: 5px; }
        .btn:hover { background: #0056b3; }
        .btn-danger { background: #dc3545; }
        .error { color: #ff0000; background: #ffe6e6; padding: 10px; border-left: 4px solid #ff0000; }
        .login-form { max-width: 300px; margin: 100px auto; padding: 20px; background: white; box-shadow: 0 0 10px rgba(0,0,0,0.1); }
    </style>
</head>
<body>
    <?php if (!is_logged_in() && isset($_GET['admin']) && $_GET['admin'] === 'login'): ?>
        <div class="login-form">
            <h2>Admin Login</h2>
            <?php if (isset($login_error)): ?><div class="error">Invalid credentials!</div><?php endif; ?>
            <form method="POST">
                <input type="text" name="user" placeholder="Username" required><br>
                <input type="password" name="pass" placeholder="Password" required><br>
                <button type="submit" class="btn">Login</button>
            </form>
            <p><a href="index.php">Back to Site</a></p>
        </div>
    <?php elseif (is_logged_in() && isset($_GET['admin']) && $_GET['admin'] === 'dashboard'): ?>
        <header>
            <h1>Admin Dashboard</h1>
        </header>
        <div class="container">
            <div class="admin-nav">
                <a href="index.php?admin=logout" class="btn btn-danger">Logout</a>
                <a href="index.php" class="btn">View Site</a>
            </div>
            
            <section>
                <h2>Edit About Me</h2>
                <form method="POST" action="index.php?admin=update_bio" class="admin-form">
                    <textarea name="bio" rows="5" placeholder="Bio text..."><?php echo htmlspecialchars($bio_data['bio']); ?></textarea><br>
                    <input type="text" name="contact" placeholder="Contact info..." value="<?php echo htmlspecialchars($bio_data['contact']); ?>"><br>
                    <button type="submit" class="btn">Update Bio</button>
                </form>
            </section>
            
            <section>
                <h2>Manage Custom News (Optional Add-Ons to RSS)</h2>
                <form method="POST" action="index.php?admin=add_news" class="admin-form">
                    <input type="text" name="title" placeholder="Title" required><br>
                    <input type="url" name="link" placeholder="Link (optional)"><br>
                    <textarea name="description" rows="3" placeholder="Description" required></textarea><br>
                    <button type="submit" class="btn">Add News Item</button>
                </form>
                <h3>Current Custom Items:</h3>
                <?php if (empty($custom_news)): ?>
                    <p>No custom news yet.</p>
                <?php else: ?>
                    <?php foreach ($custom_news as $index => $item): ?>
                        <div class="news-item">
                            <h3><?php echo htmlspecialchars($item['title']); ?> <a href="index.php?admin=delete_news&id=<?php echo $index; ?>" class="btn btn-danger" onclick="return confirm('Delete?');">Delete</a></h3>
                            <p><em><?php echo htmlspecialchars($item['date']); ?></em></p>
                            <p><?php echo htmlspecialchars($item['description']); ?></p>
                            <?php if (!empty($item['link'])): ?><a href="<?php echo htmlspecialchars($item['link']); ?>" target="_blank">Read More</a><?php endif; ?>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </section>
        </div>
    <?php else: // Public site view ?>
        <header>
            <h1>Jayden Falcone</h1>
            <p>Cybersecurity Enthusiast | Aspiring Ethical Hacker | Keeping the Digital World Safe</p>
            <?php if (is_logged_in()): ?>
                <div class="admin-nav"><a href="index.php?admin=dashboard" class="btn">Admin Dashboard</a> <a href="index.php?admin=logout" class="btn btn-danger">Logout</a></div>
            <?php else: ?>
                <div class="admin-nav"><a href="index.php?admin=login" class="btn">Admin Login</a></div>
            <?php endif; ?>
        </header>
        
        <div class="container">
            <section class="about">
                <h2>About Me</h2>
                <p><?php echo $bio_data['bio']; ?></p>
                <p><?php echo $bio_data['contact']; ?></p>
            </section>
            
            <section class="news-section">
                <h2>Latest Cybersecurity News Index</h2>
                <p>This index automatically updates with recent articles from trusted sources. Custom items are curated below.</p>
                
                <?php if (!empty($custom_news)): ?>
                    <h3>Curated Highlights</h3>
                    <?php foreach (array_slice(array_reverse($custom_news), 0, 3) as $item): // Show latest 3 ?>
                        <div class="news-item">
                            <h3><a href="<?php echo htmlspecialchars($item['link'] ?? '#'); ?>" target="_blank"><?php echo htmlspecialchars($item['title']); ?></a></h3>
                            <p><em>Added: <?php echo htmlspecialchars($item['date']); ?></em></p>
                            <p><?php echo htmlspecialchars(substr($item['description'], 0, 200)); ?>...</p>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
                
                <h3>Auto-Fetched News</h3>
                <?php if (empty($cyber_news)): ?>
                    <p class="error">Unable to fetch latest news. Please try again later.</p>
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
    <?php endif; ?>
    
    <footer style="text-align: center; padding: 10px; background-color: #1a1a1a; color: #fff; margin-top: 20px;">
        <p>&copy; 2025 Jayden Falcone. All rights reserved.</p>
    </footer>
</body>
</html>
