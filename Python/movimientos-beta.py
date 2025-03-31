from flask import Flask, jsonify, request
from flask_cors import CORS
import requests
from concurrent.futures import ThreadPoolExecutor, as_completed
from requests.adapters import HTTPAdapter
from urllib3.util.retry import Retry
from datetime import datetime

app = Flask(__name__)
CORS(app)

# Sesión con reintentos automáticos
session = requests.Session()
retry_strategy = Retry(
    total=3,
    backoff_factor=1,
    status_forcelist=[500, 502, 503, 504],
)
adapter = HTTPAdapter(max_retries=retry_strategy)
session.mount("https://", adapter)

def obtener_token():
    try:
        response = session.get('http://localhost/tokengas/auth/token_handler.php', timeout=10)
        response.raise_for_status()
        return response.json().get('access_token')
    except requests.RequestException as e:
        print("Error obteniendo el token:", e)
        return None

def obtener_ids_contrato(token):
    ids = []
    page = 1
    headers = {"Authorization": f"Bearer {token}"}

    while True:
        # Aumentamos limit=200 (si la API lo permite)
        url = f"https://api-beta.ationet.com/CompanyContracts?page={page}&limit=200"
        try:
            response = session.get(url, headers=headers, timeout=10)
            response.raise_for_status()
            data = response.json().get('Content', [])
            if not data:
                break
            # Extraer IDs
            ids.extend([item['Id'] for item in data if 'Id' in item])
            page += 1
        except requests.RequestException as e:
            print("Error al obtener IDs:", e)
            break
    return ids

def obtener_movimientos_para_id(id_contract, date_from, date_to, token):
    movimientos = []
    page = 1
    headers = {"Authorization": f"Bearer {token}"}

    while True:
        # Aumentamos pageSize a 200
        url = (
            f"https://api-beta.ationet.com/Movements?idContract={id_contract}&dateFrom={date_from}"
            f"&dateTo={date_to}&amountFrom=0&amountTo=100000000&operationType=Money%20deposit%20to%20contract"
            f"&orderType=desc&page={page}&pageSize=200"
        )
        try:
            response = session.get(url, headers=headers, timeout=10)
            response.raise_for_status()
            content = response.json().get('Content', [])
            if not content:
                break

            # Comentamos la impresión de cada movimiento
            # for movimiento in content:
            #     print("Movimiento:", movimiento)

            movimientos.extend(content)
            page += 1
        except requests.RequestException as e:
            print(f"Error en contrato {id_contract} en la página {page}: {e}")
            break
    return movimientos

def obtener_fecha_mes(year, mes):
    ultimo_dia = {
        "02": "29" if int(year) % 4 == 0 and (int(year) % 100 != 0 or int(year) % 400 == 0) else "28",
        "04": "30", "06": "30", "09": "30", "11": "30"
    }.get(mes, "31")
    date_from = f"{year}/{mes}/01 00:00:00"
    date_to = f"{year}/{mes}/{ultimo_dia} 23:59:59"
    return date_from, date_to

@app.route('/obtener_movimientos', methods=['GET'])
def obtener_movimientos():
    mes = request.args.get('mes')
    year = request.args.get('year')
    if not mes or not year:
        return jsonify({"status": "error", "message": "Faltan parámetros (mes o year)"}), 400

    mes = mes.zfill(2)
    token = obtener_token()
    if not token:
        return jsonify({"status": "error", "message": "No se pudo obtener el token"}), 500

    ids = obtener_ids_contrato(token)
    if not ids:
        return jsonify({"status": "error", "message": "No se pudieron obtener los IDs de contrato"}), 500

    date_from, date_to = obtener_fecha_mes(year, mes)
    movimientos_totales = []

    # Aumentamos max_workers a 10
    with ThreadPoolExecutor(max_workers=10) as executor:
        futures = [
            executor.submit(obtener_movimientos_para_id, id_contract, date_from, date_to, token)
            for id_contract in ids
        ]
        for future in as_completed(futures):
            movimientos_totales.extend(future.result())

    # Ordenar por fecha descendente
    movimientos_totales.sort(
        key=lambda x: datetime.strptime(x["MovementDate"].split("T")[0], "%Y-%m-%d"),
        reverse=True
    )
    return jsonify({"status": "success", "movements": movimientos_totales})

@app.route('/proxy/companies', methods=['GET'])
def obtener_companias():
    try:
        # Suponiendo que no requiere token
        response = session.get('https://api-beta.ationet.com/companies', timeout=10)
        response.raise_for_status()
        data = response.json()
        return jsonify(data)
    except requests.RequestException as e:
        print("Error al obtener compañías:", e)
        return jsonify({"error": "No se pudieron obtener las compañías"}), 500

if __name__ == '__main__':
    app.run(debug=True, host="127.0.0.1", port=5000)
