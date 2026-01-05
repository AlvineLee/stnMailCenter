<?= $this->include('layouts/header') ?>

<div class="bg-gradient-to-br from-blue-50 to-indigo-100 min-h-screen">
    <div class="w-full px-4">
        <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
            <h2 class="text-xl font-semibold text-gray-800 mb-4">주문 목록</h2>
            
            <?php if (empty($orders)): ?>
                <div class="text-center py-8">
                    <p class="text-gray-500">접수된 주문이 없습니다.</p>
                </div>
            <?php else: ?>
                <div>
                    <table>
                        <thead>
                            <tr>
                                <th>주문번호</th>
                                <th>서비스</th>
                                <th>회사명</th>
                                <th>출발지</th>
                                <th>도착지</th>
                                <th>상태</th>
                                <th>접수일</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($orders as $order): ?>
                            <tr>
                                <td>#<?= $order['id'] ?></td>
                                <td><?= $order['service_name'] ?? '일반주문' ?></td>
                                <td><?= $order['company_name'] ?></td>
                                <td><?= $order['departure_address'] ?></td>
                                <td><?= $order['destination_address'] ?></td>
                                <td>
                                    <?php
                                    $statusStyle = '';
                                    $statusText = '';
                                    switch ($order['status']) {
                                        case 'pending':
                                            $statusStyle = 'background: #fef3c7; color: #92400e';
                                            $statusText = '대기중';
                                            break;
                                        case 'processing':
                                            $statusStyle = 'background: #dbeafe; color: #1e40af';
                                            $statusText = '처리중';
                                            break;
                                        case 'completed':
                                            $statusStyle = 'background: #dcfce7; color: #166534';
                                            $statusText = '완료';
                                            break;
                                        case 'cancelled':
                                            $statusStyle = 'background: #fee2e2; color: #dc2626'; // 연한 빨간색 (예약보다 약간 더 연함)
                                            $statusText = '취소';
                                            break;
                                        default:
                                            $statusStyle = 'background: #f3f4f6; color: #374151';
                                            $statusText = '알 수 없음';
                                    }
                                    ?>
                                    <span class="status-badge" style="<?= $statusStyle ?>">
                                        <?= $statusText ?>
                                    </span>
                                </td>
                                <td><?= date('Y-m-d H:i', strtotime($order['created_at'])) ?></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<?= $this->include('layouts/footer') ?>
