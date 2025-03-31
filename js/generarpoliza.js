function generarPolizaDesdeTabla() {
    if (!Array.isArray(movimientosEstructurados) || movimientosEstructurados.length === 0) {
        console.error("movimientosEstructurados no contiene datos válidos o no está definido.");
        alert("No hay datos disponibles para generar la póliza.");
        return;
    }

    const data = []; // Datos para la hoja de cálculo
    const agrupadosPorFecha = {};

    // Agrupar movimientos por fecha y por número de movimiento
    movimientosEstructurados
        .filter((mov) => mov.resultado === "✅")
        .forEach((mov) => {
            if (!agrupadosPorFecha[mov.fecha]) {
                agrupadosPorFecha[mov.fecha] = {};
            }
            if (!agrupadosPorFecha[mov.fecha][mov.movimiento]) {
                agrupadosPorFecha[mov.fecha][mov.movimiento] = [];
            }
            agrupadosPorFecha[mov.fecha][mov.movimiento].push(mov);
        });

    // Crear pólizas por fecha
    Object.keys(agrupadosPorFecha).forEach((fecha) => {
        const movimientosPorMovimiento = agrupadosPorFecha[fecha];
        const folio = fecha.split("/")[0]; // Folio basado en el día del mes
        const fechaYYYYMMDD = fecha.split("/").reverse().join(""); // Convierte DD/MM/YYYY a YYYYMMDD

        // Encabezado de la póliza
        data.push([
            "P",
            fechaYYYYMMDD, // Fecha en formato YYYYMMDD
            "1", // Código fijo
            folio, // Día como folio
            "1", // Código fijo
            "0", // Código fijo
            `Movimientos del ${fecha}`,
            "11", // Código fijo
            "0", // Código fijo
            "0", // Código fijo
        ]);

        // Procesar cada grupo de movimientos con el mismo número de movimiento
        Object.keys(movimientosPorMovimiento).forEach((movimiento) => {
            const movimientos = movimientosPorMovimiento[movimiento];

            // 🔹 **Tomar el saldo del banco desde `saldoInicialBanco`**
            let totalSaldoBanco = movimientos.reduce((sum, mov) => sum + parseFloat(mov.saldoInicialBanco || 0), 0);
            let totalComisiones = movimientos.reduce((sum, mov) => sum + Math.abs(parseFloat(mov.totalComisionesATIO || 0)), 0);

            // 🔹 **Si hay varios movimientos con el mismo número de movimiento, agrupar el depósito**
            if (movimientos.length > 1) {
                data.push([
                    "M1",
                    "111210000000001", // Cuenta contable del banco
                    "",
                    "0", // Cargo
                    totalSaldoBanco.toFixed(2), // Monto total agrupado
                    "0",
                    "0",
                    `Banorte ${movimientos[0].compania}`.slice(0, 100).padEnd(100, " "),
                    "1",
                    "",
                    fecha.replace(/\//g, "") // Fecha en formato YYYYMMDD
                ]);
            } else {
                // Si solo hay un movimiento, registrar el depósito individual
                data.push([
                    "M1",
                    "111210000000001", // Cuenta contable del banco
                    "",
                    "0", // Cargo
                    parseFloat(movimientos[0].saldoInicialBanco).toFixed(2), // Monto bancario individual
                    "0",
                    "0",
                    `Banorte ${movimientos[0].compania}`.slice(0, 100).padEnd(100, " "),
                    "1",
                    "",
                    fecha.replace(/\//g, "") // Fecha en formato YYYYMMDD
                ]);
            }

            // 🔹 **Registrar los movimientos ATIO individuales (SIN RESTAR COMISIÓN)**
            movimientos.forEach((mov) => {
                data.push([
                    "M1",
                    mov.contrato.replace(/-/g, "").padEnd(30, " "), // Contrato sin guiones
                    "",
                    "1", // Abono
                    parseFloat(mov.saldoTotalATIO).toFixed(2), // Se usa `saldoTotalATIO` tal cual
                    "0",
                    "0",
                    `${mov.compania}`.slice(0, 100).padEnd(100, " "),
                    "1",
                    "",
                    fecha.replace(/\//g, "") // Fecha en formato YYYYMMDD
                ]);
            });

            // 🔹 **Registrar la comisión separada**
            if (totalComisiones > 0) {
                data.push([
                    "M1",
                    movimientos[0].contrato.replace(/-/g, "").padEnd(30, " "),
                    "",
                    "1", // Abono
                    totalComisiones.toFixed(2), // Monto de la comisión
                    "0",
                    "0",
                    `COMISIÓN ${movimientos[0].compania}`.slice(0, 100).padEnd(100, " "),
                    "1",
                    "",
                    fecha.replace(/\//g, "") // Fecha en formato YYYYMMDD
                ]);

                // 🔹 **Registrar el IVA de la comisión**
                const ivaMonto = totalComisiones - totalComisiones / 1.16; // IVA calculado

                // IVA no cobrado
                data.push([
                    "M1",
                    "213100010000000", // Código para IVA no cobrado
                    "",
                    "0", // Cargo
                    ivaMonto.toFixed(2), // Monto del IVA no cobrado
                    "0",
                    "0",
                    `IVA Trasladado no Cobrado ${movimientos[0].compania}`.slice(0, 100).padEnd(100, " "),
                    "1",
                    "",
                    fecha.replace(/\//g, "") // Fecha en formato YYYYMMDD
                ]);

                // IVA cobrado
                data.push([
                    "M1",
                    "213000010000000", // Código para IVA cobrado
                    "",
                    "1", // Abono
                    ivaMonto.toFixed(2), // Monto del IVA cobrado
                    "0",
                    "0",
                    `IVA Trasladado Cobrado ${movimientos[0].compania}`.slice(0, 100).padEnd(100, " "),
                    "1",
                    "",
                    fecha.replace(/\//g, "") // Fecha en formato YYYYMMDD
                ]);
            }
        });
    });

    // Generar archivo Excel
    const wb = XLSX.utils.book_new();
    const ws = XLSX.utils.aoa_to_sheet(data);
    XLSX.utils.book_append_sheet(wb, ws, "Pólizas");

    const mesFormateado = mesSeleccionado.padStart(2, "0"); // Asegura dos dígitos
    const nombreArchivo = `Poliza_${anioSeleccionado}_${mesFormateado}.xls`;
    XLSX.writeFile(wb, nombreArchivo);
    alert(`Póliza generada exitosamente como '${nombreArchivo}'.`);
}
