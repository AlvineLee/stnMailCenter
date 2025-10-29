<?php

namespace App\Models;

use CodeIgniter\Model;

class InternationalProductModel extends Model
{
    protected $table = 'tbl_orders_international_products';
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

    // Validation
    protected $validationRules = [
        'order_id' => 'required|integer',
        'product_name' => 'required|max_length[255]',
        'product_quantity' => 'required|integer|greater_than[0]',
        'product_weight' => 'permit_empty|decimal',
        'product_width' => 'permit_empty|decimal',
        'product_length' => 'permit_empty|decimal',
        'product_height' => 'permit_empty|decimal',
        'product_hs_code' => 'permit_empty|max_length[20]'
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
     * 주문 ID로 물품 상세 정보 저장
     */
    public function saveProductDetails($orderId, $productDetails)
    {
        if (empty($productDetails)) {
            return false;
        }

        // 기존 데이터 삭제
        $this->where('order_id', $orderId)->delete();

        // 새 데이터 삽입
        $data = [];
        foreach ($productDetails as $product) {
            $data[] = [
                'order_id' => $orderId,
                'product_name' => $product['product_name'],
                'product_quantity' => $product['product_quantity'],
                'product_weight' => $product['product_weight'],
                'product_width' => $product['product_width'],
                'product_length' => $product['product_length'],
                'product_height' => $product['product_height'],
                'product_hs_code' => $product['product_hs_code']
            ];
        }

        return $this->insertBatch($data);
    }

    /**
     * 주문 ID로 물품 상세 정보 조회
     */
    public function getProductDetailsByOrderId($orderId)
    {
        return $this->where('order_id', $orderId)->findAll();
    }
}
