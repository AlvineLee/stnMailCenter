# 일양 API 명세서 정리

## 1. 운송장정보 전송 API

### 1.1 기본 정보
- **기능**: 운송장 출력 정보를 일양 서버에 전송하여 저장
- **메소드**: POST
- **지원 형식**: JSON
- **접근 제한**: IP 화이트리스트 등록 필요

### 1.2 엔드포인트
- **테스트**: `https://apis.ilyanglogis.com:8999/logisticsData.json`
- **운영**: `https://apis.ilyanglogis.com/logisticsData.json`

### 1.3 요청 헤더 (필수)
```
accessKey: STK705DBCF65E36DE41AB6E09616B96C222D7585DD1E22815C07971590C26DWN
accountNo: 105102001
ediCode: STNnw
filename: ediCode+YYYYMMDD_HH24MI (예: STNnw20241201_1430)
Content-Type: application/json
Charset: UTF-8
```

### 1.4 요청 바디 구조
```json
{
  "dataList": [
    {
      // 운송장 데이터 (최대 100건)
    }
  ]
}
```

### 1.5 필수 필드 (운송장정보 전송 - ilyRecType: CC)

| 필드명 | 타입 | 설명 | 예시 |
|--------|------|------|------|
| ilySeqNo | String(20) | 고유 식별값 | "1234" |
| ilyShpDate | String(8) | 발송일자 (YYYYMMDD) | "20241201" |
| ilyRecType | String(2) | 데이터 구분 | "CC" (고객출력자료 송신) |
| ilyAwbNo | String(10) | 운송장번호 | "7777777000" |
| ilyCusAcno | String(10) | 고객번호 | "104777001" |
| ilySndName | String(60) | 발송인 회사명 | "일양로지스" |
| ilySndTel1 | String(15) | 발송인 전화번호 | "010-0000-0000" |
| ilySndZip | String(7) | 발송인 우편번호 | "10000" |
| ilySndAddr | String(120) | 발송인 주소 | "서울시 구로구 구로동" |
| ilyRcvName | String(60) | 수취인 회사명/이름 | "일양" |
| ilyRcvTel1 | String(15) | 수취인 전화번호 | "010-0000-0000" |
| ilyRcvZip | String(7) | 수취인 우편번호 | "20000" |
| ilyRcvAddr | String(120) | 수취인 주소 | "서울시 서초구 명달로 6" |
| ilyPayType | String(2) | 운임구분 | "11"(신용), "21"(선불), "22"(착불) |
| ilyBoxQty | String(3) | 출력수량 | "1" |
| ilyBoxWgt | String(7) | 무게(kg) | "20" |
| ilyCusApild | String(10) | 고객 API_ID | "STNnw" |

### 1.6 선택 필드

| 필드명 | 타입 | 설명 | 예시 |
|--------|------|------|------|
| ilyCusOrdno | String(30) | 고객 주문번호 | "A1000-10000" |
| ilyGodName | String(60) | 상품명 | "전자제품" |
| ilyGodPrice | String(9) | 상품가격 | "30000" |
| ilyDlvRmks | String(60) | 배송비고 | "파손주의" |
| ilySndManName | String(20) | 발송인 담당자명 | "홍길동" |
| ilySndTel2 | String(15) | 발송인 휴대폰 | "02-000-0000" |
| ilySndCenter | String(10) | 발송 센터코드 | "SELNS" |
| ilyRcvManName | String(20) | 수취인 담당자명 | "홍길동" |
| ilyRcvTel2 | String(15) | 수취인 휴대폰 | "02-0000-0000" |
| ilyRcvCenter | String(10) | 수취 센터코드 | "SELSS" |
| ilyDlvMesg | String(60) | 배송메모 | "빨리배송바람" |
| ilyAmtCash | String(9) | 배송운임 | "200000" (선불/착불일 때 필수) |
| ilyOrgAwbno | String(10) | 원본운송장번호 | "77777770011" |

### 1.7 유효성 체크 규칙
1. **최대 전송 건수**: 100건
2. **발송일자 형식**: YYYYMMDD
3. **운임구분별 필수 필드**:
   - 선불(21) 또는 착불(22) 선택 시 `ilyAmtCash` 필수

### 1.8 응답 구조
```json
{
  "head": {
    "returnCode": "R0",
    "returnDesc": "OK",
    "totalCount": 0,
    "successCount": 0
  },
  "body": {
    "logisticsResultData": [
      {
        "ilySeqNo": "",
        "ilyCusAcno": "1999999",
        "ilyCusOrdno": "A1000-10000",
        "ilyAwbNo": "123123",
        "ilyErrorType": "E0",
        "ilyErrorField": "ilySeqNo(E01)"
      }
    ]
  }
}
```

### 1.9 응답 필드 설명

| 필드명 | 타입 | 설명 |
|--------|------|------|
| returnCode | String(3) | 응답코드 |
| returnDesc | String(300) | 응답설명 |
| totalCount | Int | 전송 데이터 카운트 건수 |
| successCount | Int | 성공 카운트 건수 |
| ilySeqNo | String(20) | 오류건 식별번호 |
| ilyCusAcno | String(10) | 오류건 고객번호 |
| ilyAwbNo | String(10) | 오류건 운송장번호 |
| ilyCusOrdno | String(30) | 오류건 주문번호 |
| ilyErrorType | String(20) | 오류구분 |
| ilyErrorField | String(500) | 오류항목 |

## 2. 구현된 기능

### 2.1 IlyangApiService 클래스
- 일양 API 명세에 따른 요청/응답 처리
- 자동 데이터 변환 및 검증
- 에러 처리 및 로깅

### 2.2 IlyangApiSpec 클래스
- API 명세 상수 정의
- 유효성 검증 규칙
- 샘플 데이터 생성

### 2.3 Service 컨트롤러 연동
- 택배 서비스 주문 시 자동 API 호출
- 운송장번호 자동 저장
- 에러 처리 및 로깅

## 3. 사용 예시

```php
// 일양 API 서비스 초기화
$ilyangApi = new IlyangApiService(true); // 테스트 모드

// 배송 데이터 준비
$deliveryData = [
    'order_number' => 'ORD-20241201-1234',
    'departure_company_name' => '발송회사',
    'departure_contact' => '010-1234-5678',
    'departure_address' => '서울시 강남구 테헤란로 123',
    'destination_company_name' => '수취회사',
    'destination_contact' => '010-9876-5432',
    'destination_address' => '서울시 서초구 서초대로 456',
    'item_type' => '서류',
    'weight' => '1',
    'quantity' => '1',
    'payment_type' => 'prepaid'
];

// API 호출
$result = $ilyangApi->createDelivery($deliveryData);

if ($result['success']) {
    echo "배송 요청 성공: " . json_encode($result['data']);
} else {
    echo "배송 요청 실패: " . $result['error'];
}
```

## 4. 주의사항

1. **IP 화이트리스트**: API 접근을 위해 서버 IP를 일양에 등록해야 함
2. **테스트 모드**: 운영 전 반드시 테스트 환경에서 검증
3. **에러 처리**: API 실패 시에도 주문 처리는 정상 진행
4. **로깅**: 모든 API 호출과 응답을 로그로 기록
5. **데이터 검증**: 필수 필드 및 형식 검증 필수
