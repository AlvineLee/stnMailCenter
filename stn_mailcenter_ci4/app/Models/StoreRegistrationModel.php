<?php

namespace App\Models;

use CodeIgniter\Model;

class StoreRegistrationModel extends Model
{
    protected $table = 'tbl_store_registration_requests';
    protected $primaryKey = 'id';
    protected $useAutoIncrement = true;
    protected $returnType = 'array';
    protected $useSoftDeletes = false;
    protected $protectFields = true;
    protected $allowedFields = [
        'applicant_type', 'hierarchy_level', 'company_name', 'business_number',
        'business_type', 'employee_count', 'company_address', 'representative_name',
        'representative_phone', 'representative_email', 'annual_revenue',
        'primary_service_category', 'expected_monthly_orders', 'contract_period',
        'special_requirements', 'business_license_file', 'company_profile_file',
        'status', 'reviewed_by', 'reviewed_at', 'approved_by', 'approved_at',
        'rejection_reason', 'notes'
    ];

    protected $useTimestamps = true;
    protected $dateFormat = 'datetime';
    protected $createdField = 'created_at';
    protected $updatedField = 'updated_at';

    /**
     * 모든 입점신청 목록 조회 (페이징 포함)
     */
    public function getAllRegistrations($limit = null, $offset = 0, $filters = [])
    {
        $builder = $this->db->table($this->table . ' srr');
        $builder->select('
            srr.id,
            srr.company_name,
            srr.business_number,
            srr.representative_name,
            srr.representative_phone,
            srr.representative_email,
            srr.status,
            srr.created_at,
            srr.reviewed_at,
            srr.approved_at,
            srr.rejection_reason,
            u1.real_name as reviewer_name,
            u2.real_name as approver_name
        ');
        $builder->join('tbl_users u1', 'srr.reviewed_by = u1.id', 'left');
        $builder->join('tbl_users u2', 'srr.approved_by = u2.id', 'left');
        
        // 필터 적용
        if (!empty($filters['keyword'])) {
            $keyword = $filters['keyword'];
            $builder->groupStart();
            $builder->like('srr.company_name', $keyword);
            $builder->orLike('srr.business_number', $keyword);
            $builder->orLike('srr.representative_name', $keyword);
            $builder->orLike('srr.representative_email', $keyword);
            $builder->groupEnd();
        }
        
        if (!empty($filters['status'])) {
            $builder->where('srr.status', $filters['status']);
        }
        
        $builder->orderBy('srr.created_at', 'DESC');
        
        if ($limit) {
            $builder->limit($limit, $offset);
        }
        
        return $builder->get()->getResultArray();
    }

    /**
     * 총 레코드 수 조회 (필터 포함)
     */
    public function getTotalCount($filters = [])
    {
        $builder = $this->db->table($this->table . ' srr');
        
        // 필터 적용
        if (!empty($filters['keyword'])) {
            $keyword = $filters['keyword'];
            $builder->groupStart();
            $builder->like('srr.company_name', $keyword);
            $builder->orLike('srr.business_number', $keyword);
            $builder->orLike('srr.representative_name', $keyword);
            $builder->orLike('srr.representative_email', $keyword);
            $builder->groupEnd();
        }
        
        if (!empty($filters['status'])) {
            $builder->where('srr.status', $filters['status']);
        }
        
        return $builder->countAllResults();
    }

    /**
     * ID로 입점신청 상세 조회
     */
    public function getRegistrationById($id)
    {
        try {
            $builder = $this->db->table($this->table . ' srr');
            $builder->select('srr.*');
            $builder->where('srr.id', $id);
            
            $result = $builder->get()->getRowArray();
            
            if ($result) {
                // reviewer 정보 추가 (내부 직원이 심사한 경우)
                if ($result['reviewed_by']) {
                    $reviewer = $this->db->table('tbl_users')
                        ->select('real_name, email')
                        ->where('id', $result['reviewed_by'])
                        ->get()->getRowArray();
                    if ($reviewer) {
                        $result['reviewer_name'] = $reviewer['real_name'];
                        $result['reviewer_email'] = $reviewer['email'];
                    }
                }
                
                // approver 정보 추가 (내부 직원이 승인한 경우)
                if ($result['approved_by']) {
                    $approver = $this->db->table('tbl_users')
                        ->select('real_name, email')
                        ->where('id', $result['approved_by'])
                        ->get()->getRowArray();
                    if ($approver) {
                        $result['approver_name'] = $approver['real_name'];
                        $result['approver_email'] = $approver['email'];
                    }
                }
            }
            
            return $result;
        } catch (\Exception $e) {
            log_message('error', 'StoreRegistrationModel::getRegistrationById Error: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * 입점신청 서비스 목록 조회
     */
    public function getRegistrationServices($registrationId)
    {
        $builder = $this->db->table('tbl_store_registration_services srs');
        $builder->select('
            srs.*,
            st.service_name,
            st.service_code
        ');
        $builder->join('tbl_service_types st', 'srs.service_type_id = st.id');
        $builder->where('srs.registration_request_id', $registrationId);
        $builder->orderBy('srs.priority_order', 'ASC');
        
        return $builder->get()->getResultArray();
    }

    /**
     * 상태별 카운트 조회
     */
    public function getStatusCounts()
    {
        $builder = $this->db->table($this->table);
        $builder->select('
            status,
            COUNT(*) as count
        ');
        $builder->groupBy('status');
        
        $results = $builder->get()->getResultArray();
        $counts = [
            'pending' => 0,
            'under_review' => 0,
            'approved' => 0,
            'rejected' => 0,
            'total' => 0
        ];
        
        foreach ($results as $result) {
            $counts[$result['status']] = (int)$result['count'];
            $counts['total'] += (int)$result['count'];
        }
        
        return $counts;
    }

    /**
     * 상태 업데이트
     */
    public function updateStatus($id, $status, $notes = null, $userId = null)
    {
        try {
            $data = [
                'status' => $status,
                'updated_at' => date('Y-m-d H:i:s')
            ];

            if ($status === 'under_review') {
                $data['reviewed_by'] = $userId;
                $data['reviewed_at'] = date('Y-m-d H:i:s');
            } elseif ($status === 'approved') {
                $data['approved_by'] = $userId;
                $data['approved_at'] = date('Y-m-d H:i:s');
            } elseif ($status === 'rejected') {
                $data['rejection_reason'] = $notes;
            }

            if ($notes && $status !== 'rejected') {
                $data['notes'] = $notes;
            }

            $result = $this->update($id, $data);
            
            if (!$result) {
                log_message('error', 'StoreRegistrationModel::updateStatus - Update failed for ID: ' . $id);
            }
            
            return $result;
        } catch (\Exception $e) {
            log_message('error', 'StoreRegistrationModel::updateStatus Error: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * 검색 기능
     */
    public function searchRegistrations($keyword, $status = null, $limit = null, $offset = 0)
    {
        $builder = $this->db->table($this->table . ' srr');
        $builder->select('
            srr.id,
            srr.company_name,
            srr.business_number,
            srr.representative_name,
            srr.representative_phone,
            srr.representative_email,
            srr.status,
            srr.created_at,
            srr.reviewed_at,
            srr.approved_at,
            u1.real_name as reviewer_name,
            u2.real_name as approver_name
        ');
        $builder->join('tbl_users u1', 'srr.reviewed_by = u1.id', 'left');
        $builder->join('tbl_users u2', 'srr.approved_by = u2.id', 'left');
        
        if ($keyword) {
            $builder->groupStart();
            $builder->like('srr.company_name', $keyword);
            $builder->orLike('srr.business_number', $keyword);
            $builder->orLike('srr.representative_name', $keyword);
            $builder->orLike('srr.representative_email', $keyword);
            $builder->groupEnd();
        }
        
        if ($status) {
            $builder->where('srr.status', $status);
        }
        
        $builder->orderBy('srr.created_at', 'DESC');
        
        if ($limit) {
            $builder->limit($limit, $offset);
        }
        
        return $builder->get()->getResultArray();
    }
}