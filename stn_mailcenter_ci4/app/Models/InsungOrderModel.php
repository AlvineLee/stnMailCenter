<?php

namespace App\Models;

use CodeIgniter\Model;

class InsungOrderModel extends Model
{
    protected $table = 'tbl_orders_insung';
    protected $primaryKey = 'id';
    protected $useAutoIncrement = true;
    protected $returnType = 'array';
    protected $useSoftDeletes = false;
    protected $protectFields = true;
    
    protected $allowedFields = [
        'order_id',
        'ins_type',
        'ins_m_code',
        'ins_cc_code',
        'ins_user_id',
        'ins_token',
        'ins_c_name',
        'ins_c_mobile',
        'ins_c_dept_name',
        'ins_c_charge_name',
        'ins_reason_desc',
        'ins_s_start',
        'ins_start_telno',
        'ins_dept_name',
        'ins_charge_name',
        'ins_start_sido',
        'ins_start_gugun',
        'ins_start_dong',
        'ins_start_lon',
        'ins_start_lat',
        'ins_start_location',
        'ins_s_dest',
        'ins_dest_telno',
        'ins_dest_dept',
        'ins_dest_charge',
        'ins_dest_sido',
        'ins_dest_gugun',
        'ins_dest_dong',
        'ins_dest_location',
        'ins_dest_lon',
        'ins_dest_lat',
        'ins_kind',
        'ins_kind_etc',
        'ins_pay_gbn',
        'ins_doc',
        'ins_sfast',
        'ins_item_type',
        'ins_memo',
        'ins_sms_telno',
        'ins_use_check',
        'ins_pickup_date',
        'ins_pick_hour',
        'ins_pick_min',
        'ins_pick_sec',
        'ins_price',
        'ins_s_c_code',
        'ins_d_c_code',
        'ins_add_cost',
        'ins_discount_cost',
        'ins_delivery_cost',
        'ins_car_kind',
        'ins_state',
        'ins_distince',
        'ins_o_c_code',
        'ins_serial_number'
    ];
    
    protected $useTimestamps = true;
    protected $dateFormat = 'datetime';
    protected $createdField = 'created_at';
    protected $updatedField = 'updated_at';
    
    protected $validationRules = [];
    protected $validationMessages = [];
    protected $skipValidation = false;
    protected $cleanValidationRules = true;
    
    protected $beforeInsert = [];
    protected $afterInsert = [];
    protected $beforeUpdate = [];
    protected $afterUpdate = [];
    protected $beforeFind = [];
    protected $afterFind = [];
    protected $beforeDelete = [];
    protected $afterDelete = [];
    
    /**
     * 인성 접수 데이터 저장 또는 업데이트
     * 
     * @param int $orderId tbl_orders.id
     * @param array $params 인성 API에 전송한 request body ($params 배열)
     * @param string|null $serialNumber 인성 API 응답의 serial_number (response 값)
     * @return bool|int 저장/업데이트 결과
     */
    public function saveInsungOrderData($orderId, $params, $serialNumber = null)
    {
        // ========== DB Insert 준비 로깅 (디버깅용) ==========
        log_message('debug', "========== InsungOrderModel::saveInsungOrderData - DB Insert 준비 ==========");
        log_message('debug', "Order ID: {$orderId}");
        log_message('debug', "Serial Number: " . ($serialNumber ?? 'null'));

        // 기존 데이터 확인
        $existing = $this->where('order_id', $orderId)->first();
        log_message('debug', "기존 데이터 존재 여부: " . ($existing ? 'YES (UPDATE)' : 'NO (INSERT)'));

        // 인성 API request body ($params)를 테이블 필드로 매핑
        $data = [
            'order_id' => $orderId,
            'ins_type' => $params['type'] ?? null,
            'ins_m_code' => $params['m_code'] ?? null,
            'ins_cc_code' => $params['cc_code'] ?? null,
            'ins_user_id' => $params['user_id'] ?? null,
            'ins_token' => $params['token'] ?? null,
            'ins_c_name' => $params['c_name'] ?? null,
            'ins_c_mobile' => $params['c_mobile'] ?? null,
            'ins_c_dept_name' => $params['c_dept_name'] ?? null,
            'ins_c_charge_name' => $params['c_charge_name'] ?? null,
            'ins_reason_desc' => $params['reason_desc'] ?? null,
            'ins_s_start' => $params['s_start'] ?? null,
            'ins_start_telno' => $params['start_telno'] ?? null,
            'ins_dept_name' => $params['dept_name'] ?? null,
            'ins_charge_name' => $params['charge_name'] ?? null,
            'ins_start_sido' => $params['start_sido'] ?? null,
            'ins_start_gugun' => $params['start_gugun'] ?? null,
            'ins_start_dong' => $params['start_dong'] ?? null,
            'ins_start_lon' => $params['start_lon'] ?? null,
            'ins_start_lat' => $params['start_lat'] ?? null,
            'ins_start_location' => $params['start_location'] ?? null,
            'ins_s_dest' => $params['s_dest'] ?? null,
            'ins_dest_telno' => $params['dest_telno'] ?? null,
            'ins_dest_dept' => $params['dest_dept'] ?? null,
            'ins_dest_charge' => $params['dest_charge'] ?? null,
            'ins_dest_sido' => $params['dest_sido'] ?? null,
            'ins_dest_gugun' => $params['dest_gugun'] ?? null,
            'ins_dest_dong' => $params['dest_dong'] ?? null,
            'ins_dest_location' => $params['dest_location'] ?? null,
            'ins_dest_lon' => $params['dest_lon'] ?? null,
            'ins_dest_lat' => $params['dest_lat'] ?? null,
            'ins_kind' => $params['kind'] ?? null,
            'ins_kind_etc' => $params['kind_etc'] ?? null,
            'ins_pay_gbn' => $params['pay_gbn'] ?? null,
            'ins_doc' => $params['doc'] ?? null,
            'ins_sfast' => $params['sfast'] ?? null,
            'ins_item_type' => $params['item_type'] ?? null,
            'ins_memo' => $params['memo'] ?? null,
            'ins_sms_telno' => $params['sms_telno'] ?? null,
            'ins_use_check' => $params['use_check'] ?? null,
            'ins_pickup_date' => $params['pickup_date'] ?? null,
            'ins_pick_hour' => $params['pick_hour'] ?? null,
            'ins_pick_min' => $params['pick_min'] ?? null,
            'ins_pick_sec' => $params['pick_sec'] ?? null,
            'ins_price' => $params['price'] ?? null,
            'ins_s_c_code' => $params['s_c_code'] ?? null,
            'ins_d_c_code' => $params['d_c_code'] ?? null,
            'ins_add_cost' => $params['add_cost'] ?? null,
            'ins_discount_cost' => $params['discount_cost'] ?? null,
            'ins_delivery_cost' => $params['delivery_cost'] ?? null,
            'ins_car_kind' => $params['car_kind'] ?? null,
            'ins_state' => $params['state'] ?? null,
            'ins_distince' => $params['distince'] ?? null,
            'ins_o_c_code' => $params['o_c_code'] ?? null,
            'ins_serial_number' => $serialNumber
        ];

        // 각 필드의 데이터 길이 로깅 (긴 필드만)
        log_message('debug', "--- DB Insert 데이터 필드 길이 체크 ---");
        foreach ($data as $field => $value) {
            if (is_string($value)) {
                $length = mb_strlen($value, 'UTF-8');
                if ($length > 50) { // 50자 이상인 필드만 로깅
                    log_message('debug', sprintf("  [%-25s] 길이: %d, 값: %s", $field, $length, mb_substr($value, 0, 100, 'UTF-8') . ($length > 100 ? '...' : '')));
                }
            }
        }

        log_message('debug', "--- Full DB Insert Data ---");
        log_message('debug', json_encode($data, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT));

        try {
            if ($existing) {
                // 업데이트
                log_message('debug', "DB Update 실행 중... (existing id: {$existing['id']})");
                $result = $this->update($existing['id'], $data);
                log_message('debug', "DB Update 결과: " . ($result ? 'SUCCESS' : 'FAILED'));
                log_message('debug', "==========================================================================");
                return $result;
            } else {
                // 신규 저장
                log_message('debug', "DB Insert 실행 중...");
                $result = $this->insert($data);
                $insertId = $this->getInsertID();
                log_message('debug', "DB Insert 결과: " . ($result ? "SUCCESS (Insert ID: {$insertId})" : 'FAILED'));
                if (!$result) {
                    // Insert 실패 시 에러 정보 로깅
                    $error = $this->errors();
                    log_message('error', "DB Insert Error: " . json_encode($error, JSON_UNESCAPED_UNICODE));
                }
                log_message('debug', "==========================================================================");
                return $result;
            }
        } catch (\Exception $e) {
            log_message('error', "========== DB Insert Exception ==========");
            log_message('error', "Exception Message: " . $e->getMessage());
            log_message('error', "Exception Code: " . $e->getCode());
            log_message('error', "Exception File: " . $e->getFile() . ":" . $e->getLine());
            log_message('error', "Exception Trace: " . $e->getTraceAsString());
            log_message('error', "==========================================");
            throw $e;
        }
    }

    /**
     * 인성 주문번호(serial_number)만 업데이트
     * API 호출 성공 후 serial_number 저장용
     *
     * @param int $orderId tbl_orders.id
     * @param string $serialNumber 인성 API 응답의 serial_number
     * @return bool 업데이트 결과
     */
    public function updateSerialNumber($orderId, $serialNumber)
    {
        $existing = $this->where('order_id', $orderId)->first();

        if ($existing) {
            return $this->update($existing['id'], ['ins_serial_number' => $serialNumber]);
        }

        return false;
    }
    
    /**
     * 주문 ID로 인성 접수 데이터 조회
     * 
     * @param int $orderId tbl_orders.id
     * @return array|null 인성 접수 데이터
     */
    public function getByOrderId($orderId)
    {
        return $this->where('order_id', $orderId)->first();
    }
    
    /**
     * 인성 주문번호로 인성 접수 데이터 조회
     * 
     * @param string $serialNumber 인성 주문번호
     * @return array|null 인성 접수 데이터
     */
    public function getBySerialNumber($serialNumber)
    {
        return $this->where('ins_serial_number', $serialNumber)->first();
    }
}
