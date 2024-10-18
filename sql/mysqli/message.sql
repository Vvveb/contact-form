-- Categories

	-- Get one message

	CREATE PROCEDURE get(
		IN message_id INT,
		OUT fetch_row, 
	)
	BEGIN
		-- message
		SELECT *
			FROM message as _ -- (underscore) _ means that data will be kept in main array
		WHERE message_id = :message_id LIMIT 1;
	END

	-- Edit message

	CREATE PROCEDURE edit(
		IN message ARRAY,
		IN message_id INT,
		OUT affected_rows
	)
	BEGIN
		-- allow only table fields and set defaults for missing values
		@FILTER(:message, message)
		
		UPDATE message 
			
			SET @LIST(:message) 
			
		WHERE message_id = :message_id
	END	

	-- Add new message

	CREATE PROCEDURE add(
		IN message ARRAY,
		OUT insert_id
	)
	BEGIN
		
		-- allow only table fields and set defaults for missing values
		:message  = @FILTER(:message, message)

		INSERT INTO message 
		
			( @KEYS(:message) )
			
		VALUES ( :message );			

	END


	-- Get all messages 

	CREATE PROCEDURE getAll(

		-- variables
		IN  language_id INT,
		IN  user_group_id INT,
		IN  site_id INT,
		IN  search CHAR,
		
		-- pagination
		IN  start INT,
		IN  limit INT,
			
		-- return array of messages for messages query
		OUT fetch_all,
		-- return messages count for count query
		OUT fetch_one,
	)
	BEGIN

		SELECT * 
			FROM message 
		ORDER BY message_id DESC
			
		LIMIT :limit OFFSET :start;
		
		SELECT count(*) FROM (
			
			@SQL_COUNT(message.message_id, message) -- this takes previous query removes limit and replaces select columns with parameter product_id
			
		) as count;


	END


	-- Delete message

	CREATE PROCEDURE delete(
		IN  message_id ARRAY,
		OUT affected_rows
	)
	BEGIN

		DELETE FROM message WHERE message_id IN (:message_id)
	 
	END

	-- Get number of messages with specified status

	CREATE PROCEDURE getStatusCount(
		IN status INT,
		OUT fetch_row, 
	)
	BEGIN
		-- message
		SELECT count(*) as count
			FROM message as _ -- (underscore) _ means that data will be kept in main array
		WHERE status = :status LIMIT 1;
	END
