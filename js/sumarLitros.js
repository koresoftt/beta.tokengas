async function obtenerLitros() {
    try {
        // **Obtener el año actual**
        const yearActual = new Date().getFullYear().toString();

        // **Hacer la primera petición para saber cuántas páginas hay**
        let urlBase = "/tokengas/php/procesar_ventas.php?page=1";
        console.log("Petición inicial a:", urlBase);

        const respuestaInicial = await fetch(urlBase);
        const datosIniciales = await respuestaInicial.json();

        console.log("Respuesta inicial de la API:", datosIniciales);

        // **Verificar cuántas páginas hay**
        const totalPages = datosIniciales.TotalPages || 1;
        console.log("Total de páginas:", totalPages);

        // **Cargar datos en bloques de 10 páginas para evitar sobrecarga**
        let todosLosMovimientos = [];
        for (let page = 1; page <= totalPages; page += 10) {
            let promesas = [];

            for (let subPage = page; subPage < page + 10 && subPage <= totalPages; subPage++) {
                const urlPagina = `/tokengas/php/procesar_ventas.php?page=${subPage}`;
                promesas.push(fetch(urlPagina).then(res => res.json()));
            }

            // **Esperar a que se completen las 10 peticiones**
            const respuestas = await Promise.all(promesas);

            // **Filtrar solo los movimientos del año vigente y agregarlos a la lista**
            respuestas.forEach(datos => {
                if (datos.Content) {
                    const movimientosFiltrados = datos.Content.filter(mov => mov.DateTime.startsWith(yearActual));
                    todosLosMovimientos = todosLosMovimientos.concat(movimientosFiltrados);
                }
            });

            console.log(`Cargadas ${todosLosMovimientos.length} transacciones del año ${yearActual} hasta la página ${page + 9}`);
        }

        console.log("Total de movimientos del año actual obtenidos:", todosLosMovimientos.length);

        // **Construir la tabla con los datos filtrados**
        let tablaHTML = `
            <table border="1">
                <thead>
                    <tr>
                        <th>Fecha</th>
                        <th>Empresa</th>
                        <th>Litros Consumidos</th>
                    </tr>
                </thead>
                <tbody>
        `;

        todosLosMovimientos.forEach(mov => {
            tablaHTML += `
                <tr>
                    <td>${mov.DateTime}</td>
                    <td>${mov.CompanyName}</td>
                    <td>${parseFloat(mov.ProductVolumeDispensed).toFixed(2)} L</td>
                </tr>
            `;
        });

        tablaHTML += `
                </tbody>
            </table>
        `;

        // **Mostrar la tabla en la página**
        document.getElementById("resultado").innerHTML = tablaHTML;

    } catch (error) {
        console.error("Error obteniendo los litros:", error);
        document.getElementById("resultado").innerHTML = "❌ Error al obtener los datos.";
    }
}

// **Ejecutar función al hacer clic en el botón**
document.addEventListener("DOMContentLoaded", () => {
    document.getElementById("obtenerLitros").addEventListener("click", obtenerLitros);
});
