# STN MailCenter 스타일 가이드

## 전체 디자인 철학
- **파스텔 계열 색상**: 부드럽고 세련된 느낌
- **컴팩트한 디자인**: 공간 효율성 중시
- **일관성**: 모든 컴포넌트가 통일된 스타일
- **공통 CSS 관리**: 중앙 집중식 스타일 관리, 절대로 개별페이지에서 커스텀css 작성하지 말도록!

## 색상 팔레트
```css
/* 배경색 */
#f8fafc - 연한 파스텔 그레이 (기본 배경)
#f1f5f9 - 조금 더 진한 파스텔 그레이 (호버)
#e2e8f0 - 진한 파스텔 그레이 (활성 상태)

/* 텍스트 색상 */
#64748b - 중간 톤 그레이 (기본 텍스트)
#475569 - 진한 그레이 (호버 텍스트)
#334155 - 더 진한 그레이 (활성 텍스트)

/* 테두리 */
#e2e8f0 - 연한 파스텔 테두리
#cbd5e1 - 호버 테두리
#94a3b8 - 활성 테두리
```

## 폰트
- **기본 폰트**: 'Roboto', -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif
- **크기**: 12px (기본), 11px (작은 요소)
- **두께**: 500 (기본), 600 (강조)

## 버튼 스타일

### 1. 일반 버튼
```css
button {
    padding: 4px 12px !important;
    font-size: 12px !important;
    height: 24px !important;
    border-radius: 6px !important;
    font-weight: 600 !important;
    background: #f1f5f9 !important;
    color: #475569 !important;
    border: 1px solid #e2e8f0 !important;
}
```

### 2. 액션 버튼 (테이블 내)
```css
.action-buttons button {
    padding: 2px 6px !important;
    font-size: 11px !important;
    height: 20px !important;
    min-width: 40px !important;
    display: inline-block !important;
}
```

### 3. 페이징 숫자 버튼
```css
.pagination .page-number {
    width: 22px !important;
    height: 22px !important;
    border-radius: 50% !important;
    font-size: 11px !important;
    margin: 0 2px !important;
    display: inline-flex !important;
    align-items: center !important;
    justify-content: center !important;
}
```

### 4. 페이징 네비게이션 버튼
```css
.pagination .nav-button {
    padding: 4px 12px !important;
    font-size: 12px !important;
    height: 22px !important;
    border-radius: 6px !important;
    min-width: 50px !important;
}
```

## 테이블 스타일
```css
table {
    background: #fafafa;
    border: 1px solid #d1d5db;
    border-radius: 4px;
    font-size: 12px;
}

th {
    background: #f3f4f6;
    text-align: center;
    font-size: 11px;
    height: 20px;
    padding: 3px 8px;
}

td {
    text-align: left;
    font-size: 12px;
    height: 18px;
    padding: 2px 8px;
}

tbody tr {
    background: #fafafa;
}

tbody tr:nth-child(even) {
    background: #f5f5f5;
}

tbody tr:hover {
    background: #f9fafb;
}
```

## 레이아웃 클래스

### 1. 리스트 페이지 컨테이너
```css
.list-page-container {
    display: flex !important;
    flex-direction: column !important;
    flex: 1 !important;
}
```

### 2. 테이블 컨테이너
```css
.list-table-container {
    overflow: auto !important;
    max-height: calc(100vh - 300px) !important;
}
```

### 3. 페이징 영역
```css
.list-pagination {
    margin-top: 16px !important;
    flex-shrink: 0 !important;
}
```

### 4. 검색 영역
```css
.search-compact {
    padding: 8px 12px !important;
    margin-bottom: 12px !important;
}
```

## 검색 버튼
```css
.search-button {
    background: #f1f5f9 !important;
    color: #475569 !important;
    border: 1px solid #e2e8f0 !important;
    padding: 4px 12px;
    border-radius: 6px;
    font-size: 12px;
    height: 24px;
    min-width: 50px;
}

.search-button:hover {
    background: #e2e8f0 !important;
    color: #334155 !important;
}
```

## 상태 배지
```css
.status-badge {
    padding: 2px 6px;
    border-radius: 2px;
    font-size: 10px;
    font-weight: 600;
    text-transform: uppercase;
}
```

## 사용 가이드라인

### 1. 새로운 리스트 페이지 만들 때
- `.list-page-container` 클래스 사용
- `.list-table-container`로 테이블 감싸기
- `.list-pagination`으로 페이징 영역 감싸기
- `.search-compact`로 검색 영역 감싸기

### 2. 페이징 구현할 때
- 숫자 버튼: `.page-number` 클래스
- 네비게이션 버튼: `.nav-button` 클래스
- 현재 페이지: `.active` 클래스 추가

### 3. 버튼 사용할 때
- 일반 버튼: 기본 `button` 태그
- 테이블 액션: `.action-buttons` 클래스로 감싸기
- 검색 버튼: `.search-button` 클래스

### 4. 색상 사용할 때
- 항상 파스텔 계열 색상 사용
- 진한 색상은 최소한으로 사용
- 호버 상태는 조금 더 진한 톤으로

## 주의사항
- 모든 스타일은 `!important`로 강제 적용
- 공통 CSS에서 중앙 관리
- 개별 페이지에서 스타일 오버라이드 금지
- 새로운 컴포넌트는 이 가이드에 따라 구현


## 폼 스타일

### 1. 폼 컨테이너
```css
.form-container {
    background: transparent !important;
    border: none !important;
    border-radius: 0 !important;
    padding: 0 !important;
    box-shadow: none !important;
    max-width: 600px !important;
    margin: 0 !important;
}
```

### 2. 폼 그리드 (2컬럼)
```css
.form-grid {
    display: grid !important;
    grid-template-columns: 1fr 1fr !important;
    gap: 24px !important;
    margin-bottom: 16px !important;
}
```

### 3. 폼 필드
```css
.form-field {
    margin-bottom: 16px !important;
}

.form-label {
    display: block !important;
    font-size: 12px !important;
    font-weight: 600 !important;
    color: #374151 !important;
    margin-bottom: 4px !important;
}

.form-input, .form-select {
    width: 100% !important;
    padding: 6px 8px !important;
    font-size: 12px !important;
    border: 1px solid #d1d5db !important;
    border-radius: 4px !important;
    background: white !important;
    transition: all 0.2s ease !important;
    box-sizing: border-box !important;
    color: #374151 !important;
}

.form-input:focus, .form-select:focus {
    outline: none !important;
    border-color: #3b82f6 !important;
    box-shadow: 0 0 0 2px rgba(59, 130, 246, 0.1) !important;
}

.form-textarea {
    width: 100% !important;
    padding: 6px 8px !important;
    font-size: 12px !important;
    border: 1px solid #d1d5db !important;
    border-radius: 4px !important;
    background: white !important;
    min-height: 60px !important;
    resize: vertical !important;
    transition: all 0.2s ease !important;
    box-sizing: border-box !important;
    font-family: inherit !important;
    color: #374151 !important;
}
```

### 4. 폼 섹션
```css
.form-section {
    background: white !important;
    border: 1px solid #e5e7eb !important;
    border-radius: 8px !important;
    padding: 20px !important;
    margin-bottom: 0 !important;
    position: relative !important;
    box-shadow: 0 1px 3px 0 rgba(0, 0, 0, 0.1), 0 1px 2px 0 rgba(0, 0, 0, 0.06) !important;
    height: fit-content !important;
}

.form-section-title {
    font-size: 16px !important;
    font-weight: 600 !important;
    color: #374151 !important;
    margin-bottom: 16px !important;
    padding-bottom: 8px !important;
    border-bottom: 1px solid #e5e7eb !important;
    letter-spacing: -0.025em !important;
}
```

### 5. 폼 버튼
```css
.form-actions {
    display: flex !important;
    justify-content: center !important;
    gap: 8px !important;
    margin-top: 16px !important;
    padding-top: 0 !important;
    border-top: none !important;
}

.form-button {
    padding: 4px 12px !important;
    font-size: 12px !important;
    height: 24px !important;
    border-radius: 6px !important;
    font-weight: 600 !important;
    cursor: pointer !important;
    border: 1px solid #e2e8f0 !important;
    transition: all 0.2s ease !important;
    min-width: 60px !important;
}

.form-button-primary {
    background: #f1f5f9 !important;
    color: #475569 !important;
    border: 1px solid #e2e8f0 !important;
}

.form-button-primary:hover {
    background: #e2e8f0 !important;
    color: #334155 !important;
}

.form-button-secondary {
    background: #f1f5f9 !important;
    color: #475569 !important;
    border: 1px solid #e2e8f0 !important;
}

.form-button-secondary:hover {
    background: #e2e8f0 !important;
    color: #334155 !important;
}
```

### 6. 입력 필드 그룹
```css
.input-group {
    display: flex !important;
    align-items: center !important;
    gap: 12px !important;
}

.input-group .form-input {
    flex: 1 !important;
}

.input-group .input-suffix {
    font-size: 12px !important;
    color: #6b7280 !important;
    font-weight: 400 !important;
    background: transparent !important;
    padding: 0 !important;
    border: none !important;
    border-radius: 0 !important;
    margin-left: 8px !important;
}
```

### 7. 필수 필드 표시
```css
.form-label.required::after {
    content: ' *' !important;
    color: #ef4444 !important;
    font-weight: 700 !important;
}
```

## 드래그 가능한 테이블 헤더

### 1. 드래그 헤더
```css
.draggable-header {
    background: #f3f4f6 !important;
    text-align: center !important;
    font-size: 11px !important;
    height: 20px !important;
    padding: 3px 8px !important;
    cursor: move !important;
    user-select: none !important;
    position: relative !important;
}
```

### 2. 드래그 핸들
```css
.drag-handle {
    position: absolute !important;
    right: 2px !important;
    top: 50% !important;
    transform: translateY(-50%) !important;
    font-size: 10px !important;
    color: #94a3b8 !important;
}
```

## 리스트 페이지 컨테이너

### 1. 기본 컨테이너
```css
.list-page-container {
    background: white !important;
    border: 1px solid #e5e7eb !important;
    border-radius: 8px !important;
    padding: 16px !important;
    box-shadow: 0 1px 3px 0 rgba(0, 0, 0, 0.1), 0 1px 2px 0 rgba(0, 0, 0, 0.06) !important;
    display: flex !important;
    flex-direction: column !important;
    flex: 1 !important;
}
```

## 개발관련
- 화폐단위는 3자리마다 컴마 '원'으로 표현
- 폼 필드는 placeholder로 라벨 정보 제공 (예: "부서명 (예: 개발팀)")
- 모든 버튼은 회색 계열로 통일 (파란색 그라데이션 사용 금지)
- 테이블 행은 번갈아가는 배경색 적용 필수
- 인라인 스타일 사용 금지, CSS 클래스로 통일
