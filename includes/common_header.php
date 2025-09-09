<?php
// 공통 헤더 파일
?>
<!DOCTYPE html>
<html lang="ko">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo isset($page_title) ? $page_title : 'STN Network'; ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="/assets/css/style.css">
    <link rel="stylesheet" href="/assets/css/order.css">
</head>
<body>
    <div class="container">
        <?php include __DIR__ . '/sidebar.php'; ?>
        
        <main class="main-content">
            <?php if (isset($content_header)): ?>
            <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-4 mb-4">
                <h1 class="text-lg font-semibold text-gray-800 mb-1"><?php echo $content_header['title']; ?></h1>
                <p class="text-sm text-gray-600"><?php echo $content_header['description']; ?></p>
            </div>
            <?php endif; ?>
