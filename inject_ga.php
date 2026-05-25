<?php
$files = glob("*.php");
$injection = "\n    <?php @include 'includes/analytics.php'; ?>\n</head>";

foreach ($files as $file) {
    if (in_array($file, ['check_data.php', 'check_db.php', 'inject_ga.php', 'backup_database.php', 'update_db_auto.php'])) continue;
    $content = file_get_contents($file);
    if (strpos($content, '</head>') !== false && strpos($content, 'includes/analytics.php') === false) {
        $content = str_replace('</head>', $injection, $content);
        file_put_contents($file, $content);
        echo "Injected GA into $file\n";
    }
}
