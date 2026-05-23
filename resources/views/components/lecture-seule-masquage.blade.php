@if($lectureSeule ?? false)
<style>
    body.annee-lecture-seule [data-mutation-ui] { display: none !important; }
    body.annee-lecture-seule form[data-mutation-form] { pointer-events: none; opacity: 0.55; }
</style>
<script>
document.addEventListener('DOMContentLoaded', function () {
    if (!document.body.classList.contains('annee-lecture-seule')) return;

    var allow = ['logout', 'login', 'password', 'annees-scolaires', 'quitter-espace', 'global-search'];

    document.querySelectorAll('a[href]').forEach(function (a) {
        var href = a.getAttribute('href') || '';
        if (!href || allow.some(function (s) { return href.indexOf(s) !== -1; })) return;
        if (/\/(create|edit)(\/|$|\?)/.test(href) || /import/i.test(href)) {
            a.setAttribute('data-mutation-ui', '1');
            var row = a.closest('tr, li, .flex, .grid > div');
            if (row && row.querySelectorAll('a[href]').length === 1) {
                row.setAttribute('data-mutation-ui', '1');
            }
        }
    });

    document.querySelectorAll('form').forEach(function (form) {
        var method = (form.getAttribute('method') || 'get').toLowerCase();
        if (method === 'get') return;
        var action = form.getAttribute('action') || '';
        if (allow.some(function (s) { return action.indexOf(s) !== -1; })) return;
        if (form.hasAttribute('data-allow-mutation')) return;
        form.setAttribute('data-mutation-form', '1');
        form.querySelectorAll('button[type="submit"], input[type="submit"]').forEach(function (btn) {
            btn.setAttribute('data-mutation-ui', '1');
        });
    });

    document.querySelectorAll('button[formaction], button[type="submit"]').forEach(function (btn) {
        var action = btn.getAttribute('formaction') || (btn.closest('form') && btn.closest('form').action) || '';
        if (action && (/\/destroy|\/delete|\/store|\/update/i.test(action))) {
            btn.setAttribute('data-mutation-ui', '1');
        }
    });
});
</script>
@endif
