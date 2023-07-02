<?php if (!$checkedException): ?>
<!DOCTYPE html>
<html>
<head>
	<title>500 Internal Server Error</title>
	<link rel="stylesheet" href="<?php echo($offset); ?>themes/system/css/acms-admin.min.css">

</head>
<body>
<?php endif; ?>
<style>
	body {
		font-family: Sans-Serif;
		line-height: 1.5;
	}
</style>
<div class="acms-admin-container">
	<div class="acms-admin-grid">
		<div class="acms-admin-col-12">
			<p class="acms-admin-alert">
				500 Internal Server Error.
			</p>
		</div>

        <?php foreach ($errors as $error) : ?>
			<div class="acms-admin-col-12">
				<div class="acms-admin-panel">
					<div class="acms-admin-panel-header">
						<h1 class="acms-admin-panel-title">
                            <?php echo($error->message); ?> in <?php echo($error->file); ?>
							line: <?php echo($error->line); ?>
						</h1>
					</div>
					<div class="acms-admin-panel-body">
                        <?php echo($error->trace); ?>
					</div>
				</div>
			</div>
        <?php endforeach; ?>
	</div>
</div>
<?php if (!$checkedException): ?>
</body>
</html>
<?php endif; ?>