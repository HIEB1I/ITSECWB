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

/*
CREATE DATABASE IF NOT EXISTS dbadm;
USE dbadm;

-- USERS table
CREATE TABLE IF NOT EXISTS USERS (
  userID VARCHAR(10) NOT NULL PRIMARY KEY, -- U00001
  FirstName VARCHAR(200),
  LastName VARCHAR(200),
  Password VARCHAR(200),
  Email VARCHAR(200),
  Address VARCHAR(500),
  Role ENUM('Customer', 'Staff', 'Admin') NOT NULL,
  Created_At TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- PRODUCT table
CREATE TABLE IF NOT EXISTS PRODUCT (
  productID VARCHAR(10) NOT NULL PRIMARY KEY, -- P00001
  ProductName VARCHAR(200),
  Size ENUM('Extra-Small', 'Small', 'Medium', 'Large', 'Extra-Large'),
  Category ENUM ('TEES', 'BOTTOMS', 'LAYERING'),
  Description VARCHAR(500),
  QuantityAvail INT,
  Price DOUBLE,
  Image MEDIUMBLOB
);

-- CART table
CREATE TABLE IF NOT EXISTS CART (
  cartID VARCHAR(10) NOT NULL PRIMARY KEY, -- C00001
  Total DOUBLE,
  Purchased BOOLEAN,
  ref_userID VARCHAR(10) NOT NULL,
  UNIQUE INDEX ref_userID_UNIQUE (ref_userID),
  CONSTRAINT fk_cartuser
    FOREIGN KEY (ref_userID)
    REFERENCES USERS (userID)
    ON DELETE CASCADE
);

-- CART_ITEMS table
CREATE TABLE IF NOT EXISTS CART_ITEMS (
  cartItemsID VARCHAR(10) NOT NULL PRIMARY KEY, -- CI00001
  QuantityOrdered VARCHAR(10) NULL,
  ref_productID VARCHAR(10) NOT NULL,
  ref_cartID VARCHAR(10) NOT NULL,
  CONSTRAINT fk_cartitem_product
    FOREIGN KEY (ref_productID)
    REFERENCES PRODUCT (productID)
    ON DELETE CASCADE,
  CONSTRAINT fk_cartitem_cart
    FOREIGN KEY (ref_cartID)
    REFERENCES CART (cartID)
    ON DELETE CASCADE
);

*/