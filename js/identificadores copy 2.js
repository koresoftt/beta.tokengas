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
        console.log("Buscando compañías:", request.term);
        $.getJSON(`?ajax=companies&term=${encodeURIComponent(request.term)}`, data => {
          console.log("Respuesta:", data);
          if (data.error) {
            response([{ label: data.error, value: '', error: true }]);
            return;
          }
          const items = data.slice(0, 10).map(c => ({ label: c.name, value: c.name, companyId: c.id }));
          response(items.length ? items : [{ label: 'No se encontraron coincidencias.', value: '', error: true }]);
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
    $.getJSON(`?ajax=companyContracts&companyId=${companyId}`, function (data) {
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

  function cargarCompaniasIdentificadores() {
    fetch('?ajax=companies')
      .then(res => res.json())
      .then(data => {
        const selects = document.querySelectorAll('.compania-select');
        selects.forEach(select => {
          select.innerHTML = '<option value="">-- Seleccione Compañía --</option>';
          data.forEach(c => {
            const option = document.createElement('option');
            option.value = c.CompanyCode || c.id || c.name;
            option.textContent = c.name || c.label;
            select.appendChild(option);
          });
        });
      })
      .catch(err => {
        console.error('Error al cargar compañías para identificadores', err);
      });
  }

  cargarCompaniasIdentificadores();

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

  // Agregar autocomplete a futuros campos de compañía
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


  window.agregarRenglon = function () {
    contador++;
    const prevRow = document.querySelector(`#row-${contador - 1}`);
    if (!prevRow) return;

    const tipo = prevRow.querySelector('.tipo-select')?.value || '';
    const uso = prevRow.cells[1].querySelector('input')?.value || 'FLOTILLA';
    const modelo = prevRow.querySelector('.modelo-input')?.value || '';
    const programa = prevRow.cells[3].querySelector('select')?.value || 'CLASSIC';
    const compania = prevRow.cells[4].querySelector('input')?.value || '';
    const contratoHTML = prevRow.cells[5].querySelector('select')?.outerHTML || '';
    const contratoValor = prevRow.cells[5].querySelector('select')?.value || '';
    const etiquetaAnterior = prevRow.cells[6].querySelector('input')?.value || '1508-0000-0000-0000';
    const nipValor = prevRow.querySelector('.nip-field')?.value || '1234';

    const partes = etiquetaAnterior.split('-');
    let ultimosDigitos = parseInt(partes[partes.length - 1]) || 0;
    ultimosDigitos++;
    partes[partes.length - 1] = ultimosDigitos.toString().padStart(4, '0');
    const etiquetaNueva = partes.join('-');

    validarExistencia('label', etiquetaNueva).then(data => {
      if (data.exists) {
        Swal.fire({ icon: 'error', title: 'Etiqueta duplicada', text: `La etiqueta ${etiquetaNueva} ya está registrada.` });
        return;
      }

      const filaHTML = `
        <tr id="row-${contador}">
          <td>
            <select class="form-control tipo-select" onchange="actualizarModelo(this)">
              <option value="">-- Seleccione --</option>
              <option value="TARJETA" ${tipo === 'TARJETA' ? 'selected' : ''}>Tarjeta</option>
              <option value="TAG" ${tipo === 'TAG' ? 'selected' : ''}>TAG</option>
            </select>
          </td>
          <td><input type="text" class="form-control" value="${uso}" readonly></td>
          <td><input type="text" class="form-control modelo-input" value="${modelo}" readonly></td>
          <td>
            <select class="form-control">
              <option value="CLASSIC" ${programa === 'CLASSIC' ? 'selected' : ''}>Classic</option>
              <option value="TOKENGAS" ${programa === 'TOKENGAS' ? 'selected' : ''}>Tokengas</option>
            </select>
          </td>
          <td><input type="text" class="form-control" value="${compania}"></td>
          <td>${contratoHTML.replace(`value="${contratoValor}"`, `value="${contratoValor}" selected`)}</td>
          <td><input type="text" class="form-control etiqueta-input" value="${etiquetaNueva}" maxlength="19"></td>
          <td><input type="text" class="form-control uid-field" id="uid-${contador}" readonly></td>
          <td>
            <div class="input-group input-group-sm">
              <input type="text" class="form-control nip-field" maxlength="4" value="${nipValor}">
              <button class="btn btn-outline-secondary toggle-nip" type="button">
                <i class="bi bi-eye-slash"></i>
              </button>
            </div>
          </td>
          <td class="text-center">
            <input type="checkbox" class="form-check-input cambio-nip-check" checked>
          </td>
        </tr>`;

      $('#tablaIdentificadores tbody').append(filaHTML);
    });
  };

  window.borrarRenglones = function () {
    $('#tablaIdentificadores tbody tr').each(function () {
      if ($(this).find('input[type=checkbox]').is(':checked')) {
        $(this).remove();
      }
    });
  };

  window.crearIdentificadores = function () {
    const datos = [];
    const promesas = [];
    let errorDetectado = false;

    $('#tablaIdentificadores tbody tr').each(function () {
      const fila = $(this);
      const tipo = fila.find('.tipo-select').val();
      const modelo = fila.find('.modelo-input').val();
      const uid = fila.find('.uid-field').val();
      const nip = fila.find('.nip-field').val();
      const etiqueta = fila.find('.etiqueta-input').val();
      const cambioNip = fila.find('input[type=checkbox]').is(':checked');

      if (tipo && modelo && uid && nip && etiqueta) {
        datos.push({ tipo, modelo, uid, nip, etiqueta, requiereCambioNip: cambioNip });
        promesas.push(
          validarExistencia('label', etiqueta).then(data => {
            if (data.exists) {
              errorDetectado = true;
              Swal.fire({ icon: 'error', title: 'Etiqueta duplicada', text: `La etiqueta ${etiqueta} ya existe.` });
            }
          }),
          validarExistencia('track', uid).then(data => {
            if (data.exists) {
              errorDetectado = true;
              Swal.fire({ icon: 'error', title: 'TRACK duplicado', text: `El UID ${uid} ya existe.` });
            }
          })
        );
      }
    });

    Promise.all(promesas).then(() => {
      if (errorDetectado) return;
      if (datos.length === 0) {
        Swal.fire('Sin datos válidos', 'No hay identificadores listos para enviar.', 'warning');
        return;
      }
      console.log("Identificadores generados:", datos);
    });
  };
