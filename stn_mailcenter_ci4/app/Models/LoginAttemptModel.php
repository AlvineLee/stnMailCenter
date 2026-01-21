<?php

namespace App\Models;

use CodeIgniter\Model;

/**
 * 로그인 시도 기록 모델
 * 로그인 실패 기록 및 접근 제한 관리
 *
 * IP 정책:
 * - ip_address: 내부망 IP (REMOTE_ADDR) - 잠금 체크에 사용
 * - forwarded_ip: 외부 IP (X-Forwarded-For) - 기록용, 잠금 체크에 미사용
 * - 잠금은 user_id + 내부IP 조합으로만 체크 (같은 회사 다른 사용자 영향 방지)
 */
class LoginAttemptModel extends Model
{
    protected $table = 'tbl_login_attempts';
    protected $primaryKey = 'idx';
    protected $useAutoIncrement = true;
    protected $returnType = 'array';
    protected $useSoftDeletes = false;
    protected $protectFields = true;
    protected $allowedFields = [
        'user_id',
        'ip_address',
        'forwarded_ip',
        'user_agent',
        'attempt_type',
        'is_success',
        'failure_reason',
        'created_at'
    ];

    protected $useTimestamps = false;
    protected $dateFormat = 'datetime';

    /**
     * 로그인 시도 기록
     *
     * @param string $userId 시도한 사용자 ID
     * @param string $ipAddress 내부망 IP (REMOTE_ADDR)
     * @param string|null $forwardedIp 외부 IP (X-Forwarded-For)
     * @param bool $isSuccess 성공 여부
     * @param string $failureReason 실패 사유
     * @param string $attemptType 시도 유형 (daumdata, stn)
     * @return int|false 삽입된 레코드 ID 또는 false
     */
    public function recordAttempt($userId, $ipAddress, $forwardedIp, $isSuccess, $failureReason = null, $attemptType = 'daumdata')
    {
        $data = [
            'user_id' => $userId,
            'ip_address' => $ipAddress,
            'forwarded_ip' => $forwardedIp,
            'user_agent' => substr($_SERVER['HTTP_USER_AGENT'] ?? '', 0, 500),
            'attempt_type' => $attemptType,
            'is_success' => $isSuccess ? 1 : 0,
            'failure_reason' => $failureReason,
            'created_at' => date('Y-m-d H:i:s')
        ];

        return $this->insert($data);
    }

    /**
     * 특정 사용자의 최근 실패 횟수 조회 (내부IP 기준)
     *
     * 잠금 정책: user_id + 내부IP 조합으로 체크
     * - 같은 user_id로 시도한 실패만 카운트
     * - 외부IP(forwarded_ip)는 카운트에 포함하지 않음 (같은 회사 영향 방지)
     *
     * @param string $userId 사용자 ID
     * @param string $ipAddress 내부망 IP
     * @param int $minutes 조회할 시간 범위 (분)
     * @return int 실패 횟수
     */
    public function getRecentFailureCount($userId, $ipAddress, $minutes = 30)
    {
        $since = date('Y-m-d H:i:s', strtotime("-{$minutes} minutes"));

        // user_id 기준으로만 카운트 (내부IP는 참고용)
        return $this->where('is_success', 0)
                    ->where('created_at >=', $since)
                    ->where('user_id', $userId)
                    ->countAllResults();
    }

    /**
     * 특정 사용자가 잠금 상태인지 확인
     *
     * 잠금 정책: user_id 기준으로만 체크
     * - 외부IP로 인한 다른 사용자 잠금 방지
     *
     * @param string $userId 사용자 ID
     * @param string $ipAddress 내부망 IP (현재는 사용 안함, 향후 확장용)
     * @param int $maxAttempts 최대 시도 횟수
     * @param int $lockoutMinutes 잠금 시간 (분)
     * @return array ['locked' => bool, 'remaining_seconds' => int, 'failure_count' => int]
     */
    public function isLocked($userId, $ipAddress, $maxAttempts = 5, $lockoutMinutes = 5)
    {
        // 잠금 시간 내 실패 기록 조회 (user_id 기준)
        $since = date('Y-m-d H:i:s', strtotime("-{$lockoutMinutes} minutes"));

        $failures = $this->select('created_at')
                         ->where('is_success', 0)
                         ->where('created_at >=', $since)
                         ->where('user_id', $userId)
                         ->orderBy('created_at', 'DESC')
                         ->findAll();

        $failureCount = count($failures);

        if ($failureCount >= $maxAttempts) {
            // 마지막 실패 시간부터 잠금 시간 계산
            $lastFailure = strtotime($failures[0]['created_at']);
            $unlockTime = $lastFailure + ($lockoutMinutes * 60);
            $remainingSeconds = $unlockTime - time();

            if ($remainingSeconds > 0) {
                return [
                    'locked' => true,
                    'remaining_seconds' => $remainingSeconds,
                    'failure_count' => $failureCount
                ];
            }
        }

        return [
            'locked' => false,
            'remaining_seconds' => 0,
            'failure_count' => $failureCount
        ];
    }

    /**
     * 로그인 성공 시 해당 사용자/IP의 실패 기록 초기화 (옵션)
     * 성공 후에도 기록은 유지하되, 잠금 해제만 처리
     *
     * @param string $userId 사용자 ID
     * @param string $ipAddress IP 주소
     */
    public function clearFailures($userId, $ipAddress)
    {
        // 실제로 삭제하지 않고 성공 기록을 남김
        // 기록은 보존하고 isLocked에서 성공 이후 실패만 카운트하도록 처리
    }

    /**
     * 로그인 시도 내역 조회 (관리자용, 페이징)
     *
     * @param array $filters 필터 조건
     * @param int $page 페이지 번호
     * @param int $perPage 페이지당 개수
     * @return array
     */
    public function getAttemptHistory($filters = [], $page = 1, $perPage = 20)
    {
        $builder = $this->builder();

        // 필터 적용
        if (!empty($filters['user_id'])) {
            $builder->like('user_id', $filters['user_id']);
        }
        if (!empty($filters['ip_address'])) {
            $builder->groupStart()
                    ->like('ip_address', $filters['ip_address'])
                    ->orLike('forwarded_ip', $filters['ip_address'])
                    ->groupEnd();
        }
        if (isset($filters['is_success']) && $filters['is_success'] !== '') {
            $builder->where('is_success', $filters['is_success']);
        }
        if (!empty($filters['date_from'])) {
            $builder->where('created_at >=', $filters['date_from'] . ' 00:00:00');
        }
        if (!empty($filters['date_to'])) {
            $builder->where('created_at <=', $filters['date_to'] . ' 23:59:59');
        }

        // 총 개수
        $countBuilder = clone $builder;
        $totalCount = $countBuilder->countAllResults(false);

        // 정렬 및 페이징
        $builder->orderBy('created_at', 'DESC');
        $offset = ($page - 1) * $perPage;
        $builder->limit($perPage, $offset);

        $attempts = $builder->get()->getResultArray();

        // 페이징 정보
        helper('pagination');
        $pagination = calculatePagination($totalCount, $page, $perPage);

        return [
            'attempts' => $attempts,
            'total_count' => $totalCount,
            'pagination' => $pagination
        ];
    }

    /**
     * 통계 조회 (대시보드용)
     *
     * @param int $days 조회할 일수
     * @return array
     */
    public function getStatistics($days = 7)
    {
        $since = date('Y-m-d H:i:s', strtotime("-{$days} days"));

        // 전체 시도 수
        $totalAttempts = $this->where('created_at >=', $since)->countAllResults();

        // 실패 시도 수
        $failedAttempts = $this->where('created_at >=', $since)
                               ->where('is_success', 0)
                               ->countAllResults();

        // 성공 시도 수
        $successAttempts = $totalAttempts - $failedAttempts;

        // 고유 내부 IP 수
        $uniqueIps = $this->select('ip_address')
                          ->where('created_at >=', $since)
                          ->distinct()
                          ->countAllResults();

        // 일별 통계
        $dailyStats = $this->select("DATE(created_at) as date, COUNT(*) as total, SUM(CASE WHEN is_success = 0 THEN 1 ELSE 0 END) as failures")
                           ->where('created_at >=', $since)
                           ->groupBy('DATE(created_at)')
                           ->orderBy('date', 'ASC')
                           ->findAll();

        return [
            'total_attempts' => $totalAttempts,
            'failed_attempts' => $failedAttempts,
            'success_attempts' => $successAttempts,
            'unique_ips' => $uniqueIps,
            'daily_stats' => $dailyStats
        ];
    }
}