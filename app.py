from smartcard.System import readers
from smartcard.Exceptions import NoCardException, CardConnectionException
import time
import os

def main():
    print("🟢 Lector iniciado. Escanea una tarjeta...")

    r = readers()
    if not r:
        print("❌ No se encontraron lectores NFC.")
        return

    lector = r[0]
    conexion = lector.createConnection()
    uid_anterior = ""

    while True:
        try:
            conexion.connect()
            comando_uid = [0xFF, 0xCA, 0x00, 0x00, 0x00]
            datos, sw1, sw2 = conexion.transmit(comando_uid)
            if sw1 == 0x90:
                uid = ''.join(f"{byte:02X}" for byte in datos)
                if uid != uid_anterior:
                    print(f"✅ UID leído: {uid}")
                    with open("uid.txt", "w") as f:
                        f.write(uid)
                    uid_anterior = uid
            time.sleep(0.4)
        except NoCardException:
            if uid_anterior:
                # Tarjeta fue retirada → limpiar
                with open("uid.txt", "w") as f:
                    f.write("")
                uid_anterior = ""
            time.sleep(0.2)
        except CardConnectionException:
            time.sleep(0.5)
        except Exception as e:
            print(f"❌ Error inesperado: {e}")
            time.sleep(1)

if __name__ == "__main__":
    # Limpia UID al iniciar
    if os.path.exists("uid.txt"):
        with open("uid.txt", "w") as f:
            f.write("")
    main()
