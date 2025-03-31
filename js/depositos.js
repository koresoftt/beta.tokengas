$(function() {
    // -----------------------------------------------------
    // FUNCIONES AUXILIARES
    // -----------------------------------------------------
    function parseNumber(input) {
        if (typeof input === 'number') return input;
        if (!input) return 0;
        return parseFloat(input.toString().replace(/,/g, '')) || 0;
    }

    function formatCurrency(value) {
        return (isNaN(value) ? 0 : value).toLocaleString('es-MX', {
            minimumFractionDigits: 2,
            maximumFractionDigits: 2
        });
    }

    function recalcSaldo() {
        const total = parseNumber($total.val());
        let sum = 0;
        $tbody.find('.contratoCheck:checked').each(function() {
            sum += parseNumber($(this).closest('tr').find('.monto-input').val());
        });
        const saldo = total - sum;
        $saldo.text('Saldo: ' + formatCurrency(saldo));
        $mensajeAdvertencia.text(
            saldo < 0 ? `Saldo negativo. Máximo ${formatCurrency(total)}.` : ''
        );
        return saldo;
    }

    // -----------------------------------------------------
    // REFERENCIAS DOM
    // -----------------------------------------------------
    const $compania           = $('#compania');
    const $total              = $('#total');
    const $tbody              = $('#tbodyContratos');
    const $saldo              = $('#saldo');
    const $mensajeAdvertencia = $('#mensajeAdvertencia');
    const $btnEnviar          = $('#btnEnviar');

    // -----------------------------------------------------
    // VALIDAR NÚMEROS EN EL TOTAL
    // -----------------------------------------------------
    $total
      .attr({type:'text', inputmode:'decimal'})
      .on('keypress', e => {
          // Solo dígitos y punto decimal
          if (!/[0-9.]|\b/.test(e.key)) {
              e.preventDefault();
          }
      })
      .on('blur input', function(e) {
          let val = parseNumber($(this).val());
          if (val < 0) val = 0;
          if (e.type === 'blur') {
              $(this).val(formatCurrency(val));
          }
          recalcSaldo();
          // Si ya tienes companyId, puedes recargar contratos aquí (opcional)
          if (val > 0 && $compania.data('companyId')) {
              $.getJSON(`?ajax=companyContracts&companyId=${$compania.data('companyId')}`, renderContratos);
          }
      });

    // -----------------------------------------------------
    // AUTOCOMPLETE COMPAÑÍA
    // -----------------------------------------------------
    $compania.autocomplete({
        minLength: 1,
        source(request, response) {
            clearTimeout(this.debounceTimeout);
            this.debounceTimeout = setTimeout(() => {
                $.getJSON(`?ajax=companies&term=${encodeURIComponent(request.term)}`, data => {
                    if (data.error) return response([]);
                    response(data.map(c => ({
                        label: c.name,
                        value: c.name,
                        companyId: c.id
                    })));
                });
            }, 300);
        },
        select(_, ui) {
            $compania.val(ui.item.label).data('companyId', ui.item.companyId);
            // Si total > 0, cargar contratos
            if (parseNumber($total.val()) > 0) {
                $.getJSON(`?ajax=companyContracts&companyId=${ui.item.companyId}`, renderContratos);
            }
            return false;
        }
    });

    // -----------------------------------------------------
    // RENDERIZAR CONTRATOS
    // -----------------------------------------------------
    function renderContratos(contratos) {
        $tbody.empty();
        if (!Array.isArray(contratos) || contratos.length === 0) {
            return $tbody.append('<tr><td colspan="5" class="text-center">No se encontraron contratos.</td></tr>');
        }
        contratos.forEach(c => {
            // Revisar 2 dígitos del 2do segmento
            const segment = (c.Code.split('-')[1]||'00').substring(0,2);
            const requiresComm = parseInt(segment,10) > 0;
            $tbody.append(`
                <tr data-company-code="${c.CompanyCode}" data-code="${c.Code}" data-requires-commission="${requiresComm}">
                    <td><input type="checkbox" class="contratoCheck"></td>
                    <td>${c.Code}</td>
                    <td>${c.ContractDescription}</td>
                    <td><input type="text" class="monto-input form-control form-control-sm" disabled></td>
                    <td><input type="checkbox" class="comisionCheck" disabled></td>
                </tr>
            `);
        });
    }

    // -----------------------------------------------------
    // EVENTOS SOBRE FILAS
    // -----------------------------------------------------
    $tbody.on('change', '.contratoCheck', function() {
        const $row = $(this).closest('tr');
        const $monto = $row.find('.monto-input');
        const $comm = $row.find('.comisionCheck');
        const requiresComm = $row.data('requiresCommission');

        if (this.checked) {
            $monto.prop('disabled', false).val('');
            if (requiresComm) {
                $comm.prop('disabled', false);
            }
        } else {
            $monto.prop('disabled', true).val('');
            $comm.prop('disabled', true).prop('checked', false);
        }
        recalcSaldo();
    })
    .on('dblclick', '.monto-input', function() {
        $(this).val(formatCurrency(recalcSaldo()));
        recalcSaldo();
    })
    .on('input blur', '.monto-input', function(e) {
        if (e.type === 'blur') {
            $(this).val(formatCurrency(parseNumber($(this).val())));
        }
        recalcSaldo();
    })
    .on('click', '.comisionCheck', function() {
        // Solo una comisionCheck a la vez
        $tbody.find('.comisionCheck').not(this).prop('checked', false);
    });

    // -----------------------------------------------------
    // AJAX HELPER
    // -----------------------------------------------------
    function sendPayload(payload) {
        return $.ajax({
            url: '/tokengas/php/post/depositos-beta.php',
            method: 'POST',
            contentType: 'application/json',
            data: JSON.stringify(payload)
        }).then(
            data => data,
            xhr => ((xhr.responseText || '').includes('Operation Succeeded'))
                ? $.Deferred().resolve().promise()
                : $.Deferred().reject().promise()
        );
    }

    // -----------------------------------------------------
    // ENVIAR (PETICIONES)
    // -----------------------------------------------------
    function executeRequests(desc) {
        $btnEnviar.prop('disabled', true);
        const calls = [];
        $tbody.find('tr').each(function() {
            const $row = $(this);
            if (!$row.find('.contratoCheck').is(':checked')) return;

            const amount = parseNumber($row.find('.monto-input').val());
            const code = $row.data('code');
            const depositPayload = {
                SubscriberCode: '2F4',
                ActionCode: 906,
                CompanyCode: $row.data('companyCode'),
                ContractCode: code,
                Amount: amount,
                CurrencyCode: 'MXN',
                Description: desc
            };
            calls.push(sendPayload(depositPayload));

            // Si la comisiónCheck está marcada
            if ($row.find('.comisionCheck').is(':checked')) {
                const pct = parseInt((code.split('-')[1]||'00').substring(0,2),10)/10;
                const commissionAmount = parseNumber($total.val())*(pct/100)*1.16;
                calls.push(sendPayload({
                    ...depositPayload,
                    ActionCode: 907,
                    Amount: commissionAmount,
                    Description: `COM${desc}`
                }));
            }
        });
        $.when.apply($, calls)
            .then(() => {
                alert('Depósito exitoso');
                resetForm();
            })
            .catch(() => alert('Error al procesar'))
            .always(() => $btnEnviar.prop('disabled', false));
    }

    // -----------------------------------------------------
    // CLICK EN ENVIAR → VALIDACIONES
    // -----------------------------------------------------
    $btnEnviar.on('click', function() {
        const description = `TB-${$('#tbFecha').val()}-${$('#tbExtra').val()}`;
        const totalVal = parseNumber($total.val());
        const saldoRest = recalcSaldo();
        const anySelected = $tbody.find('.contratoCheck:checked').length > 0;
        const extraEmpty = !$('#tbExtra').val().trim();

        // 1) Compañía
        if (!$compania.val().trim()) {
            return alert('Selecciona una compañía');
        }
        // 2) Total > 0
        if (totalVal <= 0) {
            return alert('Total debe ser mayor a 0');
        }
        // 3) Al menos un contrato
        if (!anySelected) {
            return alert('Selecciona al menos un contrato');
        }
        // 4) Saldo == 0
        if (saldoRest !== 0) {
            return alert(`Saldo no es 0 (${formatCurrency(saldoRest)}). Corrige antes de continuar.`);
        }
        // (OPCIONAL) tbExtra vacío
        if (extraEmpty) {
            if (!confirm('Número de movimiento vacío. ¿Continuar sin él?')) {
                return;
            }
        }
        // (OPCIONAL) Comisión
        checkCommission(description);
    });

    function checkCommission(description) {
        // Revisa: si hay al menos un contrato que requiere comisión marcado,
        // se necesita que al menos un comisionCheck esté marcado.
        const anyRequires = $tbody.find('tr').filter(function(){
            return $(this).data('requiresCommission') && $(this).find('.contratoCheck').is(':checked');
        }).length > 0;

        const anyCommChecked = $tbody.find('.comisionCheck:checked').length > 0;

        // Si existe un contrato que requiere comisión, pero no se marcó ninguna,
        // se pregunta: “¿Continuar sin ella?”
        if (anyRequires && !anyCommChecked) {
            if (!confirm('Falta aplicar comisión en contratos que la requieren. ¿Continuar sin ella?')) {
                return;
            }
        }
        executeRequests(description);
    }

    // -----------------------------------------------------
    // RESET FORM
    // -----------------------------------------------------
    function resetForm() {
        $compania.val('').removeData('companyId');
        $total.val('');
        $('#tbExtra').val('');
        // Generar fecha actual en formato ddmmyyyy
        const hoy = new Date();
        $('#tbFecha').val(
            String(hoy.getDate()).padStart(2,'0') +
            String(hoy.getMonth()+1).padStart(2,'0') +
            hoy.getFullYear()
        );
        $tbody.empty();
        recalcSaldo();
    }
});
