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
  SecurityQuestion VARCHAR(255) NOT NULL,
  SecurityAnswerHash VARCHAR(255) NOT NULL,
  LastLogin DATETIME DEFAULT NULL,
  LastLoginIP VARCHAR(45) DEFAULT NULL,
  LastFailedLogin DATETIME DEFAULT NULL,
  LastFailedIP VARCHAR(45) DEFAULT NULL,
  Created_At TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FailedAttempts INT DEFAULT 0,
  LockoutUntil DATETIME NULL,
  LastPasswordChange DATETIME DEFAULT NULL
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

CREATE TABLE USER_PASSWORD_HISTORY (
    id INT AUTO_INCREMENT PRIMARY KEY,
    userID VARCHAR(10) NOT NULL,
    PasswordHash VARCHAR(255) NOT NULL,
    ChangedAt DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (userID) REFERENCES USERS(userID) ON DELETE CASCADE
);

ALTER TABLE USERS
ADD COLUMN LastPasswordChange DATETIME DEFAULT NULL;



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

CREATE TABLE currencies (
code VARCHAR(5) PRIMARY KEY,
symbol VARCHAR(5),
exchange_rate_to_php DECIMAL(10, 4) DEFAULT 1.0000,
updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);


DELIMITER //
CREATE PROCEDURE get_customer_summary(
    IN p_userID VARCHAR(10)
)
BEGIN
    SELECT 
        U.FirstName,
        U.LastName,
        U.Email,
        COUNT(DISTINCT C.cartID) as total_orders,
        SUM(CASE WHEN C.Currency = 'PHP' THEN C.Total ELSE 0 END) as total_spent_php,
        MAX(C.Order_Date) as last_order_date,
        GROUP_CONCAT(DISTINCT C.MOP) as preferred_payment_methods,
        COUNT(DISTINCT CASE WHEN C.Status = 'Delivered' THEN C.cartID END) as completed_orders,
        COUNT(DISTINCT CASE WHEN C.Status = 'To Ship' THEN C.cartID END) as pending_orders
    FROM USERS U
    LEFT JOIN CART C ON U.userID = C.ref_userID AND C.Purchased = TRUE
    WHERE U.userID = p_userID
    GROUP BY U.userID;
END //
DELIMITER ;


DELIMITER //
CREATE PROCEDURE convert_cart_currency(
    IN p_cartID VARCHAR(10),
    IN p_new_currency ENUM('PHP', 'USD', 'KRW'),
    OUT p_converted_total DECIMAL(10,2)
)
BEGIN
    DECLARE base_total DECIMAL(10,2);
    DECLARE exchange_rate DECIMAL(10,4);
    
    SELECT Total INTO base_total
    FROM CART 
    WHERE cartID = p_cartID;
    
    SELECT exchange_rate_to_php INTO exchange_rate
    FROM currencies
    WHERE code = p_new_currency;
      
    SET p_converted_total = base_total * exchange_rate;
    
    UPDATE CART 
    SET Total = p_converted_total,
        Currency = p_new_currency
    WHERE cartID = p_cartID;
    
END //
DELIMITER ;


DELIMITER //
CREATE PROCEDURE get_user_orders(IN p_userID VARCHAR(10))
BEGIN
    SELECT 
        C.cartID,
        C.Total,
        C.Currency,
        C.MOP,
        C.Status,
        C.Order_Date,
        C.Ship_By_Date,
        GROUP_CONCAT(
            CONCAT(P.ProductName, ' (', CI.QuantityOrdered, ')') 
            SEPARATOR '; '
        ) as Products
    FROM CART C
    LEFT JOIN CART_ITEMS CI ON C.cartID = CI.ref_cartID
    LEFT JOIN PRODUCT P ON CI.ref_productID = P.productID
    WHERE C.ref_userID = p_userID AND C.Purchased = TRUE
    GROUP BY C.cartID
    ORDER BY C.Order_Date DESC;
END //
DELIMITER ;

DELIMITER //
CREATE PROCEDURE update_cart_total(IN p_cartID VARCHAR(10))
BEGIN
    UPDATE CART C
    SET C.Total = (
        SELECT COALESCE(SUM(P.Price * CI.QuantityOrdered), 0)
        FROM CART_ITEMS CI
        JOIN PRODUCT P ON CI.ref_productID = P.productID
        WHERE CI.ref_cartID = p_cartID
    )
    WHERE C.cartID = p_cartID;
END //
DELIMITER ;

DELIMITER //
CREATE PROCEDURE check_product_stock(
    IN p_productID VARCHAR(10),
    IN p_size VARCHAR(20),
    OUT p_available INT,
    OUT p_product_name VARCHAR(200)
)
BEGIN
    SELECT QuantityAvail, ProductName 
    INTO p_available, p_product_name
    FROM PRODUCT 
    WHERE productID = p_productID AND Size = p_size;
END //
DELIMITER ;






CREATE USER 'admin_user'@'localhost';
GRANT ALL PRIVILEGES ON dbadm.* TO 'admin_user'@'localhost'; 


CREATE USER 'staff_user'@'localhost';

-- Full CRUD access
GRANT SELECT, INSERT, UPDATE, DELETE ON dbadm.PRODUCT TO 'staff_user'@'localhost';
GRANT SELECT, INSERT, UPDATE, DELETE ON dbadm.CART TO 'staff_user'@'localhost';
GRANT SELECT, INSERT, UPDATE, DELETE ON dbadm.CART_ITEMS TO 'staff_user'@'localhost';
GRANT SELECT, INSERT, UPDATE, DELETE ON dbadm.CART_AUDIT TO 'staff_user'@'localhost';
GRANT SELECT, INSERT, UPDATE, DELETE ON dbadm.CART_ITEMS_AUDIT TO 'staff_user'@'localhost';
GRANT SELECT ON dbadm.PRODUCT_DELETE_AUDIT TO 'staff_user'@'localhost';
GRANT SELECT ON dbadm.PRODUCT_EDIT_AUDIT TO 'staff_user'@'localhost';
-- Read-only access
GRANT SELECT ON dbadm.USERS TO 'staff_user'@'localhost';
GRANT SELECT ON dbadm.currencies TO 'staff_user'@'localhost';

-- Basic usage (ensures the account exists)
GRANT USAGE ON *.* TO 'staff_user'@'localhost';

-- PRODUCT: Read + column-specific update + references
GRANT SELECT, INSERT, UPDATE (QuantityAvail), REFERENCES ON dbadm.PRODUCT TO 'staff_user'@'localhost';
GRANT SELECT, INSERT, REFERENCES ON dbadm.USER_PASSWORD_HISTORY TO 'staff_user'@'localhost';

-- USERS: Read + update
GRANT SELECT, UPDATE ON dbadm.USERS TO 'staff_user'@'localhost';

-- Read-only tables
GRANT SELECT ON dbadm.cart_audit TO 'staff_user'@'localhost';
GRANT SELECT ON dbadm.cart_items_audit TO 'staff_user'@'localhost';
GRANT SELECT ON dbadm.currencies TO 'staff_user'@'localhost';

-- Stored procedures
GRANT EXECUTE ON PROCEDURE dbadm.check_product_stock TO 'staff_user'@'localhost';
GRANT EXECUTE ON PROCEDURE dbadm.get_user_orders TO 'staff_user'@'localhost';
GRANT EXECUTE ON PROCEDURE dbadm.update_cart_total TO 'staff_user'@'localhost';
GRANT EXECUTE ON PROCEDURE dbadm.get_customer_summary TO 'staff_user'@'localhost';
GRANT EXECUTE ON PROCEDURE dbadm.convert_cart_currency TO 'staff_user'@'localhost';


CREATE USER 'customer_user'@'localhost';

-- Basic usage (ensures the account exists)
GRANT USAGE ON *.* TO 'customer_user'@'localhost';

-- CART and CART_ITEMS: Full CRUD
GRANT SELECT, INSERT, UPDATE, DELETE ON dbadm.CART TO 'customer_user'@'localhost';
GRANT SELECT, INSERT, UPDATE, DELETE ON dbadm.CART_ITEMS TO 'customer_user'@'localhost';

-- PRODUCT: Read + column-specific update + references
GRANT SELECT, INSERT, UPDATE (QuantityAvail), REFERENCES ON dbadm.PRODUCT TO 'customer_user'@'localhost';
GRANT SELECT, INSERT, REFERENCES ON dbadm.USER_PASSWORD_HISTORY TO 'customer_user'@'localhost';

-- USERS: Read + update
GRANT SELECT, UPDATE ON dbadm.USERS TO 'customer_user'@'localhost';

-- Read-only tables
GRANT SELECT ON dbadm.cart_audit TO 'customer_user'@'localhost';
GRANT SELECT ON dbadm.cart_items_audit TO 'customer_user'@'localhost';
GRANT SELECT ON dbadm.currencies TO 'customer_user'@'localhost';

-- Stored procedures
GRANT EXECUTE ON PROCEDURE dbadm.check_product_stock TO 'customer_user'@'localhost';
GRANT EXECUTE ON PROCEDURE dbadm.get_user_orders TO 'customer_user'@'localhost';
GRANT EXECUTE ON PROCEDURE dbadm.update_cart_total TO 'customer_user'@'localhost';
GRANT EXECUTE ON PROCEDURE dbadm.get_customer_summary TO 'customer_user'@'localhost';
GRANT EXECUTE ON PROCEDURE dbadm.convert_cart_currency TO 'customer_user'@'localhost';


-- Create the public user account 
CREATE USER 'public_user'@'localhost';

-- Allow checking if an email already exists
GRANT SELECT (userID, Email) ON dbadm.USERS TO 'public_user'@'localhost';

-- Allow inserting new users (for registration)
GRANT INSERT (userID, FirstName, LastName, Password, Email, Role) 
ON dbadm.USERS TO 'public_user'@'localhost';

FLUSH PRIVILEGES;



FLUSH PRIVILEGES;

In case mag error
DROP USER IF EXISTS 'admin_user'@'localhost';
DROP USER IF EXISTS 'customer_user'@'localhost';

CREATE USER 'admin_user'@'localhost';
GRANT ALL PRIVILEGES ON dbadm.* TO 'admin_user'@'localhost';
FLUSH PRIVILEGES;