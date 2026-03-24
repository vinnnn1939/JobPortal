<?php require BASE_PATH . '/app/Views/layouts/header.php'; ?>
<section><div class="container"><div class="text-center">
     <h1>My Dashboard</h1><br>
     <p class="lead">KUKUKAKA 126 ssssss <?php echo htmlspecialchars($_SESSION['email']); ?>!</p>
     <br>
     <a href="<?php echo $_SERVER['REQUEST_SCHEME'] . '://' . $_SERVER['HTTP_HOST']; ?>/jobs" class="section-btn btn btn-default">Browse Jobs</a>
</div></div></section>
<?php require BASE_PATH . '/app/Views/layouts/footer.php'; ?>
<h1>HIIi</h1>
