let filaCount = 0;

const modelosPorTipo = {
  TAQ: {
    id: "946e5fa4-bf25-4690-9067-35dc78cbe8be",
    description: "TAG ATIONET RFID",
    requirePin: true,
    requiresPINChange: true,
    pinDigits: 4
  },
  TARJETA: {
    id: "ac353a14-8fae-4679-9bc7-899c6e22db29", // reemplazar con ID real si cambia
    description: "TARJETA ATIONET RFID",
    requirePin: true,
    requiresPINChange: false,
    pinDigits: 4
  }
};

function agregarFila() {
  filaCount++;
  const tbody = document.getElementById("tbody-form");
  const rowId = filaCount;

  const fila = document.createElement("tr");
  fila.innerHTML = `
    <td>
      <select id="tipo-${rowId}" class="form-select" onchange="actualizarModeloPorTipo(${rowId})">
        <option value="">-- Seleccione --</option>
        <option value="TAQ">TAQ</option>
        <option value="TARJETA">TARJETA</option>
      </select>
    </td>
    <td>
      <select id="contrato-${rowId}">
        <option>-- Seleccione --</option>
        <option value="1">Contrato A</option>
        <option value="2">Contrato B</option>
      </select>
    </td>
    <td><input type="text" id="etiqueta-${rowId}" oninput="generarPAN(${rowId})"></td>
    <td><input type="text" id="track-${rowId}" readonly></td>
    <td><input type="number" id="nip-${rowId}" value="1234"></td>
    <td><input type="checkbox" id="pinchange-${rowId}" checked></td>
    <td><button class="btn btn-danger btn-sm" onclick="this.closest('tr').remove()">🗑</button></td>
  `;
  tbody.appendChild(fila);
  actualizarUID(rowId);
}

function actualizarModeloPorTipo(rowId) {
  const tipo = document.getElementById(`tipo-${rowId}`).value;
  const modelo = modelosPorTipo[tipo];

  if (!modelo) return;

  document.getElementById(`nip-${rowId}`).disabled = !modelo.requirePin;
  document.getElementById(`pinchange-${rowId}`).checked = modelo.requiresPINChange;

  let hidden = document.getElementById(`modelo-id-${rowId}`);
  if (!hidden) {
    hidden = document.createElement("input");
    hidden.type = "hidden";
    hidden.id = `modelo-id-${rowId}`;
    hidden.name = "modeloId[]";
    document.getElementById(`track-${rowId}`).closest("tr").appendChild(hidden);
  }
  hidden.value = modelo.id;
}

function generarPAN(rowId) {
  const etiqueta = document.getElementById(`etiqueta-${rowId}`).value;
  const panInput = document.getElementById(`pan-${rowId}`);
  if (panInput) panInput.value = etiqueta.replace(/-/g, '');
}

function actualizarUID(rowId) {
  fetch('uid.txt?cache=' + new Date().getTime())
    .then(res => res.text())
    .then(uid => {
      const trackField = document.getElementById(`track-${rowId}`);
      if (trackField && uid.trim()) {
        trackField.value = uid.trim();
      }
    });
}

setInterval(() => {
  document.querySelectorAll('[id^=track-]').forEach(el => {
    const id = el.id.split('-')[1];
    actualizarUID(id);
  });
}, 1000);

window.agregarFila = agregarFila;