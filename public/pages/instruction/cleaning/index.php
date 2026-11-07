<?php
$slideFiles = glob(__DIR__ . '/slides/*.html');
sort($slideFiles);
?>
<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reinigung</title>
    <link rel="stylesheet" type="text/css" href="/assets/css/styles.css">
    <link rel="stylesheet" type="text/css" href="../styles.css">
</head>
<body>

<button id="back" class="button_neutral">Zurück</button>

<div id="presentation">

    <?php foreach ($slideFiles as $index => $file): ?>
        <section class="slide <?= $index === 0 ? 'active' : '' ?>">
            <?php include $file; ?>
        </section>
    <?php endforeach; ?>

</div>

<div class="controls">
    <button id="prev" class="button_neutral">‹</button>

    <span id="counter">
        1 / <?= count($slideFiles) ?>
    </span>

    <button id="next" class="button_neutral">›</button>
</div>

<script>
    window.totalSlides = <?= count($slideFiles) ?>;
</script>
<script src="../presentation.js"></script>
<script src="/assets/js/api.js"></script>
<script src="/assets/js/tool-status.js"></script>
<script src="script.js"></script>

</body>
</html>