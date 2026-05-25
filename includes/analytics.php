<?php
require_once __DIR__ . '/../config/settings_helper.php';
$ga_id = getSetting('ga_measurement_id');
if (!empty($ga_id)):
?>
<!-- Google tag (gtag.js) -->
<script async src="https://www.googletagmanager.com/gtag/js?id=<?= htmlspecialchars($ga_id) ?>"></script>
<script>
  window.dataLayer = window.dataLayer || [];
  function gtag(){dataLayer.push(arguments);}
  gtag('js', new Date());

  gtag('config', '<?= htmlspecialchars($ga_id) ?>');
</script>
<?php endif; ?>
