<!doctype html>
<html lang="zh">

<head>
    <meta charset="UTF-8">
    <meta http-equiv="Content-Type" content="text/html">
    <meta name="viewport" content="width=device-width,initial-scale=1,maximum-scale=1,user-scalable=no,minimal-ui">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-touch-fullscreen" content="no">
    <title>错误报告</title>
    <style type="text/css">
    html,
    body {
        width: 100%;
        height: 100%;
        background-color: #253e52;
        margin: 0;
        padding: 0;
    }

    .error-title,.error-file {
        line-height: 50px;
        height: 50px;
        font-size: 16px;
        padding-left: 10px;
        color: #fff;
    }

    .error-list ol {
        margin-top: 0;
        color: #f1f6f7;
        font-weight: 100;

    }

    .error-list li,
    .li {}

    .active {
        background-color: #df5c61;
    }
    </style>
</head>

<body>
    <div class="error">
        <div class="error-title">
            错误提示：<?php echo $e->getMessage(); ?>
        </div>
        <div class="error-list">
            <ol start="<?php echo $steLine + 1; ?>">

<?php for ($x = $steLine; $x <= ($errLine + 2); $x++) {
    ?>

    <?php if ($x == $errLine): ?>
            <li class="active"><?php echo str_replace(' ', '&nbsp;', $codeList[$x]); ?></li>
                <div class="li">
<?php
if ($column > 0) {
        echo str_repeat('&nbsp;', $column);
    }?>^
                </div>
    <?php else: ?>
        <li><?php echo str_replace(' ', '&nbsp;', $codeList[$x]); ?></li>
    <?php endif;?>

<?php }?>
            </ol>
        </div>
        <div class="error-file">
            查找文件：<?php echo $file; ?>
        </div>
    </div>
</body>

</html>