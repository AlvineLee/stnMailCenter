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
}

