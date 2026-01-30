<?php

namespace App\Controllers;

use App\Models\MailroomBuildingModel;
use App\Models\MailroomFloorModel;
use App\Models\MailroomDriverModel;
use App\Models\MailroomOrderModel;

/**
 * 메일룸 관리자 웹 컨트롤러
 */
class Mailroom extends BaseController
{
    protected $buildingModel;
    protected $floorModel;
    protected $driverModel;
    protected $orderModel;

    public function __construct()
    {
        $this->buildingModel = new MailroomBuildingModel();
        $this->floorModel = new MailroomFloorModel();
        $this->driverModel = new MailroomDriverModel();
        $this->orderModel = new MailroomOrderModel();
    }

    /**
     * 현재 세션의 거래처코드 반환
     */
    protected function getCompCode()
    {
        return session()->get('comp_code') ?? '';
    }

    /**
     * 대시보드 (주문 현황)
     */
    public function index()
    {
        $date = $this->request->getGet('date') ?? date('Y-m-d');
        $buildingId = $this->request->getGet('building_id');
        $compCode = $this->getCompCode();

        // 데이터베이스 에러 전체 catch
        try {
            // 테이블 존재 여부 확인
            $db = \Config\Database::connect();
            $tablesExist = $db->tableExists('tbl_mailroom_buildings');

            if (!$tablesExist) {
                throw new \Exception('Tables not exist');
            }

            $data = [
                'title' => '메일룸 관리',
                'tables_not_exist' => false,
                'buildings' => $this->buildingModel->getActiveBuildingsByCompCode($compCode),
                'stats' => $this->orderModel->getStats($buildingId, $date),
                'orders' => $buildingId
                    ? $this->orderModel->getOrdersByBuilding($buildingId, null, $date)
                    : [],
                'selected_building' => $buildingId,
                'selected_date' => $date
            ];

            return view('mailroom/dashboard', $data);

        } catch (\Exception $e) {
            // 테이블이 없거나 DB 에러 시 초기화 안내 화면
            return view('mailroom/dashboard', [
                'title' => '메일룸 관리',
                'tables_not_exist' => true,
                'buildings' => [],
                'stats' => ['urgent' => 0, 'pending' => 0, 'confirmed' => 0, 'picked' => 0, 'delivered' => 0],
                'orders' => [],
                'selected_building' => null,
                'selected_date' => $date
            ]);
        }
    }

    /**
     * 주문 접수 폼
     */
    public function create()
    {
        $compCode = $this->getCompCode();
        $data = [
            'title' => '배송 접수',
            'buildings' => $this->buildingModel->getActiveBuildingsByCompCode($compCode)
        ];

        return view('mailroom/order_create', $data);
    }

    /**
     * 주문 접수 처리
     */
    public function store()
    {
        $rules = [
            'from_building_id' => 'required|integer',
            'to_building_id' => 'required|integer',
            'item_description' => 'required|max_length[255]'
        ];

        if (!$this->validate($rules)) {
            return redirect()->back()->withInput()->with('errors', $this->validator->getErrors());
        }

        $orderNo = $this->orderModel->generateOrderNo();

        $fromBuildingId = (int)$this->request->getPost('from_building_id');
        $toBuildingId = (int)$this->request->getPost('to_building_id');

        // 배송 유형 자동 판단 (같은 건물 = internal, 다른 건물 = external)
        $deliveryType = $this->orderModel->determineDeliveryType($fromBuildingId, $toBuildingId);

        $orderData = [
            'order_no' => $orderNo,
            'from_building_id' => $fromBuildingId,
            'from_floor_id' => $this->request->getPost('from_floor_id') ?: null,
            'from_company' => $this->request->getPost('from_company'),
            'from_contact_name' => $this->request->getPost('from_contact_name'),
            'from_contact_phone' => $this->request->getPost('from_contact_phone'),
            'to_building_id' => $toBuildingId,
            'to_floor_id' => $this->request->getPost('to_floor_id') ?: null,
            'to_company' => $this->request->getPost('to_company'),
            'to_contact_name' => $this->request->getPost('to_contact_name'),
            'to_contact_phone' => $this->request->getPost('to_contact_phone'),
            'item_description' => $this->request->getPost('item_description'),
            'item_count' => $this->request->getPost('item_count') ?: 1,
            'priority' => $this->request->getPost('priority') ?: 'normal',
            'memo' => $this->request->getPost('memo'),
            'delivery_type' => $deliveryType,  // 자동 판단된 배송 유형
            'status' => 'pending',
            'created_by' => session()->get('user_id')
        ];

        $orderId = $this->orderModel->insert($orderData);

        if ($orderId) {
            // 바코드 생성
            $barcode = $this->orderModel->generateBarcode($orderId);
            $this->orderModel->update($orderId, ['barcode' => $barcode]);

            $typeLabel = $deliveryType === 'internal' ? '내부배송' : '외부배송';
            return redirect()->to('/mailroom/detail/' . $orderId)
                ->with('message', "배송이 접수되었습니다. 주문번호: {$orderNo} ({$typeLabel})");
        }

        return redirect()->back()->withInput()->with('error', '접수 중 오류가 발생했습니다.');
    }

    /**
     * 주문 상세
     */
    public function detail($orderId)
    {
        $order = $this->orderModel->find($orderId);
        if (!$order) {
            return redirect()->to('/mailroom')->with('error', '주문을 찾을 수 없습니다.');
        }

        // 건물/층 정보 추가
        $order['from_building'] = $this->buildingModel->find($order['from_building_id']);
        $order['to_building'] = $this->buildingModel->find($order['to_building_id']);
        if ($order['from_floor_id']) {
            $order['from_floor'] = $this->floorModel->find($order['from_floor_id']);
        }
        if ($order['to_floor_id']) {
            $order['to_floor'] = $this->floorModel->find($order['to_floor_id']);
        }

        $data = [
            'title' => '주문 상세',
            'order' => $order,
            'drivers' => $this->driverModel->getActiveDrivers()
        ];

        return view('mailroom/order_detail', $data);
    }

    /**
     * 기사 배정
     * handler_type: internal_driver (내부기사) 또는 external_driver (외부기사/인성)
     */
    public function assignDriver($orderId)
    {
        $order = $this->orderModel->find($orderId);
        if (!$order) {
            return redirect()->to('/mailroom')->with('error', '주문을 찾을 수 없습니다.');
        }

        $driverId = $this->request->getPost('driver_id');
        $handlerType = $this->request->getPost('handler_type') ?: 'internal_driver';

        $updateData = [
            'assigned_driver_id' => $driverId,
            'handler_type' => $handlerType,
            'status' => 'confirmed',
            'confirmed_at' => date('Y-m-d H:i:s')
        ];

        // 외부 기사(인성) 배정 시 인성 API 연동 대기 상태로 설정
        if ($handlerType === 'external_driver' && $order['delivery_type'] === 'external') {
            $updateData['insung_sync_status'] = 'pending';
        }

        $this->orderModel->update($orderId, $updateData);

        // TODO: 기사에게 푸시 알림

        $message = $handlerType === 'external_driver'
            ? '외부 기사가 배정되었습니다. (인성 연동 대기)'
            : '기사가 배정되었습니다.';

        return redirect()->to('/mailroom/detail/' . $orderId)->with('message', $message);
    }

    /**
     * 직접 처리 (메일룸 담당자가 직접 배송)
     */
    public function handleDirectly($orderId)
    {
        $order = $this->orderModel->find($orderId);
        if (!$order) {
            return redirect()->to('/mailroom')->with('error', '주문을 찾을 수 없습니다.');
        }

        $handlerMemo = $this->request->getPost('handler_memo');
        $userId = session()->get('user_id');

        $this->orderModel->update($orderId, [
            'handler_type' => 'mailroom_staff',
            'handler_user_id' => $userId,
            'handler_memo' => $handlerMemo,
            'status' => 'delivered',
            'delivered_at' => date('Y-m-d H:i:s'),
            'insung_sync_status' => 'none'  // 직접 처리는 인성 연동 안함
        ]);

        // 이력 저장
        $logModel = new \App\Models\MailroomOrderLogModel();
        $logModel->insert([
            'order_id' => $orderId,
            'status' => 'delivered',
            'message' => '담당자 직접 처리' . ($handlerMemo ? ": {$handlerMemo}" : ''),
            'created_by' => $userId
        ]);

        return redirect()->to('/mailroom/detail/' . $orderId)
            ->with('message', '배송이 직접 처리 완료되었습니다.');
    }

    /**
     * 건물별 층 목록 (AJAX)
     */
    public function getFloors($buildingId)
    {
        $floors = $this->floorModel->getFloorsByBuilding($buildingId);
        return $this->response->setJSON($floors);
    }

    /**
     * 바코드 라벨 인쇄 페이지
     */
    public function printLabel($orderId)
    {
        $order = $this->orderModel->find($orderId);
        if (!$order) {
            return redirect()->to('/mailroom')->with('error', '주문을 찾을 수 없습니다.');
        }

        $order['from_building'] = $this->buildingModel->find($order['from_building_id']);
        $order['to_building'] = $this->buildingModel->find($order['to_building_id']);
        if ($order['from_floor_id']) {
            $order['from_floor'] = $this->floorModel->find($order['from_floor_id']);
        }
        if ($order['to_floor_id']) {
            $order['to_floor'] = $this->floorModel->find($order['to_floor_id']);
        }

        $data = [
            'order' => $order
        ];

        return view('mailroom/print_label', $data);
    }

    // ========================================
    // 관리 기능
    // ========================================

    /**
     * 건물 관리
     */
    public function buildings()
    {
        try {
            // 테이블 존재 여부 확인
            $db = \Config\Database::connect();
            if (!$db->tableExists('tbl_mailroom_buildings')) {
                throw new \Exception('Table not exist');
            }

            $compCode = $this->getCompCode();

            // comp_code 컬럼 존재 여부 확인 후 필터링
            $fields = $db->getFieldNames('tbl_mailroom_buildings');
            if (in_array('comp_code', $fields) && $compCode) {
                $buildings = $this->buildingModel->where('comp_code', $compCode)->findAll();
            } else {
                $buildings = $this->buildingModel->findAll();
            }

            $data = [
                'title' => '건물 관리',
                'buildings' => $buildings
            ];

            return view('mailroom/admin/buildings', $data);

        } catch (\Exception $e) {
            return redirect()->to('/mailroom')->with('error', '데이터베이스 테이블이 생성되지 않았습니다. php spark migrate 명령을 실행해주세요.');
        }
    }

    /**
     * 기사 관리
     */
    public function drivers()
    {
        // 테이블 존재 여부 확인
        $db = \Config\Database::connect();
        if (!$db->tableExists('tbl_mailroom_drivers')) {
            return redirect()->to('/mailroom')->with('error', '데이터베이스 테이블이 생성되지 않았습니다. php spark migrate 명령을 실행해주세요.');
        }

        $compCode = $this->getCompCode();
        $drivers = $this->driverModel->findAll();

        // 각 기사별 담당 건물 추가
        foreach ($drivers as &$driver) {
            $driver['buildings'] = $this->driverModel->getDriverBuildings($driver['id']);
        }

        $data = [
            'title' => '기사 관리',
            'drivers' => $drivers,
            'buildings' => $this->buildingModel->getActiveBuildingsByCompCode($compCode)
        ];

        return view('mailroom/admin/drivers', $data);
    }

    /**
     * 주문 취소
     */
    public function cancelOrder($orderId)
    {
        $order = $this->orderModel->find($orderId);
        if (!$order) {
            return redirect()->to('/mailroom')->with('error', '주문을 찾을 수 없습니다.');
        }

        if (!in_array($order['status'], ['pending', 'confirmed'])) {
            return redirect()->to('/mailroom/detail/' . $orderId)->with('error', '취소할 수 없는 상태입니다.');
        }

        $this->orderModel->update($orderId, [
            'status' => 'cancelled',
            'cancelled_at' => date('Y-m-d H:i:s')
        ]);

        return redirect()->to('/mailroom')->with('message', '주문이 취소되었습니다.');
    }

    // ========================================
    // 건물 CRUD
    // ========================================

    /**
     * 건물 저장
     */
    public function storeBuilding()
    {
        $compCode = $this->getCompCode();

        $data = [
            'comp_code' => $compCode,
            'building_code' => $this->request->getPost('building_code'),
            'building_name' => $this->request->getPost('building_name'),
            'address' => $this->request->getPost('address'),
            'status' => $this->request->getPost('status') ?: 'active'
        ];

        $this->buildingModel->insert($data);
        return redirect()->to('/mailroom/buildings')->with('message', '건물이 추가되었습니다.');
    }

    /**
     * 건물 수정
     */
    public function updateBuilding($id)
    {
        // 권한 확인
        $building = $this->buildingModel->find($id);
        $compCode = $this->getCompCode();
        if ($building && isset($building['comp_code']) && $building['comp_code'] !== $compCode) {
            return redirect()->to('/mailroom/buildings')->with('error', '수정 권한이 없습니다.');
        }

        $data = [
            'building_code' => $this->request->getPost('building_code'),
            'building_name' => $this->request->getPost('building_name'),
            'address' => $this->request->getPost('address'),
            'status' => $this->request->getPost('status')
        ];

        $this->buildingModel->update($id, $data);
        return redirect()->to('/mailroom/buildings')->with('message', '건물 정보가 수정되었습니다.');
    }

    /**
     * 건물 삭제
     */
    public function deleteBuilding($id)
    {
        // 권한 확인
        $building = $this->buildingModel->find($id);
        $compCode = $this->getCompCode();
        if ($building && isset($building['comp_code']) && $building['comp_code'] !== $compCode) {
            return redirect()->to('/mailroom/buildings')->with('error', '삭제 권한이 없습니다.');
        }

        $this->buildingModel->delete($id);
        return redirect()->to('/mailroom/buildings')->with('message', '건물이 삭제되었습니다.');
    }

    // ========================================
    // 층 CRUD
    // ========================================

    /**
     * 층 저장 (AJAX)
     */
    public function storeFloor()
    {
        $json = $this->request->getJSON();
        $buildingId = $json->building_id ?? $this->request->getPost('building_id');
        $floorName = $json->floor_name ?? $this->request->getPost('floor_name');

        if (!$buildingId || !$floorName) {
            return $this->response->setJSON(['success' => false, 'message' => '필수 정보가 누락되었습니다.']);
        }

        $this->floorModel->insert([
            'building_id' => $buildingId,
            'floor_name' => $floorName
        ]);

        return $this->response->setJSON(['success' => true]);
    }

    /**
     * 층 삭제 (AJAX)
     */
    public function deleteFloor($id)
    {
        $this->floorModel->delete($id);
        return $this->response->setJSON(['success' => true]);
    }

    // ========================================
    // 기사 CRUD
    // ========================================

    /**
     * 기사 저장
     */
    public function storeDriver()
    {
        $data = [
            'driver_code' => $this->request->getPost('driver_code'),
            'driver_name' => $this->request->getPost('driver_name'),
            'phone' => $this->request->getPost('phone'),
            'status' => $this->request->getPost('status') ?: 'active'
        ];

        $this->driverModel->insert($data);
        return redirect()->to('/mailroom/drivers')->with('message', '기사가 추가되었습니다.');
    }

    /**
     * 기사 수정
     */
    public function updateDriver($id)
    {
        $data = [
            'driver_code' => $this->request->getPost('driver_code'),
            'driver_name' => $this->request->getPost('driver_name'),
            'phone' => $this->request->getPost('phone'),
            'status' => $this->request->getPost('status')
        ];

        $this->driverModel->update($id, $data);
        return redirect()->to('/mailroom/drivers')->with('message', '기사 정보가 수정되었습니다.');
    }

    /**
     * 기사 삭제
     */
    public function deleteDriver($id)
    {
        $this->driverModel->delete($id);
        // 담당 건물 매핑도 삭제
        $db = \Config\Database::connect();
        $db->table('mailroom_driver_buildings')->where('driver_id', $id)->delete();

        return redirect()->to('/mailroom/drivers')->with('message', '기사가 삭제되었습니다.');
    }

    /**
     * 기사 담당 건물 배정
     */
    public function assignBuildings($driverId)
    {
        $buildingIds = $this->request->getPost('building_ids') ?? [];

        $db = \Config\Database::connect();

        // 기존 매핑 삭제
        $db->table('mailroom_driver_buildings')->where('driver_id', $driverId)->delete();

        // 새 매핑 추가
        foreach ($buildingIds as $buildingId) {
            $db->table('mailroom_driver_buildings')->insert([
                'driver_id' => $driverId,
                'building_id' => $buildingId
            ]);
        }

        return redirect()->to('/mailroom/drivers')->with('message', '담당 건물이 배정되었습니다.');
    }

    /**
     * 기사 상태 변경
     */
    public function changeDriverStatus($id, $status)
    {
        if (!in_array($status, ['active', 'inactive'])) {
            return redirect()->to('/mailroom/drivers')->with('error', '잘못된 상태입니다.');
        }

        $this->driverModel->update($id, ['status' => $status]);
        return redirect()->to('/mailroom/drivers')->with('message', '기사 상태가 변경되었습니다.');
    }

    /**
     * 기사 승인
     */
    public function approveDriver($id)
    {
        $driver = $this->driverModel->find($id);
        if (!$driver || $driver['status'] !== 'pending') {
            return redirect()->to('/mailroom/drivers')->with('error', '승인할 수 없는 상태입니다.');
        }

        // 기사 코드 생성 (승인 시)
        $driverCode = 'D' . str_pad($id, 4, '0', STR_PAD_LEFT);

        $this->driverModel->update($id, [
            'status' => 'active',
            'driver_code' => $driverCode
        ]);

        // 해당 건물에 자동 배정
        if (!empty($driver['building_id'])) {
            $db = \Config\Database::connect();
            $db->table('mailroom_driver_buildings')->insert([
                'driver_id' => $id,
                'building_id' => $driver['building_id']
            ]);
        }

        return redirect()->to('/mailroom/drivers')->with('message', '기사가 승인되었습니다. 코드: ' . $driverCode);
    }

    /**
     * 기사 등록 거절
     */
    public function rejectDriver($id)
    {
        $driver = $this->driverModel->find($id);
        if (!$driver || $driver['status'] !== 'pending') {
            return redirect()->to('/mailroom/drivers')->with('error', '거절할 수 없는 상태입니다.');
        }

        $this->driverModel->delete($id);
        return redirect()->to('/mailroom/drivers')->with('message', '기사 등록이 거절되었습니다.');
    }

    // ========================================
    // QR 코드 생성
    // ========================================

    /**
     * 기사 등록 QR 코드 이미지 생성
     */
    public function driverRegisterQr($buildingId)
    {
        $building = $this->buildingModel->find($buildingId);
        if (!$building) {
            return $this->response->setStatusCode(404)->setBody('Building not found');
        }

        $registerUrl = site_url('mailroom/driver-register/' . $buildingId);

        try {
            // endroid/qr-code v4.x 사용
            if (class_exists('\Endroid\QrCode\Builder\Builder')) {
                $result = \Endroid\QrCode\Builder\Builder::create()
                    ->writer(new \Endroid\QrCode\Writer\PngWriter())
                    ->data($registerUrl)
                    ->size(200)
                    ->margin(10)
                    ->build();

                return $this->response
                    ->setContentType('image/png')
                    ->setBody($result->getString());
            }
            // endroid/qr-code v3.x 사용 (하위 호환)
            elseif (class_exists('\Endroid\QrCode\QrCode')) {
                $qrCode = new \Endroid\QrCode\QrCode($registerUrl);
                $qrCode->setSize(200);
                $qrCode->setMargin(10);

                $writer = new \Endroid\QrCode\Writer\PngWriter();
                $result = $writer->write($qrCode);

                return $this->response
                    ->setContentType('image/png')
                    ->setBody($result->getString());
            }
        } catch (\Exception $e) {
            log_message('error', 'QR코드 생성 실패: ' . $e->getMessage());
        }

        // 라이브러리가 없거나 실패 시 빈 이미지 반환
        return $this->response->setStatusCode(500)->setBody('QR generation failed');
    }

    // ========================================
    // 기사 셀프 등록
    // ========================================

    /**
     * 기사 등록 폼 (QR 스캔 후)
     */
    public function driverRegisterForm($buildingId)
    {
        $building = $this->buildingModel->find($buildingId);
        if (!$building) {
            return view('mailroom/driver_register', ['error' => '유효하지 않은 등록 링크입니다.']);
        }

        if ($building['status'] !== 'active') {
            return view('mailroom/driver_register', ['error' => '현재 등록을 받지 않는 건물입니다.']);
        }

        return view('mailroom/driver_register', ['building' => $building]);
    }

    /**
     * 기사 등록 처리
     */
    public function driverRegisterSubmit($buildingId)
    {
        $building = $this->buildingModel->find($buildingId);
        if (!$building || $building['status'] !== 'active') {
            return view('mailroom/driver_register', ['error' => '유효하지 않은 등록 링크입니다.']);
        }

        $driverName = $this->request->getPost('driver_name');
        $phone = $this->request->getPost('phone');

        if (!$driverName || !$phone) {
            return view('mailroom/driver_register', [
                'building' => $building,
                'error' => '이름과 연락처를 모두 입력해주세요.'
            ]);
        }

        // 중복 체크 (같은 건물, 같은 연락처)
        $existing = $this->driverModel->where('phone', $phone)->first();
        if ($existing) {
            return view('mailroom/driver_register', [
                'building' => $building,
                'error' => '이미 등록된 연락처입니다.'
            ]);
        }

        // 임시 코드로 등록 (승인 시 정식 코드 부여)
        $tempCode = 'PENDING_' . time();

        $driverId = $this->driverModel->insert([
            'driver_code' => $tempCode,
            'driver_name' => $driverName,
            'phone' => $phone,
            'building_id' => $buildingId,
            'status' => 'pending'
        ]);

        if ($driverId) {
            return view('mailroom/driver_register', [
                'building' => $building,
                'success' => true,
                'driver_code' => '승인 대기 중'
            ]);
        }

        return view('mailroom/driver_register', [
            'building' => $building,
            'error' => '등록 중 오류가 발생했습니다.'
        ]);
    }

    // ========================================
    // 메일룸 주문 승인 관리
    // ========================================

    /**
     * 메일룸 승인 대기 주문 목록 페이지
     */
    public function pendingOrders()
    {
        // 권한 확인 (메일룸 담당자 또는 관리자)
        $userClass = session()->get('user_class');
        if (!in_array($userClass, [1, 9])) {
            return redirect()->to('/dashboard')->with('error', '메일룸 관리 권한이 없습니다.');
        }

        $db = \Config\Database::connect();

        // 승인 대기 주문 조회
        $builder = $db->table('tbl_orders o');
        $builder->select('o.*, st.service_name, st.service_code');
        $builder->join('tbl_service_types st', 'o.service_type_id = st.id', 'left');
        $builder->where('o.mailroom_status', 'pending');
        $builder->orderBy('o.save_date', 'DESC');

        $query = $builder->get();
        $orders = $query ? $query->getResultArray() : [];

        // 연락처 복호화
        $encryptionHelper = new \App\Libraries\EncryptionHelper();
        foreach ($orders as &$order) {
            $order = $encryptionHelper->decryptFields($order, ['contact', 'departure_contact', 'destination_contact']);
        }
        unset($order);

        $data = [
            'title' => '메일룸 승인 대기 주문',
            'orders' => $orders
        ];

        return view('mailroom/pending_orders', $data);
    }
}