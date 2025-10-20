<?php

namespace App\Models;

use CodeIgniter\Model;

class BillingModel extends Model
{
    protected $table = 'tbl_billing_requests';
    protected $primaryKey = 'id';
    protected $useAutoIncrement = true;
    protected $returnType = 'array';
    protected $useSoftDeletes = false;
    protected $protectFields = true;
    protected $allowedFields = [
        'billing_number',
        'billing_type',
        'billing_period_start',
        'billing_period_end',
        'billing_date',
        'due_date',
        'customer_id',
        'department_ids',
        'customer_ids',
        'total_amount',
        'tax_amount',
        'final_amount',
        'currency',
        'status',
        'payment_status',
        'payment_method',
        'payment_date',
        'billing_file_path',
        'billing_file_name',
        'billing_notes',
        'created_by'
    ];

    protected $useTimestamps = true;
    protected $dateFormat = 'datetime';
    protected $createdField = 'created_at';
    protected $updatedField = 'updated_at';

    protected $validationRules = [
        'billing_type' => 'required|in_list[department,department_group,customer_group]',
        'billing_period_start' => 'required|valid_date',
        'billing_period_end' => 'required|valid_date',
        'billing_date' => 'required|valid_date',
        'due_date' => 'required|valid_date',
        'total_amount' => 'required|decimal',
        'tax_amount' => 'required|decimal',
        'final_amount' => 'required|decimal',
        'status' => 'permit_empty|in_list[draft,pending,sent,paid,overdue,cancelled]',
        'payment_status' => 'permit_empty|in_list[unpaid,partial,paid,overdue]',
        'created_by' => 'required|integer'
    ];

    protected $validationMessages = [
        'billing_type' => [
            'required' => '청구 유형은 필수입니다.',
            'in_list' => '유효하지 않은 청구 유형입니다.'
        ],
        'billing_period_start' => [
            'required' => '청구 기간 시작일은 필수입니다.',
            'valid_date' => '유효한 날짜를 입력해주세요.'
        ],
        'billing_period_end' => [
            'required' => '청구 기간 종료일은 필수입니다.',
            'valid_date' => '유효한 날짜를 입력해주세요.'
        ],
        'total_amount' => [
            'required' => '총 청구 금액은 필수입니다.',
            'decimal' => '유효한 금액을 입력해주세요.'
        ]
    ];

    protected $skipValidation = false;
    protected $cleanValidationRules = true;

    protected $beforeInsert = ['generateBillingNumber'];
    protected $afterInsert = [];
    protected $beforeUpdate = [];
    protected $afterUpdate = [];
    protected $beforeDelete = [];
    protected $afterDelete = [];

    /**
     * 청구번호 자동 생성
     */
    protected function generateBillingNumber(array $data)
    {
        if (!isset($data['data']['billing_number']) || empty($data['data']['billing_number'])) {
            $date = date('Ymd');
            $prefix = "BILL-{$date}-";
            
            $builder = $this->builder();
            $builder->like('billing_number', $prefix);
            $count = $builder->countAllResults();
            
            $data['data']['billing_number'] = $prefix . str_pad($count + 1, 4, '0', STR_PAD_LEFT);
        }
        
        return $data;
    }

    /**
     * 청구 유형별 청구서 목록 조회
     */
    public function getBillingRequestsByType($billingType, $status = null, $limit = 20, $offset = 0)
    {
        $builder = $this->builder();
        $builder->where('billing_type', $billingType);
        
        if ($status) {
            $builder->where('status', $status);
        }
        
        $builder->orderBy('created_at', 'DESC');
        $builder->limit($limit, $offset);
        
        return $builder->get()->getResultArray();
    }

    /**
     * 고객사별 청구서 목록 조회
     */
    public function getBillingRequestsByCustomer($customerId, $status = null, $limit = 20, $offset = 0)
    {
        $builder = $this->builder();
        $builder->groupStart();
        $builder->where('customer_id', $customerId);
        $builder->orWhere("JSON_CONTAINS(customer_ids, CAST({$customerId} AS JSON))");
        $builder->groupEnd();
        
        if ($status) {
            $builder->where('status', $status);
        }
        
        $builder->orderBy('created_at', 'DESC');
        $builder->limit($limit, $offset);
        
        return $builder->get()->getResultArray();
    }

    /**
     * 부서별 청구서 목록 조회
     */
    public function getBillingRequestsByDepartment($departmentId, $status = null, $limit = 20, $offset = 0)
    {
        $builder = $this->builder();
        $builder->where("JSON_CONTAINS(department_ids, CAST({$departmentId} AS JSON))");
        
        if ($status) {
            $builder->where('status', $status);
        }
        
        $builder->orderBy('created_at', 'DESC');
        $builder->limit($limit, $offset);
        
        return $builder->get()->getResultArray();
    }

    /**
     * 청구 기간별 통계 조회
     */
    public function getBillingStatistics($startDate, $endDate, $customerId = null, $departmentId = null)
    {
        $builder = $this->builder();
        $builder->select('
            COUNT(*) as total_count,
            SUM(final_amount) as total_amount,
            COUNT(CASE WHEN status = "paid" THEN 1 END) as paid_count,
            SUM(CASE WHEN status = "paid" THEN final_amount ELSE 0 END) as paid_amount,
            COUNT(CASE WHEN status = "pending" THEN 1 END) as pending_count,
            SUM(CASE WHEN status = "pending" THEN final_amount ELSE 0 END) as pending_amount,
            COUNT(CASE WHEN status = "overdue" THEN 1 END) as overdue_count,
            SUM(CASE WHEN status = "overdue" THEN final_amount ELSE 0 END) as overdue_amount
        ');
        
        $builder->where('billing_date >=', $startDate);
        $builder->where('billing_date <=', $endDate);
        
        if ($customerId) {
            $builder->groupStart();
            $builder->where('customer_id', $customerId);
            $builder->orWhere("JSON_CONTAINS(customer_ids, CAST({$customerId} AS JSON))");
            $builder->groupEnd();
        }
        
        if ($departmentId) {
            $builder->where("JSON_CONTAINS(department_ids, CAST({$departmentId} AS JSON))");
        }
        
        return $builder->get()->getRowArray();
    }

    /**
     * 청구서 상태 업데이트
     */
    public function updateBillingStatus($billingId, $status, $paymentStatus = null, $paymentMethod = null, $paymentDate = null)
    {
        $updateData = ['status' => $status];
        
        if ($paymentStatus !== null) {
            $updateData['payment_status'] = $paymentStatus;
        }
        
        if ($paymentMethod !== null) {
            $updateData['payment_method'] = $paymentMethod;
        }
        
        if ($paymentDate !== null) {
            $updateData['payment_date'] = $paymentDate;
        }
        
        return $this->update($billingId, $updateData);
    }

    /**
     * 청구서 상세 정보 조회 (뷰 사용)
     */
    public function getBillingRequestDetails($billingId)
    {
        $db = \Config\Database::connect();
        $builder = $db->table('v_billing_request_details');
        $builder->where('billing_request_id', $billingId);
        
        return $builder->get()->getRowArray();
    }

    /**
     * 청구서 생성 (주문 데이터 기반)
     */
    public function createBillingFromOrders($billingData, $orderIds)
    {
        $db = \Config\Database::connect();
        $db->transStart();
        
        try {
            // 청구서 생성
            $billingId = $this->insert($billingData);
            
            if (!$billingId) {
                throw new \Exception('청구서 생성에 실패했습니다.');
            }
            
            // 청구 상세 내역 생성
            $billingDetailModel = new \App\Models\BillingDetailModel();
            $totalAmount = 0;
            
            foreach ($orderIds as $orderId) {
                $orderModel = new \App\Models\OrderModel();
                $order = $orderModel->find($orderId);
                
                if ($order) {
                    $detailData = [
                        'billing_request_id' => $billingId,
                        'order_id' => $orderId,
                        'department_id' => $order['department_id'],
                        'customer_id' => $order['customer_id'],
                        'order_number' => $order['order_number'],
                        'service_type_name' => $this->getServiceTypeName($order['service_type_id']),
                        'order_date' => date('Y-m-d', strtotime($order['created_at'])),
                        'completion_date' => $order['status'] === 'completed' ? date('Y-m-d', strtotime($order['updated_at'])) : null,
                        'base_amount' => $order['total_amount'],
                        'final_amount' => $order['total_amount']
                    ];
                    
                    $billingDetailModel->insert($detailData);
                    $totalAmount += $order['total_amount'];
                }
            }
            
            // 청구서 총액 업데이트
            $taxAmount = $totalAmount * 0.1; // 10% 부가세
            $finalAmount = $totalAmount + $taxAmount;
            
            $this->update($billingId, [
                'total_amount' => $totalAmount,
                'tax_amount' => $taxAmount,
                'final_amount' => $finalAmount
            ]);
            
            $db->transComplete();
            
            if ($db->transStatus() === false) {
                throw new \Exception('청구서 생성 중 오류가 발생했습니다.');
            }
            
            return $billingId;
            
        } catch (\Exception $e) {
            $db->transRollback();
            throw $e;
        }
    }

    /**
     * 서비스 타입명 조회
     */
    private function getServiceTypeName($serviceTypeId)
    {
        $serviceTypeModel = new \App\Models\ServiceTypeModel();
        $serviceType = $serviceTypeModel->find($serviceTypeId);
        return $serviceType ? $serviceType['service_name'] : 'Unknown';
    }

    /**
     * 청구서 검색
     */
    public function searchBillingRequests($searchTerm, $filters = [], $limit = 20, $offset = 0)
    {
        $builder = $this->builder();
        
        if (!empty($searchTerm)) {
            $builder->groupStart();
            $builder->like('billing_number', $searchTerm);
            $builder->orLike('billing_notes', $searchTerm);
            $builder->groupEnd();
        }
        
        if (isset($filters['billing_type'])) {
            $builder->where('billing_type', $filters['billing_type']);
        }
        
        if (isset($filters['status'])) {
            $builder->where('status', $filters['status']);
        }
        
        if (isset($filters['payment_status'])) {
            $builder->where('payment_status', $filters['payment_status']);
        }
        
        if (isset($filters['start_date'])) {
            $builder->where('billing_date >=', $filters['start_date']);
        }
        
        if (isset($filters['end_date'])) {
            $builder->where('billing_date <=', $filters['end_date']);
        }
        
        $builder->orderBy('created_at', 'DESC');
        $builder->limit($limit, $offset);
        
        return $builder->get()->getResultArray();
    }
}
