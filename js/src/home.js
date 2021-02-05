AJAX.registerTeardown('home.js', function () {
    $('#themesModal').off('show.bs.modal');
});

AJAX.registerOnload('home.js', function () {
    $('#themesModal').on('show.bs.modal', function () {
        $.get('index.php?route=/themes', function (data) {
            $('#themesModal .modal-body').html(data.themes);
        });
    });
});
