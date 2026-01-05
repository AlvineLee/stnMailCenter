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
        'insung_order_number',
        'order_system',
        'company_name',
        'contact',
        'address',
        'sms_telno',
        'o_c_code',
        'departure_company_name',
        'departure_contact',
        'departure_department',
        'departure_manager',
        'departure_dong',
        'departure_address',
        'departure_detail',
        'departure_lon',
        'departure_lat',
        's_c_code',
        'waypoint_address',
        'waypoint_detail',
        'waypoint_contact',
        'waypoint_notes',
        'destination_type',
        'mailroom',
        'destination_company_name',
        'destination_contact',
        'destination_department',
        'destination_manager',
        'destination_dong',
        'destination_address',
        'detail_address',
        'destination_lon',
        'destination_lat',
        'd_c_code',
        'item_type',
        'quantity',
        'unit',
        'delivery_content',
        'box_medium_overload',
        'pouch_medium_overload',
        'bag_medium_overload',
        'call_type',
        'total_fare',
        'postpaid_fare',
        'distance',
        'cash_fare',
        'status',
        'total_amount',
        'add_cost',
        'discount_cost',
        'delivery_cost',
        'car_kind',
        'state',
        'payment_type',
        'notes',
        'reserve_check',
        'reserve_date',
        'reserve_hour',
        'reserve_min',
        'reserve_sec',
        'order_date',
        'order_time',
        'notification_service',
        'shipping_platform_code',
        'shipping_tracking_number',
        'rider_code_no',
        'rider_name',
        'rider_tel_number',
        'rider_lon',
        'rider_lat',
        'customer_name',
        'customer_tel_number',
        'customer_department',
        'customer_duty',
        'allocation_time',
        'pickup_time',
        'resolve_time',
        'complete_time',
        'reason',
        'order_regist_type',
        'doc',
        'sfast',
        'happy_call',
        'car_type',
        'cargo_name'
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
        'departure_company_name' => 'permit_empty|max_length[100]',
        'departure_contact' => 'permit_empty|max_length[20]',
        'departure_address' => 'permit_empty',
        'destination_company_name' => 'permit_empty|max_length[100]',
        'destination_contact' => 'permit_empty|max_length[20]',
        'destination_address' => 'permit_empty',
        'item_type' => 'permit_empty|max_length[50]',
        'delivery_content' => 'permit_empty',
        'status' => 'permit_empty|in_list[pending,processing,completed,delivered,cancelled,api_failed]',
        'order_date' => 'permit_empty|valid_date',
        'order_time' => 'permit_empty|max_length[8]',
        'notification_service' => 'permit_empty|in_list[0,1]'
    ];

    protected $validationMessages = [
        'order_date' => [
            'valid_date' => '예약 날짜는 올바른 날짜 형식이어야 합니다.'
        ],
        'order_time' => [
            'max_length' => '예약 시간은 최대 8자까지 가능합니다.'
        ],
        'departure_company_name' => [
            'required' => '출발지 상호(이름)는 필수입니다.',
            'max_length' => '출발지 상호(이름)는 최대 100자까지 가능합니다.'
        ],
        'departure_contact' => [
            'required' => '출발지 연락처는 필수입니다.',
            'max_length' => '출발지 연락처는 최대 20자까지 가능합니다.'
        ],
        'destination_company_name' => [
            'required' => '도착지 상호(이름)는 필수입니다.',
            'max_length' => '도착지 상호(이름)는 최대 100자까지 가능합니다.'
        ],
        'destination_contact' => [
            'required' => '도착지 연락처는 필수입니다.',
            'max_length' => '도착지 연락처는 최대 20자까지 가능합니다.'
        ],
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

    /**
     * 주문 저장
     */
    public function createOrder($orderData)
    {
        try {
            // DB 연결 테스트
            $db = \Config\Database::connect();
            $db->query('SELECT 1');
            
            $orderId = $this->insert($orderData);
            
            if (!$orderId) {
                $errors = $this->errors();
                log_message('error', 'Order insert failed: ' . json_encode($errors));
                log_message('error', 'Order data: ' . json_encode($orderData));
                
                // DB 연결 에러인지 확인
                if (!empty($errors)) {
                    foreach ($errors as $error) {
                        if (strpos($error, 'Unable to connect') !== false || 
                            strpos($error, 'Connection refused') !== false ||
                            strpos($error, 'Access denied') !== false) {
                            throw new \Exception('DB 연결 실패: ' . $error);
                        }
                    }
                }
                
                return false;
            }
            
            return $orderId;
        } catch (\Exception $e) {
            log_message('error', 'OrderModel::createOrder exception: ' . $e->getMessage());
            throw $e; // 상위로 예외 전파
        }
    }

    /**
     * 운송 정보 업데이트 (플랫폼코드, 송장번호)
     */
    public function updateShippingInfo($orderId, $platformCode, $trackingNumber = null)
    {
        $updateData = [
            'shipping_platform_code' => $platformCode
        ];
        
        if ($trackingNumber !== null) {
            $updateData['shipping_tracking_number'] = $trackingNumber;
        }
        
        return $this->update($orderId, $updateData);
    }

    /**
     * 운송 정보 조회
     */
    public function getShippingInfo($orderId)
    {
        $order = $this->select('shipping_tracking_number, shipping_platform_code')
                     ->find($orderId);
        
        return $order ? [
            'shipping_tracking_number' => $order['shipping_tracking_number'] ?? '',
            'shipping_platform_code' => $order['shipping_platform_code'] ?? ''
        ] : null;
    }

    /**
     * 인성 API 주문 목록을 INSERT ON DUPLICATE KEY UPDATE로 저장
     * insung_order_number를 기준으로 중복 체크
     * 주문 상세 API와 동일한 응답 구조로 파싱
     * 
     * @param array $orders 인성 API에서 받은 주문 목록 (주문 상세와 동일한 구조)
     * @param int $userId 사용자 ID (tbl_users_list.idx) - 참고용 (더 이상 사용하지 않음)
     * @param int $customerId 고객사 ID
     * @param bool $isSelfOrderOnly 본인오더조회 모드 여부 (env1=3)
     * @param string $loginUserId 로그인한 사용자의 user_id (본인오더조회 필터링 및 기본값용)
     * @return array ['inserted' => int, 'updated' => int, 'errors' => array]
     */
    public function insertOrUpdateInsungOrders($orders, $userId, $customerId, $isSelfOrderOnly = false, $loginUserId = null)
    {
        $inserted = 0;
        $updated = 0;
        $errors = [];
        $reservationOrderNumbers = []; // 예약 상태인 주문들의 인성 주문번호
        
        if (empty($orders) || !is_array($orders)) {
            return [
                'inserted' => 0,
                'updated' => 0,
                'errors' => ['주문 목록이 비어있습니다.'],
                'reservation_order_numbers' => []
            ];
        }
        
        
        // 헬퍼 함수: 객체/배열에서 값 추출 (먼저 정의)
        $getValue = function($data, $key, $default = null) {
            if (is_object($data)) {
                return $data->$key ?? $default;
            } elseif (is_array($data)) {
                return $data[$key] ?? $default;
            }
            return $default;
        };
        
        // 인성 API 응답 구조: $orders[0]: 응답 코드, $orders[1]부터: 주문 데이터 배열
        // 주문 목록 API는 여러 주문을 배열로 반환할 수 있음
        $orderList = [];
        
        // 응답 구조 확인
        if (is_array($orders) && isset($orders[0])) {
            // $orders[0]이 응답 코드인 경우
            $responseCode = is_object($orders[0]) ? ($orders[0]->code ?? $orders[0]['code'] ?? '') : (is_array($orders[0]) ? ($orders[0]['code'] ?? $orders[0]->code ?? '') : '');
            // log_message('debug', 'OrderModel::insertOrUpdateInsungOrders - Response code: ' . $responseCode);
            
            // 주문 목록 API 응답 구조 확인
            // 일반적으로: $orders[0] = 응답 코드, $orders[1] = 주문 목록 배열 또는 $orders[2]부터 각 주문
            if (isset($orders[1])) {
                if (is_array($orders[1])) {
                    // $orders[1]이 배열인 경우 (주문 목록 배열)
                    $orderList = $orders[1];
                    // log_message('debug', 'OrderModel::insertOrUpdateInsungOrders - Found order list in $orders[1], count: ' . count($orderList));
                } elseif (is_object($orders[1])) {
                    // 객체인 경우 - 주문 목록 컨테이너 객체일 수도 있음
                    // 객체 내부에 주문 목록 배열이 있을 수 있음 (예: $orders[1]->orders)
                    $orderListFromObject = $getValue($orders[1], 'orders') ?? $getValue($orders[1], 'order_list') ?? $getValue($orders[1], 'data');
                    if ($orderListFromObject && is_array($orderListFromObject)) {
                        $orderList = $orderListFromObject;
                        // log_message('debug', 'OrderModel::insertOrUpdateInsungOrders - Found order list in $orders[1] object property, count: ' . count($orderList));
                    }
                }
            }
            
            // $orders[2]부터 각 주문이 있는 경우 (주문 목록 API의 일반적인 구조)
            // 주문 목록 API는 $orders[2]부터 각 주문이 배열/객체로 들어있을 수 있음
            if (empty($orderList)) {
                foreach ($orders as $idx => $item) {
                    if ($idx > 1) { // $orders[0]: 응답 코드, $orders[1]: 메타 정보 또는 첫 주문
                        if (is_array($item) || is_object($item)) {
                            $orderList[] = $item;
                        }
                    }
                }
                if (!empty($orderList)) {
                    // log_message('debug', 'OrderModel::insertOrUpdateInsungOrders - Extracted orders from $orders[2] onwards, count: ' . count($orderList));
                }
            }
            
            // 여전히 없으면 $orders[1]을 단일 주문으로 처리
            if (empty($orderList) && isset($orders[1]) && (is_array($orders[1]) || is_object($orders[1]))) {
                $orderList = [$orders[1]];
                // log_message('debug', 'OrderModel::insertOrUpdateInsungOrders - Using $orders[1] as single order');
            }
        } elseif (is_array($orders)) {
            // $orders 자체가 주문 목록인 경우
            $orderList = $orders;
            // log_message('debug', 'OrderModel::insertOrUpdateInsungOrders - $orders is order list itself, count: ' . count($orderList));
        }
        
        if (empty($orderList)) {
            log_message('warning', 'OrderModel::insertOrUpdateInsungOrders - No order data found. Orders structure: ' . json_encode($orders, JSON_UNESCAPED_UNICODE));
            return [
                'inserted' => 0,
                'updated' => 0,
                'errors' => ['주문 데이터를 찾을 수 없습니다.'],
                'reservation_order_numbers' => []
            ];
        }
        
        // log_message('info', 'OrderModel::insertOrUpdateInsungOrders - Processing ' . count($orderList) . ' orders');
        
        // 헬퍼 함수: 숫자 변환 (원, 쉼표 제거)
        $parseAmount = function($value) {
            if (empty($value)) return null;
            $cleaned = str_replace(['원', ',', ' '], '', $value);
            return is_numeric($cleaned) ? (float)$cleaned : null;
        };
        
        // 헬퍼 함수: 날짜/시간 파싱
        $parseDateTime = function($value) {
            if (empty($value)) return null;
            $timestamp = strtotime($value);
            return $timestamp ? date('Y-m-d H:i:s', $timestamp) : null;
        };
        
        // 상태 매핑 함수 (Delivery.php와 동일)
        $mapInsungStatusToLocal = function($saveState) {
            $statusMap = [
                '10' => 'pending',      // 접수
                '11' => 'processing',   // 배차
                '12' => 'processing',   // 운행
                '20' => 'pending',      // 대기
                '30' => 'completed',    // 완료
                '40' => 'cancelled',    // 취소
                '50' => 'pending',      // 문의
                '90' => 'pending'       // 예약
            ];
            return $statusMap[$saveState] ?? 'pending';
        };
        
        foreach ($orderList as $index => $orderItem) {
            try {
                // $orderItem이 객체인지 배열인지 확인
                $isObject = is_object($orderItem);
                $isArray = is_array($orderItem);
                
                // 주문번호 451392365가 포함된 경우 간단히 로그 출력
                $orderItemJson = json_encode($orderItem, JSON_UNESCAPED_UNICODE);
                $isTargetOrderItem = (strpos($orderItemJson, '451392365') !== false);
                
                // 주문 목록 API 응답 구조: 주문 상세와 동일하거나 다를 수 있음
                $serialNumber = null;
                
                if ($isArray) {
                    
                    // $orderItem[5]: 금액 정보 (serial_number 포함)
                    if (isset($orderItem[5])) {
                        $costInfo = $orderItem[5];
                        $serialNumber = $getValue($costInfo, 'serial') ?? $getValue($costInfo, 'serial_number');
                    }
                    
                    // $orderItem[9]: 기타 정보
                    if (empty($serialNumber) && isset($orderItem[9])) {
                        $extraInfo = $orderItem[9];
                        $serialNumber = $getValue($extraInfo, 'serial') ?? $getValue($extraInfo, 'serial_number');
                    }
                    
                    // $orderItem 자체에서 직접 추출
                    if (empty($serialNumber)) {
                        $serialNumber = $getValue($orderItem, 'serial') ?? $getValue($orderItem, 'serial_number');
                    }
                } elseif ($isObject) {
                    // 객체인 경우: 직접 속성 접근
                    $serialNumber = $getValue($orderItem, 'serial') ?? $getValue($orderItem, 'serial_number');
                    
                    // 객체 내부에 배열 구조가 있을 수도 있음 (주문 상세와 동일한 구조)
                    if (empty($serialNumber)) {
                        // $orderItem->cost_info 또는 $orderItem[5] 같은 구조 확인
                        $costInfo = $getValue($orderItem, 'cost_info') ?? $getValue($orderItem, 5);
                        if ($costInfo) {
                            $serialNumber = $getValue($costInfo, 'serial') ?? $getValue($costInfo, 'serial_number');
                        }
                    }
                    
                    if (empty($serialNumber)) {
                        $extraInfo = $getValue($orderItem, 'extra_info') ?? $getValue($orderItem, 9);
                        if ($extraInfo) {
                            $serialNumber = $getValue($extraInfo, 'serial') ?? $getValue($extraInfo, 'serial_number');
                        }
                    }
                }
                
                if (empty($serialNumber)) {
                    $orderItemJson = json_encode($orderItem, JSON_UNESCAPED_UNICODE);
                    log_message('warning', 'OrderModel::insertOrUpdateInsungOrders - serial_number not found. OrderItem full structure: ' . $orderItemJson);
                    $errors[] = '주문번호(serial_number)를 찾을 수 없습니다.';
                    continue;
                }
                
                // API 응답에서 user_id 추출 (DB 저장용)
                // 필터링은 하지 않고 모든 주문을 저장 (나중에 DB에서 조회 시 필터링)
                $orderUserId = null;
                
                if ($isArray) {
                    // 배열인 경우: 직접 키로 접근 또는 $orderItem[1]에서 추출
                    $orderUserId = $orderItem['user_id'] ?? $orderItem['user_code'] ?? null;
                    if (empty($orderUserId) && isset($orderItem[1])) {
                        $customerInfo = $orderItem[1];
                        if (is_array($customerInfo)) {
                            $orderUserId = $customerInfo['user_id'] ?? $customerInfo['user_code'] ?? null;
                        } elseif (is_object($customerInfo)) {
                            $orderUserId = $customerInfo->user_id ?? $customerInfo->user_code ?? null;
                        }
                    }
                } elseif ($isObject) {
                    // 객체인 경우: 직접 속성 접근 또는 $orderItem->Result[1]에서 추출
                    $orderUserId = $orderItem->user_id ?? $orderItem->user_code ?? null;
                    if (empty($orderUserId) && isset($orderItem->Result) && isset($orderItem->Result[1])) {
                        $customerInfo = $orderItem->Result[1];
                        $orderUserId = $customerInfo->user_id ?? $customerInfo->user_code ?? null;
                    }
                }
                
                // API 응답의 user_id를 insung_user_id 필드에 저장
                // user_id는 tbl_users_list에서 idx를 찾아서 저장 (숫자 타입)
                $insungUserIdForDb = !empty($orderUserId) ? $orderUserId : $loginUserId;
                
                // user_id 문자열로 tbl_users_list에서 idx 조회
                $userIdxForDb = null;
                if (!empty($insungUserIdForDb)) {
                    $userListBuilder = $this->db->table('tbl_users_list');
                    $userListBuilder->select('idx');
                    $userListBuilder->where('user_id', $insungUserIdForDb);
                    $userListQuery = $userListBuilder->get();
                    if ($userListQuery !== false) {
                        $userListResult = $userListQuery->getRowArray();
                        if ($userListResult && !empty($userListResult['idx'])) {
                            $userIdxForDb = (int)$userListResult['idx'];
                        }
                    }
                }
                
                if (!empty($orderUserId)) {
                    // log_message('debug', "OrderModel::insertOrUpdateInsungOrders - 주문 {$serialNumber}의 insung_user_id({$orderUserId})를 저장합니다. user_id(idx): " . ($userIdxForDb ?? '없음'));
                } else {
                    // log_message('debug', "OrderModel::insertOrUpdateInsungOrders - 주문 {$serialNumber}의 insung_user_id가 없어 기본값({$loginUserId})을 사용합니다. user_id(idx): " . ($userIdxForDb ?? '없음'));
                }
                
                $isTargetOrder = false; // 로그 비활성화
                
                // 기존 주문 확인 (insung_order_number로만 확인 - INSERT ON DUPLICATE KEY UPDATE에서 사용)
                $tempOrderNumber = 'INSUNG-' . $serialNumber;
                $existingOrder = $this->where('insung_order_number', $serialNumber)->first();
                
                
                // 주문 데이터 구성 (주문 상세와 동일한 방식으로 파싱)
                // insung_user_id: API 응답의 user_id 문자열 저장
                // user_id: tbl_users_list.idx (숫자) 저장
                $orderRecord = [
                    'insung_user_id' => $insungUserIdForDb, // API 응답의 user_id 문자열 저장
                    'user_id' => $userIdxForDb, // tbl_users_list.idx (숫자)
                    'customer_id' => $customerId,
                    'service_type_id' => 1, // 기본값
                    'order_system' => 'insung',
                    'insung_order_number' => $serialNumber,
                    'order_number' => $existingOrder['order_number'] ?? $tempOrderNumber,
                    'status' => 'pending',
                    'state' => '접수', // 기본값 (한글 텍스트)
                    'order_date' => date('Y-m-d'),
                    'order_time' => date('H:i:s'),
                    'created_at' => $existingOrder['created_at'] ?? date('Y-m-d H:i:s'),
                    'updated_at' => date('Y-m-d H:i:s')
                ];
                
                // 데이터 추출 헬퍼 함수 (객체/배열 모두 처리)
                $getOrderItemValue = function($index = null, $key = null) use ($orderItem, $isArray, $isObject, $getValue) {
                    if ($isArray) {
                        // 배열인 경우 (주문 상세 API 구조)
                        if ($index !== null) {
                            if (!isset($orderItem[$index])) {
                                return null;
                            }
                            $data = $orderItem[$index];
                            return $key ? $getValue($data, $key) : $data;
                        } elseif ($key !== null) {
                            // 키로 직접 접근
                            return $getValue($orderItem, $key);
                        }
                    } elseif ($isObject) {
                        // 객체인 경우
                        if ($index !== null) {
                            // 인덱스로 접근 시도 (주문 상세와 동일한 구조)
                            $data = $getValue($orderItem, $index);
                            if ($data !== null) {
                                return $key ? $getValue($data, $key) : $data;
                            }
                        }
                        // 속성명으로 직접 접근 시도 (주문 목록 API 구조)
                        if ($key !== null) {
                            return $getValue($orderItem, $key);
                        }
                    }
                    return null;
                };
                
                // 주문 목록 API는 직접 필드 구조이므로, 먼저 직접 필드로 매핑 시도
                // 주문 상세 API 구조($orderItem[1], $orderItem[2] 등)도 함께 지원
                
                // 1. 접수자 정보 (주문 목록 API: 직접 필드, 주문 상세 API: $orderItem[1])
                $orderRecord['customer_name'] = $getOrderItemValue(null, 'customer_name') ?? $getValue($getOrderItemValue(1), 'customer_name');
                $orderRecord['customer_department'] = $getOrderItemValue(null, 'customer_department') ?? $getValue($getOrderItemValue(1), 'customer_department');
                $orderRecord['customer_duty'] = $getOrderItemValue(null, 'customer_staff') ?? $getValue($getOrderItemValue(1), 'customer_duty');
                
                // 2. 기사 정보 (주문 목록 API: 직접 필드, 주문 상세 API: $orderItem[2])
                $orderRecord['rider_code_no'] = $getOrderItemValue(null, 'rider_id') ?? $getOrderItemValue(null, 'rider_code') ?? $getValue($getOrderItemValue(2), 'rider_code_no');
                $orderRecord['rider_name'] = $getOrderItemValue(null, 'rider_name') ?? $getValue($getOrderItemValue(2), 'rider_name');
                $orderRecord['rider_tel_number'] = $getOrderItemValue(null, 'rider_mobile') ?? $getValue($getOrderItemValue(2), 'rider_tel_number');
                $orderRecord['rider_lon'] = $getOrderItemValue(null, 'rider_lon') ?? $getValue($getOrderItemValue(2), 'rider_lon');
                $orderRecord['rider_lat'] = $getOrderItemValue(null, 'rider_lat') ?? $getValue($getOrderItemValue(2), 'rider_lat');
                
                // 3. 주문 시간 정보 (주문 목록 API: 직접 필드, 주문 상세 API: $orderItem[3])
                // 예약 여부 확인 (state가 '90' 또는 '예약'인 경우)
                $orderState = $getOrderItemValue(null, 'order_state') ?? '';
                $orderStateCode = $getOrderItemValue(null, 'order_state_code') ?? '';
                $reserveCheck = $getOrderItemValue(null, 'reserve_check') ?? $getOrderItemValue(null, 'use_check') ?? '';
                $isReservation = ($orderStateCode === '90' || $orderState === '예약' || $reserveCheck === '1' || $reserveCheck === 1);
                
                // order_date는 항상 등록 날짜 사용 (order_date 필드)
                $orderDateStr = $getOrderItemValue(null, 'order_date') ?? $getValue($getOrderItemValue(3), 'order_time');
                
                // pickup_time 추출 (예약일시용)
                $pickupTimeStr = $getOrderItemValue(null, 'pickup_time') ?? $getValue($getOrderItemValue(3), 'pickup_time');
                
                if ($orderDateStr) {
                    $reserveDateTime = null;
                    
                    // "12-10 13:00" 형식 파싱 (MM-DD HH:mm)
                    if (preg_match('/(\d{2})-(\d{2})\s+(\d{2}):(\d{2})/', $orderDateStr, $matches)) {
                        $year = date('Y');
                        $month = $matches[1];
                        $day = $matches[2];
                        $hour = $matches[3];
                        $min = $matches[4];
                        // MM-DD를 YYYY-MM-DD로 변환
                        $fullDate = $year . '-' . $month . '-' . $day;
                        $orderRecord['order_date'] = date('Y-m-d', strtotime($fullDate));
                        $orderRecord['order_time'] = $hour . ':' . $min . ':00';
                    } else {
                        // "2025-12-10 13:00:00" 또는 "2025-12-08 16:22" 형식 파싱
                        $parsedTime = $parseDateTime($orderDateStr);
                        if ($parsedTime) {
                            $orderRecord['order_date'] = date('Y-m-d', strtotime($parsedTime));
                            $orderRecord['order_time'] = date('H:i:s', strtotime($parsedTime));
                        } else {
                            // "2025-12-08 16:22" 형식 직접 파싱 (초가 없는 경우)
                            if (preg_match('/(\d{4}-\d{2}-\d{2})\s+(\d{2}:\d{2})/', $orderDateStr, $matches)) {
                                $orderRecord['order_date'] = $matches[1];
                                $orderRecord['order_time'] = $matches[2] . ':00';
                            } elseif ($isTargetOrder) {
                                // log_message('warning', "OrderModel::insertOrUpdateInsungOrders - order_date 파싱 실패: {$orderDateStr}");
                            }
                        }
                    }
                    
                    // 예약일시 저장 (예약 상태이고 pickup_time이 있으면 이것만 reserve_date에 저장)
                    // 예약 상태가 아니거나 pickup_time이 없으면 reserve_date는 설정하지 않음
                    if ($isReservation && $pickupTimeStr) {
                        // 예약일 경우: reserve_date에 pickup_time만 저장
                        $parsedPickupTime = $parseDateTime($pickupTimeStr);
                        if ($parsedPickupTime) {
                            $reserveDateTime = $parsedPickupTime;
                        } else {
                            // parseDateTime 실패 시 직접 파싱
                            if (preg_match('/(\d{4}-\d{2}-\d{2})\s+(\d{2}:\d{2}):?(\d{2})?/', $pickupTimeStr, $matches)) {
                                $reserveDateTime = $matches[1] . ' ' . $matches[2] . ':' . (isset($matches[3]) ? $matches[3] : '00');
                            }
                        }
                    }
                    // 예약 상태가 아니거나 pickup_time이 없는 경우: reserve_date는 설정하지 않음 (NULL 유지)
                    
                    // reserve_date 저장
                    if ($reserveDateTime) {
                        $orderRecord['reserve_date'] = $reserveDateTime;
                        // reserve_date가 있으면 order_date와 order_time을 reserve_date 값으로 대체
                        $orderRecord['order_date'] = date('Y-m-d', strtotime($reserveDateTime));
                        $orderRecord['order_time'] = date('H:i:s', strtotime($reserveDateTime));
                    } elseif ($isTargetOrder) {
                        // log_message('warning', "OrderModel::insertOrUpdateInsungOrders - reserve_date가 NULL");
                    }
                } elseif ($isTargetOrder) {
                    // log_message('warning', "OrderModel::insertOrUpdateInsungOrders - order_date 값 없음");
                }
                
                // allocation_time: "12-08 10:09" 형식 -> "2025-12-08 10:09:00"
                $allocationTimeStr = $getOrderItemValue(null, 'allocation_time') ?? $getValue($getOrderItemValue(3), 'allocation_time');
                if ($allocationTimeStr) {
                    if (preg_match('/(\d{2}-\d{2})\s+(\d{2}:\d{2})/', $allocationTimeStr, $matches)) {
                        $year = date('Y');
                        $orderRecord['allocation_time'] = $year . '-' . $matches[1] . ' ' . $matches[2] . ':00';
                    } else {
                        $orderRecord['allocation_time'] = $parseDateTime($allocationTimeStr);
                    }
                }
                
                // pickup_time: "2025-12-08 10:16:00" 형식
                $pickupTimeStr = $getOrderItemValue(null, 'pickup_time') ?? $getValue($getOrderItemValue(3), 'pickup_time');
                if ($pickupTimeStr) {
                    $orderRecord['pickup_time'] = $parseDateTime($pickupTimeStr);
                }
                
                // complete_time
                $completeTimeStr = $getOrderItemValue(null, 'complete_time') ?? $getValue($getOrderItemValue(3), 'complete_time');
                if ($completeTimeStr && !empty($completeTimeStr)) {
                    $orderRecord['complete_time'] = $parseDateTime($completeTimeStr);
                }
                
                // 4. 주소 정보 (주문 목록 API: 직접 필드, 주문 상세 API: $orderItem[4])
                $orderRecord['departure_dong'] = $getOrderItemValue(null, 'departure_dong_name') ?? $getValue($getOrderItemValue(4), 'departure_dong_name');
                $orderRecord['departure_address'] = $getOrderItemValue(null, 'departure_address') ?? $getValue($getOrderItemValue(4), 'departure_address');
                $orderRecord['departure_company_name'] = $getOrderItemValue(null, 'departure_customer') ?? $getValue($getOrderItemValue(4), 'departure_company_name');
                $orderRecord['departure_department'] = $getOrderItemValue(null, 'departure_department') ?? $getValue($getOrderItemValue(4), 'start_department');
                $orderRecord['departure_manager'] = $getOrderItemValue(null, 'departure_staff') ?? $getValue($getOrderItemValue(4), 'start_duty');
                $orderRecord['departure_contact'] = $getOrderItemValue(null, 'departure_tel') ?? $getValue($getOrderItemValue(4), 'start_tel_number');
                
                $orderRecord['destination_dong'] = $getOrderItemValue(null, 'destination_dong_name') ?? $getValue($getOrderItemValue(4), 'destination_dong_name');
                $orderRecord['destination_address'] = $getOrderItemValue(null, 'destination_address') ?? $getValue($getOrderItemValue(4), 'destination_address');
                $orderRecord['destination_company_name'] = $getOrderItemValue(null, 'destination_customer') ?? $getValue($getOrderItemValue(4), 'destination_company_name');
                $orderRecord['destination_contact'] = $getOrderItemValue(null, 'destination_tel') ?? $getValue($getOrderItemValue(4), 'destination_tel_number');
                $orderRecord['destination_department'] = $getOrderItemValue(null, 'destination_department') ?? $getValue($getOrderItemValue(4), 'dest_department');
                $orderRecord['destination_manager'] = $getOrderItemValue(null, 'destination_staff') ?? $getValue($getOrderItemValue(4), 'dest_duty');
                
                $orderRecord['happy_call'] = $getOrderItemValue(null, 'happy_call') ?? $getValue($getOrderItemValue(4), 'happy_call');
                
                // 5. 금액 정보 (주문 목록 API: 직접 필드, 주문 상세 API: $orderItem[5])
                // state 필드에는 한글 텍스트(order_state)를 저장
                $orderState = $getOrderItemValue(null, 'order_state') ?? '';
                $orderStateCode = $getOrderItemValue(null, 'order_state_code') ?? $getValue($getOrderItemValue(5), 'state');
                
                
                if ($orderState) {
                    // order_state (한글 텍스트)가 있으면 이를 state에 저장
                    $orderRecord['state'] = $orderState;
                } elseif ($orderStateCode) {
                    // order_state가 없고 order_state_code만 있으면 코드를 그대로 저장 (fallback)
                    $orderRecord['state'] = $orderStateCode;
                }
                
                // 상태 매핑 (status 필드는 order_state_code 기반으로 매핑)
                if ($orderStateCode) {
                    $orderRecord['status'] = $mapInsungStatusToLocal($orderStateCode);
                }
                
                // 예약 상태(order_state_code=90)인 주문의 인성 주문번호 수집
                if ($orderStateCode === '90' || $orderState === '예약') {
                    if (!empty($serialNumber)) {
                        $reservationOrderNumbers[] = $serialNumber;
                    }
                }
                
                
                $orderRecord['total_amount'] = $parseAmount($getOrderItemValue(null, 'total_cost')) ?? $parseAmount($getValue($getOrderItemValue(5), 'total_cost'));
                $orderRecord['total_fare'] = $parseAmount($getOrderItemValue(null, 'basic_cost')) ?? $parseAmount($getValue($getOrderItemValue(5), 'basic_cost'));
                $orderRecord['add_cost'] = $parseAmount($getOrderItemValue(null, 'addition_cost')) ?? $parseAmount($getValue($getOrderItemValue(5), 'addition_cost'));
                $orderRecord['discount_cost'] = $parseAmount($getOrderItemValue(null, 'discount_cost')) ?? $parseAmount($getValue($getOrderItemValue(5), 'discount_cost'));
                $orderRecord['delivery_cost'] = $parseAmount($getOrderItemValue(null, 'delivery_cost')) ?? $parseAmount($getValue($getOrderItemValue(5), 'delivery_cost'));
                
                // 차종 정보
                $orderRecord['car_kind'] = $getOrderItemValue(null, 'car_type_code') ?? $getValue($getOrderItemValue(5), 'cargo_type');
                $orderRecord['car_type'] = $getOrderItemValue(null, 'car_type') ?? $getValue($getOrderItemValue(5), 'car_type');
                
                // 결제 타입 매핑
                $paymentTypeCode = $getOrderItemValue(null, 'payment_type_code') ?? '';
                $paymentType = $getOrderItemValue(null, 'payment_type') ?? '';
                if ($paymentTypeCode) {
                    // 코드 매핑: 1(선불), 2(착불), 3(신용), 4(송금)
                    $paymentTypeMap = [
                        '1' => 'cash_in_advance',
                        '2' => 'cash_on_delivery',
                        '3' => 'credit_transaction',
                        '4' => 'bank_transfer'
                    ];
                    $orderRecord['payment_type'] = $paymentTypeMap[$paymentTypeCode] ?? null;
                } elseif ($paymentType) {
                    // 한글 매핑
                    $paymentTypeMap = [
                        '선불' => 'cash_in_advance',
                        '착불' => 'cash_on_delivery',
                        '신용' => 'credit_transaction',
                        '신용거래' => 'credit_transaction',
                        '송금' => 'bank_transfer',
                        '계좌이체' => 'bank_transfer'
                    ];
                    $orderRecord['payment_type'] = $paymentTypeMap[$paymentType] ?? null;
                }
                
                // 배송방법 (delivery_type_code: 1=편도, 3=왕복, 5=경유)
                $deliveryTypeCode = $getOrderItemValue(null, 'delivery_type_code') ?? '';
                if ($deliveryTypeCode) {
                    $docMap = ['1' => '1', '3' => '3', '5' => '5'];
                    $orderRecord['doc'] = $docMap[$deliveryTypeCode] ?? '1';
                } else {
                    $orderRecord['doc'] = $getValue($getOrderItemValue(9), 'doc') ?? $getValue($getOrderItemValue(5), 'doc');
                }
                
                // 9. 기타 정보 (주문 목록 API: 직접 필드, 주문 상세 API: $orderItem[9])
                $orderRecord['delivery_content'] = $getOrderItemValue(null, 'summary') ?? $getValue($getOrderItemValue(9), 'summary');
                $orderRecord['order_regist_type'] = $getOrderItemValue(null, 'order_regist_type') ?? $getValue($getOrderItemValue(9), 'order_regist_type');
                $orderRecord['item_type'] = $getOrderItemValue(null, 'delivery_item') ?? $getOrderItemValue(null, 'delivery_item_text') ?? $getValue($getOrderItemValue(9), 'item_type');
                
                // NULL 값 제거 (기존 값 유지, 단 updated_at, user_id, insung_user_id는 항상 포함)
                $updatedAtValue = date('Y-m-d H:i:s');
                foreach ($orderRecord as $key => $value) {
                    // updated_at, user_id, insung_user_id는 제거하지 않음 (항상 업데이트)
                    if ($key === 'updated_at' || $key === 'user_id' || $key === 'insung_user_id') {
                        continue;
                    }
                    if ($value === null || $value === '') {
                        unset($orderRecord[$key]);
                    }
                }
                // updated_at은 항상 포함 (타임스탬프 업데이트)
                $orderRecord['updated_at'] = $updatedAtValue;
                // insung_user_id도 항상 포함 (API 응답의 user_id로 업데이트)
                if (!isset($orderRecord['insung_user_id'])) {
                    $orderRecord['insung_user_id'] = $insungUserIdForDb;
                }
                // user_id도 항상 포함 (tbl_users_list.idx로 업데이트)
                if (!isset($orderRecord['user_id'])) {
                    $orderRecord['user_id'] = $userIdxForDb;
                }
                
                // INSERT ON DUPLICATE KEY UPDATE 실행 (실제 MySQL 구문 사용)
                // 검증을 건너뛰고 직접 DB에 저장 (인성 API 데이터는 이미 검증됨)
                $originalSkipValidation = $this->skipValidation;
                $this->skipValidation = true;

                // DB 저장 전 최종 orderRecord 로그 출력
                $isUpdate = !empty($existingOrder);
                
                
                try {
                    // 실제 MySQL INSERT ON DUPLICATE KEY UPDATE 사용
                    $db = \Config\Database::connect();
                    
                    // 필드명과 값 분리
                    $fields = array_keys($orderRecord);
                    $values = array_values($orderRecord);
                    
                    // 값 이스케이프 처리
                    $escapedValues = array_map(function($value) use ($db) {
                        if ($value === null) {
                            return 'NULL';
                        }
                        return $db->escape($value);
                    }, $values);
                    
                    // UPDATE 절 생성 (insung_order_number와 created_at만 제외, 나머지 모든 필드는 UPDATE)
                    $updateFields = [];
                    foreach ($fields as $field) {
                        if ($field === 'insung_order_number' || $field === 'created_at') {
                            continue; // 이 필드들은 UPDATE하지 않음 (insung_order_number는 UNIQUE 키, created_at은 생성 시간 유지)
                        }
                        $updateFields[] = "`{$field}` = VALUES(`{$field}`)";
                    }
                    // updated_at은 항상 업데이트 (VALUES에 포함되어 있으면 자동으로 업데이트됨)
                    if (!in_array('updated_at', $fields)) {
                        $updateFields[] = "`updated_at` = NOW()";
                    }
                    
                    // SQL 쿼리 생성
                    $sql = "INSERT INTO `{$this->table}` (`" . implode('`, `', $fields) . "`) 
                            VALUES (" . implode(', ', $escapedValues) . ")
                            ON DUPLICATE KEY UPDATE " . implode(', ', $updateFields);
                    
                    // log_message('info', "OrderModel::insertOrUpdateInsungOrders - Executing INSERT ON DUPLICATE KEY UPDATE SQL");
                    // log_message('debug', "OrderModel::insertOrUpdateInsungOrders - SQL: " . $sql);
                    
                    // 쿼리 실행
                    $query = $db->query($sql);
                    
                    if ($query !== false) {
                        // 영향받은 행 수 확인
                        $affectedRows = $db->affectedRows();
                        
                        if ($affectedRows > 0) {
                            if ($isUpdate) {
                                $updated++;
                            } else {
                                $inserted++;
                            }
                        } else {
                            // 영향받은 행이 없어도 성공으로 처리 (데이터가 동일한 경우)
                            if ($isUpdate) {
                                $updated++;
                            } else {
                                $inserted++;
                            }
                        }
                    } else {
                        $error = $db->error();
                        throw new \Exception("SQL query execution failed: " . ($error['message'] ?? 'Unknown error'));
                    }
                    
                } catch (\Exception $e) {
                    $errors[] = "주문 저장 실패: {$serialNumber} - " . $e->getMessage();
                    log_message('error', "OrderModel::insertOrUpdateInsungOrders - Error saving order {$serialNumber}: " . $e->getMessage());
                    log_message('error', "OrderModel::insertOrUpdateInsungOrders - SQL Error: " . ($db->error()['message'] ?? 'Unknown error'));
                } finally {
                    // 검증 설정 복원
                    $this->skipValidation = $originalSkipValidation;
                }
                
            } catch (\Exception $e) {
                $errors[] = "주문 처리 중 오류: {$serialNumber} - " . $e->getMessage();
                log_message('error', "OrderModel::insertOrUpdateInsungOrders - Error processing order {$serialNumber}: " . $e->getMessage());
                // 검증 설정 복원 (예외 발생 시에도 복원)
                $this->skipValidation = $originalSkipValidation;
            }
        }
        
        return [
            'inserted' => $inserted,
            'updated' => $updated,
            'errors' => $errors,
            'reservation_order_numbers' => $reservationOrderNumbers
        ];
    }
    
    /**
     * 인성 주문번호로 주문의 reserve_date 업데이트
     * 
     * @param string $insungOrderNumber 인성 주문번호
     * @param string $reserveDate reserve_date 값 (Y-m-d H:i:s 형식)
     * @return bool 업데이트 성공 여부
     */
    public function updateReserveDateByInsungOrderNumber($insungOrderNumber, $reserveDate)
    {
        try {
            $order = $this->where('insung_order_number', $insungOrderNumber)->first();
            
            if (!$order) {
                log_message('warning', "OrderModel::updateReserveDateByInsungOrderNumber - 주문을 찾을 수 없습니다: {$insungOrderNumber}");
                return false;
            }
            
            $updateData = [
                'reserve_date' => $reserveDate,
                'updated_at' => date('Y-m-d H:i:s')
            ];
            
            $result = $this->update($order['id'], $updateData);
            
            if ($result) {
                log_message('info', "OrderModel::updateReserveDateByInsungOrderNumber - reserve_date 업데이트 성공: {$insungOrderNumber} => {$reserveDate}");
                return true;
            } else {
                log_message('error', "OrderModel::updateReserveDateByInsungOrderNumber - reserve_date 업데이트 실패: {$insungOrderNumber}");
                return false;
            }
            
        } catch (\Exception $e) {
            log_message('error', "OrderModel::updateReserveDateByInsungOrderNumber - 오류 발생: {$insungOrderNumber} - " . $e->getMessage());
            return false;
        }
    }
}
