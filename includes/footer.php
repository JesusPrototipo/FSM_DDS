
</main><!-- /container -->

<footer class="border-top py-3 mt-4">
    <div class="container d-flex justify-content-between align-items-center">
        <span class="text-body-secondary small">
            © <?= date('Y') ?> Digital Document Services
        </span>
        <span class="text-body-secondary small">
            <?php $u = Auth::usuario(); echo $u ? htmlspecialchars($u['nombre']) : ''; ?>
        </span>
    </div>
</footer>

<!-- Bootstrap JS -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js"
        integrity="sha384-FKyoEForCGlyvwx9Hj09JcYn3nv7wiPVlz7YYwJrWVcXK/BmnVDxM+D2scQbITxI"
        crossorigin="anonymous"></script>

<!-- JS propio -->
<script src="<?= BASE_PATH ?>/assets/js/app.js"></script>

</body>
</html>
