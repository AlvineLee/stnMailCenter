<!DOCTYPE html>
<html lang="ko">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>기사 등록 - <?= esc($building['building_name'] ?? '메일룸') ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Noto Sans KR', sans-serif;
        }
    </style>
</head>
<body class="bg-gray-100 min-h-screen flex items-center justify-center p-4">
    <div class="w-full max-w-md">
        <?php if (isset($success) && $success): ?>
            <!-- 등록 완료 화면 -->
            <div class="bg-white rounded-2xl shadow-lg p-8 text-center">
                <div class="w-16 h-16 bg-green-100 rounded-full flex items-center justify-center mx-auto mb-4">
                    <svg class="w-8 h-8 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                    </svg>
                </div>
                <h1 class="text-xl font-bold text-gray-800 mb-2">등록 완료</h1>
                <p class="text-gray-600 mb-4">등록 신청이 완료되었습니다.<br>관리자 승인 후 앱에서 로그인하실 수 있습니다.</p>
                <div class="bg-blue-50 rounded-lg p-4 text-left">
                    <div class="text-sm text-gray-500 mb-1">기사 코드</div>
                    <div class="text-2xl font-bold text-blue-600 font-mono"><?= esc($driver_code ?? '-') ?></div>
                    <p class="text-xs text-gray-500 mt-2">이 코드는 앱 로그인 시 사용됩니다.</p>
                </div>
            </div>
        <?php elseif (isset($error)): ?>
            <!-- 에러 화면 -->
            <div class="bg-white rounded-2xl shadow-lg p-8 text-center">
                <div class="w-16 h-16 bg-red-100 rounded-full flex items-center justify-center mx-auto mb-4">
                    <svg class="w-8 h-8 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                    </svg>
                </div>
                <h1 class="text-xl font-bold text-gray-800 mb-2">오류</h1>
                <p class="text-gray-600"><?= esc($error) ?></p>
            </div>
        <?php else: ?>
            <!-- 등록 폼 -->
            <div class="bg-white rounded-2xl shadow-lg overflow-hidden">
                <div class="bg-blue-600 p-6 text-white text-center">
                    <h1 class="text-xl font-bold"><?= esc($building['building_name'] ?? '메일룸') ?></h1>
                    <p class="text-blue-100 text-sm mt-1">기사 등록</p>
                </div>

                <form action="/mailroom/driver-register/<?= $building['id'] ?>" method="post" class="p-6 space-y-4">
                    <?= csrf_field() ?>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">이름 <span class="text-red-500">*</span></label>
                        <input type="text" name="driver_name" required autocomplete="name"
                               class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-400 text-lg"
                               placeholder="홍길동">
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">연락처 <span class="text-red-500">*</span></label>
                        <input type="tel" name="phone" required autocomplete="tel"
                               class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-400 text-lg"
                               placeholder="010-1234-5678">
                    </div>

                    <div class="pt-4">
                        <button type="submit"
                                class="w-full py-4 bg-blue-600 text-white text-lg font-bold rounded-lg hover:bg-blue-700 transition">
                            등록 신청
                        </button>
                    </div>

                    <p class="text-xs text-gray-500 text-center">
                        등록 신청 후 관리자 승인이 필요합니다.<br>
                        승인 완료 시 앱에서 기사 코드로 로그인하실 수 있습니다.
                    </p>
                </form>
            </div>
        <?php endif; ?>
    </div>
</body>
</html>