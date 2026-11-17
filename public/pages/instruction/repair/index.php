<?php
$slideFiles = glob(__DIR__ . '/slides/*.html');
sort($slideFiles);
?>
<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Werkzeug warten – Anleitung</title>
    <link rel="stylesheet" type="text/css" href="/assets/css/styles.css">
    <link rel="stylesheet" type="text/css" href="/assets/css/page.css">
    <link rel="stylesheet" type="text/css" href="../styles.css">
</head>
<body class="page">

<?php include __DIR__ . '/../../../components/app_header.php'; ?>

<header class="page-header">
    <div class="page-header-left">
        <!-- Verhalten: ../presentation.js (zurück oder Tab schließen) -->
        <button type="button" id="back" class="button_neutral page-back">Zurück</button>

        <div class="page-title">
            <h1>Werkzeug warten</h1>
            <span>Anleitung – Werkzeug warten</span>
        </div>
    </div>

    <div class="page-meta">Pfeiltasten: blättern</div>
</header>

<main class="page-main">
    <section class="page-panel presentation-panel">

        <div id="presentation">
            <?php foreach ($slideFiles as $index => $file): ?>
                <section class="slide <?= $index === 0 ? 'active' : '' ?>">
                    <?php include $file; ?>
                </section>
            <?php endforeach; ?>
        </div>

        <div class="controls">
            <button id="prev" class="button_neutral" aria-label="Vorherige Folie">‹ Zurück</button>

            <span id="counter">
                1 / <?= count($slideFiles) ?>
            </span>

            <button id="next" class="button_neutral" aria-label="Nächste Folie">Weiter ›</button>
        </div>

    </section>
</main>

<script>
    window.totalSlides = <?= count($slideFiles) ?>;
</script>
<script src="../presentation.js"></script>

<script src="/assets/js/app-header.js"></script>
</body>
</html>
