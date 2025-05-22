// js/identificadores.js
;(function($) {
  $(function() {
    let lastUID = '';
    let activeUID = null;
    let rowCounter = $('#tablaIdentificadores tbody tr').length || 1;

    // ── FORMATEO AUTOMÁTICO DE ETIQUETA ───────────────────────
    $(document).on('input', '.etiqueta-input', function() {
      const onlyDigits = $(this).val().replace(/\D/g, '');
      const groups = onlyDigits.match(/.{1,4}/g) || [];
      $(this).val(groups.join(' '));
    });

    // ── Autocomplete compañías ─────────────────────────────────
    function initAutocomplete($input) {
      if ($input.data('ui-autocomplete')) return;
      $input.autocomplete({
        delay: 300,
        minLength: 1,
        source(request, response) {
          $.getJSON(`${window.API_URL}?ajax=companies&term=${encodeURIComponent(request.term)}`)
            .done(data => {
              if (data.error) return response([{ label: data.error, value: '' }]);
              const items = data.map(c => ({ label: c.name, value: c.name, companyId: c.id }));
              response(items.length ? items : [{ label: 'No se encontraron coincidencias', value: '' }]);
            })
            .fail(() => response([{ label: 'Error de red', value: '' }]));
        },
        select(_, ui) {
          const $row = $input.closest('tr');
          $input.val(ui.item.value).data('companyId', ui.item.companyId);
          loadContracts($row, ui.item.companyId);
          return false;
        }
      });
    }
    $(document).on('focus', '.compania-autocomplete', function() {
      initAutocomplete($(this));
    });

    // ── Carga contratos ────────────────────────────────────────
    function loadContracts($row, companyId) {
      const $sel = $row.find('.contrato-select')
                       .empty()
                       .append('<option value="">-- Selecciona contrato --</option>');
      $.getJSON(`${window.API_URL}?ajax=companyContracts&companyId=${encodeURIComponent(companyId)}`)
        .done(data => {
          if (data.error) {
            $sel.append('<option value="">Error al cargar contratos</option>');
            return;
          }
          data.forEach(c => {
            $sel.append(
              `<option value="${c.Id}" data-code="${c.Code}">${c.Code} – ${c.Description}</option>`
            );
          });
        })
        .fail(() => $sel.append('<option value="">Error de red al cargar contratos</option>'));
    }

    // ── Validación etiqueta ────────────────────────────────────
    function validarEtiqueta($input) {
      const $row = $input.closest('tr');
      $row.find('.status-etiqueta').empty();
      const display = $input.val().trim();
      const labelForCheck = display.replace(/\s/g, '-');
      if (!labelForCheck) return;
      $.getJSON(`${window.API_URL}?ajax=checkIdentificador`, { label: labelForCheck })
        .done(res => {
          const icon = res.exists
            ? '<i class="bi bi-x-circle-fill text-danger"></i>'  // existe -> ❌
            : '<i class="bi bi-check-circle-fill text-success"></i>';  // no existe -> ✅
          $row.find('.status-etiqueta').html(icon);
        })
        .fail(() => {
          const warn = '<i class="bi bi-exclamation-circle-fill text-warning"></i>';
          $row.find('.status-etiqueta').html(warn);
        });
    }
    $(document).on('blur', '.etiqueta-input', function() {
      validarEtiqueta($(this));
    });

    // ── Validación track ───────────────────────────────────────
    function validarTrack($input) {
      const $row = $input.closest('tr');
      $row.find('.status-track').empty();
      const raw = $input.val().trim();
      const trackForCheck = raw.replace(/\D/g, '');
      if (!trackForCheck) return;
      $.getJSON(`${window.API_URL}?ajax=checkIdentificador`, { track: trackForCheck })
        .done(res => {
          const icon = res.exists
            ? '<i class="bi bi-x-circle-fill text-danger"></i>'
            : '<i class="bi bi-check-circle-fill text-success"></i>';
          $row.find('.status-track').html(icon);
        })
        .fail(() => {
          const warn = '<i class="bi bi-exclamation-circle-fill text-warning"></i>';
          $row.find('.status-track').html(warn);
        });
    }
    $(document).on('blur', '.uid-field', function() {
      validarTrack($(this));
    });

    // ── Lectura continua de UID ────────────────────────────────
    $(document)
      .on('focus', '.uid-field', function() { activeUID = this; })
      .on('blur',  '.uid-field', function() { activeUID = null; });

    setInterval(() => {
      if (!activeUID) return;
      fetch(`../uid.txt?cache=${Date.now()}`)
        .then(r => r.text())
        .then(txt => {
          const uid = txt.trim();
          if (uid && uid !== lastUID) {
            lastUID = uid;
            const $u = $(activeUID).val(uid);
            validarTrack($u);
          }
        });
    }, 1000);

    // ── Actualizar modelo ──────────────────────────────────────
    window.actualizarModelo = sel => {
      const v = sel.value.toUpperCase();
      const row = sel.closest('tr');
      row.querySelector('.tipo-uso-input').value = 'FLOTILLA';
      row.querySelector('.modelo-input').value =
        v === 'TAG' ? 'TAG ATIONET' :
        v === 'TARJETA' ? 'TARJETA ATIONET' : '';
    };

    // ── Agregar/Borrar renglones ──────────────────────────────
    function agregarRenglon() {
      rowCounter++;
      const $last = $('#tablaIdentificadores tbody tr:last');
      const $new  = $last.clone().attr('id', 'row-' + rowCounter);
      ['tipo-select','modelo-input','programa-select'].forEach(cls => {
        $new.find('.' + cls).val($last.find('.' + cls).val());
      });
      $new.find('.tipo-uso-input').val('FLOTILLA');
      const $cLast = $last.find('.compania-autocomplete'), $tLast = $last.find('.contrato-select');
      $new.find('.compania-autocomplete')
          .val($cLast.val())
          .data('companyId', $cLast.data('companyId'));
      $new.find('.contrato-select')
          .html($tLast.html())
          .val($tLast.val());
      const display = $last.find('.etiqueta-input').val();
      const digits = display.replace(/\D/g,'');
      const prefix = digits.slice(0, -4);
      const num = (parseInt(digits.slice(-4),10) + 1).toString().padStart(4,'0');
      const newDisplay = (prefix + num).match(/.{1,4}/g).join(' ');
      $new.find('.etiqueta-input').val(newDisplay);
      $new.find('.uid-field').val('');
      $new.find('.nip-field').val('1234');
      $new.find('.req-nip-checkbox').prop('checked', true);
      $new.find('.status-etiqueta, .status-track').empty();
      initAutocomplete($new.find('.compania-autocomplete'));
      $('#tablaIdentificadores tbody').append($new);
        validarEtiqueta( $new.find('.etiqueta-input') );

    }
    function borrarRenglones() {
      const $rows = $('#tablaIdentificadores tbody tr');
      if ($rows.length > 1) { $rows.last().remove(); rowCounter--; }
    }

    // ── Crear Identificadores ─────────────────────────────────
    function crearIdentificadores() {
      const items = [];
      const TYPE_MAP = {
        'TARJETA': { type:0, typeModelId:'6ebbe762-3a33-40cb-8d92-088f5f34bef9', typeModelDescription:'TARJETA ATIONET' },
        'TAG':     { type:1, typeModelId:'1ab9115d-0c84-4b87-8b65-bc974ce2432e', typeModelDescription:'TAG RFID' }
      };
      const PROGRAM_MAP = {
        'CLASSIC':  { programId:'4c56bc46-0553-43be-95d9-314a4dc70e0c', programDescription:'Classic' },
        'TOKENGAS': { programId:'5ec6131c-3dfd-4d60-a0bd-4ec0bd24451d', programDescription:'tokengas sinmex' }
      };
      $('#tablaIdentificadores tbody tr').each(function() {
        const $r = $(this), tipoStr = $r.find('.tipo-select').val(), tCfg = TYPE_MAP[tipoStr]||{}, progStr=$r.find('.programa-select').val(), pCfg=PROGRAM_MAP[progStr]||{}, $opt=$r.find('.contrato-select option:selected');
        const display=$r.find('.etiqueta-input').val().trim(), track=$r.find('.uid-field').val().replace(/\D/g,'').trim();
        items.push({
          NetworkId:window.NETWORK_ID,UseType:0,State:7,Type:tCfg.type,TypeModelId:tCfg.typeModelId,TypeModelDescription:tCfg.typeModelDescription,
          ProgramId:pCfg.programId,ProgramDescription:pCfg.programDescription,IdCompany:$r.find('.compania-autocomplete').data('companyId')||null,
          ContractId:$opt.val()||null,ContractCode:$opt.data('code')||null,Label:display.replace(/\s/g,'-'),TrackNumber:track,
          PAN:display.replace(/\D/g,''),PIN:$r.find('.nip-field').val().trim(),RequiresPINChange:$r.find('.req-nip-checkbox').is(':checked'),Active:true
        });
      });
      $.ajax({url:`${window.API_URL}?ajax=createIdentificadores`,method:'POST',contentType:'application/json',data:JSON.stringify({items})})
        .done(() => Swal.fire('¡Listo!','Identificadores creados con éxito.','success'))
        .fail(() => Swal.fire('Error','No se pudieron crear los identificadores.','error'));
    }

    // ── Exportar CSV ─────────────────────────────────────────
    function exportarCSV() {
      let csv = 'Etiqueta,Track\n';
      $('#tablaIdentificadores tbody tr').each(function() {
        const e=$(this).find('.etiqueta-input').val().trim(), t=$(this).find('.uid-field').val().trim();
        csv+=`"${e}","${t}"\n`;
      });
      const blob=new Blob([csv],{type:'text/csv;charset=utf-8;'}), link=document.createElement('a');
      link.href=URL.createObjectURL(blob); link.download='identificadores.csv'; document.body.appendChild(link); link.click(); document.body.removeChild(link);
    }
    $('#btnAgregar').on('click',agregarRenglon);
    $('#btnBorrar').on('click',borrarRenglones);
    $('#btnCrear').on('click',crearIdentificadores);
    $('#btnExportarExcel').on('click',exportarCSV);

  });
})(jQuery);
