<?php
return function(){
    $mdpath = storage_path("app/public/");
    header("Content-Type:text/html; charset=utf-8");
    header("Access-Control-Allow-Origin: *");
    $save_path = "/markdown/".date("Ym/d");
    $file_name = time()."_".$this->apifun->strRand(5, "0123456789");

    function exit_json($status, $msg="", $data=""){
        echo json_encode([
            'success' => $status,
            'message' => $msg,
            'url' => $data
        ]);
        exit();
    }
    try {
        $upload = $this->upload([[800, 800]], false, $save_path, $file_name);
        $files = $upload->files;
        if(empty($files) || empty($files['editormd-image-file']) || empty($files['editormd-image-file']['800_800'])){
            exit_json(0, '图片文件上传有误！');
        }
        $oldfile = $files['editormd-image-file']['800_800'];
        $newfile = str_replace("_800_800", "", $oldfile);
        $oldsrc = $mdpath . $oldfile;
        $newsrc = $mdpath . $newfile;
        rename($oldsrc, $newsrc);
        exit_json(1, '上传成功', "/storage/".$newfile);
    }catch (Exception $e){
        exit_json(0, $e->getMessage());
    }
    exit_json(0, '404 ERR!');
};