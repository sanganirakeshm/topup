<?php

namespace Dhi\AdminBundle\Helper;

class GridFunction {

    public function getSearchData($aColumns = array()) {

        $SearchType = 'ORLIKE';
        /*
         * Paging
         */
        $per_page = 10;
        $offset = 0;

        if (isset($_GET['iDisplayStart']) && $_GET['iDisplayLength'] != '-1') {
            $per_page = (int) $_GET['iDisplayLength'];
            $offset = (int) $_GET['iDisplayStart'];
        }

        /*
         * Ordering
         */
        $order_by = "";
        $sort_order = "";
        if (isset($_GET['iSortCol_0'])) {
            $order_by = "";
            $sort_order = "";

            for ($i = 0; $i < intval($_GET['iSortingCols']); $i++) {
                if ($_GET['bSortable_' . intval($_GET['iSortCol_' . $i])] == "true") {
                    $order_by = $aColumns[intval($_GET['iSortCol_' . $i])];
                    $sort_order = $_GET['sSortDir_' . $i];
                }
            }
        }

        /*
         * Filtering
         * NOTE this does not match the built-in DataTables filtering which does it
         * word by word on any field. It's possible to do here, but concerned about efficiency
         * on very large tables, and MySQL's regex functionality is very limited
         */
        $search_data = array();
        if (isset($_GET['sSearch']) && $_GET['sSearch'] != "") {
            $sWhere = "WHERE (";
            for ($i = 0; $i < count($aColumns); $i++) {
                $search_data[$aColumns[$i]] = $_GET['sSearch'];
            }
            $SearchType = 'ORLIKE';
        }

        /* Individual column filtering */
        for ($i = 0; $i < count($aColumns); $i++) {
            if (isset($_GET['bSearchable_' . $i]) && $_GET['bSearchable_' . $i] == "true" && $_GET['sSearch_' . $i] != '' && $_GET['sSearch_' . $i] != '~') {
                $search_data[$aColumns[$i]] = $_GET['sSearch_' . $i];
                $SearchType = 'ANDLIKE';
            }
        }
        $data = array();
        $data['order_by'] = $order_by;
        $data['sort_order'] = $sort_order;
        $data['search_data'] = $search_data;
        $data['per_page'] = $per_page;
        $data['offset'] = $offset;
        $data['SearchType'] = $SearchType;
        return $data;
    }

    public function orLikeSearch($data) {

        if (!empty($data)) {
            $i = 0;
            foreach ($data as $key => $value) {
                if ($i == 0) {
                    // pass the first element of the array
                    $sub = '(' . $key . ' LIKE \'%' . preg_replace("/'/", "''", $value) . '%\' ';
                } else {
                    //$this->db->or_like('Linkname', $search_string_multiple);
                    $sub .= 'OR ' . $key . ' LIKE \'%' . preg_replace("/'/", "''", $value) . '%\' ';
                }
                $i++;
            }
            $sub .= ')';
            return $sub;
        }
        return false;
    }

    public function andLikeSearch($data, $isToreg = true) {
        
        if (!empty($data)) {
            
            $i = 0;
            $sub = '';
            $querystr = array();
            $query = '';
            foreach ($data as $key => $value) {
                if ($i == 0) {
                    $sub = '( ';
                } else {
                    $sub = 'AND ';
                }

                if (strtoupper($value['Operator']) == 'LIKE') {
                    $querystr[] = $value['Field'] . ' LIKE \'%' . preg_replace("/'/", "''", $value['Value']) . '%\' ';
                } else if (strtoupper($value['Operator']) == 'RANGE') {
                    if (isset($value['Condition']) && !empty($value['Condition'])) {
                        foreach ($value['Condition'] as $val) {
                            $querystr[] = $val['Field'] . " " . $val['Operator'] . " '" . preg_replace("/'/", "''", $val['Value']) . "' ";
                        }
                    }
                } else if (strtoupper($value['Operator']) == 'IN') {
                	
                	$querystr[] = $value['Field'] . " IN ('" . ($isToreg ? preg_replace("/'/", "''", $value['Value']) : $value['Value']) . "') ";
                } else {
                    $querystr[] = $value['Field'] . " " . $value['Operator'] . " '" . preg_replace("/'/", "''", $value['Value']) . "' ";
                }

                $i++;
            }

            if (isset($querystr) && !empty($querystr)) {
                $query = '( ' . implode(' AND ', $querystr) . ' )';
            }
            return $query;
        }
        return false;
    }

}
