package es.gva.edu.iesjuandegaray.bicis;

import org.locationtech.proj4j.*;
import org.locationtech.proj4j.CRSFactory;
import org.locationtech.proj4j.CoordinateReferenceSystem;
import org.locationtech.proj4j.CoordinateTransform;
import org.locationtech.proj4j.CoordinateTransformFactory;
import org.locationtech.proj4j.ProjCoordinate;

public class ConversionGeoLongLat {

	public static String conversion(double xGeom, double yGeom){
	// Sistema origen: UTM zona 30N (Valencia)
	CRSFactory crsFactory = new CRSFactory();
	CoordinateReferenceSystem utm = crsFactory.createFromParameters(
	"ETRS89_UTM30",
	"+proj=utm +zone=30 +datum=WGS84 +units=m +no_defs"
	);
	
	// Sistema destino: Lat/Lon
	CoordinateReferenceSystem wgs84 = crsFactory.createFromParameters(
	"WGS84",
	"+proj=longlat +datum=WGS84 +no_defs"
	);
	
	// Transformación
	CoordinateTransformFactory ctFactory = new CoordinateTransformFactory();
	CoordinateTransform transform = ctFactory.createTransform(utm, wgs84);
	// Coordenadas UTM (ejemplo)
	ProjCoordinate utmCoord = new ProjCoordinate(xGeom, yGeom);
	ProjCoordinate latLon = new ProjCoordinate();
	transform.transform(utmCoord, latLon);
	System.out.println("Latitud: " + latLon.y);
	System.out.println("Longitud: " + latLon.x);
	return latLon.y + ", " + latLon.x;
	}
}