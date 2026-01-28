<!DOCTYPE html>
<html lang="ko">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>운송장 - <?= esc($order['order_no']) ?></title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        body {
            font-family: 'Malgun Gothic', sans-serif;
            font-size: 12px;
            background: #fff;
        }
        .label {
            width: 100mm;
            min-height: 70mm;
            border: 2px solid #000;
            padding: 4mm;
            margin: 0 auto;
        }
        .header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            border-bottom: 2px solid #000;
            padding-bottom: 3mm;
            margin-bottom: 3mm;
        }
        .header .logo {
            font-size: 16px;
            font-weight: bold;
        }
        .header .priority {
            padding: 2mm 4mm;
            border: 2px solid #000;
            font-weight: bold;
            font-size: 14px;
        }
        .header .priority.urgent {
            background: #000;
            color: #fff;
        }
        .order-no {
            text-align: center;
            font-size: 18px;
            font-weight: bold;
            padding: 2mm 0;
            border-bottom: 1px solid #000;
            margin-bottom: 3mm;
        }
        .section {
            margin-bottom: 3mm;
            padding-bottom: 3mm;
            border-bottom: 1px dashed #999;
        }
        .section:last-child {
            border-bottom: none;
            margin-bottom: 0;
        }
        .section-title {
            font-size: 10px;
            color: #666;
            margin-bottom: 1mm;
        }
        .section-content {
            font-size: 14px;
            font-weight: bold;
        }
        .section-sub {
            font-size: 11px;
            color: #333;
            margin-top: 1mm;
        }
        .row {
            display: flex;
            gap: 4mm;
        }
        .row .col {
            flex: 1;
        }
        .barcode-area {
            text-align: center;
            padding: 4mm 0;
            border-top: 2px solid #000;
            margin-top: 3mm;
        }
        .barcode {
            font-family: 'Libre Barcode 39', 'IDAutomationHC39M', monospace;
            font-size: 36px;
            letter-spacing: 0;
        }
        .barcode-text {
            font-family: monospace;
            font-size: 12px;
            margin-top: 2mm;
        }
        .item-info {
            background: #f5f5f5;
            padding: 2mm;
            border-radius: 1mm;
        }
        .datetime {
            font-size: 10px;
            text-align: right;
            color: #666;
            margin-top: 2mm;
        }
        @media print {
            body {
                margin: 0;
                padding: 0;
            }
            .label {
                border: 2px solid #000;
                margin: 0;
            }
            .no-print {
                display: none;
            }
            @page {
                size: 100mm 70mm;
                margin: 0;
            }
        }
        .no-print {
            text-align: center;
            padding: 10px;
            background: #f0f0f0;
            margin-bottom: 10px;
        }
        .no-print button {
            padding: 10px 30px;
            font-size: 14px;
            cursor: pointer;
            margin: 0 5px;
        }
    </style>
</head>
<body>
    <div class="no-print">
        <button onclick="window.print()">인쇄하기</button>
        <button onclick="window.close()">닫기</button>
    </div>

    <div class="label">
        <!-- 헤더 -->
        <div class="header">
            <div class="logo">MAILROOM</div>
            <?php if ($order['priority'] === 'urgent'): ?>
                <div class="priority urgent">긴급</div>
            <?php else: ?>
                <div class="priority">일반</div>
            <?php endif; ?>
        </div>

        <!-- 주문번호 -->
        <div class="order-no"><?= esc($order['order_no']) ?></div>

        <!-- 출발지 -->
        <div class="section">
            <div class="section-title">FROM 출발지</div>
            <div class="section-content">
                <?= esc($order['from_building']['building_name'] ?? '-') ?>
                <?php if (!empty($order['from_floor'])): ?>
                    <?= esc($order['from_floor']['floor_name']) ?>
                <?php endif; ?>
            </div>
            <?php if (!empty($order['from_company'])): ?>
                <div class="section-sub"><?= esc($order['from_company']) ?></div>
            <?php endif; ?>
            <?php if (!empty($order['from_contact_name'])): ?>
                <div class="section-sub"><?= esc($order['from_contact_name']) ?></div>
            <?php endif; ?>
        </div>

        <!-- 도착지 -->
        <div class="section">
            <div class="section-title">TO 도착지</div>
            <div class="section-content">
                <?= esc($order['to_building']['building_name'] ?? '-') ?>
                <?php if (!empty($order['to_floor'])): ?>
                    <?= esc($order['to_floor']['floor_name']) ?>
                <?php endif; ?>
            </div>
            <?php if (!empty($order['to_company'])): ?>
                <div class="section-sub"><?= esc($order['to_company']) ?></div>
            <?php endif; ?>
            <?php if (!empty($order['to_contact_name'])): ?>
                <div class="section-sub"><?= esc($order['to_contact_name']) ?></div>
            <?php endif; ?>
        </div>

        <!-- 물품정보 -->
        <div class="section">
            <div class="row">
                <div class="col">
                    <div class="section-title">물품</div>
                    <div class="item-info"><?= esc($order['item_description']) ?> (<?= esc($order['item_count'] ?? 1) ?>개)</div>
                </div>
            </div>
        </div>

        <!-- 바코드 -->
        <?php if (!empty($order['barcode'])): ?>
            <div class="barcode-area">
                <div class="barcode">*<?= esc($order['barcode']) ?>*</div>
                <div class="barcode-text"><?= esc($order['barcode']) ?></div>
            </div>
        <?php endif; ?>

        <!-- 접수시간 -->
        <div class="datetime">
            접수: <?= date('Y-m-d H:i', strtotime($order['created_at'])) ?>
        </div>
    </div>
</body>
</html>