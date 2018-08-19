<?php
/**
 * 递归展示商品分类
 */
function getCateTree($data, $opt = false)
{
		
	if($opt){
		$str .= '<ul class="Cate"  >';	
	} else {
		$str .= '<ul class="Cate" style="display:none;" >';	
	}

	foreach ($data as $key => $value) {
		$str .= '<li >';
		//var_dump($value);die;
		for ($i = 0; $i < $value['level']; $i++) {
			$str .= '<span class="level_button">&nbsp;</span>';
		}

		$str .= '<span class="level_button"><a style="text-decoration:none;" class="ml-5 Huifold"  title="商品规格" data-id="0">
				<i class="Hui-iconfont">&#xe6d5;</i>
			</a></span></li>';
		$str .= '<li>'.$value['cat_title'].$value['cat_title'].$value['cat_title'].$value['cat_title'].$value['cat_title'].'</li>';

		if(isset($value['low'])){
			$str .= getCateTree($value['low']);
		}
		$str .= '</ul>';
	}
	
	return $str;

}