<!DOCTYPE html>
<html lang="ko">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $title ?? 'STN Network' ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="<?= base_url('assets/css/style.css') ?>">
    <link rel="stylesheet" href="<?= base_url('assets/css/order.css') ?>">
</head>
<body>
    <div class="container">
        <?= $this->include('layouts/sidebar') ?>
        
        <main class="main-content">
            <?php if (isset($content_header)): ?>
            <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-4 mb-4">
                <h1 class="text-lg font-semibold text-gray-800 mb-1"><?= $content_header['title'] ?></h1>
                <p class="text-sm text-gray-600"><?= $content_header['description'] ?></p>
            </div>
            <?php endif; ?>
            
            <?php if (session()->getFlashdata('success')): ?>
            <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded mb-4">
                <?= session()->getFlashdata('success') ?>
            </div>
            <?php endif; ?>
            
            <?php if (session()->getFlashdata('error')): ?>
            <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-4">
                <?= session()->getFlashdata('error') ?>
            </div>
            <?php endif; ?>
            
            <?php if (isset($errors) && !empty($errors)): ?>
            <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-4">
                <ul class="list-disc list-inside">
                    <?php foreach ($errors as $error): ?>
                    <li><?= $error ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
            <?php endif; ?>
