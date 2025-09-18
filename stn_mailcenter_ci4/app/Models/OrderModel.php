<?php

namespace App\Models;

use CodeIgniter\Model;

class OrderModel extends Model
{
    protected $table = 'orders';
    protected $primaryKey = 'id';
    protected $useAutoIncrement = true;
    protected $returnType = 'array';
    protected $useSoftDeletes = false;
    protected $protectFields = true;
    protected $allowedFields = [
        'user_id',
        'service_type',
        'service_name',
        'company_name',
        'contact',
        'address',
        'departure_address',
        'departure_detail',
        'departure_contact',
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
        'created_at',
        'updated_at'
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
        'service_type' => 'required|max_length[50]',
        'service_name' => 'required|max_length[100]',
        'company_name' => 'required|max_length[100]',
        'contact' => 'required|max_length[20]',
        'departure_address' => 'required|max_length[255]',
        'destination_address' => 'required|max_length[255]',
        'item_type' => 'required|max_length[50]',
        'delivery_content' => 'required|max_length[1000]',
        'status' => 'required|in_list[pending,processing,completed,cancelled]'
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
    public function getOrdersByServiceType($serviceType)
    {
        return $this->where('service_type', $serviceType)
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
