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
CREATE DATABASE IF NOT EXISTS dbadm;
USE dbadm;

CREATE TABLE IF NOT EXISTS USERS (
  userID VARCHAR(10) NOT NULL PRIMARY KEY, 
  FirstName VARCHAR(200),
  LastName VARCHAR(200),
  Password VARCHAR(200),
  Email VARCHAR(200),
  Address VARCHAR(500),
  Role ENUM('Customer', 'Staff', 'Admin') NOT NULL,
  Created_At TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);


CREATE TABLE IF NOT EXISTS PRODUCT (
  productID VARCHAR(10) NOT NULL PRIMARY KEY,
  ProductName VARCHAR(200),
  Size ENUM('Extra-Small', 'Small', 'Medium', 'Large', 'Extra-Large'),
  Category ENUM ('TEES', 'BOTTOMS', 'LAYERING'),
  Description VARCHAR(500),
  QuantityAvail INT,
  Price DOUBLE,
  Image MEDIUMBLOB
);


CREATE TABLE IF NOT EXISTS CART (
  cartID VARCHAR(10) NOT NULL PRIMARY KEY,
  Total DOUBLE,
  Purchased BOOLEAN,
  Status ENUM('On-Going','To Ship', 'Delivered') DEFAULT 'On-Going',
  Currency ENUM('PHP', 'USD', 'WON'),
  MOP ENUM('COD', 'GCASH', 'CARD'),  
  Order_Date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,         
  Ship_By_Date TIMESTAMP NULL,
  ref_userID VARCHAR(10) NOT NULL,
  CONSTRAINT fk_cartuser
    FOREIGN KEY (ref_userID)
    REFERENCES USERS (userID)
    ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS CART_ITEMS (
  cartItemsID VARCHAR(10) NOT NULL PRIMARY KEY, 
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

CREATE TABLE IF NOT EXISTS CART_AUDIT (
  cartauditID INT PRIMARY KEY AUTO_INCREMENT,
  userID VARCHAR(10),
  cartID VARCHAR(10),
  Total DOUBLE,
  Currency ENUM('PHP', 'USD', 'WON'),
  MOP ENUM('COD', 'GCASH', 'CARD'),
  Status ENUM('On-Going','To Ship', 'Delivered'),
  Order_Date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  Ship_By_Date TIMESTAMP NULL,
  Purchased BOOLEAN
);

CREATE TABLE IF NOT EXISTS CART_ITEMS_AUDIT (
  cartItemsAuditID INT PRIMARY KEY AUTO_INCREMENT,
  ref_cartauditID INT NOT NULL,
  QuantityOrdered INT,
  Name VARCHAR(200),
  Size ENUM('Extra-Small', 'Small', 'Medium', 'Large', 'Extra-Large'),
  Description VARCHAR(500),
  Price DOUBLE,
  FOREIGN KEY (ref_cartauditID) REFERENCES CART_AUDIT(cartauditID) ON DELETE CASCADE
);

DELIMITER $$

CREATE TRIGGER cart_checkout_audit
AFTER UPDATE ON CART
FOR EACH ROW
BEGIN
  DECLARE last_audit_id INT;

  IF NEW.Status = 'Delivered' AND NEW.Purchased IS TRUE THEN
    INSERT INTO CART_AUDIT (
      userID, cartID, Total, Currency, MOP, Status, Ship_By_Date, Purchased
    )
    VALUES (
      NEW.ref_userID, NEW.cartID, NEW.Total, NEW.Currency, NEW.MOP,
      NEW.Status, NEW.Ship_By_Date, NEW.Purchased
    );

    SET last_audit_id = LAST_INSERT_ID();
    
    INSERT INTO CART_ITEMS_AUDIT (
      ref_cartauditID, QuantityOrdered, Name, Size, Description, Price
    )
    SELECT
      last_audit_id, CI.QuantityOrdered, P.ProductName, P.Size,
      P.Description, P.Price
    FROM
      CART_ITEMS CI
      JOIN PRODUCT P ON CI.ref_productID = P.productID
    WHERE
      CI.ref_cartID = NEW.cartID;
  END IF;
END $$

DELIMITER ;

DELIMITER $$

CREATE TRIGGER product_quantity_checker
BEFORE UPDATE ON PRODUCT
FOR EACH ROW
BEGIN
  IF NEW.QuantityAvail < 0 THEN
    SIGNAL SQLSTATE '45000'
    SET MESSAGE_TEXT = 'Quantity Available cannot be negative. Please enter a valid number.';
  END IF;
END $$

DELIMITER ;

DELIMITER $$
CREATE TRIGGER invalid_email
BEFORE INSERT ON USERS
FOR EACH ROW
BEGIN
  IF NEW.Email NOT LIKE '%@gmail.com'
     OR EXISTS (SELECT 1 FROM USERS WHERE Email = NEW.Email) THEN
    SIGNAL SQLSTATE '45000'
    SET MESSAGE_TEXT = 'Invalid input. Email must be a unique GMail address';
  END IF;
END$$
DELIMITER ;


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

DELIMITER $$
CREATE TRIGGER  log_product_deletion
AFTER DELETE ON PRODUCT
FOR EACH ROW
BEGIN
	INSERT INTO PRODUCT_DELETE_AUDIT (productID, ProductName, Size, Category, Description, QuantityAvail, Price, Image)
    VALUES (OLD.productID, OLD.ProductName, OLD.Size, OLD.Category, OLD.Description, OLD.QuantityAvail, OLD.Price, OLD.Image);
END
$$ DELIMITER ;

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

DELIMITER $$

CREATE TRIGGER log_product_update
AFTER UPDATE ON PRODUCT
FOR EACH ROW
BEGIN
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

/* 
CREATE USER 'admin_user'@'localhost';
GRANT ALL PRIVILEGES ON dbadm.* TO 'admin_user'@'localhost'; 
*/

/* 
CREATE USER 'staff_user'@'localhost';
GRANT SELECT, INSERT, UPDATE, DELETE ON dbadm.PRODUCT TO 'staff_user'@'localhost';
GRANT SELECT, INSERT, UPDATE, DELETE ON dbadm.CART TO 'staff_user'@'localhost';
*/

/*
CREATE USER 'customer_user'@'localhost';
GRANT SELECT ON dbadm.PRODUCT TO 'customer_user'@'localhost';
GRANT SELECT, INSERT, UPDATE, DELETE ON dbadm.CART TO 'customer_user'@'localhost';
GRANT SELECT, INSERT, UPDATE, DELETE ON dbadm.CART_ITEMS TO 'customer_user'@'localhost';
GRANT UPDATE (QuantityAvail) ON dbadm.PRODUCT TO 'customer_user'@'localhost';

FLUSH PRIVILEGES;
*/


/* 
In case mag error
DROP USER IF EXISTS 'admin_user'@'localhost';

CREATE USER 'admin_user'@'localhost';
GRANT ALL PRIVILEGES ON dbadm.* TO 'admin_user'@'localhost';
FLUSH PRIVILEGES;

*/






