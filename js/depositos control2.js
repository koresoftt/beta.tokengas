$(function() {
  // ——— UTILIDADES ———
  const parseNumber = input => {
    if (typeof input === 'number') return input;
    return parseFloat((input || '').toString().replace(/,/g, '')) || 0;
  };

  const formatCurrency = value =>
    (isNaN(value) ? 0 : value).toLocaleString('es-MX', {
      minimumFractionDigits: 2,
      maximumFractionDigits: 2
    });

  // ——— REFERENCIAS AL DOM ———
  const $companyInput      = $('#compania');
  const $totalInput        = $('#total');
  const $contractsTbody    = $('#tbodyContratos');
  const $saldoDisplay      = $('#saldo');
  const $warningMessage    = $('#mensajeAdvertencia');
  const $sendButton        = $('#btnEnviar');
  const $commissionSwitch  = $('#pagoComisionSwitch');
  const $selectAll = $("#seleccionarTodos");


  // ——— RECALCULAR SALDO ———
  function recalcSaldo() {
    const total = parseNumber($totalInput.val());
    let sum = 0;
    $contractsTbody.find('.contratoCheck:checked').each(function() {
      sum += parseNumber($(this).closest('tr').find('.monto-input').val());
    });
    const saldo = total - sum;
    $saldoDisplay.text(`Saldo: ${formatCurrency(saldo)}`);
    $warningMessage.text(
      saldo < 0 ? `Saldo negativo. Máximo ${formatCurrency(total)}.` : ''
    );
    return saldo;
  }

  // ——— AUTOCOMPLETE COMPAÑÍA ———
  $companyInput.autocomplete({
    delay: 300,
    minLength: 1,
    source(request, response) {
      $.getJSON(`?ajax=companies&term=${encodeURIComponent(request.term)}`, data => {
        if (data.error) {
          response([{ label: data.error, value: '', error: true }]);
          return;
        }
        const items = data
          .slice(0, 10)
          .map(c => ({ label: c.name, value: c.name, companyId: c.id }));
        response(items.length ? items : [{ label: 'No se encontraron coincidencias.', value: '', error: true }]);
      });
    },
    select(event, ui) {
      $companyInput.val(ui.item.label).data('companyId', ui.item.companyId);
      if (parseNumber($totalInput.val()) > 0) {
        $.getJSON(`?ajax=companyContracts&companyId=${ui.item.companyId}`, renderContracts);
      }
      return false;
    }
  });

  // ——— FORMATO DE TOTAL ———
  $totalInput
    .attr({ type: 'text', inputmode: 'decimal' })
    .on('keypress', e => { if (!/[0-9.]|\b/.test(e.key)) e.preventDefault(); })
    .on('blur input', function(e) {
      let val = parseNumber($(this).val());
      if (val < 0) val = 0;
      if (e.type === 'blur') $(this).val(formatCurrency(val));
      recalcSaldo();
      const companyId = $companyInput.data('companyId');
      if (val > 0 && companyId) {
        $.getJSON(`?ajax=companyContracts&companyId=${companyId}`, renderContracts);
      }
    });

  // ——— RENDERIZAR CONTRATOS ———
  function renderContracts(contracts) {
    if (!Array.isArray(contracts) || contracts.length === 0) {
      $contractsTbody.html('<tr><td colspan="6" class="text-center">No se encontraron contratos.</td></tr>');
      return;
    }
    const rows = contracts.map(c => {
      const seg = (c.Code.split('-')[1] || '00').substring(0, 2);
      const requiresComm = parseInt(seg, 10) > 0;
      return `
        <tr data-company-code="${c.CompanyCode}" data-code="${c.Code}" data-requires-commission="${requiresComm}">
          <td><input type="checkbox" class="contratoCheck"></td>
          <td>${c.Code}</td>
          <td>${c.ContractDescription}</td>
          <td><input type="text" class="monto-input form-control form-control-sm" disabled></td>
          <td><input type="checkbox" class="comisionCheck" disabled></td>
          <td><input type="checkbox" class="incluyeComisionCheck" disabled title="Depósito incluye comisión e IVA"></td>
        </tr>`;
    }).join('');
    $contractsTbody.html(rows);
  }

  // ——— EVENTOS EN FILAS ———
  $contractsTbody
    .on('change', '.contratoCheck', function() {
      if ($commissionSwitch.is(':checked')) return;
      const $row = $(this).closest('tr');
      const requires = $row.data('requires-commission');
      const checked  = this.checked;
      const $amount  = $row.find('.monto-input');
      const $commChk = $row.find('.comisionCheck');
      const $inclChk = $row.find('.incluyeComisionCheck');

      if (checked) {
        $amount.prop('disabled', false).val('');
        if (requires) {
          $commChk.prop('disabled', false);
          $inclChk.prop('disabled', false);
        }
      } else {
        $amount.prop('disabled', true).val('');
        $commChk.prop('disabled', true).prop('checked', false);
        $inclChk.prop('disabled', true).prop('checked', false);
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
      $contractsTbody.find('.comisionCheck').not(this).prop('checked', false);
      $contractsTbody.find('.incluyeComisionCheck').prop('checked', false);
    })
    .on('change', '.incluyeComisionCheck', function() {
      const $row = $(this).closest('tr');
      $contractsTbody.find('.incluyeComisionCheck').not(this).prop('checked', false);
      $contractsTbody.find('.comisionCheck').not($row.find('.comisionCheck')).prop('checked', false);
      if (this.checked) $row.find('.comisionCheck').prop('checked', true);
    });

  // ——— “Seleccionar todos” ———
  $selectAll.on('change', function() {
    if ($commissionSwitch.is(':checked')) {
      $(this).prop('checked', false);
      return;
    }
    const chk = this.checked;
    $contractsTbody.find('.contratoCheck').each(function() {
      $(this).prop('checked', chk).trigger('change');
    });
    recalcSaldo();
  });

  // ——— SWITCH PAGO DE COMISIÓN ———
  $commissionSwitch.on('change', function() {
    const isPago = this.checked;
    if (isPago) {
      Swal.fire({
        icon: 'info',
        title: 'Pago de Comisión',
        text: `El total ($${formatCurrency(parseNumber($totalInput.val()))}) será tomado como comisión.`
      });
      // deshabilitar todo y forzar única selección
      $contractsTbody.find('.comisionCheck, .incluyeComisionCheck').prop('checked', false).prop('disabled', true);
      $contractsTbody.find('.monto-input').prop('disabled', true).val('');
      $contractsTbody.off('change.single').on('change.single', '.contratoCheck', function() {
        $contractsTbody.find('tr').each(function() {
          $(this)
            .find('.contratoCheck').prop('checked', false)
            .end()
            .find('.monto-input').prop('disabled', true).val('');
        });
        $(this).prop('checked', true);
        const $r = $(this).closest('tr');
        $r.find('.monto-input').prop('disabled', false).focus();
        recalcSaldo();
      });
      $selectAll.prop('checked', false).prop('disabled', true);
    } else {
      // restaurar comportamiento normal
      $contractsTbody.off('change.single');
      $contractsTbody.find('tr').each(function() {
        const $r = $(this);
        const req = $r.data('requires-commission');
        const sel = $r.find('.contratoCheck').is(':checked');
        $r.find('.monto-input').prop('disabled', !sel);
        $r.find('.comisionCheck, .incluyeComisionCheck').prop('checked', false).prop('disabled', !req);
      });
      $selectAll.prop('disabled', false);
    }
  });

  // ——— FUNCIONES AJAX ———
  const sendPayload = payload =>
    $.ajax({
      url: '../php/post/postdepositos.php',
      method: 'POST',
      contentType: 'application/json',
      data: JSON.stringify(payload)
    }).then(
      data => data,
      xhr => ((xhr.responseText || '').includes('Operation Succeeded'))
        ? $.Deferred().resolve().promise()
        : $.Deferred().reject().promise()
    );

  // ——— EJECUTAR PETICIONES ———
  function executeRequests(description) {
    $sendButton.prop("disabled", true);
    const totalGlobal = parseNumber($totalInput.val());
    const calls = [];
    const pagoActivo = $commissionSwitch.is(":checked");
    const $selectedRows = $contractsTbody.find(".contratoCheck:checked").closest("tr");
    let totalDepositosParaComision = 0;
    let calcularComisionSobreTotal = false;
    let codigoBaseComision = null; // Código del contrato para calcular la comisión (si aplica)
  
    // — Pago de Comisión: 2 POSTs con el total —
    if (pagoActivo) {
      if ($selectedRows.length !== 1) {
        $sendButton.prop("disabled", false);
        return Swal.fire("Error", "Para Pago de Comisión, selecciona un solo contrato", "warning");
      }
  
      const $row = $selectedRows.first();
      const code = $row.attr("data-code");
      const companyCode = $row.attr("data-company-code");
      if (!code || !companyCode) {
        $sendButton.prop("disabled", false);
        return Swal.fire("Error", "No se pudo leer el contrato. Refresca la lista.", "error");
      }
  
      $row.find('.monto-input').val(formatCurrency(totalGlobal));
  
      calls.push(sendPayload({
        SubscriberCode: "2F4",
        ActionCode: 906,
        CompanyCode: companyCode,
        ContractCode: code,
        Amount: totalGlobal,
        CurrencyCode: "MXN",
        Description: `TB-${description}`
      }));
  
      calls.push(sendPayload({
        SubscriberCode: "2F4",
        ActionCode: 907,
        CompanyCode: companyCode,
        ContractCode: code,
        Amount: totalGlobal,
        CurrencyCode: "MXN",
        Description: `COMTB-${description}`
      }));
  
      return $.when(...calls)
        .then(() => Swal.fire("Éxito", "Pago de comisión registrado", "success").then(resetForm))
        .fail(() => Swal.fire("Error", "Falló el registro", "error"))
        .always(() => $sendButton.prop("disabled", false));
    }
  
  
    // — Incluye Comisión + IVA —
    const $incl = $contractsTbody.find(".incluyeComisionCheck:checked").closest("tr");
    if ($incl.length) {
      // Aquí la lógica de "Incluye Comisión + IVA" parece calcular la comisión individualmente.
      // Si la nueva lógica de comisión sobre el total también debe aplicar aquí, necesitarías revisarla.
      const code = $incl.attr("data-code");
      const companyCode = $incl.attr("data-company-code");
      const montoTotal = parseNumber($incl.find(".monto-input").val());
      const segment = (code.split("-")[1] || "00").substring(0, 2);
      const pct = parseInt(segment, 10) / 10 / 100;
      const factor = 1 + pct * 1.16;
      const base = montoTotal / factor;
      const comision = parseFloat((montoTotal - base).toFixed(2));
      calls.push(sendPayload({
        SubscriberCode: "2F4", ActionCode: 906,
        CompanyCode: companyCode, ContractCode: code,
        Amount: montoTotal, CurrencyCode: "MXN",
        Description: `TB-${description}`
      }));
      calls.push(sendPayload({
        SubscriberCode: "2F4", ActionCode: 907,
        CompanyCode: companyCode, ContractCode: code,
        Amount: comision, CurrencyCode: "MXN",
        Description: `COM${description}`
      }));
      return $.when(...calls)
        .then(() => Swal.fire("Éxito", "Depósito y comisión registrados", "success").then(resetForm))
        .fail(() => Swal.fire("Error", "Falló el registro", "error"))
        .always(() => $sendButton.prop("disabled", false));
    }
  
    // — Caso normal: múltiples contratos —
    $selectedRows.each(function () {
      const $r = $(this);
      const monto = parseNumber($r.find(".monto-input").val());
      const code = $r.attr("data-code");
      const companyCode = $r.attr("data-company-code");
  
      if (monto) {
        totalDepositosParaComision += monto; // Acumular el total de los depósitos
  
        calls.push(sendPayload({
          SubscriberCode: "2F4", ActionCode: 906,
          CompanyCode: companyCode, ContractCode: code,
          Amount: monto, CurrencyCode: "MXN",
          Description: `TB-${description}`
        }));
  
        if ($r.find(".comisionCheck").is(":checked") && !calcularComisionSobreTotal) {
          // Si la casilla de comisión está marcada en algún contrato,
          // activamos la bandera para calcular la comisión sobre el total
          calcularComisionSobreTotal = true;
          codigoBaseComision = code; // Usamos el código del primer contrato con comisión marcada como base
        }
      }
    });
  
    // Calcular y registrar la comisión SOBRE EL TOTAL de los depósitos (si se activó la bandera)
    if (calcularComisionSobreTotal && totalDepositosParaComision > 0 && codigoBaseComision) {
      const segment = (codigoBaseComision.split("-")[1] || "00").substring(0, 2);
      const pct = parseInt(segment, 10) / 10 / 100;
      const baseComisionTotal = totalDepositosParaComision * pct;
      const ivaComisionTotal = baseComisionTotal * 0.16;
      const comisionTotal = parseFloat((baseComisionTotal + ivaComisionTotal).toFixed(2));
  
      if (comisionTotal > 0) {
        // Asumimos que la comisión se asocia al primer contrato seleccionado para simplificar
        const primerContrato = $selectedRows.first();
        calls.push(sendPayload({
          SubscriberCode: "2F4",
          ActionCode: 907,
          CompanyCode: primerContrato.attr("data-company-code"),
          ContractCode: primerContrato.attr("data-code"),
          Amount: comisionTotal,
          CurrencyCode: "MXN",
          Description: `COM${description}-TOTAL`
        }));
      }
    }
  
    return $.when(...calls)
      .then(() => Swal.fire("Éxito", "Depósitos registrados", "success").then(resetForm))
      .fail(() => Swal.fire("Error", "Falló el registro", "error"))
      .always(() => $sendButton.prop("disabled", false));
  }


  // ——— RESET FORMULARIO ———
  function resetForm() {
    $commissionSwitch.prop('checked', false).trigger('change');
    $companyInput.val('').removeData('companyId');
    $totalInput.val('');
    
    $('#tbExtra').val('');
    const now = new Date();
    $('#tbFecha').val(
      String(now.getDate()).padStart(2,'0') +
      String(now.getMonth()+1).padStart(2,'0') +
      now.getFullYear()
    );
    $contractsTbody.empty();
    recalcSaldo();
  }

  // ——— BOTÓN ENVIAR ———
  $sendButton.on('click', function(e) {
    e.preventDefault();
    const totalVal   = parseNumber($totalInput.val());
    const anyChecked = $contractsTbody.find('.contratoCheck:checked').length>0;
    const extraEmpty = !$('#tbExtra').val().trim();
    const desc       = `${$('#tbFecha').val()}-${$('#tbExtra').val()}`;

    if (!$companyInput.val().trim()) {
      return Swal.fire('Error','Selecciona una compañía','warning');
    }
    if (totalVal <= 0) {
      return Swal.fire('Error','Total debe ser mayor a 0','warning');
    }
    if (!anyChecked) {
      return Swal.fire('Error','Selecciona al menos un contrato','warning');
    }
    if (recalcSaldo() !== 0) {
      return Swal.fire('Error',`Falta asignar (${formatCurrency(recalcSaldo())})`,'warning');
    }

    // validar número vacío
    if (extraEmpty) {
      return Swal.fire({
        icon:'warning',
        title:'Número de movimiento vacío',
        text:'¿Continuar sin él?',
        showCancelButton:true,
        confirmButtonText:'Sí, continuar',
        cancelButtonText:'No, cancelar'
      }).then(r=>{ if(r.isConfirmed) confirmCommissionAndRun(); });
    }
    confirmCommissionAndRun();

    function confirmCommissionAndRun() {
      // si no es pago de comisión, hay contratos con comisión y no marcó checkbox
      const needsComm = $contractsTbody.find('tr')
        .filter((_,r)=>$(r).data('requires-commission') && $(r).find('.contratoCheck').is(':checked')).length>0;
      const markedComm = $contractsTbody.find('.comisionCheck:checked').length>0;
      if (!$commissionSwitch.is(':checked') && needsComm && !markedComm) {
        return Swal.fire({
          icon:'warning',
          title:'Falta aplicar comisión',
          text:'¿Continuar sin incluirla?',
          showCancelButton:true,
          confirmButtonText:'Sí, continuar',
          cancelButtonText:'No, revisar'
        }).then(r=>{ if(r.isConfirmed) executeRequests(desc); });
      }
      executeRequests(desc);
    }
  });
});
