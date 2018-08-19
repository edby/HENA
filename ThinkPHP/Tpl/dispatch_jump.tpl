<?php
    if(C('LAYOUT_ON')) {
        echo '{__NOLAYOUT__}';
    }
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>跳转提示</title>
</head>
<body>
	<a id="href" href="<?php echo($jumpUrl); ?>" style="color: #000;">
	    <div style="background:url('http://img.hena360.cn/error.jpg') no-repeat center;" id="error">
	        <p style="position: fixed;top: 50%;width: 100%;text-align: center;font-size: 40px;">
	        	<?php
	        		if(isset($message)) {
	        			echo($message);
	        		} else {
	        			echo($error);
	        		}
	        	?>
	        </p>
	    </div>
	</a>
</body>



<p class="jump" style="display: none;">
页面自动sss 跳转</a> 等待时间： <b id="wait"><?php echo($waitSecond); ?></b>
</p>

<script src="__PUBLIC_HOME__/js/jquery-3.1.1.min.js"></script>
<script>
    $(function () {
        $("#error").width($(window).width()).height($(window).height())
    })
</script>
<script type="text/javascript">
(function(){
var wait = document.getElementById('wait'),href = document.getElementById('href').href;
var interval = setInterval(function(){
	var time = --wait.innerHTML;
	if(time <= 0) {
		location.href = href;
		clearInterval(interval);
	};
}, 1000);
})();
</script>
</body>
</html>
