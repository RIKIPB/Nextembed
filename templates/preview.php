<?php
// preview.php
?>
<html>
<head>
    <title>Document Preview</title>
</head>
<body>
    <?php foreach ($previews as $base64Image): ?>
        <img src="<?php echo htmlspecialchars($base64Image); ?>" style="width:100%; margin-bottom:10px;" />
    <?php endforeach; ?>
</body>
</html>
