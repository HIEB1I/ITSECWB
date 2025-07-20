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
  

DROP SCHEMA IF EXISTS dbadm;
-- SCHEMA
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
  CONSTRAINT fk_cartuser
    FOREIGN KEY (ref_userID)
    REFERENCES USERS (userID)
    ON DELETE CASCADE
);

-- CART_ITEMS table
CREATE TABLE IF NOT EXISTS CART_ITEMS (
  cartItemsID VARCHAR(10) NOT NULL PRIMARY KEY, -- CI00001
  QuantityOrdered VARCHAR(10),
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


Cart Purcahsed Log
Logs all the items user purchased



-- CART AUDIT TABLE
CREATE TABLE IF NOT EXISTS CART_AUDIT (
  cartauditID INT PRIMARY KEY AUTO_INCREMENT,
  userID VARCHAR(10),
  cartID VARCHAR(10),
  Total DOUBLE,
  Purchased BOOLEAN
);

-- CART ITEMS AUDIT TABLE
CREATE TABLE IF NOT EXISTS CART_ITEMS_AUDIT (
  cartItemsAuditID INT PRIMARY KEY AUTO_INCREMENT,
  ref_cartauditID INT NOT NULL,
  QuantityOrdered INT,
  productID VARCHAR(10),
  Name VARCHAR(200),
  Size ENUM('Extra-Small', 'Small', 'Medium', 'Large', 'Extra-Large'),
  Category ENUM('TEES', 'BOTTOMS', 'LAYERING'),
  Description VARCHAR(500),
  Price DOUBLE,
  Image MEDIUMBLOB,
  FOREIGN KEY (ref_cartauditID) REFERENCES CART_AUDIT(cartauditID) ON DELETE CASCADE
);

-- TRIGGER FOR AUDIT LOGGING
DELIMITER $$

CREATE TRIGGER cart_checkout_audit
AFTER UPDATE ON CART
FOR EACH ROW
BEGIN
  -- Declare variables FIRST
  DECLARE last_audit_id INT;

  -- Only trigger when cart is marked as purchased
  IF NEW.Purchased = TRUE AND OLD.Purchased = FALSE THEN

    -- Insert into CART_AUDIT
    INSERT INTO CART_AUDIT (userID, cartID, Total, Purchased)
    VALUES (NEW.ref_userID, NEW.cartID, NEW.Total, NEW.Purchased);

    -- Get the last inserted audit ID
    SET last_audit_id = LAST_INSERT_ID();

    -- Insert related items into CART_ITEMS_AUDIT
    INSERT INTO CART_ITEMS_AUDIT (
      ref_cartauditID, QuantityOrdered, productID, Name, Size,
      Category, Description, Price, Image
    )
    SELECT
      last_audit_id, CI.QuantityOrdered, CI.ref_productID, P.ProductName, P.Size,
      P.Category, P.Description, P.Price, P.Image
    FROM
      CART_ITEMS CI
      JOIN PRODUCT P ON CI.ref_productID = P.productID
    WHERE
      CI.ref_cartID = NEW.cartID;

  END IF;
END $$

DELIMITER ;



PRODUCT NAME CHECKER
Checks all the product names and prevents inserting same names



DELIMITER $$

CREATE TRIGGER product_name_checker
BEFORE INSERT ON PRODUCT
FOR EACH ROW
BEGIN
  IF EXISTS (
    SELECT 1 FROM PRODUCT
    WHERE ProductName = NEW.ProductName
      AND productID != NEW.productID
  ) THEN
    SIGNAL SQLSTATE '45000'
    SET MESSAGE_TEXT = 'Product name already exists.';
  END IF;
END $$

DELIMITER ;


EMail CHecker
Email must be Gmail


DELIMITER $$
CREATE TRIGGER  invalid_email
BEFORE INSERT ON USERS
FOR EACH ROW

BEGIN
	IF NEW.Email NOT LIKE'%@gmail.com' THEN
		SIGNAL SQLSTATE '45000'
		SET MESSAGE_TEXT = 'Invalid input. Email must be GMail';
    END IF;
	END

$$ DELIMITER ;




Product Delete Audit
Logs all deleted products


-- PRODUCT_DELETE_AUDIT table
CREATE TABLE IF NOT EXISTS PRODUCT_DELETE_AUDIT (
  archiveID INT PRIMARY KEY AUTO_INCREMENT,
  productID VARCHAR(10), 
  ProductName VARCHAR(200),
  Size ENUM('Extra-Small', 'Small', 'Medium', 'Large', 'Extra-Large'),
  Category ENUM ('TEES', 'BOTTOMS', 'LAYERING'),
  Description VARCHAR(500),
  QuantityAvail INT,
  Price DOUBLE,
  Image MEDIUMBLOB,
  Time_Deleted TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);


-- Deleted Products History Process
DELIMITER $$
CREATE TRIGGER  delete_product
AFTER DELETE ON PRODUCT
FOR EACH ROW
BEGIN
	INSERT INTO PRODUCT_DELETE_AUDIT (productID, ProductName, Size, Category, Description, QuantityAvail, Price, Image)
    VALUES (OLD.productID, OLD.ProductName, OLD.Size, OLD.Category, OLD.Description, OLD.QuantityAvail, OLD.Price, OLD.Image);
END
$$ DELIMITER ;



Product Edit Audit
Logs all edited products


-- Edit Products History
CREATE TABLE IF NOT EXISTS PRODUCT_EDIT_AUDIT (
  archiveID INT PRIMARY KEY AUTO_INCREMENT,
  productID VARCHAR(10),
  Old_ProductName VARCHAR(200),
  New_ProductName VARCHAR(200),
  Old_Price DOUBLE,
  New_Price DOUBLE,
  Old_Quantity INT,
  New_Quantity INT,
  Old_Size ENUM('Extra-Small', 'Small', 'Medium', 'Large', 'Extra-Large'),
  New_Size ENUM('Extra-Small', 'Small', 'Medium', 'Large', 'Extra-Large'),
  Old_Category ENUM('TEES', 'BOTTOMS', 'LAYERING'),
  New_Category ENUM('TEES', 'BOTTOMS', 'LAYERING'),
  Old_Description VARCHAR(500),
  New_Description VARCHAR(500),
  Time_Change TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Edit Products History
DELIMITER $$

CREATE TRIGGER price_product
AFTER UPDATE ON PRODUCT
FOR EACH ROW
BEGIN
  -- Log only if the price, size, quantity, category, or description has changed
  IF OLD.ProductName != NEW.ProductName OR
	 OLD.Price != NEW.Price OR
     OLD.QuantityAvail != NEW.QuantityAvail OR
     OLD.Size != NEW.Size OR
     OLD.Category != NEW.Category OR
     OLD.Description != NEW.Description THEN

    INSERT INTO PRODUCT_EDIT_AUDIT (
      productID, 
      Old_ProductName, New_ProductName,
      Old_Price, New_Price,
      Old_Quantity, New_Quantity,
      Old_Size, New_Size,
      Old_Category, New_Category,
      Old_Description, New_Description
    )
    VALUES (
      OLD.productID, 
      OLD.ProductName, NEW.ProductName,
      OLD.Price, NEW.Price,
      OLD.QuantityAvail, NEW.QuantityAvail,
      OLD.Size, NEW.Size,
      OLD.Category, NEW.Category,
      OLD.Description, NEW.Description
    );

  END IF;
END $$

DELIMITER ;









*/