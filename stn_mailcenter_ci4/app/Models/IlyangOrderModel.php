<?php

namespace App\Models;

use CodeIgniter\Model;

class IlyangOrderModel extends Model
{
    protected $table = 'tbl_orders_ilyang';
    protected $primaryKey = 'id';
    protected $useAutoIncrement = true;
    protected $returnType = 'array';
    protected $useSoftDeletes = false;
    protected $protectFields = true;
    
    protected $allowedFields = [
        'order_id',
        'ily_seq_no',
        'ily_shp_date',
        'ily_rec_type',
        'ily_awb_no',
        'ily_cus_acno',
        'ily_cus_ordno',
        'ily_god_name',
        'ily_god_price',
        'ily_dlv_rmks',
        'ily_snd_name',
        'ily_snd_man_name',
        'ily_snd_tel1',
        'ily_snd_tel2',
        'ily_snd_zip',
        'ily_snd_addr',
        'ily_snd_center',
        'ily_rcv_name',
        'ily_rcv_man_name',
        'ily_rcv_tel1',
        'ily_rcv_tel2',
        'ily_rcv_zip',
        'ily_rcv_addr',
        'ily_rcv_center',
        'ily_dlv_mesg',
        'ily_pay_type',
        'ily_box_qty',
        'ily_box_wgt',
        'ily_amt_cash',
        'ily_org_awbno',
        'ily_cus_apild',
        'ily_cus_api_id'
    ];
    
    protected $useTimestamps = true;
    protected $dateFormat = 'datetime';
    protected $createdField = 'created_at';
    protected $updatedField = 'updated_at';
    
    protected $validationRules = [];
    protected $validationMessages = [];
    protected $skipValidation = false;
    protected $cleanValidationRules = true;
    
    protected $beforeInsert = [];
    protected $afterInsert = [];
    protected $beforeUpdate = [];
    protected $afterUpdate = [];
    protected $beforeFind = [];
    protected $afterFind = [];
    protected $beforeDelete = [];
    protected $afterDelete = [];
    
    /**
     * 일양 접수 데이터 저장 또는 업데이트
     * 
     * @param int $orderId tbl_orders.id
     * @param array $waybillData 일양 API 형식 데이터 (convertOrderToWaybillData 결과)
     * @return bool|int 저장/업데이트 결과
     */
    public function saveIlyangOrderData($orderId, $waybillData)
    {
        // 기존 데이터 확인
        $existing = $this->where('order_id', $orderId)->first();
        
        // 일양 API 데이터를 테이블 필드로 매핑
        $data = [
            'order_id' => $orderId,
            'ily_seq_no' => $waybillData['ilySeqNo'] ?? null,
            'ily_shp_date' => $waybillData['ilyShpDate'] ?? null,
            'ily_rec_type' => $waybillData['ilyRecType'] ?? null,
            'ily_awb_no' => $waybillData['ilyAwbNo'] ?? null,
            'ily_cus_acno' => $waybillData['ilyCusAcno'] ?? null,
            'ily_cus_ordno' => $waybillData['ilyCusOrdno'] ?? null,
            'ily_god_name' => $waybillData['ilyGodName'] ?? null,
            'ily_god_price' => $waybillData['ilyGodPrice'] ?? null,
            'ily_dlv_rmks' => $waybillData['ilyDlvRmks'] ?? null,
            'ily_snd_name' => $waybillData['ilySndName'] ?? null,
            'ily_snd_man_name' => $waybillData['ilySndManName'] ?? null,
            'ily_snd_tel1' => $waybillData['ilySndTel1'] ?? null,
            'ily_snd_tel2' => $waybillData['ilySndTel2'] ?? null,
            'ily_snd_zip' => $waybillData['ilySndZip'] ?? null,
            'ily_snd_addr' => $waybillData['ilySndAddr'] ?? null,
            'ily_snd_center' => $waybillData['ilySndCenter'] ?? null,
            'ily_rcv_name' => $waybillData['ilyRcvName'] ?? null,
            'ily_rcv_man_name' => $waybillData['ilyRcvManName'] ?? null,
            'ily_rcv_tel1' => $waybillData['ilyRcvTel1'] ?? null,
            'ily_rcv_tel2' => $waybillData['ilyRcvTel2'] ?? null,
            'ily_rcv_zip' => $waybillData['ilyRcvZip'] ?? null,
            'ily_rcv_addr' => $waybillData['ilyRcvAddr'] ?? null,
            'ily_rcv_center' => $waybillData['ilyRcvCenter'] ?? null,
            'ily_dlv_mesg' => $waybillData['ilyDlvMesg'] ?? null,
            'ily_pay_type' => $waybillData['ilyPayType'] ?? null,
            'ily_box_qty' => $waybillData['ilyBoxQty'] ?? null,
            'ily_box_wgt' => $waybillData['ilyBoxWgt'] ?? null,
            'ily_amt_cash' => $waybillData['ilyAmtCash'] ?? null,
            'ily_org_awbno' => $waybillData['ilyOrgAwbno'] ?? null,
            'ily_cus_apild' => $waybillData['ilyCusApild'] ?? null,
            'ily_cus_api_id' => $waybillData['ilyCusApiId'] ?? null
        ];
        
        if ($existing) {
            // 업데이트
            return $this->update($existing['id'], $data);
        } else {
            // 신규 저장
            return $this->insert($data);
        }
    }
    
    /**
     * 주문 ID로 일양 접수 데이터 조회
     * 
     * @param int $orderId tbl_orders.id
     * @return array|null 일양 접수 데이터
     */
    public function getByOrderId($orderId)
    {
        return $this->where('order_id', $orderId)->first();
    }
    
    /**
     * 운송장번호로 일양 접수 데이터 조회
     * 
     * @param string $awbNo 일양 운송장번호
     * @return array|null 일양 접수 데이터
     */
    public function getByAwbNo($awbNo)
    {
        return $this->where('ily_awb_no', $awbNo)->first();
    }
}
