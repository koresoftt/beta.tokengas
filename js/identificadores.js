;(function($){
  $(function(){

    let lastUID    = '';
    let activeUID  = null;
    let rowCounter = $('#tablaIdentificadores tbody tr').length || 1;

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
              const items = data.map(c=>({
                label: c.name,
                value: c.name,
                companyId: c.id
              }));
              response(items.length ? items : [{ label:'No se encontraron coincidencias', value:'' }]);
            })
            .fail(() => response([{ label:'Error de red', value:'' }]));
        },
        select(_, ui) {
          const $row = $input.closest('tr');
          $input.val(ui.item.value).data('companyId', ui.item.companyId);
          loadContracts($row, ui.item.companyId);
          return false;
        }
      });
    }
    // inicializar al enfocar
    $(document).on('focus', '.compania-autocomplete', function(){
      initAutocomplete($(this));
    });

    // ── Carga contratos para la fila ────────────────────────────
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
              `<option value="${c.Id}" data-code="${c.Code}">
                 ${c.Code} – ${c.Description}
               </option>`
            );
          });
        })
        .fail(() => {
          $sel.append('<option value="">Error de red al cargar contratos</option>');
        });
    }

    // ── Validación de etiqueta ──────────────────────────────────
    $(document).on('blur', '.etiqueta-input', function(){
      const $i  = $(this);
      const val = $i.val().replace(/[^0-9\-]/g,'');
      if (!val) return;
      $.getJSON(`${window.API_URL}?ajax=checkIdentificador&label=${encodeURIComponent(val)}`)
        .done(d => $i.val(val + (d.exists ? ' ❌' : ' ✅')))
        .fail(()  =>   $i.val(val + ' ❌'));
    });

    // ── Lectura continua de UID ────────────────────────────────
    $(document)
      .on('focus', '.uid-field', function(){ activeUID = this; })
      .on('blur',  '.uid-field', function(){ activeUID = null; });

    setInterval(() => {
      if (!activeUID) return;
      fetch('../uid.txt?cache=' + Date.now())
        .then(r => r.text())
        .then(txt => {
          const uid = txt.trim();
          if (uid && uid !== lastUID) {
            lastUID = uid;
            const $u = $(activeUID).val(uid);
            // validamos sin iconos
            $.getJSON(`${window.API_URL}?ajax=checkIdentificador&track=${encodeURIComponent(uid)}`)
              .done(d => $u.val(uid))
              .fail(()  => $u.val(uid));
          }
        });
    }, 1000);

    // ── Actualizar modelo según tipo ───────────────────────────
    window.actualizarModelo = sel => {
      const v = sel.value.toUpperCase();
      const m = sel.closest('tr').querySelector('.modelo-input');
      m.value = v==='TAG'      ? 'TAG ATIONET'
              : v==='TARJETA'  ? 'TARJETA ATIONET'
              : '';
    };

    // ── Agregar / Borrar renglones ─────────────────────────────
    function agregarRenglon(){
      rowCounter++;
      const $last = $('#tablaIdentificadores tbody tr:last');
      const $new  = $last.clone().attr('id','row-'+rowCounter);

      // heredar tipo/modelo/programa
      ['tipo-select','modelo-input','programa-select'].forEach(cls=>{
        $new.find('.'+cls).val($last.find('.'+cls).val());
      });
      $new.find('.tipo-uso-input').val('FLOTILLA');

      // heredar compañía/contrato
      const $cLast = $last.find('.compania-autocomplete'),
            $tLast = $last.find('.contrato-select');
      $new.find('.compania-autocomplete')
          .val($cLast.val())
          .data('companyId',$cLast.data('companyId'));
      $new.find('.contrato-select')
          .html($tLast.html())
          .val($tLast.val());

      // nueva etiqueta
      const parts = $last.find('.etiqueta-input').val().split('-'),
            num   = parseInt(parts.pop(),10) + 1;
      parts.push(String(num).padStart(4,'0'));
      $new.find('.etiqueta-input').val(parts.join('-'));

      // limpiar UID / NIP / checkbox
      $new.find('.uid-field').val('');
      $new.find('.nip-field').val('1234');
      $new.find('.req-nip-checkbox').prop('checked', true);

      initAutocomplete($new.find('.compania-autocomplete'));
      $('#tablaIdentificadores tbody').append($new);
    }
    function borrarRenglones(){
      const $rows = $('#tablaIdentificadores tbody tr');
      if ($rows.length > 1) {
        $rows.last().remove();
        rowCounter--;
      }
    }

    // ── Crear Identificadores ──────────────────────────────
    function crearIdentificadores(){
      const items = [];
      const TYPE_MAP = {
        'TARJETA': {
          type: 0,
          typeModelId: '6ebbe762-3a33-40cb-8d92-088f5f34bef9',
          typeModelDescription: 'TARJETA ATIONET'
        },
        'TAG': {
          type: 1,
          typeModelId: '1ab9115d-0c84-4b87-8b65-bc974ce2432e',
          typeModelDescription: 'TAG RFID'
        }
      };
      const PROGRAM_MAP = {
        'CLASSIC': {
          programId: '4c56bc46-0553-43be-95d9-314a4dc70e0c',
          programDescription: 'Classic'
        },
        'TOKENGAS': {
          programId: '5ec6131c-3dfd-4d60-a0bd-4ec0bd24451d',
          programDescription: 'tokengas sinmex'
        }
      };

      $('#tablaIdentificadores tbody tr').each(function(){
        const $r      = $(this),
              tipoStr = $r.find('.tipo-select').val(),
              tCfg    = TYPE_MAP[tipoStr] || {},
              progStr = $r.find('.programa-select').val(),
              pCfg    = PROGRAM_MAP[progStr] || {},
              $opt    = $r.find('.contrato-select option:selected'),
              rawTrack= $r.find('.uid-field').val() || '',
              track   = rawTrack.replace(/[^A-Za-z0-9]/g,'').trim();

        items.push({
          NetworkId:            window.NETWORK_ID,
          UseType:              0,
          Type:                 tCfg.type,
          TypeModelId:          tCfg.typeModelId,
          TypeModelDescription: tCfg.typeModelDescription,
          ProgramId:            pCfg.programId,
          ProgramDescription:   pCfg.programDescription,
          IdCompany:            $r.find('.compania-autocomplete').data('companyId') || null,
          ContractId:           $opt.val() || null,
          ContractCode:         $opt.data('code') || null,
          Label:                $r.find('.etiqueta-input').val().replace(/[^0-9\-]/g,''),
          TrackNumber:          track,
          PAN:                  $r.find('.etiqueta-input').val().replace(/-/g,''),
          PIN:                  $r.find('.nip-field').val().trim(),
          RequiresPINChange:    $r.find('.req-nip-checkbox').is(':checked'),
          Active:               true
        });
      });

      console.log('🌐 [crearIdentificadores] Payload:', items);
      Swal.fire({ title:'Enviando…', didOpen(){ Swal.showLoading(); } });

      $.ajax({
        url: `${window.API_URL}?ajax=createIdentificadores`,
        method: 'POST',
        contentType: 'application/json',
        data: JSON.stringify({ items })
      })
      .done(res=>{
        console.log('✅ [crearIdentificadores] OK:', res);
        Swal.fire('¡Listo!','Identificadores creados con éxito.','success');
      })
      .fail((xhr, st, err)=>{
        console.error('❌ [crearIdentificadores] Error:', st, err, xhr.responseText);
        Swal.fire('Error','No se pudieron crear los identificadores.','error');
      });
    }

    // ── Exportar CSV ───────────────────────────────────────
    function exportarCSV(){
      let csv = 'Etiqueta,Track\n';
      $('#tablaIdentificadores tbody tr').each(function(){
        const e = $(this).find('.etiqueta-input').val().trim(),
              t = $(this).find('.uid-field').val().trim();
        csv += `"${e.replace(/"/g,'""')}","${t.replace(/"/g,'""')}"\n`;
      });
      const blob = new Blob([csv],{ type:'text/csv;charset=utf-8;' });
      const link = document.createElement('a');
      link.href     = URL.createObjectURL(blob);
      link.download = 'identificadores.csv';
      document.body.appendChild(link);
      link.click();
      document.body.removeChild(link);
    }

    // ── Bind de botones ────────────────────────────────────
    $('#btnAgregar').on('click', agregarRenglon);
    $('#btnBorrar').on('click', borrarRenglones);
    $('#btnCrear').on('click', crearIdentificadores);
    $('#btnExportarExcel').on('click', exportarCSV);
  });
})(jQuery);
