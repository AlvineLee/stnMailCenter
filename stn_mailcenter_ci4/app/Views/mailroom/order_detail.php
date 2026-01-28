<?= $this->extend('layouts/header') ?>

<?= $this->section('content') ?>
<div class="w-full flex flex-col p-4 max-w-4xl mx-auto">
    <!-- 헤더 -->
    <div class="mb-4">
        <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-3">
            <div class="flex items-center gap-3">
                <a href="/mailroom" class="text-gray-500 hover:text-gray-700">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
                    </svg>
                </a>
                <div>
                    <h1 class="text-xl font-bold text-gray-800">주문 상세</h1>
                    <p class="text-sm text-gray-500"><?= esc($order['order_no']) ?></p>
                </div>
            </div>
            <div class="flex gap-2">
                <a href="/mailroom/print/<?= $order['id'] ?>" target="_blank"
                   class="px-4 py-2 bg-gray-100 text-gray-700 text-sm font-semibold rounded-lg hover:bg-gray-200 transition">
                    운송장 출력
                </a>
            </div>
        </div>
    </div>

    <?php if (session()->getFlashdata('message')): ?>
        <div class="mb-4 p-3 bg-green-100 border border-green-300 text-green-700 rounded-lg text-sm">
            <?= session()->getFlashdata('message') ?>
        </div>
    <?php endif; ?>
    <?php if (session()->getFlashdata('error')): ?>
        <div class="mb-4 p-3 bg-red-100 border border-red-300 text-red-700 rounded-lg text-sm">
            <?= session()->getFlashdata('error') ?>
        </div>
    <?php endif; ?>

    <!-- 상태 및 우선순위 -->
    <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-4 mb-4">
        <div class="flex flex-wrap items-center gap-4">
            <div>
                <span class="text-xs text-gray-500">상태</span>
                <?php
                $statusColors = [
                    'pending' => 'bg-yellow-100 text-yellow-700 border-yellow-300',
                    'confirmed' => 'bg-blue-100 text-blue-700 border-blue-300',
                    'picked' => 'bg-indigo-100 text-indigo-700 border-indigo-300',
                    'delivered' => 'bg-green-100 text-green-700 border-green-300',
                    'cancelled' => 'bg-red-100 text-red-700 border-red-300'
                ];
                $statusText = [
                    'pending' => '접수',
                    'confirmed' => '확인',
                    'picked' => '픽업',
                    'delivered' => '완료',
                    'cancelled' => '취소'
                ];
                $color = $statusColors[$order['status']] ?? 'bg-gray-100 text-gray-700 border-gray-300';
                $text = $statusText[$order['status']] ?? $order['status'];
                ?>
                <div class="mt-1 px-3 py-1 <?= $color ?> border text-sm font-semibold rounded-full inline-block">
                    <?= $text ?>
                </div>
            </div>
            <div>
                <span class="text-xs text-gray-500">배송유형</span>
                <?php $deliveryType = $order['delivery_type'] ?? 'internal'; ?>
                <?php if ($deliveryType === 'external'): ?>
                    <div class="mt-1 px-3 py-1 bg-orange-100 text-orange-700 border border-orange-300 text-sm font-semibold rounded-full inline-block">
                        외부배송
                    </div>
                <?php else: ?>
                    <div class="mt-1 px-3 py-1 bg-teal-100 text-teal-700 border border-teal-300 text-sm font-semibold rounded-full inline-block">
                        내부배송
                    </div>
                <?php endif; ?>
            </div>
            <div>
                <span class="text-xs text-gray-500">우선순위</span>
                <?php if ($order['priority'] === 'urgent'): ?>
                    <div class="mt-1 px-3 py-1 bg-red-100 text-red-700 border border-red-300 text-sm font-semibold rounded-full inline-block">
                        긴급
                    </div>
                <?php else: ?>
                    <div class="mt-1 px-3 py-1 bg-gray-100 text-gray-600 border border-gray-300 text-sm rounded-full inline-block">
                        일반
                    </div>
                <?php endif; ?>
            </div>
            <?php if ($deliveryType === 'external'): ?>
            <div>
                <span class="text-xs text-gray-500">인성연동</span>
                <?php if (($order['insung_sync_status'] ?? 'none') === 'synced'): ?>
                    <div class="mt-1 px-3 py-1 bg-green-100 text-green-700 border border-green-300 text-sm font-semibold rounded-full inline-block">
                        연동완료
                    </div>
                <?php elseif (($order['insung_sync_status'] ?? 'none') === 'pending'): ?>
                    <div class="mt-1 px-3 py-1 bg-yellow-100 text-yellow-700 border border-yellow-300 text-sm font-semibold rounded-full inline-block">
                        연동대기
                    </div>
                <?php elseif (($order['insung_sync_status'] ?? 'none') === 'failed'): ?>
                    <div class="mt-1 px-3 py-1 bg-red-100 text-red-700 border border-red-300 text-sm font-semibold rounded-full inline-block">
                        실패
                    </div>
                <?php else: ?>
                    <div class="mt-1 px-3 py-1 bg-gray-100 text-gray-500 border border-gray-300 text-sm rounded-full inline-block">
                        미연동
                    </div>
                <?php endif; ?>
            </div>
            <?php endif; ?>
            <div class="ml-auto text-right">
                <span class="text-xs text-gray-500">접수시간</span>
                <div class="mt-1 text-sm text-gray-700"><?= date('Y-m-d H:i', strtotime($order['created_at'])) ?></div>
            </div>
        </div>
        <?php if (!empty($order['handler_type'])): ?>
        <div class="mt-3 pt-3 border-t border-gray-100 text-sm">
            <span class="text-gray-500">처리방식:</span>
            <?php
            $handlerTypeText = [
                'mailroom_staff' => '담당자 직접처리',
                'internal_driver' => '내부 기사',
                'external_driver' => '외부 기사 (인성)'
            ];
            ?>
            <span class="font-medium"><?= $handlerTypeText[$order['handler_type']] ?? $order['handler_type'] ?></span>
            <?php if (!empty($order['handler_memo'])): ?>
                <span class="text-gray-400 ml-2">(<?= esc($order['handler_memo']) ?>)</span>
            <?php endif; ?>
        </div>
        <?php endif; ?>
    </div>

    <!-- 출발지 / 도착지 -->
    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 mb-4">
        <!-- 출발지 -->
        <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-4">
            <h3 class="text-sm font-semibold text-blue-600 mb-3 flex items-center gap-2">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/>
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/>
                </svg>
                출발지
            </h3>
            <div class="space-y-2 text-sm">
                <div>
                    <span class="text-gray-500">건물:</span>
                    <span class="font-medium"><?= esc($order['from_building']['building_name'] ?? '-') ?></span>
                    <?php if (!empty($order['from_floor'])): ?>
                        <span class="text-gray-600"><?= esc($order['from_floor']['floor_name']) ?></span>
                    <?php endif; ?>
                </div>
                <?php if (!empty($order['from_company'])): ?>
                    <div>
                        <span class="text-gray-500">회사:</span>
                        <span class="font-medium"><?= esc($order['from_company']) ?></span>
                    </div>
                <?php endif; ?>
                <?php if (!empty($order['from_contact_name'])): ?>
                    <div>
                        <span class="text-gray-500">담당자:</span>
                        <span class="font-medium"><?= esc($order['from_contact_name']) ?></span>
                    </div>
                <?php endif; ?>
                <?php if (!empty($order['from_contact_phone'])): ?>
                    <div>
                        <span class="text-gray-500">연락처:</span>
                        <span class="font-medium"><?= esc($order['from_contact_phone']) ?></span>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- 도착지 -->
        <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-4">
            <h3 class="text-sm font-semibold text-green-600 mb-3 flex items-center gap-2">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/>
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/>
                </svg>
                도착지
            </h3>
            <div class="space-y-2 text-sm">
                <div>
                    <span class="text-gray-500">건물:</span>
                    <span class="font-medium"><?= esc($order['to_building']['building_name'] ?? '-') ?></span>
                    <?php if (!empty($order['to_floor'])): ?>
                        <span class="text-gray-600"><?= esc($order['to_floor']['floor_name']) ?></span>
                    <?php endif; ?>
                </div>
                <?php if (!empty($order['to_company'])): ?>
                    <div>
                        <span class="text-gray-500">회사:</span>
                        <span class="font-medium"><?= esc($order['to_company']) ?></span>
                    </div>
                <?php endif; ?>
                <?php if (!empty($order['to_contact_name'])): ?>
                    <div>
                        <span class="text-gray-500">담당자:</span>
                        <span class="font-medium"><?= esc($order['to_contact_name']) ?></span>
                    </div>
                <?php endif; ?>
                <?php if (!empty($order['to_contact_phone'])): ?>
                    <div>
                        <span class="text-gray-500">연락처:</span>
                        <span class="font-medium"><?= esc($order['to_contact_phone']) ?></span>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- 물품 정보 -->
    <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-4 mb-4">
        <h3 class="text-sm font-semibold text-orange-600 mb-3 flex items-center gap-2">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/>
            </svg>
            물품 정보
        </h3>
        <div class="grid grid-cols-2 gap-4 text-sm">
            <div>
                <span class="text-gray-500">물품:</span>
                <span class="font-medium"><?= esc($order['item_description']) ?></span>
            </div>
            <div>
                <span class="text-gray-500">수량:</span>
                <span class="font-medium"><?= esc($order['item_count'] ?? 1) ?>개</span>
            </div>
            <?php if (!empty($order['memo'])): ?>
                <div class="col-span-2">
                    <span class="text-gray-500">메모:</span>
                    <span class="font-medium"><?= esc($order['memo']) ?></span>
                </div>
            <?php endif; ?>
            <?php if (!empty($order['barcode'])): ?>
                <div class="col-span-2">
                    <span class="text-gray-500">바코드:</span>
                    <span class="font-mono font-medium"><?= esc($order['barcode']) ?></span>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- 기사 배정 / 직접 처리 -->
    <?php if (in_array($order['status'], ['pending', 'confirmed'])): ?>
        <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-4 mb-4">
            <h3 class="text-sm font-semibold text-purple-600 mb-3 flex items-center gap-2">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                </svg>
                배송 처리
            </h3>

            <!-- 탭 버튼 -->
            <div class="flex gap-2 mb-4 border-b border-gray-200">
                <button type="button" onclick="showTab('assign')"
                        id="tab-assign"
                        class="px-4 py-2 text-sm font-medium border-b-2 border-purple-600 text-purple-600">
                    기사 배정
                </button>
                <button type="button" onclick="showTab('direct')"
                        id="tab-direct"
                        class="px-4 py-2 text-sm font-medium border-b-2 border-transparent text-gray-500 hover:text-gray-700">
                    직접 처리
                </button>
            </div>

            <!-- 기사 배정 폼 -->
            <div id="panel-assign">
                <form action="/mailroom/assign/<?= $order['id'] ?>" method="post" class="space-y-3">
                    <?= csrf_field() ?>
                    <div class="flex gap-3">
                        <select name="driver_id" required
                                class="flex-1 px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-purple-400">
                            <option value="">기사 선택</option>
                            <?php foreach ($drivers as $driver): ?>
                                <option value="<?= $driver['id'] ?>" <?= ($order['assigned_driver_id'] ?? '') == $driver['id'] ? 'selected' : '' ?>>
                                    <?= esc($driver['driver_name']) ?> (<?= esc($driver['driver_code']) ?>)
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <select name="handler_type" class="px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-purple-400">
                            <option value="internal_driver">내부 기사</option>
                            <?php if (($order['delivery_type'] ?? 'internal') === 'external'): ?>
                            <option value="external_driver">외부 기사 (인성)</option>
                            <?php endif; ?>
                        </select>
                    </div>
                    <button type="submit" class="w-full px-4 py-2 bg-purple-600 text-white font-semibold rounded-md hover:bg-purple-700 transition">
                        기사 배정
                    </button>
                </form>
            </div>

            <!-- 직접 처리 폼 -->
            <div id="panel-direct" class="hidden">
                <form action="/mailroom/handle-directly/<?= $order['id'] ?>" method="post" class="space-y-3">
                    <?= csrf_field() ?>
                    <div>
                        <label class="block text-xs text-gray-500 mb-1">처리 메모 (선택)</label>
                        <input type="text" name="handler_memo" placeholder="예: 직접 전달 완료, 우편함 투입 등"
                               class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-teal-400">
                    </div>
                    <button type="submit" onclick="return confirm('직접 처리로 완료 처리하시겠습니까?')"
                            class="w-full px-4 py-2 bg-teal-600 text-white font-semibold rounded-md hover:bg-teal-700 transition">
                        직접 처리 완료
                    </button>
                    <p class="text-xs text-gray-500">* 직접 처리 시 기사 배정 없이 바로 배송 완료 처리됩니다.</p>
                </form>
            </div>
        </div>
        <script>
        function showTab(tabName) {
            // 탭 버튼 스타일
            document.getElementById('tab-assign').className = tabName === 'assign'
                ? 'px-4 py-2 text-sm font-medium border-b-2 border-purple-600 text-purple-600'
                : 'px-4 py-2 text-sm font-medium border-b-2 border-transparent text-gray-500 hover:text-gray-700';
            document.getElementById('tab-direct').className = tabName === 'direct'
                ? 'px-4 py-2 text-sm font-medium border-b-2 border-teal-600 text-teal-600'
                : 'px-4 py-2 text-sm font-medium border-b-2 border-transparent text-gray-500 hover:text-gray-700';
            // 패널 표시
            document.getElementById('panel-assign').className = tabName === 'assign' ? '' : 'hidden';
            document.getElementById('panel-direct').className = tabName === 'direct' ? '' : 'hidden';
        }
        </script>
    <?php elseif (!empty($order['assigned_driver_id'])): ?>
        <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-4 mb-4">
            <h3 class="text-sm font-semibold text-purple-600 mb-3 flex items-center gap-2">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                </svg>
                배정된 기사
            </h3>
            <div class="text-sm">
                <?php
                $assignedDriver = null;
                foreach ($drivers as $driver) {
                    if ($driver['id'] == $order['assigned_driver_id']) {
                        $assignedDriver = $driver;
                        break;
                    }
                }
                ?>
                <?php if ($assignedDriver): ?>
                    <span class="font-medium"><?= esc($assignedDriver['driver_name']) ?></span>
                    <span class="text-gray-500">(<?= esc($assignedDriver['driver_code']) ?>)</span>
                <?php else: ?>
                    <span class="text-gray-500">-</span>
                <?php endif; ?>
            </div>
        </div>
    <?php endif; ?>

    <!-- 배송 타임라인 -->
    <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-4 mb-4">
        <h3 class="text-sm font-semibold text-gray-700 mb-3">배송 진행</h3>
        <div class="relative">
            <div class="absolute left-2 top-0 bottom-0 w-0.5 bg-gray-200"></div>
            <div class="space-y-4">
                <!-- 접수 -->
                <div class="flex items-start gap-3 relative">
                    <div class="w-4 h-4 rounded-full bg-blue-600 border-2 border-white shadow z-10"></div>
                    <div class="flex-1">
                        <div class="text-sm font-medium">접수</div>
                        <div class="text-xs text-gray-500"><?= date('Y-m-d H:i', strtotime($order['created_at'])) ?></div>
                    </div>
                </div>
                <!-- 확인 -->
                <div class="flex items-start gap-3 relative">
                    <?php if (!empty($order['confirmed_at'])): ?>
                        <div class="w-4 h-4 rounded-full bg-blue-600 border-2 border-white shadow z-10"></div>
                    <?php else: ?>
                        <div class="w-4 h-4 rounded-full bg-gray-300 border-2 border-white shadow z-10"></div>
                    <?php endif; ?>
                    <div class="flex-1">
                        <div class="text-sm font-medium <?= empty($order['confirmed_at']) ? 'text-gray-400' : '' ?>">확인</div>
                        <?php if (!empty($order['confirmed_at'])): ?>
                            <div class="text-xs text-gray-500"><?= date('Y-m-d H:i', strtotime($order['confirmed_at'])) ?></div>
                        <?php endif; ?>
                    </div>
                </div>
                <!-- 픽업 -->
                <div class="flex items-start gap-3 relative">
                    <?php if (!empty($order['picked_at'])): ?>
                        <div class="w-4 h-4 rounded-full bg-blue-600 border-2 border-white shadow z-10"></div>
                    <?php else: ?>
                        <div class="w-4 h-4 rounded-full bg-gray-300 border-2 border-white shadow z-10"></div>
                    <?php endif; ?>
                    <div class="flex-1">
                        <div class="text-sm font-medium <?= empty($order['picked_at']) ? 'text-gray-400' : '' ?>">픽업</div>
                        <?php if (!empty($order['picked_at'])): ?>
                            <div class="text-xs text-gray-500"><?= date('Y-m-d H:i', strtotime($order['picked_at'])) ?></div>
                        <?php endif; ?>
                    </div>
                </div>
                <!-- 완료 -->
                <div class="flex items-start gap-3 relative">
                    <?php if (!empty($order['delivered_at'])): ?>
                        <div class="w-4 h-4 rounded-full bg-green-600 border-2 border-white shadow z-10"></div>
                    <?php else: ?>
                        <div class="w-4 h-4 rounded-full bg-gray-300 border-2 border-white shadow z-10"></div>
                    <?php endif; ?>
                    <div class="flex-1">
                        <div class="text-sm font-medium <?= empty($order['delivered_at']) ? 'text-gray-400' : '' ?>">완료</div>
                        <?php if (!empty($order['delivered_at'])): ?>
                            <div class="text-xs text-gray-500"><?= date('Y-m-d H:i', strtotime($order['delivered_at'])) ?></div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- 취소 버튼 (pending/confirmed 상태에서만) -->
    <?php if (in_array($order['status'], ['pending', 'confirmed'])): ?>
        <div class="flex gap-3">
            <button onclick="if(confirm('주문을 취소하시겠습니까?')) location.href='/mailroom/cancel/<?= $order['id'] ?>'"
                    class="flex-1 py-3 bg-red-100 text-red-700 font-semibold rounded-lg hover:bg-red-200 transition">
                주문 취소
            </button>
        </div>
    <?php endif; ?>
</div>
<?= $this->endSection() ?>