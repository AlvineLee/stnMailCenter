<?php

namespace App\Models;

use CodeIgniter\Model;

class RecentListModel extends Model
{
    protected $table = 'tbl_recent_list';
    protected $primaryKey = 'idx';
    protected $useAutoIncrement = true;
    protected $returnType = 'array';
    protected $useSoftDeletes = false;
    protected $protectFields = true;
    protected $allowedFields = [
        'user_id',
        'c_name',
        'c_telno',
        'dept_name',
        'charge_name',
        'c_dong',
        'c_addr',
        'address2',
        'c_sido',
        'gungu',
        'lon',
        'lat',
        'c_code',
        'bn'
    ];

    protected $useTimestamps = false; // created_at 컬럼이 없을 수 있으므로 비활성화
    protected $dateFormat = 'datetime';
    protected $createdField = 'created_at';
    protected $updatedField = 'updated_at';

    // Validation
    protected $validationRules = [];
    protected $validationMessages = [];
    protected $skipValidation = false;
    protected $cleanValidationRules = true;

    // Callbacks
    protected $allowCallbacks = true;
    protected $beforeInsert = [];
    protected $afterInsert = [];
    protected $beforeUpdate = [];
    protected $afterUpdate = [];
    protected $beforeFind = [];
    protected $afterFind = [];
    protected $beforeDelete = [];
    protected $afterDelete = [];

    /**
     * 사용자별 최근 사용 기록 조회
     * 
     * @param string $userId 사용자 ID
     * @param string|null $keyword 검색 키워드
     * @param int $limit 조회 개수 제한
     * @return array
     */
    public function getUserRecentList($userId, $keyword = null, $limit = 100)
    {
        $builder = $this->db->table($this->table);
        $builder->where('user_id', $userId);
        
        if (!empty($keyword)) {
            $builder->groupStart();
            $builder->like('c_name', $keyword);
            $builder->orLike('c_telno', $keyword);
            $builder->orLike('dept_name', $keyword);
            $builder->orLike('charge_name', $keyword);
            $builder->orLike('c_dong', $keyword);
            $builder->groupEnd();
        }
        
        // created_at 컬럼이 있는지 확인 후 정렬
        // 테이블에 created_at 컬럼이 없을 수 있으므로 idx로 정렬 (최신순)
        $builder->orderBy('idx', 'DESC');
        $builder->limit($limit);
        
        $query = $builder->get();
        if ($query === false) {
            return [];
        }
        
        return $query->getResultArray();
    }

    /**
     * 최근 사용 기록 추가
     * 
     * @param array $data 최근 사용 기록 데이터
     * @return int|false 삽입된 ID 또는 false
     */
    public function addRecent($data)
    {
        // 중복 체크 (같은 사용자가 같은 정보를 최근에 사용한 경우)
        $existing = $this->where('user_id', $data['user_id'])
            ->where('c_name', $data['c_name'])
            ->where('c_telno', $data['c_telno'] ?? '')
            ->where('lon', $data['lon'] ?? '')
            ->where('lat', $data['lat'] ?? '')
            ->orderBy('idx', 'DESC')
            ->first();
        
        if ($existing) {
            // 이미 존재하면 업데이트 (idx만 업데이트하여 최신으로 만듦)
            return $this->update($existing['idx'], []);
        }
        
        // 새로 추가
        return $this->insert($data);
    }

    /**
     * 오래된 최근 사용 기록 삭제 (관리용)
     * 
     * @param string $userId 사용자 ID
     * @param int $days 보관 기간 (일)
     * @return int 삭제된 레코드 수
     */
    public function cleanOldRecords($userId, $days = 90)
    {
        // created_at 컬럼이 없으므로 idx 기반으로 삭제 (최신 N개만 유지)
        $limit = 100; // 최근 100개만 유지
        
        // 전체 개수 확인
        $total = $this->where('user_id', $userId)
            ->where('bn', 0)
            ->countAllResults(false);
        
        if ($total > $limit) {
            // 오래된 레코드 조회 (idx가 작은 것부터)
            $oldRecords = $this->where('user_id', $userId)
                ->where('bn', 0)
                ->orderBy('idx', 'ASC')
                ->limit($total - $limit)
                ->findAll();
            
            // 삭제
            foreach ($oldRecords as $record) {
                $this->delete($record['idx']);
            }
            
            return count($oldRecords);
        }
        
        return 0;
    }
}

