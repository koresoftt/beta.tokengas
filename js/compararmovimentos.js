let advertenciaMostrada = false; // Declaración global para evitar mostrar múltiples veces la advertencia
    
    let movimientosEstructurados = []; 
    
    
    // Función para actualizar el resultado en la tabla
    function actualizarResultadoEnTabla(movimiento, resultado) {
        const rows = document.querySelectorAll("#movements-list tr");
    
        rows.forEach((row) => {
            const cellMovimiento = row.cells[4]?.textContent || ""; // Asumiendo que el movimiento está en la columna 4
            if (cellMovimiento.includes(movimiento)) {
                const resultadoCell = row.cells[6]; // Asumiendo que el resultado está en la columna 6
                resultadoCell.textContent = resultado;
    
                // Aplicar estilos visuales
                if (resultado === "✅") resultadoCell.style.color = "green";
                else if (resultado === "⚠️") resultadoCell.style.color = "orange";
                else resultadoCell.style.color = "red";
            }
        });
    }
    
    // Función para mostrar el resumen final
    function mostrarResumenFinal(irregularidades, logs) {
        const mensaje = irregularidades === 0
            ? "Todos los movimientos cuadran correctamente ✅."
            : `Se encontraron ${irregularidades} irregularidades ⚠️.`;
    
        const resumenDiv = document.createElement("div");
        resumenDiv.style = `
            position: fixed;
            top: 30%;
            left: 50%;
            transform: translate(-50%, -50%);
            background-color: #333;
            padding: 20px;
            border: 2px solid #333;
            border-radius: 8px;
            z-index: 1000;
        `;
    
        resumenDiv.innerHTML = `
            <h3>Resumen de Comparación</h3>
            <p>${mensaje}</p>
            <button id="aceptarBtn">Aceptar</button>
            <button id="verLogsBtn">Ver Logs</button>
        `;
    
        document.body.appendChild(resumenDiv);
    
        // Botón Aceptar
        document.getElementById("aceptarBtn").addEventListener("click", () => {
            document.body.removeChild(resumenDiv);
        });
    
        // Botón Ver Logs
        document.getElementById("verLogsBtn").addEventListener("click", () => {
            const blob = new Blob([logs.join("\n")], { type: "text/plain" });
            const url = URL.createObjectURL(blob);
            const a = document.createElement("a");
            a.href = url;
            a.download = "logs_comparacion.txt";
            a.click();
            URL.revokeObjectURL(url);
        });
    }
    
    
    
  function compararMovimientos(csvData) {
    if (!Array.isArray(csvData) || csvData.length === 0) {
        console.error("Error: csvData no es un array válido o está vacío.");
        return;
    }

    movimientosEstructurados = [];
    let irregularidades = 0; // Contador de irregularidades
    let logs = []; // Almacena los logs para descarga

    csvData.forEach((movBanco) => {
        const movimientosCoincidentes = movimientosProcesadosAPI.filter(
            (movATIO) => movATIO.movimiento === movBanco.movimiento
        );

        if (movimientosCoincidentes.length === 0) {
            const movimientoLog = {
                movimiento: movBanco.movimiento,
                fecha: movBanco.fecha || "Fecha no proporcionada",
                saldoInicialBanco: movBanco.monto.toFixed(2),
                totalDepositosATIO: "0.00",
                totalRetirosATIO: "0.00",
                totalDevolucionesATIO: "0.00",
                totalComisionesATIO: "0.00",
                saldoTotalBanco: movBanco.monto.toFixed(2),
                saldoTotalATIO: "0.00",
                saldoFinalAjustado: movBanco.monto.toFixed(2),
                resultado: "⚠️"
            };

            movimientosEstructurados.push(movimientoLog);
            logs.push(`🔹 Movimiento: ${movBanco.movimiento}\n` +
                `  Fecha: ${movimientoLog.fecha}\n` +
                `  Saldo Inicial Banco: $${movimientoLog.saldoInicialBanco}\n` +
                `  Total Depósitos ATIO: $${movimientoLog.totalDepositosATIO}\n` +
                `  Total Retiros ATIO: $${movimientoLog.totalRetirosATIO}\n` +
                `  Total Devoluciones ATIO: $${movimientoLog.totalDevolucionesATIO}\n` +
                `  Total Comisiones ATIO: $${movimientoLog.totalComisionesATIO}\n` +
                `  Saldo Total Banco: $${movimientoLog.saldoTotalBanco}\n` +
                `  Saldo Total ATIO: $${movimientoLog.saldoTotalATIO}\n` +
                `  Saldo Final Ajustado: $${movimientoLog.saldoFinalAjustado}\n` +
                `  Resultado: ⚠️\n`
            );
            irregularidades++;
            actualizarResultadoEnTabla(movBanco.movimiento, "⚠️");
            return;
        }

        const contratosUnicos = [...new Set(movimientosCoincidentes.map((mov) => mov.contrato))];
        const esDepositoGlobal = contratosUnicos.length > 1;

        contratosUnicos.forEach((contrato) => {
            let totalDepositosATIO = 0;
            let totalRetirosATIO = 0;
            let totalDevolucionesATIO = 0;
            let totalComisionesATIO = 0;

            const movimientosContrato = movimientosCoincidentes.filter(
                (movATIO) => movATIO.contrato === contrato
            );

            movimientosContrato.forEach((movATIO) => {
    if (movATIO.tipo === "OTRO") {
        return; // Saltar este movimiento si es de tipo "OTRO"
    }

    if (["DEPÓSITO", "CHEQUE", "EFECTIVO"].includes(movATIO.tipo)) {
        totalDepositosATIO += movATIO.monto;
    } else if (movATIO.tipo === "RETIRO") {
        totalRetirosATIO += movATIO.monto;
    } else if (movATIO.tipo === "DEVOLUCIÓN") {
        totalDevolucionesATIO += movATIO.monto;
    } else if (movATIO.tipo === "COMISIÓN") {
        totalComisionesATIO += movATIO.monto;
    }
});
            let saldoInicialBanco = esDepositoGlobal ? totalDepositosATIO : movBanco.monto;
            const saldoTotalBanco = parseFloat((saldoInicialBanco + totalComisionesATIO).toFixed(2));
            const saldoTotalATIO = parseFloat(
                (
                    totalDepositosATIO -
                    Math.abs(totalRetirosATIO) +
                    Math.abs(totalDevolucionesATIO) -
                    Math.abs(totalComisionesATIO)
                ).toFixed(2)
            );
            const saldoFinalAjustado = parseFloat((saldoTotalBanco - saldoTotalATIO).toFixed(2));
            const resultado = Math.abs(saldoFinalAjustado) < 0.01 ? "✅" : "⚠️";

            const movimientoEstructurado = {
                movimiento: movBanco.movimiento,
                fecha: movBanco.fecha || "Fecha no proporcionada",
                contrato,
                compania: movimientosContrato[0]?.compania || "Sin compañía",
                saldoInicialBanco: saldoInicialBanco.toFixed(2),
                totalDepositosATIO: totalDepositosATIO.toFixed(2),
                totalRetirosATIO: totalRetirosATIO.toFixed(2),
                totalDevolucionesATIO: totalDevolucionesATIO.toFixed(2),
                totalComisionesATIO: totalComisionesATIO.toFixed(2),
                saldoTotalBanco: saldoTotalBanco.toFixed(2),
                saldoTotalATIO: saldoTotalATIO.toFixed(2),
                saldoFinalAjustado: saldoFinalAjustado.toFixed(2),
                resultado
            };

            movimientosEstructurados.push(movimientoEstructurado);
            logs.push(
                `🔹 Movimiento: ${movBanco.movimiento}\n` +
                `  Fecha: ${movimientoEstructurado.fecha}\n` +
                `  Contrato: ${contrato}\n` +
                `  Compañía: ${movimientoEstructurado.compania}\n` +
                `  Saldo Inicial Banco: $${movimientoEstructurado.saldoInicialBanco}\n` +
                `  Total Depósitos ATIO: $${movimientoEstructurado.totalDepositosATIO}\n` +
                `  Total Retiros ATIO: $${movimientoEstructurado.totalRetirosATIO}\n` +
                `  Total Devoluciones ATIO: $${movimientoEstructurado.totalDevolucionesATIO}\n` +
                `  Total Comisiones ATIO: $${movimientoEstructurado.totalComisionesATIO}\n` +
                `  Saldo Total Banco: $${movimientoEstructurado.saldoTotalBanco}\n` +
                `  Saldo Total ATIO: $${movimientoEstructurado.saldoTotalATIO}\n` +
                `  Saldo Final Ajustado: $${movimientoEstructurado.saldoFinalAjustado}\n` +
                `  Resultado: ${resultado}\n`
            );
            actualizarResultadoEnTabla(movBanco.movimiento, resultado);
        });
    });

    if (!advertenciaMostrada) {
        mostrarResumenFinal(irregularidades, logs);
        advertenciaMostrada = true;
    }

    detectarMovimientosAEditar(csvData);

    console.log("Movimientos estructurados:", movimientosEstructurados);

    // Actualizar el botón de Log con la cantidad de registros y habilitarlo
const logButton = document.getElementById("ver-logs-btn");
if (logButton) {
    logButton.innerHTML = `Log (${logs.length} registros)`;
    logButton.setAttribute("data-logs", logs.join("\n"));
    logButton.disabled = false;
}

    return movimientosEstructurados;
}

    