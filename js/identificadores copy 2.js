// js/identificadores.js
$(function() {
  const apiUrl = 'identificadores.php';
  let lastUID = '', activeUID = null;
  let rowCounter = $('#tablaIdentificadores tbody tr').length;

  // Inicializo el autocomplete de la primera fila
  initAutocomplete($('.compania-autocomplete'));

  // dispara instanciación al enfocar
  $(document).on('focus', '.compania-autocomplete', function(){
    initAutocomplete($(this));
  });

  function initAutocomplete($input) {
    // comprobación correcta de jquery-ui 1.12+
    if ($input.autocomplete('instance')) return;

    console.log('🌐 [initAutocomplete] bind en', $input);
    $input.autocomplete({
      delay: 300,
      minLength: 1,
      source(request, response) {
        console.log('🌐 [autocomplete] AJAX companies term=', request.term);
        $.getJSON(`${apiUrl}?ajax=companies&term=${encodeURIComponent(request.term)}`)
         .done(data => {
            if (data.error) {
              return response([{ label: data.error, value: '' }]);
            }
            const items = data.map(c => ({
              label: c.name,
              value: c.name,
              companyId: c.id
            }));
            response(items.length ? items : [{ label: 'Sin coincidencias', value: '' }]);
         })
         .fail(() => response([{ label: 'Error de red', value: '' }]));
      },
      select(event, ui) {
        const $row = $input.closest('tr');
        $input.val(ui.item.value).data('companyId', ui.item.companyId);
        loadContracts($row, ui.item.companyId);
        return false;
      }
    });
  }

  function loadContracts($row, companyId) {
    const $sel = $row.find('.contrato-select')
      .empty()
      .append('<option value="">-- Selecciona contrato --</option>');
    $.getJSON(`${apiUrl}?ajax=companyContracts&companyId=${encodeURIComponent(companyId)}`)
     .done(data => {
        if (data.error) {
          return $sel.append('<option value="">Error al cargar</option>');
        }
        data.forEach(c => {
          $sel.append(`<option value="${c.Code}">${c.Code} – ${c.Description}</option>`);
        });
     })
     .fail(() => {
       $sel.append('<option value="">Error de red al cargar</option>');
     });
  }

  // Validación etiqueta
  $(document).on('blur', '.etiqueta-input', function(){
    const $i = $(this), val = $i.val().replace(/[^0-9\-]/g,'');
    if (!val) return;
    $.getJSON(`${apiUrl}?ajax=checkIdentificador&label=${encodeURIComponent(val)}`)
     .done(d => $i.val(val + (d.exists?' ❌':' ✅')))
     .fail(() => $i.val(val+' ❌'));
  });

  // Validación UID
  $(document).on('focus', '.uid-field',  function(){ activeUID = this; })
             .on('blur',  '.uid-field',  function(){ activeUID = null; });
  setInterval(() => {
    if (!activeUID) return;
    fetch('../uid.txt?cache='+Date.now())
     .then(r => r.text())
     .then(t => {
       const uid = t.trim();
       if (uid && uid !== lastUID) {
         lastUID = uid;
         const $u = $(activeUID).val(uid);
         $.getJSON(`${apiUrl}?ajax=checkIdentificador&track=${encodeURIComponent(uid)}`)
          .done(d => $u.val(uid + (d.exists?' ❌':' ✅')))
          .fail(() => $u.val(uid+' ❌'));
       }
     });
  },1000);

  // Actualizar modelo según tipo
  window.actualizarModelo = function(sel){
    const v = sel.value.toUpperCase();
    const m = sel.closest('tr').querySelector('.modelo-input');
    m.value = v==='TAG' ? 'TAG ATIONET'
             : v==='TARJETA' ? 'TARJETA ATIONET'
             : '';
  };

  // Agregar fila
  function agregarRenglon(){
    rowCounter++;
    const $last = $('#tablaIdentificadores tbody tr:last'),
          $new  = $last.clone().attr('id','row-'+rowCounter);

    // Heredar tipo, modelo, programa
    ['tipo-select','modelo-input','programa-select']
      .forEach(cls => $new.find('.'+cls).val($last.find('.'+cls).val()));

    // Heredar compañía/contrato
    const $cLast = $last.find('.compania-autocomplete'),
          $kLast = $last.find('.contrato-select');
    $new.find('.compania-autocomplete')
        .val($cLast.val())
        .data('companyId',$cLast.data('companyId'));
    $new.find('.contrato-select')
        .html($kLast.html())
        .val($kLast.val());

    // Limpiar lo demás
    $new.find('.etiqueta-input').val('');
    $new.find('.uid-field').val('');
    $new.find('.nip-field').val('1234');
    $new.find('.req-nip-checkbox').prop('checked', true);

    initAutocomplete($new.find('.compania-autocomplete'));
    $('#tablaIdentificadores tbody').append($new);
  }

  // Borrar última fila
  function borrarRenglones(){
    const $rows = $('#tablaIdentificadores tbody tr');
    if ($rows.length>1) {
      $rows.last().remove();
      rowCounter--;
    }
  }

  // Crear identificadores
  function crearIdentificadores(){
    const TYPE_MAP = {
      'TARJETA': { type:0, typeModelId:'6ebbe762-3a33-40cb-8d92-088f5f34bef9', typeModelDescription:'TARJETA ATIONET' },
      'TAG':     { type:1, typeModelId:'1ab9115d-0c84-4b87-8b65-bc974ce2432e', typeModelDescription:'TAG RFID' }
    };
    const PROGRAM_MAP = {
      'CLASSIC':  { programId:'4c56bc46-0553-43be-95d9-314a4dc70e0c', programDescription:'Classic' },
      'TOKENGAS': { programId:'5ec6131c-3dfd-4d60-a0bd-4ec0bd24451d', programDescription:'tokengas sinmex' }
    };
    const items = [];
    $('#tablaIdentificadores tbody tr').each(function(){
      const $r = $(this),
            tStr = $r.find('.tipo-select').val(),
            tCfg = TYPE_MAP[tStr]||{},
            pStr = $r.find('.programa-select').val(),
            pCfg = PROGRAM_MAP[pStr]||{};

      const companyId   = $r.find('.compania-autocomplete').data('companyId')||null,
            contractId  = $r.find('.contrato-select').val()||null,
            contractCode= contractId,
            label       = $r.find('.etiqueta-input').val().replace(/[^0-9\-]/g,''),
            trackNumber = $r.find('.uid-field').val().trim(),
            pan         = label.replace(/-/g,''),
            pin         = $r.find('.nip-field').val().trim(),
            reqPIN      = $r.find('.req-nip-checkbox').is(':checked');

      items.push({
        NetworkId:             NETWORK_ID,
        UseType:               0,
        Type:                  tCfg.type,
        TypeModelId:           tCfg.typeModelId,
        TypeModelDescription:  tCfg.typeModelDescription,
        ProgramId:             pCfg.programId,
        ProgramDescription:    pCfg.programDescription,
        IdCompany:             companyId,
        ContractId:            contractId,
        ContractCode:          contractCode,
        Label:                 label,
        TrackNumber:           trackNumber,
        PAN:                   pan,
        PIN:                   pin,
        RequiresPINChange:     reqPIN,
        Active:                true
      });
    });

    console.log('🌐 [crearIdentificadores] Payload a enviar:', { items });

    $.ajax({
      url: 'postidentificadores.php?ajax=createIdentificadores',
      method: 'POST',
      contentType: 'application/json',
      data: JSON.stringify({ items }),
      success(resp) {
        console.log('✅ [crearIdentificadores] OK', resp);
        Swal.fire('¡Listo!','Identificadores creados con éxito.','success');
      },
      error(xhr) {
        console.error('❌ [crearIdentificadores] Error', xhr);
        Swal.fire('Error','No se pudieron crear los identificadores.','error');
      }
    });
  }

  $('#btnAgregar').on('click', agregarRenglon);
  $('#btnBorrar').on('click', borrarRenglones);
  $('#btnCrear').on('click', crearIdentificadores);
  $('#btnExportarExcel').on('click', function(){
    let csv = 'Etiqueta,Track\n';
    $('#tablaIdentificadores tbody tr').each(function(){
      const e = $(this).find('.etiqueta-input').val().trim(),
            t = $(this).find('.uid-field').val().trim();
      csv += `"${e.replace(/"/g,'""')}","${t.replace(/"/g,'""')}"\n`;
    });
    const blob = new Blob([csv], {type:'text/csv;charset=utf-8;'}),
          link = document.createElement('a');
    link.href    = URL.createObjectURL(blob);
    link.download= 'identificadores.csv';
    document.body.appendChild(link);
    link.click();
    document.body.removeChild(link);
  });
});
