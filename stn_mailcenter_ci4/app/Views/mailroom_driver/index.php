<!DOCTYPE html>
<html lang="ko">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <meta name="theme-color" content="#2563eb">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
    <title>메일룸 기사</title>
    <link rel="manifest" href="/manifest.json">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background: #f3f4f6;
            min-height: 100vh;
            padding-bottom: 80px;
        }
        .header {
            background: linear-gradient(135deg, #2563eb 0%, #1d4ed8 100%);
            color: white;
            padding: 20px 16px;
            position: sticky;
            top: 0;
            z-index: 100;
        }
        .header h1 {
            font-size: 18px;
            font-weight: 600;
        }
        .header .driver-info {
            font-size: 14px;
            opacity: 0.9;
            margin-top: 4px;
        }
        .header-row {
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .install-btn {
            background: rgba(255,255,255,0.2);
            border: 1px solid rgba(255,255,255,0.3);
            color: white;
            padding: 8px 12px;
            border-radius: 8px;
            font-size: 12px;
            font-weight: 600;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
        }
        .install-btn.hidden {
            display: none;
        }
        .install-btn:active {
            background: rgba(255,255,255,0.3);
        }
        .install-btn svg {
            width: 16px;
            height: 16px;
            vertical-align: middle;
            margin-right: 4px;
        }
        .stats {
            display: flex;
            gap: 8px;
            padding: 12px 16px;
            background: white;
            border-bottom: 1px solid #e5e7eb;
        }
        .stat-item {
            flex: 1;
            text-align: center;
            padding: 8px;
            border-radius: 8px;
            background: #f9fafb;
        }
        .stat-item.urgent {
            background: #fef2f2;
        }
        .stat-number {
            font-size: 24px;
            font-weight: 700;
            color: #1f2937;
        }
        .stat-item.urgent .stat-number {
            color: #dc2626;
        }
        .stat-label {
            font-size: 11px;
            color: #6b7280;
            margin-top: 2px;
        }
        .tab-bar {
            display: flex;
            background: white;
            border-bottom: 1px solid #e5e7eb;
            position: sticky;
            top: 70px;
            z-index: 99;
        }
        .tab {
            flex: 1;
            padding: 12px;
            text-align: center;
            font-size: 14px;
            color: #6b7280;
            border-bottom: 2px solid transparent;
            cursor: pointer;
        }
        .tab.active {
            color: #2563eb;
            border-bottom-color: #2563eb;
            font-weight: 600;
        }
        .order-list {
            padding: 12px;
        }
        .order-card {
            background: white;
            border-radius: 12px;
            padding: 16px;
            margin-bottom: 12px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
            cursor: pointer;
            transition: transform 0.15s;
        }
        .order-card:active {
            transform: scale(0.98);
        }
        .order-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 12px;
        }
        .order-time {
            font-size: 13px;
            color: #6b7280;
        }
        .order-status {
            padding: 4px 10px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
        }
        .order-status.pending {
            background: #dbeafe;
            color: #1d4ed8;
        }
        .order-status.picked {
            background: #fef3c7;
            color: #d97706;
        }
        .order-status.completed {
            background: #d1fae5;
            color: #059669;
        }
        .order-route {
            display: flex;
            align-items: stretch;
            gap: 12px;
        }
        .route-line {
            width: 20px;
            display: flex;
            flex-direction: column;
            align-items: center;
        }
        .route-dot {
            width: 10px;
            height: 10px;
            border-radius: 50%;
            background: #2563eb;
        }
        .route-dot.end {
            background: #dc2626;
        }
        .route-connector {
            flex: 1;
            width: 2px;
            background: #e5e7eb;
            margin: 4px 0;
        }
        .route-info {
            flex: 1;
        }
        .route-point {
            margin-bottom: 16px;
        }
        .route-point:last-child {
            margin-bottom: 0;
        }
        .route-label {
            font-size: 11px;
            color: #9ca3af;
            margin-bottom: 2px;
        }
        .route-building {
            font-size: 15px;
            font-weight: 600;
            color: #1f2937;
        }
        .route-detail {
            font-size: 13px;
            color: #6b7280;
        }
        .order-item {
            margin-top: 12px;
            padding-top: 12px;
            border-top: 1px solid #f3f4f6;
            font-size: 13px;
            color: #4b5563;
        }
        .order-item strong {
            color: #1f2937;
        }
        .urgent-badge {
            display: inline-block;
            background: #dc2626;
            color: white;
            padding: 2px 8px;
            border-radius: 4px;
            font-size: 11px;
            font-weight: 600;
            margin-left: 8px;
        }
        .bottom-nav {
            position: fixed;
            bottom: 0;
            left: 0;
            right: 0;
            background: white;
            border-top: 1px solid #e5e7eb;
            display: flex;
            padding: 8px 0;
            padding-bottom: max(8px, env(safe-area-inset-bottom));
        }
        .nav-item {
            flex: 1;
            text-align: center;
            padding: 8px;
            color: #6b7280;
            text-decoration: none;
        }
        .nav-item.active {
            color: #2563eb;
        }
        .nav-item svg {
            width: 24px;
            height: 24px;
            margin: 0 auto;
        }
        .nav-item span {
            display: block;
            font-size: 11px;
            margin-top: 4px;
        }
        .empty-state {
            text-align: center;
            padding: 60px 20px;
            color: #9ca3af;
        }
        .empty-state svg {
            width: 64px;
            height: 64px;
            margin-bottom: 16px;
            opacity: 0.5;
        }
    </style>
</head>
<body>
    <div class="header">
        <div class="header-row">
            <h1>메일룸 기사</h1>
            <button id="installBtn" class="install-btn">
                <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/>
                </svg>
                앱 설치
            </button>
        </div>
        <div class="driver-info"><?= esc($driver_name) ?> 님 | 그랑서울타워 담당</div>
    </div>

    <div class="stats">
        <div class="stat-item urgent">
            <div class="stat-number"><?= count(array_filter($orders, fn($o) => $o['priority'] === 'urgent' && $o['status'] === 'pending')) ?></div>
            <div class="stat-label">긴급</div>
        </div>
        <div class="stat-item">
            <div class="stat-number"><?= count(array_filter($orders, fn($o) => $o['status'] === 'pending')) ?></div>
            <div class="stat-label">접수</div>
        </div>
        <div class="stat-item">
            <div class="stat-number"><?= count(array_filter($orders, fn($o) => $o['status'] === 'picked')) ?></div>
            <div class="stat-label">픽업</div>
        </div>
        <div class="stat-item">
            <div class="stat-number"><?= count(array_filter($orders, fn($o) => $o['status'] === 'completed')) ?></div>
            <div class="stat-label">완료</div>
        </div>
    </div>

    <div class="tab-bar">
        <div class="tab active" data-filter="all">전체</div>
        <div class="tab" data-filter="pending">접수</div>
        <div class="tab" data-filter="picked">픽업</div>
        <div class="tab" data-filter="completed">완료</div>
    </div>

    <div class="order-list">
        <?php foreach ($orders as $order): ?>
        <div class="order-card" data-status="<?= $order['status'] ?>" onclick="location.href='/mailroom-driver/detail/<?= $order['id'] ?>'">
            <div class="order-header">
                <div class="order-time">
                    <?= $order['created_at'] ?>
                    <?php if ($order['priority'] === 'urgent'): ?>
                    <span class="urgent-badge">긴급</span>
                    <?php endif; ?>
                </div>
                <div class="order-status <?= $order['status'] ?>"><?= $order['status_text'] ?></div>
            </div>
            <div class="order-route">
                <div class="route-line">
                    <div class="route-dot"></div>
                    <div class="route-connector"></div>
                    <div class="route-dot end"></div>
                </div>
                <div class="route-info">
                    <div class="route-point">
                        <div class="route-label">출발</div>
                        <div class="route-building"><?= esc($order['from_building']) ?> <?= esc($order['from_floor']) ?></div>
                        <div class="route-detail"><?= esc($order['from_company']) ?></div>
                    </div>
                    <div class="route-point">
                        <div class="route-label">도착</div>
                        <div class="route-building"><?= esc($order['to_building']) ?> <?= esc($order['to_floor']) ?></div>
                        <div class="route-detail"><?= esc($order['to_company']) ?></div>
                    </div>
                </div>
            </div>
            <div class="order-item">
                <strong>물품:</strong> <?= esc($order['item']) ?>
                <?php if ($order['memo']): ?>
                <br><strong>메모:</strong> <?= esc($order['memo']) ?>
                <?php endif; ?>
            </div>
        </div>
        <?php endforeach; ?>
    </div>

    <div class="bottom-nav">
        <a href="/mailroom-driver" class="nav-item active">
            <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01"/>
            </svg>
            <span>배송목록</span>
        </a>
        <a href="#" class="nav-item">
            <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v1m6 11h2m-6 0h-2v4m0-11v3m0 0h.01M12 12h4.01M16 20h4M4 12h4m12 0h.01M5 8h2a1 1 0 001-1V5a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1zm12 0h2a1 1 0 001-1V5a1 1 0 00-1-1h-2a1 1 0 00-1 1v2a1 1 0 001 1zM5 20h2a1 1 0 001-1v-2a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1z"/>
            </svg>
            <span>바코드</span>
        </a>
        <a href="#" class="nav-item">
            <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
            </svg>
            <span>내 정보</span>
        </a>
    </div>

    <script>
        // 탭 필터링
        document.querySelectorAll('.tab').forEach(tab => {
            tab.addEventListener('click', function() {
                document.querySelectorAll('.tab').forEach(t => t.classList.remove('active'));
                this.classList.add('active');

                const filter = this.dataset.filter;
                document.querySelectorAll('.order-card').forEach(card => {
                    if (filter === 'all' || card.dataset.status === filter) {
                        card.style.display = 'block';
                    } else {
                        card.style.display = 'none';
                    }
                });
            });
        });

        // Service Worker 등록
        if ('serviceWorker' in navigator) {
            navigator.serviceWorker.register('/sw.js')
                .then(reg => console.log('SW registered'))
                .catch(err => console.log('SW registration failed', err));
        }

        // PWA 설치 프롬프트
        let deferredPrompt = null;
        const installBtn = document.getElementById('installBtn');

        // 이미 앱으로 실행 중이면 버튼 숨김
        if (window.matchMedia('(display-mode: standalone)').matches || window.navigator.standalone) {
            installBtn.classList.add('hidden');
        }

        window.addEventListener('beforeinstallprompt', (e) => {
            e.preventDefault();
            deferredPrompt = e;
        });

        installBtn.addEventListener('click', async () => {
            // PWA 설치 가능한 경우
            if (deferredPrompt) {
                deferredPrompt.prompt();
                const { outcome } = await deferredPrompt.userChoice;
                if (outcome === 'accepted') {
                    installBtn.classList.add('hidden');
                }
                deferredPrompt = null;
                return;
            }

            // iOS Safari
            if (/iPhone|iPad|iPod/.test(navigator.userAgent)) {
                alert('📱 앱 설치 방법\n\n1. Safari 하단의 공유 버튼(□↑)을 누르세요\n2. "홈 화면에 추가"를 선택하세요');
                return;
            }

            // Android Chrome
            if (/Android/.test(navigator.userAgent)) {
                alert('📱 앱 설치 방법\n\n1. 우측 상단 ⋮ 메뉴를 누르세요\n2. "홈 화면에 추가" 또는 "앱 설치"를 선택하세요');
                return;
            }

            // PC 브라우저
            alert('📱 앱 설치 방법\n\n주소창 오른쪽의 설치 아이콘(⊕)을 클릭하거나,\n메뉴에서 "앱 설치"를 선택하세요.\n\n※ HTTPS 환경에서만 설치 가능합니다.');
        });

        window.addEventListener('appinstalled', () => {
            installBtn.classList.add('hidden');
        });
    </script>
</body>
</html>