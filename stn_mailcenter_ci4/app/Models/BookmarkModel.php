<?php

namespace App\Models;

use CodeIgniter\Model;

class BookmarkModel extends Model
{
    protected $table = 'tbl_bookmark_list';
    protected $primaryKey = 'idx';
    protected $useAutoIncrement = true;
    protected $returnType = 'array';
    protected $useSoftDeletes = false;
    protected $protectFields = true;
    protected $allowedFields = [
        'user_id',
        'company_name',
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
     * 사용자별 즐겨찾기 목록 조회
     * 
     * @param string $userId 사용자 ID
     * @param string|null $keyword 검색 키워드 (상호명, 연락처, 부서명 등)
     * @param int $limit 조회 개수 제한
     * @return array
     */
    public function getUserBookmarks($userId, $keyword = null, $limit = 100)
    {
        $builder = $this->db->table($this->table);
        $builder->where('user_id', $userId);
        $builder->where('bn', 1); // 즐겨찾기만 조회
        
        if (!empty($keyword)) {
            $builder->groupStart();
            $builder->like('company_name', $keyword);
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
     * 즐겨찾기 추가 (INSERT ON DUPLICATE KEY UPDATE 방식)
     * 
     * @param array $data 즐겨찾기 데이터
     * @return int|false 삽입/업데이트된 ID 또는 false
     */
    public function addBookmark($data)
    {
        // 필수 필드 확인
        if (empty($data['user_id']) || empty($data['company_name'])) {
            return false;
        }
        
        // bn 필드 설정
        $data['bn'] = 1;
        
        // 중복 체크: user_id, company_name, c_telno 기준
        $existing = $this->where('user_id', $data['user_id'])
            ->where('company_name', $data['company_name'])
            ->where('c_telno', $data['c_telno'] ?? '')
            ->first();
        
        if ($existing) {
            // 이미 존재하면 업데이트 (모든 필드 업데이트)
            $updateData = [
                'dept_name' => $data['dept_name'] ?? '',
                'charge_name' => $data['charge_name'] ?? '',
                'c_dong' => $data['c_dong'] ?? '',
                'c_addr' => $data['c_addr'] ?? '',
                'address2' => $data['address2'] ?? '',
                'c_sido' => $data['c_sido'] ?? '',
                'gungu' => $data['gungu'] ?? '',
                'lon' => $data['lon'] ?? '',
                'lat' => $data['lat'] ?? '',
                'c_code' => $data['c_code'] ?? '',
                'bn' => 1
            ];
            return $this->update($existing['idx'], $updateData);
        }
        
        // 새로 추가
        return $this->insert($data);
    }

    /**
     * 즐겨찾기 삭제
     * 
     * @param int $idx 즐겨찾기 ID
     * @param string $userId 사용자 ID (권한 확인용)
     * @return bool
     */
    public function removeBookmark($idx, $userId)
    {
        $bookmark = $this->find($idx);
        if (!$bookmark || $bookmark['user_id'] !== $userId) {
            return false;
        }
        
        return $this->update($idx, ['bn' => 0]);
    }

    /**
     * 즐겨찾기 존재 여부 확인
     * 
     * @param string $userId 사용자 ID
     * @param string $companyName 상호명
     * @param string $cTelno 연락처
     * @return bool
     */
    public function isBookmarked($userId, $companyName, $cTelno)
    {
        $result = $this->where('user_id', $userId)
            ->where('company_name', $companyName)
            ->where('c_telno', $cTelno)
            ->where('bn', 1)
            ->first();
        
        return !empty($result);
    }
}

