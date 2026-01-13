<?php

namespace App\Models;

use CodeIgniter\Model;

class UserSettlementDeptModel extends Model
{
    protected $table = 'tbl_user_settlement_depts';
    protected $primaryKey = 'idx';
    protected $useAutoIncrement = true;
    protected $returnType = 'array';
    protected $useSoftDeletes = false;
    protected $protectFields = true;
    protected $allowedFields = [
        'user_idx',
        'dept_name'
    ];

    protected $useTimestamps = true;
    protected $dateFormat = 'datetime';
    protected $createdField = 'created_at';
    protected $updatedField = 'updated_at';

    protected $validationRules = [
        'user_idx' => 'required|integer',
        'dept_name' => 'required|max_length[100]'
    ];

    protected $validationMessages = [];
    protected $skipValidation = false;

    /**
     * 사용자의 정산관리부서 목록 조회
     * 
     * @param int $userIdx 사용자 idx (tbl_users_list.idx)
     * @return array 정산관리부서 목록
     */
    public function getSettlementDeptsByUserIdx($userIdx)
    {
        return $this->where('user_idx', $userIdx)
                    ->findAll();
    }

    /**
     * 사용자의 정산관리부서명 목록만 조회 (배열로 반환)
     * 
     * @param int $userIdx 사용자 idx (tbl_users_list.idx)
     * @return array 부서명 배열
     */
    public function getSettlementDeptNamesByUserIdx($userIdx)
    {
        $depts = $this->where('user_idx', $userIdx)
                      ->findAll();
        
        return array_column($depts, 'dept_name');
    }

    /**
     * 사용자의 정산관리부서 일괄 저장 (기존 데이터 삭제 후 새로 저장)
     * 
     * @param int $userIdx 사용자 idx (tbl_users_list.idx)
     * @param array $deptNames 부서명 배열
     * @return bool 성공 여부
     */
    public function saveSettlementDepts($userIdx, $deptNames)
    {
        $db = \Config\Database::connect();
        $db->transStart();

        try {
            // 기존 데이터 삭제
            $this->where('user_idx', $userIdx)->delete();

            // 새 데이터 저장
            if (!empty($deptNames) && is_array($deptNames)) {
                foreach ($deptNames as $deptName) {
                    if (!empty(trim($deptName))) {
                        $this->insert([
                            'user_idx' => $userIdx,
                            'dept_name' => trim($deptName)
                        ]);
                    }
                }
            }

            $db->transComplete();
            return $db->transStatus() !== false;
        } catch (\Exception $e) {
            $db->transRollback();
            log_message('error', 'UserSettlementDeptModel::saveSettlementDepts - ' . $e->getMessage());
            return false;
        }
    }

    /**
     * 정산 조회 시 사용할 부서명 배열 반환
     * 특정 사용자의 정산관리부서 목록을 반환하여 WHERE IN 조건에 사용
     * 
     * @param int $userIdx 사용자 idx (tbl_users_list.idx)
     * @return array|null 부서명 배열, 없으면 null (전체 조회 가능)
     */
    public function getSettlementDeptNamesForQuery($userIdx)
    {
        $deptNames = $this->getSettlementDeptNamesByUserIdx($userIdx);
        
        // 정산관리부서가 설정되어 있지 않으면 null 반환 (전체 조회)
        if (empty($deptNames)) {
            return null;
        }
        
        return $deptNames;
    }

    /**
     * 여러 사용자의 정산관리부서 목록을 조회 (배치 조회용)
     * 
     * @param array $userIdxes 사용자 idx 배열
     * @return array user_idx를 키로 하는 부서명 배열
     */
    public function getSettlementDeptsByUserIdxes($userIdxes)
    {
        if (empty($userIdxes)) {
            return [];
        }

        $depts = $this->whereIn('user_idx', $userIdxes)
                      ->findAll();
        
        $result = [];
        foreach ($depts as $dept) {
            if (!isset($result[$dept['user_idx']])) {
                $result[$dept['user_idx']] = [];
            }
            $result[$dept['user_idx']][] = $dept['dept_name'];
        }
        
        return $result;
    }
}

