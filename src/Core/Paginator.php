<?php
namespace App\Core;

class Paginator {
    public static function render(int $page, int $pages, array $params=[]): string {
        if($pages<=1) return '';
        unset($params['page']);
        $qs=$params?'&'.http_build_query($params):'';
        $html='<div class="pagination">';
        // Назад
        if($page>1) $html.='<a class="page-btn" href="?page='.($page-1).$qs.'">←</a>';
        else $html.='<span class="page-btn disabled">←</span>';
        // Страницы
        $range=2;
        for($i=1;$i<=$pages;$i++){
            if($i===1||$i===$pages||($i>=$page-$range&&$i<=$page+$range)){
                $cls='page-btn'.($i===$page?' active':'');
                $html.='<a class="'.$cls.'" href="?page='.$i.$qs.'">'.$i.'</a>';
            } elseif($i===$page-$range-1||$i===$page+$range+1){
                $html.='<span class="page-btn disabled">…</span>';
            }
        }
        // Вперёд
        if($page<$pages) $html.='<a class="page-btn" href="?page='.($page+1).$qs.'">→</a>';
        else $html.='<span class="page-btn disabled">→</span>';
        $html.='</div>';
        return $html;
    }
}
