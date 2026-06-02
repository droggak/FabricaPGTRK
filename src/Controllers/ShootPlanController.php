<?php
namespace App\Controllers;
use App\Core\DB;
use App\Core\Input;
use App\Middleware\Auth;

class ShootPlanController extends BaseController {
    private string $dir;
    public function __construct(){ $this->dir=ROOT.'/uploads/shoot_plans/'; }

    public function upload(array $p): void {
        Auth::requireRole(['coordinator','admin']);
        Input::verifyCsrf();
        if(empty($_FILES['plan_file']['tmp_name'])){$this->json(['ok'=>false,'error'=>'Файл не выбран'],422);}
        $file=$_FILES['plan_file'];
        $ext=strtolower(pathinfo($file['name'],PATHINFO_EXTENSION));
        if(!in_array($ext,['docx','doc'],true)){$this->json(['ok'=>false,'error'=>'Только .docx'],422);}
        if($file['size']>10*1024*1024){$this->json(['ok'=>false,'error'=>'Макс. 10 МБ'],422);}
        $stored=date('Y-m-d_His').'_'.bin2hex(random_bytes(4)).'.'.$ext;
        if(!is_dir($this->dir)) mkdir($this->dir,0755,true);
        if(!move_uploaded_file($file['tmp_name'],$this->dir.$stored)){$this->json(['ok'=>false,'error'=>'Ошибка сохранения'],500);}
        $user=Auth::user();
        $id=DB::getInstance()->insert('shoot_plan_files',[
            'filename'=>basename($file['name']),'stored_name'=>$stored,
            'plan_date'=>Input::date('plan_date')?:null,
            'show_id'=>Input::int('show_id')?:null,
            'uploaded_by'=>$user['id'],'uploader_name'=>$user['name'],
            'file_size'=>$file['size'],
        ]);
        $this->json(['ok'=>true,'id'=>$id,'name'=>basename($file['name'])]);
    }

    public function download(array $p): void {
        Auth::require();
        $f=DB::getInstance()->row('SELECT * FROM shoot_plan_files WHERE id=?',[(int)$p['id']]);
        if(!$f)$this->notFound();
        $path=$this->dir.$f['stored_name'];
        if(!file_exists($path))$this->notFound();
        header('Content-Type: application/octet-stream');
        header('Content-Disposition: inline; filename="'.addslashes($f['filename']).'"');
        header('Content-Length: '.filesize($path));
        readfile($path);exit;
    }

    public function list(array $p): void {
        Auth::require();
        $date=Input::date('date','get');
        $w=[];$params=[];
        if($date){$w[]='plan_date=?';$params[]=$date;}
        $where=$w?'WHERE '.implode(' AND ',$w):'';
        $files=DB::getInstance()->rows("SELECT id,filename,plan_date,uploader_name,file_size,created_at FROM shoot_plan_files {$where} ORDER BY created_at DESC LIMIT 30",$params);
        $this->json(['ok'=>true,'files'=>$files]);
    }

    public function delete(array $p): void {
        Auth::requireRole(['coordinator','admin']);
        Input::verifyCsrf();
        $f=DB::getInstance()->row('SELECT * FROM shoot_plan_files WHERE id=?',[(int)$p['id']]);
        if(!$f){$this->json(['ok'=>false,'error'=>'Не найден'],404);}
        $path=$this->dir.$f['stored_name'];
        if(file_exists($path))unlink($path);
        DB::getInstance()->query('DELETE FROM shoot_plan_files WHERE id=?',[(int)$p['id']]);
        $this->json(['ok'=>true]);
    }
}
