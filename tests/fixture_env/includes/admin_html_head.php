<meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>Admin Menu Icons — local test fixture</title>
<?php foreach (['css/bootstrap.min.css', 'fontawesome/css/fontawesome.min.css', 'fontawesome/css/solid.min.css', 'css/menu.css', 'css/stylesheet.css'] as $asset) { ?>
<link rel="stylesheet" href="/shop/secret-admin/core/<?= $asset ?>">
<?php } ?>
<link rel="stylesheet" href="/shop/zc_plugins/AdminMenuIcons/v1.1.0/admin/includes/css/global_stylesheet_admin_menu_icons.css">
<link rel="stylesheet" href="/shop/zc_plugins/AdminMenuIcons/v1.1.0/admin/includes/css/admin_menu_icons.css">
<script src="/shop/secret-admin/core/javascript/jquery.min.js"></script>
<script src="/shop/secret-admin/core/javascript/bootstrap.min.js"></script>
<?php require $pluginRoot . '/admin/includes/javascript/global_jscript_admin_menu_icons.php'; ?>
<script src="/shop/zc_plugins/AdminMenuIcons/v1.1.0/admin/includes/javascript/global_jscript_admin_menu_icons.js"></script>
<script src="/shop/zc_plugins/AdminMenuIcons/v1.1.0/admin/includes/javascript/admin_menu_icons.js"></script>
