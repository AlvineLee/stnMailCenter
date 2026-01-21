<?php

namespace App\Models;

use CodeIgniter\Model;

/**
 * 시스템 설정 모델
 * 전역 시스템 설정 관리
 */
class SystemSettingModel extends Model
{
    protected $table = 'tbl_system_settings';
    protected $primaryKey = 'idx';
    protected $useAutoIncrement = true;
    protected $returnType = 'array';
    protected $useSoftDeletes = false;
    protected $protectFields = true;
    protected $allowedFields = [
        'setting_key',
        'setting_value',
        'setting_type',
        'description',
        'updated_by',
        'updated_at'
    ];

    protected $useTimestamps = false;
    protected $dateFormat = 'datetime';

    // 기본 설정값
    protected $defaults = [
        'login_max_attempts' => 5,           // 최대 로그인 시도 횟수
        'login_lockout_minutes' => 5,        // 잠금 시간 (분)
        'login_attempt_window' => 30,        // 시도 횟수 체크 시간 범위 (분)
    ];

    /**
     * 설정값 조회
     *
     * @param string $key 설정 키
     * @param mixed $default 기본값
     * @return mixed
     */
    public function getSetting($key, $default = null)
    {
        $result = $this->where('setting_key', $key)->first();

        if ($result) {
            return $this->castValue($result['setting_value'], $result['setting_type']);
        }

        // 기본값 반환
        return $default ?? ($this->defaults[$key] ?? null);
    }

    /**
     * 설정값 저장 또는 업데이트
     *
     * @param string $key 설정 키
     * @param mixed $value 설정 값
     * @param string $type 값 타입 (string, int, float, bool, json)
     * @param string|null $description 설명
     * @param int|null $updatedBy 수정한 사용자 ID
     * @return bool
     */
    public function setSetting($key, $value, $type = 'string', $description = null, $updatedBy = null)
    {
        $existing = $this->where('setting_key', $key)->first();

        $data = [
            'setting_key' => $key,
            'setting_value' => $this->serializeValue($value, $type),
            'setting_type' => $type,
            'updated_at' => date('Y-m-d H:i:s')
        ];

        if ($description !== null) {
            $data['description'] = $description;
        }

        if ($updatedBy !== null) {
            $data['updated_by'] = $updatedBy;
        }

        if ($existing) {
            return $this->update($existing['idx'], $data);
        } else {
            return $this->insert($data) !== false;
        }
    }

    /**
     * 여러 설정값 일괄 조회
     *
     * @param array $keys 설정 키 배열
     * @return array
     */
    public function getSettings($keys)
    {
        $results = $this->whereIn('setting_key', $keys)->findAll();

        $settings = [];
        foreach ($results as $result) {
            $settings[$result['setting_key']] = $this->castValue($result['setting_value'], $result['setting_type']);
        }

        // 기본값으로 채우기
        foreach ($keys as $key) {
            if (!isset($settings[$key])) {
                $settings[$key] = $this->defaults[$key] ?? null;
            }
        }

        return $settings;
    }

    /**
     * 로그인 관련 설정 일괄 조회
     *
     * @return array
     */
    public function getLoginSettings()
    {
        return $this->getSettings([
            'login_max_attempts',
            'login_lockout_minutes',
            'login_attempt_window'
        ]);
    }

    /**
     * 모든 설정 조회 (관리자용)
     *
     * @return array
     */
    public function getAllSettings()
    {
        return $this->orderBy('setting_key', 'ASC')->findAll();
    }

    /**
     * 값 타입에 따른 캐스팅
     *
     * @param string $value 문자열 값
     * @param string $type 타입
     * @return mixed
     */
    protected function castValue($value, $type)
    {
        switch ($type) {
            case 'int':
            case 'integer':
                return (int)$value;
            case 'float':
            case 'double':
                return (float)$value;
            case 'bool':
            case 'boolean':
                return filter_var($value, FILTER_VALIDATE_BOOLEAN);
            case 'json':
            case 'array':
                return json_decode($value, true);
            default:
                return $value;
        }
    }

    /**
     * 값 직렬화
     *
     * @param mixed $value 값
     * @param string $type 타입
     * @return string
     */
    protected function serializeValue($value, $type)
    {
        if ($type === 'json' || $type === 'array') {
            return json_encode($value, JSON_UNESCAPED_UNICODE);
        }
        if ($type === 'bool' || $type === 'boolean') {
            return $value ? '1' : '0';
        }
        return (string)$value;
    }

    /**
     * 기본 설정값 초기화 (테이블 생성 후 실행)
     *
     * @return void
     */
    public function initializeDefaults()
    {
        $descriptions = [
            'login_max_attempts' => '로그인 최대 시도 횟수 (이 횟수 초과 시 잠금)',
            'login_lockout_minutes' => '로그인 잠금 시간 (분)',
            'login_attempt_window' => '로그인 시도 횟수 체크 시간 범위 (분)'
        ];

        foreach ($this->defaults as $key => $value) {
            $existing = $this->where('setting_key', $key)->first();
            if (!$existing) {
                $type = is_int($value) ? 'int' : (is_bool($value) ? 'bool' : 'string');
                $this->setSetting($key, $value, $type, $descriptions[$key] ?? null);
            }
        }
    }
}