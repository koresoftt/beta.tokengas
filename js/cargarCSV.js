let csvData = []; // Definición global para almacenar datos CSV procesados

function uploadCSV(event) {
    const file = event.target.files[0]; // Obtiene el archivo seleccionado
    if (file) {
        const reader = new FileReader();

        reader.onload = function (e) {
            const csvContent = e.target.result;
            const csvLines = csvContent.split("\n").filter(line => line.trim() !== ""); // Filtra líneas vacías
            let header = parseCSVLine(csvLines[0]);

            // Limpiar encabezados
            header = header.map(col => col.replace(/^"|"$/g, '').trim());
   //         console.log("Encabezados encontrados (limpios):", header);

            // Buscar índices dinámicamente
            const indexFechaOperacion = header.findIndex(col => col.toUpperCase() === "FECHA DE OPERACIÓN");
            const indexFecha = header.findIndex(col => col.toUpperCase() === "FECHA");
            const indexDepositos = header.findIndex(col => col.toUpperCase().includes("DEPÓSITO") || col.toUpperCase().includes("MONTO"));
            const indexMovimiento = header.findIndex(col => col.toUpperCase().includes("MOVIMIENTO"));

            if (indexFecha === -1 || indexDepositos === -1 || indexMovimiento === -1) {
                console.error("Columnas requeridas no encontradas. Revisa el formato del CSV.");
                alert("Error: El archivo CSV no contiene las columnas requeridas (FECHA, DEPÓSITOS/MONTO, MOVIMIENTO).");
                return;
            }

            // Procesar las líneas del CSV
            csvData = csvLines.slice(1).map(line => {
                const columns = parseCSVLine(line);

                // Ajustar para usar la columna correcta de FECHA
                const fechaRaw = columns[indexFecha]?.replace(/^"|"$/g, '').trim(); // Usar columna "FECHA"
                const depositosRaw = columns[indexDepositos]?.replace(/[\$,"]/g, '').trim() || "0";
                const movimientoRaw = columns[indexMovimiento]?.replace(/^"|"$/g, '').trim();

                const monto = parseFloat(depositosRaw) || 0;
                const movimientoFinal = extraerUltimosDigitos(movimientoRaw);
                const fecha = convertirFechaDDMMYYYY(fechaRaw);

                if (monto > 0 && movimientoFinal) {
                    return {
                        fecha: fecha,
                        monto: monto,
                        movimiento: movimientoFinal
                    };
                } else {
                   // console.warn("Fila ignorada (sin monto o movimiento válido):", columns);
                    return null;
                }
            }).filter(item => item !== null);

            console.log("Datos CSV procesados:", csvData);

            // Llamar a la función compararMovimientos (de otro archivo)
            if (typeof compararMovimientos === "function") {
                try {
                    compararMovimientos(csvData);
                } catch (error) {
                    console.error("Error en la función 'compararMovimientos':", error);
                }
            } else {
                console.error("Error: La función 'compararMovimientos' no está definida.");
            }
        };

        reader.onerror = function () {
            console.error("Error al leer el archivo CSV.");
            alert("Hubo un error al leer el archivo CSV.");
        };

        reader.readAsText(file); // Lee el archivo CSV como texto
    } else {
        alert("Por favor selecciona un archivo CSV.");
    }
}



// Función para parsear una línea CSV
function parseCSVLine(line) {
    const match = line.match(/(".*?"|[^",\s]+)(?=\s*,|\s*$)/g);
    return match ? match.map(col => col.trim()) : [];
}

// Convertir fecha a formato DD/MM/YYYY
function convertirFechaDDMMYYYY(fecha) {
    if (!fecha || fecha === "N/A") return fecha;
    const partes = fecha.split(/[-\/]/);
    if (partes.length !== 3) return "N/A";

    if (partes[0].length === 4) {
        // Formato YYYY/MM/DD -> Convertir a DD/MM/YYYY
        return `${partes[2]}/${partes[1]}/${partes[0]}`;
    } else {
        return `${partes[0]}/${partes[1]}/${partes[2]}`;
    }
}

// Extraer últimos números de una cadena (movimiento)
function extraerUltimosDigitos(movimiento) {
    if (!movimiento) return null; // Manejo seguro de valores vacíos o nulos
    const match = movimiento.match(/(\d+)$/);
    return match ? match[0] : null;
}