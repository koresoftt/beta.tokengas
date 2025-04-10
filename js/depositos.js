/**
 * depositos.js
 * ------------
 * Este script implementa:
 * - Autocomplete para el input "#compania", mostrando "Buscando..." inmediatamente
 *   al iniciar la búsqueda y "No se encontraron coincidencias." si no se obtienen resultados.
 * - Funciones de utilidad para formatear números y recalcular saldos.
 * - Renderizado de contratos y envío de depósitos vía AJAX con notificaciones usando SweetAlert2.
 *
 * Requisitos:
 * - jQuery 3.6.0
 * - jQuery UI 1.12 o superior
 * - SweetAlert2 (incluir en el HTML)
 *
 * NOTA: Asegúrate de que este script se ejecute cuando el DOM esté listo y que el elemento
 * con id "compania" exista en la página.
 */

$(function() {
    // ---------------------------
    // UTILIDADES
    // ---------------------------
    const parseNumber = input => {
      if (typeof input === "number") return input;
      if (!input) return 0;
      return parseFloat(input.toString().replace(/,/g, "")) || 0;
    };
  
    const formatCurrency = value =>
      (isNaN(value) ? 0 : value).toLocaleString("es-MX", {
        minimumFractionDigits: 2,
        maximumFractionDigits: 2
      });
  
    const recalcSaldo = () => {
      const total = parseNumber($total.val());
      let sum = 0;
      $tbody.find(".contratoCheck:checked").each(function() {
        sum += parseNumber($(this).closest("tr").find(".monto-input").val());
      });
      const saldo = total - sum;
      $saldo.text(`Saldo: ${formatCurrency(saldo)}`);
      $mensajeAdvertencia.text(saldo < 0 ? `Saldo negativo. Máximo ${formatCurrency(total)}.` : "");
      return saldo;
    };
  
    // ---------------------------
    // REFERENCIAS AL DOM
    // ---------------------------
    const $compania = $("#compania");
    const $total = $("#total");
    const $tbody = $("#tbodyContratos");
    const $saldo = $("#saldo");
    const $mensajeAdvertencia = $("#mensajeAdvertencia");
    const $btnEnviar = $("#btnEnviar");
  
    if ($compania.length === 0) {
      console.error("Error: No se encontró el elemento con id 'compania'");
      return;
    }
  
    // Bandera para controlar el estado de la búsqueda
    let isSearching = false;
    let searchTimeout;
  
    // ---------------------------
    // AUTOCOMPLETE CON MENSAJES INYECTADOS
    // ---------------------------
    $compania.autocomplete({
      delay: 300,
      minLength: 1,
  
      // Evento "search": se activa al iniciar la búsqueda.
      // Inyecta "Buscando..." en el menú y fuerza que se muestre.
      search: function() {
        console.log("Evento search activado. Valor:", $(this).val());
        isSearching = true;
        const instance = $(this).autocomplete("instance");
        if (instance && instance.menu) {
          // Inyectamos el mensaje "Buscando..."
          instance.menu.element.html(
            "<li class='ui-menu-item'><div>Buscando...</div></li>"
          );
          // Forzamos la visualización del menú
          instance.menu.element.show();
        }
      },
  
      // Evento "source": se encarga de obtener los datos mediante AJAX.
      source: function(request, response) {
        console.log("Source activado. Término:", request.term);
        clearTimeout(searchTimeout);
        searchTimeout = setTimeout(() => {
          $.getJSON(`?ajax=companies&term=${encodeURIComponent(request.term)}`, function(data) {
            console.log("Respuesta AJAX:", data);
            if (data.error) {
              response([{ label: data.error, value: "", error: true }]);
              return;
            }
            // Mapear los resultados y limitar a 10 elementos
            let results = data.map(c => ({
              label: c.name,
              value: c.name,
              companyId: c.id
            })).slice(0, 10);
            if (results.length === 0) {
              results = [{ label: "No se encontraron coincidencias.", value: "", error: true }];
            }
            response(results);
          });
        }, 300);
      },
  
      // Evento "response": se ejecuta cuando se recibe la respuesta AJAX.
      // Desactiva la bandera de búsqueda y, si no hay resultados, muestra el mensaje correspondiente.
      response: function(event, ui) {
        console.log("Evento response. Contenido:", ui.content);
        isSearching = false;
        if (ui.content.length === 0) {
          const instance = $(this).autocomplete("instance");
          if (instance && instance.menu) {
            instance.menu.element.html(
              "<li class='ui-menu-item'><div>No se encontraron coincidencias.</div></li>"
            );
            instance.menu.element.show();
          }
        }
      },
  
      // Evento "select": se ejecuta al seleccionar un ítem.
      select: function(event, ui) {
        console.log("Ítem seleccionado:", ui.item);
        $compania.val(ui.item.label).data("companyId", ui.item.companyId);
        if (parseNumber($total.val()) > 0) {
          $.getJSON(`?ajax=companyContracts&companyId=${ui.item.companyId}`, renderContratos);
        }
        return false;
      }
    });
  
    // ---------------------------
    // VALIDACIÓN DEL INPUT "TOTAL"
    // ---------------------------
    $total
      .attr({ type: "text", inputmode: "decimal" })
      .on("keypress", function(e) {
        if (!/[0-9.]|\b/.test(e.key)) {
          e.preventDefault();
        }
      })
      .on("blur input", function(e) {
        let val = parseNumber($(this).val());
        if (val < 0) val = 0;
        if (e.type === "blur") {
          $(this).val(formatCurrency(val));
        }
        recalcSaldo();
        if (val > 0 && $compania.data("companyId")) {
          $.getJSON(`?ajax=companyContracts&companyId=${$compania.data("companyId")}`, renderContratos);
        }
      });
  
    // ---------------------------
    // RENDERIZADO DE CONTRATOS
    // ---------------------------
    function renderContratos(contratos) {
      if (!Array.isArray(contratos) || contratos.length === 0) {
        $tbody.html('<tr><td colspan="5" class="text-center">No se encontraron contratos.</td></tr>');
        return;
      }
      const rows = contratos.map(c => {
        const segment = (c.Code.split("-")[1] || "00").substring(0, 2);
        const requiresComm = parseInt(segment, 10) > 0;
        return `
          <tr data-company-code="${c.CompanyCode}" data-code="${c.Code}" data-requires-commission="${requiresComm}">
            <td><input type="checkbox" class="contratoCheck"></td>
            <td>${c.Code}</td>
            <td>${c.ContractDescription}</td>
            <td><input type="text" class="monto-input form-control form-control-sm" disabled></td>
            <td><input type="checkbox" class="comisionCheck" disabled></td>
          </tr>
        `;
      }).join("");
      $tbody.html(rows);
    }
  
    // ---------------------------
    // EVENTOS EN LAS FILAS DE CONTRATOS
    // ---------------------------
    $tbody.on("change", ".contratoCheck", function() {
      const $row = $(this).closest("tr");
      const $monto = $row.find(".monto-input");
      const $comm = $row.find(".comisionCheck");
      const requiresComm = $row.data("requiresCommission");
      if (this.checked) {
        $monto.prop("disabled", false).val("");
        if (requiresComm) {
          $comm.prop("disabled", false);
        }
      } else {
        $monto.prop("disabled", true).val("");
        $comm.prop("disabled", true).prop("checked", false);
      }
      recalcSaldo();
    })
    .on("dblclick", ".monto-input", function() {
      $(this).val(formatCurrency(recalcSaldo()));
      recalcSaldo();
    })
    .on("input blur", ".monto-input", function(e) {
      if (e.type === "blur") {
        $(this).val(formatCurrency(parseNumber($(this).val())));
      }
      recalcSaldo();
    })
    .on("click", ".comisionCheck", function() {
      $tbody.find(".comisionCheck").not(this).prop("checked", false);
    });
  
    // ---------------------------
    // ENVÍO DE DEPÓSITOS (AJAX) CON SWEETALERT2
    // ---------------------------
    const sendPayload = payload =>
      $.ajax({
        url: "../php/post/depositos.php",
        method: "POST",
        contentType: "application/json",
        data: JSON.stringify(payload)
      }).then(
        data => data,
        xhr =>
          ((xhr.responseText || "").includes("Operation Succeeded"))
            ? $.Deferred().resolve().promise()
            : $.Deferred().reject().promise()
      );
  
    function executeRequests(desc) {
      $btnEnviar.prop("disabled", true);
      const calls = [];
      $tbody.find("tr").each(function() {
        const $row = $(this);
        if (!$row.find(".contratoCheck").is(":checked")) return;
        const amount = parseNumber($row.find(".monto-input").val());
        const code = $row.data("code");
        const depositPayload = {
          SubscriberCode: "2F4",
          ActionCode: 906,
          CompanyCode: $row.data("companyCode"),
          ContractCode: code,
          Amount: amount,
          CurrencyCode: "MXN",
          Description: desc
        };
        calls.push(sendPayload(depositPayload));
        if ($row.find(".comisionCheck").is(":checked")) {
          const pct = parseInt((code.split("-")[1] || "00").substring(0, 2), 10) / 10;
          const commissionAmount = parseNumber($total.val()) * (pct / 100) * 1.16;
          calls.push(sendPayload({
            ...depositPayload,
            ActionCode: 907,
            Amount: commissionAmount,
            Description: `COM${desc}`
          }));
        }
      });
      $.when(...calls)
        .then(() => {
          Swal.fire({ title: "Depósito exitoso", icon: "success" });
          resetForm();
        })
        .fail(() => {
          Swal.fire({ title: "Error al procesar", icon: "error" });
        })
        .always(() => $btnEnviar.prop("disabled", false));
    }
  
    $btnEnviar.on("click", () => {
      const description = `TB-${$("#tbFecha").val()}-${$("#tbExtra").val()}`;
      const totalVal = parseNumber($total.val());
      const saldoRest = recalcSaldo();
      const anySelected = $tbody.find(".contratoCheck:checked").length > 0;
      const extraEmpty = !$("#tbExtra").val().trim();
  
      if (!$compania.val().trim()) {
        return Swal.fire({ title: "Error", text: "Selecciona una compañía", icon: "warning" });
      }
      if (totalVal <= 0) {
        return Swal.fire({ title: "Error", text: "Total debe ser mayor a 0", icon: "warning" });
      }
      if (!anySelected) {
        return Swal.fire({ title: "Error", text: "Selecciona al menos un contrato", icon: "warning" });
      }
      if (saldoRest !== 0) {
        return Swal.fire({
          title: "Error",
          text: `Saldo no es 0 (${formatCurrency(saldoRest)}). Corrige antes de continuar.`,
          icon: "warning"
        });
      }
      if (extraEmpty) {
        Swal.fire({
          title: "Número de movimiento vacío",
          text: "¿Continuar sin él?",
          icon: "warning",
          showCancelButton: true,
          confirmButtonText: "Sí, continuar",
          cancelButtonText: "No, cancelar"
        }).then(result => {
          if (result.isConfirmed) {
            checkCommission(description);
          }
        });
      } else {
        checkCommission(description);
      }
    });
  
    function checkCommission(description) {
      const anyRequires = $tbody.find("tr").filter(function() {
        return $(this).data("requiresCommission") && $(this).find(".contratoCheck").is(":checked");
      }).length > 0;
      const anyCommChecked = $tbody.find(".comisionCheck:checked").length > 0;
      if (anyRequires && !anyCommChecked) {
        Swal.fire({
          title: "Falta aplicar comisión",
          text: "¿Continuar sin ella?",
          icon: "warning",
          showCancelButton: true,
          confirmButtonText: "Sí, continuar",
          cancelButtonText: "No, cancelar"
        }).then(result => {
          if (result.isConfirmed) {
            executeRequests(description);
          }
        });
      } else {
        executeRequests(description);
      }
    }
  
    function resetForm() {
      $compania.val("").removeData("companyId");
      $total.val("");
      $("#tbExtra").val("");
      const hoy = new Date();
      $("#tbFecha").val(
        String(hoy.getDate()).padStart(2, "0") +
        String(hoy.getMonth() + 1).padStart(2, "0") +
        hoy.getFullYear()
      );
      $tbody.empty();
      recalcSaldo();
    }
  });
  