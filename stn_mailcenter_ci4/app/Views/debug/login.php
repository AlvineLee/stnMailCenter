<?= $this->extend('layouts/header') ?>

<?= $this->section('content') ?>
<div class="container mx-auto px-4 py-8">
    <div class="bg-white rounded-lg shadow-md p-6">
        <h1 class="text-2xl font-bold text-gray-800 mb-6">🔍 로그인 디버깅 정보</h1>
        
        <!-- 알림 메시지 -->
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
        
        <!-- 1. 데이터베이스 연결 상태 -->
        <div class="mb-6">
            <h2 class="text-lg font-semibold text-gray-700 mb-2">1. 데이터베이스 연결 상태</h2>
            <div class="bg-gray-50 p-3 rounded">
                <p><?= $debug_info['db_connection'] ?? '확인 중...' ?></p>
            </div>
        </div>
        
        <!-- 2. 테이블 존재 확인 -->
        <div class="mb-6">
            <h2 class="text-lg font-semibold text-gray-700 mb-2">2. 테이블 존재 확인</h2>
            <div class="bg-gray-50 p-3 rounded">
                <?php if (isset($debug_info['tables'])): ?>
                    <?php foreach ($debug_info['tables'] as $table => $status): ?>
                        <p><strong><?= $table ?>:</strong> <?= $status ?></p>
                    <?php endforeach; ?>
                <?php else: ?>
                    <p>테이블 정보를 확인할 수 없습니다.</p>
                <?php endif; ?>
            </div>
        </div>
        
        <!-- 3. 사용자 데이터 확인 -->
        <div class="mb-6">
            <h2 class="text-lg font-semibold text-gray-700 mb-2">3. 사용자 데이터 확인</h2>
            <div class="bg-gray-50 p-3 rounded">
                <?php if (isset($debug_info['user_error'])): ?>
                    <p class="text-red-600"><?= $debug_info['user_error'] ?></p>
                <?php else: ?>
                    <p><strong>총 사용자 수:</strong> <?= $debug_info['user_count'] ?? 0 ?></p>
                    <?php if (isset($debug_info['users']) && !empty($debug_info['users'])): ?>
                        <div class="mt-3">
                            <table class="min-w-full bg-white border border-gray-300">
                                <thead class="bg-gray-100">
                                    <tr>
                                        <th class="border border-gray-300 px-3 py-2 text-left">ID</th>
                                        <th class="border border-gray-300 px-3 py-2 text-left">Username</th>
                                        <th class="border border-gray-300 px-3 py-2 text-left">Real Name</th>
                                        <th class="border border-gray-300 px-3 py-2 text-left">Role</th>
                                        <th class="border border-gray-300 px-3 py-2 text-left">Status</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($debug_info['users'] as $user): ?>
                                        <tr>
                                            <td class="border border-gray-300 px-3 py-2"><?= $user['id'] ?></td>
                                            <td class="border border-gray-300 px-3 py-2"><?= $user['username'] ?></td>
                                            <td class="border border-gray-300 px-3 py-2"><?= $user['real_name'] ?></td>
                                            <td class="border border-gray-300 px-3 py-2"><?= $user['user_role'] ?></td>
                                            <td class="border border-gray-300 px-3 py-2"><?= $user['status'] ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>
                <?php endif; ?>
            </div>
        </div>
        
        <!-- 4. 고객사 데이터 확인 -->
        <div class="mb-6">
            <h2 class="text-lg font-semibold text-gray-700 mb-2">4. 고객사 데이터 확인</h2>
            <div class="bg-gray-50 p-3 rounded">
                <?php if (isset($debug_info['customer_error'])): ?>
                    <p class="text-red-600"><?= $debug_info['customer_error'] ?></p>
                <?php else: ?>
                    <p><strong>총 고객사 수:</strong> <?= $debug_info['customer_count'] ?? 0 ?></p>
                    <?php if (isset($debug_info['customers']) && !empty($debug_info['customers'])): ?>
                        <div class="mt-3">
                            <table class="min-w-full bg-white border border-gray-300">
                                <thead class="bg-gray-100">
                                    <tr>
                                        <th class="border border-gray-300 px-3 py-2 text-left">ID</th>
                                        <th class="border border-gray-300 px-3 py-2 text-left">Name</th>
                                        <th class="border border-gray-300 px-3 py-2 text-left">Level</th>
                                        <th class="border border-gray-300 px-3 py-2 text-left">Code</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($debug_info['customers'] as $customer): ?>
                                        <tr>
                                            <td class="border border-gray-300 px-3 py-2"><?= $customer['id'] ?></td>
                                            <td class="border border-gray-300 px-3 py-2"><?= $customer['customer_name'] ?></td>
                                            <td class="border border-gray-300 px-3 py-2"><?= $customer['hierarchy_level'] ?></td>
                                            <td class="border border-gray-300 px-3 py-2"><?= $customer['customer_code'] ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>
                <?php endif; ?>
            </div>
        </div>
        
        <!-- 5. stn_admin 사용자 상세 확인 -->
        <div class="mb-6">
            <h2 class="text-lg font-semibold text-gray-700 mb-2">5. stn_admin 사용자 상세 확인</h2>
            <div class="bg-gray-50 p-3 rounded">
                <?php if (isset($debug_info['stn_admin_error'])): ?>
                    <p class="text-red-600"><?= $debug_info['stn_admin_error'] ?></p>
                <?php elseif (isset($debug_info['stn_admin']) && $debug_info['stn_admin']): ?>
                    <?php $user = $debug_info['stn_admin']; ?>
                    <p><strong>ID:</strong> <?= $user['id'] ?></p>
                    <p><strong>Username:</strong> <?= $user['username'] ?></p>
                    <p><strong>Real Name:</strong> <?= $user['real_name'] ?></p>
                    <p><strong>Status:</strong> <?= $user['status'] ?></p>
                    <p><strong>Is Active:</strong> <?= $user['is_active'] ? 'Yes' : 'No' ?></p>
                    <p><strong>User Role:</strong> <?= $user['user_role'] ?></p>
                    <p><strong>Customer ID:</strong> <?= $user['customer_id'] ?></p>
                    <p><strong>Password Hash:</strong> <?= substr($user['password'], 0, 20) ?>...</p>
                    <p><strong>비밀번호 '1111' 검증:</strong> <?= isset($debug_info['password_test']) ? $debug_info['password_test'] : '확인 중...' ?></p>
                <?php else: ?>
                    <p class="text-red-600">❌ stn_admin 사용자를 찾을 수 없습니다</p>
                    <div class="mt-3">
                        <a href="/debug/createUser" class="bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded">
                            stn_admin 사용자 생성
                        </a>
                    </div>
                <?php endif; ?>
            </div>
        </div>
        
        <!-- 6. 비밀번호 해시 테스트 -->
        <div class="mb-6">
            <h2 class="text-lg font-semibold text-gray-700 mb-2">6. 비밀번호 해시 테스트</h2>
            <div class="bg-gray-50 p-3 rounded">
                <?php if (isset($debug_info['password_test'])): ?>
                    <p><strong>테스트 비밀번호:</strong> <?= $debug_info['password_test']['test_password'] ?></p>
                    <p><strong>생성된 해시:</strong> <?= $debug_info['password_test']['generated_hash'] ?></p>
                    <p><strong>검증 결과:</strong> <?= $debug_info['password_test']['verification'] ?></p>
                <?php else: ?>
                    <p>비밀번호 테스트 정보를 확인할 수 없습니다.</p>
                <?php endif; ?>
            </div>
        </div>
        
        <!-- 해결 방법 -->
        <div class="mb-6">
            <h2 class="text-lg font-semibold text-gray-700 mb-2">7. 해결 방법</h2>
            <div class="bg-yellow-50 border border-yellow-200 p-3 rounded">
                <p class="mb-2">만약 stn_admin 사용자가 없다면:</p>
                <ol class="list-decimal list-inside space-y-1">
                    <li>위의 "stn_admin 사용자 생성" 버튼을 클릭하세요</li>
                    <li>또는 데이터베이스에서 다음 SQL을 실행하세요:</li>
                </ol>
                <pre class="mt-2 bg-gray-100 p-2 rounded text-sm overflow-x-auto">
INSERT INTO tbl_users (customer_id, username, password, real_name, email, phone, department, position, user_role, status, is_active) VALUES
(1, 'stn_admin', '1111', 'STN관리자', 'admin@stn.co.kr', '02-1234-5678', '관리팀', '대표', 'super_admin', 'active', TRUE);
                </pre>
            </div>
        </div>
        
        <!-- 새로고침 버튼 -->
        <div class="text-center">
            <a href="/debug/login" class="bg-gray-500 hover:bg-gray-700 text-white font-bold py-2 px-4 rounded">
                새로고침
            </a>
        </div>
    </div>
</div>
<?= $this->endSection() ?>
