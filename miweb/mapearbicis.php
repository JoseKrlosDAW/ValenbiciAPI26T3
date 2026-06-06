<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mapa de Estaciones ValenBisi</title>

    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css">

    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>

    <style>
        body {
            margin: 0;
            font-family: Arial, sans-serif;
            text-align: center;
            background-color: #f4f1fb;
            color: #2d2438;
        }

        .contenedor {
            width: 92%;
            max-width: 1200px;
            margin: 30px auto;
            background-color: #ffffff;
            padding: 25px;
            border-radius: 16px;
            box-shadow: 0 8px 24px rgba(75, 46, 131, 0.18);
            border-top: 10px solid #6d4aff;
        }

        h1 {
            color: #4b2e83;
            font-size: 28px;
            margin-top: 0;
            margin-bottom: 10px;
        }

        .subtitulo {
            color: #6b5f78;
            font-size: 16px;
            margin-bottom: 20px;
        }

        #map {
            height: 600px;
            width: 100%;
            margin-top: 20px;
            border-radius: 14px;
            border: 2px solid #ded6ef;
            box-shadow: 0 4px 14px rgba(75, 46, 131, 0.18);
        }

        .boton-listado {
            display: inline-block;
            background-color: #ffffff;
            color: #4b2e83;
            text-decoration: none;
            padding: 12px 24px;
            border-radius: 30px;
            font-weight: bold;
            border: 2px solid #ded6ef;
            box-shadow: 0 4px 12px rgba(109, 74, 255, 0.25);
            transition: background-color 0.25s ease, color 0.25s ease, border-color 0.25s ease, box-shadow 0.25s ease;
        }

        .boton-listado:hover {
            background-color: #4b2e83;
            color: #ffffff;
            border-color: #4b2e83;
            box-shadow: 0 6px 16px rgba(75, 46, 131, 0.35);
        }

        .zona-busqueda {
            margin-top: 18px;
            margin-bottom: 10px;
        }

        .zona-busqueda input {
            padding: 10px;
            width: 260px;
            border: 2px solid #ded6ef;
            border-radius: 20px;
            font-size: 14px;
        }

        .zona-busqueda button {
            padding: 10px 18px;
            border: none;
            border-radius: 20px;
            background-color: #4b2e83;
            color: white;
            font-weight: bold;
            cursor: pointer;
        }

        .zona-busqueda button:hover {
            background-color: #6d4aff;
        }

        .leyenda {
            margin-top: 18px;
            display: flex;
            justify-content: center;
            flex-wrap: wrap;
            gap: 12px;
            color: #4b2e83;
            font-size: 14px;
        }

        .item-leyenda {
            background-color: #f7f3ff;
            padding: 8px 12px;
            border-radius: 20px;
            border: 1px solid #ded6ef;
        }
    </style>
</head>

<body>

<div class="contenedor">

    <h1>Mapeo de Bicicletas en Valencia</h1>

    <p class="subtitulo">
        Mapa de estaciones ValenBisi con colores según las bicicletas disponibles.
    </p>

    <a href="index.php" class="boton-listado">Volver al listado</a>

    <div class="zona-busqueda">
        <input type="text" id="busqueda" placeholder="Buscar por dirección">
        <button onclick="buscarEstacion()">Buscar estación</button>
    </div>

    <div id="map"></div>

    <div class="leyenda">
        <div class="item-leyenda">Rojo: menos de 5 bicicletas</div>
        <div class="item-leyenda">Naranja: de 5 a 9 bicicletas</div>
        <div class="item-leyenda">Amarillo: de 10 a 19 bicicletas</div>
        <div class="item-leyenda">Verde: 20 o más bicicletas</div>
    </div>

</div>

<script>
    // Inicializa el mapa centrado en Valencia
    var map = L.map('map').setView([39.47, -0.37], 13);

    // Añadir capa base de OpenStreetMap
    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors'
    }).addTo(map);

    // Array donde guardaremos los marcadores para poder buscarlos después
    var marcadores = [];

    // Función para definir el color del marcador según las bicicletas disponibles
    function getMarkerColor(available) {
        if (available < 5) {
            return 'red';
        } else if (available >= 5 && available < 10) {
            return 'orange';
        } else if (available >= 10 && available < 20) {
            return 'yellow';
        } else {
            return 'green';
        }
    }

    // Cargar el archivo data.json
    fetch('data.json')
        .then(response => {
            if (!response.ok) {
                throw new Error(`Error al cargar data.json: ${response.statusText}`);
            }

            return response.json();
        })
        .then(data => {
            // Iterar sobre las estaciones y agregar marcadores al mapa
            Object.values(data).forEach(station => {
                const { lat, lon, address, available, free, total } = station;

                if (lat && lon) {
                    var colorMarcador = getMarkerColor(available);

                    // Crear un círculo con un color dependiendo de las bicicletas disponibles
                    var marcador = L.circleMarker([lat, lon], {
                        color: colorMarcador,
                        fillColor: colorMarcador,
                        radius: 8,
                        fillOpacity: 0.8
                    })
                    .addTo(map)
                    .bindPopup(`
                        <strong>${address}</strong><br><br>
                        <b>Bicicletas disponibles:</b> ${available}<br>
                        <b>Huecos libres:</b> ${free}<br>
                        <b>Capacidad total:</b> ${total}
                    `);

                    // Guardamos cada marcador junto con su dirección para poder buscarlo
                    marcadores.push({
                        direccion: address.toLowerCase(),
                        latitud: lat,
                        longitud: lon,
                        marcador: marcador
                    });
                }
            });
        })
        .catch(error => {
            console.error('Error cargando los datos:', error);
        });

    // Función para buscar una estación por dirección
    function buscarEstacion() {
        var textoBusqueda = document.getElementById("busqueda").value.toLowerCase().trim();

        if (textoBusqueda === "") {
            alert("Escribe una dirección para buscar.");
            return;
        }

        for (var i = 0; i < marcadores.length; i++) {
            if (marcadores[i].direccion.includes(textoBusqueda)) {
                map.setView([marcadores[i].latitud, marcadores[i].longitud], 17);
                marcadores[i].marcador.openPopup();
                return;
            }
        }

        alert("No se ha encontrado ninguna estación con ese texto.");
    }
</script>

</body>
</html>