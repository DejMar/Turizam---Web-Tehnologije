<?php
$adminPage = $adminPage ?? 'dashboard';
?>
<aside class="admin-sidebar">
    <h2>Administracija</h2>
    <nav>
        <a href="index.php" class="<?= $adminPage === 'dashboard' ? 'active' : '' ?>">Pregled</a>
        <a href="rezervacije.php" class="<?= $adminPage === 'rezervacije' ? 'active' : '' ?>">Rezervacije</a>
        <a href="sobe.php" class="<?= $adminPage === 'sobe' ? 'active' : '' ?>">Sobe</a>
        <a href="korisnici.php" class="<?= $adminPage === 'korisnici' ? 'active' : '' ?>">Korisnici</a>
        <a href="izvjestaji.php" class="<?= $adminPage === 'izvjestaji' ? 'active' : '' ?>">Izvještaji</a>
    </nav>
    <a href="../index.php" class="sidebar-back">&larr; Nazad na sajt</a>
</aside>
