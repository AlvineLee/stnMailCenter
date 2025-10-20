<?php

namespace App\Models;

use CodeIgniter\Model;

class OrderModel extends Model
{
    protected $table = 'tbl_orders';
    protected $primaryKey = 'id';
    protected $useAutoIncrement = true;
    protected $returnType = 'array';
    protected $useSoftDeletes = false;
    protected $protectFields = true;
    protected $allowedFields = [
        'user_id',
        'customer_id',
        'department_id',
        'service_type_id',
        'order_number',
        'company_name',
        'contact',
        'address',
        'departure_address',
        'departure_detail',
        'departure_contact',
        'waypoint_address',
        'waypoint_detail',
        'waypoint_contact',
        'waypoint_notes',
        'destination_type',
        'mailroom',
        'destination_address',
        'detail_address',
        'destination_contact',
        'item_type',
        'quantity',
        'unit',
        'delivery_content',
        'status',
        'total_amount',
        'payment_type',
        'notes'
    ];

    // Dates
    protected $useTimestamps = true;
    protected $dateFormat = 'datetime';
    protected $createdField = 'created_at';
    protected $updatedField = 'updated_at';
    protected $deletedField = 'deleted_at';

    // Validation
    protected $validationRules = [
        'user_id' => 'required|integer',
        'customer_id' => 'required|integer',
        'service_type_id' => 'required|integer',
        'company_name' => 'required|max_length[100]',
        'contact' => 'required|max_length[20]',
        'departure_address' => 'required',
        'destination_address' => 'required',
        'item_type' => 'required|max_length[50]',
        'delivery_content' => 'required',
        'status' => 'permit_empty|in_list[pending,processing,completed,cancelled]'
    ];

    protected $validationMessages = [
        'user_id' => [
            'required' => '사용자 ID는 필수입니다.',
            'integer' => '사용자 ID는 정수여야 합니다.'
        ],
        'service_type' => [
            'required' => '서비스 타입은 필수입니다.',
            'max_length' => '서비스 타입은 최대 50자까지 가능합니다.'
        ],
        'service_name' => [
            'required' => '서비스명은 필수입니다.',
            'max_length' => '서비스명은 최대 100자까지 가능합니다.'
        ],
        'company_name' => [
            'required' => '회사명은 필수입니다.',
            'max_length' => '회사명은 최대 100자까지 가능합니다.'
        ],
        'contact' => [
            'required' => '연락처는 필수입니다.',
            'max_length' => '연락처는 최대 20자까지 가능합니다.'
        ],
        'departure_address' => [
            'required' => '출발지 주소는 필수입니다.',
            'max_length' => '출발지 주소는 최대 255자까지 가능합니다.'
        ],
        'destination_address' => [
            'required' => '도착지 주소는 필수입니다.',
            'max_length' => '도착지 주소는 최대 255자까지 가능합니다.'
        ],
        'item_type' => [
            'required' => '물품 타입은 필수입니다.',
            'max_length' => '물품 타입은 최대 50자까지 가능합니다.'
        ],
        'delivery_content' => [
            'required' => '전달 내용은 필수입니다.',
            'max_length' => '전달 내용은 최대 1000자까지 가능합니다.'
        ],
        'status' => [
            'required' => '상태는 필수입니다.',
            'in_list' => '올바른 상태값이 아닙니다.'
        ]
    ];

    protected $skipValidation = false;
    protected $cleanValidationRules = true;

    // Callbacks
    protected $allowCallbacks = true;
    protected $beforeInsert = ['generateOrderNumber'];
    protected $afterInsert = [];
    protected $beforeUpdate = [];
    protected $afterUpdate = [];
    protected $beforeDelete = [];
    protected $afterDelete = [];

    /**
     * 주문번호 자동 생성
     */
    protected function generateOrderNumber(array $data)
    {
        if (!isset($data['data']['order_number']) || empty($data['data']['order_number'])) {
            $date = date('Ymd');
            $prefix = "ORD-{$date}-";
            
            $builder = $this->builder();
            $builder->like('order_number', $prefix);
            $count = $builder->countAllResults();
            
            $data['data']['order_number'] = $prefix . str_pad($count + 1, 4, '0', STR_PAD_LEFT);
        }
        
        return $data;
    }

    /**
     * 사용자별 주문 목록 조회
     */
    public function getOrdersByUser($userId)
    {
        return $this->where('user_id', $userId)
                   ->orderBy('created_at', 'DESC')
                   ->findAll();
    }

    /**
     * 상태별 주문 목록 조회
     */
    public function getOrdersByStatus($status)
    {
        return $this->where('status', $status)
                   ->orderBy('created_at', 'DESC')
                   ->findAll();
    }

    /**
     * 서비스 타입별 주문 목록 조회
     */
    public function getOrdersByServiceType($serviceTypeId)
    {
        return $this->where('service_type_id', $serviceTypeId)
                   ->orderBy('created_at', 'DESC')
                   ->findAll();
    }

    /**
     * 고객사별 주문 목록 조회
     */
    public function getOrdersByCustomer($customerId)
    {
        return $this->where('customer_id', $customerId)
                   ->orderBy('created_at', 'DESC')
                   ->findAll();
    }

    /**
     * 부서별 주문 목록 조회
     */
    public function getOrdersByDepartment($departmentId)
    {
        return $this->where('department_id', $departmentId)
                   ->orderBy('created_at', 'DESC')
                   ->findAll();
    }

    /**
     * 주문과 사용자 정보 조인 조회
     */
    public function getOrdersWithUserInfo()
    {
        return $this->select('orders.*, users.username, users.company_name as user_company')
                   ->join('users', 'orders.user_id = users.id', 'left')
                   ->orderBy('orders.created_at', 'DESC')
                   ->findAll();
    }

    /**
     * 특정 기간 주문 조회
     */
    public function getOrdersByDateRange($startDate, $endDate)
    {
        return $this->where('created_at >=', $startDate)
                   ->where('created_at <=', $endDate)
                   ->orderBy('created_at', 'DESC')
                   ->findAll();
    }

    /**
     * 주문 통계 조회
     */
    public function getOrderStats($userId = null)
    {
        $builder = $this->builder();
        
        if ($userId) {
            $builder->where('user_id', $userId);
        }
        
        $stats = [
            'total' => $builder->countAllResults(false),
            'pending' => $builder->where('status', 'pending')->countAllResults(false),
            'processing' => $builder->where('status', 'processing')->countAllResults(false),
            'completed' => $builder->where('status', 'completed')->countAllResults(false),
            'cancelled' => $builder->where('status', 'cancelled')->countAllResults(false)
        ];
        
        return $stats;
    }

    /**
     * 주문 상태 업데이트
     */
    public function updateOrderStatus($orderId, $status)
    {
        return $this->update($orderId, ['status' => $status]);
    }
}
