<?php

namespace App\Models;

use CodeIgniter\Model;

class InternationalProductDetailModel extends Model
{
    protected $table = 'tbl_international_product_details';
    protected $primaryKey = 'id';
    protected $useAutoIncrement = true;
    protected $returnType = 'array';
    protected $useSoftDeletes = false;
    protected $protectFields = true;
    protected $allowedFields = [
        'order_id',
        'product_name',
        'product_quantity',
        'product_weight',
        'product_width',
        'product_length',
        'product_height',
        'product_hs_code'
    ];

    // Dates
    protected $useTimestamps = true;
    protected $dateFormat = 'datetime';
    protected $createdField = 'created_at';
    protected $updatedField = 'updated_at';
    protected $deletedField = 'deleted_at';

    // Validation
    protected $validationRules = [
        'order_id' => 'required|integer',
        'product_name' => 'required|max_length[255]',
        'product_quantity' => 'required|integer|greater_than[0]',
        'product_weight' => 'permit_empty|decimal|greater_than_equal_to[0]',
        'product_width' => 'permit_empty|decimal|greater_than_equal_to[0]',
        'product_length' => 'permit_empty|decimal|greater_than_equal_to[0]',
        'product_height' => 'permit_empty|decimal|greater_than_equal_to[0]',
        'product_hs_code' => 'permit_empty|max_length[50]'
    ];

    protected $validationMessages = [
        'order_id' => [
            'required' => '주문 ID는 필수입니다.',
            'integer' => '주문 ID는 정수여야 합니다.'
        ],
        'product_name' => [
            'required' => '발송품명은 필수입니다.',
            'max_length' => '발송품명은 255자를 초과할 수 없습니다.'
        ],
        'product_quantity' => [
            'required' => '물품개수는 필수입니다.',
            'integer' => '물품개수는 정수여야 합니다.',
            'greater_than' => '물품개수는 0보다 커야 합니다.'
        ],
        'product_weight' => [
            'decimal' => '무게는 숫자여야 합니다.',
            'greater_than_equal_to' => '무게는 0 이상이어야 합니다.'
        ],
        'product_width' => [
            'decimal' => '가로는 숫자여야 합니다.',
            'greater_than_equal_to' => '가로는 0 이상이어야 합니다.'
        ],
        'product_length' => [
            'decimal' => '세로는 숫자여야 합니다.',
            'greater_than_equal_to' => '세로는 0 이상이어야 합니다.'
        ],
        'product_height' => [
            'decimal' => '높이는 숫자여야 합니다.',
            'greater_than_equal_to' => '높이는 0 이상이어야 합니다.'
        ],
        'product_hs_code' => [
            'max_length' => 'HS-code는 50자를 초과할 수 없습니다.'
        ]
    ];

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
     * 주문 ID로 물품 상세 정보 조회
     */
    public function getByOrderId($orderId)
    {
        return $this->where('order_id', $orderId)->findAll();
    }

    /**
     * 주문 ID로 물품 상세 정보 삭제
     */
    public function deleteByOrderId($orderId)
    {
        return $this->where('order_id', $orderId)->delete();
    }

    /**
     * 주문 ID로 물품 상세 정보 일괄 저장
     */
    public function saveProductDetails($orderId, $productDetails)
    {
        // 기존 데이터 삭제
        $this->deleteByOrderId($orderId);
        
        // 새 데이터 저장
        $data = [];
        foreach ($productDetails as $detail) {
            $data[] = [
                'order_id' => $orderId,
                'product_name' => $detail['product_name'],
                'product_quantity' => $detail['product_quantity'],
                'product_weight' => $detail['product_weight'] ?? null,
                'product_width' => $detail['product_width'] ?? null,
                'product_length' => $detail['product_length'] ?? null,
                'product_height' => $detail['product_height'] ?? null,
                'product_hs_code' => $detail['product_hs_code'] ?? null,
            ];
        }
        
        return $this->insertBatch($data);
    }
}
