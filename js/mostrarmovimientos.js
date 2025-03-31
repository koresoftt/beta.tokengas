// Variables globales
let anioSeleccionado = document.getElementById("select-anio").value; // Año inicial sincronizado
let mesSeleccionado = ""; // Mes seleccionado
let movimientosProcesadosAPI = []; // Array global para almacenar los movimientos procesados

console.log(`Año inicial: ${anioSeleccionado}`); // Log inicial para depuración

// Actualizar el año seleccionado
document.getElementById("select-anio").addEventListener("change", (event) => {
    anioSeleccionado = event.target.value; // Actualiza el año
    console.log(`Año seleccionado: ${anioSeleccionado}`); // Log para depuración

    // Si hay un mes seleccionado, recargar movimientos con el nuevo año
    if (mesSeleccionado) {
        cargarMovimientos(mesSeleccionado);
    }
});

// Capturar el mes seleccionado y resaltar el botón activo
document.querySelectorAll(".btn-month").forEach((button) => {
    button.addEventListener("click", () => {
        mesSeleccionado = button.getAttribute("data-mes"); // Captura el mes seleccionado
        document.querySelectorAll(".btn-month").forEach((btn) => btn.classList.remove("active"));
        button.classList.add("active");

        console.log(`Mes seleccionado: ${mesSeleccionado}`); // Log para depuración
        cargarMovimientos(mesSeleccionado); // Cargar movimientos con mes y año seleccionados
    });
});

/**
 * Función para cargar movimientos desde el endpoint.
 * Se utiliza URLSearchParams para construir la URL con los parámetros.
 * Se asume que el endpoint (por ejemplo, /obtener_movimientos) se encarga de extraer el token desde la sesión.
 */
function cargarMovimientos(mes) {
    const mesFormateado = mes.padStart(2, "0"); // Asegurar dos dígitos para el mes

    // Construir los parámetros de consulta de forma robusta
    const params = new URLSearchParams({
        accion: "obtener_movimientos",
        mes: mesFormateado,
        year: anioSeleccionado
    });
    const url = `http://127.0.0.1:5000/obtener_movimientos?${params.toString()}`;
    console.log(`URL de la solicitud: ${url}`); // Log para depuración

    const movementsList = document.getElementById("movements-list");
    // Mostrar mensaje de carga en la tabla
    movementsList.innerHTML = '<tr><td colspan="7" class="text-center">⏳ Cargando...</td></tr>';

    fetch(url)
        .then((response) => response.json())
        .then((data) => {
            if (data.status === "success" && Array.isArray(data.movements)) {
                mostrarMovimientos(data.movements);
            } else {
                console.error("No se encontraron movimientos o respuesta inválida:", data);
                movementsList.innerHTML = '<tr><td colspan="7" class="text-center text-danger">No se encontraron movimientos.</td></tr>';
            }
        })
        .catch((error) => {
            console.error("Error en la solicitud:", error);
            movementsList.innerHTML = '<tr><td colspan="7" class="text-center text-danger">Error al cargar los movimientos.</td></tr>';
        });
}

/**
 * Función para mostrar los movimientos en la tabla.
 * Se filtran y ordenan los movimientos, y se almacena la información procesada en un array global.
 */
function mostrarMovimientos(movimientos) {
    const movementsList = document.getElementById("movements-list");
    movementsList.innerHTML = ""; // Limpia la tabla

    // Vaciar el array global
    movimientosProcesadosAPI.length = 0;

    // Filtrar movimientos: Solo incluir aquellos cuyo MovementType sea 0 o 1
    const movimientosFiltrados = movimientos.filter(movement => movement.MovementType === 0 || movement.MovementType === 1);

    // Ordenar los movimientos por fecha descendente usando MovementDate
    movimientosFiltrados.sort((a, b) => new Date(b.MovementDate) - new Date(a.MovementDate));

    movimientosFiltrados.forEach(movement => {
        const fechaAPI = movement.MovementDate || "N/A";
        const fechaFormateada = formatoFechaDDMMYYYY(fechaAPI.split("T")[0]);

        const codigoContrato = movement.ContractCode || "N/A";
        const compania = movement.CompanyName || "N/A";
        const descripcionMovimiento = movement.MovementDescription || "N/A";
        const tipoMovimiento = determinarTipoMovimiento(movement.MovementType, descripcionMovimiento);
        const monto = movement.Amount !== undefined
            ? `$${movement.Amount.toLocaleString("en-US", { minimumFractionDigits: 2, maximumFractionDigits: 2 })}`
            : "N/A";

        const row = document.createElement("tr");
        row.innerHTML = `
            <td>${fechaFormateada}</td>
            <td>${codigoContrato}</td>
            <td>${compania}</td>
            <td>${tipoMovimiento}</td>
            <td>${descripcionMovimiento}</td>
            <td>${monto}</td>
            <td>❌</td>
        `;
        movementsList.appendChild(row);

        // Guardar la información procesada en el array global
        movimientosProcesadosAPI.push({
            fecha: fechaFormateada,
            monto: parseFloat(movement.Amount) || 0,
            movimiento: extraerCodigoMovimiento(descripcionMovimiento),
            tipo: tipoMovimiento,
            contrato: codigoContrato,
            compania: compania
        });
    });

    console.log("Movimientos filtrados y procesados de la API:", movimientosProcesadosAPI);
}

/**
 * Convierte una fecha en formato YYYY-MM-DD a DD/MM/YYYY.
 *
 * @param {string} fecha Fecha en formato YYYY-MM-DD.
 * @returns {string} Fecha en formato DD/MM/YYYY.
 */
function formatoFechaDDMMYYYY(fecha) {
    if (!fecha || fecha === "N/A") return fecha;
    const partes = fecha.split("-");
    return `${partes[2]}/${partes[1]}/${partes[0]}`;
}

/**
 * Determina el tipo de movimiento según el MovementType y la descripción.
 *
 * @param {number} movementType Tipo de movimiento.
 * @param {string} movimientoDescripcion Descripción del movimiento.
 * @returns {string} El tipo de movimiento formateado.
 */
function determinarTipoMovimiento(movementType, movimientoDescripcion) {
    const descripcion = (typeof movimientoDescripcion === "string")
        ? movimientoDescripcion.toUpperCase().trim()
        : "";

    if (movementType === 0 && (descripcion.startsWith("TB") || descripcion.startsWith("CH") || descripcion.startsWith("EF"))) {
        return "DEPÓSITO";
    }
    if (movementType === 0 && descripcion.startsWith("COM")) {
        return "COMISIÓN";
    }
    if (movementType === 1) {
        if (descripcion.startsWith("TB")) return "OTRO";
        if (descripcion.startsWith("COM")) return "COMISIÓN";
        if (descripcion.startsWith("R")) return "RETIRO";
        if (descripcion.startsWith("D")) return "DEVOLUCIÓN";
    }
    return "OTRO";
}

/**
 * Extrae el código numérico al final de la descripción del movimiento.
 *
 * @param {string} descripcion Descripción del movimiento.
 * @returns {string} El código extraído o "N/A" si no se encuentra.
 */
function extraerCodigoMovimiento(descripcion) {
    const partes = descripcion.split("-");
    const codigo = partes.pop()?.trim();
    return codigo && /^\d+$/.test(codigo) ? codigo : "N/A";
}

// Filtro para la tabla: Permite buscar movimientos filtrando las filas de la tabla
document.getElementById("tabla-filtro").addEventListener("keyup", function() {
    const filtro = this.value.toLowerCase();
    const filas = document.querySelectorAll("#movements-list tr");
    
    filas.forEach(fila => {
        const textoFila = fila.textContent.toLowerCase();
        fila.style.display = textoFila.includes(filtro) ? "" : "none";
    });
});
