<?php

namespace App\Controllers;

use App\Models\BillingModel;
use App\Models\BillingDetailModel;
use App\Models\DepartmentModel;
use App\Models\CustomerHierarchyModel;
use App\Models\OrderModel;

class Billing extends BaseController
{
    protected $billingModel;
    protected $billingDetailModel;
    protected $departmentModel;
    protected $customerHierarchyModel;
    protected $orderModel;

    public function __construct()
    {
        // 프로토타입 단계에서는 모델 초기화 비활성화
        // $this->billingModel = new BillingModel();
        // $this->billingDetailModel = new BillingDetailModel();
        // $this->departmentModel = new DepartmentModel();
        // $this->customerHierarchyModel = new CustomerHierarchyModel();
        // $this->orderModel = new OrderModel();
    }

    /**
     * 청구 관리 메인 페이지
     */
    public function index()
    {
        // 프로토타입용 Mock 데이터
        $data = [
            'title' => '청구 관리',
            'content_header' => [
                'title' => '청구 관리',
                'description' => '청구서를 관리하고 조회할 수 있습니다.'
            ],
            'billing_types' => [
                'department' => '부서별 청구',
                'department_group' => '부서묶음 청구',
                'customer_group' => '고객묶음 청구'
            ],
            'billing_statuses' => [
                'draft' => '초안',
                'pending' => '대기',
                'sent' => '발송',
                'paid' => '결제완료',
                'overdue' => '연체',
                'cancelled' => '취소'
            ],
            'billings' => [
                [
                    'id' => 1,
                    'billing_number' => 'BILL-20240115-0001',
                    'billing_type' => 'department',
                    'billing_date' => '2024-01-15',
                    'due_date' => '2024-02-15',
                    'total_amount' => 1500000,
                    'final_amount' => 1650000,
                    'status' => 'sent',
                    'payment_status' => 'pending',
                    'customer_name' => 'STN 네트워크',
                    'department_names' => '개발팀',
                    'created_at' => '2024-01-15 09:00:00'
                ],
                [
                    'id' => 2,
                    'billing_number' => 'BILL-20240116-0002',
                    'billing_type' => 'department_group',
                    'billing_date' => '2024-01-16',
                    'due_date' => '2024-02-16',
                    'total_amount' => 2300000,
                    'final_amount' => 2530000,
                    'status' => 'paid',
                    'payment_status' => 'paid',
                    'customer_name' => 'STN 서울지사',
                    'department_names' => '마케팅팀, 영업팀',
                    'created_at' => '2024-01-16 10:00:00'
                ],
                [
                    'id' => 3,
                    'billing_number' => 'BILL-20240117-0003',
                    'billing_type' => 'customer_group',
                    'billing_date' => '2024-01-17',
                    'due_date' => '2024-02-17',
                    'total_amount' => 5000000,
                    'final_amount' => 5500000,
                    'status' => 'draft',
                    'payment_status' => 'unpaid',
                    'customer_name' => 'STN 강남대리점',
                    'department_names' => null,
                    'created_at' => '2024-01-17 11:00:00'
                ]
            ]
        ];

        // 필터 파라미터
        $filters = [
            'billing_type' => $this->request->getGet('billing_type'),
            'status' => $this->request->getGet('status'),
            'start_date' => $this->request->getGet('start_date'),
            'end_date' => $this->request->getGet('end_date')
        ];

        $data['filters'] = $filters;

        return view('billing/index', $data);
    }

    /**
     * 부서별 청구 페이지
     */
    public function department()
    {
        // 프로토타입용 Mock 데이터
        $data = [
            'title' => '부서별 청구',
            'content_header' => [
                'title' => '부서별 청구',
                'description' => '개별 부서별로 청구서를 생성할 수 있습니다.'
            ],
            'customers' => [
                [
                    'id' => 1,
                    'company_name' => 'STN 네트워크',
                    'hierarchy_level' => 'head_office',
                    'is_active' => 1
                ],
                [
                    'id' => 2,
                    'company_name' => 'STN 서울지사',
                    'hierarchy_level' => 'branch',
                    'is_active' => 1
                ],
                [
                    'id' => 3,
                    'company_name' => 'STN 강남대리점',
                    'hierarchy_level' => 'agency',
                    'is_active' => 1
                ]
            ],
            'departments' => [
                [
                    'id' => 1,
                    'department_code' => 'DEPT001',
                    'department_name' => '개발팀',
                    'customer_id' => 1
                ],
                [
                    'id' => 2,
                    'department_code' => 'DEPT002',
                    'department_name' => '마케팅팀',
                    'customer_id' => 1
                ],
                [
                    'id' => 3,
                    'department_code' => 'DEPT003',
                    'department_name' => '영업팀',
                    'customer_id' => 1
                ]
            ]
        ];

        // 고객사 선택 시 부서 목록 조회 (프로토타입에서는 동일한 데이터 반환)
        $customerId = $this->request->getGet('customer_id');
        if ($customerId) {
            $data['selected_customer_id'] = $customerId;
        }

        return view('billing/department', $data);
    }

    /**
     * 부서묶음 청구 페이지
     */
    public function departmentGroup()
    {
        // 프로토타입용 Mock 데이터
        $data = [
            'title' => '부서묶음 청구',
            'content_header' => [
                'title' => '부서묶음 청구',
                'description' => '여러 부서를 묶어서 청구서를 생성할 수 있습니다.'
            ],
            'customers' => [
                [
                    'id' => 1,
                    'company_name' => 'STN 네트워크',
                    'hierarchy_level' => 'head_office',
                    'is_active' => 1
                ],
                [
                    'id' => 2,
                    'company_name' => 'STN 서울지사',
                    'hierarchy_level' => 'branch',
                    'is_active' => 1
                ],
                [
                    'id' => 3,
                    'company_name' => 'STN 강남대리점',
                    'hierarchy_level' => 'agency',
                    'is_active' => 1
                ]
            ]
        ];

        return view('billing/department_group', $data);
    }

    /**
     * 고객묶음 청구 페이지
     */
    public function customerGroup()
    {
        // 프로토타입용 Mock 데이터
        $data = [
            'title' => '고객묶음 청구',
            'content_header' => [
                'title' => '고객묶음 청구',
                'description' => '여러 고객사를 묶어서 청구서를 생성할 수 있습니다.'
            ],
            'customers' => [
                [
                    'id' => 1,
                    'company_name' => 'STN 네트워크',
                    'hierarchy_level' => 'head_office',
                    'is_active' => 1
                ],
                [
                    'id' => 2,
                    'company_name' => 'STN 서울지사',
                    'hierarchy_level' => 'branch',
                    'is_active' => 1
                ],
                [
                    'id' => 3,
                    'company_name' => 'STN 강남대리점',
                    'hierarchy_level' => 'agency',
                    'is_active' => 1
                ]
            ]
        ];

        return view('billing/customer_group', $data);
    }

    /**
     * 청구서 생성 페이지
     */
    public function create()
    {
        $billingType = $this->request->getGet('type');
        
        if (!in_array($billingType, ['department', 'department_group', 'customer_group'])) {
            return redirect()->to('/billing')->with('error', '유효하지 않은 청구 유형입니다.');
        }

        $data = [
            'title' => '청구서 생성',
            'billing_type' => $billingType,
            'customers' => $this->customerHierarchyModel->getActiveCustomers(),
            'departments' => [],
            'unbilled_orders' => []
        ];

        // 고객사 선택 시 관련 데이터 조회
        $customerId = $this->request->getGet('customer_id');
        if ($customerId) {
            $data['departments'] = $this->departmentModel->getDepartmentsByCustomer($customerId);
            $data['selected_customer_id'] = $customerId;
            
            // 미청구 주문 조회
            $data['unbilled_orders'] = $this->billingDetailModel->getUnbilledOrders($customerId);
        }

        return view('billing/create', $data);
    }

    /**
     * 청구서 생성 처리
     */
    public function store()
    {
        $rules = [
            'billing_type' => 'required|in_list[department,department_group,customer_group]',
            'billing_period_start' => 'required|valid_date',
            'billing_period_end' => 'required|valid_date',
            'billing_date' => 'required|valid_date',
            'due_date' => 'required|valid_date',
            'order_ids' => 'required'
        ];

        if (!$this->validate($rules)) {
            return redirect()->back()->withInput()->with('errors', $this->validator->getErrors());
        }

        $data = $this->request->getPost();
        $orderIds = $data['order_ids'];
        
        // 주문 ID 배열 처리
        if (is_string($orderIds)) {
            $orderIds = explode(',', $orderIds);
        }

        // 청구서 기본 데이터 구성
        $billingData = [
            'billing_type' => $data['billing_type'],
            'billing_period_start' => $data['billing_period_start'],
            'billing_period_end' => $data['billing_period_end'],
            'billing_date' => $data['billing_date'],
            'due_date' => $data['due_date'],
            'customer_id' => $data['customer_id'] ?? null,
            'department_ids' => isset($data['department_ids']) ? json_encode($data['department_ids']) : null,
            'customer_ids' => isset($data['customer_ids']) ? json_encode($data['customer_ids']) : null,
            'billing_notes' => $data['billing_notes'] ?? null,
            'created_by' => session()->get('user_id')
        ];

        try {
            $billingId = $this->billingModel->createBillingFromOrders($billingData, $orderIds);
            
            return redirect()->to('/billing/' . $billingId)->with('success', '청구서가 성공적으로 생성되었습니다.');
            
        } catch (\Exception $e) {
            return redirect()->back()->withInput()->with('error', $e->getMessage());
        }
    }

    /**
     * 청구서 상세 페이지
     */
    public function show($id)
    {
        $billing = $this->billingModel->getBillingRequestDetails($id);
        
        if (!$billing) {
            throw new \CodeIgniter\Exceptions\PageNotFoundException('청구서를 찾을 수 없습니다.');
        }

        $data = [
            'title' => '청구서 상세',
            'billing' => $billing,
            'billing_details' => $this->billingDetailModel->getBillingDetailsByRequest($id)
        ];

        return view('billing/show', $data);
    }

    /**
     * 청구서 수정 페이지
     */
    public function edit($id)
    {
        $billing = $this->billingModel->find($id);
        
        if (!$billing) {
            throw new \CodeIgniter\Exceptions\PageNotFoundException('청구서를 찾을 수 없습니다.');
        }

        if ($billing['status'] !== 'draft') {
            return redirect()->to('/billing/' . $id)->with('error', '초안 상태의 청구서만 수정할 수 있습니다.');
        }

        $data = [
            'title' => '청구서 수정',
            'billing' => $billing,
            'billing_details' => $this->billingDetailModel->getBillingDetailsByRequest($id, false)
        ];

        return view('billing/edit', $data);
    }

    /**
     * 청구서 수정 처리
     */
    public function update($id)
    {
        $billing = $this->billingModel->find($id);
        
        if (!$billing) {
            throw new \CodeIgniter\Exceptions\PageNotFoundException('청구서를 찾을 수 없습니다.');
        }

        if ($billing['status'] !== 'draft') {
            return redirect()->to('/billing/' . $id)->with('error', '초안 상태의 청구서만 수정할 수 있습니다.');
        }

        $rules = [
            'billing_period_start' => 'required|valid_date',
            'billing_period_end' => 'required|valid_date',
            'billing_date' => 'required|valid_date',
            'due_date' => 'required|valid_date',
            'billing_notes' => 'permit_empty'
        ];

        if (!$this->validate($rules)) {
            return redirect()->back()->withInput()->with('errors', $this->validator->getErrors());
        }

        $data = $this->request->getPost();
        
        if ($this->billingModel->update($id, $data)) {
            return redirect()->to('/billing/' . $id)->with('success', '청구서가 성공적으로 수정되었습니다.');
        } else {
            return redirect()->back()->withInput()->with('error', '청구서 수정에 실패했습니다.');
        }
    }

    /**
     * 청구서 상태 업데이트
     */
    public function updateStatus($id)
    {
        $billing = $this->billingModel->find($id);
        
        if (!$billing) {
            return $this->response->setJSON(['success' => false, 'message' => '청구서를 찾을 수 없습니다.']);
        }

        $status = $this->request->getPost('status');
        $paymentStatus = $this->request->getPost('payment_status');
        $paymentMethod = $this->request->getPost('payment_method');
        $paymentDate = $this->request->getPost('payment_date');

        if ($this->billingModel->updateBillingStatus($id, $status, $paymentStatus, $paymentMethod, $paymentDate)) {
            return $this->response->setJSON(['success' => true, 'message' => '청구서 상태가 업데이트되었습니다.']);
        } else {
            return $this->response->setJSON(['success' => false, 'message' => '청구서 상태 업데이트에 실패했습니다.']);
        }
    }

    /**
     * 청구서 삭제
     */
    public function delete($id)
    {
        $billing = $this->billingModel->find($id);
        
        if (!$billing) {
            return $this->response->setJSON(['success' => false, 'message' => '청구서를 찾을 수 없습니다.']);
        }

        if ($billing['status'] !== 'draft') {
            return $this->response->setJSON(['success' => false, 'message' => '초안 상태의 청구서만 삭제할 수 있습니다.']);
        }

        if ($this->billingModel->delete($id)) {
            return $this->response->setJSON(['success' => true, 'message' => '청구서가 삭제되었습니다.']);
        } else {
            return $this->response->setJSON(['success' => false, 'message' => '청구서 삭제에 실패했습니다.']);
        }
    }

    /**
     * 미청구 주문 조회 (AJAX)
     */
    public function getUnbilledOrders()
    {
        $customerId = $this->request->getGet('customer_id');
        $departmentId = $this->request->getGet('department_id');
        $startDate = $this->request->getGet('start_date');
        $endDate = $this->request->getGet('end_date');

        $orders = $this->billingDetailModel->getUnbilledOrders($customerId, $departmentId, $startDate, $endDate);
        
        return $this->response->setJSON(['success' => true, 'data' => $orders]);
    }

    /**
     * 청구서 통계 조회 (AJAX)
     */
    public function getStatistics()
    {
        $startDate = $this->request->getGet('start_date');
        $endDate = $this->request->getGet('end_date');
        $customerId = $this->request->getGet('customer_id');
        $departmentId = $this->request->getGet('department_id');

        $statistics = $this->billingModel->getBillingStatistics($startDate, $endDate, $customerId, $departmentId);
        
        return $this->response->setJSON(['success' => true, 'data' => $statistics]);
    }

    /**
     * 청구서 검색 (AJAX)
     */
    public function search()
    {
        $searchTerm = $this->request->getGet('search');
        $filters = [
            'billing_type' => $this->request->getGet('billing_type'),
            'status' => $this->request->getGet('status'),
            'start_date' => $this->request->getGet('start_date'),
            'end_date' => $this->request->getGet('end_date')
        ];

        $billings = $this->billingModel->searchBillingRequests($searchTerm, $filters, 20, 0);
        
        return $this->response->setJSON(['success' => true, 'data' => $billings]);
    }

    /**
     * 청구서 발송 처리
     */
    public function send($id)
    {
        $billing = $this->billingModel->find($id);
        
        if (!$billing) {
            return $this->response->setJSON(['success' => false, 'message' => '청구서를 찾을 수 없습니다.']);
        }

        if ($billing['status'] !== 'pending') {
            return $this->response->setJSON(['success' => false, 'message' => '발송 가능한 상태가 아닙니다.']);
        }

        // 청구서 상태를 'sent'로 변경
        if ($this->billingModel->updateBillingStatus($id, 'sent')) {
            // TODO: 실제 이메일 발송 로직 구현
            return $this->response->setJSON(['success' => true, 'message' => '청구서가 발송되었습니다.']);
        } else {
            return $this->response->setJSON(['success' => false, 'message' => '청구서 발송에 실패했습니다.']);
        }
    }
}
