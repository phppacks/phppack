<!DOCTYPE html>
<html>
  <head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1.0">
    <title>PHPPACK编译</title>
    <script type="text/javascript" src="<?php echo ROOT . "/vendor/phppacks/phppack/build/bin/jquery.js"; ?>"></script>
    <script type="text/javascript" src="<?php echo ROOT . "/vendor/phppacks/phppack/build/bin/babel.js"; ?>"></script>
    <script type="text/javascript" src="<?php echo ROOT . "/vendor/phppacks/phppack/build/bin/base64.js"; ?>"></script>
</head>
<body>
    <div id="app"></div>
    <!--
      --循环输出所有组件信息
      -->
    <?php foreach ($compontent as $key => $value): ?>
    <script type="text/phpppack" id="compontent_<?php echo $key; ?>">
    <?php echo $value; ?>
    </script>
    <?php endforeach;?>



    <script type="text/phpppack" id="compontent_style">
    <?php foreach ($style as $key => $value): ?>
    <?php echo $value; ?>
    <?php endforeach;?>
    </script>



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
        return require("phppack_0_a");
    })([
    </script>
    <!--
      --通过Jquery获取数据信息拼装
      -->
    <script type="text/javascript">
    var total = <?php echo count($compontent); ?>;
    var posturl = '<?php echo $url; ?>';
    var phpppack = $("#compontent_s").text();
    var style = $("#compontent_style").text();
    for (var i = 0; i < total; i++) {
        var compontent = $("#compontent_"+i).text();
        var script = {
            async: false,
            content: compontent,
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
    }
    phpppack += "{}])";

    var base = new Base64();

    var data = {
        compontent: base.encode(phpppack),
        style:base.encode(style),
        config:'<?php echo $config; ?>',
        css:'<?php echo $css; ?>',
        js:'<?php echo $js; ?>'
    };



    $.ajax({
        url: posturl,
        type:"POST",
        data:data,
        success: function(e){
            console.log(e);
           //location.reload();
        }
     });


    </script>
</body>
</html>