/*
DUMP 12.12.14:
lastIns
INSERT
vrs6_bookings
BEGIN
       DECLARE cal_id TEXT DEFAULT NULL;
       SELECT calendar_id
       FROM vrs6_calendars
       WHERE listing_id = NEW.listing_id
       INTO cal_id;
       
       SET NEW.calendar_id = cal_id;
       SET NEW.mtime = NOW();
       
   END
BEFORE
NULL
NO_ENGINE_SUBSTITUTION
dev@localhost
utf8mb4
utf8mb4_general_ci
latin1_swedish_ci
lastUPD
UPDATE
vrs6_bookings
BEGIN
       DECLARE cal_id TEXT DEFAULT NULL;
       SELECT calendar_id
       FROM vrs6_calendars
       WHERE listing_id = NEW.listing_id
       INTO cal_id;
       
       SET NEW.calendar_id = cal_id;
       SET NEW.mtime = NOW();
       
   END
BEFORE
NULL
NO_ENGINE_SUBSTITUTION
dev@localhost
utf8mb4
utf8mb4_general_ci
latin1_swedis

*/

##TRIGGERS

DELIMITER $$

DROP TRIGGER IF EXISTS `lastIns` $$
CREATE TRIGGER `lastIns` BEFORE INSERT ON `vrs6_bookings` 
	FOR EACH ROW BEGIN 
	
	DECLARE cal_id TEXT DEFAULT NULL; 
	
	SELECT calendar_id FROM vrs6_calendars WHERE listing_id = NEW.listing_id INTO cal_id; 
	
	SET NEW.calendar_id = cal_id; 
	SET NEW.mtime = NOW(); 
END$$

DELIMITER ;





COM O UPDATE DE CALENDAR ID E MTIME

DELIMITER $$
DROP TRIGGER IF EXISTS `lastUPD` $$
CREATE
	TRIGGER `lastUPD` BEFORE UPDATE 
	ON `vrs6_bookings` 
	FOR EACH ROW BEGIN
		DECLARE cal_id TEXT DEFAULT NULL;
		
		SELECT calendar_id
        FROM vrs6_calendars
		WHERE listing_id = NEW.listing_id
        INTO cal_id;
		
		SET NEW.calendar_id = cal_id;
		SET NEW.mtime = NOW();
		
    END$$

DELIMITER ;

##Rotinas

DELIMITER $$

DROP PROCEDURE IF EXISTS `insertRoutine` $$
CREATE PROCEDURE `insertRoutine`(
IN inDate VARCHAR(100),
IN outDate VARCHAR(100),
IN listingID INT,
IN eventID VARCHAR(100),
IN calendarID VARCHAR(100),
IN eventName VARCHAR(100),
OUT caso INT,
OUT bID_out INT,
OUT listID_out INT
)
BEGIN
	DECLARE bookingID INT;

	SELECT booking_id INTO bookingID
	FROM vrs6_bookings
	WHERE listing_id=listingID AND ((inDate <  start_date AND outDate <= end_date AND outDate>start_date) OR (inDate >= start_date AND inDate <  end_date AND outDate>=end_date) OR (inDate >= start_date AND inDate <  end_date AND outDate<end_date) OR (inDate <  start_date AND outDate >  end_date)) AND (listing_id=listingID) AND (booking_status<>2);

	IF(bookingID > 0) THEN
		INSERT INTO sync_errors (calendar_id, start_date, end_date, conflict_with_bID, listing_id) VALUES (calendarID, inDate, outDate, bookingID, listingID);
		SET caso = 1;
		SET bID_out = bookingID;
		SET listID_out = listingID;
	ELSE
		INSERT INTO vrs6_bookings (user_id, listing_id, blank_booking, admin_status, booking_status, start_date, end_date, event_id, calendar_id, event_name) VALUES ('555', listingID, '1', '1', '1', inDate, outDate, eventID, calendarID, eventName);
		SET caso = 2;
		SET bID_out = bookingID;
		SET listID_out = listingID;
	END IF;
END $$

DELIMITER ;

##just in case

DELIMITER $$

DROP PROCEDURE IF EXISTS `insert_fromGoogle` $$
CREATE PROCEDURE `insert_fromGoogle`(
IN inDate VARCHAR(100),
IN outDate VARCHAR(100),
IN listingID INT,
IN eventID VARCHAR(100),
IN calendarID VARCHAR(100),
IN eventName VARCHAR(100),
OUT caso INT,
OUT str TEXT
)
BEGIN
	DECLARE bookingID INT;

	SELECT booking_id INTO bookingID
	FROM vrs6_bookings
	WHERE listing_id=listingID AND (inDate <  start_date AND outDate <= end_date AND outDate>start_date) OR (inDate >= start_date AND inDate <  end_date AND outDate>=end_date) OR (inDate >= start_date AND inDate <  end_date AND outDate<end_date) OR (inDate <  start_date AND outDate >  end_date);

	IF(bookingID > 0) THEN
		INSERT INTO sync_errors (calendar_id, start_date, end_date, conflict_with_bID) VALUES (calendarID, inDate, outDate, bookingID);
		SET caso = 1;
	ELSE
		INSERT INTO vrs6_bookings (user_id, listing_id, blank_booking, admin_status, booking_status, start_date, end_date, event_id, calendar_id, event_name) VALUES ('555', listingID, '1', '1', '1', inDate, outDate, eventID, calendarID, eventName);
		SET caso = 2;
	END IF;
END $$

DELIMITER ;
