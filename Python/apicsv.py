
import pandas as pd
import aiohttp
import asyncio
import sys
import requests
from urllib.parse import urlencode

BASE_URL = "https://api.ationet.com/Transactions"
API_KEY = "Dk2TUUNw_cpnX_5ZQZMou7PBA88HQuSsuG7W94ZXaWB-detDs8PLN3PVrL81kQe8bhfwUGYhkL6xIvx-Lloi6hxN6U8V21rv-RIGFDthBruKXzb6QFcIRYKAD78vgGwb7cllfsdrOkrchVGSVOxriNf0JoA8JZOR0r_dp_8-wie0F5XOTB1zWPVx8oa1VXSVzP1DPe6vOkXiGVlVSfkDgKiy5ak8veVDuVU7WPEt_J96SrNT84yLcqV9iogneb9kkiPN34oloNMsiL0jVeO4Fnoxwec47qyfeCNx4pdArfB0ESt8NxbUst7o-JRBvUaTn0zvjGHQrt1gfcDqQFZHlqe09ibZXQfGySIM59_vUWBjawnoUYMJnp2k7mLMCXwrF7GJ_SM6D-uPq1Fpvtn7NCpF_To3xHob04ZeodT1AJ9rRfInfWpugC0tPFpLRJFP0XBZSsfbnLmeQ2tkXDewZhB9ot3ortOSwDmXb_wuZrv6E8azORYgrL2_qRE7p55QkvdqtTaQP4-nDocVlAAEtvvE60J04i2Qtj438WgpCj7UB1QLfYo46P72zchGc2V331O9a2ojXIkyigrqFVoRFILXruR-bW_8-Y4EunsShBs0PbIG3KxD_RcdBMh2YqZp0LspXrbY0MKEdUT0YVf_FyG1rD1qZ_aIUD6EEOkFpG70Fb3Cz_XZzQl0ZrOzfpl72XKBerQzkRiEGtxOmsN4omXyzZmAHDNngEJ9REeQspK9wO3wIKhqNlAjMOR2Mcf6EIMxVPX0_VvJbEQQ2M_g3YWfDi7zO7sw22XlovNmcdB84mwFgSbbMtSKRr3YC6X-4cqfbl9Dvs1yOXdnQMcgOKdMKO0nyLYC5UZ51ygX1grnX1bE7_TShvLcwb94MizKpbb3CDGDlUEE6coBPl0XUkWucZ9Sz5uXfxn7s2Pqqg6yAsMH_7U2qAtpi6mxEOm7_h0bGEQ9KFAbOaY8dPUA62ecMO3kgn3delYTM4SSONn0tKXaAuGJDcqg3hjhbdHjK1yYf3nnbBkAAF3qzMAzhnGej002Gqe2dYvaS_ZrLKXBTlTKQHC-yrXKycvs7tFZbQV3f2OtwS8PUhiHIuMh9T-hO7Wn3YnqVb_he92Xj1wli-2l3MaYDpQYCKHsSMyGlpBbLb7loobHACal_Nnm0zP7ljTKuCixNkn1yW6ItKESuDj3f1GJemomNMASt7MQCrA5iPH6RYov0tr056h7w5QSNtF6LTJWZxxrFkQKpVlGoL6afwuHPj9HaEQqMmMwTdQlxvnfEjXzYNrSg3d5vK2PtzHEcq8iJcgdhp5wsn6WwLAhwcoro4QyTAsI7GUi_Wjl2PIwKKuzYlsOPfkLe59xF60x0p9dJaAFEEgiTvWkxlkcfRCSyPw7eq0Ehk3b0Ghel4-wI5RXY1lwCj7dXmzB1LbBqRWjOGN3M9-xK8dHappvuyzum7uKAvPz-iNf-usVOTqxC_WrR00MSJx8o42OAPUy6meFRgzvanmJ1sItU5DPU3q0J7XJwMJII2DVmKglWRAJW939fS_NsI5oZJikDMo5xNuFObC5aOD5Hp288W-_0K3YkdaA9Bj_HcFhp6x7niHWumtQ7cwF5ZzpDjqk6ABCvixzFgqaGLKzp2rlLI2d-pGruU0u_fhXa2hcGkOmeiz3sgiIYmhpTWPbOKnNDN4qm79m8aODlCpT-3HnfPJCkxAxsrqFLCgJfoO02thqLV3RIEs0bhELaQljCZM8zZHR7fTQ0Caexf5T4Pn0J6P8NqOEFHN2oxDkYx480GhFoIpHSlMId25WhYucIOBvd0fR-l9uzEQCYemugcSADQX9fZwEjHU09Jwv2jc7ECeHHF2qI7t4WQv0KugywlzpzKJxhmx4ncuDFNdB71c6fnt1Lu-Qw5VETo5lCUqjXtXaneQLR0fmstSXe5MSZP1xRx2V4BQEwEC9ckfAZXUcjrhFojvAgsS26iKo_MG2"  

headers = {
    "Authorization": f"Bearer {API_KEY}",
    "Content-Type": "application/json"
}

def formatear_fecha(fecha, inicio=True):
    hora = "00:00:00" if inicio else "23:59:59"
    return fecha.replace('-', '/') + f" {hora}"

async def fetch_page(session, url, semaphore, page):
    async with semaphore:
        try:
            async with session.get(url, headers=headers) as response:
                response.raise_for_status()
                data = await response.json()
                print(f"✅ Página {page} descargada ({len(data.get('Content', []))} registros)")
                return data.get('Content', [])
        except Exception as e:
            print(f"❌ Error en página {page}: {e}")
            return []

def movimientos_a_dataframe(movimientos):
    registros = []
    for mov in movimientos:
        registros.append({
            'Fecha': mov.get('DateTime', 'N/A'),
          #  'Comercio': mov.get('MerchantName', 'N/A'),
            'Estacion': mov.get('SiteName', 'N/A'),
            'Compañia': mov.get('CompanyName', 'N/A'),
            'Litros': mov.get('ProductVolumeDispensed', 0),
            'Costo por Litro': mov.get('ProductUnitPriceDispensed', 0)
        })
    return pd.DataFrame(registros)

async def obtener_todas_las_paginas(fecha_desde, fecha_hasta, total_pages):
    semaphore = asyncio.Semaphore(5)  # Control de peticiones simultáneas
    async with aiohttp.ClientSession() as session:
        tareas = []
        for page in range(1, total_pages + 1):
            params = {
                "dateTimeFrom": fecha_desde,
                "dateTimeTo": fecha_hasta,
                "page": page,
                "pageSize": 50,
                "paginate": "true"
            }
            url = f"{BASE_URL}?{urlencode(params)}"
            tareas.append(fetch_page(session, url, semaphore, page))

        movimientos_totales = []
        for tarea in asyncio.as_completed(tareas):
            contenido = await tarea
            movimientos_totales.extend(contenido)
        return movimientos_totales

def obtener_numero_paginas(fecha_desde, fecha_hasta):
    params = {
        "dateTimeFrom": fecha_desde,
        "dateTimeTo": fecha_hasta,
        "page": 1,
        "pageSize": 50,
        "paginate": "true"
    }
    url = f"{BASE_URL}?{urlencode(params)}"
    print(f"🔍 URL de prueba: {url}")  # Log claro para verificar
    response = requests.get(url, headers=headers)
    response.raise_for_status()
    data = response.json()
    total_pages = data.get('TotalPages', 1)
    print(f"🚀 Total de páginas a descargar: {total_pages}")
    return total_pages

def procesar_y_guardar_csv(fecha_desde, fecha_hasta):
    fecha_desde_fmt = formatear_fecha(fecha_desde, True)
    fecha_hasta_fmt = formatear_fecha(fecha_hasta, False)

    total_pages = obtener_numero_paginas(fecha_desde_fmt, fecha_hasta_fmt)
    movimientos_totales = asyncio.run(obtener_todas_las_paginas(fecha_desde_fmt, fecha_hasta_fmt, total_pages))

    if movimientos_totales:
        df_final = movimientos_a_dataframe(movimientos_totales)
        nombre_archivo = f"movimientos_{fecha_desde}_a_{fecha_hasta}.csv"
        df_final.to_csv(nombre_archivo, index=False, encoding='utf-8-sig')
        print(f"🎉 Archivo guardado exitosamente: {nombre_archivo} ({len(df_final)} registros)")
    else:
        print("⚠️ No se obtuvieron movimientos.")

if __name__ == "__main__":
    if len(sys.argv) != 3:
        print("Uso: python apicsv.py YYYY-MM-DD YYYY-MM-DD")
        sys.exit(1)

    fecha_desde = sys.argv[1]
    fecha_hasta = sys.argv[2]

    procesar_y_guardar_csv(fecha_desde, fecha_hasta)
