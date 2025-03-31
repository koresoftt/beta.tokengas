$(function() {
    // -----------------------------------------------------
    // FUNCIONES AUXILIARES DE FORMATO Y CÁLCULO
    // -----------------------------------------------------
    function parseNumber(str) {
        if (!str) return 0;
        // Elimina todas las comas
        str = str.replace(/,/g, '');
        let num = parseFloat(str);
        return isNaN(num) ? 0 : num;
    }
    function formatCurrency(value) {
        if (isNaN(value)) value = 0;
        return value.toLocaleString('es-MX', {
            minimumFractionDigits: 2,
            maximumFractionDigits: 2
        });
    }
    function recalcSaldo() {
        let total = parseNumber($total.val());
        let sumMontos = 0;
        $('#tbodyContratos').find('.monto-input').each(function() {
            let $row = $(this).closest('tr');
            let $check = $row.find('.contratoCheck');
            if ($check.is(':checked')) {
                sumMontos += parseNumber($(this).val());
            }
        });
        let saldo = total - sumMontos;
        $saldo.text('Saldo: ' + formatCurrency(saldo));
        if (saldo < 0) {
            $mensajeAdvertencia.text('El saldo no puede ser menor a 0. El máximo es ' + formatCurrency(total) + '. Favor de revisar.');
        } else {
            $mensajeAdvertencia.text('');
        }
        return saldo;
    }

    // -----------------------------------------------------
    // REFERENCIAS A ELEMENTOS DEL DOM
    // -----------------------------------------------------
    const $compania           = $('#compania');
    const $total              = $('#total');
    const $listaContratos     = $('#listaContratos');
    const $companyCount       = $('#companyCount');
    const $saldo              = $('#saldo');
    const $mensajeAdvertencia = $('#mensajeAdvertencia');
    const $btnEnviar          = $('#btnEnviar');

    // -----------------------------------------------------
    // EVENTOS SOBRE EL CAMPO "TOTAL"
    // -----------------------------------------------------
    $total.on('blur', function() {
        let num = parseNumber($(this).val());
        $(this).val(formatCurrency(num));
    }).on('input', function() {
        recalcSaldo();
    });

    // -----------------------------------------------------
    // AUTOCOMPLETE PARA EL CAMPO "COMPAÑÍA"
    // -----------------------------------------------------
    $compania.autocomplete({
        minLength: 1,
        source: function(request, response) {
            clearTimeout(this.debounceTimeout);
            this.debounceTimeout = setTimeout(() => {
                $.ajax({
                    url: '?ajax=companies&term=' + encodeURIComponent(request.term),
                    dataType: 'json',
                    success: function(data) {
                        if (data.error) {
                            $companyCount.text(data.error);
                            response([]);
                            return;
                        }
                        $companyCount.text('Se encontraron ' + data.length + ' compañías.');
                        response($.map(data, function(item) {
                            return {
                                label: item.name,
                                value: item.name,
                                companyId: item.id
                            };
                        }));
                    },
                    error: function() {
                        $companyCount.text('Error en la petición.');
                        response([]);
                    }
                });
            }, 500);
        },
        select: function(event, ui) {
            $compania.val(ui.item.label);
            const companyId = ui.item.companyId;
            console.log("Seleccionaste la compañía ID:", companyId);
            $.ajax({
                url: '?ajax=companyContracts&companyId=' + encodeURIComponent(companyId),
                dataType: 'json',
                success: function(contratos) {
                    const $tbody = $('#tbodyContratos');
                    $tbody.empty();
                    if (!Array.isArray(contratos) || contratos.length === 0) {
                        $tbody.append(`
                            <tr>
                                <td colspan="5" class="text-center">
                                    No se encontraron contratos para esta compañía.
                                </td>
                            </tr>
                        `);
                        return;
                    }
                    contratos.forEach(function(contrato) {
                        let code = contrato.Code || contrato.Id || 'N/A';
                        let description = contrato.Description || contrato.Name || 'Sin nombre';
                        $tbody.append(`
                            <tr>
                                <td>
                                    <input type="checkbox" class="contratoCheck" value="${code}">
                                </td>
                                <td>${code}</td>
                                <td>${description}</td>
                                <td>
                                    <input type="text" class="monto-input" placeholder="Ingrese monto" style="width:100px;" disabled>
                                </td>
                                <td>
                                    <input type="checkbox" class="comisionCheck">
                                </td>
                            </tr>
                        `);
                    });
                    // Asociar eventos a los elementos creados
                    $tbody.find('.contratoCheck').on('change', function() {
                        const $row = $(this).closest('tr');
                        const $monto = $row.find('.monto-input');
                        if ($(this).is(':checked')) {
                            $monto.prop('disabled', false);
                        } else {
                            $monto.val('');
                            $monto.prop('disabled', true);
                        }
                        recalcSaldo();
                    });
                    $tbody.find('.monto-input').on('blur', function() {
                        let num = parseNumber($(this).val());
                        $(this).val(formatCurrency(num));
                    }).on('input', function() {
                        recalcSaldo();
                    });
                    $tbody.find('.comisionCheck').on('click', function() {
                        if ($(this).is(':checked')) {
                            $tbody.find('.comisionCheck').not(this).prop('checked', false);
                        }
                    });
                },
                error: function() {
                    const $tbody = $('#tbodyContratos');
                    $tbody.empty();
                    $tbody.append(`
                        <tr>
                            <td colspan="5" class="text-danger text-center">
                                Error al obtener los contratos.
                            </td>
                        </tr>
                    `);
                }
            });
            return false;
        },
        focus: function(event, ui) {
            event.preventDefault();
            $compania.val(ui.item.label);
        }
    });

    // -----------------------------------------------------
    // BOTÓN "ENVIAR"
    // -----------------------------------------------------
    $btnEnviar.on('click', function() {
        let errorContratoSinMonto = false;
        $('#tbodyContratos').find('tr').each(function() {
            const $row = $(this);
            const $check = $row.find('.contratoCheck');
            const $monto = $row.find('.monto-input');
            if ($check.is(':checked')) {
                let montoVal = parseNumber($monto.val());
                if (montoVal <= 0) {
                    errorContratoSinMonto = true;
                    return false;
                }
            }
        });
        if (errorContratoSinMonto) {
            alert('Error: Hay un contrato seleccionado sin monto válido. Por favor, ingresa un monto mayor a 0.');
            return;
        }
        let saldo = recalcSaldo();
        if (saldo < 0) {
            alert('El saldo no puede ser menor a 0. El máximo es ' + formatCurrency(parseNumber($total.val())) + '. Favor de revisar.');
            return;
        }
        if (saldo > 0) {
            alert('Aún queda saldo sin asignar. Debes asignar exactamente ' + formatCurrency(saldo) + ' más para llegar a 0.');
            return;
        }
        alert('Se aplicó Saldo correctamente.');
    });
});
