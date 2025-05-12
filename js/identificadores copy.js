// js/identificadores.js
$(function () {
  const $companyInput = $('#compania');
  const $contractInput = $('#contrato');

  // — UTILIDADES para números/moneda —
  const parseNumber = input =>
    typeof input === 'number'
      ? input
      : parseFloat((input || '').toString().replace(/,/g, '')) || 0;
  const formatCurrency = v =>
    (isNaN(v) ? 0 : v).toLocaleString('es-MX', {
      minimumFractionDigits: 2,
      maximumFractionDigits: 2
    });

  // — AUTOCOMPLETE COMPAÑÍAS —
  if ($companyInput.length && $.fn.autocomplete) {
    $companyInput.autocomplete({
      delay: 300,
      minLength: 1,
      source(request, response) {
        $.getJSON(
          `identificadores.php?ajax=companies&term=${encodeURIComponent(request.term)}`
        )
          .done(data => {
            if (data.error) {
              return response([{ label: data.error, value: '' }]);
            }
            const items = data.map(c => ({
              label: c.name,
              value: c.name,
              companyId: c.id
            }));
            response(
              items.length
                ? items
                : [{ label: 'No se encontraron coincidencias', value: '' }]
            );
          })
          .fail(() => {
            response([{ label: 'Error de red', value: '' }]);
          });
      },
      select(_, ui) {
        $companyInput
          .val(ui.item.value)
          .data('companyId', ui.item.companyId);
        cargarContratos(ui.item.companyId);
        return false;
      }
    });
  }

  // — CARGA CONTRATOS según compañía —
  function cargarContratos(companyId) {
    $.getJSON(
      `identificadores.php?ajax=companyContracts&companyId=${encodeURIComponent(companyId)}`
    )
      .done(data => {
        const $sel = $contractInput.empty().append(
          '<option value="">-- Selecciona contrato --</option>'
        );
        if (data.error) {
          $sel.append('<option value="">Error al cargar contratos</option>');
          return;
        }
        data.forEach(c => {
          $sel.append(
            `<option value="${c.Code}">${c.Code} – ${c.Description}</option>`
          );
        });
      })
      .fail(() => {
        $contractInput
          .empty()
          .append('<option value="">Error de red al cargar contratos</option>');
      });
  }

  // — VALIDACIÓN ETIQUETA (label) —
  $(document).on('blur', '.etiqueta-input', function () {
    const $i = $(this);
    const val = $i.val().replace(/[^0-9\-]/g, '');
    if (!val) return;
    $.getJSON(
      `identificadores.php?ajax=checkIdentificador&label=${encodeURIComponent(val)}`
    )
      .done(data => {
        $i.val(val + (data.exists ? ' ❌' : ' ✅'));
      })
      .fail(() => {
        $i.val(val + ' ❌');
      });
  });

  // — VALIDACIÓN TRACK (UID) —
  let lastUID = '';
  let activeUID = null;
  $(document)
    .on('focus', '.uid-field', function () {
      activeUID = this;
    })
    .on('blur', '.uid-field', function () {
      activeUID = null;
    });

  setInterval(() => {
    if (!activeUID) return;
    fetch('../uid.txt?cache=' + Date.now())
      .then(r => r.text())
      .then(uid => {
        uid = uid.trim();
        if (uid && uid !== lastUID) {
          lastUID = uid;
          const $u = $(activeUID).val(uid);
          $.getJSON(
            `identificadores.php?ajax=checkIdentificador&track=${encodeURIComponent(uid)}`
          )
            .done(data => {
              $u.val(uid + (data.exists ? ' ❌' : ' ✅'));
            })
            .fail(() => {
              $u.val(uid + ' ❌');
            });
        }
      });
  }, 1000);

  // — actualizarModelo según TIPO —
  window.actualizarModelo = sel => {
    const v = sel.value.toUpperCase();
    const m = sel.closest('tr').querySelector('.modelo-input');
    m.value =
      v === 'TAG'
        ? 'TAG ATIONET'
        : v === 'TARJETA'
        ? 'TARJETA ATIONET'
        : '';
  };
});
