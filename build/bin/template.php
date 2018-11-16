<!DOCTYPE html>
<html>
  <head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1.0">
    <title>demo</title>

    <script type="text/javascript" src="<?php echo ROOT . "/vendor/phppacks/phppack/build/bin/babel.js"; ?>"></script>
    <script type="text/javascript">
    var __phppack_require = [];
    var __phppack_compile = function(modules) {
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
        require.m = modules;
        require.c = installedModules;
        require.p = "";
        return require("phppack_0");
    }
    </script>
  </head>
  <body>
    <div id="app"></div>
    <!-- built files will be auto injected -->

	<?php foreach ($compontent as $key => $value): ?>
    <script type="text/babel" code="<?php echo $value['index']; ?>">
		<?php echo $value['body']; ?>
    </script>
    <?php endforeach;?>
    <script type="text/babel" code="999999999">
	__phppack_compile(__phppack_require);
    </script>
  </body>
</html>
