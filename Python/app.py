from flask import Flask, request

app = Flask(__name__)
cola_uid = []

@app.route('/recibir', methods=['POST'])
def recibir_track():
    track = request.form.get('track', '').strip()
    if track:
        cola_uid.append(track)
        return f"Track recibido: {track}", 200
    return "No se recibió ningún track", 400

@app.route('/recibir_nfc_test', methods=['GET'])
def recibir_nfc_test():
    if cola_uid:
        return cola_uid.pop(0)  # devuelve el primero y lo elimina
    return "Esperando tarjeta NFC", 200
