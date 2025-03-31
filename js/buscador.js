$(document).ready(function () {
    $("#compania").autocomplete({
        source: function (request, response) {
            $.ajax({
                url: "/tokengas/php/compañias.php",
                type: "GET",
                dataType: "json",
                success: function (data) {
                    response($.map(data, function (item) {
                        return {
                            label: item.name, // Nombre visible en la lista
                            value: item.name  // Valor que se colocará en el input
                        };
                    }));
                },
                error: function () {
                    console.error("Error al obtener datos de compañías.");
                }
            });
        },
        minLength: 1, // Número de caracteres antes de mostrar sugerencias
        delay: 200
    });
});
