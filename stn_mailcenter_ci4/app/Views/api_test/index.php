<?= $this->extend('layouts/header') ?>

<?= $this->section('content') ?>
<div class="container mx-auto px-4 py-8">
    <h1 class="text-3xl font-bold text-gray-800 mb-8">API 테스트</h1>

    <!-- IP 정보 섹션 -->
    <div class="bg-white rounded-lg shadow-md p-6 mb-6">
        <h2 class="text-xl font-semibold text-gray-700 mb-4">IP 정보</h2>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div>
                <label class="block text-sm font-medium text-gray-600">현재 IP</label>
                <p class="text-lg font-mono bg-gray-100 p-2 rounded"><?= $current_ip ?></p>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-600">WhiteList IP</label>
                <p class="text-lg font-mono bg-gray-100 p-2 rounded"><?= $whitelist_ip ?></p>
            </div>
        </div>
        
        <div class="mt-4">
            <button id="refreshIpInfo" class="bg-blue-500 hover:bg-blue-600 text-white px-4 py-2 rounded">
                IP 정보 새로고침
            </button>
        </div>
    </div>

    <!-- API 테스트 섹션 -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        <!-- 일양 API 테스트 -->
        <div class="bg-white rounded-lg shadow-md p-6">
            <h2 class="text-xl font-semibold text-gray-700 mb-4">일양 API 테스트</h2>
            <div class="space-y-4">
                <button id="testIlyangApi" class="w-full bg-green-500 hover:bg-green-600 text-white px-4 py-2 rounded">
                    일양 API 테스트 실행
                </button>
                <button id="getApiSpec" class="w-full bg-blue-500 hover:bg-blue-600 text-white px-4 py-2 rounded">
                    API 명세 조회
                </button>
                <button id="generateSample" class="w-full bg-purple-500 hover:bg-purple-600 text-white px-4 py-2 rounded">
                    샘플 데이터 생성
                </button>
            </div>
            <div id="ilyangResult" class="mt-4 p-4 bg-gray-100 rounded hidden">
                <h3 class="font-semibold mb-2">테스트 결과:</h3>
                <pre id="ilyangResultContent" class="text-sm overflow-auto"></pre>
            </div>
        </div>

        <!-- IP 테스트 -->
        <div class="bg-white rounded-lg shadow-md p-6">
            <h2 class="text-xl font-semibold text-gray-700 mb-4">IP 정보 테스트</h2>
            <div class="space-y-4">
                <button id="testIpInfo" class="w-full bg-orange-500 hover:bg-orange-600 text-white px-4 py-2 rounded">
                    IP 정보 상세 조회
                </button>
            </div>
            <div id="ipResult" class="mt-4 p-4 bg-gray-100 rounded hidden">
                <h3 class="font-semibold mb-2">IP 정보:</h3>
                <pre id="ipResultContent" class="text-sm overflow-auto"></pre>
            </div>
        </div>
    </div>

    <!-- 로그 섹션 -->
    <div class="bg-white rounded-lg shadow-md p-6 mt-6">
        <h2 class="text-xl font-semibold text-gray-700 mb-4">실시간 로그</h2>
        <div id="logContainer" class="bg-black text-green-400 p-4 rounded h-64 overflow-auto font-mono text-sm">
            <div>로그가 여기에 표시됩니다...</div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // 일양 API 테스트
    document.getElementById('testIlyangApi').addEventListener('click', function() {
        const resultDiv = document.getElementById('ilyangResult');
        const resultContent = document.getElementById('ilyangResultContent');
        
        resultDiv.classList.remove('hidden');
        resultContent.textContent = '테스트 실행 중...';
        
        fetch('/api-test/ilyang')
            .then(response => response.json())
            .then(data => {
                resultContent.textContent = JSON.stringify(data, null, 2);
                addLog('일양 API 테스트: ' + (data.success ? '성공' : '실패'));
            })
            .catch(error => {
                resultContent.textContent = '에러: ' + error.message;
                addLog('일양 API 테스트 에러: ' + error.message);
            });
    });

    // API 명세 조회
    document.getElementById('getApiSpec').addEventListener('click', function() {
        const resultDiv = document.getElementById('ilyangResult');
        const resultContent = document.getElementById('ilyangResultContent');
        
        resultDiv.classList.remove('hidden');
        resultContent.textContent = '로딩 중...';
        
        fetch('/api-test/spec')
            .then(response => response.json())
            .then(data => {
                resultContent.textContent = JSON.stringify(data, null, 2);
                addLog('API 명세 조회 완료');
            })
            .catch(error => {
                resultContent.textContent = '에러: ' + error.message;
                addLog('API 명세 조회 에러: ' + error.message);
            });
    });

    // 샘플 데이터 생성
    document.getElementById('generateSample').addEventListener('click', function() {
        const resultDiv = document.getElementById('ilyangResult');
        const resultContent = document.getElementById('ilyangResultContent');
        
        resultDiv.classList.remove('hidden');
        resultContent.textContent = '로딩 중...';
        
        fetch('/api-test/sample')
            .then(response => response.json())
            .then(data => {
                resultContent.textContent = JSON.stringify(data, null, 2);
                addLog('샘플 데이터 생성 완료');
            })
            .catch(error => {
                resultContent.textContent = '에러: ' + error.message;
                addLog('샘플 데이터 생성 에러: ' + error.message);
            });
    });

    // IP 정보 테스트
    document.getElementById('testIpInfo').addEventListener('click', function() {
        const resultDiv = document.getElementById('ipResult');
        const resultContent = document.getElementById('ipResultContent');
        
        resultDiv.classList.remove('hidden');
        resultContent.textContent = '로딩 중...';
        
        fetch('/api-test/ip')
            .then(response => response.json())
            .then(data => {
                resultContent.textContent = JSON.stringify(data, null, 2);
                addLog('IP 정보 조회 완료');
            })
            .catch(error => {
                resultContent.textContent = '에러: ' + error.message;
                addLog('IP 정보 조회 에러: ' + error.message);
            });
    });

    // IP 정보 새로고침
    document.getElementById('refreshIpInfo').addEventListener('click', function() {
        location.reload();
    });

    // 로그 추가 함수
    function addLog(message) {
        const logContainer = document.getElementById('logContainer');
        const timestamp = new Date().toLocaleTimeString();
        const logEntry = document.createElement('div');
        logEntry.textContent = `[${timestamp}] ${message}`;
        logContainer.appendChild(logEntry);
        logContainer.scrollTop = logContainer.scrollHeight;
    }

    // 초기 로그
    addLog('API 테스트 페이지 로드 완료');
});
</script>

<?= $this->endSection() ?>

<?= $this->include('layouts/footer') ?>
