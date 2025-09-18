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
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">주문번호</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">서비스</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">회사명</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">출발지</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">도착지</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">상태</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">접수일</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            <?php foreach ($orders as $order): ?>
                            <tr class="hover:bg-gray-50">
                                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                                    #<?= $order['id'] ?>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                    <?= $order['service_name'] ?? '일반주문' ?>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                    <?= $order['company_name'] ?>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                    <?= $order['departure_address'] ?>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                    <?= $order['destination_address'] ?>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <?php
                                    $statusClass = '';
                                    $statusText = '';
                                    switch ($order['status']) {
                                        case 'pending':
                                            $statusClass = 'bg-yellow-100 text-yellow-800';
                                            $statusText = '대기중';
                                            break;
                                        case 'processing':
                                            $statusClass = 'bg-blue-100 text-blue-800';
                                            $statusText = '처리중';
                                            break;
                                        case 'completed':
                                            $statusClass = 'bg-green-100 text-green-800';
                                            $statusText = '완료';
                                            break;
                                        case 'cancelled':
                                            $statusClass = 'bg-red-100 text-red-800';
                                            $statusText = '취소';
                                            break;
                                        default:
                                            $statusClass = 'bg-gray-100 text-gray-800';
                                            $statusText = '알 수 없음';
                                    }
                                    ?>
                                    <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full <?= $statusClass ?>">
                                        <?= $statusText ?>
                                    </span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                    <?= date('Y-m-d H:i', strtotime($order['created_at'])) ?>
                                </td>
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
