# STN MailCenter 메뉴 구조 설계

## 📋 전체 시스템 메뉴 구조

### **1. 메인 대시보드**
- **홈** - 전체 현황 대시보드
- **알림** - 실시간 알림 센터

---

## 👤 사용자별 메뉴 구조

### **🔹 일반 사용자 (User)**
```
📦 주문관리
├── 주문접수
│   ├── 퀵서비스 (4개)
│   ├── 택배서비스 (4개) 
│   ├── 생활서비스 (6개)
│   ├── 일반서비스 (3개)
│   └── 해외/연계서비스 (7개)
├── 주문조회
├── 주문수정
└── 주문취소

📊 내 정보
├── 프로필 관리
├── 비밀번호 변경
├── 알림 설정
└── 이용내역
```

### **🔹 나도사장 (Boss)**
```
👑 나도사장 관리
├── 나도사장 대시보드
├── 하위 라인 관리
│   ├── 하위 나도사장 목록
│   ├── 하위 나도사장 등록
│   └── 하위 나도사장 승인
├── 계약업체 관리
│   ├── 계약업체 목록
│   ├── 계약업체 등록
│   └── 계약업체 계약 관리
├── 수수료 관리
│   ├── 수수료 현황
│   ├── 수수료 내역
│   └── 수수료 정산
└── 실적 관리
    ├── 월별 실적
    ├── 하위 라인 실적
    └── 실적 리포트

📦 주문관리 (일반 사용자와 동일)
📊 내 정보 (일반 사용자와 동일)
```

### **🔹 고객사 관리자 (Customer Admin)**
```
🏢 고객사 관리
├── 고객사 대시보드
├── 사용자 관리
│   ├── 사용자 목록
│   ├── 사용자 등록
│   └── 권한 관리
├── 부서 관리
│   ├── 부서 목록
│   ├── 부서 등록
│   ├── 부서 수정
│   └── 부서별 사용자 관리
├── 청구 관리
│   ├── 청구 현황
│   ├── 부서별 청구
│   ├── 부서묶음 청구
│   ├── 고객묶음 청구
│   ├── 청구서 생성
│   ├── 청구서 발송
│   └── 청구 내역 조회
├── 서비스 권한 관리
│   ├── 서비스 활성화/비활성화
│   ├── 주문 수 제한 설정
│   └── 특별 지시사항
├── 실적 관리
│   ├── 월별 실적
│   ├── 서비스별 실적
│   └── 실적 리포트
└── 정산 관리
    ├── 정산 현황
    ├── 정산 내역
    └── 정산 요청

📦 주문관리 (일반 사용자와 동일)
📊 내 정보 (일반 사용자와 동일)
```

### **🔹 시스템 관리자 (System Admin)**
```
⚙️ 시스템 관리
├── 시스템 대시보드
├── 사용자 관리
│   ├── 전체 사용자 목록
│   ├── 사용자 권한 관리
│   └── 사용자 상태 관리
├── 고객사 관리
│   ├── 고객사 목록
│   ├── 고객사 계층 관리
│   ├── 고객사 계약 관리
│   └── 부서 관리
├── 청구 관리
│   ├── 전체 청구 현황
│   ├── 청구 설정 관리
│   ├── 청구서 템플릿 관리
│   ├── 청구 발송 관리
│   └── 청구 통계 및 분석
├── 서비스 관리
│   ├── 서비스 타입 관리
│   ├── 서비스 권한 관리
│   └── 서비스 설정
├── 나도사장 관리
│   ├── 나도사장 신청 관리
│   ├── 나도사장 승인/거부
│   ├── 나도사장 계층 관리
│   └── 나도사장 실적 관리
├── 입점 관리
│   ├── 입점 신청 관리
│   ├── 입점 심사 평가
│   ├── 계약 관리
│   └── 실적 관리
├── API 연동 관리
│   ├── API 설정
│   ├── API 로그
│   └── API 상태 모니터링
├── 알림 관리
│   ├── 알림 템플릿 관리
│   ├── 알림 채널 관리
│   ├── 알림 큐 관리
│   └── 알림 통계
└── 시스템 설정
    ├── 기본 설정
    ├── 보안 설정
    └── 백업/복원

📊 통계 및 리포트
├── 주문 통계
├── 수수료 통계
├── 실적 통계
└── 시스템 통계
```

---

## 📄 필요한 페이지 목록

### **🔹 공통 페이지 (모든 사용자)**
1. **로그인/로그아웃**
   - `/auth/login` - 로그인 페이지
   - `/auth/logout` - 로그아웃 처리
   - `/auth/register` - 회원가입 페이지

2. **대시보드**
   - `/dashboard` - 메인 대시보드
   - `/dashboard/notifications` - 알림 센터

3. **프로필 관리**
   - `/profile` - 프로필 조회/수정
   - `/profile/password` - 비밀번호 변경
   - `/profile/notifications` - 알림 설정

### **🔹 주문 관련 페이지**
4. **주문 접수**
   - `/orders/create` - 주문 접수 메인
   - `/orders/create/quick-motorcycle` - 퀵오토바이
   - `/orders/create/quick-vehicle` - 퀵차량
   - `/orders/create/quick-flex` - 퀵플렉스
   - `/orders/create/quick-moving` - 퀵이사
   - `/orders/create/parcel-visit` - 방문택배
   - `/orders/create/parcel-same-day` - 당일택배
   - `/orders/create/parcel-convenience` - 편의점택배
   - `/orders/create/parcel-bag` - 택배백
   - `/orders/create/life-buy` - 사다주기
   - `/orders/create/life-taxi` - 택시
   - `/orders/create/life-driver` - 대리운전
   - `/orders/create/life-wreath` - 화환
   - `/orders/create/life-accommodation` - 숙박
   - `/orders/create/life-stationery` - 문구
   - `/orders/create/general-document` - 사내문서
   - `/orders/create/general-errand` - 개인심부름
   - `/orders/create/general-tax` - 세무컨설팅
   - `/orders/create/international` - 해외특송
   - `/orders/create/linked-bus` - 연계버스
   - `/orders/create/linked-ktx` - 연계KTX
   - `/orders/create/linked-airport` - 연계공항
   - `/orders/create/linked-shipping` - 연계해운
   - `/orders/create/postal` - 우편
   - `/orders/create/mailroom` - 메일룸

5. **주문 관리**
   - `/orders` - 주문 목록
   - `/orders/{id}` - 주문 상세
   - `/orders/{id}/edit` - 주문 수정
   - `/orders/{id}/cancel` - 주문 취소
   - `/orders/{id}/status` - 주문 상태 변경

### **🔹 나도사장 관련 페이지**
6. **나도사장 신청**
   - `/boss/register` - 나도사장 신청
   - `/boss/register/success` - 신청 완료
   - `/boss/register/status` - 신청 상태 조회

7. **나도사장 관리**
   - `/boss/dashboard` - 나도사장 대시보드
   - `/boss/downlines` - 하위 라인 관리
   - `/boss/downlines/create` - 하위 나도사장 등록
   - `/boss/contractors` - 계약업체 관리
   - `/boss/contractors/create` - 계약업체 등록
   - `/boss/commissions` - 수수료 관리
   - `/boss/performance` - 실적 관리

### **🔹 고객사 관리 페이지**
8. **고객사 관리**
   - `/customer/dashboard` - 고객사 대시보드
   - `/customer/users` - 사용자 관리
   - `/customer/users/create` - 사용자 등록
   - `/customer/departments` - 부서 관리
   - `/customer/departments/create` - 부서 등록
   - `/customer/departments/{id}/edit` - 부서 수정
   - `/customer/services` - 서비스 권한 관리
   - `/customer/performance` - 실적 관리
   - `/customer/settlements` - 정산 관리

9. **청구 관리**
   - `/billing` - 청구 현황
   - `/billing/department` - 부서별 청구
   - `/billing/department-group` - 부서묶음 청구
   - `/billing/customer-group` - 고객묶음 청구
   - `/billing/create` - 청구서 생성
   - `/billing/{id}` - 청구서 상세
   - `/billing/{id}/edit` - 청구서 수정
   - `/billing/{id}/send` - 청구서 발송

### **🔹 시스템 관리 페이지**
10. **사용자 관리**
   - `/admin/users` - 전체 사용자 목록
   - `/admin/users/{id}` - 사용자 상세
   - `/admin/users/{id}/edit` - 사용자 수정
   - `/admin/users/{id}/permissions` - 권한 관리

11. **고객사 관리**
    - `/admin/customers` - 고객사 목록
    - `/admin/customers/{id}` - 고객사 상세
    - `/admin/customers/{id}/edit` - 고객사 수정
    - `/admin/customers/{id}/hierarchy` - 계층 관리
    - `/admin/departments` - 부서 관리
    - `/admin/departments/{id}` - 부서 상세

12. **청구 관리**
    - `/admin/billing` - 전체 청구 현황
    - `/admin/billing/settings` - 청구 설정 관리
    - `/admin/billing/templates` - 청구서 템플릿 관리
    - `/admin/billing/dispatch` - 청구 발송 관리
    - `/admin/billing/statistics` - 청구 통계 및 분석

13. **서비스 관리**
    - `/admin/services` - 서비스 타입 관리
    - `/admin/services/create` - 서비스 등록
    - `/admin/services/{id}/edit` - 서비스 수정
    - `/admin/services/permissions` - 서비스 권한 관리

14. **나도사장 관리**
    - `/admin/boss/registrations` - 나도사장 신청 관리
    - `/admin/boss/registrations/{id}` - 신청 상세
    - `/admin/boss/registrations/{id}/evaluate` - 심사 평가
    - `/admin/boss/accounts` - 나도사장 계정 관리
    - `/admin/boss/hierarchy` - 계층 관리
    - `/admin/boss/performance` - 실적 관리

15. **입점 관리**
    - `/admin/store/registrations` - 입점 신청 관리
    - `/admin/store/registrations/{id}` - 신청 상세
    - `/admin/store/registrations/{id}/evaluate` - 심사 평가
    - `/admin/store/contracts` - 계약 관리
    - `/admin/store/performance` - 실적 관리
    - `/admin/store/settlements` - 정산 관리

16. **API 연동 관리**
    - `/admin/api/integrations` - API 연동 설정
    - `/admin/api/integrations/create` - API 등록
    - `/admin/api/integrations/{id}/edit` - API 수정
    - `/admin/api/logs` - API 로그
    - `/admin/api/monitoring` - API 상태 모니터링

17. **알림 관리**
    - `/admin/notifications/templates` - 알림 템플릿 관리
    - `/admin/notifications/templates/create` - 템플릿 등록
    - `/admin/notifications/channels` - 알림 채널 관리
    - `/admin/notifications/queue` - 알림 큐 관리
    - `/admin/notifications/statistics` - 알림 통계

18. **시스템 설정**
    - `/admin/settings/general` - 기본 설정
    - `/admin/settings/security` - 보안 설정
    - `/admin/settings/backup` - 백업/복원

19. **통계 및 리포트**
    - `/admin/reports/orders` - 주문 통계
    - `/admin/reports/commissions` - 수수료 통계
    - `/admin/reports/performance` - 실적 통계
    - `/admin/reports/billing` - 청구 통계
    - `/admin/reports/system` - 시스템 통계

---

## 🎯 페이지 구현 우선순위

### **Phase 1: 기본 기능 (1-2주)**
- 로그인/로그아웃
- 기본 대시보드
- 주문 접수 (5-10개 서비스)
- 주문 조회/관리

### **Phase 2: 사용자 관리 (2-3주)**
- 사용자 프로필 관리
- 고객사 관리
- 서비스 권한 관리

### **Phase 3: 나도사장 시스템 (3-4주)**
- 나도사장 신청/승인
- 나도사장 관리
- 수수료 시스템

### **Phase 4: 입점 관리 (2-3주)**
- 입점 신청/심사
- 계약 관리
- 실적/정산 관리

### **Phase 5: 고급 기능 (2-3주)**
- API 연동
- 알림 시스템
- 통계/리포트

---

## 📱 반응형 디자인 고려사항

### **데스크톱 (1200px+)**
- 사이드바 네비게이션
- 다중 컬럼 레이아웃
- 상세한 데이터 테이블

### **태블릿 (768px - 1199px)**
- 접이식 사이드바
- 2컬럼 레이아웃
- 터치 친화적 버튼

### **모바일 (767px 이하)**
- 하단 탭 네비게이션
- 단일 컬럼 레이아웃
- 스와이프 제스처

---

## 🔐 권한 기반 메뉴 시스템

### **메뉴 표시 로직**
```php
// 사용자 타입별 메뉴 필터링
public function getMenuItems($userType, $userRole)
{
    $allMenus = $this->getAllMenus();
    $filteredMenus = [];
    
    foreach ($allMenus as $menu) {
        if ($this->hasPermission($userType, $userRole, $menu['permission'])) {
            $filteredMenus[] = $menu;
        }
    }
    
    return $filteredMenus;
}
```

### **동적 메뉴 생성**
```php
// 서비스별 주문 접수 메뉴 동적 생성
public function getOrderMenus($customerId)
{
    $allowedServices = $this->getAllowedServices($customerId);
    $menus = [];
    
    foreach ($allowedServices as $service) {
        $menus[] = [
            'title' => $service['service_name'],
            'url' => "/orders/create/{$service['service_code']}",
            'icon' => $service['icon'],
            'category' => $service['service_category']
        ];
    }
    
    return $menus;
}
```

이 정도면 체계적인 메뉴 구조와 페이지 구성이 가능할 것 같습니다. 단계적으로 구현하면 관리하기 쉬울 것 같아요!
