<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Disponibilidad de ValenBisi</title>
    <link rel="stylesheet" href="estilos.css">
</head>

<body>

<body>

<div class="contenedor">

<h1>Disponibilidad de ValenBisi</h1>
<p class="subtitulo">Consulta actualizada de estaciones, bicicletas disponibles y huecos libres en la ciudad de Valencia.</p>

<?php

// Pedimos directamente la geometría en WGS84 (EPSG:4326), que es el formato usado por la mayoría de mapas web:
// x = longitud, y = latitud.
$baseUrl = "https://geoportal.valencia.es/server/rest/services/OPENDATA/Trafico/MapServer/228/query?where=1=1&outFields=*&returnGeometry=true&outSR=4326&f=json";

/**
 * Convierte coordenadas UTM ETRS89 / zona 30N (EPSG:25830) a WGS84 (EPSG:4326).
 *
 * La capa actual de ValenBisi trabaja en EPSG:25830. Aunque arriba solicitamos outSR=4326,
 * mantenemos esta función como respaldo profesional por si la API devolviera de nuevo X/Y proyectadas.
 *
 * @return array{latitude: float, longitude: float}
 */
function epsg25830ToWgs84(float $easting, float $northing): array
{
    // Parámetros GRS80 / ETRS89. Para este uso urbano, la diferencia práctica con WGS84 es despreciable.
    $a = 6378137.0;
    $f = 1 / 298.257222101;
    $k0 = 0.9996;
    $zone = 30;
    $falseEasting = 500000.0;
    $falseNorthing = 0.0;

    $e = sqrt($f * (2 - $f));
    $e1sq = ($e * $e) / (1 - $e * $e);

    $x = $easting - $falseEasting;
    $y = $northing - $falseNorthing;

    $m = $y / $k0;
    $mu = $m / ($a * (1 - pow($e, 2) / 4 - 3 * pow($e, 4) / 64 - 5 * pow($e, 6) / 256));

    $e1 = (1 - sqrt(1 - $e * $e)) / (1 + sqrt(1 - $e * $e));

    $j1 = 3 * $e1 / 2 - 27 * pow($e1, 3) / 32;
    $j2 = 21 * pow($e1, 2) / 16 - 55 * pow($e1, 4) / 32;
    $j3 = 151 * pow($e1, 3) / 96;
    $j4 = 1097 * pow($e1, 4) / 512;

    $fp = $mu
        + $j1 * sin(2 * $mu)
        + $j2 * sin(4 * $mu)
        + $j3 * sin(6 * $mu)
        + $j4 * sin(8 * $mu);

    $sinFp = sin($fp);
    $cosFp = cos($fp);
    $tanFp = tan($fp);

    $c1 = $e1sq * $cosFp * $cosFp;
    $t1 = $tanFp * $tanFp;

    $r1 = $a * (1 - $e * $e) / pow(1 - ($e * $e * $sinFp * $sinFp), 1.5);
    $n1 = $a / sqrt(1 - ($e * $e * $sinFp * $sinFp));

    $d = $x / ($n1 * $k0);

    $latRad = $fp - ($n1 * $tanFp / $r1) * (
        pow($d, 2) / 2
        - (5 + 3 * $t1 + 10 * $c1 - 4 * $c1 * $c1 - 9 * $e1sq) * pow($d, 4) / 24
        + (61 + 90 * $t1 + 298 * $c1 + 45 * $t1 * $t1 - 252 * $e1sq - 3 * $c1 * $c1) * pow($d, 6) / 720
    );

    $lonOrigin = deg2rad(($zone - 1) * 6 - 180 + 3);

    $lonRad = $lonOrigin + (
        $d
        - (1 + 2 * $t1 + $c1) * pow($d, 3) / 6
        + (5 - 2 * $c1 + 28 * $t1 - 3 * $c1 * $c1 + 8 * $e1sq + 24 * $t1 * $t1) * pow($d, 5) / 120
    ) / $cosFp;

    return [
        'latitude' => rad2deg($latRad),
        'longitude' => rad2deg($lonRad),
    ];
}

/**
 * Normaliza la geometría de ValenBisi para que el JSON siempre tenga latitude/longitude.
 * Si la API devuelve EPSG:4326, usa x/y directamente. Si devuelve EPSG:25830, convierte desde UTM.
 *
 * @param array<string, mixed> $geometry
 * @return array{latitude: float, longitude: float, source_x: float, source_y: float}
 */
function normalizeValenbisiGeometry(array $geometry): array
{
    $x = isset($geometry['x']) ? (float)$geometry['x'] : 0.0;
    $y = isset($geometry['y']) ? (float)$geometry['y'] : 0.0;

    // En EPSG:4326 Valencia estará aproximadamente en lon -0.x y lat 39.x.
    $looksLikeLonLat = ($x >= -180 && $x <= 180 && $y >= -90 && $y <= 90);

    if ($looksLikeLonLat) {
        return [
            'latitude' => $y,
            'longitude' => $x,
            'source_x' => $x,
            'source_y' => $y,
        ];
    }

    $converted = epsg25830ToWgs84($x, $y);

    return [
        'latitude' => $converted['latitude'],
        'longitude' => $converted['longitude'],
        'source_x' => $x,
        'source_y' => $y,
    ];
}

$allStations = [];
$errorOccurred = false;

$url = $baseUrl;

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, ["Accept: application/json"]);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false); // Desactivar la verificación del certificado SSL. Solo para desarrollo.

$response = curl_exec($ch);

if ($response === false) {
    echo "<p style='color: red; text-align: center;'>Error en cURL: " . curl_error($ch) . "</p>";
    $errorOccurred = true;
    die("<p>No hay resultados</p>");
}

$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

if ($httpCode != 200) {
    echo "<p style='color: red; text-align: center;'>Error en la solicitud a la API (Código HTTP: " . $httpCode . "). URL: " . $url . "</p>";
    $errorOccurred = true;
    die("<p>No hay resultados</p>");
}

curl_close($ch);

$data = json_decode($response, true);

if ($data === null) {
    echo "<p style='color: red; text-align: center;'>Error al decodificar la respuesta JSON. Response: " . htmlspecialchars($response) . "</p>";
    $errorOccurred = true;
    die("<p>No hay resultados</p>");
}

if (isset($data["features"]) && is_array($data["features"]) && count($data["features"]) > 0) {
    foreach ($data["features"] as $station) {
        $geometry = normalizeValenbisiGeometry($station['geometry'] ?? []);

        $allStations[$station['attributes']['number']] = [
            'address' => $station['attributes']['address'],
            'open' => ($station['attributes']['open'] == "T"),
            'available' => (int)$station['attributes']['available'],
            'free' => (int)$station['attributes']['free'],
            'total' => (int)$station['attributes']['total'],
            'updated_at' => $station['attributes']['updated_at'],

            // Coordenadas listas para pintar en mapas web: Leaflet, Google Maps, Mapbox, etc.
            'latitude' => round($geometry['latitude'], 7),
            'longitude' => round($geometry['longitude'], 7),

            // Mantengo las coordenadas originales por trazabilidad y depuración.
            'lon' => $geometry['source_x'],
            'lat' => $geometry['source_y']
        ];
    }
} else {
    echo "<p style='color: orange; text-align: center;'>No hay resultados en esta página o el formato de la respuesta es incorrecto.</p>";
    var_dump($data); // Imprime $data para depuración.
    die("<p>No hay resultados</p>");
}

if (!$errorOccurred && !empty($allStations)) {
    // Usamos !empty() para verificar si $allStations tiene elementos.
    $filePath = getcwd() . '/data.json';

    if (file_put_contents($filePath, json_encode($allStations))) {
        echo "<p style='color: green; text-align: center;'>Datos guardados en: " . $filePath . "</p>";
    } else {
        echo "<p style='color: red; text-align: center;'>Error al guardar el archivo data.json. Verifica los permisos de escritura.</p>";
    }
} elseif (!$errorOccurred && empty($allStations)) {
    echo "<p style='color: orange; text-align: center;'>No se encontraron datos de estaciones.</p>";
}

if (!empty($allStations)) {
    echo "<table>";
    echo "<tr>";
    echo "<th>Dirección</th>";
    echo "<th>Número</th>";
    echo "<th>Abierto</th>";
    echo "<th>Disponibles</th>";
    echo "<th>Libres</th>";
    echo "<th>Total</th>";
    echo "<th>Actualizado</th>";
    echo "<th>Latitud</th>";
    echo "<th>Longitud</th>";
    echo "</tr>";

    foreach ($allStations as $number => $station) {
        echo "<tr>";
        echo "<td><strong>Dirección:</strong> " . htmlspecialchars($station['address']) . "</td>";
        echo "<td>" . $number . "</td>";
        echo "<td>" . ($station['open'] ? "Sí" : "No") . "</td>";
        echo "<td>" . $station['available'] . "</td>";
        echo "<td>" . $station['free'] . "</td>";
        echo "<td>" . $station['total'] . "</td>";
        echo "<td>" . $station['updated_at'] . "</td>";
        echo "<td>" . $station['latitude'] . "</td>";
        echo "<td>" . $station['longitude'] . "</td>";
        echo "</tr>";
    }

    echo "</table>";
}

?>

<div class="boton-contenedor">
    <a class="boton-mapa" href="mapearbicis.php">Ver mapa de estaciones</a>
</div>

</div>

</body>
</html>