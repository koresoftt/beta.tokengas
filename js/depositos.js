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

    // ——— COMISIONES ESPECIALES POR CONTRATO ———
const contratosConComisionEspecial = {
  '1122-0421-0000159': 0.0042, // 0.42%
  '1122-0421-0000160': 0.0042, // 0.42%
  '1122-0421-0000161': 0.0042, // 0.42%
  '1122-0421-0000162': 0.0042, // 0.42%

  
};

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
  .on('input blur paste', function(e) {
    let raw = $(this).val();

    if (e.type === 'paste') {
      e.preventDefault();
      raw = (e.originalEvent || e).clipboardData.getData('text/plain');
    }

    // Limpiar: solo números y un punto decimal
    let cleaned = raw.replace(/[^0-9.]/g, '');
    const parts = cleaned.split('.');
    if (parts.length > 2) {
      cleaned = parts[0] + '.' + parts[1];
    }

    let val = parseNumber(cleaned);
    if (val < 0) val = 0;

    if (e.type === 'blur' || e.type === 'paste') {
      $(this).val(formatCurrency(val));
    } else {
      $(this).val(cleaned);
    }

    recalcSaldo();

    const companyId = $companyInput.data('companyId');
    if (val > 0 && companyId) {
      $.getJSON(`?ajax=companyContracts&companyId=${companyId}`, renderContracts);
    }
  });

let contratosCargados = [];

  // ——— RENDERIZAR CONTRATOS ———
  function renderContracts(contracts) {
    contratosCargados = contracts; 
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
          <td>${c.Description}</td>
<td><input type="text" class="monto-input form-control form-control-sm" disabled inputmode="decimal" pattern="[0-9]*" /></td>
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
    .on('input blur paste', '.monto-input', function(e) {
      let raw = $(this).val();
    
      // Si es evento de pegado, usa el valor del portapapeles
      if (e.type === 'paste') {
        e.preventDefault();
        const paste = (e.originalEvent || e).clipboardData.getData('text/plain');
        raw = paste;
      }
    
      // Limpiar $ y comas, dejar solo números y un punto
      let cleaned = raw.replace(/[^0-9.]/g, '');
    
      // Solo un punto decimal permitido
      const parts = cleaned.split('.');
      if (parts.length > 2) {
        cleaned = parts[0] + '.' + parts[1];
      }
    
      const numeric = parseNumber(cleaned);
    
      // Mostrar formateado si es blur o paste
      if (e.type === 'blur' || e.type === 'paste') {
        $(this).val(formatCurrency(numeric));
      } else {
        $(this).val(cleaned);
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
    $sendButton.prop('disabled', true);
    const totalGlobal = parseNumber($totalInput.val());
    const calls = [];
    const pagoActivo = $commissionSwitch.is(':checked');
    const $selectedRows = $contractsTbody.find('.contratoCheck:checked').closest('tr');

    // 1) Pago de Comisión 100%
    if (pagoActivo) {
      if ($selectedRows.length !== 1) {
        $sendButton.prop('disabled', false);
        return Swal.fire('Error', 'Para Pago de Comisión, selecciona un solo contrato', 'warning');
      }
      const $row = $selectedRows.first();
      const code = $row.data('code');
      const companyCode = $row.data('companyCode');
      if (!code || !companyCode) {
        $sendButton.prop('disabled', false);
        return Swal.fire('Error', 'No se pudo leer el contrato. Refresca la lista.', 'error');
      }
      $row.find('.monto-input').val(formatCurrency(totalGlobal));
      calls.push(sendPayload({ SubscriberCode: '2F4', ActionCode: 906, CompanyCode: companyCode, ContractCode: code, Amount: totalGlobal, CurrencyCode: 'MXN', Description: `TB-${description}` }));
      calls.push(sendPayload({ SubscriberCode: '2F4', ActionCode: 907, CompanyCode: companyCode, ContractCode: code, Amount: totalGlobal, CurrencyCode: 'MXN', Description: `COMTB-${description}` }));
      return $.when(...calls)
        .then(() => Swal.fire('Éxito', 'Pago de comisión registrado', 'success').then(resetForm))
        .fail(() => Swal.fire('Error', 'Falló el registro', 'error'))
        .always(() => $sendButton.prop('disabled', false));
    }

    // 2) Depósitos múltiples
    const $includeRow = $contractsTbody.find('.incluyeComisionCheck:checked').closest('tr');

    if ($includeRow.length) {
      // 2.1) POST de depósito por cada contrato seleccionado
      $selectedRows.each(function() {
        const $r = $(this);
        const monto = parseNumber($r.find('.monto-input').val());
        if (!monto) return;
        const code = $r.data('code');
        const companyCode = $r.data('companyCode');
        calls.push(sendPayload({ SubscriberCode: '2F4', ActionCode: 906, CompanyCode: companyCode, ContractCode: code, Amount: monto, CurrencyCode: 'MXN', Description: `TB-${description}` }));
      });
      // 2.2) Calcular comisión+IVA sobre totalGlobal y asociar al contrato marcado
      const codeIncl = $includeRow.data('code');
      const companyIncl = $includeRow.data('companyCode');
      const pctIncl = contratosConComisionEspecial[codeIncl] !== undefined
      ? contratosConComisionEspecial[codeIncl]
       : parseInt((codeIncl.split('-')[1] || '00').substring(0, 2), 10) / 10 / 100;
      const base = totalGlobal / (1 + pctIncl * 1.16);
      const com = parseFloat((totalGlobal - base).toFixed(2));
      calls.push(sendPayload({ SubscriberCode: '2F4', ActionCode: 907, CompanyCode: companyIncl, ContractCode: codeIncl, Amount: com, CurrencyCode: 'MXN', Description: `COM${description}` }));
    } else {
      // 3) Depósitos múltiples sin incluir comisión+IVA
      // 3.1) POST de depósito por cada contrato seleccionado
      $selectedRows.each(function() {
        const $r = $(this);
        const monto = parseNumber($r.find('.monto-input').val());
        if (!monto) return;
        const code = $r.data('code');
        const companyCode = $r.data('companyCode');
        calls.push(sendPayload({
          SubscriberCode: '2F4',
          ActionCode: 906,
          CompanyCode: companyCode,
          ContractCode: code,
          Amount: monto,
          CurrencyCode: 'MXN',
          Description: `TB-${description}`
        }));
      });
      // 3.2) Calcular y enviar comisión SOBRE totalGlobal si al menos un contrato la marca
      const $commRow = $contractsTbody.find('.comisionCheck:checked').closest('tr').first();
      if ($commRow.length) {
        const codeComm = $commRow.data('code');
        const companyComm = $commRow.data('companyCode');
        const pct = contratosConComisionEspecial[codeComm] !== undefined
        ? contratosConComisionEspecial[codeComm]
        : parseInt((codeComm.split('-')[1] || '00').substring(0, 2), 10) / 10 / 100;
        const com = parseFloat((totalGlobal * pct * 1.16).toFixed(2));
        calls.push(sendPayload({
          SubscriberCode: '2F4',
          ActionCode: 907,
          CompanyCode: companyComm,
          ContractCode: codeComm,
          Amount: com,
          CurrencyCode: 'MXN',
          Description: `COM${description}`
        }));
      }
    }
    return $.when(...calls)
      .then(() => Swal.fire('Éxito', 'Depósitos registrados', 'success').then(resetForm))
      .fail(() => Swal.fire('Error', 'Falló el registro', 'error'))
      .always(() => $sendButton.prop('disabled', false));
  }

  // ——— RESET FORMULARIO ———
  function resetForm() {
    $commissionSwitch.prop('checked', false).trigger('change');
    $companyInput.val('').removeData('companyId');
    $totalInput.val('');
    $selectAll.prop('checked', false).prop('disabled', false);
    
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
    const saldoActual = recalcSaldo();
    if (Math.abs(saldoActual) > 0.01) {
      return Swal.fire('Error', `Falta asignar (${formatCurrency(saldoActual)})`, 'warning');
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
  const needsComm = $contractsTbody.find('tr')
    .filter((_, r) => $(r).data('requires-commission') && $(r).find('.contratoCheck').is(':checked')).length > 0;
  const markedComm = $contractsTbody.find('.comisionCheck:checked').length > 0;
  const $incluyeRow = $contractsTbody.find('.incluyeComisionCheck:checked').closest('tr');

  const companiaNombre = $companyInput.val();
  const contrato = $contractsTbody.find('.contratoCheck:checked').first().closest('tr').data('code');
  const monto = parseNumber($totalInput.val());
  const fecha = new Date().toLocaleString('es-MX');

  // ——— Calcular comisión real ———
  const $comRow = $contractsTbody.find('.comisionCheck:checked, .incluyeComisionCheck:checked').closest('tr').first();
  let comisionReal = 0;

  if ($comRow.length) {
    const code = $comRow.data('code');
    const pct = contratosConComisionEspecial[code] !== undefined
      ? contratosConComisionEspecial[code]
      : parseInt((code.split('-')[1] || '00').substring(0, 2), 10) / 10 / 100;

    const incluye = $comRow.find('.incluyeComisionCheck').is(':checked');
    comisionReal = incluye
      ? parseFloat((monto - (monto / (1 + pct * 1.16))).toFixed(2))
      : parseFloat((monto * pct * 1.16).toFixed(2));
  }

  // 🚨 Validación si "Depósito con Comisión" está marcado
  if ($incluyeRow.length) {
    const codeIncl = $incluyeRow.data('code');
    const pctIncl = contratosConComisionEspecial[codeIncl] !== undefined
      ? contratosConComisionEspecial[codeIncl]
      : parseInt((codeIncl.split('-')[1] || '00').substring(0, 2), 10) / 10 / 100;
    const base = monto / (1 + pctIncl * 1.16);
    const com = parseFloat((monto - base).toFixed(2));
    const neto = parseFloat(base.toFixed(2));

    return Swal.fire({
      icon: 'info',
      title: 'Depósito con Comisión',
      html: `
        <b>Total ingresado:</b> $${formatCurrency(monto)}<br>
        <b>Monto neto:</b> $${formatCurrency(neto)}<br>
        <b>Comisión + IVA:</b> $${formatCurrency(com)}<br><br>
        ¿Deseas continuar?
      `,
      showCancelButton: true,
      confirmButtonText: 'Sí, enviar',
      cancelButtonText: 'No, revisar'
    }).then(r => {
      if (r.isConfirmed) ejecutarConCorreo();
    });
  }

  if (!$commissionSwitch.is(':checked') && needsComm && !markedComm) {
    return Swal.fire({
      icon: 'warning',
      title: 'Falta aplicar comisión',
      text: '¿Continuar sin incluirla?',
      showCancelButton: true,
      confirmButtonText: 'Sí, continuar',
      cancelButtonText: 'No, revisar'
    }).then(r => {
      if (r.isConfirmed) ejecutarConCorreo();
    });
  }

  ejecutarConCorreo();

  function ejecutarConCorreo() {
    executeRequests(`${$('#tbFecha').val()}-${$('#tbExtra').val()}`).then(() => {
      $.ajax({
        url: '/beta.tokengas/PHP/notificaciondep.php',
        method: 'POST',
        contentType: 'application/json',
        data: JSON.stringify({
          compania: companiaNombre,
          contrato: contrato,
          monto: monto.toFixed(2),
          comision: comisionReal.toFixed(2),
          fecha: fecha,
          CompanyContracts: contratosCargados
        })
      }).then(() => {
        Swal.fire('Correo Enviado', 'Se ha enviado el correo de confirmación a la empresa.', 'success');
      }).catch(() => {
        Swal.fire('Aviso', 'El depósito fue exitoso, pero no se pudo enviar el correo de confirmación.', 'info');
      });
    });
  }
}

    
  });
});
