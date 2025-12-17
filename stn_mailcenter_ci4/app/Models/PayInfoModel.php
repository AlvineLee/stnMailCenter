<?php

namespace App\Models;

use CodeIgniter\Model;

class PayInfoModel extends Model
{
    protected $table = 'tbl_pay_info';
    protected $primaryKey = 'p_idx';
    protected $useAutoIncrement = true;
    protected $returnType = 'array';
    protected $useSoftDeletes = false;
    protected $protectFields = true;
    protected $allowedFields = [
        'p_comp_gbn',
        'p_start_km',
        'p_dest_km',
        'p_truck_base_price',
        'p_bike_calc_type',
        'p_bike_value',
        'p_damas_calc_type',
        'p_damas_value',
        'p_labo_calc_type',
        'p_labo_value',
        'p_truck_tonnages'
    ];

    protected $useTimestamps = false;
    protected $dateFormat = 'datetime';

    protected $validationRules = [];
    protected $validationMessages = [];
    protected $skipValidation = false;
    protected $cleanValidationRules = true;

    protected $beforeInsert = [];
    protected $afterInsert = [];
    protected $beforeUpdate = [];
    protected $afterUpdate = [];
    protected $beforeDelete = [];
    protected $afterDelete = [];

    /**
     * 회사 구분별 거리 구간 목록 조회
     * 
     * @param string $compGbn 회사 구분 코드 (기본값: 'K')
     * @return array
     */
    public function getPayInfoByCompGbn($compGbn = 'K')
    {
        return $this->where('p_comp_gbn', $compGbn)
                    ->orderBy('p_start_km', 'ASC')
                    ->findAll();
    }

    /**
     * 거리 구간별 요금 정보 조회
     * 
     * @param string $compGbn 회사 구분 코드
     * @param int $distance 거리 (km)
     * @return array|null
     */
    public function getPayInfoByDistance($compGbn, $distance)
    {
        return $this->where('p_comp_gbn', $compGbn)
                    ->where('p_start_km <=', $distance)
                    ->where('p_dest_km >=', $distance)
                    ->first();
    }

    /**
     * 거리 구간 저장 (일괄 처리)
     * 
     * @param string $compGbn 회사 구분 코드
     * @param array $segments 거리 구간 배열
     * @return bool
     */
    public function savePayInfoSegments($compGbn, $segments)
    {
        $db = \Config\Database::connect();
        $db->transStart();

        try {
            // 기존 데이터 삭제
            $this->where('p_comp_gbn', $compGbn)->delete();

            // 새 데이터 삽입
            foreach ($segments as $segment) {
                $data = [
                    'p_comp_gbn' => $compGbn,
                    'p_start_km' => $segment['start_km'],
                    'p_dest_km' => $segment['dest_km'],
                    'p_truck_base_price' => $segment['truck_base_price'] ?? 0,
                    'p_bike_calc_type' => $segment['bike_calc_type'] ?? 'fixed',
                    'p_bike_value' => $segment['bike_value'] ?? 0,
                    'p_damas_calc_type' => $segment['damas_calc_type'] ?? 'fixed',
                    'p_damas_value' => $segment['damas_value'] ?? 0,
                    'p_labo_calc_type' => $segment['labo_calc_type'] ?? 'fixed',
                    'p_labo_value' => $segment['labo_value'] ?? 0,
                    'p_truck_tonnages' => isset($segment['truck_tonnages']) ? json_encode($segment['truck_tonnages']) : null
                ];

                $this->insert($data);
            }

            $db->transComplete();

            if ($db->transStatus() === false) {
                return false;
            }

            return true;
        } catch (\Exception $e) {
            $db->transRollback();
            log_message('error', 'PayInfoModel::savePayInfoSegments - Error: ' . $e->getMessage());
            return false;
        }
    }
}


