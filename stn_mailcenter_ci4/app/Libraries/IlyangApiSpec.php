<?php

namespace App\Libraries;

/**
 * 일양 API 명세서 정리
 * 운송장정보 전송 관련 API 스펙
 */
class IlyangApiSpec
{
    /**
     * API 엔드포인트 정보
     */
    const ENDPOINTS = [
        'test' => 'https://apis.ilyanglogis.com:8999',
        'prod' => 'https://apis.ilyanglogis.com'
    ];

    /**
     * 요청 헤더 정보
     */
    const HEADERS = [
        'accessKey' => 'STK705DBCF65E36DE41AB6E09616B96C222D7585DD1E22815C07971590C26DWN',
        'accountNo' => '105102001',
        'ediCode' => 'STNnw',
        'Content-Type' => 'application/json',
        'Charset' => 'UTF-8'
    ];

    /**
     * WhiteList IP 정보
     */
    const WHITELIST_IP = '1.217.87.178';

    /**
     * 요청 바디 필수 필드 (운송장정보 전송 - ilyRecType: CC)
     */
    const REQUIRED_FIELDS = [
        'ilySeqNo',           // 필수 - 고유 식별값 (String 20)
        'ilyShpDate',         // 필수 - 발송일자 YYYYMMDD (String 8)
        'ilyRecType',         // 필수 - 데이터 구분 (String 2) - CC: 고객출력자료 송신
        'ilyAwbNo',           // 필수 - 운송장번호 (String 10) - CC일 때 필수
        'ilyCusAcno',         // 필수 - 고객번호 (String 10)
        'ilySndName',         // 필수 - 발송인 회사명 (String 60)
        'ilySndTel1',         // 필수 - 발송인 전화번호 (String 15)
        'ilySndZip',          // 필수 - 발송인 우편번호 (String 7)
        'ilySndAddr',         // 필수 - 발송인 주소 (String 120)
        'ilyRcvName',         // 필수 - 수취인 회사명/이름 (String 60)
        'ilyRcvTel1',         // 필수 - 수취인 전화번호 (String 15)
        'ilyRcvZip',          // 필수 - 수취인 우편번호 (String 7)
        'ilyRcvAddr',         // 필수 - 수취인 주소 (String 120)
        'ilyPayType',         // 필수 - 운임구분 (String 2) - 11:신용, 21:선불, 22:착불
        'ilyBoxQty',          // 필수 - 출력수량 (String 3) - 기본값: 1
        'ilyBoxWgt',          // 필수 - 무게 kg (String 7)
        'ilyCusApild'         // 필수 - 고객 API_ID (String 10) - 계정이 하나면 ediCode 사용
    ];

    /**
     * 요청 바디 선택 필드
     */
    const OPTIONAL_FIELDS = [
        'ilyCusOrdno',        // 고객 주문번호 (String 30)
        'ilyGodName',         // 상품명 (String 60)
        'ilyGodPrice',        // 상품가격 (String 9)
        'ilyDlvRmks',         // 배송비고 (String 60)
        'ilySndManName',      // 발송인 담당자명 (String 20)
        'ilySndTel2',         // 발송인 휴대폰 (String 15)
        'ilySndCenter',       // 발송 센터코드 (String 10)
        'ilyRcvManName',      // 수취인 담당자명 (String 20)
        'ilyRcvTel2',         // 수취인 휴대폰 (String 15)
        'ilyRcvCenter',       // 수취 센터코드 (String 10)
        'ilyDlvMesg',         // 배송메모 (String 60)
        'ilyAmtCash',         // 배송운임 (String 9) - 선불(21) 또는 착불(22)일 때 필수
        'ilyOrgAwbno'         // 원본운송장번호 (String 10)
    ];

    /**
     * 유효성 체크 규칙
     */
    const VALIDATION_RULES = [
        'max_records' => 100,                    // 최대 100건까지 전송 가능
        'date_format' => 'YYYYMMDD',             // 발송일자 형식
        'required_for_prepaid' => ['ilyAmtCash'], // 선불(21) 또는 착불(22)일 때 배송운임 필수
        'pay_types' => [
            '11' => '신용',
            '21' => '선불',
            '22' => '착불'
        ],
        'rec_types' => [
            'CC' => '고객출력자료 송신',
            'OS' => '픽업요청'
        ]
    ];

    /**
     * 응답 메시지 구조
     */
    const RESPONSE_STRUCTURE = [
        'head' => [
            'returnCode' => 'String(3)',      // 응답코드
            'returnDesc' => 'String(300)',    // 응답설명
            'totalCount' => 'Int',            // 전송 데이터 카운트 건수
            'successCount' => 'Int'           // 성공 카운트 건수
        ],
        'body' => [
            'logisticsResultData' => [        // List
                'ilySeqNo' => 'String(20)',      // 오류건 식별번호
                'ilyCusAcno' => 'String(10)',    // 오류건 고객번호
                'ilyAwbNo' => 'String(10)',      // 오류건 운송장번호
                'ilyCusOrdno' => 'String(30)',   // 오류건 주문번호
                'ilyErrorType' => 'String(20)',  // 오류구분
                'ilyErrorField' => 'String(500)' // 오류항목
            ]
        ]
    ];

    /**
     * 요청 바디 예시 데이터 생성
     */
    public static function getSampleRequestBody()
    {
        return [
            'dataList' => [
                [
                    'ilySeqNo' => '1234',
                    'ilyShpDate' => '20220814',
                    'ilyRecType' => 'CC',
                    'ilyAwbNo' => '7777777000',
                    'ilyCusAcno' => '104777001',
                    'ilyCusOrdno' => 'A1000-10000',
                    'ilyGodName' => '전자제품',
                    'ilyGodPrice' => '30000',
                    'ilyDlvRmks' => '파손주의',
                    'ilySndName' => '일양로지스',
                    'ilySndManName' => '홍길동',
                    'ilySndTel1' => '010-0000-0000',
                    'ilySndTel2' => '02-000-0000',
                    'ilySndZip' => '10000',
                    'ilySndAddr' => '서울시 구로구 구로동',
                    'ilySndCenter' => 'SELNS',
                    'ilyRcvName' => '일양',
                    'ilyRcvManName' => '홍길동',
                    'ilyRcvTel1' => '010-0000-0000',
                    'ilyRcvTel2' => '02-0000-0000',
                    'ilyRcvZip' => '20000',
                    'ilyRcvAddr' => '서울시 서초구 명달로 6',
                    'ilyRcvCenter' => 'SELSS',
                    'ilyDlvMesg' => '빨리배송바람',
                    'ilyPayType' => '11',
                    'ilyBoxQty' => '1',
                    'ilyBoxWgt' => '20',
                    'ilyAmtCash' => '200000',
                    'ilyOrgAwbno' => '77777770011',
                    'ilyCusApild' => 'LFlogi'
                ]
            ]
        ];
    }

    /**
     * 응답 메시지 예시
     */
    public static function getSampleResponse()
    {
        return [
            'head' => [
                'returnCode' => 'R0',
                'returnDesc' => 'OK',
                'totalCount' => 0,
                'successCount' => 0
            ],
            'body' => [
                'logisticsResultData' => [
                    [
                        'ilySeqNo' => '',
                        'ilyCusAcno' => '1999999',
                        'ilyCusOrdno' => 'A1000-10000',
                        'ilyAwbNo' => '123123',
                        'ilyErrorType' => 'E0',
                        'ilyErrorField' => 'ilySeqNo(E01)'
                    ],
                    [
                        'ilySeqNo' => '',
                        'ilyCusAcno' => '2999999',
                        'ilyCusOrdno' => 'B1000-10000',
                        'ilyAwbNo' => '10300000',
                        'ilyErrorType' => 'E0',
                        'ilyErrorField' => 'ilySeqNo(E01)'
                    ]
                ]
            ]
        ];
    }

    /**
     * 필수 필드 검증
     */
    public static function validateRequiredFields($data)
    {
        $errors = [];
        
        foreach (self::REQUIRED_FIELDS as $field) {
            if (!isset($data[$field]) || empty($data[$field])) {
                $errors[] = "필수 필드 누락: {$field}";
            }
        }

        // 선불/착불일 때 배송운임 필수 체크
        if (in_array($data['ilyPayType'] ?? '', ['21', '22'])) {
            if (empty($data['ilyAmtCash'])) {
                $errors[] = "선불/착불 선택 시 배송운임(ilyAmtCash) 필수";
            }
        }

        return $errors;
    }

    /**
     * 데이터 형식 검증
     */
    public static function validateDataFormat($data)
    {
        $errors = [];

        // 발송일자 형식 체크 (YYYYMMDD)
        if (isset($data['ilyShpDate'])) {
            if (!preg_match('/^\d{8}$/', $data['ilyShpDate'])) {
                $errors[] = "발송일자 형식 오류: YYYYMMDD 형식이어야 함";
            }
        }

        // 운임구분 체크
        if (isset($data['ilyPayType'])) {
            if (!in_array($data['ilyPayType'], ['11', '21', '22'])) {
                $errors[] = "운임구분 오류: 11(신용), 21(선불), 22(착불) 중 하나여야 함";
            }
        }

        return $errors;
    }
}
