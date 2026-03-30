<?php
defined('BASEPATH') or exit('No direct script access allowed');

class Asset_model extends CI_Model
{
    private $supportsQrImagePathCache = null;
    private $supportsDepartmentIdCache = null;

    private function supportsQrImagePath()
    {
        if ($this->supportsQrImagePathCache === null) {
            $this->supportsQrImagePathCache = $this->db->field_exists('qr_image_path', 'assets');
        }

        return $this->supportsQrImagePathCache;
    }

    private function supportsDepartmentId()
    {
        if ($this->supportsDepartmentIdCache === null) {
            $this->supportsDepartmentIdCache = $this->db->field_exists('department_id', 'assets');
        }

        return $this->supportsDepartmentIdCache;
    }

    private function baseAssetSelect()
    {
        $select = [
            'a.asset_id',
            'a.asset_name',
            'a.asset_type',
            'a.serial_number',
            'a.qr_code',
            'a.location',
            'a.assigned_user_id',
            'a.status',
            'a.created_at',
            'u.name AS assigned_user_name',
            'u.email AS assigned_user_email',
            'u.department_id AS assigned_department_id',
            'ud.department_name AS assigned_department_name',
        ];

        if ($this->supportsDepartmentId()) {
            $select[] = 'a.department_id';
            $select[] = 'COALESCE(a.department_id, u.department_id) AS effective_department_id';
            $select[] = 'COALESCE(ad.department_name, ud.department_name) AS asset_department_name';
        } else {
            $select[] = 'NULL AS department_id';
            $select[] = 'u.department_id AS effective_department_id';
            $select[] = 'ud.department_name AS asset_department_name';
        }

        if ($this->supportsQrImagePath()) {
            $select[] = 'a.qr_image_path';
        }

        return implode(',', $select);
    }

    private function baseAssetQuery()
    {
        $this->db
            ->select($this->baseAssetSelect())
            ->from('assets a')
            ->join('users u', 'u.user_id = a.assigned_user_id', 'left')
            ->join('departments ud', 'ud.department_id = u.department_id', 'left');

        if ($this->supportsDepartmentId()) {
            $this->db->join('departments ad', 'ad.department_id = a.department_id', 'left');
        }

        return $this->db;
    }

    private function sanitizeWritePayload(array $data)
    {
        if (!$this->supportsQrImagePath()) {
            unset($data['qr_image_path']);
        }

        if (!$this->supportsDepartmentId()) {
            unset($data['department_id']);
        }

        return $data;
    }

    private function applyDepartmentFilter($query, $departmentId)
    {
        $departmentId = (int) $departmentId;

        if ($departmentId <= 0) {
            return $query;
        }

        if ($this->supportsDepartmentId()) {
            $query->group_start()
                ->where('a.department_id', $departmentId)
                ->or_group_start()
                    ->where('a.department_id IS NULL', null, false)
                    ->where('u.department_id', $departmentId)
                ->group_end()
            ->group_end();

            return $query;
        }

        $query->where('u.department_id', $departmentId);

        return $query;
    }

    public function get_by_qr_code($qr_code)
    {
        return $this->baseAssetQuery()
            ->where('a.qr_code', trim((string) $qr_code))
            ->get()
            ->row();
    }

    public function get_by_serial_number($serialNumber, $departmentId = null)
    {
        $query = $this->baseAssetQuery()
            ->where('a.serial_number', trim((string) $serialNumber));

        $this->applyDepartmentFilter($query, $departmentId);

        return $query
            ->order_by('a.asset_id', 'DESC')
            ->get()
            ->row();
    }

    public function get_by_id($asset_id)
    {
        return $this->baseAssetQuery()
            ->where('a.asset_id', (int) $asset_id)
            ->get()
            ->row();
    }

    public function get_all_assets()
    {
        return $this->baseAssetQuery()
            ->order_by('a.created_at', 'DESC')
            ->get()
            ->result_array();
    }

    public function get_print_assets(array $filters = [])
    {
        $query = $this->baseAssetQuery();
        $search = trim((string) ($filters['q'] ?? ''));
        $departmentId = isset($filters['department_id']) ? (int) $filters['department_id'] : 0;
        $assetType = trim((string) ($filters['asset_type'] ?? ''));
        $serialNumber = trim((string) ($filters['serial_number'] ?? ''));

        if ($search !== '') {
            $query->group_start();
            $query->like('a.qr_code', $search)
                ->or_like('a.asset_name', $search)
                ->or_like('a.serial_number', $search)
                ->or_like('a.asset_type', $search)
                ->or_like('a.location', $search)
                ->or_like('u.name', $search)
                ->or_like('ud.department_name', $search);

            if ($this->supportsDepartmentId()) {
                $query->or_like('ad.department_name', $search);
            }

            $query->group_end();
        }

        $this->applyDepartmentFilter($query, $departmentId);

        if ($assetType !== '') {
            $query->where('a.asset_type', $assetType);
        }

        if ($serialNumber !== '') {
            $query->like('a.serial_number', $serialNumber);
        }

        return $query
            ->order_by('a.asset_name', 'ASC')
            ->order_by('a.serial_number', 'ASC')
            ->get()
            ->result_array();
    }

    public function get_manage_assets(array $filters = [])
    {
        $query = $this->baseAssetQuery();
        $search = trim((string) ($filters['q'] ?? ''));
        $departmentId = isset($filters['department_id']) ? (int) $filters['department_id'] : 0;
        $assetType = trim((string) ($filters['asset_type'] ?? ''));
        $status = trim((string) ($filters['status'] ?? ''));
        $limit = (int) ($filters['limit'] ?? 150);

        if ($search !== '') {
            $query->group_start();
            $query->like('a.qr_code', $search)
                ->or_like('a.asset_name', $search)
                ->or_like('a.serial_number', $search)
                ->or_like('a.asset_type', $search)
                ->or_like('a.location', $search)
                ->or_like('u.name', $search)
                ->or_like('u.email', $search)
                ->or_like('ud.department_name', $search);

            if ($this->supportsDepartmentId()) {
                $query->or_like('ad.department_name', $search);
            }

            $query->group_end();
        }

        $this->applyDepartmentFilter($query, $departmentId);

        if ($assetType !== '') {
            $query->where('a.asset_type', $assetType);
        }

        if ($status !== '') {
            $query->where('a.status', $status);
        }

        $limit = $limit > 0 ? min($limit, 300) : 150;

        return $query
            ->order_by('a.asset_id', 'DESC')
            ->limit($limit)
            ->get()
            ->result_array();
    }

    public function get_print_department_options()
    {
        if ($this->supportsDepartmentId()) {
            return $this->db
                ->distinct()
                ->select('COALESCE(a.department_id, u.department_id) AS department_id, COALESCE(ad.department_name, ud.department_name) AS department_name', false)
                ->from('assets a')
                ->join('users u', 'u.user_id = a.assigned_user_id', 'left')
                ->join('departments ad', 'ad.department_id = a.department_id', 'left')
                ->join('departments ud', 'ud.department_id = u.department_id', 'left')
                ->where('COALESCE(a.department_id, u.department_id) IS NOT NULL', null, false)
                ->order_by('department_name', 'ASC')
                ->get()
                ->result_array();
        }

        return $this->db
            ->distinct()
            ->select('ud.department_id, ud.department_name')
            ->from('assets a')
            ->join('users u', 'u.user_id = a.assigned_user_id', 'left')
            ->join('departments ud', 'ud.department_id = u.department_id', 'left')
            ->where('ud.department_id IS NOT NULL', null, false)
            ->order_by('ud.department_name', 'ASC')
            ->get()
            ->result_array();
    }

    public function get_print_asset_type_options()
    {
        $rows = $this->db
            ->distinct()
            ->select('asset_type')
            ->from('assets')
            ->where('asset_type IS NOT NULL', null, false)
            ->where('asset_type !=', '')
            ->order_by('asset_type', 'ASC')
            ->get()
            ->result_array();

        return array_values(array_filter(array_map(function ($row) {
            return trim((string) ($row['asset_type'] ?? ''));
        }, $rows)));
    }

    public function qr_code_exists($qr_code, $excludeAssetId = null)
    {
        $this->db
            ->from('assets')
            ->where('qr_code', trim((string) $qr_code));

        if ($excludeAssetId !== null) {
            $this->db->where('asset_id !=', (int) $excludeAssetId);
        }

        return (bool) $this->db->count_all_results();
    }

    public function serial_number_exists($serialNumber, $excludeAssetId = null)
    {
        $this->db
            ->from('assets')
            ->where('serial_number', trim((string) $serialNumber));

        if ($excludeAssetId !== null) {
            $this->db->where('asset_id !=', (int) $excludeAssetId);
        }

        return (bool) $this->db->count_all_results();
    }

    public function get_latest_serial_number($departmentId = null)
    {
        $query = $this->db
            ->select('a.serial_number')
            ->from('assets a')
            ->join('users u', 'u.user_id = a.assigned_user_id', 'left')
            ->where('a.serial_number IS NOT NULL', null, false)
            ->where('a.serial_number !=', '');

        $this->applyDepartmentFilter($query, $departmentId);

        $row = $query
            ->order_by('a.asset_id', 'DESC')
            ->limit(1)
            ->get()
            ->row();

        return trim((string) ($row->serial_number ?? ''));
    }

    public function insert_asset(array $data)
    {
        $data = $this->sanitizeWritePayload($data);

        if (!$this->db->insert('assets', $data)) {
            return false;
        }

        return (int) $this->db->insert_id();
    }

    public function update_asset($assetId, array $data)
    {
        $data = $this->sanitizeWritePayload($data);

        return (bool) $this->db
            ->where('asset_id', (int) $assetId)
            ->update('assets', $data);
    }

    public function count_tickets_using_asset($assetId)
    {
        return (int) $this->db
            ->where('asset_id', (int) $assetId)
            ->where('deleted_at IS NULL', null, false)
            ->count_all_results('tickets');
    }

    public function delete_asset($assetId)
    {
        return (bool) $this->db
            ->where('asset_id', (int) $assetId)
            ->delete('assets');
    }
}
