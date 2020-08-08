<?php
class Excel{
    function __construct(){
        $this->ord_a = ord('A');
    }

    private function setExportHeader($filename, $spreadsheet){
        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment;filename="'.$filename.'.xlsx"');
        header('Cache-Control: max-age=0');
        header('Cache-Control: max-age=1');

        header ('Expires: Mon, 26 Jul 1997 05:00:00 GMT');
        header ('Last-Modified: '.gmdate('D, d M Y H:i:s').' GMT');
        header ('Cache-Control: cache, must-revalidate');
        header ('Pragma: public');
        $spreadsheet->getProperties()->setCreator("Maarten Balliauw")
            ->setLastModifiedBy("Maarten Balliauw")
            ->setTitle("Office 2007 XLSX Test Document")
            ->setSubject("Office 2007 XLSX Test Document")
            ->setDescription("Test document for Office 2007 XLSX, generated using PHP classes.")
            ->setKeywords("office 2007 openxml php")
            ->setCategory("Test result file");
        $spreadsheet->getDefaultStyle()->getFont()->setName('宋体')
            ->setSize(11);
    }

    /**
     * 获取单元格行名称
     * @param $ord
     * @return string
     */
    private function getCellName($ord){
        $first = $ord / 26;
        if($first >= 1){
            $first = chr(intval($first) + $this->ord_a - 1);
            $second = chr($ord % 26 + $this->ord_a);
        }else{
            $first = '';
            $second = chr($ord + $this->ord_a);
        }
        return $first.$second;
    }

    /**
     * 导出到xlsx格式
     * @param $field 字段信息
     * @param array $data 数据
     * @param string $title 标题说明
     * @param bool $is_fixed_title 是否固定标题，如果不固定则加入时间作为扩展文件名
     * @throws \PhpOffice\PhpSpreadsheet\Exception
     * @throws \PhpOffice\PhpSpreadsheet\Writer\Exception
     */
    public function export($field, $data=[], $title="Default", $is_fixed_title=false){
        if(empty($title)){
            $title = 'null';
        }
        $spreadsheet = new PhpOffice\PhpSpreadsheet\Spreadsheet();
        $filename = $title;
        if(!$is_fixed_title){
            if(empty($title)){
                $filename = "";
            }else{
                $filename .= "_";
            }
            $filename .= date("Ymd_His")."_".str_pad(rand(1, 10000), 5, 0, STR_PAD_LEFT);
        }
        $this->setExportHeader($filename, $spreadsheet);
        $new_data = [];
        $field_max = [];
        if(!is_array($data)){
            $data = [];
        }
        $dcot = count($data) + 1;
        foreach ($data as $key=>$val){
            foreach ($val as $k=>$v){
                $v = trim($v);
                $v = preg_replace("/<.*?>/is", "", $v);
                $new_data[$key][$k] = $v;
                $vlen = (strlen($v) + mb_strlen($v)) / 2;
                if(!isset($field_max[$k]) || $field_max[$k] < $vlen){
                    $field_max[$k] = $vlen;
                }
            }
        }

        $remarks = [];
        if(empty($field_max) && is_array($field)){
            foreach ($field as $key=>$val){
                $key = trim($key);
                $width = 10;
                if(is_array($val)){
                    if(empty($val['name'])){
                        $name = $key;
                    }else{
                        $name = trim($val['name']);
                        empty($name) && $name = $key;
                    }
                    $w = $val['width'];
                    if(isset($w) && is_numeric($w)){
                        $width = intval($w / 12);
                        if($width < 10){
                            $width = 10;
                        }
                    }
                }else{
                    $name = trim($val);
                    empty($name) && $name = $key;
                }
                $field_max[$name] = $width;
                if(!empty($val['remark'])){
                    $remarks[$name] = $val['remark'];
                }
            }
        }

        $ord = 0;
        $chrs = [];
        $i = 0;
        foreach ($field_max as $key=>$val){
            $chr =  $this->getCellName($ord);
            $chrs[$key] = $chr;
            if($val < 10){
                $val = 10;
            }elseif($val > 60){
                $val = 60;
            }else{
                $val ++;
            }
            $fk = $field[$key];
            $spreadsheet->getActiveSheet()->getColumnDimension($chr)->setWidth($val);
            if(isset($fk)) {
                if(is_array($fk)){
                    $fname = $fk['name'];
                    if(empty($fname)){
                        $fname = $key;
                    }
                }else{
                    $fname = $fk;
                }
            }else{
                $fname = $key;
            }
            $spreadsheet->setActiveSheetIndex(0)->setCellValue("{$chr}1", $fname);
            if(!empty($remarks[$key])){
                $sp_remark = $spreadsheet->getActiveSheet()->getComment("{$chr}1");
                $sp_remark->getText()->createTextRun($remarks[$key]);
                $sp_remark->setWidth(200);
            }
            $ord ++;
            $i ++;
        }

        // 设置单元格为文本格式
        $spreadsheet->getActiveSheet()->getStyle("A:{$chr}")
            ->getNumberFormat()
            ->setFormatCode(PhpOffice\PhpSpreadsheet\Style\NumberFormat::FORMAT_TEXT);

        $spreadsheet->getActiveSheet()->freezePane('A2');

        if($i > 0){
            $last_name = $this->getCellName($ord - 1);
            $spreadsheet->getActiveSheet()->getStyle("A1:{$last_name}1")->applyFromArray(
                [
                    'font' => [
                        'color' => [
                            'rgb' => '008800'
                        ]
                    ]
                ]
            );
            // $spreadsheet->getActiveSheet()->setAutoFilter("A1:{$last_name}1"); // 筛选功能
            $spreadsheet->getActiveSheet()->getStyle("A1:{$last_name}1")->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER_CONTINUOUS);
            $spreadsheet->getActiveSheet()->getStyle("A1:{$last_name}{$dcot}")->getAlignment()->setVertical(\PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER);
        }

        $i = 1;
        foreach ($new_data as $key=>$val){
            $i ++;
            foreach ($chrs as $ck=>$cv){
                $spreadsheet->setActiveSheetIndex(0)->setCellValue("{$cv}{$i}", $val[$ck]);
            }
        }


        $spreadsheet->setActiveSheetIndex(0);
        $spreadsheet->getSheet(0)->setTitle($title);
        $writer = \PhpOffice\PhpSpreadsheet\IOFactory::createWriter($spreadsheet, 'Xlsx');
        $writer->save('php://output');
        exit;
    }

    /**
     * 获取xlsx文件
     * @param $file_path 文件名
     * @param array $fields 显示字段
     * @param bool $is_field_hide 是否匹配字段键值
     * @return array
     * @throws \PhpOffice\PhpSpreadsheet\Exception
     * @throws \PhpOffice\PhpSpreadsheet\Reader\Exception
     */
    public function get($file_path, $fields=[], $is_field_hide=false){
        if(!is_file($file_path)){
            return [false, '文件不存在'];
        }

        list($status, $file_type) = $this->isExcelFile($file_path);
        if(!$status){
            return [false, $file_type];
        }

        $reader = PhpOffice\PhpSpreadsheet\IOFactory::createReader($file_type);
        $reader->setReadDataOnly(TRUE);
        $spreadsheet = $reader->load($file_path);

        $worksheet = $spreadsheet->getActiveSheet();
        $max_row = $worksheet->getHighestRow(); // 总行数
        if($max_row < 1){
            return [true, []];
        }
        // 最大输出10000行数据
        if($max_row > 10000){
            $max_row = 10000;
        }
        $max_col = $worksheet->getHighestColumn(); // 总列数
        $max_col_index = PhpOffice\PhpSpreadsheet\Cell\Coordinate::columnIndexFromString($max_col);

        empty($fields) && $fields = [];
        $rfield = [];
        $rwidth = [];
        foreach ($fields as $key=>$val){
            $key = strtolower(trim($key));
            if(is_array($val)){
                $vname = $val['name'];
                if(empty($vname)){
                    $vname = $key;
                }else{
                    $vname = trim($vname);
                }
                if(isset($val['width'])){
                    $rwidth[$vname] = $val['width'];
                }
            }else{
                $vname = trim($val);
            }
            $rfield[$vname] = $key;
        }
        $field_kv = [];
        $field_list = [];
        for($i = 1; $i <= $max_col_index; $i ++){
            $name = trim($worksheet->getCellByColumnAndRow($i, 1)->getValue());
            if(empty($name)){
                continue;
            }
            if(isset($rfield[$name])){
                $field_kv[$i] = $rfield[$name];
                $fv = [
                    'key' => $rfield[$name],
                    'name' => $name
                ];
                if(isset($rwidth[$name])){
                    $fv['width'] = $rwidth[$name];
                }
                $field_list[] = $fv;
            }elseif($is_field_hide){
                $field_kv[$i] = false;
            }else{
                $field_kv[$i] = $name;
                $field_list[] = [
                    'key' => $name,
                    'name' => $name
                ];
            }
        }
        $ret_array = [];
        for ($row = 2; $row <= $max_row; $row++) {
            $ra = [];
            for($i = 1; $i <= $max_col_index; $i ++){
                $fkv = $field_kv[$i];
                if($fkv === false){
                    continue;
                }
                $ra[$fkv] = $worksheet->getCellByColumnAndRow($i, $row)->getValue();
            }
            if(empty($ra)){
                continue;
            }
            $ret_array[] = $ra;
        }
        return [true, [$field_list, $ret_array, $rfield]];
    }

    /**
     * 判断是否为excel文件
     * @param string $file
     * @return array
     */
    private function isExcelFile($file = ''){
        $fp = fopen($file, "rb");
        $bin = fread($fp, 4); //只读2字节
        fclose($fp);
        $str_info = @unpack("C4chars", $bin);
        $type_code = $str_info['chars1'] . $str_info['chars2'] . $str_info['chars3'] . $str_info['chars4'];
        $errors = [
            '982035101' => '该文件已加密，请先解密文件后操作',
        ];
        $oks = [
            '807534' => 'Xlsx',
            '20820717224' => 'Xls',
        ];
        if(isset($errors[$type_code])){
            return [false, $errors[$type_code]];
        }
        $ok = $oks[$type_code];
        if(!isset($ok)){
            return [false, '文件格式错误'];
        }
        return [true, $ok];
    }
}