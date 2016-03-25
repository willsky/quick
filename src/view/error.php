<html>
    <head>
        <title>错误页面</title>
    </head>
    <body style="margin:0px 20px;">
        <div style="text-align:center; font-size:30px;">系统错误: <?php echo $code; ?></div>
        <div style="text-align:left; font-size:14px;"><strong>Message</strong>: <?php echo $message; ?></div>
        <div style="text-align:left; font-size:14px;"><strong>File</strong>: <?php echo $errorFile; ?></div>
        <div style="text-align:left; font-size:14px;"><strong>Line</strong>: <?php echo $errorLine; ?></div>
        <div style="text-align:left; font-size:14px;"><strong>Trace</strong>: <pre><?php var_export($trace); ?></pre></div>
    </body>
</html>