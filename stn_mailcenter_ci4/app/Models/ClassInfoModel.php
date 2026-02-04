<?php

namespace App\Models;

use CodeIgniter\Model;

/**
 * 권한(Class) 정보 모델
 * tbl_class_info 테이블 관리
 */
class ClassInfoModel extends Model
{
    protected $table = 'tbl_class_info';
    protected $primaryKey = 'class_id';
    protected $useAutoIncrement = false; // class_id는 수동 관리
    protected $returnType = 'array';
    protected $useSoftDeletes = false;
    protected $protectFields = true;
    protected $allowedFields = [
        'class_id',
        'class_name',
        'class_desc',
        'permission_level',
        'is_active',
        'created_at',
        'updated_at'
    ];

    protected $useTimestamps = true;
    protected $createdField = 'created_at';
    protected $updatedField = 'updated_at';
    protected $dateFormat = 'datetime';

    /**
     * 활성화된 권한 목록 조회
     *
     * @return array
     */
    public function getActiveClasses()
    {
        return $this->where('is_active', 1)
                    ->orderBy('permission_level', 'ASC')
                    ->findAll();
    }

    /**
     * 전체 권한 목록 조회 (관리자용)
     *
     * @return array
     */
    public function getAllClasses()
    {
        return $this->orderBy('permission_level', 'ASC')
                    ->findAll();
    }

    /**
     * 권한 ID로 권한명 조회
     *
     * @param int $classId 권한 ID
     * @return string|null
     */
    public function getClassName($classId)
    {
        $result = $this->find($classId);
        return $result ? $result['class_name'] : null;
    }

    /**
     * 권한 ID 존재 여부 확인
     *
     * @param int $classId 권한 ID
     * @return bool
     */
    public function classExists($classId)
    {
        return $this->find($classId) !== null;
    }

    /**
     * 드롭다운용 권한 목록 (활성화된 것만)
     *
     * @return array [class_id => class_name] 형태
     */
    public function getClassDropdown()
    {
        $classes = $this->getActiveClasses();
        $dropdown = [];
        foreach ($classes as $class) {
            $dropdown[$class['class_id']] = $class['class_name'];
        }
        return $dropdown;
    }

    /**
     * 권한 레벨 범위로 조회
     *
     * @param int $minLevel 최소 레벨
     * @param int $maxLevel 최대 레벨
     * @return array
     */
    public function getClassesByLevelRange($minLevel, $maxLevel)
    {
        return $this->where('permission_level >=', $minLevel)
                    ->where('permission_level <=', $maxLevel)
                    ->where('is_active', 1)
                    ->orderBy('permission_level', 'ASC')
                    ->findAll();
    }
}
