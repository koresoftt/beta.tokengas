$(function () {
  const $companyInput = $('#compania');
  const $contractInput = $('#contrato');
  let contador = 1;
  let campoUIDActivo = null;
  let ultimoUIDLeido = '';

  function inicializarAutocompleteCompania($input) {
    $input.autocomplete({
      delay: 300,
      minLength: 1,
      source(request, response) {
        $.getJSON('identificadores.php?ajax=companies&term=' + encodeURIComponent(request.term))
          .done(data => {
            if (data.error) {
              response([{ label: data.error, value: '', error: true }]);
              return;
            }
            const items = data.slice(0, 10).map(c => ({ label: c.name, value: c.name, companyId: c.id }));
            response(items.length ? items : [{ label: 'No se encontraron coincidencias.', value: '', error: true }]);
          })
          .fail((xhr, status, error) => {
            console.error('Error al obtener compañías:', status, error);
            response([{ label: 'Error al consultar compañías.', value: '', error: true }]);
          });
      },
      select(event, ui) {
        $input.val(ui.item.label).data('companyId', ui.item.companyId);
        cargarContratosPorCompania(ui.item.companyId);
        return false;
      }
    });
  }

  if ($companyInput.length > 0 && $.fn.autocomplete) {
    inicializarAutocompleteCompania($companyInput);
  }

  function cargarContratosPorCompania(companyId) {
    if (!companyId || !$contractInput.length) return;
    $.getJSON('identificadores.php?ajax=companyContracts&companyId=' + companyId, function (data) {
      if (!Array.isArray(data)) {
        $contractInput.val('');
        return;
      }
      if ($contractInput.is('select')) {
        $contractInput.html('<option value="">-- Selecciona contrato --</option>');
        data.forEach(c => {
          $contractInput.append($('<option>', { value: c.Code, text: `${c.Code} - ${c.Description}` }));
        });
      } else {
        const list = data.map(c => `${c.Code} - ${c.Description}`);
        $contractInput.autocomplete({
          source: list,
          minLength: 0
        }).focus(function () {
          $(this).autocomplete('search', '');
        });
      }
    });
  }

  function validarExistencia(criterio, valor) {
    return $.get('identificadores.php', { ajax: 'checkIdentificador', [criterio]: valor });
  }

  $(document).on('input', '.etiqueta-input', function () {
    const raw = $(this).val().replace(/\D/g, '').slice(0, 16);
    const formatted = raw.match(/.{1,4}/g)?.join('-') || '';
    $(this).val(formatted);
  });

  $(document).on('blur', '.etiqueta-input', function () {
    const etiqueta = $(this).val().trim();
    const $input = $(this);
    if (!etiqueta) return;

    validarExistencia('label', etiqueta).then(data => {
      const icono = data.exists ? '❌' : '✅';
      $input.val(etiqueta + ' ' + icono);
    });
  });

  $(document).on('focus', '.uid-field', function () {
    campoUIDActivo = this;
  });

  $(document).on('blur', '.uid-field', function () {
    campoUIDActivo = null;
  });

  setInterval(() => {
    if (!campoUIDActivo) return;
    fetch('../uid.txt?cache=' + new Date().getTime())
      .then(res => res.text())
      .then(uid => {
        const limpio = uid.trim();
        if (limpio && limpio !== ultimoUIDLeido) {
          ultimoUIDLeido = limpio;
          campoUIDActivo.value = limpio;
          validarExistencia('track', limpio).then(data => {
            const icono = data.exists ? '❌' : '✅';
            campoUIDActivo.value = limpio + ' ' + icono;
          });
        }
      });
  }, 1000);

  window.actualizarModelo = function (selectElement) {
    const valor = selectElement.value.toUpperCase();
    const modeloInput = selectElement.closest('tr').querySelector('.modelo-input');
    modeloInput.value = valor === 'TAG' ? 'TAG ATIONET' : valor === 'TARJETA' ? 'TARJETA ATIONET' : '';
  };

  $(document).on('focus', '.compania-autocomplete:not(.ui-autocomplete-input)', function () {
    $(this).addClass('ui-autocomplete-input');
    inicializarAutocompleteCompania($(this));
  });

  window.leerUID = function (rowId) {
    fetch('../uid.txt?cache=' + new Date().getTime())
      .then(res => res.text())
      .then(uid => {
        document.getElementById('uid-' + rowId).value = uid.trim();
      })
      .catch(() => {
        alert('No se pudo leer el UID');
      });
  };
});
