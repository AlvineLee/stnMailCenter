<?php

namespace App\Controllers;

use App\Models\DepartmentModel;
use App\Models\CustomerHierarchyModel;
use App\Models\UserModel;

class Department extends BaseController
{
    protected $departmentModel;
    protected $customerHierarchyModel;
    protected $userModel;

    public function __construct()
    {
        // 프로토타입 단계에서는 모델 초기화 비활성화
        // $this->departmentModel = new DepartmentModel();
        // $this->customerHierarchyModel = new CustomerHierarchyModel();
        // $this->userModel = new UserModel();
    }

    /**
     * 부서 목록 페이지
     */
    public function index()
    {
        // 프로토타입용 Mock 데이터
        $data = [
            'title' => '부서 관리',
            'content_header' => [
                'title' => '부서 관리',
                'description' => '부서 정보를 관리하고 조회할 수 있습니다.'
            ],
            'departments' => [
                [
                    'id' => 1,
                    'department_code' => 'DEPT001',
                    'department_name' => '개발팀',
                    'manager_name' => '김개발',
                    'manager_contact' => '010-1234-5678',
                    'is_active' => 1,
                    'created_at' => '2024-01-15 09:00:00'
                ],
                [
                    'id' => 2,
                    'department_code' => 'DEPT002',
                    'department_name' => '마케팅팀',
                    'manager_name' => '이마케팅',
                    'manager_contact' => '010-2345-6789',
                    'is_active' => 1,
                    'created_at' => '2024-01-16 10:00:00'
                ],
                [
                    'id' => 3,
                    'department_code' => 'DEPT003',
                    'department_name' => '영업팀',
                    'manager_name' => '박영업',
                    'manager_contact' => '010-3456-7890',
                    'is_active' => 1,
                    'created_at' => '2024-01-17 11:00:00'
                ]
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

        // 고객사 필터
        $customerId = $this->request->getGet('customer_id');
        if ($customerId) {
            $data['selected_customer_id'] = $customerId;
            // 고객사별 부서 필터링 (Mock)
            $data['departments'] = array_filter($data['departments'], function($dept) use ($customerId) {
                return $dept['id'] <= 3; // 임시 필터링
            });
        }

        return view('department/index', $data);
    }

    /**
     * 부서 생성 페이지
     */
    public function create()
    {
        // 프로토타입용 Mock 데이터
        $data = [
            'title' => '부서 등록',
            'content_header' => [
                'title' => '부서 등록',
                'description' => '새로운 부서를 등록합니다.'
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
            'parent_departments' => []
        ];

        // 고객사 선택 시 상위 부서 목록 조회
        $customerId = $this->request->getGet('customer_id');
        if ($customerId) {
            $data['selected_customer_id'] = $customerId;
        }

        return view('department/create', $data);
    }

    /**
     * 부서 계층 페이지
     */
    public function getHierarchy()
    {
        // 프로토타입용 Mock 데이터
        $data = [
            'title' => '부서 계층',
            'content_header' => [
                'title' => '부서 계층',
                'description' => '부서의 계층 구조를 확인할 수 있습니다.'
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

        return view('department/hierarchy', $data);
    }

    /**
     * 부서 생성 처리
     */
    public function store()
    {
        $rules = [
            'customer_id' => 'required|integer',
            'department_code' => 'required|max_length[20]',
            'department_name' => 'required|max_length[100]',
            'parent_department_id' => 'permit_empty|integer',
            'manager_name' => 'permit_empty|max_length[50]',
            'manager_contact' => 'permit_empty|max_length[20]',
            'manager_email' => 'permit_empty|valid_email|max_length[100]',
            'cost_center' => 'permit_empty|max_length[20]',
            'budget_limit' => 'permit_empty|decimal',
            'notes' => 'permit_empty'
        ];

        if (!$this->validate($rules)) {
            return redirect()->back()->withInput()->with('errors', $this->validator->getErrors());
        }

        $data = $this->request->getPost();
        
        // 부서 코드 중복 확인
        if (!$this->departmentModel->isDepartmentCodeUnique($data['customer_id'], $data['department_code'])) {
            return redirect()->back()->withInput()->with('error', '이미 사용 중인 부서 코드입니다.');
        }

        // 부서 레벨 계산
        if (!empty($data['parent_department_id'])) {
            $parentDepartment = $this->departmentModel->find($data['parent_department_id']);
            $data['department_level'] = $parentDepartment['department_level'] + 1;
        } else {
            $data['department_level'] = 1;
        }

        // 최대 레벨 제한 (3단계)
        if ($data['department_level'] > 3) {
            return redirect()->back()->withInput()->with('error', '부서는 최대 3단계까지만 생성할 수 있습니다.');
        }

        if ($this->departmentModel->insert($data)) {
            return redirect()->to('/department')->with('success', '부서가 성공적으로 등록되었습니다.');
        } else {
            return redirect()->back()->withInput()->with('error', '부서 등록에 실패했습니다.');
        }
    }

    /**
     * 부서 상세 페이지
     */
    public function show($id)
    {
        $department = $this->departmentModel->find($id);
        
        if (!$department) {
            throw new \CodeIgniter\Exceptions\PageNotFoundException('부서를 찾을 수 없습니다.');
        }

        $data = [
            'title' => '부서 상세',
            'department' => $department,
            'user_count' => $this->departmentModel->getDepartmentUserCount($id),
            'budget_usage' => $this->departmentModel->getDepartmentBudgetUsage($id),
            'parent_department' => null,
            'child_departments' => []
        ];

        // 상위 부서 정보
        if ($department['parent_department_id']) {
            $data['parent_department'] = $this->departmentModel->find($department['parent_department_id']);
        }

        // 하위 부서 목록
        $data['child_departments'] = $this->departmentModel->getDepartmentHierarchy($department['customer_id'], $id);

        return view('department/show', $data);
    }

    /**
     * 부서 수정 페이지
     */
    public function edit($id)
    {
        $department = $this->departmentModel->find($id);
        
        if (!$department) {
            throw new \CodeIgniter\Exceptions\PageNotFoundException('부서를 찾을 수 없습니다.');
        }

        $data = [
            'title' => '부서 수정',
            'department' => $department,
            'customers' => $this->customerHierarchyModel->getActiveCustomers(),
            'parent_departments' => $this->departmentModel->getDepartmentsByCustomer($department['customer_id'])
        ];

        return view('department/edit', $data);
    }

    /**
     * 부서 수정 처리
     */
    public function update($id)
    {
        $department = $this->departmentModel->find($id);
        
        if (!$department) {
            throw new \CodeIgniter\Exceptions\PageNotFoundException('부서를 찾을 수 없습니다.');
        }

        $rules = [
            'customer_id' => 'required|integer',
            'department_code' => 'required|max_length[20]',
            'department_name' => 'required|max_length[100]',
            'parent_department_id' => 'permit_empty|integer',
            'manager_name' => 'permit_empty|max_length[50]',
            'manager_contact' => 'permit_empty|max_length[20]',
            'manager_email' => 'permit_empty|valid_email|max_length[100]',
            'cost_center' => 'permit_empty|max_length[20]',
            'budget_limit' => 'permit_empty|decimal',
            'notes' => 'permit_empty'
        ];

        if (!$this->validate($rules)) {
            return redirect()->back()->withInput()->with('errors', $this->validator->getErrors());
        }

        $data = $this->request->getPost();
        
        // 부서 코드 중복 확인 (자기 자신 제외)
        if (!$this->departmentModel->isDepartmentCodeUnique($data['customer_id'], $data['department_code'], $id)) {
            return redirect()->back()->withInput()->with('error', '이미 사용 중인 부서 코드입니다.');
        }

        // 부서 레벨 계산
        if (!empty($data['parent_department_id'])) {
            $parentDepartment = $this->departmentModel->find($data['parent_department_id']);
            $data['department_level'] = $parentDepartment['department_level'] + 1;
        } else {
            $data['department_level'] = 1;
        }

        // 최대 레벨 제한 (3단계)
        if ($data['department_level'] > 3) {
            return redirect()->back()->withInput()->with('error', '부서는 최대 3단계까지만 생성할 수 있습니다.');
        }

        if ($this->departmentModel->update($id, $data)) {
            return redirect()->to('/department/' . $id)->with('success', '부서가 성공적으로 수정되었습니다.');
        } else {
            return redirect()->back()->withInput()->with('error', '부서 수정에 실패했습니다.');
        }
    }

    /**
     * 부서 삭제 처리
     */
    public function delete($id)
    {
        $department = $this->departmentModel->find($id);
        
        if (!$department) {
            return $this->response->setJSON(['success' => false, 'message' => '부서를 찾을 수 없습니다.']);
        }

        // 삭제 가능 여부 확인
        if (!$this->departmentModel->canDeleteDepartment($id)) {
            return $this->response->setJSON(['success' => false, 'message' => '사용자나 주문이 있는 부서는 삭제할 수 없습니다.']);
        }

        if ($this->departmentModel->delete($id)) {
            return $this->response->setJSON(['success' => true, 'message' => '부서가 성공적으로 삭제되었습니다.']);
        } else {
            return $this->response->setJSON(['success' => false, 'message' => '부서 삭제에 실패했습니다.']);
        }
    }

    /**
     * 부서 활성화/비활성화
     */
    public function toggleStatus($id)
    {
        $department = $this->departmentModel->find($id);
        
        if (!$department) {
            return $this->response->setJSON(['success' => false, 'message' => '부서를 찾을 수 없습니다.']);
        }

        $newStatus = !$department['is_active'];
        
        if ($this->departmentModel->toggleDepartmentStatus($id, $newStatus)) {
            $statusText = $newStatus ? '활성화' : '비활성화';
            return $this->response->setJSON(['success' => true, 'message' => "부서가 {$statusText}되었습니다."]);
        } else {
            return $this->response->setJSON(['success' => false, 'message' => '부서 상태 변경에 실패했습니다.']);
        }
    }

    /**
     * 부서 검색 (AJAX)
     */
    public function search()
    {
        $customerId = $this->request->getGet('customer_id');
        $searchTerm = $this->request->getGet('search');
        
        if (!$customerId || !$searchTerm) {
            return $this->response->setJSON(['success' => false, 'message' => '검색 조건이 올바르지 않습니다.']);
        }

        $departments = $this->departmentModel->searchDepartments($customerId, $searchTerm);
        
        return $this->response->setJSON(['success' => true, 'data' => $departments]);
    }

    /**
     * 고객사별 부서 목록 (AJAX)
     */
    public function getByCustomer()
    {
        $customerId = $this->request->getGet('customer_id');
        
        if (!$customerId) {
            return $this->response->setJSON(['success' => false, 'message' => '고객사 ID가 필요합니다.']);
        }

        $departments = $this->departmentModel->getDepartmentsByCustomer($customerId);
        
        return $this->response->setJSON(['success' => true, 'data' => $departments]);
    }

    /**
     * 부서 계층 구조 조회 (AJAX)
     */
    public function getHierarchyAjax()
    {
        $customerId = $this->request->getGet('customer_id');
        
        if (!$customerId) {
            return $this->response->setJSON(['success' => false, 'message' => '고객사 ID가 필요합니다.']);
        }

        $hierarchy = $this->departmentModel->getDepartmentHierarchy($customerId);
        
        return $this->response->setJSON(['success' => true, 'data' => $hierarchy]);
    }
}
