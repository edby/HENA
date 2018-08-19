<?php
echo '<form action="mp.php" method="post">
  <p>复制txt里面的内容，并填写: <input type="text" name="content" /></p>
  
   <p></p>
    <p></p>
  <input type="submit" value="提交" />
</form>';


if($_POST){
	
		if(strlen($_POST['content'])<20){
			if($_POST){
					$file = fopen('MP_verify_'.$_POST['content'].'.txt','w');
					fwrite($file,$_POST['content']);
					fclose($file);
					echo '<font size="10px" color="red">添加成功</font>';
				}else{
					echo '添加失败';
				}
			
		}else{
			echo '文件内容有误,请重新填写';
		}
	

	
	
}