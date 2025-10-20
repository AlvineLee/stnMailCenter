<?php

namespace App\Models;

use CodeIgniter\Model;

class BillingDetailModel extends Model
{
    protected $table = 'tbl_billing_details';
    protected $primaryKey = 'id';
    protected $useAutoIncrement = true;
    protected $returnType = 'array';
    protected $useSoftDeletes = false;
    protected $protectFields = true;
    protected $allowedFields = [
        'billing_request_id',
        'order_id',
        'department_id',
        'customer_id',
        'order_number',
        'service_type_name',
        'order_date',
        'completion_date',
        'base_amount',
        'discount_amount',
        'additional_fee',
        'tax_amount',
        'final_amount',
        'is_included',
        'billing_notes'
    ];

    protected $useTimestamps = true;
    protected $dateFormat = 'datetime';
    protected $createdField = 'created_at';
    protected $updatedField = 'updated_at';

    protected $validationRules = [
        'billing_request_id' => 'required|integer',
        'order_id' => 'required|integer',
        'customer_id' => 'required|integer',
        'order_number' => 'required|max_length[50]',
        'service_type_name' => 'required|max_length[100]',
        'order_date' => 'required|valid_date',
        'base_amount' => 'required|decimal',
        'final_amount' => 'required|decimal',
        'is_included' => 'permit_empty|in_list[0,1]'
    ];

    protected $validationMessages = [
        'billing_request_id' => [
            'required' => '청구 요청 ID는 필수입니다.',
            'integer' => '청구 요청 ID는 정수여야 합니다.'
        ],
        'order_id' => [
            'required' => '주문 ID는 필수입니다.',
            'integer' => '주문 ID는 정수여야 합니다.'
        ],
        'order_number' => [
            'required' => '주문번호는 필수입니다.',
            'max_length' => '주문번호는 50자를 초과할 수 없습니다.'
        ]
    ];

    protected $skipValidation = false;
    protected $cleanValidationRules = true;

    /**
     * 청구서별 상세 내역 조회
     */
    public function getBillingDetailsByRequest($billingRequestId, $includedOnly = true)
    {
        $builder = $this->builder();
        $builder->where('billing_request_id', $billingRequestId);
        
        if ($includedOnly) {
            $builder->where('is_included', 1);
        }
        
        $builder->orderBy('order_date', 'DESC');
        
        return $builder->get()->getResultArray();
    }

    /**
     * 주문별 청구 내역 조회
     */
    public function getBillingDetailsByOrder($orderId)
    {
        $builder = $this->builder();
        $builder->where('order_id', $orderId);
        $builder->orderBy('created_at', 'DESC');
        
        return $builder->get()->getResultArray();
    }

    /**
     * 부서별 청구 상세 통계
     */
    public function getDepartmentBillingStats($departmentId, $startDate = null, $endDate = null)
    {
        $builder = $this->builder();
        $builder->select('
            COUNT(*) as total_orders,
            SUM(final_amount) as total_amount,
            SUM(base_amount) as base_amount,
            SUM(discount_amount) as total_discount,
            SUM(additional_fee) as total_additional_fee,
            SUM(tax_amount) as total_tax
        ');
        
        $builder->where('department_id', $departmentId);
        $builder->where('is_included', 1);
        
        if ($startDate) {
            $builder->where('order_date >=', $startDate);
        }
        if ($endDate) {
            $builder->where('order_date <=', $endDate);
        }
        
        return $builder->get()->getRowArray();
    }

    /**
     * 고객사별 청구 상세 통계
     */
    public function getCustomerBillingStats($customerId, $startDate = null, $endDate = null)
    {
        $builder = $this->builder();
        $builder->select('
            COUNT(*) as total_orders,
            SUM(final_amount) as total_amount,
            SUM(base_amount) as base_amount,
            SUM(discount_amount) as total_discount,
            SUM(additional_fee) as total_additional_fee,
            SUM(tax_amount) as total_tax
        ');
        
        $builder->where('customer_id', $customerId);
        $builder->where('is_included', 1);
        
        if ($startDate) {
            $builder->where('order_date >=', $startDate);
        }
        if ($endDate) {
            $builder->where('order_date <=', $endDate);
        }
        
        return $builder->get()->getRowArray();
    }

    /**
     * 서비스 타입별 청구 통계
     */
    public function getServiceTypeBillingStats($startDate = null, $endDate = null, $customerId = null, $departmentId = null)
    {
        $builder = $this->builder();
        $builder->select('
            service_type_name,
            COUNT(*) as order_count,
            SUM(final_amount) as total_amount,
            AVG(final_amount) as avg_amount
        ');
        
        $builder->where('is_included', 1);
        
        if ($startDate) {
            $builder->where('order_date >=', $startDate);
        }
        if ($endDate) {
            $builder->where('order_date <=', $endDate);
        }
        if ($customerId) {
            $builder->where('customer_id', $customerId);
        }
        if ($departmentId) {
            $builder->where('department_id', $departmentId);
        }
        
        $builder->groupBy('service_type_name');
        $builder->orderBy('total_amount', 'DESC');
        
        return $builder->get()->getResultArray();
    }

    /**
     * 청구서 포함/제외 토글
     */
    public function toggleIncluded($detailId, $included)
    {
        return $this->update($detailId, ['is_included' => $included ? 1 : 0]);
    }

    /**
     * 청구 상세 금액 업데이트
     */
    public function updateBillingAmounts($detailId, $amounts)
    {
        $updateData = [];
        
        if (isset($amounts['base_amount'])) {
            $updateData['base_amount'] = $amounts['base_amount'];
        }
        if (isset($amounts['discount_amount'])) {
            $updateData['discount_amount'] = $amounts['discount_amount'];
        }
        if (isset($amounts['additional_fee'])) {
            $updateData['additional_fee'] = $amounts['additional_fee'];
        }
        if (isset($amounts['tax_amount'])) {
            $updateData['tax_amount'] = $amounts['tax_amount'];
        }
        
        // 최종 금액 계산
        $baseAmount = $amounts['base_amount'] ?? $this->find($detailId)['base_amount'];
        $discountAmount = $amounts['discount_amount'] ?? $this->find($detailId)['discount_amount'];
        $additionalFee = $amounts['additional_fee'] ?? $this->find($detailId)['additional_fee'];
        $taxAmount = $amounts['tax_amount'] ?? $this->find($detailId)['tax_amount'];
        
        $updateData['final_amount'] = $baseAmount - $discountAmount + $additionalFee + $taxAmount;
        
        return $this->update($detailId, $updateData);
    }

    /**
     * 청구서별 총액 재계산
     */
    public function recalculateBillingTotal($billingRequestId)
    {
        $builder = $this->builder();
        $builder->select('
            SUM(base_amount) as total_base,
            SUM(discount_amount) as total_discount,
            SUM(additional_fee) as total_additional,
            SUM(tax_amount) as total_tax,
            SUM(final_amount) as total_final
        ');
        
        $builder->where('billing_request_id', $billingRequestId);
        $builder->where('is_included', 1);
        
        $result = $builder->get()->getRowArray();
        
        if ($result) {
            $billingModel = new \App\Models\BillingModel();
            $billingModel->update($billingRequestId, [
                'total_amount' => $result['total_base'] - $result['total_discount'] + $result['total_additional'],
                'tax_amount' => $result['total_tax'],
                'final_amount' => $result['total_final']
            ]);
        }
        
        return $result;
    }

    /**
     * 미청구 주문 조회 (특정 기간, 고객사/부서별)
     */
    public function getUnbilledOrders($customerId = null, $departmentId = null, $startDate = null, $endDate = null)
    {
        $db = \Config\Database::connect();
        $builder = $db->table('tbl_orders o');
        
        $builder->select('
            o.id,
            o.order_number,
            o.service_type_id,
            o.customer_id,
            o.department_id,
            o.total_amount,
            o.created_at,
            o.status,
            st.service_name,
            ch.company_name,
            d.department_name
        ');
        
        $builder->join('tbl_service_types st', 'o.service_type_id = st.id', 'left');
        $builder->join('tbl_customer_hierarchy ch', 'o.customer_id = ch.id', 'left');
        $builder->join('tbl_departments d', 'o.department_id = d.id', 'left');
        
        // 이미 청구된 주문 제외
        $builder->where('o.id NOT IN (SELECT order_id FROM tbl_billing_details WHERE is_included = 1)', null, false);
        
        // 완료된 주문만
        $builder->where('o.status', 'completed');
        
        if ($customerId) {
            $builder->where('o.customer_id', $customerId);
        }
        
        if ($departmentId) {
            $builder->where('o.department_id', $departmentId);
        }
        
        if ($startDate) {
            $builder->where('o.created_at >=', $startDate);
        }
        
        if ($endDate) {
            $builder->where('o.created_at <=', $endDate);
        }
        
        $builder->orderBy('o.created_at', 'ASC');
        
        return $builder->get()->getResultArray();
    }
}
