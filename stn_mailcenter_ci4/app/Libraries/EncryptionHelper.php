<?php

namespace App\Libraries;

/**
 * 개인정보 암호화/복호화 헬퍼 클래스
 * AES-256-CBC 방식 사용
 */
class EncryptionHelper
{
    private $encryptionKey;
    private $cipher = 'AES-256-CBC';
    
    public function __construct()
    {
        // 암호화/복호화 키 설정 (32바이트로 맞춤)
        // 두 키를 조합하여 하나의 키로 생성
        $encKey = 'enc_dptmxldps1!';
        $decKey = 'dec_dptmxldps1!';
        $combinedKey = $encKey . $decKey; // 두 키를 조합
        $this->encryptionKey = hash('sha256', $combinedKey, true); // 32바이트 키 생성
    }
    
    /**
     * 데이터 암호화
     * 
     * @param string $data 암호화할 데이터
     * @return string|false 암호화된 데이터 (base64 인코딩) 또는 false
     */
    public function encrypt($data)
    {
        if (empty($data)) {
            return $data;
        }
        
        try {
            // IV 생성 (16바이트)
            $iv = openssl_random_pseudo_bytes(16);
            
            // 암호화 (OPENSSL_RAW_DATA 옵션 사용하여 바이너리 데이터 반환)
            $encrypted = openssl_encrypt($data, $this->cipher, $this->encryptionKey, OPENSSL_RAW_DATA, $iv);
            
            if ($encrypted === false) {
                log_message('error', 'EncryptionHelper::encrypt - Encryption failed');
                return false;
            }
            
            // IV와 암호화된 데이터를 함께 base64 인코딩하여 반환
            // 형식: base64(iv + encrypted_data)
            $combined = $iv . $encrypted;
            return base64_encode($combined);
        } catch (\Exception $e) {
            log_message('error', 'EncryptionHelper::encrypt - Exception: ' . $e->getMessage());
            return false;
        }
    }
    
    /**
     * 데이터 복호화
     * 
     * @param string $encryptedData 암호화된 데이터 (base64 인코딩)
     * @return string|false 복호화된 데이터 또는 false
     */
    public function decrypt($encryptedData)
    {
        if (empty($encryptedData)) {
            return $encryptedData;
        }
        
        // 암호화된 데이터는 최소 30자 이상이어야 함 (IV 16바이트 + 암호화 데이터 최소 16바이트 = 32바이트 → base64 인코딩 시 약 43자)
        // 30자 미만이면 평문 데이터로 간주하고 복호화 시도하지 않음
        if (strlen($encryptedData) < 30) {
            return $encryptedData;
        }
        
        try {
            // base64 디코딩
            $combined = base64_decode($encryptedData, true);
            
            if ($combined === false) {
                // base64 디코딩 실패 시 원본 데이터 반환 (이미 복호화된 데이터일 수 있음)
                return $encryptedData;
            }
            
            $combinedLength = strlen($combined);
            
            // IV 추출 (첫 16바이트)
            if ($combinedLength < 16) {
                // 데이터가 너무 짧으면 이미 복호화된 데이터일 수 있음 (에러 로그 없이 반환)
                return $encryptedData;
            }
            
            $iv = substr($combined, 0, 16);
            $encryptedBinary = substr($combined, 16);
            
            // 암호화된 데이터가 비어있으면 원본 반환
            if (empty($encryptedBinary)) {
                log_message('error', 'EncryptionHelper::decrypt - Encrypted data is empty after IV extraction');
                return $encryptedData;
            }
            
            // 먼저 새로운 방식(RAW_DATA)으로 복호화 시도
            $decrypted = openssl_decrypt($encryptedBinary, $this->cipher, $this->encryptionKey, OPENSSL_RAW_DATA, $iv);
            
            // 새로운 방식 실패 시 이전 방식(base64 인코딩된 데이터)으로 시도
            if ($decrypted === false) {
                $encryptedBase64 = base64_encode($encryptedBinary);
                $decrypted = openssl_decrypt($encryptedBase64, $this->cipher, $this->encryptionKey, 0, $iv);
            }
            
            if ($decrypted === false) {
                // 복호화 실패 시 로그 출력 및 원본 데이터 반환
                $errors = [];
                while (($error = openssl_error_string()) !== false) {
                    $errors[] = $error;
                }
                log_message('error', 'EncryptionHelper::decrypt - Decryption failed. Encrypted data length: ' . strlen($encryptedData) . ', Combined length: ' . $combinedLength . ', Encrypted binary length: ' . strlen($encryptedBinary));
                if (!empty($errors)) {
                    log_message('error', 'EncryptionHelper::decrypt - OpenSSL errors: ' . implode('; ', $errors));
                }
                // 복호화 실패 시 원본 반환 (이미 복호화된 데이터일 수 있음)
                return $encryptedData;
            }
            
            return $decrypted;
        } catch (\Exception $e) {
            log_message('error', 'EncryptionHelper::decrypt - Exception: ' . $e->getMessage());
            return $encryptedData; // 복호화 실패 시 원본 반환
        }
    }
    
    /**
     * 배열의 특정 필드들을 암호화
     * 
     * @param array $data 데이터 배열
     * @param array $fields 암호화할 필드명 배열
     * @return array 암호화된 데이터 배열
     */
    public function encryptFields(array $data, array $fields)
    {
        foreach ($fields as $field) {
            if (isset($data[$field]) && $data[$field] !== null && $data[$field] !== '') {
                $encrypted = $this->encrypt($data[$field]);
                if ($encrypted !== false) {
                    $data[$field] = $encrypted;
                } else {
                    // 암호화 실패 시 로그 출력
                    log_message('error', 'EncryptionHelper::encryptFields - Failed to encrypt field: ' . $field . ', value: ' . substr($data[$field], 0, 20));
                }
            }
        }
        return $data;
    }
    
    /**
     * 배열의 특정 필드들을 복호화
     * 
     * @param array $data 데이터 배열
     * @param array $fields 복호화할 필드명 배열
     * @return array 복호화된 데이터 배열
     */
    public function decryptFields(array $data, array $fields)
    {
        foreach ($fields as $field) {
            if (isset($data[$field]) && !empty($data[$field])) {
                $data[$field] = $this->decrypt($data[$field]);
            }
        }
        return $data;
    }
    
    /**
     * 전화번호 마스킹 처리
     * 앞 3자리와 뒤 4자리를 제외한 가운데 숫자만 마스킹
     * 
     * @param string $phone 전화번호
     * @return string 마스킹된 전화번호 (앞 3자리 + 가운데 마스킹 + 뒤 4자리)
     */
    public function maskPhone($phone)
    {
        if (empty($phone) || $phone === '-') {
            return $phone;
        }
        
        // 전화번호에서 숫자만 추출
        $numbers = preg_replace('/\D/', '', $phone);
        $numberLength = strlen($numbers);
        
        if ($numberLength < 7) {
            // 7자리 미만이면 마스킹하지 않음
            return $phone;
        }
        
        // 앞 3자리
        $first3 = substr($numbers, 0, 3);
        // 뒤 4자리
        $last4 = substr($numbers, -4);
        // 가운데 부분 (마스킹 대상)
        $middle = substr($numbers, 3, $numberLength - 7);
        $middleMasked = str_repeat('*', strlen($middle));
        
        // 항상 하이픈 포함하여 마스킹 (010-XXXX-5678 형식)
        return $first3 . '-' . $middleMasked . '-' . $last4;
    }
    
    /**
     * 주소 마스킹 처리
     * 시/도, 시/군/구까지만 표시하고 나머지는 마스킹
     * 
     * @param string $address 주소
     * @return string 마스킹된 주소
     */
    public function maskAddress($address)
    {
        if (empty($address) || $address === '-') {
            return $address;
        }
        
        // 주소를 공백으로 분리
        $parts = preg_split('/\s+/', trim($address));
        
        if (count($parts) <= 2) {
            // 시/도, 시/군/구만 있거나 그 이하인 경우 전체 마스킹
            $length = mb_strlen($address, 'UTF-8');
            return mb_substr($address, 0, min(6, $length), 'UTF-8') . str_repeat('*', max(0, $length - 6));
        }
        
        // 시/도, 시/군/구까지만 표시 (최대 2개 단어)
        $visibleParts = array_slice($parts, 0, 2);
        $visibleText = implode(' ', $visibleParts);
        
        // 나머지 부분은 마스킹 (원본 주소 길이 유지)
        $totalLength = mb_strlen($address, 'UTF-8');
        $visibleLength = mb_strlen($visibleText, 'UTF-8');
        $maskLength = max(3, $totalLength - $visibleLength - 1); // 최소 3자리 마스킹
        
        return $visibleText . ' ' . str_repeat('*', $maskLength);
    }
    
    /**
     * 금액 마스킹 처리
     * 뒷자리 3개를 제외한 앞자리 전체를 마스킹
     * 
     * @param string $amount 금액 (예: "50,000원", "100000원")
     * @return string 마스킹된 금액
     */
    public function maskAmount($amount)
    {
        if (empty($amount) || $amount === '-') {
            return $amount;
        }
        
        // 숫자만 추출
        $numbers = preg_replace('/\D/', '', $amount);
        
        if (empty($numbers) || strlen($numbers) < 3) {
            // 숫자가 없거나 3자리 미만이면 전체 마스킹
            return str_repeat('*', mb_strlen($amount, 'UTF-8'));
        }
        
        // 뒷자리 3개만 표시
        $last3 = substr($numbers, -3);
        $prefixLength = strlen($numbers) - 3;
        
        // 원본에 "원"이 있으면 유지
        $hasWon = (strpos($amount, '원') !== false);
        
        // 원본에 쉼표가 있으면 쉼표 위치 유지
        $hasComma = (strpos($amount, ',') !== false);
        
        if ($hasComma) {
            // 쉼표가 있는 경우: ***,000원 형식
            $masked = str_repeat('*', $prefixLength) . ',' . $last3;
        } else {
            // 쉼표가 없는 경우: ***000원 형식
            $masked = str_repeat('*', $prefixLength) . $last3;
        }
        
        if ($hasWon) {
            return $masked . '원';
        } else {
            return $masked;
        }
    }
    
    /**
     * 배열의 특정 필드들을 마스킹 처리
     * 
     * @param array $data 데이터 배열
     * @param array $fields 마스킹할 필드명 배열
     * @return array 마스킹된 데이터 배열
     */
    public function maskFields(array $data, array $fields)
    {
        foreach ($fields as $field) {
            if (isset($data[$field]) && !empty($data[$field]) && $data[$field] !== '-') {
                $data[$field] = $this->maskPhone($data[$field]);
            }
        }
        return $data;
    }
}

