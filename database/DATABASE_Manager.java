package database;

import java.sql.Connection;
import java.sql.DriverManager;

public class DATABASE_Manager {
    public static Connection getConnection() throws Exception {
        String url = "jdbc:mysql://localhost:3306/dbadm";
        String user = "root";
        String password = "password1";

        Class.forName("com.mysql.cj.jdbc.Driver");
        return DriverManager.getConnection(url, user, password);
    }
}
