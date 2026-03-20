<?php
class Menu_model extends CI_Model
{
    private function fetchMenus($roleId, array $options = [])
    {
        $departmentId = isset($options['department_id'])
            ? (int) $options['department_id']
            : (int) $this->session->userdata('department_id');
        $scope = isset($options['scope']) ? (string) $options['scope'] : 'auto';
        $menuIds = array_values(array_filter(array_map('intval', (array) ($options['menu_ids'] ?? []))));
        $hasDepartmentColumn = $this->db->field_exists('department_id', 'role_sidebar_menus');
        $hasRoleItOnlyColumn = $this->db->field_exists('is_it_only', 'role_sidebar_menus');
        $hasMenuItOnlyColumn = $this->db->field_exists('is_it_only', 'sidebar_menus');

        $this->db
            ->select('sidebar_menus.*')
            ->from('role_sidebar_menus')
            ->join('sidebar_menus', 'sidebar_menus.id = role_sidebar_menus.menu_id')
            ->where('role_sidebar_menus.role_id', (int) $roleId)
            ->where('sidebar_menus.status', 'Active');

        if ($hasDepartmentColumn) {
            $this->db->where('role_sidebar_menus.department_id', $departmentId);
        }

        if (!empty($menuIds)) {
            $this->db->where_in('sidebar_menus.id', $menuIds);
        }

        if ($scope === 'auto') {
            $scope = $departmentId === 2 ? 'all' : 'non_it';
        }

        if ($scope === 'non_it') {
            if ($hasRoleItOnlyColumn) {
                $this->db->where('role_sidebar_menus.is_it_only', 0);
            } elseif ($hasMenuItOnlyColumn) {
                $this->db->where('sidebar_menus.is_it_only', 0);
            }
        } elseif ($scope === 'it') {
            if ($hasRoleItOnlyColumn) {
                $this->db->where('role_sidebar_menus.is_it_only', 1);
            } elseif ($hasMenuItOnlyColumn) {
                $this->db->where('sidebar_menus.is_it_only', 1);
            }
        }

        return $this->db
            ->distinct()
            ->order_by('sidebar_menus.sort_order', 'ASC')
            ->get()
            ->result_array();
    }

    private function mergeMenus(array ...$menuSets)
    {
        $merged = [];

        foreach ($menuSets as $menus) {
            foreach ($menus as $menu) {
                $menuId = (int) ($menu['id'] ?? 0);

                if ($menuId <= 0 || isset($merged[$menuId])) {
                    continue;
                }

                $merged[$menuId] = $menu;
            }
        }

        uasort($merged, function ($left, $right) {
            $leftSort = (int) ($left['sort_order'] ?? 0);
            $rightSort = (int) ($right['sort_order'] ?? 0);

            if ($leftSort !== $rightSort) {
                return $leftSort <=> $rightSort;
            }

            $leftParent = (int) ($left['parent_id'] ?? 0);
            $rightParent = (int) ($right['parent_id'] ?? 0);

            if ($leftParent === 0 && $rightParent !== 0) {
                return -1;
            }

            if ($leftParent !== 0 && $rightParent === 0) {
                return 1;
            }

            if ($leftParent !== $rightParent) {
                return $leftParent <=> $rightParent;
            }

            return (int) ($left['id'] ?? 0) <=> (int) ($right['id'] ?? 0);
        });

        return array_values($merged);
    }

    private function getNonItAuthorityMenuIds()
    {
        return [4, 6, 8, 9];
    }

    private function normalizeNonItAuthorityMenus(array $menus)
    {
        foreach ($menus as &$menu) {
            if ((int) ($menu['id'] ?? 0) === 6) {
                $menu['menu_name'] = 'Management';
            }
        }
        unset($menu);

        return $menus;
    }

    public function get_menus_by_role($role_id)
    {
        $department_id = (int) $this->session->userdata('department_id');
        $role_id = (int) $role_id;
        $menus = $this->fetchMenus($role_id, [
            'department_id' => $department_id,
            'scope' => 'auto',
        ]);

        if ($department_id !== 2 && $role_id === 2) {
            $menus = $this->mergeMenus(
                $menus,
                $this->fetchMenus(1, [
                    'department_id' => $department_id,
                    'scope' => 'non_it',
                ]),
                $this->fetchMenus(2, [
                    'department_id' => $department_id,
                    'scope' => 'all',
                    'menu_ids' => $this->getNonItAuthorityMenuIds(),
                ])
            );

            $menus = $this->normalizeNonItAuthorityMenus($menus);
        }

        return $menus;
    }
}
