<?php
$files = glob("c:\\xampp\\htdocs\\pos\\admin\\*.php");
$count = 0;
foreach ($files as $f) {
    if (basename($f) == 'sidebar.php')
        continue;

    $content = file_get_contents($f);
    $changed = false;

    // Add SweetAlert
    if (strpos($content, 'sweetalert2') === false && strpos($content, '</head>') !== false) {
        $content = str_replace('</head>', "    <script src=\"https://cdn.jsdelivr.net/npm/sweetalert2@11\"></script>\n</head>", $content);
        $changed = true;
    }

    // Replace sidebar
    if (preg_match('/<aside class="sidebar">.*?<\/aside>/s', $content)) {
        $content = preg_replace('/<aside class="sidebar">.*?<\/aside>/s', "<?php include 'sidebar.php'; ?>", $content);
        $changed = true;
    }

    // Convert old `alert` or `confirm` optionally? No, let's do that manually later or selectively.
    if ($changed) {
        file_put_contents($f, $content);
        $count++;
    }
}
echo "Updated $count files.\n";
