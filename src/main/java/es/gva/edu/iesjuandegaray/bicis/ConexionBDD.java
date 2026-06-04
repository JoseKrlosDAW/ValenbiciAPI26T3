package es.gva.edu.iesjuandegaray.bicis;

import java.sql.Connection;
import java.sql.DriverManager;
import java.sql.SQLException;
import java.sql.Statement;

import java.awt.EventQueue;
import javax.swing.JFrame;
import javax.swing.JPanel;
import javax.swing.border.EmptyBorder;
import javax.swing.JButton;
import javax.swing.JTextArea;
import javax.swing.JTextField;
import javax.swing.JLabel;
import javax.swing.JScrollPane;
import java.awt.event.MouseAdapter;
import java.awt.event.MouseEvent;

public class ConexionBDD extends JFrame {

    private static final long serialVersionUID = 1L;
    private JPanel contentPane;
    private JTextField textFieldNEstaciones;

    private static Connection con;
    private static Statement s;
    private static DatosJSon dJSon;
    private static int numEst = 3;

    private static final String DRIVER = "com.mysql.cj.jdbc.Driver";
    private static final String USER = "test";
    private static final String PASS = "";
    private static final String URL = "jdbc:mysql://localhost:3306/valenbicibd";

    public static void main(String[] args) {
        EventQueue.invokeLater(new Runnable() {
            public void run() {
                try {
                    ConexionBDD frame = new ConexionBDD();
                    frame.setVisible(true);
                } catch (Exception e) {
                    e.printStackTrace();
                }
            }
        });
    }

    public ConexionBDD() {
        dJSon = new DatosJSon(numEst);
        setDefaultCloseOperation(JFrame.EXIT_ON_CLOSE);
        setBounds(100, 100, 450, 379);
        contentPane = new JPanel();
        contentPane.setBorder(new EmptyBorder(5, 5, 5, 5));
        setContentPane(contentPane);
        contentPane.setLayout(null);

        // TextArea con ScrollPane
        JTextArea textAreaDatos = new JTextArea();
        JScrollPane scrollPane = new JScrollPane(textAreaDatos);
        scrollPane.setBounds(179, 64, 215, 107);
        scrollPane.setVerticalScrollBarPolicy(JScrollPane.VERTICAL_SCROLLBAR_ALWAYS);
        scrollPane.setHorizontalScrollBarPolicy(JScrollPane.HORIZONTAL_SCROLLBAR_NEVER);
        contentPane.add(scrollPane);

        // Campo de texto para número de estaciones
        textFieldNEstaciones = new JTextField();
        textFieldNEstaciones.setBounds(314, 11, 86, 20);
        contentPane.add(textFieldNEstaciones);
        textFieldNEstaciones.setColumns(10);
        textFieldNEstaciones.setText("" + numEst);

        // Labels
        JLabel lblNewLabel = new JLabel("Introduce el número de estaciones a consultar:");
        lblNewLabel.setBounds(10, 14, 275, 14);
        contentPane.add(lblNewLabel);

        JLabel lblNewLabel_1 = new JLabel("Obtener Datos de Estaciones:");
        lblNewLabel_1.setBounds(179, 39, 221, 14);
        contentPane.add(lblNewLabel_1);

        JLabel lblNewLabel_2 = new JLabel("Estado Conexión:");
        lblNewLabel_2.setBounds(179, 202, 215, 14);
        contentPane.add(lblNewLabel_2);

        JLabel lblNewLabel_3 = new JLabel("Primero Obtener Datos de Estaciones y Conectar con BDD");
        lblNewLabel_3.setBounds(146, 254, 278, 14);
        contentPane.add(lblNewLabel_3);

        // BOTON DATOS
        JButton btnNewButtonDatos = new JButton("Datos");
        btnNewButtonDatos.addMouseListener(new MouseAdapter() {
            @Override
            public void mouseClicked(MouseEvent e) {
                numEst = Integer.parseInt(textFieldNEstaciones.getText());
                dJSon = new DatosJSon(numEst);
                dJSon.mostrarDatos(numEst);
                textAreaDatos.setText(dJSon.getDatos());
            }
        });
        btnNewButtonDatos.setBounds(10, 45, 111, 23);
        contentPane.add(btnNewButtonDatos);

        // BOTON CONECTAR
        JButton btnNewButtonConectar = new JButton("Conectar");
        btnNewButtonConectar.addMouseListener(new MouseAdapter() {
            @Override
            public void mouseClicked(MouseEvent e) {
                con = conector();
                if (con != null) {
                    lblNewLabel_2.setText("Conexion establecida");
                } else {
                    lblNewLabel_2.setText("Error en la conexion");
                }
            }
        });
        btnNewButtonConectar.setBounds(10, 188, 111, 23);
        contentPane.add(btnNewButtonConectar);

        // BOTON AÑADIR A BDD
        JButton btnNewButtonAdd = new JButton("Añadir a BDD");
        btnNewButtonAdd.addMouseListener(new MouseAdapter() {
            @Override
            public void mouseClicked(MouseEvent e) {
                if (con != null && dJSon != null) {
                    String[] values = dJSon.getValues();
                    for (int i = 0; i < values.length; i++) {
                        if (!values[i].isEmpty()) {
                            String sql = "INSERT INTO historico (estacion_id, direccion, bicis_disponibles, anclajes_libres, estado_operativo, fecha_registro, ubicacion) VALUES " + values[i];
                            try {
                                s.executeUpdate(sql);
                            } catch (SQLException ex) {
                                ex.printStackTrace();
                            }
                        }
                    }
                    lblNewLabel_3.setText("Datos añadidos a la BDD");
                } else {
                    lblNewLabel_3.setText("Primero Obtener Datos y Conectar");
                }
            }
        });
        btnNewButtonAdd.setBounds(10, 250, 111, 23);
        contentPane.add(btnNewButtonAdd);

        // BOTON CERRAR CONEXION
        JButton btnNewButtonCerrarConex = new JButton("Cerrar Conexión");
        btnNewButtonCerrarConex.addMouseListener(new MouseAdapter() {
            @Override
            public void mouseClicked(MouseEvent e) {
                try {
                    if (con != null) {
                        con.close();
                        lblNewLabel_2.setText("Conexion cerrada");
                    }
                } catch (SQLException ex) {
                    ex.printStackTrace();
                }
            }
        });
        btnNewButtonCerrarConex.setBounds(191, 289, 124, 23);
        contentPane.add(btnNewButtonCerrarConex);
    }

    public Connection conector() {
        con = null;
        try {
            Class.forName(DRIVER);
            con = (Connection) DriverManager.getConnection(URL, USER, PASS);
            s = con.createStatement();
            return con;
        } catch (ClassNotFoundException | SQLException e) {
            return null;
        }
    }
}