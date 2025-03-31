// sidebar.js

$(document).ready(function() {
    // Función para cerrar los elementos del menú desplegable
    function closeMenuItems() {
        $('.nav-treeview').slideUp(200); // O usa .hide()
        $('.nav-item.menu-open').removeClass('menu-open');
    }

    // Escucha el evento 'collapsed.lte.pushmenu' de AdminLTE
    $(document).on('collapsed.lte.pushmenu', function() {
        closeMenuItems();
    });

    // Escucha el evento 'expanded.lte.pushmenu' de AdminLTE
    $(document).on('expanded.lte.pushmenu', function() {
        closeMenuItems();
    });

    // Maneja el toggle del treeview (menú desplegable)
    $('[data-lte-toggle="treeview"]').on('click', function(event) {
        event.preventDefault();
        var $this = $(this);
        var $navItem = $this.closest('.nav-item');
        var $navTreeview = $navItem.find('.nav-treeview').first();

        // Alterna la clase 'menu-open' para el ítem del menú
        $navItem.toggleClass('menu-open');

        // Despliega o oculta el treeview con animación
        $navTreeview.slideToggle(200, function() {
            // AdminLTE ajusta el scroll al expandir/contraer el menú,
            // pero si usas slideToggle, necesitas recalcular el scrollbar.
            $(document).trigger('layout.fix');
        });
    });

    // Asegura que el estado del menú se conserve al recargar la página
    $(window).on('load', function() {
        // Recalcular el scrollbar después de que la página se carga completamente
        $(document).trigger('layout.fix');
    });
});