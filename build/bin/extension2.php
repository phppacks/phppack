<!DOCTYPE html>
<html>
  <head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1.0">
    <title>PHPPACK编译</title>
    <script type="text/javascript" src="<?php echo ROOT . "/vendor/phppacks/phppack/build/bin/jquery.js"; ?>"></script>
    <script type="text/javascript" src="<?php echo ROOT . "/vendor/phppacks/phppack/build/bin/babel.js"; ?>"></script>
    <script type="text/javascript" src="<?php echo ROOT . "/vendor/phppacks/phppack/build/bin/base64.js"; ?>"></script>
    <script type="text/javascript">
	$(document).ready(function(){
		  var lockList = <?php echo $lock; ?>;
		  var config = <?php echo $config; ?>;
		  var url  = '<?php echo $url; ?>'
		  var base = new Base64();
		  for (var i in lockList) {
		  	  var style = $(".style[name='"+i+"']").text();
		  	  var info = $(".info[name='"+i+"']").text();
		  	  var phpppack = $("#compontent_s").text();
			  $(".compontent[name='"+i+"']").each(function(){
					var script = {
			            async: false,
			            content: $(this).text(),
			            error: true,
			            executed: true,
			            loaded: true,
			            plugins: null,
			            presets: null,
			            url: null
			        };
			        phpppack += "function(module, exports, require) {\n";
			        phpppack += Babel.phppackCompile(script);
			        phpppack += "\n},"
		  	  });
		  	  phpppack += "{}])";

	  	      var submit = {
					script:base.encode(phpppack),
			        style:base.encode(style),
			        config:config,
					info:info
			  };
		      $.ajax({
					url: url,
					type:"POST",
					data:submit,
					success: function(e){
						console.log(e);
						//location.reload();
					}
		      });
		  }

	});
	</script>
</head>
<body>
    <div id="app"></div>
    <script type="text/phpppack" id="compontent_s">
    (function(modules) {
        var installedModules = [];
        function require(moduleNum) {
            var numArr =  moduleNum.split('_');
            var moduleId = numArr[1];
            if (installedModules[moduleId]){
                return installedModules[moduleId].exports;
            }
            var module = installedModules[moduleId] = {
                exports: {},
                id: moduleId,
                loaded: false
            };
            modules[moduleId].call(module.exports, module, module.exports, require);
            module.loaded = true;
            return module.exports;
        }
        function _interopRequireDefault(obj) { return obj && obj.__esModule ? obj : { default: obj }; }

        require.ensure = function(name){
            var _ensure = require(name);
            var _ensure2 = _interopRequireDefault(_ensure);
            return _ensure2.default;
        }
        require.m = modules;
        require.c = installedModules;
        require.p = "";
        window.onload =function(){
            return require("phppack_0");
        }
    })([
    </script>

	<!---------------------------------------------------------------------------
	  -- 开启锁定文件数据循环
	  -------------------------------------------------------------------------->
	<?php foreach ($data as $name => $extension): ?>
		 <!--
	      --循环输出所有组件信息
	      -->
	    <?php foreach ($extension['compontent'] as $key => $value): ?>
	    <script type="text/phpppack" name="<?php echo $name; ?>" class="compontent">
	    	<?php echo $value; ?>
	    </script>
	    <?php endforeach;?>

	    <!--
	      --循环输出所有样式信息
	      -->
	    <script type="text/phpppack" name="<?php echo $name; ?>" class="style">
	    	<?php echo implode("", $extension['style']); ?>
	    </script>

	    <script type="text/phpppack" name="<?php echo $name; ?>" class="info">
	    	<?php echo $extension['info']; ?>
	    </script>
	<?php endforeach;?>
	<!---------------------------------------------------------------------------
	  -- 结束锁定文件数据循环
	  -------------------------------------------------------------------------->
</body>
</html>