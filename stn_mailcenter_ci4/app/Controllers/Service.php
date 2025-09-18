<?php

namespace App\Controllers;

use App\Controllers\BaseController;

class Service extends BaseController
{
    public function __construct()
    {
        helper('form');
    }
    /**
     * 메일룸 서비스
     */
    public function mailroom()
    {
        return $this->showServicePage('mailroom', '메일룸서비스');
    }
    
    /**
     * 퀵서비스 - 오토바이
     */
    public function quickMotorcycle()
    {
        return $this->showServicePage('quick-motorcycle', '오토바이(소화물)');
    }
    
    /**
     * 퀵서비스 - 차량
     */
    public function quickVehicle()
    {
        return $this->showServicePage('quick-vehicle', '차량(화물)');
    }
    
    /**
     * 퀵서비스 - 플렉스
     */
    public function quickFlex()
    {
        return $this->showServicePage('quick-flex', '플렉스(소화물)');
    }
    
    /**
     * 퀵서비스 - 이사짐
     */
    public function quickMoving()
    {
        return $this->showServicePage('quick-moving', '이사짐화물(소형)');
    }
    
    /**
     * 해외특송서비스
     */
    public function international()
    {
        return $this->showServicePage('international', '해외특송서비스');
    }
    
    /**
     * 연계배송서비스 - 고속버스
     */
    public function linkedBus()
    {
        return $this->showServicePage('linked-bus', '고속버스(제로데이)');
    }
    
    /**
     * 택배서비스 - 방문택배
     */
    public function parcelVisit()
    {
        return $this->showServicePage('parcel-visit', '방문택배');
    }
    
    /**
     * 생활서비스 - 사다주기
     */
    public function lifeBuy()
    {
        return $this->showServicePage('life-buy', '사다주기');
    }
    
    /**
     * 공통 서비스 페이지 표시
     */
    private function showServicePage($serviceType, $serviceName)
    {
        // 로그인 체크
        if (!session()->get('is_logged_in')) {
            return redirect()->to('/auth/login');
        }
        
        $data = [
            'title' => "STN Network - {$serviceName}",
            'content_header' => [
                'title' => $serviceName,
                'description' => "{$serviceName} 주문을 접수해주세요"
            ],
            'service_type' => $serviceType,
            'service_name' => $serviceName,
            'user' => [
                'username' => session()->get('username'),
                'company_name' => session()->get('company_name')
            ]
        ];
        
        return view('service/index', $data);
    }
    
    /**
     * 서비스별 주문 접수 처리
     */
    public function submitServiceOrder()
    {
        // 로그인 체크
        if (!session()->get('is_logged_in')) {
            return redirect()->to('/auth/login');
        }
        
        $serviceType = $this->request->getPost('service_type');
        $serviceName = $this->request->getPost('service_name');
        
        // 폼 데이터 검증
        $validation = \Config\Services::validation();
        $validation->setRules([
            'companyName' => 'required',
            'contact' => 'required',
            'departureAddress' => 'required',
            'destinationAddress' => 'required',
            'itemType' => 'required',
            'deliveryContent' => 'required'
        ]);
        
        if (!$validation->withRequest($this->request)->run()) {
            return redirect()->back()
                ->withInput()
                ->with('errors', $validation->getErrors());
        }
        
        // 주문 데이터 준비
        $orderData = [
            'user_id' => session()->get('user_id'),
            'service_type' => $serviceType,
            'service_name' => $serviceName,
            'company_name' => $this->request->getPost('companyName'),
            'contact' => $this->request->getPost('contact'),
            'address' => $this->request->getPost('address'),
            'departure_address' => $this->request->getPost('departureAddress'),
            'departure_detail' => $this->request->getPost('departureDetail'),
            'departure_contact' => $this->request->getPost('departureContact'),
            'destination_type' => $this->request->getPost('destinationType'),
            'mailroom' => $this->request->getPost('mailroom'),
            'destination_address' => $this->request->getPost('destinationAddress'),
            'detail_address' => $this->request->getPost('detailAddress'),
            'destination_contact' => $this->request->getPost('destinationContact'),
            'item_type' => $this->request->getPost('itemType'),
            'quantity' => $this->request->getPost('quantity'),
            'unit' => $this->request->getPost('unit'),
            'delivery_content' => $this->request->getPost('deliveryContent'),
            'status' => 'pending',
            'created_at' => date('Y-m-d H:i:s')
        ];
        
        // 주문 저장 (임시로 세션에 저장)
        $orders = session()->get('orders') ?? [];
        $orderData['id'] = count($orders) + 1;
        $orders[] = $orderData;
        session()->set('orders', $orders);
        
        return redirect()->to('/service/' . $serviceType)
            ->with('success', "{$serviceName} 주문이 접수되었습니다.");
    }
}
