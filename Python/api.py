import sys
import asyncio
import aiohttp

# Recibir las fechas desde los argumentos proporcionados por PHP
dateTimeFrom = sys.argv[1]
dateTimeTo = sys.argv[2]

# Configuración de la API
BASE_URL = "https://api.ationet.com/Transactions"
API_KEY = "Dk2TUUNw_cpnX_5ZQZMou7PBA88HQuSsuG7W94ZXaWB-detDs8PLN3PVrL81kQe8bhfwUGYhkL6xIvx-Lloi6hxN6U8V21rv-RIGFDthBruKXzb6QFcIRYKAD78vgGwb7cllfsdrOkrchVGSVOxriNf0JoA8JZOR0r_dp_8-wie0F5XOTB1zWPVx8oa1VXSVzP1DPe6vOkXiGVlVSfkDgKiy5ak8veVDuVU7WPEt_J96SrNT84yLcqV9iogneb9kkiPN34oloNMsiL0jVeO4Fnoxwec47qyfeCNx4pdArfB0ESt8NxbUst7o-JRBvUaTn0zvjGHQrt1gfcDqQFZHlqe09ibZXQfGySIM59_vUWBjawnoUYMJnp2k7mLMCXwrF7GJ_SM6D-uPq1Fpvtn7NCpF_To3xHob04ZeodT1AJ9rRfInfWpugC0tPFpLRJFP0XBZSsfbnLmeQ2tkXDewZhB9ot3ortOSwDmXb_wuZrv6E8azORYgrL2_qRE7p55QkvdqtTaQP4-nDocVlAAEtvvE60J04i2Qtj438WgpCj7UB1QLfYo46P72zchGc2V331O9a2ojXIkyigrqFVoRFILXruR-bW_8-Y4EunsShBs0PbIG3KxD_RcdBMh2YqZp0LspXrbY0MKEdUT0YVf_FyG1rD1qZ_aIUD6EEOkFpG70Fb3Cz_XZzQl0ZrOzfpl72XKBerQzkRiEGtxOmsN4omXyzZmAHDNngEJ9REeQspK9wO3wIKhqNlAjMOR2Mcf6EIMxVPX0_VvJbEQQ2M_g3YWfDi7zO7sw22XlovNmcdB84mwFgSbbMtSKRr3YC6X-4cqfbl9Dvs1yOXdnQMcgOKdMKO0nyLYC5UZ51ygX1grnX1bE7_TShvLcwb94MizKpbb3CDGDlUEE6coBPl0XUkWucZ9Sz5uXfxn7s2Pqqg6yAsMH_7U2qAtpi6mxEOm7_h0bGEQ9KFAbOaY8dPUA62ecMO3kgn3delYTM4SSONn0tKXaAuGJDcqg3hjhbdHjK1yYf3nnbBkAAF3qzMAzhnGej002Gqe2dYvaS_ZrLKXBTlTKQHC-yrXKycvs7tFZbQV3f2OtwS8PUhiHIuMh9T-hO7Wn3YnqVb_he92Xj1wli-2l3MaYDpQYCKHsSMyGlpBbLb7loobHACal_Nnm0zP7ljTKuCixNkn1yW6ItKESuDj3f1GJemomNMASt7MQCrA5iPH6RYov0tr056h7w5QSNtF6LTJWZxxrFkQKpVlGoL6afwuHPj9HaEQqMmMwTdQlxvnfEjXzYNrSg3d5vK2PtzHEcq8iJcgdhp5wsn6WwLAhwcoro4QyTAsI7GUi_Wjl2PIwKKuzYlsOPfkLe59xF60x0p9dJaAFEEgiTvWkxlkcfRCSyPw7eq0Ehk3b0Ghel4-wI5RXY1lwCj7dXmzB1LbBqRWjOGN3M9-xK8dHappvuyzum7uKAvPz-iNf-usVOTqxC_WrR00MSJx8o42OAPUy6meFRgzvanmJ1sItU5DPU3q0J7XJwMJII2DVmKglWRAJW939fS_NsI5oZJikDMo5xNuFObC5aOD5Hp288W-_0K3YkdaA9Bj_HcFhp6x7niHWumtQ7cwF5ZzpDjqk6ABCvixzFgqaGLKzp2rlLI2d-pGruU0u_fhXa2hcGkOmeiz3sgiIYmhpTWPbOKnNDN4qm79m8aODlCpT-3HnfPJCkxAxsrqFLCgJfoO02thqLV3RIEs0bhELaQljCZM8zZHR7fTQ0Caexf5T4Pn0J6P8NqOEFHN2oxDkYx480GhFoIpHSlMId25WhYucIOBvd0fR-l9uzEQCYemugcSADQX9fZwEjHU09Jwv2jc7ECeHHF2qI7t4WQv0KugywlzpzKJxhmx4ncuDFNdB71c6fnt1Lu-Qw5VETo5lCUqjXtXaneQLR0fmstSXe5MSZP1xRx2V4BQEwEC9ckfAZXUcjrhFojvAgsS26iKo_MG2"  
params = {
    "dateTimeFrom": dateTimeFrom,
    "dateTimeTo": dateTimeTo,
    "pageSize": 50,
    "paginate": "true"
}
headers = {
    "Authorization": f"Bearer {API_KEY}",
    "Content-Type": "application/json"
}

async def fetch_page(session, page, totals):
    """Obtiene y procesa una página específica de transacciones."""
    local_params = params.copy()
    local_params["page"] = page
    async with session.get(BASE_URL, headers=headers, params=local_params) as response:
        if response.status != 200:
            print(f"Error en la página {page}: {response.status}")
            return None

        data = await response.json()
        transactions = data.get("Content", [])
        for trans in transactions:
            fuel_code = trans.get("FuelCode")
            volume = float(trans.get("ProductVolumeDispensed", 0))
            if fuel_code in totals:
                totals[fuel_code] += volume
            else:
                totals[fuel_code] = volume

        total_pages = data.get("TotalPages", 1)
        return total_pages

async def fetch_all_transactions():
    """Procesa todas las páginas en paralelo para sumar litros."""
    totals = {}
    async with aiohttp.ClientSession() as session:
        # Procesar la primera página para determinar el total de páginas
        total_pages = await fetch_page(session, 1, totals)
        if not total_pages:
            return totals

        # Procesar las demás páginas en paralelo
        tasks = [fetch_page(session, page, totals) for page in range(2, total_pages + 1)]
        await asyncio.gather(*tasks)

    return totals

async def main():
    # Sumar directamente los litros vendidos
    totals = await fetch_all_transactions()

    # Mapear códigos de combustible a nombres
    fuel_mapping = {"10100": "REGULAR", "10300": "PREMIUM", "10400": "DIESEL"}
    for code, total in totals.items():
        product_name = fuel_mapping.get(code, "DESCONOCIDO")
        print(f"{product_name}: {total:.2f} litros")

if __name__ == "__main__":
    asyncio.run(main())
