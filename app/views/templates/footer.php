    </main>
    </div><!-- /.content -->
    </div><!-- /.app -->

    <script nonce="<?= e(CSP_NONCE); ?>">
      const BASEURL = <?= js_value(BASEURL); ?>;
      const CSRF_TOKEN = <?= js_value(csrf_token()); ?>;
    </script>


    <!-- JS aplikasi (vanilla, tanpa jQuery/Bootstrap) -->
    <script src="<?= asset('js/app.js'); ?>"></script>
    <script src="<?= asset('js/tasks.js'); ?>"></script>
    <script src="<?= asset('js/agenda.js'); ?>"></script>
    <script src="<?= asset('js/notes.js'); ?>"></script>

    </body>

    </html>