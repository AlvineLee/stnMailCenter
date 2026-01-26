<!DOCTYPE html>
<html lang="ko">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <meta name="theme-color" content="#2563eb">
    <title>배송 상세</title>
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
            padding-bottom: 100px;
        }
        .header {
            background: linear-gradient(135deg, #2563eb 0%, #1d4ed8 100%);
            color: white;
            padding: 16px;
            display: flex;
            align-items: center;
            gap: 12px;
        }
        .back-btn {
            background: none;
            border: none;
            color: white;
            padding: 8px;
            margin: -8px;
            cursor: pointer;
        }
        .back-btn svg {
            width: 24px;
            height: 24px;
        }
        .header h1 {
            font-size: 18px;
            font-weight: 600;
        }
        .status-banner {
            padding: 16px;
            text-align: center;
            font-weight: 600;
        }
        .status-banner.pending {
            background: #dbeafe;
            color: #1d4ed8;
        }
        .status-banner.picked {
            background: #fef3c7;
            color: #d97706;
        }
        .status-banner.completed {
            background: #d1fae5;
            color: #059669;
        }
        .card {
            background: white;
            margin: 12px;
            border-radius: 12px;
            padding: 16px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
        }
        .card-title {
            font-size: 13px;
            color: #6b7280;
            margin-bottom: 12px;
            font-weight: 600;
        }
        .route-box {
            display: flex;
            gap: 12px;
        }
        .route-line {
            width: 24px;
            display: flex;
            flex-direction: column;
            align-items: center;
            padding: 4px 0;
        }
        .route-dot {
            width: 12px;
            height: 12px;
            border-radius: 50%;
            background: #2563eb;
            border: 3px solid #dbeafe;
        }
        .route-dot.end {
            background: #dc2626;
            border-color: #fee2e2;
        }
        .route-connector {
            flex: 1;
            width: 2px;
            background: #e5e7eb;
            margin: 8px 0;
        }
        .route-info {
            flex: 1;
        }
        .route-point {
            padding: 12px 0;
            border-bottom: 1px solid #f3f4f6;
        }
        .route-point:last-child {
            border-bottom: none;
        }
        .route-label {
            font-size: 12px;
            color: #9ca3af;
            margin-bottom: 4px;
        }
        .route-building {
            font-size: 17px;
            font-weight: 600;
            color: #1f2937;
            margin-bottom: 4px;
        }
        .route-detail {
            font-size: 14px;
            color: #6b7280;
        }
        .info-row {
            display: flex;
            padding: 12px 0;
            border-bottom: 1px solid #f3f4f6;
        }
        .info-row:last-child {
            border-bottom: none;
        }
        .info-label {
            width: 80px;
            font-size: 14px;
            color: #6b7280;
        }
        .info-value {
            flex: 1;
            font-size: 14px;
            color: #1f2937;
        }
        .urgent-badge {
            display: inline-block;
            background: #dc2626;
            color: white;
            padding: 2px 8px;
            border-radius: 4px;
            font-size: 12px;
            font-weight: 600;
        }
        .action-buttons {
            position: fixed;
            bottom: 0;
            left: 0;
            right: 0;
            background: white;
            padding: 12px 16px;
            padding-bottom: max(12px, env(safe-area-inset-bottom));
            border-top: 1px solid #e5e7eb;
            display: flex;
            gap: 12px;
        }
        .btn {
            flex: 1;
            padding: 16px;
            border: none;
            border-radius: 12px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: transform 0.15s;
        }
        .btn:active {
            transform: scale(0.98);
        }
        .btn-primary {
            background: #2563eb;
            color: white;
        }
        .btn-success {
            background: #059669;
            color: white;
        }
        .btn-secondary {
            background: #f3f4f6;
            color: #4b5563;
        }
        .btn:disabled {
            opacity: 0.5;
            cursor: not-allowed;
        }
        .call-btn {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            padding: 12px;
            background: #f3f4f6;
            border-radius: 8px;
            color: #4b5563;
            text-decoration: none;
            font-size: 14px;
            margin-top: 8px;
        }
        .call-btn svg {
            width: 18px;
            height: 18px;
        }
    </style>
</head>
<body>
    <div class="header">
        <button class="back-btn" onclick="history.back()">
            <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
            </svg>
        </button>
        <h1>배송 상세</h1>
    </div>

    <div class="status-banner <?= $order['status'] ?>">
        <?php if ($order['status'] === 'pending'): ?>
            픽업 대기중
        <?php elseif ($order['status'] === 'picked'): ?>
            배송중 - 도착지로 이동하세요
        <?php else: ?>
            배송 완료
        <?php endif; ?>
    </div>

    <div class="card">
        <div class="card-title">배송 경로</div>
        <div class="route-box">
            <div class="route-line">
                <div class="route-dot"></div>
                <div class="route-connector"></div>
                <div class="route-dot end"></div>
            </div>
            <div class="route-info">
                <div class="route-point">
                    <div class="route-label">출발지</div>
                    <div class="route-building"><?= esc($order['from_building']) ?> <?= esc($order['from_floor']) ?></div>
                    <div class="route-detail"><?= esc($order['from_company']) ?></div>
                    <a href="tel:010-1234-5678" class="call-btn">
                        <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"/>
                        </svg>
                        발송자에게 전화
                    </a>
                </div>
                <div class="route-point">
                    <div class="route-label">도착지</div>
                    <div class="route-building"><?= esc($order['to_building']) ?> <?= esc($order['to_floor']) ?></div>
                    <div class="route-detail"><?= esc($order['to_company']) ?></div>
                    <a href="tel:010-9876-5432" class="call-btn">
                        <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"/>
                        </svg>
                        수신자에게 전화
                    </a>
                </div>
            </div>
        </div>
    </div>

    <div class="card">
        <div class="card-title">배송 정보</div>
        <div class="info-row">
            <div class="info-label">접수시간</div>
            <div class="info-value"><?= $order['created_at'] ?></div>
        </div>
        <div class="info-row">
            <div class="info-label">우선순위</div>
            <div class="info-value">
                <?php if ($order['priority'] === 'urgent'): ?>
                    <span class="urgent-badge">긴급</span>
                <?php else: ?>
                    일반
                <?php endif; ?>
            </div>
        </div>
        <div class="info-row">
            <div class="info-label">물품</div>
            <div class="info-value"><?= esc($order['item']) ?></div>
        </div>
        <?php if ($order['memo']): ?>
        <div class="info-row">
            <div class="info-label">메모</div>
            <div class="info-value"><?= esc($order['memo']) ?></div>
        </div>
        <?php endif; ?>
    </div>

    <div class="action-buttons">
        <?php if ($order['status'] === 'pending'): ?>
            <button class="btn btn-primary" onclick="handlePickup()">픽업 완료</button>
        <?php elseif ($order['status'] === 'picked'): ?>
            <button class="btn btn-success" onclick="handleComplete()">배송 완료</button>
        <?php else: ?>
            <button class="btn btn-secondary" disabled>배송 완료됨</button>
        <?php endif; ?>
    </div>

    <script>
        function handlePickup() {
            if (confirm('물품을 픽업하셨나요?')) {
                alert('픽업 완료 처리되었습니다.\n(데모 - 실제로는 API 호출)');
                // 실제 구현: API 호출 후 상태 변경
            }
        }

        function handleComplete() {
            if (confirm('배송을 완료하셨나요?')) {
                alert('배송 완료 처리되었습니다.\n(데모 - 실제로는 API 호출)');
                location.href = '/mailroom-driver';
            }
        }
    </script>
</body>
</html>