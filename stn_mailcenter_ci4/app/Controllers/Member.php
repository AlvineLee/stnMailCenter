<?php

namespace App\Controllers;

use App\Controllers\BaseController;
use App\Models\UserModel;
use App\Models\CustomerHierarchyModel;
use App\Models\AuthModel;
use App\Models\InsungUsersListModel;

class Member extends BaseController
{
    protected $userModel;
    protected $customerHierarchyModel;
    protected $authModel;
    protected $insungUsersListModel;

    public function __construct()
    {
        $this->userModel = new UserModel();
        $this->customerHierarchyModel = new CustomerHierarchyModel();
        $this->authModel = new AuthModel();
        $this->insungUsersListModel = new InsungUsersListModel();
        helper('form');
    }

    public function list()
    {
        // 로그인 체크
        if (!session()->get('is_logged_in')) {
            return redirect()->to('/auth/login');
        }

        $loginType = session()->get('login_type');
        $userId = session()->get('user_id');
        
        // 로그인 타입에 따라 다른 모델 사용
        if ($loginType === 'daumdata') {
            // daumdata 로그인: InsungUsersListModel 사용
            $userInfo = $this->insungUsersListModel->getUserWithCompanyInfo($userId);
            
            if (!$userInfo) {
                return redirect()->to('/')
                    ->with('error', '사용자 정보를 찾을 수 없습니다.');
            }
            
            // daumdata 사용자 정보를 STN 형식으로 변환
            $userInfo = [
                'username' => $userInfo['user_id'] ?? '',
                'real_name' => $userInfo['user_name'] ?? '',
                'phone' => $userInfo['user_tel1'] ?? '',
                'customer_name' => $userInfo['comp_name'] ?? '',
                'address_zonecode' => '', // daumdata에는 zonecode가 없을 수 있음
                'address' => $userInfo['user_addr'] ?? '',
                'address_detail' => $userInfo['user_addr_detail'] ?? '',
                'login_type' => 'daumdata',
                'user_idx' => $userInfo['idx'] ?? null,
                'comp_code' => $userInfo['comp_code'] ?? null
            ];
        } else {
            // STN 로그인: UserModel 사용
            $userInfo = $this->userModel->getUserAccountInfo($userId);
            
            if (!$userInfo) {
                return redirect()->to('/')
                    ->with('error', '사용자 정보를 찾을 수 없습니다.');
            }
            
            $userInfo['login_type'] = 'stn';
        }

        $data = [
            'title' => '회원정보 변경',
            'content_header' => [
                'title' => '회원정보 변경',
                'description' => ''
            ],
            'user' => $userInfo
        ];

        return view('member/list', $data);
    }

    /**
     * 비밀번호 변경 처리
     */
    public function changePassword()
    {
        // 로그인 체크
        if (!session()->get('is_logged_in')) {
            return $this->response->setJSON([
                'success' => false,
                'message' => '로그인이 필요합니다.'
            ])->setStatusCode(401);
        }

        $userId = session()->get('user_id');

        // JSON 요청 처리
        $inputData = $this->request->getJSON(true);
        if (empty($inputData)) {
            $inputData = $this->request->getPost();
        }

        $validation = \Config\Services::validation();
        $validation->setRules([
            'current_password' => 'required',
            'new_password' => 'required|min_length[4]',
            'new_password_confirm' => 'required|matches[new_password]'
        ], [
            'current_password' => [
                'required' => '현재 비밀번호를 입력해주세요.'
            ],
            'new_password' => [
                'required' => '새 비밀번호를 입력해주세요.',
                'min_length' => '비밀번호는 최소 4자 이상이어야 합니다.'
            ],
            'new_password_confirm' => [
                'required' => '새 비밀번호 확인을 입력해주세요.',
                'matches' => '새 비밀번호가 일치하지 않습니다.'
            ]
        ]);

        if (!$validation->run($inputData)) {
            return $this->response->setJSON([
                'success' => false,
                'message' => '입력값 검증에 실패했습니다.',
                'errors' => $validation->getErrors()
            ])->setStatusCode(400);
        }

        try {
            $loginType = session()->get('login_type');
            
            // 로그인 타입에 따라 다른 모델 사용
            if ($loginType === 'daumdata') {
                // daumdata 로그인: InsungUsersListModel 사용
                $user = $this->insungUsersListModel->getUserWithCompanyInfo($userId);
                
                if (!$user) {
                    return $this->response->setJSON([
                        'success' => false,
                        'message' => '사용자를 찾을 수 없습니다.'
                    ])->setStatusCode(404);
                }

                // 현재 비밀번호 확인 (daumdata는 평문 비밀번호)
                if ($user['user_pass'] !== $inputData['current_password']) {
                    return $this->response->setJSON([
                        'success' => false,
                        'message' => '현재 비밀번호가 올바르지 않습니다.'
                    ])->setStatusCode(400);
                }

                // 비밀번호 변경 (daumdata는 평문으로 저장)
                $updateResult = $this->insungUsersListModel->update($user['idx'], [
                    'user_pass' => $inputData['new_password']
                ]);

                if (!$updateResult) {
                    throw new \Exception('비밀번호 변경에 실패했습니다.');
                }
            } else {
                // STN 로그인: UserModel 사용
                $user = $this->userModel->find($userId);
                
                if (!$user) {
                    return $this->response->setJSON([
                        'success' => false,
                        'message' => '사용자를 찾을 수 없습니다.'
                    ])->setStatusCode(404);
                }

                // 현재 비밀번호 확인
                if (!password_verify($inputData['current_password'], $user['password'])) {
                    return $this->response->setJSON([
                        'success' => false,
                        'message' => '현재 비밀번호가 올바르지 않습니다.'
                    ])->setStatusCode(400);
                }

                // 비밀번호 변경 (UserModel의 beforeUpdate에서 자동 해시 처리됨)
                $updateResult = $this->userModel->update($userId, [
                    'password' => $inputData['new_password'] // 평문 비밀번호 (UserModel이 자동 해시)
                ]);

                if (!$updateResult) {
                    throw new \Exception('비밀번호 변경에 실패했습니다.');
                }
            }

            return $this->response->setJSON([
                'success' => true,
                'message' => '비밀번호가 성공적으로 변경되었습니다.'
            ]);

        } catch (\Exception $e) {
            log_message('error', 'Member::changePassword - ' . $e->getMessage());
            
            return $this->response->setJSON([
                'success' => false,
                'message' => $e->getMessage()
            ])->setStatusCode(500);
        }
    }

    /**
     * 주소 업데이트 처리
     */
    public function updateAddress()
    {
        // 로그인 체크
        if (!session()->get('is_logged_in')) {
            return $this->response->setJSON([
                'success' => false,
                'message' => '로그인이 필요합니다.'
            ])->setStatusCode(401);
        }

        $userId = session()->get('user_id');

        // JSON 요청 처리
        $inputData = $this->request->getJSON(true);
        if (empty($inputData)) {
            $inputData = $this->request->getPost();
        }

        $validation = \Config\Services::validation();
        $validation->setRules([
            'address' => 'required'
        ], [
            'address' => [
                'required' => '주소를 입력해주세요.'
            ]
        ]);

        if (!$validation->run($inputData)) {
            return $this->response->setJSON([
                'success' => false,
                'message' => '입력값 검증에 실패했습니다.',
                'errors' => $validation->getErrors()
            ])->setStatusCode(400);
        }

        try {
            $loginType = session()->get('login_type');
            
            // 로그인 타입에 따라 다른 모델 사용
            if ($loginType === 'daumdata') {
                // daumdata 로그인: InsungUsersListModel 사용
                $user = $this->insungUsersListModel->getUserWithCompanyInfo($userId);
                
                if (!$user) {
                    return $this->response->setJSON([
                        'success' => false,
                        'message' => '사용자를 찾을 수 없습니다.'
                    ])->setStatusCode(404);
                }

                // 사용자 주소 업데이트 (daumdata 필드명 사용)
                $updateData = [
                    'user_addr' => $inputData['address'] ?? null,
                    'user_addr_detail' => $inputData['address_detail'] ?? null
                ];

                // null 값 제거
                $updateData = array_filter($updateData, function($value) {
                    return $value !== null;
                });

                if (empty($updateData)) {
                    return $this->response->setJSON([
                        'success' => false,
                        'message' => '저장할 주소 정보가 없습니다.'
                    ])->setStatusCode(400);
                }

                $updateResult = $this->insungUsersListModel->update($user['idx'], $updateData);

                if (!$updateResult) {
                    throw new \Exception('주소 저장에 실패했습니다.');
                }
            } else {
                // STN 로그인: UserModel 사용
                $user = $this->userModel->find($userId);
                
                if (!$user) {
                    return $this->response->setJSON([
                        'success' => false,
                        'message' => '사용자를 찾을 수 없습니다.'
                    ])->setStatusCode(404);
                }

                // 사용자 주소 업데이트
                $updateData = [
                    'address_zonecode' => $inputData['address_zonecode'] ?? null,
                    'address' => $inputData['address'] ?? null,
                    'address_detail' => $inputData['address_detail'] ?? null
                ];

                // null 값 제거
                $updateData = array_filter($updateData, function($value) {
                    return $value !== null;
                });

                if (empty($updateData)) {
                    return $this->response->setJSON([
                        'success' => false,
                        'message' => '저장할 주소 정보가 없습니다.'
                    ])->setStatusCode(400);
                }

                $updateResult = $this->userModel->update($userId, $updateData);

                if (!$updateResult) {
                    throw new \Exception('주소 저장에 실패했습니다.');
                }
            }

            return $this->response->setJSON([
                'success' => true,
                'message' => '주소가 성공적으로 저장되었습니다.'
            ]);

        } catch (\Exception $e) {
            log_message('error', 'Member::updateAddress - ' . $e->getMessage());
            
            return $this->response->setJSON([
                'success' => false,
                'message' => $e->getMessage()
            ])->setStatusCode(500);
        }
    }

    /**
     * 사용자 정보 업데이트 처리 (담당자명, 연락처, 주소, 비밀번호)
     */
    public function updateUserInfo()
    {
        // 로그인 체크
        if (!session()->get('is_logged_in')) {
            return $this->response->setJSON([
                'success' => false,
                'message' => '로그인이 필요합니다.'
            ])->setStatusCode(401);
        }

        $userId = session()->get('user_id');

        // JSON 요청 처리
        $inputData = $this->request->getJSON(true);
        if (empty($inputData)) {
            $inputData = $this->request->getPost();
        }

        // 비밀번호 변경 여부 확인
        $isPasswordChange = !empty($inputData['current_password']) || 
                           !empty($inputData['new_password']) || 
                           !empty($inputData['new_password_confirm']);

        // 유효성 검사 규칙 설정
        $validationRules = [
            'real_name' => 'required|min_length[2]|max_length[50]',
            'phone' => 'permit_empty|min_length[4]|max_length[20]'
        ];

        $validationMessages = [
            'real_name' => [
                'required' => '담당자명을 입력해주세요.',
                'min_length' => '담당자명은 최소 2자 이상이어야 합니다.',
                'max_length' => '담당자명은 최대 50자까지 가능합니다.'
            ],
            'phone' => [
                'min_length' => '연락처는 최소 4자 이상이어야 합니다.',
                'max_length' => '연락처는 최대 20자까지 가능합니다.'
            ]
        ];

        // 비밀번호 변경이 있는 경우 추가 검증
        if ($isPasswordChange) {
            $validationRules['current_password'] = 'required';
            $validationRules['new_password'] = 'required|min_length[4]';
            $validationRules['new_password_confirm'] = 'required|matches[new_password]';

            $validationMessages['current_password'] = [
                'required' => '현재 비밀번호를 입력해주세요.'
            ];
            $validationMessages['new_password'] = [
                'required' => '새 비밀번호를 입력해주세요.',
                'min_length' => '비밀번호는 최소 4자 이상이어야 합니다.'
            ];
            $validationMessages['new_password_confirm'] = [
                'required' => '새 비밀번호 확인을 입력해주세요.',
                'matches' => '새 비밀번호가 일치하지 않습니다.'
            ];
        }

        $validation = \Config\Services::validation();
        $validation->setRules($validationRules, $validationMessages);

        if (!$validation->run($inputData)) {
            return $this->response->setJSON([
                'success' => false,
                'message' => '입력값 검증에 실패했습니다.',
                'errors' => $validation->getErrors()
            ])->setStatusCode(400);
        }

        try {
            $loginType = session()->get('login_type');
            
            // 로그인 타입에 따라 다른 모델 사용
            if ($loginType === 'daumdata') {
                // daumdata 로그인: InsungUsersListModel 사용
                $user = $this->insungUsersListModel->getUserWithCompanyInfo($userId);
                
                if (!$user) {
                    return $this->response->setJSON([
                        'success' => false,
                        'message' => '사용자를 찾을 수 없습니다.'
                    ])->setStatusCode(404);
                }

                // 비밀번호 변경이 있는 경우 현재 비밀번호 확인 (daumdata는 평문)
                if ($isPasswordChange) {
                    if ($user['user_pass'] !== $inputData['current_password']) {
                        return $this->response->setJSON([
                            'success' => false,
                            'message' => '현재 비밀번호가 올바르지 않습니다.'
                        ])->setStatusCode(400);
                    }
                }

                // 사용자 정보 업데이트 (daumdata 필드명 사용)
                $updateData = [
                    'user_name' => $inputData['real_name'],
                    'user_tel1' => $inputData['phone'] ?? null,
                    'user_addr' => $inputData['address'] ?? null,
                    'user_addr_detail' => $inputData['address_detail'] ?? null
                ];

                // 비밀번호 변경이 있는 경우 추가 (daumdata는 평문으로 저장)
                if ($isPasswordChange) {
                    $updateData['user_pass'] = $inputData['new_password'];
                }

                // null 값 제거
                $updateData = array_filter($updateData, function($value) {
                    return $value !== null;
                });

                $updateResult = $this->insungUsersListModel->update($user['idx'], $updateData);

                if (!$updateResult) {
                    throw new \Exception('정보 저장에 실패했습니다.');
                }
            } else {
                // STN 로그인: UserModel 사용
                $user = $this->userModel->find($userId);
                
                if (!$user) {
                    return $this->response->setJSON([
                        'success' => false,
                        'message' => '사용자를 찾을 수 없습니다.'
                    ])->setStatusCode(404);
                }

                // 비밀번호 변경이 있는 경우 현재 비밀번호 확인
                if ($isPasswordChange) {
                    if (!password_verify($inputData['current_password'], $user['password'])) {
                        return $this->response->setJSON([
                            'success' => false,
                            'message' => '현재 비밀번호가 올바르지 않습니다.'
                        ])->setStatusCode(400);
                    }
                }

                // 사용자 정보 업데이트
                $updateData = [
                    'real_name' => $inputData['real_name'],
                    'phone' => $inputData['phone'] ?? null,
                    'address_zonecode' => $inputData['address_zonecode'] ?? null,
                    'address' => $inputData['address'] ?? null,
                    'address_detail' => $inputData['address_detail'] ?? null
                ];

                // 비밀번호 변경이 있는 경우 추가
                if ($isPasswordChange) {
                    $updateData['password'] = $inputData['new_password']; // 평문 비밀번호 (UserModel이 자동 해시)
                }

                // null 값 제거
                $updateData = array_filter($updateData, function($value) {
                    return $value !== null;
                });

                $updateResult = $this->userModel->update($userId, $updateData);

                if (!$updateResult) {
                    throw new \Exception('정보 저장에 실패했습니다.');
                }
            }

            $message = '정보가 성공적으로 저장되었습니다.';
            if ($isPasswordChange) {
                $message = '정보와 비밀번호가 성공적으로 저장되었습니다.';
            }

            return $this->response->setJSON([
                'success' => true,
                'message' => $message
            ]);

        } catch (\Exception $e) {
            log_message('error', 'Member::updateUserInfo - ' . $e->getMessage());
            
            return $this->response->setJSON([
                'success' => false,
                'message' => $e->getMessage()
            ])->setStatusCode(500);
        }
    }
}
