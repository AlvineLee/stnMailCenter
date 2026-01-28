<?= $this->extend('layouts/header') ?>

<?= $this->section('content') ?>
<div class="stats-dashboard">
    <!-- 필터 영역 -->
    <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-4 mb-4">
        <form method="get" id="filter-form" class="flex flex-wrap items-center gap-4">
            <!-- 기간 유형 선택 -->
            <div class="flex items-center gap-2">
                <label class="text-sm font-medium text-gray-700">기간유형:</label>
                <div class="flex rounded-lg overflow-hidden border border-gray-300">
                    <?php foreach ($period_type_labels as $key => $label): ?>
                    <button type="button"
                            onclick="changePeriodType('<?= $key ?>')"
                            class="px-3 py-1.5 text-sm font-medium transition-colors <?= $period_type === $key ? 'bg-blue-600 text-white' : 'bg-white text-gray-700 hover:bg-gray-100' ?>">
                        <?= $label ?>
                    </button>
                    <?php endforeach; ?>
                </div>
                <input type="hidden" name="period_type" id="period_type" value="<?= esc($period_type) ?>">
            </div>

            <!-- 집계 날짜 선택 -->
            <div class="flex items-center gap-2">
                <label class="text-sm font-medium text-gray-700">📅 집계일:</label>
                <select name="period_start" id="period_start" onchange="this.form.submit()" class="border border-gray-300 rounded-lg px-3 py-1.5 text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500 font-medium bg-yellow-50">
                    <?php foreach ($available_periods as $period): ?>
                    <option value="<?= esc($period['period_start']) ?>" <?= $period_start === $period['period_start'] ? 'selected' : '' ?>>
                        <?= esc($period['period_label']) ?>
                    </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <!-- 퀵사 선택 (테이블 필터용) -->
            <div class="flex items-center gap-2">
                <label class="text-sm font-medium text-gray-700">퀵사:</label>
                <select name="cc_code" id="cc_code" class="border border-gray-300 rounded-lg px-3 py-1.5 text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                    <option value="all">전체</option>
                    <?php foreach ($cc_list as $cc): ?>
                    <option value="<?= esc($cc['cc_code']) ?>" <?= $cc_code_filter === $cc['cc_code'] ? 'selected' : '' ?>>
                        <?= esc(!empty($cc['api_name']) ? $cc['api_name'] : $cc['cc_code']) ?>
                    </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <button type="submit" class="px-4 py-1.5 bg-blue-600 text-white rounded-lg text-sm font-medium hover:bg-blue-700 transition-colors">
                조회
            </button>
        </form>
    </div>

    <!-- 요약 카드 -->
    <?php if (!empty($selected_period_stats)): ?>
    <?php $latestStat = $selected_period_stats; ?>
    <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-6">
        <!-- 총 주문 -->
        <div class="bg-gradient-to-br from-blue-500 to-blue-600 rounded-xl p-4 text-white shadow-lg">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-blue-100 text-sm">총 주문</p>
                    <p class="text-2xl font-bold mt-1"><?= number_format($latestStat['total_orders'] ?? 0) ?></p>
                </div>
                <div class="bg-white/20 rounded-full p-3">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"></path>
                    </svg>
                </div>
            </div>
            <p class="text-blue-100 text-xs mt-2"><?= esc($latestStat['period_label'] ?? '') ?></p>
        </div>

        <!-- 완료율 -->
        <div class="bg-gradient-to-br from-green-500 to-green-600 rounded-xl p-4 text-white shadow-lg">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-green-100 text-sm">완료율</p>
                    <p class="text-2xl font-bold mt-1"><?= number_format($latestStat['completion_rate'] ?? 0, 1) ?>%</p>
                </div>
                <div class="bg-white/20 rounded-full p-3">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                </div>
            </div>
            <p class="text-green-100 text-xs mt-2">완료: <?= number_format($latestStat['state_30_count'] ?? 0) ?>건</p>
        </div>

        <!-- 취소율 -->
        <div class="bg-gradient-to-br from-red-500 to-red-600 rounded-xl p-4 text-white shadow-lg">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-red-100 text-sm">취소율</p>
                    <p class="text-2xl font-bold mt-1"><?= number_format($latestStat['cancellation_rate'] ?? 0, 1) ?>%</p>
                </div>
                <div class="bg-white/20 rounded-full p-3">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                </div>
            </div>
            <p class="text-red-100 text-xs mt-2">취소: <?= number_format($latestStat['state_40_count'] ?? 0) ?>건</p>
        </div>

        <!-- 평균 배송시간 -->
        <div class="bg-gradient-to-br from-purple-500 to-purple-600 rounded-xl p-4 text-white shadow-lg">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-purple-100 text-sm">평균 배송시간</p>
                    <p class="text-2xl font-bold mt-1"><?= $latestStat['avg_delivery_time_min'] !== null ? number_format($latestStat['avg_delivery_time_min']) . '분' : '-' ?></p>
                </div>
                <div class="bg-white/20 rounded-full p-3">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                </div>
            </div>
            <p class="text-purple-100 text-xs mt-2">기사 수: <?= number_format($latestStat['unique_riders'] ?? 0) ?>명</p>
        </div>
    </div>
    <?php endif; ?>

    <!-- 차트 영역 - 콜센터별 비교 -->
    <?php if (!empty($top_call_centers)): ?>
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-6">
        <!-- 콜센터별 주문 건수 -->
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
            <h3 class="text-lg font-semibold text-gray-900 mb-4">📊 콜센터별 주문 건수 (<?= esc($period_type_label) ?>)</h3>
            <div class="h-72">
                <canvas id="ccOrdersChart"></canvas>
            </div>
        </div>

        <!-- 콜센터별 완료율 -->
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
            <h3 class="text-lg font-semibold text-gray-900 mb-4">✅ 콜센터별 완료율 (<?= esc($period_type_label) ?>)</h3>
            <div class="h-72">
                <canvas id="ccCompletionRateChart"></canvas>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <!-- 상태별 분포 & 기타 -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-6">
        <!-- 상태별 분포 -->
        <?php if (!empty($stats)): ?>
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
            <h3 class="text-lg font-semibold text-gray-900 mb-4">🎯 주문 상태 분포</h3>
            <div class="h-64">
                <canvas id="statusDistributionChart"></canvas>
            </div>
        </div>
        <?php endif; ?>

        <!-- 콜센터별 취소율 -->
        <?php if (!empty($top_call_centers)): ?>
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
            <h3 class="text-lg font-semibold text-gray-900 mb-4">❌ 콜센터별 취소율 (<?= esc($period_type_label) ?>)</h3>
            <div class="h-64">
                <canvas id="ccCancellationRateChart"></canvas>
            </div>
        </div>
        <?php endif; ?>
    </div>

    <!-- 시간대별 & 운송수단별 분포 -->
    <?php if (!empty($stats)): ?>
    <?php
        $latestStat = $stats[0];
        $hourlyData = json_decode($latestStat['hourly_distribution'] ?? '[]', true) ?: array_fill(0, 24, 0);
        $carTypeData = json_decode($latestStat['car_type_distribution'] ?? '{}', true) ?: [];
    ?>
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-6">
        <!-- 시간대별 주문 분포 -->
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
            <h3 class="text-lg font-semibold text-gray-900 mb-4">⏰ 시간대별 주문 분포</h3>
            <div class="h-64">
                <canvas id="hourlyChart"></canvas>
            </div>
        </div>

        <!-- 운송수단별 분포 -->
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
            <h3 class="text-lg font-semibold text-gray-900 mb-4">🚗 운송수단별 분포</h3>
            <div class="h-64">
                <canvas id="carTypeChart"></canvas>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <!-- 통계 테이블 -->
    <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
        <h3 class="text-lg font-semibold text-gray-900 mb-4"><?= esc($period_type_label) ?> 통계 상세</h3>
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead>
                    <tr class="bg-gray-50 border-b border-gray-200">
                        <th class="px-3 py-3 text-left font-medium text-gray-700">기간</th>
                        <th class="px-3 py-3 text-right font-medium text-gray-700">총 주문</th>
                        <th class="px-3 py-3 text-right font-medium text-gray-700">완료</th>
                        <th class="px-3 py-3 text-right font-medium text-gray-700">취소</th>
                        <th class="px-3 py-3 text-right font-medium text-gray-700">완료율</th>
                        <th class="px-3 py-3 text-right font-medium text-gray-700">배차시간</th>
                        <th class="px-3 py-3 text-right font-medium text-gray-700">픽업시간</th>
                        <th class="px-3 py-3 text-right font-medium text-gray-700">배송시간</th>
                        <th class="px-3 py-3 text-right font-medium text-gray-700">총거리</th>
                        <th class="px-3 py-3 text-right font-medium text-gray-700">총금액</th>
                        <th class="px-3 py-3 text-right font-medium text-gray-700">평균금액</th>
                        <th class="px-3 py-3 text-right font-medium text-gray-700">기사수</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    <?php if (empty($stats)): ?>
                    <tr>
                        <td colspan="12" class="px-4 py-8 text-center text-gray-500">데이터가 없습니다.</td>
                    </tr>
                    <?php else: ?>
                    <?php foreach ($stats as $stat): ?>
                    <tr class="hover:bg-gray-50">
                        <td class="px-3 py-3 font-medium text-gray-900 whitespace-nowrap"><?= esc($stat['period_label']) ?></td>
                        <td class="px-3 py-3 text-right text-gray-700"><?= number_format($stat['total_orders']) ?></td>
                        <td class="px-3 py-3 text-right text-green-600"><?= number_format($stat['state_30_count']) ?></td>
                        <td class="px-3 py-3 text-right text-red-600"><?= number_format($stat['state_40_count']) ?></td>
                        <td class="px-3 py-3 text-right">
                            <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium <?= $stat['completion_rate'] >= 80 ? 'bg-green-100 text-green-800' : ($stat['completion_rate'] >= 60 ? 'bg-yellow-100 text-yellow-800' : 'bg-red-100 text-red-800') ?>">
                                <?= number_format($stat['completion_rate'], 1) ?>%
                            </span>
                        </td>
                        <td class="px-3 py-3 text-right text-gray-700"><?= $stat['avg_dispatch_time_min'] !== null ? $stat['avg_dispatch_time_min'] . '분' : '-' ?></td>
                        <td class="px-3 py-3 text-right text-gray-700"><?= $stat['avg_pickup_time_min'] !== null ? $stat['avg_pickup_time_min'] . '분' : '-' ?></td>
                        <td class="px-3 py-3 text-right text-gray-700"><?= $stat['avg_delivery_time_min'] !== null ? $stat['avg_delivery_time_min'] . '분' : '-' ?></td>
                        <td class="px-3 py-3 text-right text-gray-700"><?= number_format($stat['total_distance_km'] ?? 0, 1) ?>km</td>
                        <td class="px-3 py-3 text-right text-blue-600 font-medium"><?= number_format($stat['total_price'] ?? 0) ?>원</td>
                        <td class="px-3 py-3 text-right text-gray-700"><?= number_format($stat['avg_price'] ?? 0) ?>원</td>
                        <td class="px-3 py-3 text-right text-gray-700"><?= number_format($stat['unique_riders']) ?>명</td>
                    </tr>
                    <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Chart.js CDN -->
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<script>
// 기간 유형 변경
function changePeriodType(type) {
    document.getElementById('period_type').value = type;
    document.getElementById('filter-form').submit();
}

// 공통 차트 설정
Chart.defaults.font.family = "'Roboto', sans-serif";
Chart.defaults.color = '#6B7280';

// 콜센터별 차트 데이터
<?php if (!empty($top_call_centers)): ?>
const ccLabels = <?= json_encode(array_map(function($c) {
    // api_name이 비어있으면 cc_code를 사용
    return !empty($c['api_name']) ? $c['api_name'] : ($c['cc_code'] ?? 'Unknown');
}, $top_call_centers)) ?>;
const ccTotalOrders = <?= json_encode(array_map(function($c) { return (int)$c['total_orders']; }, $top_call_centers)) ?>;
const ccCompletedOrders = <?= json_encode(array_map(function($c) { return (int)($c['state_30_count'] ?? 0); }, $top_call_centers)) ?>;
const ccCancelledOrders = <?= json_encode(array_map(function($c) { return (int)($c['state_40_count'] ?? 0); }, $top_call_centers)) ?>;
const ccCompletionRates = <?= json_encode(array_map(function($c) { return (float)($c['completion_rate'] ?? 0); }, $top_call_centers)) ?>;
const ccCancellationRates = <?= json_encode(array_map(function($c) { return (float)($c['cancellation_rate'] ?? 0); }, $top_call_centers)) ?>;

const chartColors = ['#3B82F6', '#10B981', '#F59E0B', '#EF4444', '#8B5CF6', '#EC4899', '#14B8A6', '#F97316', '#06B6D4', '#84CC16'];

// 콜센터별 주문 건수 차트 (수평 막대)
if (document.getElementById('ccOrdersChart')) {
    new Chart(document.getElementById('ccOrdersChart'), {
        type: 'bar',
        data: {
            labels: ccLabels,
            datasets: [{
                label: '주문 건수',
                data: ccTotalOrders,
                backgroundColor: chartColors.slice(0, ccLabels.length),
                borderRadius: 6
            }]
        },
        options: {
            indexAxis: 'y',
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: { display: false },
                tooltip: {
                    callbacks: {
                        label: (item) => `${item.raw.toLocaleString()}건`
                    }
                }
            },
            scales: {
                x: {
                    beginAtZero: true,
                    grid: { color: '#F3F4F6' },
                    ticks: { callback: v => v.toLocaleString() }
                },
                y: {
                    grid: { display: false }
                }
            }
        }
    });
}

// 콜센터별 완료율 차트 (수평 막대)
if (document.getElementById('ccCompletionRateChart')) {
    new Chart(document.getElementById('ccCompletionRateChart'), {
        type: 'bar',
        data: {
            labels: ccLabels,
            datasets: [{
                label: '완료율',
                data: ccCompletionRates,
                backgroundColor: ccCompletionRates.map(rate =>
                    rate >= 90 ? '#10B981' : rate >= 70 ? '#F59E0B' : '#EF4444'
                ),
                borderRadius: 6
            }]
        },
        options: {
            indexAxis: 'y',
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: { display: false },
                tooltip: {
                    callbacks: {
                        label: (item) => `${item.raw.toFixed(1)}%`
                    }
                }
            },
            scales: {
                x: {
                    beginAtZero: true,
                    max: 100,
                    grid: { color: '#F3F4F6' },
                    ticks: { callback: v => v + '%' }
                },
                y: {
                    grid: { display: false }
                }
            }
        }
    });
}

// 콜센터별 취소율 차트 (수평 막대)
if (document.getElementById('ccCancellationRateChart')) {
    new Chart(document.getElementById('ccCancellationRateChart'), {
        type: 'bar',
        data: {
            labels: ccLabels,
            datasets: [{
                label: '취소율',
                data: ccCancellationRates,
                backgroundColor: ccCancellationRates.map(rate =>
                    rate <= 5 ? '#10B981' : rate <= 10 ? '#F59E0B' : '#EF4444'
                ),
                borderRadius: 6
            }]
        },
        options: {
            indexAxis: 'y',
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: { display: false },
                tooltip: {
                    callbacks: {
                        label: (item) => `${item.raw.toFixed(1)}%`
                    }
                }
            },
            scales: {
                x: {
                    beginAtZero: true,
                    grid: { color: '#F3F4F6' },
                    ticks: { callback: v => v + '%' }
                },
                y: {
                    grid: { display: false }
                }
            }
        }
    });
}
<?php endif; ?>

// 상태별 분포 차트
<?php if (!empty($stats)): ?>
<?php
    $latestStat = $stats[0];
    $hourlyData = json_decode($latestStat['hourly_distribution'] ?? '[]', true) ?: array_fill(0, 24, 0);
    $carTypeData = json_decode($latestStat['car_type_distribution'] ?? '{}', true) ?: [];
?>
const statusData = {
    labels: ['대기(10)', '배차(11)', '픽업(12)', '이동(20)', '완료(30)', '취소(40)', '예약(50)', '임시(90)'],
    datasets: [{
        data: [
            <?= (int)($latestStat['state_10_count'] ?? 0) ?>,
            <?= (int)($latestStat['state_11_count'] ?? 0) ?>,
            <?= (int)($latestStat['state_12_count'] ?? 0) ?>,
            <?= (int)($latestStat['state_20_count'] ?? 0) ?>,
            <?= (int)($latestStat['state_30_count'] ?? 0) ?>,
            <?= (int)($latestStat['state_40_count'] ?? 0) ?>,
            <?= (int)($latestStat['state_50_count'] ?? 0) ?>,
            <?= (int)($latestStat['state_90_count'] ?? 0) ?>
        ],
        backgroundColor: [
            '#9CA3AF', '#60A5FA', '#34D399', '#FBBF24', '#10B981', '#EF4444', '#A78BFA', '#6B7280'
        ]
    }]
};

if (document.getElementById('statusDistributionChart')) {
    new Chart(document.getElementById('statusDistributionChart'), {
        type: 'doughnut',
        data: statusData,
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    position: 'right',
                    labels: { usePointStyle: true, padding: 15, font: { size: 11 } }
                }
            },
            cutout: '60%'
        }
    });
}

// 시간대별 주문 분포 차트
const hourlyLabels = Array.from({length: 24}, (_, i) => `${i}시`);
const hourlyValues = <?= json_encode(array_values($hourlyData)) ?>;

if (document.getElementById('hourlyChart')) {
    new Chart(document.getElementById('hourlyChart'), {
        type: 'bar',
        data: {
            labels: hourlyLabels,
            datasets: [{
                label: '주문 건수',
                data: hourlyValues,
                backgroundColor: 'rgba(99, 102, 241, 0.7)',
                borderColor: '#6366F1',
                borderWidth: 1,
                borderRadius: 4
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: { display: false },
                tooltip: {
                    callbacks: {
                        title: (items) => `${items[0].label}`,
                        label: (item) => `${item.raw}건`
                    }
                }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    grid: { color: '#F3F4F6' },
                    ticks: { stepSize: Math.ceil(Math.max(...hourlyValues) / 5) || 1 }
                },
                x: {
                    grid: { display: false },
                    ticks: {
                        maxRotation: 0,
                        callback: function(val, idx) {
                            return idx % 3 === 0 ? this.getLabelForValue(val) : '';
                        }
                    }
                }
            }
        }
    });
}

// 운송수단별 분포 차트
const carTypeLabels = <?= json_encode(array_keys($carTypeData)) ?>;
const carTypeValues = <?= json_encode(array_values($carTypeData)) ?>;
const carTypeColors = ['#F59E0B', '#3B82F6', '#10B981', '#EF4444', '#8B5CF6', '#EC4899', '#14B8A6', '#F97316'];

if (document.getElementById('carTypeChart') && carTypeLabels.length > 0) {
    new Chart(document.getElementById('carTypeChart'), {
        type: 'pie',
        data: {
            labels: carTypeLabels,
            datasets: [{
                data: carTypeValues,
                backgroundColor: carTypeColors.slice(0, carTypeLabels.length),
                borderWidth: 2,
                borderColor: '#fff'
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    position: 'right',
                    labels: {
                        usePointStyle: true,
                        padding: 15,
                        font: { size: 12 },
                        generateLabels: function(chart) {
                            const data = chart.data;
                            const total = data.datasets[0].data.reduce((a, b) => a + b, 0);
                            return data.labels.map((label, i) => {
                                const value = data.datasets[0].data[i];
                                const pct = total > 0 ? ((value / total) * 100).toFixed(1) : 0;
                                return {
                                    text: `${label} (${pct}%)`,
                                    fillStyle: data.datasets[0].backgroundColor[i],
                                    hidden: false,
                                    index: i
                                };
                            });
                        }
                    }
                },
                tooltip: {
                    callbacks: {
                        label: function(item) {
                            const total = item.dataset.data.reduce((a, b) => a + b, 0);
                            const pct = total > 0 ? ((item.raw / total) * 100).toFixed(1) : 0;
                            return `${item.label}: ${item.raw}건 (${pct}%)`;
                        }
                    }
                }
            }
        }
    });
}
<?php endif; ?>
</script>

<style>
.stats-dashboard {
    max-width: 100%;
}
</style>
<?= $this->endSection() ?>