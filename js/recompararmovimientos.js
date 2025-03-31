function detectarMovimientosAEditar(csvData) {
    const rows = document.querySelectorAll("#movements-list tr");

    rows.forEach((row, index) => {
        const celdaAcciones = row.cells[6];

        // Verificar si ya existe el botón para evitar duplicados
        if (!celdaAcciones.querySelector(".btn-editar")) {
            // Botón Editar (Lápiz)
            const botonEditar = document.createElement("button");
            botonEditar.textContent = "✏️";
            botonEditar.classList.add("btn", "btn-sm", "btn-editar");
            botonEditar.setAttribute("data-index", index);

            // Estilos en línea para mejorar la apariencia
            botonEditar.style.cssText = `
                background: none;
                border: none;
                padding: 0;
                cursor: pointer;
                font-size: 16px;
                margin-left: 10px;
                display: inline-flex;
                align-items: center;
            `;

            botonEditar.addEventListener("click", () => {
                activarEdicion(index, botonEditar, csvData);
            });

            // Agregar botón Editar a la celda de acciones
            celdaAcciones.appendChild(botonEditar);
        }
    });
}

function activarEdicion(index, boton, csvData) {
    const fila = document.querySelectorAll("#movements-list tr")[index];
    const celdaDescripcion = fila.cells[4]; // Celda "Descripción del Movimiento"
    const celdaTipo = fila.cells[3]; // Celda "Tipo de Movimiento"

    // Guardar el valor original
    const valorOriginal = celdaDescripcion.textContent.trim();

    // Hacer la celda editable
    celdaDescripcion.contentEditable = true;
    celdaDescripcion.focus();

    // Cambiar el botón a modo "Guardar"
    boton.textContent = "Guardar";
    boton.classList.remove("btn-editar");
    boton.classList.add("btn-success");
    boton.disabled = true; // Deshabilitar el botón para evitar doble clic inmediato

    // Evento para guardar cambios
    function guardarEdicion() {
        const nuevaDescripcion = celdaDescripcion.textContent.trim();

        // Verificar si la fila ya está verificada
        const resultadoActual = fila.cells[6]?.textContent.trim();
        if (resultadoActual === "✅") {
            console.log("Fila ya verificada, no se necesita recomparar.");
            celdaDescripcion.contentEditable = false;
            boton.disabled = false; // Habilitar nuevamente el botón
            return;
        }

        // Si hay cambios en la descripción
        if (nuevaDescripcion !== valorOriginal) {
            movimientosProcesadosAPI[index].movimiento = extraerCodigoMovimiento(nuevaDescripcion);

            // Determinar el movimiento según la descripción
            let movementType = 0;
            if (nuevaDescripcion.startsWith("RET") || nuevaDescripcion.startsWith("R")) {
                movementType = 1; // RETIRO
            } else if (nuevaDescripcion.startsWith("COM")) {
                movementType = 1; // COMISIÓN
            }

            const nuevoTipo = determinarTipoMovimiento(movementType, nuevaDescripcion);
            movimientosProcesadosAPI[index].tipo = nuevoTipo;

            // Actualizar en la tabla
            celdaTipo.textContent = nuevoTipo;

            // Recomparar movimientos y actualizar la tabla
            compararMovimientos(csvData);
            detectarMovimientosAEditar(csvData);
        }

        // Restaurar el botón a modo "Editar" después de un breve retraso
        setTimeout(() => {
            celdaDescripcion.contentEditable = false;
            boton.textContent = "✏️";
            boton.classList.remove("btn-success");
            boton.classList.add("btn-editar");
            boton.disabled = false; // Habilitar el botón nuevamente

            // Restaurar estilos del botón después de la edición
            boton.style.cssText = `
                background: none;
                border: none;
                padding: 0;
                cursor: pointer;
                font-size: 16px;
                margin-left: 10px;
                display: inline-flex;
                align-items: center;
            `;
        }, 100); // Pequeño retraso de 100ms para evitar doble clic inmediato

        // Eliminar los eventos después de guardar
        celdaDescripcion.removeEventListener("keydown", enterGuardar);
        celdaDescripcion.removeEventListener("blur", guardarEdicion);
    }

    // Guardar al presionar Enter
    function enterGuardar(e) {
        if (e.key === "Enter") {
            e.preventDefault();
            guardarEdicion();
        }
    }

    celdaDescripcion.addEventListener("keydown", enterGuardar);
    celdaDescripcion.addEventListener("blur", guardarEdicion);
}

// Función auxiliar para extraer el código del movimiento
function extraerCodigoMovimiento(descripcion) {
    const partes = descripcion.split("-");
    const codigo = partes.pop()?.trim();
    return codigo && /^\d+$/.test(codigo) ? codigo : "N/A";
}

// Función auxiliar para determinar el tipo de movimiento
function determinarTipoMovimiento(movementType, movimientoDescripcion) {
    const descripcion = movimientoDescripcion.toUpperCase().trim();

    if (movementType === 0) {
        if (descripcion.startsWith("TB")) return "DEPÓSITO";
        if (descripcion.startsWith("CH")) return "CHEQUE";
        if (descripcion.startsWith("EF")) return "EFECTIVO";
        if (descripcion.startsWith("COM")) return "COMISIÓN";
        if (descripcion.startsWith("RET")) return "RETIRO";
    }

    if (movementType === 1) {
        if (descripcion.startsWith("COM")) return "COMISIÓN";
        if (descripcion.startsWith("R")) return "RETIRO";
        if (descripcion.startsWith("D")) return "DEVOLUCIÓN";
    }

    return "OTRO";
}
