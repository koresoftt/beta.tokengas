// js/identificadores.js
$(function () {
  const apiUrl    = 'identificadores.php';
  const $tbody    = $('#tablaIdentificadores tbody');
  let lastUID     = '';
  let activeUID   = null;
  let rowCounter  = $tbody.find('tr').length || 1;

  // — UTILIDADES para números/moneda —
  const parseNumber = input =>
    typeof input === 'number'
      ? input
      : parseFloat((input||'').toString().replace(/,/g,''))||0;

  // — INICIALIZAR AUTOCOMPLETE en un input dado —
  function initAutocomplete($input) {
    if ($input.data('ui-autocomplete')) return;  // ya inicializado
    $input.autocomplete({
      delay: 300,
      minLength: 1,
      source(request, response) {
        $.getJSON(`${apiUrl}?ajax=companies&term=${encodeURIComponent(request.term)}`)
          .done(data => {
            if (data.error) return response([{ label: data.error, value: '' }]);
            const items = data.map(c => ({ label:c.name, value:c.name, companyId:c.id }));
            response(items.length ? items : [{ label:'No se encontraron coincidencias',value:'' }]);
          })
          .fail(() => response([{ label:'Error de red',value:'' }]));
      },
      select(_, ui) {
        const $row = $input.closest('tr');
        $input.val(ui.item.value).data('companyId', ui.item.companyId);
        loadContracts($row, ui.item.companyId);
        return false;
      }
    });
  }

  // — CARGAR CONTRATOS en la fila dada —
  function loadContracts($row, companyId) {
    const $sel = $row.find('.contrato-select');
    $sel.empty().append('<option value="">-- Selecciona contrato --</option>');
    $.getJSON(`${apiUrl}?ajax=companyContracts&companyId=${encodeURIComponent(companyId)}`)
      .done(data => {
        if (data.error) {
          $sel.append('<option value="">Error al cargar</option>');
        } else {
          data.forEach(c => {
            $sel.append(`<option value="${c.Code}">${c.Code} – ${c.Description}</option>`);
          });
        }
      })
      .fail(() => $sel.append('<option value="">Error de red</option>'));
  }

  // — INICIAL: autocomplete en la primera fila —
  initAutocomplete($tbody.find('.compania-autocomplete').first());

  // — VALIDACIÓN ETIQUETA —
  $tbody.on('blur', '.etiqueta-input', function() {
    const $i = $(this), val = $i.val().replace(/[^0-9\-]/g,'');
    if (!val) return;
    $.getJSON(`${apiUrl}?ajax=checkIdentificador&label=${encodeURIComponent(val)}`)
      .done(d => $i.val(val + (d.exists?' ❌':' ✅')))
      .fail(()=> $i.val(val + ' ❌'));
  });

  // — TRACK (UID) automático —
  $(document)
    .on('focus', '.uid-field', function(){ activeUID = this; })
    .on('blur',  '.uid-field', function(){ activeUID = null; });

  setInterval(() => {
    if (!activeUID) return;
    fetch('uid.txt?cache='+Date.now())
      .then(r=>r.text()).then(txt=>{
        const uid = txt.trim();
        if (uid && uid !== lastUID) {
          lastUID = uid;
          const $u = $(activeUID).val(uid);
          $.getJSON(`${apiUrl}?ajax=checkIdentificador&track=${encodeURIComponent(uid)}`)
            .done(d=> $u.val(uid + (d.exists?' ❌':' ✅')))
            .fail(()=> $u.val(uid + ' ❌'));
        }
      })
      .catch(()=>{});
  }, 1000);

  // — actualizarModelo según TIPO —
  window.actualizarModelo = sel => {
    const v = sel.value.toUpperCase();
    const row = sel.closest('tr');
    row.querySelector('.modelo-input').value =
      v==='TAG'      ? 'TAG ATIONET' :
      v==='TARJETA'  ? 'TARJETA ATIONET' :
                      '';
  };

  // — AGREGAR RENGLÓN —
  window.agregarRenglon = function() {
    rowCounter++;
    const $last = $tbody.find('tr:last');
    const $new  = $last.clone().attr('id','row-'+rowCounter);

    // 1) Heredar TIPO, MODELO y PROGRAMA
    const tipoVal   = $last.find('.tipo-select').val();
    const modeloVal = $last.find('.modelo-input').val();
    const progVal   = $last.find('.programa-select').val();
    $new.find('.tipo-select').val(tipoVal);
    $new.find('.modelo-input').val(modeloVal);
    $new.find('.programa-select').val(progVal);

    // 2) Tipo de uso fijo
    $new.find('.tipo-uso-input').val('FLOTILLA');

    // 3) Compañía y Contrato
    const $globalComp  = $tbody.find('.compania-autocomplete').first();
    const compVal      = $globalComp.val();
    const compId       = $globalComp.data('companyId');
    $new.find('.compania-autocomplete')
        .val(compVal)
        .data('companyId', compId);
    const $globalCont  = $tbody.find('.contrato-select').first();
    $new.find('.contrato-select')
        .html($globalCont.html())
        .val($globalCont.val());

    // 4) Etiqueta secuencial
    const parts  = $last.find('.etiqueta-input').val().split('-');
    const suf    = parseInt(parts.pop(),10)+1;
    const prefix = parts.join('-')+'-';
    $new.find('.etiqueta-input')
        .val(prefix + String(suf).padStart(4,'0'));

    // 5) Limpiar UID
    $new.find('.uid-field').val('');

    // 6) NIP constante
    $new.find('.nip-field').val('1234');

    // 7) REQ. CAMBIO NIP siempre marcado
    $new.find('.req-nip-checkbox').prop('checked', true);

    // 8) Reactivar autocomplete en nueva fila
    initAutocomplete($new.find('.compania-autocomplete'));

    // 9) Insertar
    $tbody.append($new);
  };

  // — ELIMINAR RENGLÓN —
  window.borrarRenglones = function() {
    const $rows = $tbody.find('tr');
    if ($rows.length > 1) {
      $rows.last().remove();
      rowCounter--;  // opcional: mantener el contador en línea
    }
  };

});
