from flask import Flask, jsonify, request
from flask_cors import CORS
import requests
from concurrent.futures import ThreadPoolExecutor, as_completed
from requests.adapters import HTTPAdapter
from urllib3.util.retry import Retry
from datetime import datetime

app = Flask(__name__)
CORS(app)

# Configuración de la sesión con reintentos automáticos
session = requests.Session()
retry_strategy = Retry(
    total=3,
    backoff_factor=1,
    status_forcelist=[500, 502, 503, 504],
)
adapter = HTTPAdapter(max_retries=retry_strategy)
session.mount("https://", adapter)

def obtener_token():
    """
    Llama a tu endpoint local 'token_handler.php' para obtener el token.
    """
    try:
        response = session.get('http://localhost/tokengas/auth/token_handler.php', timeout=10)
        response.raise_for_status()
        return response.json().get('access_token')
    except requests.RequestException as e:
        print("Error obteniendo el token:", e)
        return None

def obtener_ids_contrato(token):
    """
    Itera sobre las páginas de CompanyContracts (limit=200).
    Imprime la página y la cantidad de contratos obtenidos.
    """
    ids = []
    page = 1
    headers = {"Authorization": f"Bearer {token}"}

    while True:
        url = f"https://api.ationet.com/CompanyContracts?page={page}&limit=200"
        print(f"Obteniendo contratos de la página {page} -> {url}")
        try:
            response = session.get(url, headers=headers, timeout=10)
            response.raise_for_status()
            data = response.json().get('Content', [])

            print(f" - Se obtuvieron {len(data)} contratos en la página {page}")

            if not data:
                print("No hay más contratos. Saliendo del bucle de contratos.")
                break

            # Extraer IDs
            ids.extend([item['Id'] for item in data if 'Id' in item])
            page += 1
        except requests.RequestException as e:
            print("Error al obtener IDs en la página", page, ":", e)
            break

    print(f"Total de contratos obtenidos: {len(ids)}")
    return ids

def obtener_movimientos_para_id(id_contract, date_from, date_to, token):
    """
    Para un contrato dado, itera sobre las páginas de Movements (pageSize=50).
    Imprime la página y la cantidad de movimientos obtenidos.
    """
    movimientos = []
    page = 1
    headers = {"Authorization": f"Bearer {token}"}

    while True:
        url = (
            f"https://api.ationet.com/Movements?idContract={id_contract}"
            f"&dateFrom={date_from}&dateTo={date_to}"
            f"&amountFrom=0&amountTo=100000000"
            f"&operationType=Money%20deposit%20to%20contract"
            f"&orderType=desc&page={page}&pageSize=50"
        )
        print(f"[{id_contract}] Página {page} -> {url}")
        try:
            response = session.get(url, headers=headers, timeout=10)
            response.raise_for_status()
            content = response.json().get('Content', [])

            print(f" - Se obtuvieron {len(content)} movimientos en la página {page} para el contrato {id_contract}")

            if not content:
                print(f"No hay más movimientos para {id_contract} en la página {page}. Saliendo.")
                break

            movimientos.extend(content)
            page += 1

        except requests.RequestException as e:
            print(f"Error en contrato {id_contract}, página {page}: {e}")
            break

    return movimientos

def obtener_fecha_mes(year, mes):
    """
    Determina la fecha inicial y final de un mes, considerando bisiestos para febrero.
    """
    ultimo_dia = {
        "02": "29" if int(year) % 4 == 0 and (int(year) % 100 != 0 or int(year) % 400 == 0) else "28",
        "04": "30", "06": "30", "09": "30", "11": "30"
    }.get(mes, "31")
    date_from = f"{year}/{mes}/01 00:00:00"
    date_to = f"{year}/{mes}/{ultimo_dia} 23:59:59"
    return date_from, date_to

@app.route('/obtener_movimientos', methods=['GET'])
def obtener_movimientos():
    """
    Endpoint principal: recibe 'mes' y 'year' por query params,
    obtiene token, recorre contratos, luego movimientos para cada contrato.
    Devuelve JSON con la lista total de movimientos.
    """
    mes = request.args.get('mes')
    year = request.args.get('year')
    if not mes or not year:
        return jsonify({"status": "error", "message": "Faltan parámetros (mes o year)"}), 400

    mes = mes.zfill(2)
    token = obtener_token()
    if not token:
        return jsonify({"status": "error", "message": "No se pudo obtener el token"}), 500

    # Obtener todos los contratos paginados
    ids = obtener_ids_contrato(token)
    if not ids:
        return jsonify({"status": "error", "message": "No se pudieron obtener los IDs de contrato"}), 500

    date_from, date_to = obtener_fecha_mes(year, mes)
    movimientos_totales = []

    # Procesar cada contrato en paralelo (max_workers=10)
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
    """
    Ejemplo de endpoint proxy para 'companies' si no requiere token.
    """
    try:
        response = session.get('https://api.ationet.com/companies', timeout=10)
        response.raise_for_status()
        data = response.json()
        return jsonify(data)
    except requests.RequestException as e:
        print("Error al obtener compañías:", e)
        return jsonify({"error": "No se pudieron obtener las compañías"}), 500

if __name__ == '__main__':
    app.run(debug=True, host="127.0.0.1", port=5000)
