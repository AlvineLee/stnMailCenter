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
        'weight',
        'dimensions',
        'insurance_amount',
        'bag_type',
        'bag_material',
        'box_selection',
        'box_quantity',
        'pouch_selection',
        'pouch_quantity',
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
        'save_date',
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
        'cargo_name',
        'delivery_completed_date',
        'delivery_receiver_name',
        'delivery_trace_history',
        'ilyang_trace_code'
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
        'contact' => 'required|max_length[255]', // 암호화된 데이터를 위해 길이 증가
        'departure_company_name' => 'permit_empty|max_length[100]',
        'departure_contact' => 'permit_empty|max_length[255]', // 암호화된 데이터를 위해 길이 증가
        'departure_address' => 'permit_empty',
        'destination_company_name' => 'permit_empty|max_length[100]',
        'destination_contact' => 'permit_empty|max_length[255]', // 암호화된 데이터를 위해 길이 증가
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
            'max_length' => '출발지 연락처는 최대 255자까지 가능합니다.'
        ],
        'destination_company_name' => [
            'required' => '도착지 상호(이름)는 필수입니다.',
            'max_length' => '도착지 상호(이름)는 최대 100자까지 가능합니다.'
        ],
        'destination_contact' => [
            'required' => '도착지 연락처는 필수입니다.',
            'max_length' => '도착지 연락처는 최대 255자까지 가능합니다.'
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
            'max_length' => '연락처는 최대 255자까지 가능합니다.'
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
            
            // 전화번호 필드 암호화 처리 (인성 접수 데이터와 동일)
            $encryptionHelper = new \App\Libraries\EncryptionHelper();
            $phoneFields = ['contact', 'departure_contact', 'destination_contact', 'rider_tel_number', 'customer_tel_number', 'sms_telno'];
            $orderData = $encryptionHelper->encryptFields($orderData, $phoneFields);
            
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
        
        // 전화번호 필드 암호화 처리 (업데이트 시에도 적용)
        $encryptionHelper = new \App\Libraries\EncryptionHelper();
        $phoneFields = ['contact', 'departure_contact', 'destination_contact', 'rider_tel_number', 'customer_tel_number', 'sms_telno'];
        $updateData = $encryptionHelper->encryptFields($updateData, $phoneFields);
        
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
     * @param string|null $mCode API 마스터 코드 (회원정보 자동 등록용)
     * @param string|null $ccCode API 콜센터 코드 (회원정보 자동 등록용)
     * @param string|null $token API 토큰 (회원정보 자동 등록용)
     * @param int|null $apiIdx API 인덱스 (회원정보 자동 등록용)
     * @param string|null $compCode 거래처 코드 (삭제 로직용)
     * @param string|null $startDate 동기화 시작일 (삭제 로직용, YYYY-MM-DD)
     * @param string|null $endDate 동기화 종료일 (삭제 로직용, YYYY-MM-DD)
     * @return array ['inserted' => int, 'updated' => int, 'deleted' => int, 'errors' => array]
     */
    public function insertOrUpdateInsungOrders($orders, $userId, $customerId, $isSelfOrderOnly = false, $loginUserId = null, $mCode = null, $ccCode = null, $token = null, $apiIdx = null, $compCode = null, $startDate = null, $endDate = null)
    {
        $inserted = 0;
        $updated = 0;
        $deleted = 0;
        $errors = [];
        $reservationOrderNumbers = []; // 예약 상태인 주문들의 인성 주문번호
        $receivedOrderNumbers = []; // 인성 API에서 받은 주문번호 목록 (삭제 로직용)
        
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
                
                // API가 던져준 데이터 그대로 사용 (다른 필드들과 동일하게 처리)
                $serialNumber = $getOrderItemValue(null, 'serial_number') ?? $getOrderItemValue(null, 'serial');
                
                if (empty($serialNumber)) {
                    $orderItemJson = json_encode($orderItem, JSON_UNESCAPED_UNICODE);
                    log_message('warning', 'OrderModel::insertOrUpdateInsungOrders - serial_number not found. OrderItem full structure: ' . $orderItemJson);
                    $errors[] = '주문번호(serial_number)를 찾을 수 없습니다.';
                    continue;
                }
                
                // 인성 API에서 받은 주문번호 수집 (삭제 로직용)
                if (!empty($serialNumber)) {
                    $receivedOrderNumbers[] = $serialNumber;
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
                // API의 user_id가 없으면 NULL로 설정 (로그인 세션 ID를 넣지 않음)
                $insungUserIdForDb = !empty($orderUserId) ? $orderUserId : null;
                
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
                    
                    // DB에 없으면 인성 API로 회원정보 조회 후 추가
                    if (!$userIdxForDb && $mCode && $ccCode && $token) {
                        try {
                            $insungApiService = new \App\Libraries\InsungApiService();
                            $memberResult = $insungApiService->getMemberDetail($mCode, $ccCode, $token, $insungUserIdForDb, $apiIdx);
                            
                            if ($memberResult && (is_array($memberResult) || is_object($memberResult))) {
                                // API 응답 파싱
                                $code = '';
                                $memberDetail = null;
                                
                                if (is_array($memberResult) && isset($memberResult[0])) {
                                    $code = $memberResult[0]->code ?? $memberResult[0]['code'] ?? '';
                                    if ($code === '1000' && isset($memberResult[1])) {
                                        $memberDetail = is_object($memberResult[1]) ? (array)$memberResult[1] : $memberResult[1];
                                    }
                                } elseif (is_object($memberResult) && isset($memberResult->Result)) {
                                    $code = $memberResult->Result[0]->result_info[0]->code ?? '';
                                    if ($code === '1000' && isset($memberResult->Result[1]->item[0])) {
                                        $memberDetail = (array)$memberResult->Result[1]->item[0];
                                    }
                                }
                                
                                // 회원정보가 조회되면 DB에 추가
                                if ($code === '1000' && $memberDetail) {
                                    $userCcode = $memberDetail['c_code'] ?? $memberDetail['user_code'] ?? '';
                                    $userName = $memberDetail['name'] ?? $memberDetail['cust_name'] ?? $memberDetail['charge_name'] ?? '';
                                    $userDept = $memberDetail['dept_name'] ?? '';
                                    $userTel1 = $memberDetail['tel_no1'] ?? $memberDetail['tel_number'] ?? '';
                                    $userTel2 = $memberDetail['tel_no2'] ?? '';
                                    $compNo = $memberDetail['comp_no'] ?? '';
                                    $userCompany = $compNo; // comp_no를 user_company로 사용
                                    
                                    // 주소 정보
                                    $sido = $memberDetail['sido'] ?? '';
                                    $gugun = $memberDetail['gugun'] ?? '';
                                    $dongName = $memberDetail['dong_name'] ?? $memberDetail['basic_dong'] ?? '';
                                    $ri = $memberDetail['ri'] ?? '';
                                    $userAddr = trim(implode(' ', array_filter([$sido, $gugun, $dongName, $ri])));
                                    $lon = $memberDetail['lon'] ?? '';
                                    $lat = $memberDetail['lat'] ?? '';
                                    
                                    // tbl_users_list에 추가
                                    $newUserData = [
                                        'user_id' => $insungUserIdForDb,
                                        'user_pass' => $insungUserIdForDb, // 기본값: user_id와 동일
                                        'user_name' => $userName,
                                        'user_dept' => $userDept,
                                        'user_tel1' => $userTel1,
                                        'user_company' => $userCompany,
                                        'user_ccode' => $userCcode,
                                        'user_type' => '5', // 기본값: 일반 고객
                                        'user_class' => '5', // 기본값: 일반
                                        'user_addr' => $userAddr,
                                        'user_addr_detail' => $ri,
                                        'user_sido' => $sido,
                                        'user_gungu' => $gugun,
                                        'user_dong' => $dongName,
                                        'user_lon' => $lon ?: null,
                                        'user_lat' => $lat ?: null
                                    ];
                                    
                                    if (!empty($userTel2)) {
                                        $newUserData['user_tel2'] = $userTel2;
                                    }
                                    
                                    $insertResult = $userListBuilder->insert($newUserData);
                                    
                                    if ($insertResult) {
                                        log_message('info', "OrderModel::insertOrUpdateInsungOrders - Auto-registered user from API: user_id={$insungUserIdForDb}");
                                        // 새로 생성한 idx 조회
                                        $userListBuilder2 = $this->db->table('tbl_users_list');
                                        $userListBuilder2->select('idx');
                                        $userListBuilder2->where('user_id', $insungUserIdForDb);
                                        $userListQuery2 = $userListBuilder2->get();
                                        if ($userListQuery2 !== false) {
                                            $userListResult2 = $userListQuery2->getRowArray();
                                            if ($userListResult2 && !empty($userListResult2['idx'])) {
                                                $userIdxForDb = (int)$userListResult2['idx'];
                                            }
                                        }
                                    }
                                }
                            }
                        } catch (\Exception $e) {
                            log_message('error', "OrderModel::insertOrUpdateInsungOrders - Failed to fetch member from API: " . $e->getMessage());
                        }
                    }
                } else {
                    // API의 user_id가 없을 때는 로그인한 사용자의 idx를 user_id로 사용
                    // $userId는 로그인한 사용자의 tbl_users_list.idx (숫자)
                    if (!empty($userId)) {
                        $userIdxForDb = (int)$userId;
                    }
                }
                
                if (!empty($orderUserId)) {
                    // log_message('debug', "OrderModel::insertOrUpdateInsungOrders - 주문 {$serialNumber}의 insung_user_id({$orderUserId})를 저장합니다. user_id(idx): " . ($userIdxForDb ?? '없음'));
                } else {
                    // log_message('debug', "OrderModel::insertOrUpdateInsungOrders - 주문 {$serialNumber}의 insung_user_id가 없어 NULL로 설정합니다. user_id(idx): " . ($userIdxForDb ?? '없음') . " (로그인 사용자 idx 사용)");
                }
                
                $isTargetOrder = false; // 로그 비활성화
                
                // 기존 주문 확인
                // 1. insung_order_number로 확인 (우선)
                $tempOrderNumber = 'INSUNG-' . $serialNumber;
                // 삭제된 데이터도 찾을 수 있으므로 명시적으로 존재하는 데이터만 확인
                $existingOrder = $this->where('insung_order_number', $serialNumber)->first();
                
                // 2. insung_order_number가 없으면 order_number로 확인 (ORD- 형식 주문 업데이트용)
                // 단, 삭제된 데이터는 제외 (insung_order_number가 NULL이 아닌 경우만)
                if (!$existingOrder) {
                    // order_number가 INSUNG- 형식인 경우도 확인
                    $existingOrder = $this->where('order_number', $tempOrderNumber)
                                          ->where('insung_order_number IS NOT NULL')
                                          ->where('insung_order_number !=', '')
                                          ->first();
                }
                
                
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
                
                // 주문 목록 API는 직접 필드 구조이므로, 먼저 직접 필드로 매핑 시도
                // 주문 상세 API 구조($orderItem[1], $orderItem[2] 등)도 함께 지원
                // getOrderItemValue 함수는 위에서 이미 정의됨
                
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
                // API에서 받은 save_date와 order_date를 그대로 저장 (updated_at만 사용하므로 변환 불필요)
                $saveDateStr = $getOrderItemValue(null, 'save_date') ?? '';
                $orderDateStr = $getOrderItemValue(null, 'order_date') ?? $getValue($getOrderItemValue(3), 'order_time');
                
                // save_date를 TIMESTAMP 형식으로 변환하여 저장
                if ($saveDateStr) {
                    // "2025-12-30 오후 2:19:00" 형식을 "2025-12-30 14:19:00" 형식으로 변환
                    $convertedSaveDate = $saveDateStr;
                    
                    // "오후" 또는 "오전" 처리
                    if (preg_match('/(\d{4}-\d{2}-\d{2})\s+(오전|오후)\s+(\d{1,2}):(\d{2}):(\d{2})/', $saveDateStr, $matches)) {
                        $date = $matches[1];
                        $ampm = $matches[2];
                        $hour = (int)$matches[3];
                        $min = $matches[4];
                        $sec = $matches[5];
                        
                        // 오후면 12시간 추가 (단, 12시는 그대로)
                        if ($ampm === '오후' && $hour < 12) {
                            $hour += 12;
                        } elseif ($ampm === '오전' && $hour == 12) {
                            $hour = 0;
                        }
                        
                        $convertedSaveDate = sprintf('%s %02d:%s:%s', $date, $hour, $min, $sec);
                    }
                    
                    $orderRecord['save_date'] = $convertedSaveDate;
                }
                
                // order_date를 그대로 저장 (API에서 받은 형식 그대로)
                if ($orderDateStr) {
                    $orderRecord['order_date'] = $orderDateStr;
                    // order_date에서 날짜와 시간 분리 (간단한 처리만)
                    // "2025-12-30 14:19" 형식에서 날짜와 시간 추출
                    if (preg_match('/(\d{4}-\d{2}-\d{2})/', $orderDateStr, $dateMatches)) {
                        $orderRecord['order_date'] = $dateMatches[1];
                    }
                    if (preg_match('/(\d{2}:\d{2})/', $orderDateStr, $timeMatches)) {
                        $orderRecord['order_time'] = $timeMatches[1] . ':00';
                    }
                }
                
                // resolve_time 추출 (예약시간, /api/order_list/dept/detail API에서 제공)
                $resolveTimeStr = $getOrderItemValue(null, 'resolve_time') ?? $getValue($getOrderItemValue(3), 'resolve_time');
                if ($resolveTimeStr && !empty(trim($resolveTimeStr))) {
                    $orderRecord['resolve_time'] = $parseDateTime($resolveTimeStr);
                    // resolve_time이 있으면 reserve_date에 저장 (예약시간)
                    $orderRecord['reserve_date'] = $parseDateTime($resolveTimeStr);
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
                    // resolve_time이 없고 pickup_time이 있으면 reserve_date에 저장 (예약일 때는 예약시간)
                    if (empty($orderRecord['reserve_date'])) {
                        $orderRecord['reserve_date'] = $parseDateTime($pickupTimeStr);
                    }
                }
                
                // complete_time
                $completeTimeStr = $getOrderItemValue(null, 'complete_time') ?? $getValue($getOrderItemValue(3), 'complete_time');
                if ($completeTimeStr && !empty($completeTimeStr)) {
                    $orderRecord['complete_time'] = $parseDateTime($completeTimeStr);
                }
                
                // 4. 주소 정보 (주문 목록 API: 직접 필드, 주문 상세 API: $orderItem[4])
                $departureDong = $getOrderItemValue(null, 'departure_dong_name') ?? $getValue($getOrderItemValue(4), 'departure_dong_name');
                $departureAddress = $getOrderItemValue(null, 'departure_address') ?? $getValue($getOrderItemValue(4), 'departure_address');
                $departureCompanyName = $getOrderItemValue(null, 'departure_customer') ?? $getValue($getOrderItemValue(4), 'departure_company_name');
                $departureDepartment = $getOrderItemValue(null, 'departure_department') ?? $getValue($getOrderItemValue(4), 'start_department');
                $departureManager = $getOrderItemValue(null, 'departure_staff') ?? $getValue($getOrderItemValue(4), 'start_duty');
                $departureContact = $getOrderItemValue(null, 'departure_tel') ?? $getValue($getOrderItemValue(4), 'start_tel_number');
                $departureLon = $getOrderItemValue(null, 'departure_lon') ?? $getOrderItemValue(null, 'start_lon') ?? $getValue($getOrderItemValue(4), 'start_lon');
                $departureLat = $getOrderItemValue(null, 'departure_lat') ?? $getOrderItemValue(null, 'start_lat') ?? $getValue($getOrderItemValue(4), 'start_lat');
                $sCCode = $getOrderItemValue(null, 's_c_code') ?? $getOrderItemValue(null, 'start_c_code') ?? $getValue($getOrderItemValue(4), 'start_c_code');
                
                $orderRecord['departure_dong'] = $departureDong;
                $orderRecord['departure_address'] = $departureAddress;
                $orderRecord['departure_company_name'] = $departureCompanyName;
                $orderRecord['departure_department'] = $departureDepartment;
                $orderRecord['departure_manager'] = $departureManager;
                $orderRecord['departure_contact'] = $departureContact;
                $orderRecord['departure_lon'] = $departureLon;
                $orderRecord['departure_lat'] = $departureLat;
                $orderRecord['s_c_code'] = $sCCode;
                // departure_detail: dong 값이 있으면 dong 값을 사용, 없으면 빈 문자열
                $orderRecord['departure_detail'] = $departureDong ?? '';
                
                $destinationDong = $getOrderItemValue(null, 'destination_dong_name') ?? $getValue($getOrderItemValue(4), 'destination_dong_name');
                $destinationAddress = $getOrderItemValue(null, 'destination_address') ?? $getValue($getOrderItemValue(4), 'destination_address');
                $destinationCompanyName = $getOrderItemValue(null, 'destination_customer') ?? $getValue($getOrderItemValue(4), 'destination_company_name');
                $destinationDepartment = $getOrderItemValue(null, 'destination_department') ?? $getValue($getOrderItemValue(4), 'dest_department');
                $destinationManager = $getOrderItemValue(null, 'destination_staff') ?? $getValue($getOrderItemValue(4), 'dest_duty');
                $destinationContact = $getOrderItemValue(null, 'destination_tel') ?? $getValue($getOrderItemValue(4), 'destination_tel_number');
                $destinationLon = $getOrderItemValue(null, 'destination_lon') ?? $getOrderItemValue(null, 'dest_lon') ?? $getValue($getOrderItemValue(4), 'dest_lon');
                $destinationLat = $getOrderItemValue(null, 'destination_lat') ?? $getOrderItemValue(null, 'dest_lat') ?? $getValue($getOrderItemValue(4), 'dest_lat');
                $dCCode = $getOrderItemValue(null, 'd_c_code') ?? $getOrderItemValue(null, 'dest_c_code') ?? $getValue($getOrderItemValue(4), 'dest_c_code');
                
                $orderRecord['destination_dong'] = $destinationDong;
                $orderRecord['destination_address'] = $destinationAddress;
                $orderRecord['destination_company_name'] = $destinationCompanyName;
                $orderRecord['destination_department'] = $destinationDepartment;
                $orderRecord['destination_manager'] = $destinationManager;
                $orderRecord['destination_contact'] = $destinationContact;
                $orderRecord['destination_lon'] = $destinationLon;
                $orderRecord['destination_lat'] = $destinationLat;
                $orderRecord['d_c_code'] = $dCCode;
                // detail_address: dong 값이 있으면 dong 값을 사용, 없으면 빈 문자열
                $orderRecord['detail_address'] = $destinationDong ?? '';
                
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
                
                // 전화번호 필드 암호화 처리
                $encryptionHelper = new \App\Libraries\EncryptionHelper();
                $phoneFields = ['contact', 'departure_contact', 'destination_contact', 'rider_tel_number', 'customer_tel_number', 'sms_telno'];
                $orderRecord = $encryptionHelper->encryptFields($orderRecord, $phoneFields);
                
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
                    
                    // 기존 주문이 있고 insung_order_number가 없는 경우 (ORD- 형식 주문 업데이트)
                    // INSERT ON DUPLICATE KEY UPDATE는 UNIQUE 키 기준이므로, order_number로는 작동하지 않음
                    // 따라서 기존 주문을 찾아서 직접 UPDATE
                    if ($existingOrder && empty($existingOrder['insung_order_number'])) {
                        // 기존 주문 업데이트 (insung_order_number 추가)
                        $updateSql = "UPDATE `{$this->table}` SET " . implode(', ', $updateFields) . " WHERE `id` = " . (int)$existingOrder['id'];
                        $db->query($updateSql);
                        $affectedRows = $db->affectedRows();
                        
                        if ($affectedRows > 0) {
                            $updated++;
                            log_message('info', "OrderModel::insertOrUpdateInsungOrders - Updated existing order (ORD- format) with insung_order_number: {$serialNumber}, Order ID: {$existingOrder['id']}");
                        }
                    } else {
                        // 일반 INSERT ON DUPLICATE KEY UPDATE 실행
                        $sql = "INSERT INTO `{$this->table}` (`" . implode('`, `', $fields) . "`) 
                                VALUES (" . implode(', ', $escapedValues) . ")
                                ON DUPLICATE KEY UPDATE " . implode(', ', $updateFields);
                        
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
        
        // 인성 API에서 받지 않은 주문 삭제 처리
        if (!empty($receivedOrderNumbers) && !empty($compCode)) {
            try {
                // DELETE 쿼리 실행 (JOIN을 사용한 DELETE는 MySQL에서 직접 지원하지 않으므로 서브쿼리 사용)
                $subQuery = $this->db->table('tbl_orders o2');
                $subQuery->select('o2.id');
                $subQuery->join('tbl_users_list u_list2', 'o2.user_id = u_list2.idx', 'left');
                
                // 기본 조건: 인성 API 주문만
                $subQuery->where('o2.order_system', 'insung');
                
                // comp_code 조건
                $subQuery->where('u_list2.user_company', $compCode);
                
                // 기간 조건 (updated_at 기준)
                if (!empty($startDate)) {
                    $subQuery->where('DATE(o2.updated_at) >=', $startDate);
                } else {
                    // startDate가 없으면 오늘 날짜 기준
                    $today = date('Y-m-d');
                    $subQuery->where('DATE(o2.updated_at) >=', $today);
                }
                
                if (!empty($endDate)) {
                    $subQuery->where('DATE(o2.updated_at) <=', $endDate);
                }
                
                // 본인오더조회 필터링 (isSelfOrderOnly가 true이고 loginUserId가 있으면)
                if ($isSelfOrderOnly && !empty($loginUserId)) {
                    $subQuery->where('o2.insung_user_id', $loginUserId);
                }
                
                // 인성 API에서 받은 주문번호 목록에 없는 주문만 삭제
                if (!empty($receivedOrderNumbers)) {
                    $subQuery->whereNotIn('o2.insung_order_number', $receivedOrderNumbers);
                }
                
                // insung_order_number가 NULL이거나 빈 문자열인 주문은 삭제하지 않음 (안전장치)
                $subQuery->where('o2.insung_order_number IS NOT NULL');
                $subQuery->where('o2.insung_order_number !=', '');
                
                $subQuerySql = $subQuery->getCompiledSelect(false);
                
                // DELETE 쿼리 실행
                $deleteSql = "DELETE FROM tbl_orders WHERE id IN ({$subQuerySql})";
                $this->db->query($deleteSql);
                
                $deleted = $this->db->affectedRows();
                
                if ($deleted > 0) {
                    log_message('info', "OrderModel::insertOrUpdateInsungOrders - Deleted {$deleted} orders not found in API response (comp_code: {$compCode}, startDate: {$startDate}, endDate: {$endDate})");
                }
            } catch (\Exception $e) {
                $errors[] = "주문 삭제 중 오류: " . $e->getMessage();
                log_message('error', "OrderModel::insertOrUpdateInsungOrders - Error deleting orders: " . $e->getMessage());
            }
        }
        
        return [
            'inserted' => $inserted,
            'updated' => $updated,
            'deleted' => $deleted,
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
