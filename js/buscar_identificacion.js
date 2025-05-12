$(function () {
    const $etiquetaInput = $('.etiqueta-input');
  
    $etiquetaInput.on('blur', function () {
      const etiqueta = $(this).val().trim();
      if (!etiqueta) return;
  
      fetch(`https://api-beta.ationet.com/Identifications?label=${encodeURIComponent(etiqueta)}`, {
        method: 'GET',
        headers: {
          'Authorization': 'Bearer ' + sessionStorage.getItem('access_token'), // Asegúrate de guardar tu token ahí
          'Accept': 'application/json'
        }
      })
        .then(res => res.json())
        .then(data => {
          const existe = Array.isArray(data.Content) && data.Content.some(i => i.Label === etiqueta);
          if (existe) {
            $(this).removeClass('is-valid').addClass('is-invalid');
            if (!$(this).next('.invalid-feedback').length) {
              $(this).after('<div class="invalid-feedback">Etiqueta ya existe</div>');
            }
          } else {
            $(this).removeClass('is-invalid').addClass('is-valid');
            $(this).next('.invalid-feedback').remove();
          }
        })
        .catch(() => {
          console.error('Error al validar etiqueta');
        });
    });
  });
  