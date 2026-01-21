<?php

namespace App\Commands;

use CodeIgniter\CLI\BaseCommand;
use CodeIgniter\CLI\CLI;

/**
 * 슈퍼관리자 대시보드 통계 집계 Command
 *
 * 사용법: php spark dashboard:aggregate
 * Cron 설정: 0 0 * * * cd /path/to/project && php spark dashboard:aggregate >> /var/log/dashboard_stats.log 2>&1
 */
class AggregateDashboardStats extends BaseCommand
{
    protected $group = 'Dashboard';
    protected $name = 'dashboard:aggregate';
    protected $description = '슈퍼관리자 대시보드 통계를 집계합니다 (매일 자정 Cron용)';
    protected $usage = 'dashboard:aggregate [date]';
    protected $arguments = [
        'date' => '집계 기준일 (YYYY-MM-DD 형식, 기본값: 오늘)',
    ];

    public function run(array $params)
    {
        $statDate = $params[0] ?? date('Y-m-d');

        // 날짜 형식 검증
        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $statDate)) {
            CLI::error("잘못된 날짜 형식입니다. YYYY-MM-DD 형식으로 입력해주세요.");
            return;
        }

        CLI::write("========================================", 'yellow');
        CLI::write("대시보드 통계 집계 시작", 'yellow');
        CLI::write("기준일: {$statDate}", 'yellow');
        CLI::write("========================================", 'yellow');

        $db = \Config\Database::connect();
        $startTime = microtime(true);

        try {
            // 1. 전체 요약 통계 집계
            CLI::write("1. 전체 요약 통계 집계 중...", 'white');
            $this->aggregateSummary($db, $statDate);
            CLI::write("   완료!", 'green');

            // 2. 콜센터별 거래처 수 집계
            CLI::write("2. 콜센터별 거래처 수 집계 중...", 'white');
            $this->aggregateByCallCenter($db, $statDate);
            CLI::write("   완료!", 'green');

            // 3. 사용자 수 많은 거래처 TOP 50 집계
            CLI::write("3. 사용자 수 많은 거래처 TOP 50 집계 중...", 'white');
            $this->aggregateTopCompaniesByUserCount($db, $statDate);
            CLI::write("   완료!", 'green');

            // 4. 신규 등록 거래처 TOP 50 집계
            CLI::write("4. 신규 등록 거래처 TOP 50 집계 중...", 'white');
            $this->aggregateRecentCompanies($db, $statDate);
            CLI::write("   완료!", 'green');

            $endTime = microtime(true);
            $elapsed = round($endTime - $startTime, 2);

            CLI::write("========================================", 'green');
            CLI::write("통계 집계 완료! (소요시간: {$elapsed}초)", 'green');
            CLI::write("========================================", 'green');

            log_message('info', "Dashboard stats aggregation completed for {$statDate} in {$elapsed}s");

        } catch (\Exception $e) {
            CLI::error("통계 집계 중 오류 발생: " . $e->getMessage());
            log_message('error', "Dashboard stats aggregation failed: " . $e->getMessage());
        }
    }

    /**
     * 전체 요약 통계 집계
     */
    private function aggregateSummary($db, $statDate)
    {
        // 기존 데이터 삭제 (해당 날짜)
        $db->table('tbl_dashboard_stats_summary')
            ->where('stat_date', $statDate)
            ->delete();

        // 총 콜센터 수
        $totalCallCenters = $db->table('tbl_cc_list')->countAllResults();

        // 활성 거래처 수
        $activeCompanies = $db->table('tbl_company_list')
            ->where('use_yn', 'Y')
            ->countAllResults();

        // 비활성 거래처 수
        $inactiveCompanies = $db->table('tbl_company_list')
            ->where('use_yn !=', 'Y')
            ->countAllResults();

        // 총 거래처 수
        $totalCompanies = $db->table('tbl_company_list')->countAllResults();

        // 총 사용자 수
        $totalUsers = $db->table('tbl_users_list')->countAllResults();

        // 저장
        $db->table('tbl_dashboard_stats_summary')->insert([
            'stat_date' => $statDate,
            'total_call_centers' => $totalCallCenters,
            'total_companies' => $totalCompanies,
            'active_companies' => $activeCompanies,
            'inactive_companies' => $inactiveCompanies,
            'total_users' => $totalUsers,
            'created_at' => date('Y-m-d H:i:s'),
        ]);

        CLI::write("   - 콜센터: {$totalCallCenters}개, 거래처: {$totalCompanies}개 (활성: {$activeCompanies}, 비활성: {$inactiveCompanies}), 사용자: {$totalUsers}명", 'light_gray');
    }

    /**
     * 콜센터별 거래처 수 집계
     */
    private function aggregateByCallCenter($db, $statDate)
    {
        // 기존 데이터 삭제
        $db->table('tbl_dashboard_stats_by_cc')
            ->where('stat_date', $statDate)
            ->delete();

        // 콜센터별 거래처 수 조회
        $builder = $db->table('tbl_cc_list cc');
        $builder->select('
            cc.idx as cc_idx,
            cc.cc_code,
            cc.cc_name,
            COUNT(c.comp_idx) as company_count,
            SUM(CASE WHEN c.use_yn = "Y" THEN 1 ELSE 0 END) as active_count,
            SUM(CASE WHEN c.use_yn != "Y" OR c.use_yn IS NULL THEN 1 ELSE 0 END) as inactive_count
        ');
        $builder->join('tbl_company_list c', 'c.cc_idx = cc.idx', 'left');
        $builder->groupBy('cc.idx, cc.cc_code, cc.cc_name');
        $builder->orderBy('company_count', 'DESC');

        $results = $builder->get()->getResultArray();

        // 저장
        $insertData = [];
        foreach ($results as $row) {
            $insertData[] = [
                'stat_date' => $statDate,
                'cc_idx' => $row['cc_idx'],
                'cc_code' => $row['cc_code'],
                'cc_name' => $row['cc_name'],
                'company_count' => $row['company_count'] ?? 0,
                'active_count' => $row['active_count'] ?? 0,
                'inactive_count' => $row['inactive_count'] ?? 0,
                'created_at' => date('Y-m-d H:i:s'),
            ];
        }

        if (!empty($insertData)) {
            $db->table('tbl_dashboard_stats_by_cc')->insertBatch($insertData);
        }

        CLI::write("   - " . count($results) . "개 콜센터 통계 저장", 'light_gray');
    }

    /**
     * 사용자 수 많은 거래처 TOP 50 집계
     */
    private function aggregateTopCompaniesByUserCount($db, $statDate)
    {
        // 기존 데이터 삭제
        $db->table('tbl_dashboard_stats_top_companies')
            ->where('stat_date', $statDate)
            ->delete();

        // 사용자 수 많은 거래처 조회
        $builder = $db->table('tbl_company_list c');
        $builder->select('
            c.comp_idx,
            c.comp_code,
            c.comp_name,
            c.use_yn,
            cc.cc_code,
            cc.cc_name,
            COUNT(u.idx) as user_count
        ');
        $builder->join('tbl_cc_list cc', 'c.cc_idx = cc.idx', 'left');
        $builder->join('tbl_users_list u', 'u.user_company = c.comp_code', 'left');
        $builder->groupBy('c.comp_idx, c.comp_code, c.comp_name, c.use_yn, cc.cc_code, cc.cc_name');
        $builder->having('user_count >', 0);
        $builder->orderBy('user_count', 'DESC');
        $builder->limit(50);

        $results = $builder->get()->getResultArray();

        // 저장
        $insertData = [];
        $rank = 1;
        foreach ($results as $row) {
            $insertData[] = [
                'stat_date' => $statDate,
                'rank_order' => $rank++,
                'comp_idx' => $row['comp_idx'],
                'comp_code' => $row['comp_code'],
                'comp_name' => $row['comp_name'],
                'cc_code' => $row['cc_code'],
                'cc_name' => $row['cc_name'],
                'user_count' => $row['user_count'] ?? 0,
                'use_yn' => $row['use_yn'] ?? 'N',
                'created_at' => date('Y-m-d H:i:s'),
            ];
        }

        if (!empty($insertData)) {
            $db->table('tbl_dashboard_stats_top_companies')->insertBatch($insertData);
        }

        CLI::write("   - " . count($results) . "개 거래처 통계 저장", 'light_gray');
    }

    /**
     * 신규 등록 거래처 TOP 50 집계
     */
    private function aggregateRecentCompanies($db, $statDate)
    {
        // 기존 데이터 삭제
        $db->table('tbl_dashboard_stats_recent_companies')
            ->where('stat_date', $statDate)
            ->delete();

        // 최근 등록 거래처 조회
        $builder = $db->table('tbl_company_list c');
        $builder->select('
            c.comp_idx,
            c.comp_code,
            c.comp_name,
            c.comp_owner,
            c.use_yn,
            c.created_at as company_created_at,
            cc.cc_code,
            cc.cc_name
        ');
        $builder->join('tbl_cc_list cc', 'c.cc_idx = cc.idx', 'left');
        $builder->where('c.created_at IS NOT NULL');
        $builder->orderBy('c.created_at', 'DESC');
        $builder->limit(50);

        $results = $builder->get()->getResultArray();

        // 저장
        $insertData = [];
        $rank = 1;
        foreach ($results as $row) {
            $insertData[] = [
                'stat_date' => $statDate,
                'rank_order' => $rank++,
                'comp_idx' => $row['comp_idx'],
                'comp_code' => $row['comp_code'],
                'comp_name' => $row['comp_name'],
                'comp_owner' => $row['comp_owner'],
                'cc_code' => $row['cc_code'],
                'cc_name' => $row['cc_name'],
                'use_yn' => $row['use_yn'] ?? 'N',
                'company_created_at' => $row['company_created_at'],
                'created_at' => date('Y-m-d H:i:s'),
            ];
        }

        if (!empty($insertData)) {
            $db->table('tbl_dashboard_stats_recent_companies')->insertBatch($insertData);
        }

        CLI::write("   - " . count($results) . "개 신규 거래처 통계 저장", 'light_gray');
    }
}