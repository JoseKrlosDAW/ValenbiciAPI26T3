package es.gva.edu.iesjuandegaray.bicis;

import org.apache.http.HttpEntity;
import org.apache.http.HttpResponse;
import org.apache.http.client.methods.HttpGet;
import org.apache.http.impl.client.CloseableHttpClient;
import org.apache.http.impl.client.HttpClients;
import org.apache.http.util.EntityUtils;
import org.apache.http.conn.ssl.SSLConnectionSocketFactory;
import org.apache.http.conn.ssl.TrustAllStrategy;
import org.apache.http.conn.ssl.NoopHostnameVerifier;
import org.apache.http.ssl.SSLContexts;

import javax.net.ssl.SSLContext;

import org.json.JSONArray;
import org.json.JSONObject;

import java.io.IOException;

public class DatosJSon {

    private static String API_URL;
    private String datos = "";
    private String[] values;
    private int numEst;

    public DatosJSon(int nE) {
        numEst = nE;
        datos = "";
        API_URL = "https://geoportal.valencia.es/server/rest/services/OPENDATA/Trafico/MapServer/228/query"
                + "?where=1%3D1"
                + "&outFields=*"
                + "&returnGeometry=true"
                + "&f=json";

        values = new String[numEst];
        for (int i = 0; i < numEst; i++) {
            values[i] = "";
        }
    }

    public void mostrarDatos(int nE) {

        numEst = nE;
        datos = "";

        API_URL = "https://geoportal.valencia.es/server/rest/services/OPENDATA/Trafico/MapServer/228/query"
                + "?where=1%3D1"
                + "&outFields=*"
                + "&returnGeometry=true"
                + "&f=json";

        int number;
        String nombre;
        int bicis;
        int anclajes;
        double x, y;

        values = new String[numEst];
        for (int i = 0; i < numEst; i++) {
            values[i] = "";
        }

        String coords = "";
        String lat = "", lon = "";
        String[] partes;

        if (API_URL.isEmpty()) {
            setDatos(getDatos().concat("La URL de la API no está especificada."));
            return;
        }

        try {
            // Configuración SSL para confiar en el certificado del geoportal
            SSLContext sslContext = SSLContexts.custom()
                    .loadTrustMaterial(null, new TrustAllStrategy())
                    .build();

            SSLConnectionSocketFactory sslSocketFactory = new SSLConnectionSocketFactory(
                    sslContext, NoopHostnameVerifier.INSTANCE);

            try (CloseableHttpClient httpClient = HttpClients.custom()
                    .setSSLSocketFactory(sslSocketFactory)
                    .build()) {

                HttpGet request = new HttpGet(API_URL);
                HttpResponse response = httpClient.execute(request);
                HttpEntity entity = response.getEntity();

                if (entity != null) {
                    String result = EntityUtils.toString(entity);

                    try {
                        JSONObject jsonObject = new JSONObject(result);
                        JSONArray features = jsonObject.getJSONArray("features");

                        // BUCLE SENCILLO
                        for (int i = 0; i < numEst && i < features.length(); i++) {

                            JSONObject feature = features.getJSONObject(i);
                            JSONObject attributes = feature.getJSONObject("attributes");

                            number = attributes.optInt("number", 0);
                            nombre = attributes.optString("address", "Desconocida");
                            bicis = attributes.optInt("available", 0);
                            anclajes = attributes.optInt("free", 0);

                            JSONObject geom = feature.getJSONObject("geometry");
                            x = geom.optDouble("x", 0);
                            y = geom.optDouble("y", 0);

                            // Conversion coordenadas UTM a GPS
                            coords = ConversionGeoLongLat.conversion(x, y);
                            partes = coords.split(",");
                            lat = partes[0].trim();
                            lon = partes[1].trim();

                            // Guardamos los datos para mostrar en el textArea
                            setDatos(getDatos().concat("Estación: " + nombre + "\n"));
                            setDatos(getDatos().concat("Bicicletas disponibles: " + bicis + "\n"));
                            setDatos(getDatos().concat("Espacios disponibles: " + anclajes + "\n"));
                            setDatos(getDatos().concat("Ubicacion_lon: " + lon + "\n"));
                            setDatos(getDatos().concat("Ubicacion_lat: " + lat + "\n\n"));

                            // Preparamos los valores para insertar en la base de datos
                            String open = attributes.optString("open", "F");
                            int estado = open.equals("T") ? 1 : 0;
                            values[i] = "(" + number + ", '" + nombre + "', " + bicis + ", "
                                    + anclajes + ", " + estado
                                    + ", NOW(), ST_GeomFromText('POINT(" + lon + " " + lat + ")', 4326))";
                        }

                    } catch (org.json.JSONException e) {
                        setDatos(getDatos().concat("Error al procesar los datos JSON: " + e.getMessage()));
                    }
                }
            }

        } catch (IOException e) {
            e.printStackTrace();
        } catch (Exception e) {
            e.printStackTrace();
        }
    }

    public String getDatos() {
        return datos;
    }

    public void setDatos(String datos) {
        this.datos = datos;
    }

    public String[] getValues() {
        return values;
    }

    public void setValues(String[] values) {
        this.values = values;
    }

    public int getNumEst() {
        return numEst;
    }

    public void setNumEst(int numEst) {
        this.numEst = numEst;
    }
}